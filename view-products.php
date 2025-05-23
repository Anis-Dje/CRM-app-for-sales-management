<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
  header("Location: auth.php");
  exit;
}
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecom_store";
$port = 3307; // As specified in your previous files

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get categories for filter dropdown
$categoriesQuery = "SELECT * FROM product_categories ORDER BY p_cat_title";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
if ($categoriesResult && $categoriesResult->num_rows > 0) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get manufacturers for filter dropdown
$manufacturersQuery = "SELECT * FROM manufacturers ORDER BY manufacturer_title";
$manufacturersResult = $conn->query($manufacturersQuery);
$manufacturers = [];
if ($manufacturersResult && $manufacturersResult->num_rows > 0) {
    while ($row = $manufacturersResult->fetch_assoc()) {
        $manufacturers[] = $row;
    }
}

// Pagination and filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$brandFilter = isset($_GET['brand']) ? intval($_GET['brand']) : 0;
$statusFilter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Build query based on filters
$countQuery = "SELECT COUNT(*) as total FROM products p 
               LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
               LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
               WHERE 1=1";

$productsQuery = "SELECT p.*, pc.p_cat_title, m.manufacturer_title 
                 FROM products p
                 LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
                 LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
                 WHERE 1=1";

// Add filters to query
if (!empty($search)) {
    $searchCondition = " AND (p.product_title LIKE '%$search%' OR p.product_keywords LIKE '%$search%')";
    $countQuery .= $searchCondition;
    $productsQuery .= $searchCondition;
}

if ($categoryFilter > 0) {
    $categoryCondition = " AND p.p_cat_id = $categoryFilter";
    $countQuery .= $categoryCondition;
    $productsQuery .= $categoryCondition;
}

if ($brandFilter > 0) {
    $brandCondition = " AND p.manufacturer_id = $brandFilter";
    $countQuery .= $brandCondition;
    $productsQuery .= $brandCondition;
}

if (!empty($statusFilter)) {
    $statusCondition = " AND p.status = '$statusFilter'";
    $countQuery .= $statusCondition;
    $productsQuery .= $statusCondition;
}

// Get total count for pagination
$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Complete the products query with order and limit
$productsQuery .= " ORDER BY p.product_id DESC LIMIT $offset, $itemsPerPage";

// Execute the query
$productsResult = $conn->query($productsQuery);
$products = [];

if ($productsResult && $productsResult->num_rows > 0) {
    // Check if product_images table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
    $hasImagesTable = $tableCheck->num_rows > 0;
    
    while ($row = $productsResult->fetch_assoc()) {
        if ($hasImagesTable) {
            // Get primary image from product_images table
            $imageQuery = "SELECT image_path FROM product_images WHERE product_id = {$row['product_id']} AND is_primary = 1 LIMIT 1";
            $imageResult = $conn->query($imageQuery);
            
            if ($imageResult && $imageResult->num_rows > 0) {
                $imagePath = $imageResult->fetch_assoc()['image_path'];
                $row['product_img1'] = 'product_images/' . $imagePath;
            } else {
                // Try to get any image if no primary image
                $imageQuery = "SELECT image_path FROM product_images WHERE product_id = {$row['product_id']} LIMIT 1";
                $imageResult = $conn->query($imageQuery);
                
                if ($imageResult && $imageResult->num_rows > 0) {
                    $imagePath = $imageResult->fetch_assoc()['image_path'];
                    $row['product_img1'] = 'product_images/' . $imagePath;
                } else {
                    $row['product_img1'] = '/placeholder.svg?height=100&width=100';
                }
            }
        } else {
            // Fallback to placeholder if no images table
            $row['product_img1'] = '/placeholder.svg?height=100&width=100';
        }
        
        $products[] = $row;
    }
}

