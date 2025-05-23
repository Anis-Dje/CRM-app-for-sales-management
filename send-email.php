<?php
session_start();
include 'C:/xampp/htdocs/PPD/php/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if vendor/autoload.php exists
if (!file_exists('vendor/autoload.php')) {
    die('PHPMailer not installed. Please run: composer install');
}

require 'vendor/autoload.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['email'])) {
    $order_id = intval($_POST['order_id']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    if (!$email) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Invalid email address']));
    }

    try {
        // Fetch order details
        $order_query = "SELECT co.*, c.customer_name 
                       FROM customer_orders co 
                       JOIN customers c ON co.customer_id = c.customer_id 
                       WHERE co.order_id = ?";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'Order not found']));
        }

        // Fetch order items from order_items table (more reliable than cart)
        $items_query = "SELECT oi.*, p.product_title 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.product_id 
                       WHERE oi.order_id = ?";
        $stmt = $conn->prepare($items_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        $items = $items_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // If no items in order_items, fallback to cart table
        if (empty($items)) {
            $items_query = "SELECT c.*, p.product_title 
                           FROM cart c 
                           JOIN products p ON c.p_id = p.product_id 
                           WHERE c.customer_id = ? AND c.status = 'ORDERED'
                           ORDER BY c.cart_id DESC";
            $stmt = $conn->prepare($items_query);
            $stmt->bind_param("i", $order['customer_id']);
            $stmt->execute();
            $items_result = $stmt->get_result();
            $items = $items_result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        // Fetch customer details
        $customer_query = "SELECT * FROM customers WHERE customer_id = ?";
        $stmt = $conn->prepare($customer_query);
        $stmt->bind_param("i", $order['customer_id']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$customer) {
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'Customer not found']));
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($items as $item) {
            $price = isset($item['price']) ? $item['price'] : $item['p_price'];
            $qty = isset($item['quantity']) ? $item['quantity'] : $item['qty'];
            $subtotal += $price * $qty;
        }

        // Create HTML email content
        $subject = "Order Confirmation - TechMarket Order #$order_id";
        
        $html_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2ecc71; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .order-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .item { border-bottom: 1px solid #eee; padding: 10px 0; }
                .item:last-child { border-bottom: none; }
                .total { font-weight: bold; font-size: 18px; color: #2ecc71; }
                .footer { text-align: center; padding: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛒 TechMarket</h1>
                    <h2>Order Confirmation</h2>
                </div>
                
                <div class='content'>
                    <p>Dear " . htmlspecialchars($customer['customer_name']) . ",</p>
                    <p>Thank you for your purchase! Your order has been confirmed and is being processed.</p>
                    
                    <div class='order-details'>
                        <h3>Order Details</h3>
                        <p><strong>Order ID:</strong> #" . htmlspecialchars($order['order_id']) . "</p>
                        <p><strong>Order Date:</strong> " . date('F j, Y', strtotime($order['order_date'])) . "</p>
                        <p><strong>Status:</strong> " . ucfirst(htmlspecialchars($order['order_status'])) . "</p>
                        
                        <h4>Shipping Address:</h4>
                        <p>" . htmlspecialchars($order['shipping_address']) . "<br>
                        " . htmlspecialchars($order['city']) . ", " . htmlspecialchars($order['postal_code']) . "<br>
                        Phone: " . htmlspecialchars($order['phone']) . "</p>
                    </div>
                    
                    <div class='order-details'>
                        <h3>Order Items</h3>";
        
        foreach ($items as $item) {
            $price = isset($item['price']) ? $item['price'] : $item['p_price'];
            $qty = isset($item['quantity']) ? $item['quantity'] : $item['qty'];
            $item_total = $price * $qty;
            
            $html_body .= "
                        <div class='item'>
                            <strong>" . htmlspecialchars($item['product_title']) . "</strong><br>
                            Quantity: " . $qty . " × " . number_format($price, 0) . " DA = " . number_format($item_total, 0) . " DA
                        </div>";
        }
        
        $html_body .= "
                        <div class='item total'>
                            <strong>Total: " . number_format($order['due_amount'], 0) . " DA</strong>
                        </div>
                    </div>
                    
                    <p>We'll send you another email when your order ships. If you have any questions, please contact our support team.</p>
                </div>
                
                <div class='footer'>
                    <p>Thank you for shopping with TechMarket!</p>
                    <p>📧 support@techmarket.com | 📞 +213 XXX XXX XXX</p>
                </div>
            </div>
        </body>
        </html>";

        // Plain text version
        $text_body = "Dear " . $customer['customer_name'] . ",\n\n";
        $text_body .= "Thank you for your purchase! Below are the details of your order:\n\n";
        $text_body .= "Order ID: #" . $order['order_id'] . "\n";
        $text_body .= "Order Date: " . date('F j, Y', strtotime($order['order_date'])) . "\n";
        $text_body .= "Status: " . ucfirst($order['order_status']) . "\n";
        $text_body .= "Total Amount: " . number_format($order['due_amount'], 0) . " DA\n\n";
        $text_body .= "Items:\n";
        foreach ($items as $item) {
            $price = isset($item['price']) ? $item['price'] : $item['p_price'];
            $qty = isset($item['quantity']) ? $item['quantity'] : $item['qty'];
            $text_body .= "- " . $item['product_title'] . " (Qty: " . $qty . ", Price: " . number_format($price, 0) . " DA)\n";
        }
        $text_body .= "\nShipping Address:\n" . $order['shipping_address'] . "\n" . $order['city'] . ", " . $order['postal_code'] . "\n";
        $text_body .= "\nFor any inquiries, contact us at support@techmarket.com.\n\nBest regards,\nTechMarket Team";

        // PHPMailer setup
        $mail = new PHPMailer(true);
        
        // Enable SMTP debugging to see detailed errors
        // 0 = off (production)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug = 2; // Set to 2 for detailed debugging, change to 0 in production

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Replace with your actual Gmail address
        $mail->Password = 'your-app-password';    // Replace with your actual app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // or ENCRYPTION_SMTPS for port 465
        $mail->Port = 587; // or 465 for ENCRYPTION_SMTPS

        // Optional: Set timeout values if the connection is slow
        $mail->Timeout = 60; // seconds
        $mail->SMTPKeepAlive = true; // SMTP connection will not close after each email sent

        // Recipients
        $mail->setFrom('support@techmarket.com', 'TechMarket Support');
        $mail->addAddress($email, $customer['customer_name']);
        $mail->addReplyTo('support@techmarket.com', 'TechMarket Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        $mail->AltBody = $text_body;

        $mail->send();
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Order confirmation email sent successfully!',
            'order_id' => $order_id,
            'email' => $email
        ]);

    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send email: ' . $e->getMessage()
        ]);
    }

} else {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request. Missing order_id or email.'
    ]);
}
?>
