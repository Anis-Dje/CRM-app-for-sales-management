<?php
session_start();
include 'C:/xampp/htdocs/PPD/php/config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to add items to cart']);
    exit;
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log incoming request data
$log_data = "Request data: " . json_encode($_POST);
error_log($log_data);

// Check if required parameters are set
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Get parameters
$customer_id = $_SESSION['customer_id'];
$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$buy_now = isset($_POST['buy_now']) ? intval($_POST['buy_now']) : 0;
// Validate parameters
if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity']);
    exit;
}

// Check if product exists and has stock
$product_query = "SELECT product_id, product_title, product_price, product_psp_price, stock FROM products WHERE product_id = ?";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product_result = $stmt->get_result();
$product = $product_result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Check stock
if ($product['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock available. Only ' . $product['stock'] . ' units available.']);
    exit;
}

// Get product price if not provided
if ($price <= 0) {
    $price = $product['product_psp_price'] > 0 ? $product['product_psp_price'] : $product['product_price'];
}

// Check if the product is already in the cart
$check_query = "SELECT * FROM cart WHERE customer_id = ? AND p_id = ? AND status = 'WAITING'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $customer_id, $product_id);
$stmt->execute();
$check_result = $stmt->get_result();
$exists = $check_result->fetch_assoc();
$stmt->close();

try {
    if ($exists) {
        // Update quantity if the product is already in cart
        $new_quantity = $exists['qty'] + $quantity;
        
        // Check if new quantity exceeds stock
        if ($new_quantity > $product['stock']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot add ' . $quantity . ' more units. This would exceed available stock. Max available: ' . ($product['stock'] - $exists['qty'])
            ]);
            exit;
        }
        
        $update_query = "UPDATE cart SET qty = ? WHERE customer_id = ? AND p_id = ? AND status = 'WAITING'";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("iii", $new_quantity, $customer_id, $product_id);
        $success = $stmt->execute();
        $stmt->close();
    } else {
        // Insert new product to cart
        $insert_query = "INSERT INTO cart (customer_id, p_id, qty, p_price, status) VALUES (?, ?, ?, ?, 'WAITING')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiid", $customer_id, $product_id, $quantity, $price);
        $success = $stmt->execute();
        $stmt->close();
    }

    if ($success) {
        // Get cart count
        $count_query = "SELECT COUNT(*) as count FROM cart WHERE customer_id = ? AND status = 'WAITING'";
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $cart_count = $count_row['count'];
        $stmt->close();
        
        // Calculate total
        $total_query = "SELECT SUM(p_price * qty) as total FROM cart WHERE customer_id = ? AND status = 'WAITING'";
        $stmt = $conn->prepare($total_query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $total_result = $stmt->get_result();
        $total_row = $total_result->fetch_assoc();
        $total = $total_row['total'];
        $stmt->close();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added to cart successfully', 
            'cart_count' => $cart_count,
            'total' => number_format($total, 0) . ' D.A',
            'buy_now' => $buy_now
        ]);
        
        // Log success
        error_log("Product added successfully: Product ID $product_id, Quantity $quantity, Buy Now $buy_now");
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
        error_log("Failed to add product to cart: " . $conn->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    error_log("Exception in add-to-cart.php: " . $e->getMessage());
}

$conn->close();
?>
