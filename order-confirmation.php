<?php
session_start();
include 'C:/xampp/htdocs/PPD/php/config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = $_GET['order_id'];
$customer_id = $_SESSION['customer_id'];

// Fetch order details
$order_query = "SELECT * FROM customer_orders WHERE order_id = ? AND customer_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Get order items from order_items table first
$items_query = "SELECT oi.*, p.product_title 
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$stmt->close();

// If no items found in order_items, fall back to cart table
if ($items_result->num_rows == 0) {
    $items_query = "SELECT c.*, p.product_title 
                    FROM cart c 
                    JOIN products p ON c.p_id = p.product_id 
                    WHERE c.customer_id = ? AND c.status = 'ORDERED'";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $stmt->close();
}

$items = $items_result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    // Handle both order_items and cart table field names
    $price = isset($item['price']) ? $item['price'] : (isset($item['p_price']) ? $item['p_price'] : 0);
    $qty = isset($item['quantity']) ? $item['quantity'] : (isset($item['qty']) ? $item['qty'] : 0);
    $subtotal += $price * $qty;
}

// Safely get order values with defaults
$shipping_cost = isset($order['shipping_cost']) ? $order['shipping_cost'] : 500; // Default to 500 if not set
$discount_amount = isset($order['discount_amount']) ? $order['discount_amount'] : 0; // Default to 0 if not set
$total = isset($order['due_amount']) ? $order['due_amount'] : 0; // Get total directly from order table
$payment_method = isset($order['payment_method']) ? $order['payment_method'] : 'Not specified';
$invoice_no = isset($order['invoice_no']) ? $order['invoice_no'] : 'N/A';
$order_status = isset($order['order_status']) ? $order['order_status'] : 'Unknown';
$order_date = isset($order['order_date']) ? $order['order_date'] : 'N/A';

// Customer details
$customer_query = "SELECT * FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Safely get customer values with defaults
$customer_name = isset($customer['customer_name']) ? $customer['customer_name'] : 'N/A';
$customer_email = isset($customer['email']) ? $customer['email'] : 'N/A';
$shipping_address = isset($order['shipping_address']) ? $order['shipping_address'] : 'N/A';
$city = isset($order['city']) ? $order['city'] : 'N/A';
$postal_code = isset($order['postal_code']) ? $order['postal_code'] : 'N/A';
$phone = isset($order['phone']) ? $order['phone'] : 'N/A';

// Helper function to safely format numbers
function safe_number_format($number, $decimals = 0) {
    return is_numeric($number) ? number_format($number, $decimals) : '0';
}

