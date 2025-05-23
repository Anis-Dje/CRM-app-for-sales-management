<?php
// This script updates the fidelity redemption process to ensure accessories are properly linked to products

// Database connection
$conn = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get all accessories from fidelity_gifts
$gifts_query = "SELECT * FROM fidelity_gifts WHERE type = 'accessory'";
$gifts_result = mysqli_query($conn, $gifts_query);

echo "<h2>Linking Fidelity Gifts to Products</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Gift ID</th><th>Gift Name</th><th>Product ID</th><th>Product Title</th><th>Status</th></tr>";

// For each accessory, check if there's a matching product
while ($gift = mysqli_fetch_assoc($gifts_result)) {
    $gift_id = $gift['id'];
    $gift_name = $gift['name'];
    
    // Check if there's a product with the exact same name
    $product_query = "SELECT product_id, product_title FROM products WHERE product_title = ?";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("s", $gift_name);
    $stmt->execute();
    $product_result = $stmt->get_result();
    
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        $product_id = $product['product_id'];
        $product_title = $product['product_title'];
        $status = "Matched";
    } else {
        // If no exact match, try a LIKE query
        $product_query = "SELECT product_id, product_title FROM products WHERE product_title LIKE ? AND p_cat_id = 9 LIMIT 1";
        $search_term = "%" . $gift_name . "%";
        $stmt = $conn->prepare($product_query);
        $stmt->bind_param("s", $search_term);
        $stmt->execute();
        $product_result = $stmt->get_result();
        
        if ($product_result->num_rows > 0) {
            $product = $product_result->fetch_assoc();
            $product_id = $product['product_id'];
            $product_title = $product['product_title'];
            $status = "Partial Match";
        } else {
            $product_id = "Not Found";
            $product_title = "N/A";
            $status = "No Match";
        }
    }
    
    echo "<tr>";
    echo "<td>$gift_id</td>";
    echo "<td>$gift_name</td>";
    echo "<td>$product_id</td>";
    echo "<td>$product_title</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<p>This script helps you verify that all fidelity gift accessories are properly linked to products in the database.</p>";
echo "<p>If any gifts show 'No Match', you may need to add those products to the database or update the gift names to match existing products.</p>";

mysqli_close($conn);
?>
