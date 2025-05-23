<?php
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

// Query to get all product categories
$query = "SELECT * FROM product_categories ORDER BY p_cat_title";
$result = $conn->query($query);

// Prepare response
$categories = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Return JSON response
echo json_encode($categories);

$conn->close();
?>