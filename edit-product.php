<?php
// Include database connection
include 'C:\xampp\htdocs\PPD\php\config.php';
session_start();

// Redirect to login if not authenticated as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: auth.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to sanitize input data
function sanitize($data) {
  global $conn;
  if ($data === null) {
    return '';
  }
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $conn->real_escape_string($data);
}

// Function to handle file upload
function uploadImage($file, $target_dir = "product_images/") {
  // Create directory if it doesn't exist
  if (!file_exists($target_dir)) {
      mkdir($target_dir, 0777, true);
  }
  
  $target_file = $target_dir . basename($file["name"]);
  $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
  
  // Generate unique filename
  $filename = uniqid() . '.' . $imageFileType;
  $target_file = $target_dir . $filename;
  
  // Check if image file is an actual image
  $check = getimagesize($file["tmp_name"]);
  if($check === false) {
      return false;
  }
  
  // Check file size (limit to 5MB)
  if ($file["size"] > 5000000) {
      return false;
  }
  
  // Allow certain file formats
  if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
      return false;
  }
  
  // Upload file
  if (move_uploaded_file($file["tmp_name"], $target_file)) {
      return $filename; // Return just the filename, not the full path
  } else {
      return false;
  }
}

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<script>alert('Invalid product ID')</script>";
  echo "<script>window.open('view-products.php','_self')</script>";
  exit();
}

$product_id = intval($_GET['id']);

// Get product data
$product_query = "SELECT * FROM products WHERE product_id = $product_id";
$product_result = $conn->query($product_query);

if (!$product_result || $product_result->num_rows == 0) {
  echo "<script>alert('Product not found')</script>";
  echo "<script>window.open('view-products.php','_self')</script>";
  exit();
}

$product = $product_result->fetch_assoc();

// Check if product_images table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
$hasImagesTable = $tableCheck->num_rows > 0;

// Create product_images table if it doesn't exist
if (!$hasImagesTable) {
    $createTableSQL = "CREATE TABLE product_images (
        id INT(11) NOT NULL AUTO_INCREMENT,
        product_id INT(11) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        is_primary TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY product_id (product_id)
    )";
    
    if (!$conn->query($createTableSQL)) {
        echo "<script>alert('Error creating product_images table: " . $conn->error . "')</script>";
        echo "<script>window.open('view-products.php','_self')</script>";
        exit();
    }
    
    $hasImagesTable = true;
    
    // If we just created the table, migrate existing product images
    if (!empty($product['product_img1'])) {
        $sql = "INSERT INTO product_images (product_id, image_path, is_primary) 
                VALUES ('$product_id', '{$product['product_img1']}', 1)";
        $conn->query($sql);
    }
    
    if (!empty($product['product_img2'])) {
        $sql = "INSERT INTO product_images (product_id, image_path, is_primary) 
                VALUES ('$product_id', '{$product['product_img2']}', 0)";
        $conn->query($sql);
    }
    
    if (!empty($product['product_img3'])) {
        $sql = "INSERT INTO product_images (product_id, image_path, is_primary) 
                VALUES ('$product_id', '{$product['product_img3']}', 0)";
        $conn->query($sql);
    }
}

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
$images_query = "SELECT * FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC, $primaryKeyColumn ASC";

// Get product images
$images_result = $conn->query($images_query);
$product_images = [];
$primary_image = null;

if ($images_result && $images_result->num_rows > 0) {
    while ($image = $images_result->fetch_assoc()) {
        if ($image['is_primary'] == 1) {
            $primary_image = $image;
        } else {
            $product_images[] = $image;
        }
    }
}

