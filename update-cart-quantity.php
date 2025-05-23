<?php
session_start();
include 'C:/xampp/htdocs/PPD/php/config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if it's an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get parameters
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$customerId = $_SESSION['customer_id'];

// Validate parameters
if ($productId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity']);
    exit;
}

// Check product stock
$stock_query = "SELECT stock FROM products WHERE product_id = ?";
$stmt = $conn->prepare($stock_query);
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if ($product['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock available. Only ' . $product['stock'] . ' units available.']);
    exit;
}

// Update cart quantity
$update_query = "UPDATE cart SET qty = ? WHERE customer_id = ? AND p_id = ? AND status = 'WAITING'";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("iii", $quantity, $customerId, $productId);
$stmt->execute();

if ($stmt->affected_rows > 0 || $stmt->affected_rows === 0) { // 0 means no change was needed
    // Get updated cart total
    $total_query = "SELECT SUM(qty * p_price) as total FROM cart WHERE customer_id = ? AND status = 'WAITING'";
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'] ?? 0;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cart updated successfully',
        'total' => number_format($total, 0) . ' D.A'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}

$stmt->close();
$conn->close();
?>
