<?php
// Include database connection
include 'C:\xampp\htdocs\PPD\php\config.php';
session_start();

// Redirect to login if not authenticated as admin
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Get product ID
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Check if product exists
$query = "SELECT product_id FROM products WHERE product_id = $productId";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Check if product_images table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
        $hasImagesTable = $tableCheck->num_rows > 0;
        
        if ($hasImagesTable) {
            // First, let's get the column names from the product_images table to identify the primary key
            $columnsQuery = "SHOW COLUMNS FROM product_images";
            $columnsResult = $conn->query($columnsQuery);
            $primaryKeyColumn = 'id'; // Default name

            if ($columnsResult && $columnsResult->num_rows > 0) {
                while ($column = $columnsResult->fetch_assoc()) {
                    if ($column['Key'] == 'PRI') {
                        $primaryKeyColumn = $column['Field'];
                        break;
                    }
                }
            }

            // Now use the correct primary key column name in our query
            $imagesQuery = "SELECT $primaryKeyColumn, image_path FROM product_images WHERE product_id = $productId";
            $imagesResult = $conn->query($imagesQuery);
            
            if ($imagesResult && $imagesResult->num_rows > 0) {
                while ($image = $imagesResult->fetch_assoc()) {
                    // Delete the image file from server
                    $imagePath = 'product_images/' . $image['image_path'];
                    if (file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }
                
                // Delete all images from database
                $deleteImagesQuery = "DELETE FROM product_images WHERE product_id = $productId";
                if (!$conn->query($deleteImagesQuery)) {
                    throw new Exception("Error deleting product images: " . $conn->error);
                }
            }
        } else {
            // If product_images table doesn't exist, try to delete images from the old way
            $oldImagesQuery = "SELECT product_img1, product_img2, product_img3 FROM products WHERE product_id = $productId";
            $oldImagesResult = $conn->query($oldImagesQuery);
            
            if ($oldImagesResult && $oldImagesResult->num_rows > 0) {
                $product = $oldImagesResult->fetch_assoc();
                
                // Delete product images from server
                $imagesToDelete = [$product['product_img1'], $product['product_img2'], $product['product_img3']];
                
                foreach ($imagesToDelete as $image) {
                    if (!empty($image) && file_exists('product_images/' . $image)) {
                        @unlink('product_images/' . $image);
                    }
                }
            }
        }
        
        // Check if product_specifications table exists
        $specTableCheck = $conn->query("SHOW TABLES LIKE 'product_specifications'");
        $hasSpecsTable = $specTableCheck->num_rows > 0;
        
        if ($hasSpecsTable) {
            // Delete product specifications
            $deleteSpecsQuery = "DELETE FROM product_specifications WHERE product_id = $productId";
            if (!$conn->query($deleteSpecsQuery)) {
                throw new Exception("Error deleting product specifications: " . $conn->error);
            }
        }
        
        // Delete product from database
        $deleteProductQuery = "DELETE FROM products WHERE product_id = $productId";
        if (!$conn->query($deleteProductQuery)) {
            throw new Exception("Error deleting product: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
}

$conn->close();
?>