// Process form submission
if(isset($_POST['update'])) {
  // Sanitize form data
  $product_title = sanitize($_POST['product_title']);
  $p_cat_id = sanitize($_POST['p_cat_id']);
  $cat_id = isset($_POST['cat_id']) ? sanitize($_POST['cat_id']) : 0;
  $manufacturer_id = sanitize($_POST['manufacturer_id']);
  $product_url = sanitize($_POST['product_url']);
  $product_price = sanitize($_POST['product_price']);
  $product_psp_price = !empty($_POST['product_psp_price']) ? sanitize($_POST['product_psp_price']) : null;
  $product_desc = sanitize($_POST['product_desc']);
  $product_features = sanitize($_POST['product_features']);
  $product_video = sanitize($_POST['product_video']);
  $product_keywords = sanitize($_POST['product_keywords']);
  $product_label = sanitize($_POST['product_label']);
  $status = sanitize($_POST['status']);
  $stock = !empty($_POST['stock']) ? intval(sanitize($_POST['stock'])) : 0;
  
  // Update product in database
  $sql = "UPDATE products SET 
              p_cat_id = '$p_cat_id', 
              cat_id = '$cat_id', 
              manufacturer_id = '$manufacturer_id', 
              product_title = '$product_title', 
              product_url = '$product_url', 
              product_price = '$product_price', 
              product_psp_price = " . ($product_psp_price ? "'$product_psp_price'" : "NULL") . ", 
              product_desc = '$product_desc', 
              product_features = '$product_features', 
              product_video = '$product_video', 
              product_keywords = '$product_keywords', 
              product_label = '$product_label', 
              status = '$status',
              stock = '$stock'
          WHERE product_id = $product_id";
  
  if ($conn->query($sql) === TRUE) {
      // Handle primary image upload
      if(isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] == 0) {
          $primary_image_file = uploadImage($_FILES['primary_image']);
          if($primary_image_file) {
              // If there's already a primary image, update it
              if ($primary_image) {
                  $sql = "UPDATE product_images SET image_path = '$primary_image_file' 
                        WHERE $primaryKeyColumn = {$primary_image[$primaryKeyColumn]}";
                  $conn->query($sql);
              } else {
                  // Otherwise, insert a new primary image
                  $sql = "INSERT INTO product_images (product_id, image_path, is_primary) 
                          VALUES ('$product_id', '$primary_image_file', 1)";
                  $conn->query($sql);
              }
          } else {
              echo "<script>alert('Error uploading primary image. Product will be updated without changing this image.')</script>";
          }
      }
      
      // Handle additional images upload
      if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
          $file_count = count($_FILES['additional_images']['name']);
          
          for ($i = 0; $i < $file_count; $i++) {
              // Skip empty file inputs
              if ($_FILES['additional_images']['error'][$i] != 0) continue;
              
              // Create a temporary file array structure for our uploadImage function
              $file = array(
                  'name' => $_FILES['additional_images']['name'][$i],
                  'type' => $_FILES['additional_images']['type'][$i],
                  'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                  'error' => $_FILES['additional_images']['error'][$i],
                  'size' => $_FILES['additional_images']['size'][$i]
              );
              
              $additional_image = uploadImage($file);
              if ($additional_image) {
                  $sql = "INSERT INTO product_images (product_id, image_path, is_primary) 
                          VALUES ('$product_id', '$additional_image', 0)";
                  $conn->query($sql);
              }
          }
      }
      
      // Handle deleted images
      if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
          foreach ($_POST['delete_images'] as $image_id) {
              $image_id = intval($image_id);
              
              // Get the image path before deleting
              $image_query = "SELECT image_path FROM product_images WHERE $primaryKeyColumn = $image_id AND product_id = $product_id";
              $image_result = $conn->query($image_query);
              
              if ($image_result && $image_result->num_rows > 0) {
                  $image = $image_result->fetch_assoc();
                  $image_path = 'product_images/' . $image['image_path'];
                  
                  // Delete the image file if it exists
                  if (file_exists($image_path)) {
                      @unlink($image_path);
                  }
                  
                  // Delete the image record
                  $sql = "DELETE FROM product_images WHERE $primaryKeyColumn = $image_id AND product_id = $product_id";
                  $conn->query($sql);
              }
          }
      }
      
      // Check if product_specifications table exists
      $result = $conn->query("SHOW TABLES LIKE 'product_specifications'");
      if ($result->num_rows > 0) {
          // Delete existing specifications
          $conn->query("DELETE FROM product_specifications WHERE product_id = $product_id");
          
          // Add new specifications
          $features = explode("\n", $product_features);
          foreach ($features as $feature) {
              $feature = trim($feature);
              if (!empty($feature)) {
                  // Try to split by colon for name:value format
                  $parts = explode(':', $feature, 2);
                  if (count($parts) == 2) {
                      $spec_name = trim($parts[0]);
                      $spec_value = trim($parts[1]);
                      
                      $sql = "INSERT INTO product_specifications (product_id, spec_name, spec_value) 
                              VALUES ('$product_id', '$spec_name', '$spec_value')";
                      $conn->query($sql);
                  }
              }
          }
      }
      
      echo "<script>alert('Product has been updated successfully')</script>";
      echo "<script>window.open('view-products.php','_self')</script>";
  } else {
      echo "<script>alert('Error: " . $conn->error . "')</script>";
  }
}

