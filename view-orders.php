<?php
session_start();

// Redirect to login if not authenticated as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: auth.php");
    exit;
}

// Database connection
$conn = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['order_status']);
    
    // Update status in customer_orders
    $update_query = "UPDATE customer_orders SET order_status = '$new_status' WHERE order_id = $order_id";
    
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, $update_query);
        mysqli_commit($conn);
        $_SESSION['admin_message'] = "Order status updated successfully.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['admin_error'] = "Failed to update order status: " . $e->getMessage();
    }
    header("Location: view-orders.php");
    exit();
}

// Fetch all orders
$orders_query = "
    SELECT co.*, c.customer_name, c.email, d.code as discount_code, d.discount_type, d.discount_value
    FROM customer_orders co
    JOIN customers c ON co.customer_id = c.customer_id
    LEFT JOIN discounts d ON co.discount_id = d.id
    ORDER BY co.order_date DESC";
$orders_result = mysqli_query($conn, $orders_query);

// Fetch order items for each order from cart table
$orders = [];
if ($orders_result && mysqli_num_rows($orders_result) > 0) {
    while ($order = mysqli_fetch_assoc($orders_result)) {
        $order_id = $order['order_id'];
        $customer_id = $order['customer_id'];
        $items_query = "
            SELECT c.*, p.product_title, p.product_price 
            FROM cart c 
            JOIN products p ON c.p_id = p.product_id
            WHERE c.customer_id = ? AND c.status = 'ORDERED' AND c.added_date <= (SELECT MAX(order_date) FROM customer_orders WHERE order_id = ?)";
        $stmt = mysqli_prepare($conn, $items_query);
        mysqli_stmt_bind_param($stmt, "ii", $customer_id, $order_id);
        mysqli_stmt_execute($stmt);
        $items_result = mysqli_stmt_get_result($stmt);
        
        $items = [];
        $total_items = 0;
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
            $total_items += $item['qty'];
        }
        $order['items'] = $items;
        $order['total_items'] = $total_items;
        $order['has_discount'] = (!empty($order['discount_id']) || !empty($order['discount_code']));
        $orders[] = $order;
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechMarket - View Orders</title>
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
            
            <li class="active">
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
          <h1>View Orders</h1>
        </div>
        <div class="header-right">
          <div class="search-container">
            <input type="search" placeholder="Search...">
            <i class="fa-solid fa-search"></i>
          </div>
        </div>
      </header>

      <div class="content-wrapper">
        <!-- Messages -->
        <?php if (isset($_SESSION['admin_message'])): ?>
          <div class="card" style="background-color: rgba(74, 222, 128, 0.2); margin-bottom: 20px;">
            <div class="card-content">
              <p style="color: #4ade80;"><?php echo htmlspecialchars($_SESSION['admin_message']); ?></p>
            </div>
          </div>
          <?php unset($_SESSION['admin_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['admin_error'])): ?>
          <div class="card" style="background-color: rgba(239, 68, 68, 0.2); margin-bottom: 20px;">
            <div class="card-content">
              <p style="color: #ef4444;"><?php echo htmlspecialchars($_SESSION['admin_error']); ?></p>
            </div>
          </div>
          <?php unset($_SESSION['admin_error']); ?>
        <?php endif; ?>

        <!-- Orders Table -->
        <div class="table-section">
          <div class="card">
            <div class="card-header table-header">
              <div class="tabs">
                <button class="tab active" data-tab="orders">Orders</button>
              </div>
              <div class="table-actions">
                <button class="btn btn-outline">
                  <i class="fa-solid fa-columns"></i>
                  Customize Columns
                </button>
              </div>
            </div>
            <div class="card-content">
              <div class="tab-content active" id="orders">
                <?php if (empty($orders)): ?>
                  <div class="empty-state">No orders found.</div>
                <?php else: ?>
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th class="checkbox-column">
                          <input type="checkbox">
                        </th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total Items</th>
                        <th>Total Amount</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th class="actions-column"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($orders as $order): ?>
                        <tr>
                          <td class="checkbox-column">
                            <input type="checkbox">
                          </td>
                          <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                          <td>
                            <?php echo htmlspecialchars($order['customer_name']); ?><br>
                            <span style="color: var(--text-secondary); font-size: 12px;"><?php echo htmlspecialchars($order['email']); ?></span>
                          </td>
                          <td><?php echo htmlspecialchars($order['total_items']); ?></td>
                          <td><?php echo number_format($order['due_amount'], 0) . ' D.A'; ?></td>
                          <td><?php echo date('Y-m-d H:i:s', strtotime($order['order_date'])); ?></td>
                          <td>
                            <form method="POST" style="display: inline;">
                              <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                              <select name="order_status" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 4px; padding: 5px; color: var(--text-primary);">
                                <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $order['order_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                              </select>
                              <button type="submit" name="update_status" class="btn btn-outline" style="margin-left: 10px;">Update</button>
                            </form>
                          </td>
                          <td class="actions-column">
                            <button class="btn-icon toggle-details" data-order-id="<?php echo $order['order_id']; ?>">
                              <i class="fa-solid fa-ellipsis-h"></i>
                            </button>
                          </td>
                        </tr>
                        <!-- Order Details Row -->
                        <tr class="order-details" id="details-<?php echo $order['order_id']; ?>" style="display: none;">
                          <td colspan="8">
                            <div style="padding: 15px; background-color: var(--bg-primary); border-radius: 5px;">
                              <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 10px;">Order Items</h3>
                              <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                  <tr>
                                    <th style="padding: 8px; text-align: left; color: var(--text-secondary); font-weight: 500; font-size: 14px;">Product</th>
                                    <th style="padding: 8px; text-align: left; color: var(--text-secondary); font-weight: 500; font-size: 14px;">Quantity</th>
                                    <th style="padding: 8px; text-align: left; color: var(--text-secondary); font-weight: 500; font-size: 14px;">Price</th>
                                    <th style="padding: 8px; text-align: left; color: var(--text-secondary); font-weight: 500; font-size: 14px;">Subtotal</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                      <td style="padding: 8px;"><?php echo htmlspecialchars($item['product_title']); ?></td>
                                      <td style="padding: 8px;"><?php echo htmlspecialchars($item['qty']); ?></td>
                                      <td style="padding: 8px;"><?php echo number_format($item['product_price'], 0) . ' D.A'; ?></td>
                                      <td style="padding: 8px;"><?php echo number_format($item['product_price'] * $item['qty'], 0) . ' D.A'; ?></td>
                                    </tr>
                                  <?php endforeach; ?>
                                </tbody>
                              </table>
                              <?php if ($order['has_discount'] && ($order['discount_id'] || !empty($order['discount_code']))): ?>
                                <div style="margin-top: 15px; padding: 10px; background-color: rgba(0, 209, 178, 0.1); border-radius: 5px;">
                                  <h4 style="font-size: 14px; margin-bottom: 5px;">Discount Applied</h4>
                                  <?php if (!empty($order['discount_code'])): ?>
                                    <p style="margin: 0; font-size: 13px;">Code: <?php echo htmlspecialchars($order['discount_code']); ?></p>
                                  <?php endif; ?>
                                  <?php if ($order['discount_type']): ?>
                                    <p style="margin: 0; font-size: 13px;">
                                      Type: <?php echo $order['discount_type'] === 'percentage' ? 'Percentage' : 'Fixed Amount'; ?> 
                                      (<?php echo $order['discount_type'] === 'percentage' ? $order['discount_value'] . '%' : number_format($order['discount_value'], 0) . ' D.A'; ?>)
                                    </p>
                                  <?php endif; ?>
                                </div>
                              <?php endif; ?>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>
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

      // Toggle order details
      const toggleButtons = document.querySelectorAll('.toggle-details');
      toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
          const orderId = this.getAttribute('data-order-id');
          const detailsRow = document.getElementById(`details-${orderId}`);
          if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
            detailsRow.style.display = 'table-row';
            this.innerHTML = '<i class="fa-solid fa-chevron-up"></i>';
          } else {
            detailsRow.style.display = 'none';
            this.innerHTML = '<i class="fa-solid fa-ellipsis-h"></i>';
          }
        });
      });
    });
  </script>
</body>
</html>
