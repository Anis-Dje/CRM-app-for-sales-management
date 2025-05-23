<?php
  // Database connection
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
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
  }
  
  // Delete customer if requested
  if(isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Start transaction for safe deletion
    mysqli_begin_transaction($conn);
    
    try {
        // Get customer image before deletion for cleanup
        $stmt_img = $conn->prepare("SELECT customer_image FROM customers WHERE customer_id = ?");
        $stmt_img->bind_param("i", $delete_id);
        $stmt_img->execute();
        $result_img = $stmt_img->get_result();
        $customer_data = $result_img->fetch_assoc();
        $customer_image = $customer_data['customer_image'] ?? null;
        $stmt_img->close();
        
        // Step 1: Get all order IDs for this customer
        $order_ids_query = $conn->prepare("SELECT order_id FROM customer_orders WHERE customer_id = ?");
        $order_ids_query->bind_param("i", $delete_id);
        $order_ids_query->execute();
        $order_ids_result = $order_ids_query->get_result();
        $order_ids = [];
        while ($row = $order_ids_result->fetch_assoc()) {
            $order_ids[] = $row['order_id'];
        }
        $order_ids_query->close();
        
        // Step 2: Delete payments for all customer orders
        if (!empty($order_ids)) {
            $order_ids_placeholder = str_repeat('?,', count($order_ids) - 1) . '?';
            $stmt_payments = $conn->prepare("DELETE FROM payments WHERE order_id IN ($order_ids_placeholder)");
            $stmt_payments->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
            $stmt_payments->execute();
            $stmt_payments->close();
            
            // Step 3: Delete order items for all customer orders
            $stmt_order_items = $conn->prepare("DELETE FROM order_items WHERE order_id IN ($order_ids_placeholder)");
            $stmt_order_items->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
            $stmt_order_items->execute();
            $stmt_order_items->close();
        }
        
        // Step 4: Delete customer orders
        $stmt_orders = $conn->prepare("DELETE FROM customer_orders WHERE customer_id = ?");
        $stmt_orders->bind_param("i", $delete_id);
        $stmt_orders->execute();
        $stmt_orders->close();
        
        // Step 5: Delete from cart
        $stmt_cart = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
        $stmt_cart->bind_param("i", $delete_id);
        $stmt_cart->execute();
        $stmt_cart->close();
        
        // Step 6: Delete from fidelity_redemptions if it exists
        $fidelity_table_exists = $conn->query("SHOW TABLES LIKE 'fidelity_redemptions'")->num_rows > 0;
        if ($fidelity_table_exists) {
            $stmt_fidelity = $conn->prepare("DELETE FROM fidelity_redemptions WHERE customer_id = ?");
            $stmt_fidelity->bind_param("i", $delete_id);
            $stmt_fidelity->execute();
            $stmt_fidelity->close();
        }
        
        // Step 7: Finally delete the customer
        $stmt_customer = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt_customer->bind_param("i", $delete_id);
        $stmt_customer->execute();
        
        if($stmt_customer->affected_rows > 0) {
            // Delete customer image file if it exists and is not default
            if($customer_image && $customer_image !== 'default-profile.png' && file_exists("customer_images/" . $customer_image)) {
                unlink("customer_images/" . $customer_image);
            }
            
            mysqli_commit($conn);
            $success_message = "Customer and all related data have been deleted successfully!";
        } else {
            mysqli_rollback($conn);
            $error_message = "Customer not found or already deleted.";
        }
        $stmt_customer->close();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Failed to delete customer: " . $e->getMessage();
    }
  }
  
  // Pagination
  $per_page = 10;
  
  if(isset($_GET['page'])) {
    $page = intval($_GET['page']);
  } else {
    $page = 1;
  }
  
  $start_from = ($page-1) * $per_page;
  
  // Get customers with pagination
  $get_customers = "SELECT * FROM customers ORDER BY customer_id DESC LIMIT $start_from, $per_page";
  $run_customers = mysqli_query($conn, $get_customers);
  
  // Get total number of customers
  $count_query = "SELECT COUNT(*) as total FROM customers";
  $count_result = mysqli_query($conn, $count_query);
  $count_row = mysqli_fetch_assoc($count_result);
  $total_customers = $count_row['total'];
  $total_pages = ceil($total_customers / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Customers | Admin Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            
            <li class="has-submenu open">
              <a href="#" class="submenu-toggle">
                <i class="fa-solid fa-user"></i>
                <span>Users</span>
                <i class="fa-solid fa-chevron-down submenu-icon"></i>
              </a>
              <ul class="submenu">
                <li><a href="insert-admin.php">Insert Admin</a></li>
                <li><a href="view-admins.php">View Admins</a></li>
                <li class="active"><a href="view-customers.php">View Customers</a></li>
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
          <h1>View Customers</h1>
        </div>
        <div class="header-right">
          <div class="search-container">
            <input type="search" placeholder="Search customers..." id="customerSearch" onkeyup="searchCustomers()">
            <i class="fa-solid fa-search"></i>
          </div>
        </div>
      </header>

      <div class="content-wrapper">
        <?php if(isset($error_message)): ?>
          <div class="alert alert-error" style="background-color: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444;">
            <i class="fa-solid fa-circle-exclamation"></i> 
            <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>
        
        <?php if(isset($success_message)): ?>
          <div class="alert alert-success" style="background-color: rgba(74, 222, 128, 0.2); color: var(--accent-color); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--accent-color);">
            <i class="fa-solid fa-circle-check"></i> 
            <strong>Success:</strong> <?php echo htmlspecialchars($success_message); ?>
          </div>
        <?php endif; ?>
        
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Customer List (<?php echo number_format($total_customers); ?> total)</h3>
            <div class="table-actions">
              <button class="btn btn-outline" onclick="exportCustomers()">
                <i class="fa-solid fa-download"></i>
                Export
              </button>
              <button class="btn btn-outline" onclick="toggleFilters()">
                <i class="fa-solid fa-filter"></i>
                Filter
              </button>
            </div>
          </div>
          <div class="card-content">
            <table class="data-table" id="customersTable">
              <thead>
                <tr>
                  <th class="checkbox-column">
                    <input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes()">
                  </th>
                  <th>ID</th>
                  <th>Image</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Country</th>
                  <th>City</th>
                  <th>Points</th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  if(mysqli_num_rows($run_customers) > 0) {
                    while($row_customer = mysqli_fetch_array($run_customers)) {
                      $customer_id = $row_customer['customer_id'];
                      $customer_name = htmlspecialchars($row_customer['customer_name']);
                      $customer_email = htmlspecialchars($row_customer['email']);
                      $customer_country = htmlspecialchars($row_customer['customer_country']);
                      $customer_city = htmlspecialchars($row_customer['customer_city']);
                      $customer_image = htmlspecialchars($row_customer['customer_image']);
                      $customer_points = $row_customer['customer_points'];
                ?>
                <tr>
                  <td class="checkbox-column">
                    <input type="checkbox" class="customer-checkbox" value="<?php echo $customer_id; ?>">
                  </td>
                  <td><?php echo $customer_id; ?></td>
                  <td>
                    <?php if($customer_image && file_exists("customer_images/" . $customer_image)): ?>
                      <img src="customer_images/<?php echo $customer_image; ?>" alt="<?php echo $customer_name; ?>" width="40" height="40" style="border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                      <img src="customer_images/default-profile.png" alt="Default Profile" width="40" height="40" style="border-radius: 50%; object-fit: cover;">
                    <?php endif; ?>
                  </td>
                  <td><?php echo $customer_name; ?></td>
                  <td><?php echo $customer_email; ?></td>
                  <td><?php echo $customer_country; ?></td>
                  <td><?php echo $customer_city; ?></td>
                  <td><?php echo number_format($customer_points); ?></td>
                  <td class="actions-column">
                    <div class="action-buttons">
                      <a href="edit-customers.php?id=<?php echo $customer_id; ?>" class="btn-icon" title="Edit">
                        <i class="fa-solid fa-edit"></i>
                      </a>
                      <a href="javascript:void(0)" class="btn-icon btn-danger" title="Delete" onclick="confirmDelete(<?php echo $customer_id; ?>, '<?php echo addslashes($customer_name); ?>')">
                        <i class="fa-solid fa-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php
                    }
                  } else {
                ?>
                <tr>
                  <td colspan="9" style="text-align: center; padding: 20px;">No customers found</td>
                </tr>
                <?php
                  }
                ?>
              </tbody>
            </table>
            
            <div class="pagination" style="margin-top: 20px; display: flex; justify-content: center;">
              <?php if($page > 1): ?>
                <a href="view-customers.php?page=<?php echo ($page - 1); ?>" class="btn btn-outline">Previous</a>
              <?php else: ?>
                <button class="btn btn-outline" disabled>Previous</button>
              <?php endif; ?>
              
              <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <?php if($i == $page): ?>
                  <a href="view-customers.php?page=<?php echo $i; ?>" class="btn btn-outline" style="margin: 0 5px; background-color: rgba(74, 222, 128, 0.1); color: var(--accent-color);"><?php echo $i; ?></a>
                <?php else: ?>
                  <a href="view-customers.php?page=<?php echo $i; ?>" class="btn btn-outline" style="margin: 0 5px;"><?php echo $i; ?></a>
                <?php endif; ?>
              <?php endfor; ?>
              
              <?php if($page < $total_pages): ?>
                <a href="view-customers.php?page=<?php echo ($page + 1); ?>" class="btn btn-outline">Next</a>
              <?php else: ?>
                <button class="btn btn-outline" disabled>Next</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
        if (itemPage === currentPage || item.parentElement.classList.contains('active')) {
          const parentSubmenu = item.closest('.has-submenu');
          if (parentSubmenu) {
            parentSubmenu.classList.add('open');
          }
        }
      });
    });
    
    // Search customers function
    function searchCustomers() {
      const input = document.getElementById('customerSearch');
      const filter = input.value.toUpperCase();
      const table = document.getElementById('customersTable');
      const tr = table.getElementsByTagName('tr');
      
      for (let i = 1; i < tr.length; i++) {
        let found = false;
        const td = tr[i].getElementsByTagName('td');
        
        for (let j = 1; j < td.length - 1; j++) {
          const txtValue = td[j].textContent || td[j].innerText;
          if (txtValue.toUpperCase().indexOf(filter) > -1) {
            found = true;
            break;
          }
        }
        
        if (found) {
          tr[i].style.display = '';
        } else {
          tr[i].style.display = 'none';
        }
      }
    }
    
    // Toggle all checkboxes
    function toggleAllCheckboxes() {
      const selectAll = document.getElementById('selectAll');
      const checkboxes = document.getElementsByClassName('customer-checkbox');
      
      for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = selectAll.checked;
      }
    }
    
    // Confirm delete with better UX
    function confirmDelete(customerId, customerName) {
      const confirmMessage = `⚠️ DELETE CUSTOMER CONFIRMATION ⚠️\n\n` +
        `Customer: "${customerName}" (ID: ${customerId})\n\n` +
        `This will permanently delete:\n` +
        `• Customer account and profile\n` +
        `• All order history\n` +
        `• All payment records\n` +
        `• Cart items\n` +
        `• Fidelity points and redemptions\n` +
        `• Profile image\n\n` +
        `⚠️ THIS ACTION CANNOT BE UNDONE! ⚠️\n\n` +
        `Are you absolutely sure you want to proceed?`;
        
      if (confirm(confirmMessage)) {
        // Show loading state
        const deleteBtn = event.target.closest('.btn-icon');
        const originalContent = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        deleteBtn.style.pointerEvents = 'none';
        
        // Redirect to delete
        window.location.href = `view-customers.php?delete=${customerId}`;
      }
    }
    
    // Export customers function (placeholder)
    function exportCustomers() {
      alert('Export functionality would be implemented here');
    }
    
    // Toggle filters function (placeholder)
    function toggleFilters() {
      alert('Filter functionality would be implemented here');
    }
    
    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.style.opacity = '0';
          alert.style.transform = 'translateY(-10px)';
          setTimeout(() => {
            alert.remove();
          }, 300);
        }, 5000);
      });
    });
  </script>

  <style>
    .alert {
      transition: all 0.3s ease;
    }
    
    .btn-danger {
      color: #ef4444 !important;
    }
    
    .btn-danger:hover {
      background-color: rgba(239, 68, 68, 0.1) !important;
    }
    
    .action-buttons {
      display: flex;
      gap: 8px;
    }
    
    .btn-icon {
      padding: 8px;
      border-radius: 4px;
      transition: all 0.2s ease;
    }
    
    .btn-icon:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }
  </style>
</body>
</html>
