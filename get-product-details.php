<?php
// Database connection
include 'C:\xampp\htdocs\PPD\php\config.php';
    session_start();
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');

// Get product ID
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

// Check if manufacturers table exists
$result = $conn->query("SHOW TABLES LIKE 'manufacturers'");
$manufacturersTableExists = $result->num_rows > 0;

// Query to get product details
if ($manufacturersTableExists) {
    $query = "SELECT p.*, pc.p_cat_title, m.manufacturer_title 
              FROM products p
              LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
              LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
              WHERE p.product_id = $productId";
} else {
    $query = "SELECT p.*, pc.p_cat_title, '' as manufacturer_title 
              FROM products p
              LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
              WHERE p.product_id = $productId";
}

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $product = $result->fetch_assoc();
    
    // Format prices for display
    $product['product_price'] = number_format($product['product_price'], 0);
    if ($product['product_psp_price'] > 0) {
        $product['product_psp_price'] = number_format($product['product_psp_price'], 0);
    }
    
    // Get product images from product_images table
    $product_id = $product['product_id'];
    $images_query = "SELECT image_path, is_primary FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC, image_id";
    $images_result = $conn->query($images_query);

    // Reset image fields
    $product['product_img1'] = '';
    $product['product_img2'] = '';
    $product['product_img3'] = '';
    $product['product_img4'] = '';

    if ($images_result && $images_result->num_rows > 0) {
        $i = 1;
        while ($img_row = $images_result->fetch_assoc()) {
            if ($i <= 4) { // Only store up to 4 images
                $image_path = $img_row['image_path'];
                // Check if the path already includes 'product_images/'
                if (strpos($image_path, 'product_images/') === false) {
                    $product['product_img' . $i] = 'product_images/' . $image_path;
                } else {
                    $product['product_img' . $i] = $image_path;
                }
                $i++;
            }
        }
    }

    // If no images found, use placeholder
    if (empty($product['product_img1'])) {
        $product['product_img1'] = 'https://via.placeholder.com/400x400';
    }
    
    echo json_encode($product);
} else {
    echo json_encode(['error' => 'Product not found']);
}

$conn->close();
?>