// Get categories for dropdown
$p_cat_query = "SELECT * FROM product_categories ORDER BY p_cat_title";
$cat_query = "SELECT * FROM categories";
$manufacturer_query = "SELECT * FROM manufacturers ORDER BY manufacturer_title";

// Check if categories table exists
$result = $conn->query("SHOW TABLES LIKE 'categories'");
if ($result->num_rows == 0) {
  // If categories table doesn't exist, use product_categories for both dropdowns
  $cat_query = "SELECT p_cat_id as cat_id, p_cat_title as cat_title FROM product_categories ORDER BY p_cat_title";
}

$p_cat_result = $conn->query($p_cat_query);
$cat_result = $conn->query($cat_query);
$manufacturer_result = $conn->query($manufacturer_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Product - Admin Dashboard</title>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  .form-group input, .form-group textarea, .form-group select {
    background-color: #b6bccb;
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 5px;
    font-size: 14px;
  }
  .product-thumbnail {
    width: 100px;
    height: 100px;
    object-fit: contain;
    border-radius: 4px;
    border: 1px solid var(--border-color);
    background-color: #fff;
  }
  .modal .main-image {
    height: 250px;
  }
  .modal .thumbnail {
    width: 50px;
    height: 50px;
  }
  .image-upload-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
  }
  .primary-image-upload {
    flex: 1;
    min-width: 300px;
    max-width: 400px;
    border: 2px solid #4d6e83;
    border-radius: 8px;
    padding: 20px;
    background-color: rgba(77, 110, 131, 0.1);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  .primary-image-upload label.title {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #4d6e83;
    font-size: 16px;
  }
  .primary-image-upload .badge {
    display: inline-block;
    background-color: #4d6e83;
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 8px;
  }
  .additional-images-upload {
    flex: 2;
    min-width: 300px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.05);
  }
  .additional-images-upload label.title {
    display: block;
    margin-bottom: 10px;
    font-weight: 500;
  }
  .image-preview {
    width: 100%;
    height: 200px;
    margin-top: 10px;
    border-radius: 4px;
    overflow: hidden;
    background-color: rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .image-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }
  .selected-files-container {
    margin-top: 15px;
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 10px;
    background-color: rgba(0, 0, 0, 0.1);
  }
  .selected-file-item {
    display: flex;
    align-items: center;
    padding: 8px;
    border-bottom: 1px solid var(--border-color);
  }
  .selected-file-item:last-child {
    border-bottom: none;
  }
  .selected-file-preview {
    width: 50px;
    height: 50px;
    border-radius: 4px;
    overflow: hidden;
    margin-right: 10px;
    background-color: #fff;
  }
  .selected-file-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .selected-file-info {
    flex: 1;
  }
  .selected-file-name {
    font-weight: 500;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
  }
  .selected-file-size {
    font-size: 12px;
    color: #b6bccb;
  }
  .selected-file-remove {
    color: #ff4d4d;
    cursor: pointer;
    padding: 5px;
  }
  input[type="file"] {
    width: 100%;
    padding: 8px;
    background-color: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    color: var(--text-secondary);
  }
  .product-gallery .main-image {
    height: 300px;
  }
  .product-gallery .thumbnail {
    width: 60px;
    height: 60px;
  }
  .file-upload-btn {
    display: inline-block;
    padding: 10px 15px;
    background-color: #4d6e83;
    color: white;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
    text-align: center;
    margin-top: 10px;
    width: 100%;
  }
  .file-upload-btn:hover {
    background-color: #3a5363;
  }
  .file-upload-btn i {
    margin-right: 5px;
  }
  .hidden-file-input {
    display: none;
  }
  .no-files-message {
    text-align: center;
    padding: 20px;
    color: #b6bccb;
    font-style: italic;
  }
  .current-image-container {
    margin-bottom: 15px;
  }
  .current-image-preview {
    width: 100%;
    height: 200px;
    border-radius: 4px;
    overflow: hidden;
    background-color: rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
  }
  .current-image-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }
  .current-image-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .current-image-name {
    font-size: 14px;
    color: #b6bccb;
  }
  .existing-images-container {
    margin-top: 15px;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 10px;
    background-color: rgba(0, 0, 0, 0.1);
  }
  .existing-image-item {
    display: flex;
    align-items: center;
    padding: 8px;
    border-bottom: 1px solid var(--border-color);
  }
  .existing-image-item:last-child {
    border-bottom: none;
  }
  .existing-image-preview {
    width: 50px;
    height: 50px;
    border-radius: 4px;
    overflow: hidden;
    margin-right: 10px;
    background-color: #fff;
  }
  .existing-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .existing-image-info {
    flex: 1;
  }
  .existing-image-name {
    font-weight: 500;
    margin-bottom: 2px;
  }
  .existing-image-actions {
    display: flex;
    align-items: center;
  }
  .delete-image-checkbox {
    margin-right: 5px;
  }
  .delete-image-label {
    color: #ff4d4d;
    font-size: 14px;
    cursor: pointer;
  }
  @media (max-width: 768px) {
    .image-upload-container {
      flex-direction: column;
    }
    .primary-image-upload, .additional-images-upload {
      max-width: 100%;
    }
    .product-gallery .main-image {
      height: 250px;
    }
    #p_cat_id, #manufacturer_id, #cat_id, #product_label, #status {
      width: 300px;
      height: 50px;
      border: none;
      appearance: none;
      color: white;
      background-color: #4d6e83;
      font-size: 20px;
      border-radius: 7px;
      text-align: center;
    }
    .file-upload-btn {
      width: 100%;
    }
  }
