<?php
session_start();

// Redirect to login if not authenticated as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: auth.php");
    exit;
}


// Redirect to login if not authenticated as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: auth.php");
    exit;
}

// Database connection
$con = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// Form submission handling
if (isset($_POST['submit'])) {
    $manufacturer_title = mysqli_real_escape_string($con, $_POST['manufacturer_title']);
    $manufacturer_top = mysqli_real_escape_string($con, $_POST['manufacturer_top']);
    $manufacturer_image = $_FILES['manufacturer_image']['name'];
    $temp_name = $_FILES['manufacturer_image']['tmp_name'];

    // Check if manufacturer title already exists
    $check_title = "SELECT * FROM manufacturers WHERE manufacturer_title='$manufacturer_title'";
    $run_check = mysqli_query($con, $check_title);
    $count = mysqli_num_rows($run_check);

    if ($count > 0) {
        $error_message = "Manufacturer title already exists!";
    } else {
        // Handle image upload
        $image_extension = pathinfo($manufacturer_image, PATHINFO_EXTENSION);
        $new_image_name = "manufacturer_" . time() . "." . $image_extension;

        // Move uploaded file with new name
        if (move_uploaded_file($temp_name, "manufacturer_images/$new_image_name")) {
            // Use prepared statement to avoid SQL injection
            $stmt = mysqli_prepare($con, "INSERT INTO manufacturers (manufacturer_title, manufacturer_top, manufacturer_image) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $manufacturer_title, $manufacturer_top, $new_image_name);

            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Manufacturer added successfully!";
            } else {
                $error_message = "Failed to add manufacturer: " . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Failed to upload image!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Insert Manufacturer | Admin Dashboard</title>
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
                <li class="active"><a href="insert-manufacturer.php">Insert Manufacturer</a></li>
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
          <h1>Insert Manufacturer</h1>
        </div>
        <div class="header-right">
          <div class="search-container">
            <input type="search" placeholder="Search...">
            <i class="fa-solid fa-search"></i>
          </div>
        </div>
      </header>

      <div class="content-wrapper">
        <?php if (isset($error_message)): ?>
          <div class="alert" style="background-color: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_message; ?>
          </div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
          <div class="alert" style="background-color: rgba(74, 222, 128, 0.2); color: var(--accent-color); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-check"></i> <?php echo $success_message; ?>
          </div>
        <?php endif; ?>
        
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Add New Manufacturer</h3>
          </div>
          <div class="card-content">
            <form action="" method="post" enctype="multipart/form-data">
              <div class="form-row">
                <div class="form-group">
                  <label>Manufacturer Title</label>
                  <input type="text" name="manufacturer_title" required>
                </div>
                <div class="form-group">
                  <label>Top Manufacturer</label>
                  <select name="manufacturer_top" required>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                  </select>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label>Manufacturer Image</label>
                  <input type="file" name="manufacturer_image" required>
                </div>
              </div>
              
              <div class="form-group">
                <button type="submit" name="submit" class="btn-primary">
                  <i class="fa-solid fa-plus"></i> Add Manufacturer
                </button>
              </div>
            </form>
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
  </script>
</body>
</html>