<?php
session_start();
include 'C:/xampp/htdocs/PPD/php/config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: auth.php?redirect=checkout.php");
    exit;
}

// Get customer information
$customer_id = $_SESSION['customer_id'];
$customer_query = "SELECT *, fidelity_discount FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer_result = $stmt->get_result();
$customer = $customer_result->fetch_assoc();
$stmt->close();

// If customer data is not found or incomplete, use session data as fallback
if (!$customer) {
    $customer = [
        'customer_name' => isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : '',
        'email' => isset($_SESSION['customer_email']) ? $_SESSION['customer_email'] : '',
        'customer_address' => '',
        'customer_city' => '',
        'customer_postal_code' => '',
        'customer_phone' => '',
        'fidelity_discount' => 0,
        'customer_points' => 0
    ];
} else {
    if (!isset($customer['email']) || empty($customer['email'])) {
        $customer['email'] = isset($_SESSION['customer_email']) ? $_SESSION['customer_email'] : '';
    }
}

// Get cart items (include both WAITING and FIDELITY statuses)
$cart_query = "SELECT c.*, p.product_title, p.fidelity_score 
               FROM cart c 
               JOIN products p ON c.p_id = p.product_id 
               WHERE c.customer_id = ? AND c.status IN ('WAITING', 'FIDELITY')";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$stmt->close();

// Calculate subtotal (exclude FIDELITY items from subtotal) and points
$subtotal = 0;
$cart_items = [];
$total_points = 0;
while ($item = $cart_result->fetch_assoc()) {
    if ($item['status'] === 'WAITING') {
        $subtotal += $item['p_price'] * $item['qty'];
        $total_points += $item['fidelity_score'] * $item['qty'];
    }
    
    // Get product image from product_images table
    $product_id = $item['p_id'];
    $image_query = "SELECT image_path FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC LIMIT 1";
    $img_result = $conn->query($image_query);
    if ($img_result && $img_result->num_rows > 0) {
        $img_row = $img_result->fetch_assoc();
        $image_path = $img_row['image_path'];
        // Check if the path already includes 'product_images/'
        if (strpos($image_path, 'product_images/') === false) {
            $item['product_img1'] = 'product_images/' . $image_path;
        } else {
            $item['product_img1'] = $image_path;
        }
    } else {
        $item['product_img1'] = 'https://via.placeholder.com/60x60';
    }
    
    $cart_items[] = $item;
}

// Get available discounts from discounts table (for fallback)
$discount_query = "SELECT * FROM discounts WHERE status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURDATE())";
$discount_result = $conn->query($discount_query);
$discounts = [];
while ($discount = $discount_result->fetch_assoc()) {
    $discounts[] = $discount;
}

