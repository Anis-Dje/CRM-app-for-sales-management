<?php
  session_start(); 
  if(!isset($_SESSION['admin_email'])) {
    header("Location: auth.php");
    exit();
  }
  // Database connection
  $con = mysqli_connect("localhost:3307","root","","ecom_store");
  
  if(mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
  }
  
  // Check if customer ID is provided
  if(!isset($_GET['id'])) {
    header("Location: view-customers.php");
    exit();
  }
  
  $customer_id = mysqli_real_escape_string($con, $_GET['id']);
  
  // Get customer details
  $get_customer = "SELECT * FROM customers WHERE customer_id = '$customer_id'";
  $run_customer = mysqli_query($con, $get_customer);
  
  if(mysqli_num_rows($run_customer) == 0) {
    header("Location: view-customers.php");
    exit();
  }
  
  $row_customer = mysqli_fetch_array($run_customer);
  $customer_name = $row_customer['customer_name'];
  $customer_email = $row_customer['email'];
  $customer_country = $row_customer['customer_country'];
  $customer_city = $row_customer['customer_city'];
  $customer_address = $row_customer['customer_address'];
  $customer_image = $row_customer['customer_image'];
  $customer_points = $row_customer['customer_points'];
  
  // Get customer orders with order items
  $get_orders = "SELECT co.*, 
               GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.product_title) SEPARATOR ', ') as order_items,
               SUM(oi.quantity) as total_quantity
               FROM customer_orders co
               LEFT JOIN order_items oi ON co.order_id = oi.order_id
               LEFT JOIN products p ON oi.product_id = p.product_id
               WHERE co.customer_id = '$customer_id'
               GROUP BY co.order_id
               ORDER BY co.order_date DESC";
  $run_orders = mysqli_query($con, $get_orders);
  
  // Form submission handling
  if(isset($_POST['update'])) {
    $customer_name = mysqli_real_escape_string($con, $_POST['customer_name']);
    $customer_email = mysqli_real_escape_string($con, $_POST['customer_email']);
    $customer_country = mysqli_real_escape_string($con, $_POST['customer_country']);
    $customer_city = mysqli_real_escape_string($con, $_POST['customer_city']);
    $customer_address = mysqli_real_escape_string($con, $_POST['customer_address']);
    $customer_points = mysqli_real_escape_string($con, $_POST['customer_points']);
    
    // Check if password is being updated
    if(!empty($_POST['customer_pass'])) {
      $customer_pass = mysqli_real_escape_string($con, $_POST['customer_pass']);
      $password_update = ", password='$customer_pass'";
    } else {
      $password_update = "";
    }
    
    // Check if image is being updated
    $image_update = "";
    if ($_FILES['customer_image']['size'] > 0 && $_FILES['customer_image']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['customer_image']['type'], $allowed_types)) {
            $error_message = "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
        } else {
            $file_ext = pathinfo($_FILES['customer_image']['name'], PATHINFO_EXTENSION);
            $customer_image_new = "customer_" . $customer_id . "_" . time() . "." . $file_ext; // Unique file name
            $temp_name = $_FILES['customer_image']['tmp_name'];
            $destination = __DIR__ . "/customer_images/" . $customer_image_new;
            
            // Verify directory exists
            if (!is_dir(__DIR__ . "/customer_images")) {
                $error_message = "customer_images directory does not exist.";
            } elseif (move_uploaded_file($temp_name, $destination)) {
                // Delete old image if it exists and is different
                if (!empty($customer_image) && file_exists(__DIR__ . "/customer_images/" . $customer_image)) {
                    unlink(__DIR__ . "/customer_images/" . $customer_image);
                }
                $image_update = ", customer_image='$customer_image_new'";
                $customer_image = $customer_image_new; // Update for display
            } else {
                $error_message = "Failed to move uploaded file. Check folder permissions.";
            }
        }
    }
    
    // Update customer in database if no errors
    if (!isset($error_message)) {
        $update_customer = "UPDATE customers SET 
                            customer_name='$customer_name', 
                            email='$customer_email', 
                            customer_country='$customer_country', 
                            customer_city='$customer_city', 
                            customer_address='$customer_address', 
                            customer_points='$customer_points'
                            $password_update
                            $image_update
                            WHERE customer_id='$customer_id'";
        
        $run_update = mysqli_query($con, $update_customer);
        
        if($run_update) {
            $success_message = "Customer updated successfully!";
            
            // Refresh customer data
            $get_customer = "SELECT * FROM customers WHERE customer_id = '$customer_id'";
            $run_customer = mysqli_query($con, $get_customer);
            $row_customer = mysqli_fetch_array($run_customer);
            
            $customer_name = $row_customer['customer_name'];
            $customer_email = $row_customer['email'];
            $customer_country = $row_customer['customer_country'];
            $customer_city = $row_customer['customer_city'];
            $customer_address = $row_customer['customer_address'];
            $customer_image = $row_customer['customer_image'];
            $customer_points = $row_customer['customer_points'];
        } else {
            $error_message = "Failed to update customer: " . mysqli_error($con);
        }
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Customer | Admin Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <li class = active><a href="view-customers.php">View Customers</a></li>
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
          <h1>Edit Customer</h1>
        </div>
        <div class="header-right">
          <div class="search-container">
            <input type="search" placeholder="Search...">
            <i class="fa-solid fa-search"></i>
          </div>
        </div>
      </header>

      <div class="content-wrapper">
        <?php if(isset($error_message)): ?>
          <div class="alert" style="background-color: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_message; ?>
          </div>
        <?php endif; ?>
        
        <?php if(isset($success_message)): ?>
          <div class="alert" style="background-color: rgba(74, 222, 128, 0.2); color: var(--accent-color); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-check"></i> <?php echo $success_message; ?>
          </div>
        <?php endif; ?>
        
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Customer Details</h3>
            <div style="display: flex; gap: 10px;">
              <button onclick="deleteCustomer(<?php echo $customer_id; ?>)" class="btn btn-danger">
                <i class="fa-solid fa-trash"></i> Delete Customer
              </button>
              <a href="view-customers.php" class="btn btn-outline">
                <i class="fa-solid fa-arrow-left"></i> Back to Customers
              </a>
            </div>
          </div>
          <div class="card-content">
            <div style="display: flex; gap: 30px; margin-bottom: 30px;">
              <div style="flex: 0 0 200px;">
                <div style="width: 200px; height: 200px; border-radius: 10px; overflow: hidden; margin-bottom: 15px;">
                  <img src="customer_images/<?php echo $customer_image; ?>" alt="Customer" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="text-align: center;">
                  <p style="font-size: 18px; font-weight: bold; margin-bottom: 5px;"><?php echo $customer_name; ?></p>
                  <p style="color: var(--text-secondary); margin-bottom: 15px;"><?php echo $customer_email; ?></p>
                  <p style="background-color: rgba(74, 222, 128, 0.1); color: var(--accent-color); padding: 5px 10px; border-radius: 5px; display: inline-block;">
                    <i class="fa-solid fa-star"></i> <?php echo number_format($customer_points); ?> points
                  </p>
                </div>
              </div>
              
              <div style="flex: 1;">
                <form action="" method="post" enctype="multipart/form-data">
                  <div class="form-row">
                    <div class="form-group">
                      <label>Customer Name</label>
                      <input type="text" name="customer_name" value="<?php echo $customer_name; ?>" required>
                    </div>
                    <div class="form-group">
                      <label>Customer Email</label>
                      <input type="email" name="customer_email" value="<?php echo $customer_email; ?>" required>
                    </div>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group">
                      <label>Customer Country</label>
                      <input type="text" name="customer_country" value="<?php echo $customer_country; ?>" required>
                    </div>
                    <div class="form-group">
                      <label>Customer City</label>
                      <input type="text" name="customer_city" value="<?php echo $customer_city; ?>" required>
                    </div>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group">
                      <label>Customer Address</label>
                      <input type="text" name="customer_address" value="<?php echo $customer_address; ?>" required>
                    </div>
                    <div class="form-group">
                      <label>Customer Points</label>
                      <input type="number" name="customer_points" value="<?php echo $customer_points; ?>" required>
                    </div>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group">
                      <label>New Password (leave blank to keep current)</label>
                      <input type="password" name="customer_pass">
                    </div>
                    <div class="form-group">
                      <label>New Customer Image (optional)</label>
                      <input type="file" name="customer_image" accept="image/jpeg,image/png,image/gif">
                    </div>
                  </div>
                  
                  <div class="form-group">
                    <button type="submit" name="update" class="btn-primary">
                      <i class="fa-solid fa-save"></i> Update Customer
                    </button>
                  </div>
                </form>
              </div>
            </div>
            
            <div class="card" style="margin-top: 30px;">
              <div class="card-header">
                <h3 class="card-title">Customer Orders</h3>
              </div>
              <div class="card-content">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Order ID</th>
                      <th>Invoice No</th>
                      <th>Amount</th>
                      <th>Items</th>
                      <th>Total Quantity</th>
                      <th>Order Date</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      if(mysqli_num_rows($run_orders) > 0) {
                        while($row_order = mysqli_fetch_array($run_orders)) {
                          $order_id = $row_order['order_id'];
                          $invoice_no = $row_order['invoice_no'];
                          $due_amount = $row_order['due_amount'];
                          $order_items = $row_order['order_items'] ?: 'No items';
                          $total_quantity = $row_order['total_quantity'] ?: 0;
                          $order_date = $row_order['order_date'];
                          $order_status = $row_order['order_status'];
                    ?>
                    <tr>
                      <td><?php echo $order_id; ?></td>
                      <td><?php echo $invoice_no; ?></td>
                      <td><?php echo number_format($due_amount, 0); ?> D.A</td>
                      <td><?php echo $order_items; ?></td>
                      <td><?php echo $total_quantity; ?></td>
                      <td><?php echo date('Y-m-d', strtotime($order_date)); ?></td>
                      <td>
                        <?php if($order_status == 'pending'): ?>
                          <span class="status-badge in-process">Pending</span>
                        <?php elseif($order_status == 'completed'): ?>
                          <span class="status-badge done">Complete</span>
                        <?php else: ?>
                          <span class="status-badge"><?php echo ucfirst($order_status); ?></span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <a href="view-order-details.php?id=<?php echo $order_id; ?>" class="btn-icon" title="View Details">
                          <i class="fa-solid fa-eye"></i>
                        </a>
                      </td>
                    </tr>
                    <?php
                        }
                      } else {
                    ?>
                    <tr>
                      <td colspan="8" style="text-align: center; padding: 20px;">No orders found for this customer</td>
                    </tr>
                    <?php
                      }
                    ?>
                  </tbody>
                </table>
              </div>
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
        // Check if this menu item links to current page or if item is already marked active
        if (itemPage === currentPage || item.parentElement.classList.contains('active')) {
          // Find parent submenu and open it
          const parentSubmenu = item.closest('.has-submenu');
          if (parentSubmenu) {
            parentSubmenu.classList.add('open');
          }
        }
      });
    });

    function deleteCustomer(customerId) {
      if (confirm('Are you sure you want to delete this customer? This action cannot be undone and will also delete all associated orders and cart items.')) {
        // Create a form to submit the delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete-customer.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'customer_id';
        input.value = customerId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
      }
    }
  </script>
</body>
</html>