</style>
</head>
<body class="dark-theme">
<div class="dashboard-container">
<aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">
          <i class="fas fa-shopping-cart"></i>
          <span><span class="text-green">Tech</span>Market</span>
        </div>
      </div>

      <div class="sidebar-content">
        <nav class="sidebar-menu">
          <ul>
            <li class>
              <a href="dashboard.php">
                <i class="fa-solid fa-circle-check"></i>
                <span>Dashboard</span>
              </a>
            </li>
            
            <li class="has-submenu">
              <a href="#" class="submenu-toggle">
                <i class="fa-solid fa-table-cells"></i>
                <span>Products</span>
                <i class="fa-solid fa-chevron-down submenu-icon"></i>
              </a>
              <ul class="submenu">
                <li><a href="insert-product.php">Insert Product</a></li>
                <li class="active"><a href="view-products.php">View Products</a></li>
              </ul>
            </li>
            
            <li class="has-submenu">
              <a href="#" class="submenu-toggle">
                <i class="fa-solid fa-user"></i>
                <span>Users</span>
                <i class="fa-solid fa-chevron-down submenu-icon"></i>
              </a>
              <ul class="submenu">
                <li><a href="insert-admin.php">Insert Admin</a></li>
                <li><a href="view-admins.php">View Admins</a></li>
                <li><a href="view-customers.php">View Customers</a></li>
              </ul>
            </li>
          

            <li class="has-submenu">
              <a href="#" class="submenu-toggle">
                <i class="fa-solid fa-industry"></i>
                <span>Manufacturer</span>
                <i class="fa-solid fa-chevron-down submenu-icon"></i>
              </a>
              <ul class="submenu">
                <li><a href="insert-manufacturers.php">Insert Manufacturer</a></li>
                <li><a href="view-manufacturers.php">View Manufacturer</a></li>
              </ul>
            </li>
            
            <li>
              <a href="view-orders.php">
                <i class="fa-solid fa-list"></i>
                <span>View Orders</span>
              </a>
            </li>
            
            <li>
              <a href="view-payments.php">
                <i class="fa-solid fa-pencil"></i>
                <span>View Payments</span>
              </a>
            </li>
            
            <li>
              <a href="fidelity-system.php">
                <i class="fa-solid fa-building"></i>
                <span>Fidelity System</span>
              </a>
            </li>
          </ul>
        </nav>
      </div>
      
      <!-- Bottom sidebar items -->
      <div class="sidebar-footer">
        <ul>
          <li class="user-profile">
            <a href="profile.php">
              <img src="<?php echo $_SESSION['admin_image'] ? 'admin_images/' . htmlspecialchars($_SESSION['admin_image']) : 'admin_images/default-profile.png'; ?>" alt="Admin Profile" class="profile-image">
              <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <span class="user-email"><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
              </div>
              <i class="fa-solid fa-ellipsis-vertical menu-dots"></i>
            </a>
          </li>
          <li class="logout-item">
            <a href="logout.php">
              <i class="fa-solid fa-power-off"></i>
              <span>Log Out</span>
            </a>
          </li>
        </ul>
      </div>
    </aside>

  <!-- Main Content -->
  <main class="main-content">
    <header class="main-header">
      <div class="header-left">
        <button id="sidebar-toggle" class="sidebar-toggle">
          <i class="fa-solid fa-bars"></i>
        </button>
        <h1>Edit Product</h1>
      </div>
      <div class="header-right">
        <div class="search-container">
          <input type="search" placeholder="Search...">
          <i class="fa-solid fa-search"></i>
        </div>
      </div>
    </header>

    <div class="content-wrapper">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Edit Product: <?php echo $product['product_title']; ?></h3>
          <a href="view-products.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Products
          </a>
        </div>
        <div class="card-content">
          <form action="" method="post" enctype="multipart/form-data" class="product-form">
            
            <div class="form-group">
              <label for="product_title">Product Title</label>
              <input type="text" id="product_title" name="product_title" value="<?php echo $product['product_title']; ?>" required>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="p_cat_id">Product Category</label>
                <select id="p_cat_id" name="p_cat_id" required>
                  <option value="">Select a Category</option>
                  <?php
                  if($p_cat_result && $p_cat_result->num_rows > 0) {
                      $p_cat_result->data_seek(0);
                      while($row = $p_cat_result->fetch_assoc()) {
                          $selected = ($row['p_cat_id'] == $product['p_cat_id']) ? 'selected' : '';
                          echo "<option value='" . $row['p_cat_id'] . "' $selected>" . $row['p_cat_title'] . "</option>";
                      }
                  }
                  ?>
                </select>
              </div>
              
              <!-- Add cat_id field if categories table exists -->
              <?php if ($result->num_rows > 0): ?>
              <div class="form-group">
                <label for="cat_id">Category</label>
                <select id="cat_id" name="cat_id">
                  <option value="0">Select a Category</option>
                  <?php
                  if($cat_result && $cat_result->num_rows > 0) {
                      $cat_result->data_seek(0);
                      while($row = $cat_result->fetch_assoc()) {
                          $selected = ($row['cat_id'] == $product['cat_id']) ? 'selected' : '';
                          echo "<option value='" . $row['cat_id'] . "' $selected>" . $row['cat_title'] . "</option>";
                      }
                  }
                  ?>
                </select>
              </div>
              <?php endif; ?>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="manufacturer_id">Brand</label>
                <select id="manufacturer_id" name="manufacturer_id" required>
                  <option value="">Select a Brand</option>
                  <?php
                  if($manufacturer_result && $manufacturer_result->num_rows > 0) {
                      $manufacturer_result->data_seek(0);
                      while($row = $manufacturer_result->fetch_assoc()) {
                          $selected = ($row['manufacturer_id'] == $product['manufacturer_id']) ? 'selected' : '';
                          echo "<option value='" . $row['manufacturer_id'] . "' $selected>" . $row['manufacturer_title'] . "</option>";
                      }
                  }
                  ?>
                </select>
              </div>
              
              <div class="form-group">
                <label for="product_url">Product URL</label>
                <input type="text" id="product_url" name="product_url" value="<?php echo $product['product_url']; ?>" required>
                <small>URL should be lowercase with hyphens instead of spaces</small>
              </div>
            </div>
            
            <!-- New Image Upload Section -->
            <div class="form-group">
              <label>Product Images</label>
              <div class="image-upload-container">
                <!-- Primary Image Upload -->
                <div class="primary-image-upload">
                  <label class="title">Primary Image <span class="badge">Required</span></label>
                  
                  <?php if ($primary_image): ?>
                  <div class="current-image-container">
                    <div class="current-image-preview">
                      <img src="<?php echo 'product_images/' . $primary_image['image_path']; ?>" alt="Current Primary Image">
                    </div>
                    <div class="current-image-info">
                      <span class="current-image-name">Current: <?php echo $primary_image['image_path']; ?></span>
                    </div>
                  </div>
                  <?php endif; ?>
                  
                  <input type="file" id="primary_image" name="primary_image" accept="image/*" class="hidden-file-input" <?php echo !$primary_image ? 'required' : ''; ?>>
                  <label for="primary_image" class="file-upload-btn">
                    <i class="fas fa-upload"></i> <?php echo $primary_image ? 'Change Primary Image' : 'Choose Primary Image'; ?>
                  </label>
                  <div class="image-preview" id="primary_preview"></div>
                </div>
                
                <!-- Additional Images Upload -->
                <div class="additional-images-upload">
                  <label class="title">Additional Images</label>
                  
                  <?php if (count($product_images) > 0): ?>
                  <div class="existing-images-container">
                    <?php foreach ($product_images as $image): ?>
                    <div class="existing-image-item">
                      <div class="existing-image-preview">
                        <img src="<?php echo 'product_images/' . $image['image_path']; ?>" alt="Additional Image">
                      </div>
                      <div class="existing-image-info">
                        <div class="existing-image-name"><?php echo $image['image_path']; ?></div>
                      </div>
                      <div class="existing-image-actions">
                        <input type="checkbox" id="delete_image_<?php echo $image[$primaryKeyColumn]; ?>" name="delete_images[]" value="<?php echo $image[$primaryKeyColumn]; ?>" class="delete-image-checkbox">
                        <label for="delete_image_<?php echo $image[$primaryKeyColumn]; ?>" class="delete-image-label">Delete</label>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                  
                  <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple class="hidden-file-input">
                  <label for="additional_images" class="file-upload-btn">
                    <i class="fas fa-images"></i> Add More Images
                  </label>
                  <div class="selected-files-container" id="selected_files_container">
                    <div class="no-files-message">No new additional images selected</div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="product_price">Original Price</label>
                <input type="number" id="product_price" name="product_price" value="<?php echo $product['product_price']; ?>" required>
              </div>
              
              <div class="form-group">
                <label for="product_psp_price">Sale Price</label>
                <input type="number" id="product_psp_price" name="product_psp_price" value="<?php echo $product['product_psp_price'] > 0 ? $product['product_psp_price'] : ''; ?>">
                <small>Leave empty if not on sale</small>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="stock">Stock Quantity</label>
                <input type="number" id="stock" name="stock" min="0" value="<?php echo $product['stock']; ?>" required>
                <small>Enter the number of items in stock</small>
              </div>
            </div>
            
            <div class="form-group">
              <label for="product_desc">Product Description</label>
              <textarea id="product_desc" name="product_desc" rows="4" required><?php echo $product['product_desc']; ?></textarea>
            </div>
            
            <div class="form-group">
              <label for="product_features">Product Features</label>
              <textarea id="product_features" name="product_features" rows="4" required><?php echo $product['product_features']; ?></textarea>
              <small>Enter one feature per line. Use format "Feature: Value" for specifications</small>
            </div>
            
            <div class="form-group">
              <label for="product_video">Product Video URL (Optional)</label>
              <input type="url" id="product_video" name="product_video" value="<?php echo $product['product_video']; ?>">
              <small>YouTube or Vimeo URL</small>
            </div>
            
            <div class="form-group">
              <label for="product_keywords">Product Keywords</label>
              <input type="text" id="product_keywords" name="product_keywords" value="<?php echo $product['product_keywords']; ?>" required>
              <small>Comma separated keywords for search</small>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="product_label">Product Label</label>
                <select id="product_label" name="product_label">
                  <option value="" <?php echo empty($product['product_label']) ? 'selected' : ''; ?>>No Label</option>
                  <option value="new" <?php echo $product['product_label'] == 'new' ? 'selected' : ''; ?>>New</option>
                  <option value="sale" <?php echo $product['product_label'] == 'sale' ? 'selected' : ''; ?>>Sale</option>
                  <option value="hot" <?php echo $product['product_label'] == 'hot' ? 'selected' : ''; ?>>Hot</option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                  <option value="product" <?php echo $product['status'] == 'product' ? 'selected' : ''; ?>>Active</option>
                  <option value="draft" <?php echo $product['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                  <option value="bundle" <?php echo $product['status'] == 'bundle' ? 'selected' : ''; ?>>Bundle</option>
                </select>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" name="update" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Update Product
              </button>
              <a href="view-products.php" class="btn btn-outline">
                <i class="fa-solid fa-times"></i> Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const dashboardContainer = document.querySelector('.dashboard-container');
    
    sidebarToggle.addEventListener('click', function() {
      dashboardContainer.classList.toggle('sidebar-collapsed');
    });
    
    // Submenu toggle
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(toggle => {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const parent = this.parentElement;
        parent.classList.toggle('open');
      });
    });
    
    // Keep current submenu open
    const currentPage = window.location.pathname.split('/').pop();
    const submenuItems = document.querySelectorAll('.submenu li a');
    
    submenuItems.forEach(item => {
      const itemPage = item.getAttribute('href');
      // Check if this menu item links to current page or if item is already marked active
      if (itemPage === currentPage || item.parentElement.classList.contains('active')) {
        // Find parent submenu and open it
        const parentSubmenu = item.closest('.has-submenu');
        if (parentSubmenu) {
          parentSubmenu.classList.add('open');
        }
      }
    });
    
    // Primary image preview
    const primaryImageInput = document.getElementById('primary_image');
    const primaryPreview = document.getElementById('primary_preview');
    
    primaryImageInput.addEventListener('change', function() {
      primaryPreview.innerHTML = '';
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const img = document.createElement('img');
          img.src = e.target.result;
          primaryPreview.appendChild(img);
        }
        reader.readAsDataURL(this.files[0]);
      }
    });
    
    // Additional images preview
    const additionalImagesInput = document.getElementById('additional_images');
    const selectedFilesContainer = document.getElementById('selected_files_container');
    
    additionalImagesInput.addEventListener('change', function() {
      // Clear the container
      selectedFilesContainer.innerHTML = '';
      
      if (this.files.length === 0) {
        selectedFilesContainer.innerHTML = '<div class="no-files-message">No new additional images selected</div>';
        return;
      }
      
      // Create a file item for each selected file
      Array.from(this.files).forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'selected-file-item';
        fileItem.dataset.index = index;
        
        const filePreview = document.createElement('div');
        filePreview.className = 'selected-file-preview';
        
        const fileInfo = document.createElement('div');
        fileInfo.className = 'selected-file-info';
        
        const fileName = document.createElement('div');
        fileName.className = 'selected-file-name';
        fileName.textContent = file.name;
        
        const fileSize = document.createElement('div');
        fileSize.className = 'selected-file-size';
        fileSize.textContent = formatFileSize(file.size);
        
        fileInfo.appendChild(fileName);
        fileInfo.appendChild(fileSize);
        
        // Create a reader to generate preview
        const reader = new FileReader();
        reader.onload = function(e) {
          const img = document.createElement('img');
          img.src = e.target.result;
          filePreview.appendChild(img);
        }
        reader.readAsDataURL(file);
        
        fileItem.appendChild(filePreview);
        fileItem.appendChild(fileInfo);
        
        selectedFilesContainer.appendChild(fileItem);
      });
    });
    
    // Format file size to human-readable format
    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Auto-generate URL from title
    document.getElementById('product_title').addEventListener('input', function() {
      // Only auto-generate URL if it hasn't been manually edited
      const urlField = document.getElementById('product_url');
      if (!urlField.dataset.edited) {
        const title = this.value;
        const url = title.toLowerCase()
                        .replace(/[^\w\s-]/g, '') // Remove special characters
                        .replace(/\s+/g, '-')     // Replace spaces with hyphens
                        .replace(/-+/g, '-');     // Replace multiple hyphens with single hyphen
        
        urlField.value = url;
      }
    });
    
    // Mark URL field as edited when user changes it
    document.getElementById('product_url').addEventListener('input', function() {
      this.dataset.edited = true;
    });
  });
</script>
</body>
</html>
