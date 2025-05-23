<script type="text/javascript">
        var gk_isXlsx = false;
        var gk_xlsxFileLookup = {};
        var gk_fileData = {};
        function filledCell(cell) {
          return cell !== '' && cell != null;
        }
        function loadFileData(filename) {
        if (gk_isXlsx && gk_xlsxFileLookup[filename]) {
            try {
                var workbook = XLSX.read(gk_fileData[filename], { type: 'base64' });
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                // Convert sheet to JSON to filter blank rows
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                // Filter out blank rows (rows where all cells are empty, null, or undefined)
                var filteredData = jsonData.filter(row => row.some(filledCell));

                // Heuristic to find the header row by ignoring rows with fewer filled cells than the next row
                var headerRowIndex = filteredData.findIndex((row, index) =>
                  row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                );
                // Fallback
                if (headerRowIndex === -1 || headerRowIndex > 25) {
                  headerRowIndex = 0;
                }

                // Convert filtered JSON back to CSV
                var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex)); // Create a new sheet from filtered array of arrays
                csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
                return csv;
            } catch (e) {
                console.error(e);
                return "";
            }
        }
        return gk_fileData[filename] || "";
        }
        </script><?php
        session_start();

        // Redirect to login if not authenticated as admin
        if (!isset($_SESSION['admin_id'])) {
            header("Location: auth.php");
            exit;
        }     
  // Database connection
  $con = mysqli_connect("localhost:3307","root","","ecom_store");
  
  if(mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
  }
  
  // Check if admin ID is provided
  if(!isset($_GET['id'])) {
    header("Location: view-admins.php");
    exit();
  }
  
  $admin_id = mysqli_real_escape_string($con, $_GET['id']);
  
  // Get admin details
  $get_admin = "SELECT * FROM admins WHERE admin_id = '$admin_id'";
  $run_admin = mysqli_query($con, $get_admin);
  
  if(mysqli_num_rows($run_admin) == 0) {
    header("Location: view-admins.php");
    exit();
  }
  
  $row_admin = mysqli_fetch_array($run_admin);
  $admin_name = $row_admin['admin_name'];
  $admin_email = $row_admin['admin_email'];
  $admin_country = $row_admin['admin_country'];
  $admin_contact = $row_admin['admin_contact'];
  $admin_job = $row_admin['admin_job'];
  $admin_image = $row_admin['admin_image'];
  $admin_about = $row_admin['admin_about'];
  
  // Form submission handling
  if(isset($_POST['update_admin'])) {
    $admin_name = mysqli_real_escape_string($con, $_POST['admin_name']);
    $admin_email = mysqli_real_escape_string($con, $_POST['admin_email']);
    $admin_country = mysqli_real_escape_string($con, $_POST['admin_country']);
    $admin_contact = mysqli_real_escape_string($con, $_POST['admin_contact']);
    $admin_job = mysqli_real_escape_string($con, $_POST['admin_job']);
    $admin_about = mysqli_real_escape_string($con, $_POST['admin_about']);
    
    // Check if password is being updated
    if(!empty($_POST['admin_pass'])) {
      $admin_pass = mysqli_real_escape_string($con, $_POST['admin_pass']);
      $password_update = ", admin_pass='$admin_pass'";
    } else {
      $password_update = "";
    }
    
    // Check if image is being updated
    if(!empty($_FILES['admin_image']['name'])) {
      $admin_image = $_FILES['admin_image']['name'];
      $temp_name = $_FILES['admin_image']['tmp_name'];
      
      // Move uploaded file
      move_uploaded_file($temp_name, "admin_images/$admin_image");
      $image_update = ", admin_image='$admin_image'";
    } else {
      $image_update = "";
    }
    
    // Update admin in database
    $update_admin = "UPDATE admins SET 
                        admin_name='$admin_name', 
                        admin_email='$admin_email', 
                        admin_country='$admin_country', 
                        admin_contact='$admin_contact', 
                        admin_job='$admin_job',
                        admin_about='$admin_about'
                        $password_update
                        $image_update
                        WHERE admin_id='$admin_id'";
    
    $run_update = mysqli_query($con, $update_admin);
    
    if($run_update) {
      $success_message = "Admin updated successfully!";
      
      // Refresh admin data
      $get_admin = "SELECT * FROM admins WHERE admin_id = '$admin_id'";
      $run_admin = mysqli_query($con, $get_admin);
      $row_admin = mysqli_fetch_array($run_admin);
      
      $admin_name = $row_admin['admin_name'];
      $admin_email = $row_admin['admin_email'];
      $admin_country = $row_admin['admin_country'];
      $admin_contact = $row_admin['admin_contact'];
      $admin_job = $row_admin['admin_job'];
      $admin_image = $row_admin['admin_image'];
      $admin_about = $row_admin['admin_about'];
    } else {
      $error_message = "Failed to update admin: " . mysqli_error($con);
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Admin | Admin Dashboard</title>
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
                <ul class="submenu ">
                <li><a href="insert-admin.php">Insert Admin</a></li>
                <li class="active"><a href="view-admins.php">View Admins</a></li>
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
              <a href="">
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
          <h1>Edit Admin</h1>
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
            <h3 class="card-title">Admin Details</h3>
            <a href="view-admins.php" class="btn btn-outline">
              <i class="fa-solid fa-arrow-left"></i> Back to Admins
            </a>
          </div>
          <div class="card-content">
            <div style="display: flex; gap: 30px; margin-bottom: 30px;">
              <div style="flex: 0 0 200px;">
                <div style="width: 200px; height: 200px; border-radius: 10px; overflow: hidden; margin-bottom: 15px;">
                  <img src="admin_images/<?php echo $admin_image; ?>" alt="Admin" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="text-align: center;">
                  <p style="font-size: 18px; font-weight: bold; margin-bottom: 5px;"><?php echo $admin_name; ?></p>
                  <p style="color: var(--text-secondary); margin-bottom: 15px;"><?php echo $admin_email; ?></p>
                </div>
              </div>
              
              <div style="flex: 1;">
                <form action="" method="post" enctype="multipart/form-data">
                  <div class="form-row">
                    <div class="form-group">
                      <label>Admin Name</label>
                      <input type="text" name="admin_name" value="<?php echo $admin_name; ?>" required>
                    </div>
                    <div class="form-group">
                      <label>Admin Email</label>
                      <input type="email" name="admin_email" value="<?php echo $admin_email; ?>" required>
                    </div>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group">
                      <label>Admin Country</label>
                      <input type="text" name="admin_country" value="<?php echo $admin_country; ?>" required>
                    </div>
                    <div class="form-group">
                      <label>Admin Contact</label>
                      <input type="text" name="admin_contact" value="<?php echo $admin_contact; ?>" required>
                    </div>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group">
                      <label>Admin About</label>
                      <input type="text" name="admin_about" value="<?php echo $admin_about; ?>" required>
                    </div>
                    <div class="form-group">
                      <label>Admin Job</label>
                      <input type="text" name="admin_job" value="<?php echo $admin_job; ?>" required>
                    </div>
                  </div>
                  
                  <div class="form-row">
                    <div class="form-group">
                      <label>New Password (leave blank to keep current)</label>
                      <input type="password" name="admin_pass">
                    </div>
                    <div class="form-group">
                      <label>New Admin Image (optional)</label>
                      <input type="file" name="admin_image">
                    </div>
                  </div>
                  
                  <div class="form-group">
                    <button type="submit" name="update_admin" class="btn-primary">
                      <i class="fa-solid fa-save"></i> Update Admin
                    </button>
                  </div>
                </form>
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