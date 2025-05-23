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
  
  // Form submission handling
  if(isset($_POST['submit'])) {
    $admin_name = mysqli_real_escape_string($con, $_POST['admin_name']);
    $admin_email = mysqli_real_escape_string($con, $_POST['admin_email']);
    $admin_pass = mysqli_real_escape_string($con, $_POST['admin_pass']);
    $confirm_pass = mysqli_real_escape_string($con, $_POST['confirm_pass']);
    $admin_contact = mysqli_real_escape_string($con, $_POST['admin_contact']);
    $admin_country = mysqli_real_escape_string($con, $_POST['admin_country']);
    $admin_job = mysqli_real_escape_string($con, $_POST['admin_job']);
    $admin_about = mysqli_real_escape_string($con, $_POST['admin_about']);
    
    // Check if passwords match
    if($admin_pass !== $confirm_pass) {
      $error_message = "Passwords do not match!";
    } else {
      // Check if email already exists
      $check_email = "SELECT * FROM admins WHERE admin_email='$admin_email'";
      $run_check = mysqli_query($con, $check_email);
      $count = mysqli_num_rows($run_check);
      
      if($count > 0) {
        $error_message = "Email already exists!";
      } else {
        // Handle image upload
        $admin_image = $_FILES['admin_image']['name'];
        $temp_name = $_FILES['admin_image']['tmp_name'];
        
        // Generate a unique filename to avoid special character issues
        $image_extension = pathinfo($admin_image, PATHINFO_EXTENSION);
        $new_image_name = "admin_" . time() . "." . $image_extension;
        
        // Move uploaded file with new name
        move_uploaded_file($temp_name, "admin_images/$new_image_name");
        
        // Hash the password for security
        $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
        
        // Use prepared statement to avoid SQL injection and special character issues
        $stmt = mysqli_prepare($con, "INSERT INTO admins (admin_name, admin_email, admin_pass, admin_image, admin_contact, admin_country, admin_job, admin_about) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        mysqli_stmt_bind_param($stmt, "ssssssss", 
            $admin_name, 
            $admin_email, 
            $hashed_password, 
            $new_image_name, 
            $admin_contact, 
            $admin_country, 
            $admin_job, 
            $admin_about
        );
        
        if(mysqli_stmt_execute($stmt)) {
          $success_message = "Admin added successfully!";
        } else {
          $error_message = "Failed to add admin: " . mysqli_stmt_error($stmt);
        }
        
        mysqli_stmt_close($stmt);
      }
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Insert Admin | Admin Dashboard</title>
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
            <li class=>
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
                <li class = active><a href="insert-admin.php">Insert Admin</a></li>
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
          <h1>Insert Admin</h1>
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
            <h3 class="card-title">Add New Administrator</h3>
          </div>
          <div class="card-content">
            <form action="" method="post" enctype="multipart/form-data">
              <div class="form-row">
                <div class="form-group">
                  <label>Admin Name</label>
                  <input type="text" name="admin_name" required>
                </div>
                <div class="form-group">
                  <label>Admin Email</label>
                  <input type="email" name="admin_email" required>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label>Admin Password</label>
                  <input type="password" name="admin_pass" required>
                </div>
                <div class="form-group">
                  <label>Confirm Password</label>
                  <input type="password" name="confirm_pass" required>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label>Admin Contact</label>
                  <input type="text" name="admin_contact" required>
                </div>
                <div class="form-group">
                  <label>Admin Country</label>
                  <input type="text" name="admin_country" required>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label>Admin Job</label>
                  <input type="text" name="admin_job" required>
                </div>
                <div class="form-group">
                  <label>Admin Image</label>
                  <input type="file" name="admin_image" required>
                </div>
              </div>
              
              <div class="form-group">
                <label>Admin About</label>
                <textarea name="admin_about" rows="5" required></textarea>
              </div>
              
              <div class="form-group">
                <button type="submit" name="submit" class="btn-primary">
                  <i class="fa-solid fa-plus"></i> Add Admin
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
  </script>
</body>
</html>