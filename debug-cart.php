<?php
session_start();
include 'C:/xampp/htdocs/PPD/php/config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Cart and Add-to-Cart Debug Information</h2>";

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo "<p style='color: red;'>❌ User not logged in</p>";
    echo "<p><a href='auth.php'>Login here</a></p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ User logged in: Customer ID " . $_SESSION['customer_id'] . "</p>";
}

$customer_id = $_SESSION['customer_id'];

// Test add-to-cart functionality
echo "<h3>Testing Add-to-Cart Functionality</h3>";

// Get a product for testing
$product_query = "SELECT product_id, product_title, product_price, stock FROM products WHERE stock > 0 LIMIT 1";
$product_result = $conn->query($product_query);

if ($product_result && $product_result->num_rows > 0) {
    $test_product = $product_result->fetch_assoc();
    echo "<p>Test product: " . $test_product['product_title'] . " (ID: " . $test_product['product_id'] . ")</p>";
    
    // Test add-to-cart directly
    echo "<h4>Direct Database Test</h4>";
    try {
        // First, check if product is already in cart
        $check_query = "SELECT * FROM cart WHERE customer_id = ? AND p_id = ? AND status = 'WAITING'";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $customer_id, $test_product['product_id']);
        $stmt->execute();
        $check_result = $stmt->get_result();
        $exists = $check_result->fetch_assoc();
        $stmt->close();
        
        if ($exists) {
            echo "<p>Product already in cart with quantity: " . $exists['qty'] . "</p>";
            
            // Update quantity
            $new_qty = $exists['qty'] + 1;
            $update_query = "UPDATE cart SET qty = ? WHERE customer_id = ? AND p_id = ? AND status = 'WAITING'";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iii", $new_qty, $customer_id, $test_product['product_id']);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Successfully updated cart quantity to " . $new_qty . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to update cart: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            // Insert new cart item
            $insert_query = "INSERT INTO cart (customer_id, p_id, ip_add, qty, p_price, status) VALUES (?, ?, ?, 1, ?, 'WAITING')";
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $price = $test_product['product_price'];
            
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iisd", $customer_id, $test_product['product_id'], $ip_address, $price);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Successfully added product to cart</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to add to cart: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // Test add-to-cart.php via curl
    echo "<h4>Testing add-to-cart.php via CURL</h4>";
    
    $curl = curl_init();
    $post_data = [
        'product_id' => $test_product['product_id'],
        'quantity' => 1,
        'price' => $test_product['product_price']
    ];
    
    curl_setopt_array($curl, [
        CURLOPT_URL => 'http://localhost/ppd/add-to-cart.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_COOKIE => 'PHPSESSID=' . $_COOKIE['PHPSESSID']
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    echo "<p>HTTP Response Code: " . $http_code . "</p>";
    echo "<p>Response:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Try to parse JSON
    $json_response = json_decode($response, true);
    if ($json_response === null) {
        echo "<p style='color: red;'>❌ Invalid JSON response</p>";
    } else {
        echo "<p style='color: green;'>✅ Valid JSON response</p>";
        echo "<p>Success: " . ($json_response['success'] ? 'Yes' : 'No') . "</p>";
        echo "<p>Message: " . htmlspecialchars($json_response['message'] ?? 'None') . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No products found for testing</p>";
}

// Check cart items
$cart_query = "SELECT c.*, p.product_title FROM cart c JOIN products p ON c.p_id = p.product_id WHERE c.customer_id = ? AND c.status = 'WAITING'";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_count = $cart_result->num_rows;
$stmt->close();

echo "<h3>Current Cart Status</h3>";
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

$conn->close();

echo "<br><hr>";
echo "<p><a href='product-details.php?id=" . ($test_product['product_id'] ?? '1') . "' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Test Add to Cart on Product Page</a></p>";
echo "<p><a href='cart.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>View Cart</a></p>";
echo "<p><a href='checkout.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Checkout</a></p>";
echo "<p><a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Back to Homepage</a></p>";
?>
