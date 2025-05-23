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

// Get discount code from request
$discount_code = isset($_POST['code']) ? trim($_POST['code']) : '';
$subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;

if (empty($discount_code) || $subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Query the database for the discount code
$query = "SELECT * FROM discounts WHERE code = ? AND status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURDATE())";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $discount_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $discount = $result->fetch_assoc();
    
    // Check if there are usage limits
    if ($discount['usage_limit'] > 0 && $discount['usage_count'] >= $discount['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'This discount code has reached its usage limit']);
        exit;
    }
    
    // Check if there's a minimum order amount
    if ($discount['min_order_amount'] > 0 && $subtotal < $discount['min_order_amount']) {
        echo json_encode([
            'success' => false, 
            'message' => 'This discount requires a minimum order of ' . number_format($discount['min_order_amount'], 0) . ' D.A'
        ]);
        exit;
    }
    
    // Calculate discount amount
    $discount_amount = 0;
    if ($discount['discount_type'] == 'percentage') {
        $discount_amount = $subtotal * ($discount['discount_value'] / 100);
        
        // Apply maximum discount if specified
        if ($discount['max_discount_amount'] > 0 && $discount_amount > $discount['max_discount_amount']) {
            $discount_amount = $discount['max_discount_amount'];
        }
    } else { // fixed amount
        $discount_amount = $discount['discount_value'];
    }
    
    // Return success response with discount details
    echo json_encode([
        'success' => true,
        'discount_id' => $discount['id'],
        'discount_code' => $discount['code'],
        'discount_type' => $discount['discount_type'],
        'discount_value' => $discount['discount_value'],
        'discount_amount' => $discount_amount,
        'formatted_discount' => number_format($discount_amount, 0) . ' D.A'
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid discount code']);
}

$stmt->close();
$conn->close();
?>
