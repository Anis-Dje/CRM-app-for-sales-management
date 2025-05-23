<?php
session_start();
include 'C:/xampp/htdocs/PPD/php/config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Checkout Debug Information</h2>";

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo "<p style='color: red;'>❌ User not logged in</p>";
    echo "<p><a href='auth.php'>Login here</a></p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ User logged in: Customer ID " . $_SESSION['customer_id'] . "</p>";
}

$customer_id = $_SESSION['customer_id'];

// Check cart items
$cart_query = "SELECT c.*, p.product_title FROM cart c JOIN products p ON c.p_id = p.product_id WHERE c.customer_id = ? AND c.status IN ('WAITING', 'FIDELITY')";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_count = $cart_result->num_rows;
$stmt->close();

echo "<h3>Cart Status</h3>";
if ($cart_count > 0) {
    echo "<p style='color: green;'>✅ Cart has $cart_count items</p>";
    
    // Show cart items
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Product</th><th>Quantity</th><th>Price</th><th>Status</th></tr>";
    while ($item = $cart_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['product_title']) . "</td>";
        echo "<td>" . $item['qty'] . "</td>";
        echo "<td>" . number_format($item['p_price'], 0) . " D.A</td>";
        echo "<td>" . $item['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    $stmt->close();
} else {
    echo "<p style='color: red;'>❌ Cart is empty</p>";
}

// Check database tables
echo "<h3>Database Tables Status</h3>";

$tables_to_check = ['cart', 'products', 'customers', 'customer_orders', 'order_items'];
foreach ($tables_to_check as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' missing</p>";
    }
}

// Check order_items table structure
$order_items_check = $conn->query("SHOW TABLES LIKE 'order_items'");
if ($order_items_check->num_rows > 0) {
    echo "<h4>order_items table structure:</h4>";
    $structure = $conn->query("DESCRIBE order_items");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test a simple insert to order_items (if table exists)
if ($order_items_check->num_rows > 0 && $cart_count > 0) {
    echo "<h3>Test Insert to order_items</h3>";
    try {
        // Get first cart item for testing
        $stmt = $conn->prepare($cart_query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $test_item = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Try to insert a test record
        $test_insert = "INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (0, ?, ?, ?)";
        $stmt = $conn->prepare($test_insert);
        $stmt->bind_param("idi", $test_item['p_id'], $test_item['p_price'], $test_item['qty']);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Test insert successful</p>";
            // Clean up test record
            $test_id = $conn->insert_id;
            $conn->query("DELETE FROM order_items WHERE id = $test_id");
        } else {
            echo "<p style='color: red;'>❌ Test insert failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test insert error: " . $e->getMessage() . "</p>";
    }
}

$conn->close();

echo "<br><hr>";
echo "<p><a href='create-order-items-table.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Run Database Setup</a></p>";
echo "<p><a href='checkout.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Try Checkout</a></p>";
echo "<p><a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Back to Homepage</a></p>";
?>