// Helper function to safely escape HTML
function safe_htmlspecialchars($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - TechMarket</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .confirmation-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th, .table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table th {
            background-color: #f5f5f5;
        }
        .actions {
            margin-top: 20px;
            text-align: center;
        }
        .btn {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #0e2a3b;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0a1f2d;
        }
        .email-form {
            margin-top: 10px;
            display: inline-block;
        }
        .email-form input[type="email"] {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        /* Force text colors */
        body, h1, h2, h3, p, span, div, label, input, table, th, td {
            color: #0e2a3b !important;
        }
        /* Only header elements and buttons should have white text */
        .header, .header *, .category-nav, .category-nav * {
            color: white !important;
        }
        .btn-primary {
            color: white !important;
        }
        /* Ensure inputs are readable */
        input[type="email"], input[type="text"] {
            background-color: #fff !important;
            border: 1px solid #ddd !important;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .success-message i {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span><span class="text-green">Tech</span>Market</span>
                    </a>
                </div>
                <div class="header-icons">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="cart.php"><i class="fas fa-shopping-bag"></i> Cart</a>
                    <a href="user-profile.php"><i class="fas fa-user"></i> My Account</a>
                </div>
            </div>
        </div>
    </header>

    <main class="confirmation-container">
        <div class="confirmation-header">
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <strong>Order Placed Successfully!</strong>
            </div>
            <h1>Order Confirmation</h1>
            <p>Thank you for your purchase! Your order has been placed successfully.</p>
        </div>

        <div class="section">
            <h2>Order Details</h2>
            <table class="table">
                <tr><th>Order ID</th><td><?php echo safe_htmlspecialchars($order['order_id']); ?></td></tr>
                <tr><th>Invoice No</th><td><?php echo safe_htmlspecialchars($invoice_no); ?></td></tr>
                <tr><th>Order Date</th><td><?php echo safe_htmlspecialchars($order_date); ?></td></tr>
                <tr><th>Order Status</th><td><span style="text-transform: capitalize;"><?php echo safe_htmlspecialchars($order_status); ?></span></td></tr>
                <tr><th>Payment Method</th><td style="text-transform: capitalize;"><?php echo safe_htmlspecialchars(str_replace('_', ' ', $payment_method)); ?></td></tr>
            </table>
        </div>

        <div class="section">
            <h2>Customer Information</h2>
            <table class="table">
                <tr><th>Name</th><td><?php echo safe_htmlspecialchars($customer_name); ?></td></tr>
                <tr><th>Email</th><td><?php echo safe_htmlspecialchars($customer_email); ?></td></tr>
                <tr><th>Shipping Address</th><td><?php echo safe_htmlspecialchars($shipping_address . ', ' . $city . ', ' . $postal_code); ?></td></tr>
                <tr><th>Phone</th><td><?php echo safe_htmlspecialchars($phone); ?></td></tr>
            </table>
        </div>

        <div class="section">
            <h2>Order Items</h2>
            <table class="table">
                <tr><th>Product</th><th>Quantity</th><th>Price (DA)</th><th>Total (DA)</th></tr>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): 
                    // Handle both order_items and cart table field names
                    $price = isset($item['price']) ? $item['price'] : (isset($item['p_price']) ? $item['p_price'] : 0);
                    $qty = isset($item['quantity']) ? $item['quantity'] : (isset($item['qty']) ? $item['qty'] : 0);
                    $product_id = isset($item['product_id']) ? $item['product_id'] : (isset($item['p_id']) ? $item['p_id'] : 0);
                    $product_title = isset($item['product_title']) ? $item['product_title'] : 'Unknown Product';
                ?>
                    <tr>
                        <td><?php echo safe_htmlspecialchars($product_title); ?></td>
                        <td><?php echo safe_htmlspecialchars($qty); ?></td>
                        <td><?php echo safe_number_format($price, 0); ?></td>
                        <td><?php echo safe_number_format($price * $qty, 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No items found for this order.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="section">
            <h2>Summary</h2>
            <table class="table">
                <tr><th>Subtotal</th><td><?php echo safe_number_format($subtotal, 0); ?> DA</td></tr>
                <tr><th>Shipping Cost</th><td><?php echo safe_number_format($shipping_cost, 0); ?> DA</td></tr>
                <?php if ($discount_amount > 0): ?>
                <tr><th>Discount</th><td>-<?php echo safe_number_format($discount_amount, 0); ?> DA</td></tr>
                <?php endif; ?>
                <tr style="font-weight: bold; background-color: #f8f9fa;"><th>Total</th><td><?php echo safe_number_format($total, 0); ?> DA</td></tr>
            </table>
        </div>

        <div class="actions">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-download"></i> Download as PDF
            </button>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-shopping-cart"></i> Continue Shopping
            </a>
            
            <?php if ($customer_email && $customer_email !== 'N/A'): ?>
            <form class="email-form" method="post" action="send-email.php" target="_blank">
                <input type="hidden" name="order_id" value="<?php echo safe_htmlspecialchars($order_id); ?>">
                <input type="email" name="email" value="<?php echo safe_htmlspecialchars($customer_email); ?>" placeholder="Enter email" required>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> Send to Email
                </button>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Add print functionality
        window.addEventListener('DOMContentLoaded', () => {
            // Print styles for better PDF output
            const printStyles = `
                @media print {
                    .header, .actions { display: none !important; }
                    .confirmation-container { 
                        box-shadow: none !important; 
                        max-width: none !important;
                        margin: 0 !important;
                    }
                    body { font-size: 12px !important; }
                    .table th, .table td { padding: 5px !important; }
                }
            `;
            
            const styleSheet = document.createElement("style");
            styleSheet.type = "text/css";
            styleSheet.innerText = printStyles;
            document.head.appendChild(styleSheet);
        });
    </script>
</body>
</html>
