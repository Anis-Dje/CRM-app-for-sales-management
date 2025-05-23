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
$customerId = $_SESSION['customer_id'];

// Validate parameters
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Remove item from cart
$delete_query = "DELETE FROM cart WHERE customer_id = ? AND p_id = ? AND status = 'WAITING'";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("ii", $customerId, $productId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // Get updated cart total
    $total_query = "SELECT SUM(qty * p_price) as total FROM cart WHERE customer_id = ? AND status = 'WAITING'";
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'] ?? 0;
    
    // Get updated cart count
    $count_query = "SELECT COUNT(*) as count FROM cart WHERE customer_id = ? AND status = 'WAITING'";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] ?? 0;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Item removed from cart',
        'total' => number_format($total, 0) . ' D.A',
        'count' => $count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart']);
}

$stmt->close();
$conn->close();
?>
