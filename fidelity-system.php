<?php
  // Start session and check if admin is logged in
  include 'C:\xampp\htdocs\PPD\php\config.php';
  session_start();
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  if (!isset($_SESSION['admin_id'])) {
    header("Location: auth.php");
    exit;
  }
  // Function to sanitize input data
  function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
  }

  // Function to safely handle htmlspecialchars for potentially null values
  function safeHtmlSpecialChars($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
  }

  // Check if fidelity columns exist in products table, if not create them
  $checkColumnsQuery = "SHOW COLUMNS FROM products LIKE 'fidelity_percentage'";
  $columnResult = $conn->query($checkColumnsQuery);
  if ($columnResult->num_rows == 0) {
    $alterTableQuery = "ALTER TABLE products 
                        ADD COLUMN fidelity_percentage INT DEFAULT 0,
                        ADD COLUMN fidelity_score INT DEFAULT 0";
    $conn->query($alterTableQuery);
  }

  // Check if fidelity_gifts table exists, if not create it
  $checkTableQuery = "SHOW TABLES LIKE 'fidelity_gifts'";
  $tableResult = $conn->query($checkTableQuery);
  if ($tableResult->num_rows == 0) {
    $createTableQuery = "CREATE TABLE fidelity_gifts (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      description TEXT,
      type ENUM('discount', 'accessory') NOT NULL,
      value INT NOT NULL,
      required_points INT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      min_product_price INT DEFAULT 0
    )";
    $conn->query($createTableQuery);
    
    // Insert sample data
    $sampleDataQuery = "INSERT INTO fidelity_gifts (name, description, type, value, required_points) VALUES
      ('10% Discount', 'Get 10% off on your next purchase', 'discount', 10, 1000),
      ('20% Discount', 'Get 20% off on your next purchase', 'discount', 20, 2000),
      ('Free Phone Case', 'Get a free phone case with your next purchase', 'accessory', 1500, 1500),
      ('Free Wireless Earbuds', 'Get free wireless earbuds with your next purchase', 'accessory', 3000, 3000)";
    $conn->query($sampleDataQuery);
  }

  // Handle AJAX requests
  if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    // Update product fidelity percentage and calculate score
    if ($_GET['action'] === 'update_percentage' && isset($_POST['product_id']) && isset($_POST['percentage'])) {
      $productId = intval($_POST['product_id']);
      $percentage = intval($_POST['percentage']);
      
      // Get product price
      $priceQuery = "SELECT product_price, product_psp_price FROM products WHERE product_id = $productId";
      $priceResult = $conn->query($priceQuery);
      
      if ($priceResult && $priceResult->num_rows > 0) {
        $product = $priceResult->fetch_assoc();
        // Use promotional price if available, otherwise use regular price
        $price = $product['product_psp_price'] > 0 ? $product['product_psp_price'] : $product['product_price'];
        
        // Calculate score
        $score = floor(($price * $percentage) / 100);
        
        // Update product
        $updateQuery = "UPDATE products 
                        SET fidelity_percentage = $percentage, 
                            fidelity_score = $score 
                        WHERE product_id = $productId";
        
        if ($conn->query($updateQuery)) {
          echo json_encode(['success' => true, 'score' => $score]);
        } else {
          echo json_encode(['success' => false, 'message' => $conn->error]);
        }
      } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
      }
      exit;
    }
    
    // Add new fidelity gift
    if ($_GET['action'] === 'add_gift' && isset($_POST['name'])) {
      $name = sanitize($_POST['name']);
      $description = sanitize($_POST['description']);
      $type = sanitize($_POST['type']);
      $value = intval($_POST['value']);
      $requiredPoints = intval($_POST['required_points']);
      $minProductPrice = isset($_POST['min_product_price']) ? intval($_POST['min_product_price']) : 0;
      
      $insertQuery = "INSERT INTO fidelity_gifts (name, description, type, value, required_points, min_product_price) 
                      VALUES ('$name', '$description', '$type', $value, $requiredPoints, $minProductPrice)";
      
      if ($conn->query($insertQuery)) {
        $newId = $conn->insert_id;
        echo json_encode([
          'success' => true, 
          'gift' => [
            'id' => $newId,
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'value' => $value,
            'required_points' => $requiredPoints,
            'min_product_price' => $minProductPrice
          ]
        ]);
      } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
      }
      exit;
    }
    
    // Delete fidelity gift
    if ($_GET['action'] === 'delete_gift' && isset($_POST['gift_id'])) {
      $giftId = intval($_POST['gift_id']);
      
      $deleteQuery = "DELETE FROM fidelity_gifts WHERE id = $giftId";
      
      if ($conn->query($deleteQuery)) {
        echo json_encode(['success' => true]);
      } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
      }
      exit;
    }
  }

  // Get products with fidelity data
  $productsQuery = "SELECT p.*, pc.p_cat_title, m.manufacturer_title 
                   FROM products p
                   LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
                   LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
                   ORDER BY p.product_id DESC";
  $productsResult = $conn->query($productsQuery);
  $products = [];
  if ($productsResult && $productsResult->num_rows > 0) {
    while ($row = $productsResult->fetch_assoc()) {
      // Get product image from product_images table
      $product_id = $row['product_id'];
      $image_query = "SELECT image_path FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC LIMIT 1";
      $img_result = $conn->query($image_query);
      if ($img_result && $img_result->num_rows > 0) {
        $img_row = $img_result->fetch_assoc();
        $image_path = $img_row['image_path'];
        // Check if the path already includes 'product_images/'
        if (strpos($image_path, 'product_images/') === false) {
          $row['product_img1'] = 'product_images/' . $image_path;
        } else {
          $row['product_img1'] = $image_path;
        }
      } else {
        $row['product_img1'] = 'https://via.placeholder.com/50x50';
      }
      $products[] = $row;
    }
  }

  // Get fidelity gifts
  $giftsQuery = "SELECT * FROM fidelity_gifts ORDER BY required_points ASC";
  $giftsResult = $conn->query($giftsQuery);
  $gifts = [];
  if ($giftsResult && $giftsResult->num_rows > 0) {
    while ($row = $giftsResult->fetch_assoc()) {
      $gifts[] = $row;
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fidelity System - Admin Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Additional styles for fidelity system */
    .tabs {
      display: flex;
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 20px;
    }
    
    .tab {
      padding: 10px 20px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 500;
      color: var(--text-secondary);
      background: none;
      border: none;
      position: relative;
    }
    
    .tab.active {
      color: var(--accent-color);
    }
    
    .tab.active::after {
      content: "";
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 100%;
      height: 2px;
      background-color: var(--accent-color);
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
    }
    
    .percentage-input {
      width: 70px;
      padding: 5px;
      text-align: center;
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 4px;
      color: var(--text-primary);
    }
    
    .score-value {
      font-weight: bold;
      color: var(--accent-color);
    }
    
    .product-thumbnail {
      width: 50px;
      height: 50px;
      object-fit: contain;
      border-radius: 4px;
      background-color: #fff;
    }
    
    .gift-form {
      background-color: var(--bg-secondary);
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 8px 12px;
      background-color: var(--bg-primary);
      border: 1px solid var(--border-color);
      border-radius: 4px;
      color: var(--text-primary);
    }
    
    .gift-type-badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .gift-type-discount {
      background-color: rgba(74, 222, 128, 0.2);
      color: var(--accent-color);
    }
    
    .gift-type-accessory {
      background-color: rgba(59, 130, 246, 0.2);
      color: #3b82f6;
    }
    
    .btn-add-gift {
      background-color: var(--accent-color);
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 4px;
      cursor: pointer;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
    }
    
    .btn-add-gift:hover {
      background-color: #22c55e;
    }
    
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .search-container {
      margin-bottom: 20px;
    }
    
    .search-input {
      width: 100%;
      max-width: 300px;
      padding: 8px 12px 8px 35px;
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 4px;
      color: var(--text-primary);
    }
    
    .search-wrapper {
      position: relative;
      max-width: 300px;
    }
    
    .search-wrapper i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-secondary);
    }
    
    .grid-container {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }
    
    @media (min-width: 768px) {
      .grid-container {
        grid-template-columns: 1fr 1fr;
      }
    }

    .calculate-btn {
      background-color: var(--accent-color);
      color: white;
      border: none;
      border-radius: 4px;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .calculate-btn:hover {
      background-color: #22c55e;
    }
  </style>
</head>
<body class="dark-theme">
  <div class="dashboard-container">
    <!-- Sidebar -->
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
            <li>
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
                <li><a href="view-products.php">View Products</a></li>
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
            
            <li class="active">
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
              <img src="<?php echo $_SESSION['admin_image'] ? 'admin_images/' . safeHtmlSpecialChars($_SESSION['admin_image']) : 'admin_images/default-profile.png'; ?>" alt="Admin Profile" class="profile-image">
              <div class="user-info">
                <span class="user-name"><?php echo safeHtmlSpecialChars($_SESSION['admin_name']); ?></span>
                <span class="user-email"><?php echo safeHtmlSpecialChars($_SESSION['admin_email']); ?></span>
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
          <h1>Fidelity System</h1>
        </div>
        <div class="header-right">
          <div class="search-container">
            <input type="search" placeholder="Search..." id="search-input">
            <i class="fa-solid fa-search"></i>
          </div>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="card">
          <div class="card-header">
            <div class="tabs">
              <button class="tab active" data-tab="products-tab">Products Fidelity</button>
              <button class="tab" data-tab="gifts-tab">Fidelity Gifts</button>
            </div>
          </div>
          <div class="card-content">
            <!-- Products Tab -->
            <div class="tab-content active" id="products-tab">
              <div class="search-container">
                <div class="search-wrapper">
                  <i class="fa-solid fa-search"></i>
                  <input type="text" id="product-search" class="search-input" placeholder="Search products...">
                </div>
              </div>
              
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Price (DA)</th>
                    <th>Percentage (%)</th>
                    <th>Score</th>
                  </tr>
                </thead>
                <tbody id="products-list">
                  <?php foreach ($products as $product): ?>
                    <tr data-product-id="<?php echo $product['product_id']; ?>" data-product-title="<?php echo safeHtmlSpecialChars($product['product_title']); ?>" data-product-category="<?php echo safeHtmlSpecialChars($product['p_cat_title'] ?? ''); ?>" data-product-brand="<?php echo safeHtmlSpecialChars($product['manufacturer_title'] ?? ''); ?>">
                      <td>
                        <img src="<?php echo safeHtmlSpecialChars($product['product_img1']); ?>" alt="<?php echo safeHtmlSpecialChars($product['product_title']); ?>" class="product-thumbnail">
                      </td>
                      <td><?php echo safeHtmlSpecialChars($product['product_title']); ?></td>
                      <td><?php echo safeHtmlSpecialChars($product['p_cat_title'] ?? ''); ?></td>
                      <td><?php echo safeHtmlSpecialChars($product['manufacturer_title'] ?? ''); ?></td>
                      <td><?php echo number_format($product['product_psp_price'] > 0 ? $product['product_psp_price'] : $product['product_price']); ?></td>
                      <td>
                        <div style="display: flex; align-items: center; gap: 5px;">
                          <input 
                            type="number" 
                            class="percentage-input" 
                            min="0" 
                            max="100" 
                            value="<?php echo $product['fidelity_percentage'] ?? 0; ?>" 
                            data-product-id="<?php echo $product['product_id']; ?>"
                            data-product-price="<?php echo $product['product_psp_price'] > 0 ? $product['product_psp_price'] : $product['product_price']; ?>"
                          >
                          <button 
                            type="button" 
                            class="calculate-btn btn-icon" 
                            data-product-id="<?php echo $product['product_id']; ?>"
                            title="Calculate Points"
                          >
                            <i class="fa-solid fa-calculator"></i>
                          </button>
                        </div>
                      </td>
                      <td>
                        <span class="score-value" id="score-<?php echo $product['product_id']; ?>">
                          <?php echo $product['fidelity_score'] ?? 0; ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            
            <!-- Gifts Tab -->
            <div class="tab-content" id="gifts-tab">
              <div class="grid-container">
                <!-- Gifts List -->
                <div class="card">
                  <div class="card-header">
                    <h3 class="card-title">Fidelity Gifts</h3>
                  </div>
                  <div class="card-content">
                    <table class="data-table">
                      <thead>
                        <tr>
                          <th>Gift Name</th>
                          <th>Description</th>
                          <th>Type</th>
                          <th>Value</th>
                          <th>Required Points</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody id="gifts-list">
                        <?php foreach ($gifts as $gift): ?>
                          <tr data-gift-id="<?php echo $gift['id']; ?>">
                            <td><?php echo safeHtmlSpecialChars($gift['name']); ?></td>
                            <td><?php echo safeHtmlSpecialChars($gift['description']); ?></td>
                            <td>
                              <span class="gift-type-badge gift-type-<?php echo $gift['type']; ?>">
                                <?php echo $gift['type'] === 'discount' ? 'Discount' : 'Accessory'; ?>
                              </span>
                            </td>
                            <td>
                              <?php echo $gift['type'] === 'discount' ? $gift['value'] . '%' : number_format($gift['value']) . ' DA'; ?>
                            </td>
                            <td><?php echo number_format($gift['required_points']); ?></td>
                            <td>
                              <button class="btn-icon delete-gift" data-gift-id="<?php echo $gift['id']; ?>">
                                <i class="fa-solid fa-trash"></i>
                              </button>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                
                <!-- Add Gift Form -->
                <div class="card">
                  <div class="card-header">
                    <h3 class="card-title">Add New Gift</h3>
                  </div>
                  <div class="card-content">
                    <form id="add-gift-form" class="gift-form">
                      <div class="form-group">
                        <label for="gift-name">Gift Name</label>
                        <input type="text" id="gift-name" name="name" required>
                      </div>
                      
                      <div class="form-group">
                        <label for="gift-description">Description</label>
                        <textarea id="gift-description" name="description" rows="3"></textarea>
                      </div>
                      
                      <div class="form-group">
                        <label for="gift-type">Type</label>
                        <select id="gift-type" name="type">
                          <option value="discount">Discount</option>
                          <option value="accessory">Accessory</option>
                        </select>
                      </div>
                      
                      <div class="form-group">
                        <label for="gift-value" id="value-label">Discount Percentage</label>
                        <input type="number" id="gift-value" name="value" min="0" required>
                      </div>
                      
                      <div class="form-group">
                        <label for="gift-points">Required Points</label>
                        <input type="number" id="gift-points" name="required_points" min="0" required>
                      </div>
                      
                      <div class="form-group" id="min-price-container">
                        <label for="min-product-price">Minimum Product Price (DA)</label>
                        <input type="number" id="min-product-price" name="min_product_price" min="0" value="0">
                        <small>Set to 0 for no minimum price requirement</small>
                      </div>
                      
                      <button type="submit" class="btn-add-gift" id="submit-gift">
                        <i class="fa-solid fa-plus"></i> Add Gift
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
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
      
      // Tab switching
      const tabs = document.querySelectorAll('.tab');
      const tabContents = document.querySelectorAll('.tab-content');
      
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Remove active class from all tabs
          tabs.forEach(t => t.classList.remove('active'));
          // Add active class to clicked tab
          this.classList.add('active');
          
          // Hide all tab content
          tabContents.forEach(content => content.classList.remove('active'));
          
          // Show the corresponding tab content
          const tabId = this.getAttribute('data-tab');
          document.getElementById(tabId).classList.add('active');
        });
      });
      
      // Handle percentage input changes
      const percentageInputs = document.querySelectorAll('.percentage-input');
      percentageInputs.forEach(input => {
        input.addEventListener('change', function() {
          const productId = this.getAttribute('data-product-id');
          const percentage = parseInt(this.value) || 0;
          
          // Ensure percentage is between 0 and 100
          if (percentage < 0) this.value = 0;
          if (percentage > 100) this.value = 100;
          
          updateProductPercentage(productId, this.value);
        });
      });
      
      // Calculate button click handler
      const calculateButtons = document.querySelectorAll('.calculate-btn');
      calculateButtons.forEach(button => {
        button.addEventListener('click', function() {
          const productId = this.getAttribute('data-product-id');
          const inputField = document.querySelector(`.percentage-input[data-product-id="${productId}"]`);
          const percentage = inputField.value;
          
          // Update the product percentage
          updateProductPercentage(productId, percentage);
        });
      });
      
      // Function to update product percentage via AJAX
      function updateProductPercentage(productId, percentage) {
        const scoreElement = document.getElementById(`score-${productId}`);
        const originalText = scoreElement.textContent;
        scoreElement.innerHTML = '<div class="loading-spinner"></div>';
        
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('percentage', percentage);
        
        fetch('fidelity-system.php?action=update_percentage', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            scoreElement.textContent = data.score;
          } else {
            alert('Error updating percentage: ' + data.message);
            scoreElement.textContent = originalText;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while updating the percentage.');
          scoreElement.textContent = originalText;
        });
      }
      
      // Product search functionality
      const productSearch = document.getElementById('product-search');
      productSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#products-list tr');
        
        rows.forEach(row => {
          const title = row.getAttribute('data-product-title').toLowerCase();
          const category = row.getAttribute('data-product-category').toLowerCase();
          const brand = row.getAttribute('data-product-brand').toLowerCase();
          
          if (title.includes(searchTerm) || category.includes(searchTerm) || brand.includes(searchTerm)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      });
      
      // Gift type change handler
      const giftType = document.getElementById('gift-type');
      const valueLabel = document.getElementById('value-label');
      
      giftType.addEventListener('change', function() {
        if (this.value === 'discount') {
          valueLabel.textContent = 'Discount Percentage';
        } else {
          valueLabel.textContent = 'Accessory Price (DA)';
        }
      });
      
      // Add gift form submission
      const addGiftForm = document.getElementById('add-gift-form');
      addGiftForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitButton = document.getElementById('submit-gift');
        const originalButtonContent = submitButton.innerHTML;
        submitButton.innerHTML = '<div class="loading-spinner"></div> Adding...';
        submitButton.disabled = true;
        
        const formData = new FormData(this);
        
        fetch('fidelity-system.php?action=add_gift', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Add new gift to the table
            const gift = data.gift;
            const giftsList = document.getElementById('gifts-list');
            
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-gift-id', gift.id);
            
            newRow.innerHTML = `
              <td>${gift.name}</td>
              <td>${gift.description}</td>
              <td>
                <span class="gift-type-badge gift-type-${gift.type}">
                  ${gift.type === 'discount' ? 'Discount' : 'Accessory'}
                </span>
              </td>
              <td>
                ${gift.type === 'discount' ? gift.value + '%' : gift.value.toLocaleString() + ' DA'}
              </td>
              <td>${gift.required_points.toLocaleString()}</td>
              <td>
                <button class="btn-icon delete-gift" data-gift-id="${gift.id}">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            `;
            
            giftsList.appendChild(newRow);
            
            // Reset form
            addGiftForm.reset();
            
            // Add event listener to the new delete button
            const newDeleteButton = newRow.querySelector('.delete-gift');
            newDeleteButton.addEventListener('click', function() {
              const giftId = this.getAttribute('data-gift-id');
              deleteGift(giftId);
            });
            
            alert('Gift added successfully!');
          } else {
            alert('Error adding gift: ' + data.message);
          }
          
          submitButton.innerHTML = originalButtonContent;
          submitButton.disabled = false;
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while adding the gift.');
          submitButton.innerHTML = originalButtonContent;
          submitButton.disabled = false;
        });
      });
      
      // Delete gift functionality
      const deleteButtons = document.querySelectorAll('.delete-gift');
      deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
          const giftId = this.getAttribute('data-gift-id');
          deleteGift(giftId);
        });
      });
      
      function deleteGift(giftId) {
        if (confirm('Are you sure you want to delete this gift?')) {
          const formData = new FormData();
          formData.append('gift_id', giftId);
          
          fetch('fidelity-system.php?action=delete_gift', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Remove the row from the table
              const row = document.querySelector(`tr[data-gift-id="${giftId}"]`);
              if (row) {
                row.remove();
              }
              alert('Gift deleted successfully!');
            } else {
              alert('Error deleting gift: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the gift.');
          });
        }
      }
    });
  </script>
</body>
</html>