// Handle AJAX requests for product details
if (isset($_GET['ajax']) && $_GET['ajax'] === 'product-details' && isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    
    // First get the basic product details
    $detailsQuery = "SELECT p.*, pc.p_cat_title, m.manufacturer_title 
                    FROM products p
                    LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
                    LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
                    WHERE p.product_id = $productId";
    
    $detailsResult = $conn->query($detailsQuery);
    
    if ($detailsResult && $detailsResult->num_rows > 0) {
        $product = $detailsResult->fetch_assoc();
        
        // Check if product_images table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
        $hasImagesTable = $tableCheck->num_rows > 0;
        
        if ($hasImagesTable) {
            // Get all images for this product from product_images table
            $imagesQuery = "SELECT image_path, is_primary FROM product_images WHERE product_id = $productId ORDER BY is_primary DESC, id ASC";
            $imagesResult = $conn->query($imagesQuery);
            
            $images = [];
            if ($imagesResult && $imagesResult->num_rows > 0) {
                while ($img = $imagesResult->fetch_assoc()) {
                    $images[] = $img;
                }
                
                // Assign images to product
                if (count($images) > 0) {
                    $product['product_img1'] = 'product_images/' . $images[0]['image_path'];
                    
                    if (count($images) > 1) {
                        $product['product_img2'] = 'product_images/' . $images[1]['image_path'];
                    }
                    
                    if (count($images) > 2) {
                        $product['product_img3'] = 'product_images/' . $images[2]['image_path'];
                    }
                } else {
                    $product['product_img1'] = '/placeholder.svg?height=300&width=300';
                }
            } else {
                $product['product_img1'] = '/placeholder.svg?height=300&width=300';
            }
        } else {
            // Fallback to placeholder if no images table
            $product['product_img1'] = '/placeholder.svg?height=300&width=300';
        }
        
        // Ensure all required fields have default values
        $product['product_psp_price'] = $product['product_psp_price'] ?? 0;
        $product['product_label'] = $product['product_label'] ?? '';
        $product['product_video'] = $product['product_video'] ?? '';
        $product['stock'] = $product['stock'] ?? 0;
        
        header('Content-Type: application/json');
        echo json_encode($product);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
}

// Handle AJAX requests for product deletion
if (isset($_GET['ajax']) && $_GET['ajax'] === 'delete-product' && isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    
    // First delete associated images from product_images table
    $deleteImagesQuery = "DELETE FROM product_images WHERE product_id = $productId";
    $conn->query($deleteImagesQuery);
    
    // Then delete the product
    $deleteQuery = "DELETE FROM products WHERE product_id = $productId";
    $deleteResult = $conn->query($deleteQuery);
    
    if ($deleteResult) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $conn->error]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Products - Admin Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
        .product-thumbnail {
      width: 100px;
      height: 100px;
      object-fit: contain;
      border-radius: 4px;
      border: 1px solid var(--border-color);
      background-color: #fff;
    }

    /* Modal image styles */
    .modal .main-image {
      height: 250px;
    }

    .modal .thumbnail {
      width: 50px;
      height: 50px;
    }

    /* Image upload and preview styles */
    .image-upload-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .image-upload {
      flex: 1;
      min-width: 200px;
      max-width: 250px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 15px;
      background-color: rgba(255, 255, 255, 0.05);
    }

    .image-upload label {
      display: block;
      margin-bottom: 10px;
      font-weight: 500;
    }

    .image-preview {
      width: 100%;
      height: 150px;
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

    /* Make file inputs more visually appealing */
    input[type="file"] {
      width: 100%;
      padding: 8px;
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 4px;
      color: var(--text-secondary);
    }

    /* Product details page styles */
    .product-gallery .main-image {
      height: 300px;
    }

    .product-gallery .thumbnail {
      width: 60px;
      height: 60px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .image-upload-container {
        flex-direction: column;
      }
      
      .image-upload {
        max-width: 100%;
      }
      
      .product-gallery .main-image {
        height: 250px;
      }
    }
    .category-filter{
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
                <li class = active><a href="view-products.php">View Products</a></li>
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
          <h1>View Products</h1>
        </div>
        <div class="header-right">
          <div class="search-container">
            <input type="search" placeholder="Search products..." id="product-search">
            <i class="fa-solid fa-search"></i>
          </div>
          <a href="insert-product.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add New Product
          </a>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="card">
          <div class="card-header table-header">
            <div class="tabs">
              <button class="tab <?php echo empty($statusFilter) ? 'active' : ''; ?>" data-tab="all-products">All Products</button>
              <button class="tab <?php echo $statusFilter === 'product' ? 'active' : ''; ?>" data-tab="product-status">Active</button>
              <button class="tab <?php echo $statusFilter === 'bundle' ? 'active' : ''; ?>" data-tab="bundle-status">Bundle</button>
              <button class="tab <?php echo $statusFilter === 'draft' ? 'active' : ''; ?>" data-tab="draft-status">Draft</button>
            </div>
            <div class="table-actions">
              <form id="filter-form" method="get" action="view-products.php">
                <select id="category-filter" name="category" class="category-filter">
                  <option value="">All Categories</option>
                  <?php foreach ($categories as $category): ?>
                  <option value="<?php echo $category['p_cat_id']; ?>" <?php echo $categoryFilter == $category['p_cat_id'] ? 'selected' : ''; ?>>
                    <?php echo $category['p_cat_title']; ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <select id="brand-filter" name="brand" class="category-filter">
                  <option value="">All Brands</option>
                  <?php foreach ($manufacturers as $manufacturer): ?>
                  <option value="<?php echo $manufacturer['manufacturer_id']; ?>" <?php echo $brandFilter == $manufacturer['manufacturer_id'] ? 'selected' : ''; ?>>
                    <?php echo $manufacturer['manufacturer_title']; ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" name="status" id="status-filter" value="<?php echo $statusFilter; ?>">
                <button type="submit" class="btn btn-outline" id="filter-btn">
                  <i class="fa-solid fa-filter"></i> Filter
                </button>
              </form>
            </div>
          </div>
          <div class="card-content">
            <div class="tab-content active" id="all-products">
              <table class="data-table products-table">
                <thead>
                  <tr>
                    <th class="checkbox-column">
                      <input type="checkbox" id="select-all">
                    </th>
                    <th>Image</th>
                    <th>Product Title</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Sale Price</th>
                    <th>Stock Quantity</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="products-list">
                  <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                      <tr>
                        <td class="checkbox-column">
                          <input type="checkbox" class="product-checkbox" value="<?php echo $product['product_id']; ?>">
                        </td>
                        <td>
                          <img src="<?php echo $product['product_img1']; ?>" alt="<?php echo $product['product_title']; ?>" class="product-thumbnail">
                        </td>
                        <td>
                          <a href="#" class="product-title-link" data-id="<?php echo $product['product_id']; ?>"><?php echo $product['product_title']; ?></a>
                        </td>
                        <td><?php echo $product['p_cat_title']; ?></td>
                        <td><?php echo $product['manufacturer_title']; ?></td>
                        <td><?php echo number_format($product['product_price'], 0); ?> D.A</td>
                        <td><?php echo $product['product_psp_price'] > 0 ? number_format($product['product_psp_price'], 0) . ' D.A' : '-'; ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>
                          <span class="status-badge <?php echo $product['status']; ?>"><?php echo $product['status']; ?></span>
                        </td>
                        <td class="actions-column">
                          <div class="action-buttons">
                            <a href="edit-product.php?id=<?php echo $product['product_id']; ?>" class="btn-icon" title="Edit">
                              <i class="fa-solid fa-pencil"></i>
                            </a>
                            <button class="btn-icon view-product" data-id="<?php echo $product['product_id']; ?>" title="View Details">
                              <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="btn-icon delete-product" data-id="<?php echo $product['product_id']; ?>" title="Delete">
                              <i class="fa-solid fa-trash"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="10" class="text-center">No products found</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
              
              <!-- Pagination -->
              <?php if ($totalPages > 1): ?>
              <div class="pagination" id="pagination">
                <?php if ($page > 1): ?>
                  <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&brand=<?php echo $brandFilter; ?>&status=<?php echo $statusFilter; ?>" class="pagination-btn">Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&brand=<?php echo $brandFilter; ?>&status=<?php echo $statusFilter; ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                  <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <span class="pagination-ellipsis">...</span>
                  <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                  <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&brand=<?php echo $brandFilter; ?>&status=<?php echo $statusFilter; ?>" class="pagination-btn">Next</a>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Product Details Modal -->
  <div class="modal" id="product-details-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modal-product-title">Product Details</h2>
        <button class="close-modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="product-details">
          <!-- Product Gallery -->
          <div class="product-gallery">
            <div class="main-image">
              <img src="/placeholder.svg" alt="Product Image" id="modal-main-image">
            </div>
            <div class="thumbnail-container" id="modal-thumbnails">
              <!-- Thumbnails will be dynamically generated -->
            </div>
          </div>

          <!-- Product Info -->
          <div class="product-info">
            <div class="product-brand" id="modal-product-brand">Brand: </div>
            
            <div class="product-price-container">
              <div class="current-price" id="modal-current-price"></div>
              <div class="original-price-details" id="modal-original-price"></div>
              <div class="discount" id="modal-discount"></div>
            </div>
            
            <div class="product-description" id="modal-product-description">
            </div>
            
            <div class="product-features">
              <h3>Key Features</h3>
              <ul class="features-list" id="modal-features-list">
                <!-- Features will be dynamically generated -->
              </ul>
            </div>
            
            <div class="product-meta">
              <p><strong>Status:</strong> <span id="modal-status"></span></p>
              <p><strong>Stock Quantity:</strong> <span id="modal-stock"></span></p>
              <p><strong>Product URL:</strong> <span id="modal-product-url"></span></p>
              <p><strong>Keywords:</strong> <span id="modal-keywords"></span></p>
              <p><strong>Label:</strong> <span id="modal-label"></span></p>
              <p><strong>Added on:</strong> <span id="modal-date"></span></p>
            </div>
            
            <div class="modal-actions">
              <a href="#" class="btn btn-primary" id="edit-product-btn">
                <i class="fa-solid fa-pencil"></i> Edit Product
              </a>
              <button class="btn btn-danger" id="delete-product-btn">
                <i class="fa-solid fa-trash"></i> Delete Product
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
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
      
      // Tab switching
      const tabs = document.querySelectorAll('.tab');
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Remove active class from all tabs
          tabs.forEach(t => t.classList.remove('active'));
          // Add active class to clicked tab
          this.classList.add('active');
          
          // Set status filter based on tab
          let status = '';
          if (this.getAttribute('data-tab') === 'product-status') status = 'product';
          if (this.getAttribute('data-tab') === 'bundle-status') status = 'bundle';
          if (this.getAttribute('data-tab') === 'draft-status') status = 'draft';
          
          document.getElementById('status-filter').value = status;
          document.getElementById('filter-form').submit();
        });
      });
      
      // Modal functionality
      const modal = document.getElementById('product-details-modal');
      const closeModal = document.querySelector('.close-modal');
      
      closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
      });
      
      window.addEventListener('click', function(event) {
        if (event.target === modal) {
          modal.style.display = 'none';
        }
      });
      
      // Search functionality
      const searchInput = document.getElementById('product-search');
      let searchTimeout;
      
      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          const searchTerm = this.value.trim();
          if (searchTerm.length > 2 || searchTerm.length === 0) {
            window.location.href = 'view-products.php?search=' + encodeURIComponent(searchTerm) + 
                                   '&category=' + document.getElementById('category-filter').value +
                                   '&brand=' + document.getElementById('brand-filter').value +
                                   '&status=' + document.getElementById('status-filter').value;
          }
        }, 500);
      });
      
      // View product details
      const viewButtons = document.querySelectorAll('.view-product');
      viewButtons.forEach(button => {
        button.addEventListener('click', function() {
          const productId = this.getAttribute('data-id');
          viewProductDetails(productId);
        });
      });
      
      // Product title links
      const productLinks = document.querySelectorAll('.product-title-link');
      productLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const productId = this.getAttribute('data-id');
          viewProductDetails(productId);
        });
      });
      
      // Delete product
      const deleteButtons = document.querySelectorAll('.delete-product');
      deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
          const productId = this.getAttribute('data-id');
          if (confirm('Are you sure you want to delete this product?')) {
            deleteProduct(productId);
          }
        });
      });
      
      // Function to view product details
      function viewProductDetails(productId) {
        // Reset modal content first
        const modalBody = document.querySelector('.modal-body');
        modalBody.innerHTML = `
          <div class="product-details">
            <!-- Product Gallery -->
            <div class="product-gallery">
              <div class="main-image">
                <img src="/placeholder.svg?height=250&width=250" alt="Loading..." id="modal-main-image">
              </div>
              <div class="thumbnail-container" id="modal-thumbnails">
                <!-- Thumbnails will be dynamically generated -->
              </div>
            </div>

            <!-- Product Info -->
            <div class="product-info">
              <div class="product-brand" id="modal-product-brand">Loading...</div>
              
              <div class="product-price-container">
                <div class="current-price" id="modal-current-price">Loading...</div>
                <div class="original-price-details" id="modal-original-price" style="display: none;"></div>
                <div class="discount" id="modal-discount" style="display: none;"></div>
              </div>
              
              <div class="product-description" id="modal-product-description">
                <p>Loading product description...</p>
              </div>
              
              <div class="product-features">
                <h3>Key Features</h3>
                <ul class="features-list" id="modal-features-list">
                  <li>Loading features...</li>
                </ul>
              </div>
              
              <div class="product-meta">
                <p><strong>Status:</strong> <span id="modal-status">-</span></p>
                <p><strong>Stock Quantity:</strong> <span id="modal-stock">-</span></p>
                <p><strong>Product URL:</strong> <span id="modal-product-url">-</span></p>
                <p><strong>Keywords:</strong> <span id="modal-keywords">-</span></p>
                <p><strong>Label:</strong> <span id="modal-label">-</span></p>
                <p><strong>Added on:</strong> <span id="modal-date">-</span></p>
              </div>
              
              <div class="modal-actions">
                <a href="#" class="btn btn-primary" id="edit-product-btn">
                  <i class="fa-solid fa-pencil"></i> Edit Product
                </a>
                <button class="btn btn-danger" id="delete-product-btn">
                  <i class="fa-solid fa-trash"></i> Delete Product
                </button>
              </div>
            </div>
          </div>
        `;
        
        // Show modal
        modal.style.display = 'block';
        
        // Fetch product details
        fetch(`view-products.php?ajax=product-details&id=${productId}`)
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then(product => {
            if (product && !product.error) {
              // Update modal content with actual data
              document.getElementById('modal-product-title').textContent = product.product_title || 'Unknown Product';
              document.getElementById('modal-main-image').src = product.product_img1 || '/placeholder.svg?height=250&width=250';
              document.getElementById('modal-product-brand').textContent = `Brand: ${product.manufacturer_title || 'Unknown'}`;
              
              // Format prices
              const currentPrice = (product.product_psp_price && product.product_psp_price > 0) ? product.product_psp_price : product.product_price;
              document.getElementById('modal-current-price').textContent = (currentPrice || 0) + ' D.A';
              
              if (product.product_psp_price && product.product_psp_price > 0) {
                document.getElementById('modal-original-price').textContent = (product.product_price || 0) + ' D.A';
                const discountPercentage = Math.round((1 - product.product_psp_price / product.product_price) * 100);
                document.getElementById('modal-discount').textContent = `-${discountPercentage}%`;
                document.getElementById('modal-discount').style.display = 'block';
                document.getElementById('modal-original-price').style.display = 'block';
              } else {
                document.getElementById('modal-original-price').style.display = 'none';
                document.getElementById('modal-discount').style.display = 'none';
              }
              
              document.getElementById('modal-product-description').innerHTML = `<p>${product.product_desc || 'No description available'}</p>`;
              
              // Features
              const featuresList = document.getElementById('modal-features-list');
              featuresList.innerHTML = '';
              if (product.product_features) {
                const features = product.product_features.split('\n');
                features.forEach(feature => {
                  if (feature.trim()) {
                    featuresList.innerHTML += `<li><i class="fas fa-check"></i> ${feature}</li>`;
                  }
                });
              } else {
                featuresList.innerHTML = '<li>No features listed</li>';
              }
              
              // Thumbnails
              const thumbnailContainer = document.getElementById('modal-thumbnails');
              thumbnailContainer.innerHTML = '';
              if (product.product_img1) {
                thumbnailContainer.innerHTML += `
                  <div class="thumbnail active" onclick="changeModalImage(this, '${product.product_img1}')">
                    <img src="${product.product_img1}" alt="${product.product_title} - View 1">
                  </div>
                `;
              }
              if (product.product_img2) {
                thumbnailContainer.innerHTML += `
                  <div class="thumbnail" onclick="changeModalImage(this, '${product.product_img2}')">
                    <img src="${product.product_img2}" alt="${product.product_title} - View 2">
                  </div>
                `;
              }
              if (product.product_img3) {
                thumbnailContainer.innerHTML += `
                  <div class="thumbnail" onclick="changeModalImage(this, '${product.product_img3}')">
                    <img src="${product.product_img3}" alt="${product.product_title} - View 3">
                  </div>
                `;
              }
              
              // Meta information
              document.getElementById('modal-status').textContent = product.status || 'Unknown';
              document.getElementById('modal-stock').textContent = product.stock || '0';
              document.getElementById('modal-product-url').textContent = product.product_url || '-';
              document.getElementById('modal-keywords').textContent = product.product_keywords || '-';
              document.getElementById('modal-label').textContent = product.product_label || 'None';
              document.getElementById('modal-date').textContent = product.date ? new Date(product.date).toLocaleDateString() : '-';
              
              // Action buttons
              document.getElementById('edit-product-btn').href = `edit-product.php?id=${product.product_id}`;
              
              // Remove any existing event listeners and add new one
              const deleteBtn = document.getElementById('delete-product-btn');
              const newDeleteBtn = deleteBtn.cloneNode(true);
              deleteBtn.parentNode.replaceChild(newDeleteBtn, deleteBtn);
              
              newDeleteBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this product?')) {
                  deleteProduct(product.product_id);
                  modal.style.display = 'none';
                }
              });
            } else {
              modalBody.innerHTML = '<p class="text-center">Product not found or error loading details</p>';
            }
          })
          .catch(error => {
            console.error('Error loading product details:', error);
            modalBody.innerHTML = '<p class="text-center">Error loading product details. Please check the console for more information.</p>';
          });
      }
      
      // Function to delete product
      function deleteProduct(productId) {
        fetch(`view-products.php?ajax=delete-product&id=${productId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert('Product deleted successfully');
              // Reload the page to reflect changes
              window.location.reload();
            } else {
              alert('Error deleting product: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error deleting product:', error);
            alert('Error deleting product. Please try again.');
          });
      }
      
      // Select all checkbox
      const selectAll = document.getElementById('select-all');
      if (selectAll) {
        selectAll.addEventListener('change', function() {
          const checkboxes = document.querySelectorAll('.product-checkbox');
          checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
          });
        });
      }
    });
    
    // Function to change modal image
    function changeModalImage(thumbnail, newSrc) {
      // Update main image
      document.getElementById('modal-main-image').src = newSrc;
      
      // Update active thumbnail
      const thumbnails = document.querySelectorAll('#modal-thumbnails .thumbnail');
      thumbnails.forEach(thumb => {
        thumb.classList.remove('active');
      });
      thumbnail.classList.add('active');
    }

  </script>
</body>
</html>
