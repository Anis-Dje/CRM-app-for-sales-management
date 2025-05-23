<?php
// Database connection
include 'C:\xampp\htdocs\PPD\php\config.php';
    session_start();
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => "Connection failed: " . $conn->connect_error]));
}

// Set content type to JSON
header('Content-Type: application/json');

// Get query parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$brand = isset($_GET['brand']) ? $conn->real_escape_string($_GET['brand']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Items per page
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Check if manufacturers table exists
$result = $conn->query("SHOW TABLES LIKE 'manufacturers'");
$manufacturersTableExists = $result->num_rows > 0;

// Build query
if ($manufacturersTableExists) {
    $query = "SELECT p.*, pc.p_cat_title, m.manufacturer_title 
              FROM products p
              LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
              LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
              WHERE 1=1";
} else {
    // If manufacturers table doesn't exist, just join with product_categories
    $query = "SELECT p.*, pc.p_cat_title, '' as manufacturer_title 
              FROM products p
              LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
              WHERE 1=1";
}

// Add filters
if (!empty($search)) {
    $query .= " AND (p.product_title LIKE '%$search%' OR p.product_keywords LIKE '%$search%')";
}

if (!empty($category)) {
    $query .= " AND p.p_cat_id = '$category'";
}

if (!empty($brand) && $manufacturersTableExists) {
    $query .= " AND p.manufacturer_id = '$brand'";
}

if (!empty($status)) {
    $query .= " AND p.status = '$status'";
}

// Count total items for pagination
$countQuery = str_replace("p.*, pc.p_cat_title", "COUNT(*) as total", $query);
$countQuery = str_replace("p.*, pc.p_cat_title, m.manufacturer_title", "COUNT(*) as total", $countQuery);
$countQuery = str_replace("p.*, pc.p_cat_title, '' as manufacturer_title", "COUNT(*) as total", $countQuery);

$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Add pagination to query
$query .= " ORDER BY p.date DESC LIMIT $offset, $itemsPerPage";

// Execute query
$result = $conn->query($query);

// Prepare response
$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format prices for display
        $row['product_price'] = number_format($row['product_price'], 0) . ' D.A';
        if ($row['product_psp_price'] > 0) {
            $row['product_psp_price'] = number_format($row['product_psp_price'], 0) . ' D.A';
        }
        
        // Get product image from product_images table
        $product_id = $row['product_id'];
        $image_query = "SELECT image_path FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC LIMIT 1";
        $image_result = $conn->query($image_query);
        if ($image_result && $image_result->num_rows > 0) {
            $image_row = $image_result->fetch_assoc();
            $image_path = $image_row['image_path'];
            // Check if the path already includes 'product_images/'
            if (strpos($image_path, 'product_images/') === false) {
                $row['product_img1'] = 'product_images/' . $image_path;
            } else {
                $row['product_img1'] = $image_path;
            }
        } else {
            $row['product_img1'] = 'https://via.placeholder.com/150x200';
        }

        // Get additional images
        $additional_images_query = "SELECT image_path FROM product_images WHERE product_id = $product_id AND is_primary = 0 ORDER BY image_id LIMIT 2";
        $additional_images_result = $conn->query($additional_images_query);
        if ($additional_images_result && $additional_images_result->num_rows > 0) {
            $i = 2;
            while ($img_row = $additional_images_result->fetch_assoc()) {
                $image_path = $img_row['image_path'];
                // Check if the path already includes 'product_images/'
                if (strpos($image_path, 'product_images/') === false) {
                    $row['product_img' . $i] = 'product_images/' . $image_path;
                } else {
                    $row['product_img' . $i] = $image_path;
                }
                $i++;
            }
        }
        
        $products[] = $row;
    }
}

// Prepare pagination data
$pagination = [
    'currentPage' => $page,
    'totalPages' => $totalPages,
    'totalItems' => $totalItems,
    'itemsPerPage' => $itemsPerPage
];

// Return JSON response
echo json_encode([
    'products' => $products,
    'pagination' => $pagination
]);

$conn->close();
?>