// Initialize variables
$shipping_cost = 500; // Default shipping cost (500 DA)
$discount_amount = $customer['fidelity_discount']; // Use fidelity_discount by default
$discount_code = ''; // Will be set if using a manual discount code
$total = max(0, $subtotal + $shipping_cost - $discount_amount);

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . print_r($_POST, true));

    // Validate form data
    $shipping_address = trim($_POST['shipping_address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $phone = trim($_POST['phone']);
    $payment_method = $_POST['payment_method'];
    $discount_code = isset($_POST['discount_code']) ? trim($_POST['discount_code']) : '';
    $discount_amount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : $customer['fidelity_discount'];

    // Validate required fields
    $errors = [];
    if (empty($shipping_address)) $errors[] = "Shipping address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($postal_code)) $errors[] = "Postal code is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($payment_method)) $errors[] = "Payment method is required";

    // Validate credit card fields if credit card payment method is selected
    if ($payment_method === 'credit_card') {
        $card_number = isset($_POST['card_number']) ? trim($_POST['card_number']) : '';
        $card_holder = isset($_POST['card_holder']) ? trim($_POST['card_holder']) : '';
        $expiry_date = isset($_POST['expiry_date']) ? trim($_POST['expiry_date']) : '';
        $cvv = isset($_POST['cvv']) ? trim($_POST['cvv']) : '';

        if (empty($card_number)) $errors[] = "Card number is required";
        if (empty($card_holder)) $errors[] = "Card holder name is required";
        if (empty($expiry_date)) $errors[] = "Expiry date is required";
        if (empty($cvv)) $errors[] = "CVV is required";

        // Enhanced validation
        if (!empty($card_number)) {
            $clean_card_number = str_replace(' ', '', $card_number);
            if (!preg_match('/^[0-9]{16}$/', $clean_card_number)) {
                $errors[] = "Card number must be exactly 16 digits";
            }
        }

        if (!empty($expiry_date) && !preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry_date)) {
            $errors[] = "Expiry date must be in MM/YY format";
        }

        // Check if card is not expired
        if (!empty($expiry_date) && preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry_date)) {
            list($month, $year) = explode('/', $expiry_date);
            $year = '20' . $year; // Convert YY to YYYY
            $expiry_timestamp = mktime(0, 0, 0, $month + 1, 1, $year); // First day of next month
            if ($expiry_timestamp < time()) {
                $errors[] = "Card has expired";
            }
        }

        if (!empty($cvv) && !preg_match('/^[0-9]{3,4}$/', $cvv)) {
            $errors[] = "CVV must be 3 or 4 digits";
        }

        if (!empty($card_holder) && !preg_match('/^[a-zA-Z\s]+$/', $card_holder)) {
            $errors[] = "Card holder name can only contain letters and spaces";
        }
    }

    // Validate cart
    if (empty($cart_items)) {
        $errors[] = "Your cart is empty";
    }

    // If no errors, proceed with order creation
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Calculate final total
            $total = max(0, $subtotal + $shipping_cost - $discount_amount);

            // Generate invoice number
            $invoice_no = 'INV-' . time() . '-' . $customer_id;
            
            // Set order status
            $order_status = 'pending';

            // Get the structure of the customer_orders table
            $table_structure_query = "DESCRIBE customer_orders";
            $table_structure_result = $conn->query($table_structure_query);
            $table_columns = [];
            
            while ($column = $table_structure_result->fetch_assoc()) {
                $table_columns[] = $column['Field'];
            }

            // Build the query based on the actual table structure
            $columns = [];
            $placeholders = [];
            $param_types = "";
            $param_values = [];

            // Always include these core fields
            if (in_array('customer_id', $table_columns)) {
                $columns[] = 'customer_id';
                $placeholders[] = '?';
                $param_types .= 'i';
                $param_values[] = $customer_id;
            }

            if (in_array('due_amount', $table_columns)) {
                $columns[] = 'due_amount';
                $placeholders[] = '?';
                $param_types .= 'd';
                $param_values[] = $total;
            }

            if (in_array('invoice_no', $table_columns)) {
                $columns[] = 'invoice_no';
                $placeholders[] = '?';
                $param_types .= 's';
                $param_values[] = $invoice_no;
            }

            if (in_array('order_date', $table_columns)) {
                $columns[] = 'order_date';
                $placeholders[] = 'NOW()';
            }

            if (in_array('order_status', $table_columns)) {
                $columns[] = 'order_status';
                $placeholders[] = '?';
                $param_types .= 's';
                $param_values[] = $order_status;
            }

            if (in_array('shipping_address', $table_columns)) {
                $columns[] = 'shipping_address';
                $placeholders[] = '?';
                $param_types .= 's';
                $param_values[] = $shipping_address;
            }

            if (in_array('city', $table_columns)) {
                $columns[] = 'city';
                $placeholders[] = '?';
                $param_types .= 's';
                $param_values[] = $city;
            }

            if (in_array('postal_code', $table_columns)) {
                $columns[] = 'postal_code';
                $placeholders[] = '?';
                $param_types .= 's';
                $param_values[] = $postal_code;
            }

            if (in_array('phone', $table_columns)) {
                $columns[] = 'phone';
                $placeholders[] = '?';
                $param_types .= 's';
                $param_values[] = $phone;
            }

            if (in_array('payment_method', $table_columns)) {
                $columns[] = 'payment_method';
                $placeholders[] = '?';
                $param_types .= 's';
                $param_values[] = $payment_method;
            }

            if (in_array('discount_code', $table_columns)) {
                $columns[] = 'discount_code';
                $placeholders[] = '?';
                $param_types .= 's';
                $param_values[] = $discount_code;
            }

            if (in_array('discount_amount', $table_columns)) {
                $columns[] = 'discount_amount';
                $placeholders[] = '?';
                $param_types .= 'd';
                $param_values[] = $discount_amount;
            }

            // Build the final query
            $order_query = "INSERT INTO customer_orders (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
            
            $stmt = $conn->prepare($order_query);
            if ($stmt === false) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            if (!empty($param_types) && !empty($param_values)) {
                $stmt->bind_param($param_types, ...$param_values);
            }
            
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();

            // If payment method is credit card, create payment record
            if ($payment_method === 'credit_card') {
                // Generate a reference number for the transaction
                $ref_no = time() . rand(1000, 9999);
                
                // Generate a transaction code
                $transaction_code = rand(100000, 999999);
                
                // Get current date
                $payment_date = date('Y-m-d H:i:s');
                
                // Insert payment record
                $payment_query = "INSERT INTO payments (order_id, amount, payment_mode, ref_no, code, payment_date) VALUES (?, ?, ?, ?, ?, ?)";
                $payment_stmt = $conn->prepare($payment_query);
                
                if ($payment_stmt === false) {
                    throw new Exception("Error preparing payment statement: " . $conn->error);
                }
                
                $payment_mode = 'Credit Card';
                $payment_stmt->bind_param("iisiss", $order_id, $total, $payment_mode, $ref_no, $transaction_code, $payment_date);
                $payment_stmt->execute();
                
                if ($payment_stmt->error) {
                    throw new Exception("Error inserting payment record: " . $payment_stmt->error);
                }
                
                $payment_stmt->close();
                
                // Log payment details for debugging
                error_log("Payment record created - Order ID: $order_id, Amount: $total, Ref: $ref_no, Code: $transaction_code");
            }

            // Check if order_items table exists and get its structure
            $order_items_exists = $conn->query("SHOW TABLES LIKE 'order_items'")->num_rows > 0;
            
            if ($order_items_exists) {
                $order_items_structure_query = "DESCRIBE order_items";
                $order_items_structure_result = $conn->query($order_items_structure_query);
                $order_items_columns = [];
                
                while ($column = $order_items_structure_result->fetch_assoc()) {
                    $order_items_columns[] = $column['Field'];
                }

                // Move cart items to order_items table
                foreach ($cart_items as $item) {
                    // Build the query based on the actual table structure
                    $item_columns = [];
                    $item_placeholders = [];
                    $item_param_types = "";
                    $item_param_values = [];

                    if (in_array('order_id', $order_items_columns)) {
                        $item_columns[] = 'order_id';
                        $item_placeholders[] = '?';
                        $item_param_types .= 'i';
                        $item_param_values[] = $order_id;
                    }

                    if (in_array('product_id', $order_items_columns)) {
                        $item_columns[] = 'product_id';
                        $item_placeholders[] = '?';
                        $item_param_types .= 'i';
                        $item_param_values[] = $item['p_id'];
                    }

                    if (in_array('price', $order_items_columns)) {
                        $item_columns[] = 'price';
                        $item_placeholders[] = '?';
                        $item_param_types .= 'd';
                        $item_param_values[] = $item['p_price'];
                    }

                    if (in_array('quantity', $order_items_columns)) {
                        $item_columns[] = 'quantity';
                        $item_placeholders[] = '?';
                        $item_param_types .= 'i';
                        $item_param_values[] = $item['qty'];
                    }

                    if (in_array('status', $order_items_columns)) {
                        $item_columns[] = 'status';
                        $item_placeholders[] = '?';
                        $item_param_types .= 's';
                        $item_param_values[] = $item['status'];
                    }

                    // Build the final query
                    $insert_order_item_query = "INSERT INTO order_items (" . implode(", ", $item_columns) . ") VALUES (" . implode(", ", $item_placeholders) . ")";
                    
                    $stmt = $conn->prepare($insert_order_item_query);
                    if ($stmt === false) {
                        throw new Exception("Error preparing statement: " . $conn->error);
                    }
                    
                    if (!empty($item_param_types) && !empty($item_param_values)) {
                        $stmt->bind_param($item_param_types, ...$item_param_values);
                    }
                    
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // Update cart items to ORDERED status instead of deleting
            $update_cart_query = "UPDATE cart SET status = 'ORDERED' WHERE customer_id = ? AND status IN ('WAITING', 'FIDELITY')";
            $stmt = $conn->prepare($update_cart_query);
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $stmt->close();

            // Update product stock for each item
            foreach ($cart_items as $item) {
                $stock_query = "SELECT stock FROM products WHERE product_id = ?";
                $stmt = $conn->prepare($stock_query);
                $stmt->bind_param("i", $item['p_id']);
                $stmt->execute();
                $stock_result = $stmt->get_result();
                $product = $stock_result->fetch_assoc();
                $stmt->close();

                if ($product['stock'] < $item['qty']) {
                    throw new Exception("Insufficient stock for product ID " . $item['p_id']);
                }

                $update_stock_query = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
                $stmt = $conn->prepare($update_stock_query);
                $stmt->bind_param("ii", $item['qty'], $item['p_id']);
                $stmt->execute();
                $stmt->close();
            }

            // Deduct fidelity_discount from customer if used
            if ($discount_amount > 0 && empty($discount_code)) {
                $update_discount_query = "UPDATE customers SET fidelity_discount = fidelity_discount - ? WHERE customer_id = ?";
                $stmt = $conn->prepare($update_discount_query);
                $stmt->bind_param("di", $discount_amount, $customer_id);
                $stmt->execute();
                $stmt->close();
            }

            // Add points to customer
            if ($total_points > 0) {
                $update_points_query = "UPDATE customers SET customer_points = customer_points + ? WHERE customer_id = ?";
                $stmt = $conn->prepare($update_points_query);
                $stmt->bind_param("ii", $total_points, $customer_id);
                $stmt->execute();
                $stmt->close();
            }

            // Commit transaction
            $conn->commit();

            // Set success message with payment details if credit card was used
            if ($payment_method === 'credit_card') {
                $_SESSION['payment_success'] = [
                    'order_id' => $order_id,
                    'amount' => $total,
                    'ref_no' => $ref_no ?? '',
                    'transaction_code' => $transaction_code ?? '',
                    'payment_method' => 'Credit Card'
                ];
            }

            header("Location: order-confirmation.php?order_id=" . $order_id);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "An error occurred while processing your order: " . $e->getMessage();
            error_log("Order processing error: " . $e->getMessage());
        }
    } else {
        $error_message = "Please correct the following errors: " . implode(", ", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TechMarket</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
        .checkout-form {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .checkout-summary {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            position: sticky;
            top: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .form-control:focus {
            border-color: #0e2a3b;
            outline: none;
        }
        .error-message {
            color: #e53e3e;
            font-size: 14px;
            margin-top: 5px;
        }
        .cart-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .cart-item-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 15px;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-title {
            font-weight: 500;
            margin-bottom: 5px;
        }
        .cart-item-price {
            color: #555;
        }
        .cart-item-quantity {
            color: #777;
            font-size: 14px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-total {
            font-weight: 700;
            font-size: 18px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eee;
        }
        .discount-form {
            display: flex;
            margin-bottom: 20px;
        }
        .discount-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        .discount-button {
            background-color: #0e2a3b;
            color: white;
            border: none;
            padding: 0 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        .discount-button:hover {
            background-color: #0a1f2d;
        }
        .discount-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 15px;
        }
        .submit-button {
            background-color: #0e2a3b;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .submit-button:hover {
            background-color: #0a1f2d;
        }
        .payment-methods {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .payment-method {
            flex: 1;
            min-width: 120px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .payment-method:hover {
            border-color: #0e2a3b;
        }
        .payment-method.selected {
            border-color: #0e2a3b;
            background-color: rgba(14, 42, 59, 0.05);
        }
        .payment-method i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }
        .credit-card-fields {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: none;
        }
        .credit-card-fields.active {
            display: block;
        }
        .card-row {
            display: flex;
            gap: 15px;
        }
        .card-row .form-group {
            flex: 1;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #ef4444;
        }
        .alert-info {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #38bdf8;
        }
        body, h1, h2, h3, p, span, div, label, input, select, textarea, table, th, td {
            color: #0e2a3b !important;
        }
        .form-group label {
            color: #0e2a3b !important;
        }
        .form-control {
            color: #0e2a3b !important;
            background-color: #fff !important;
        }
        .order-item, .summary-row, .summary-total {
            color: #0e2a3b !important;
        }
        .header, .header *, .category-nav, .category-nav * {
            color: white !important;
        }
        .btn-primary, .checkout-btn, .submit-button, .discount-button {
            color: white !important;
        }
        .alert-info a {
            color: #0369a1 !important;
            text-decoration: underline;
        }
        .discount-section {
            margin-bottom: 20px;
        }

        .fidelity-discount {
            margin-bottom: 15px;
        }

        .discount-codes {
            margin-bottom: 15px;
        }

        .discount-list {
            list-style: none;
            padding: 0;
            margin: 0 0 15px 0;
        }

        .discount-list li {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
        }

        .discount-list li label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .discount-list li input[type="radio"] {
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

    <main class="checkout-container">
        <h1>Checkout</h1>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Please correct the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="index.php">Continue shopping</a>
            </div>
        <?php else: ?>
            <form method="post" action="checkout.php" id="checkout-form">
                <div class="checkout-grid">
                    <div class="checkout-form">
                        <h2>Shipping Information</h2>

                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($customer['customer_name'] ?? ''); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="shipping_address">Shipping Address*</label>
                            <input type="text" id="shipping_address" name="shipping_address" class="form-control" value="<?php echo htmlspecialchars($_POST['shipping_address'] ?? $customer['customer_address'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="city">City*</label>
                            <input type="text" id="city" name="city" class="form-control" value="<?php echo htmlspecialchars($_POST['city'] ?? $customer['customer_city'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="postal_code">Postal Code*</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($_POST['postal_code'] ?? $customer['customer_postal_code'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number*</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? $customer['customer_phone'] ?? ''); ?>" required>
                        </div>

                        <h2>Payment Method</h2>
                        <div class="form-group">
                            <input type="hidden" id="payment_method" name="payment_method" value="<?php echo htmlspecialchars($_POST['payment_method'] ?? 'cash_on_delivery'); ?>">
                            <div class="payment-methods">
                                <div class="payment-method <?php echo (!isset($_POST['payment_method']) || $_POST['payment_method'] == 'cash_on_delivery') ? 'selected' : ''; ?>" data-method="cash_on_delivery">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div>Cash on Delivery</div>
                                </div>
                                <div class="payment-method <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'credit_card') ? 'selected' : ''; ?>" data-method="credit_card">
                                    <i class="fas fa-credit-card"></i>
                                    <div>Credit Card</div>
                                </div>
                            </div>
                        </div>

                        <div id="credit-card-fields" class="credit-card-fields <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'credit_card') ? 'active' : ''; ?>">
                            <div class="form-group">
                                <label for="card_number">Card Number*</label>
                                <input type="text" id="card_number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="card_holder">Card Holder Name*</label>
                                <input type="text" id="card_holder" name="card_holder" class="form-control" placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['card_holder'] ?? ''); ?>">
                            </div>
                            <div class="card-row">
                                <div class="form-group">
                                    <label for="expiry_date">Expiry Date*</label>
                                    <input type="text" id="expiry_date" name="expiry_date" class="form-control" placeholder="MM/YY" value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="cvv">CVV*</label>
                                    <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123" value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-summary">
                        <h2>Order Summary</h2>

                        <div class="cart-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item">
                                    <img src="<?php echo htmlspecialchars($item['product_img1']); ?>" alt="<?php echo htmlspecialchars($item['product_title']); ?>" class="cart-item-image">
                                    <div class="cart-item-details">
                                        <div class="cart-item-title"><?php echo htmlspecialchars($item['product_title']); ?></div>
                                        <div class="cart-item-price"><?php echo $item['status'] === 'FIDELITY' ? 'Free (Fidelity Gift)' : number_format($item['p_price'], 0) . ' D.A'; ?></div>
                                        <div class="cart-item-quantity">Qty: <?php echo $item['qty']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="discount-section">
                        <h3>Apply Discount</h3>

                        <?php if ($customer['fidelity_discount'] > 0): ?>
                            <div class="fidelity-discount">
                                <label>
                                    <input type="radio" name="discount_type" value="fidelity" checked>
                                    Use Fidelity Discount: <?php echo number_format($customer['fidelity_discount'], 0); ?> D.A
                                </label>
                                <input type="hidden" id="fidelity_discount_amount" value="<?php echo $customer['fidelity_discount']; ?>">
                            </div>
                        <?php endif; ?>

                        <div class="discount-codes">
                            <h4>Available Discount Codes</h4>
                            <?php if (!empty($discounts)): ?>
                                <ul class="discount-list">
                                    <?php foreach ($discounts as $discount): ?>
                                        <li>
                                            <label>
                                                <input type="radio" name="discount_type" value="code" data-code="<?php echo htmlspecialchars($discount['code']); ?>" 
                                                    data-type="<?php echo htmlspecialchars($discount['discount_type']); ?>"
                                                    data-value="<?php echo htmlspecialchars($discount['discount_value']); ?>">
                                                <?php echo htmlspecialchars($discount['code']); ?> - 
                                                <?php echo $discount['discount_type'] == 'percentage' ? $discount['discount_value'] . '%' : number_format($discount['discount_value'], 0) . ' D.A'; ?> off
                                                <?php if ($discount['min_order_amount'] > 0): ?>
                                                    (Min. order: <?php echo number_format($discount['min_order_amount'], 0); ?> D.A)
                                                <?php endif; ?>
                                                <?php if ($discount['expiry_date']): ?>
                                                    (Expires: <?php echo htmlspecialchars($discount['expiry_date']); ?>)
                                                <?php endif; ?>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No discount codes available.</p>
                            <?php endif; ?>
                        </div>

                        <div class="discount-form">
                            <input type="text" id="discount_code" name="discount_code" class="discount-input" placeholder="Enter discount code" value="<?php echo htmlspecialchars($discount_code); ?>">
                            <button type="button" id="apply-discount" class="discount-button">Apply</button>
                        </div>
                        <input type="hidden" id="discount_amount" name="discount_amount" value="<?php echo $discount_amount; ?>">
                    </div>

                        <div class="order-summary">
                            <div class="summary-row">
                                <div>Subtotal</div>
                                <div id="subtotal"><?php echo number_format($subtotal, 0) . ' D.A'; ?></div>
                            </div>
                            <div class="summary-row">
                                <div>Shipping</div>
                                <div id="shipping"><?php echo number_format($shipping_cost, 0) . ' D.A'; ?></div>
                            </div>
                            <div class="summary-row" id="discount-row" style="<?php echo $discount_amount > 0 ? '' : 'display: none;'; ?>">
                                <div>Discount</div>
                                <div id="discount">-<?php echo number_format($discount_amount, 0) . ' D.A'; ?></div>
                            </div>
                            <div class="summary-row summary-total">
                                <div>Total</div>
                                <div id="total"><?php echo number_format($total, 0) . ' D.A'; ?></div>
                            </div>
                        </div>

                        <button type="submit" class="submit-button">Place Order</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <script src="checkout-discount.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Payment method selection
            const paymentMethods = document.querySelectorAll('.payment-method');
            const paymentMethodInput = document.getElementById('payment_method');
            const creditCardFields = document.getElementById('credit-card-fields');

            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    paymentMethods.forEach(m => m.classList.remove('selected'));
                    this.classList.add('selected');
                    const methodValue = this.getAttribute('data-method');
                    paymentMethodInput.value = methodValue;

                    if (methodValue === 'credit_card') {
                        creditCardFields.classList.add('active');
                    } else {
                        creditCardFields.classList.remove('active');
                    }
                });
            });

            // Card number formatting
            const cardNumberInput = document.getElementById('card_number');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = '';
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) {
                            formattedValue += ' ';
                        }
                        formattedValue += value[i];
                    }
                    e.target.value = formattedValue;
                });
            }

            // Expiry date formatting
            const expiryDateInput = document.getElementById('expiry_date');
            if (expiryDateInput) {
                expiryDateInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value;
                });
            }

            // CVV formatting
            const cvvInput = document.getElementById('cvv');
            if (cvvInput) {
                cvvInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    e.target.value = value.substring(0, 4);
                });
            }

            // Disable discount input if fidelity discount is available
            <?php if ($customer['fidelity_discount'] > 0): ?>
                const discountSelect = document.getElementById('discount-select');
                const discountInput = document.getElementById('discount_code');
                const discountButton = document.getElementById('apply-discount');
                if (discountSelect) discountSelect.disabled = true;
                if (discountInput) discountInput.disabled = true;
                if (discountButton) discountButton.disabled = true;
            <?php endif; ?>
        });
    </script>
</body>
</html>
