<?php
session_start();

// Redirect to login if not authenticated as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: auth.php");
    exit;
}

// Function to check if an image file exists and return the correct path
function getImagePath($imageName) {
    $basePath = 'admin_images/';
    $fullPath = $basePath . $imageName;
    $defaultImage = $basePath . 'default-profile.png';
    
    if ($imageName && file_exists($fullPath)) {
        return $fullPath;
    }
    return $defaultImage;
}

// Database connection
$con = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// Delete admin if requested
if (isset($_GET['delete'])) {
    $delete_id = mysqli_real_escape_string($con, $_GET['delete']);
    
    // Prevent deleting the logged-in admin
    if ($delete_id == $_SESSION['admin_id']) {
        $error_message = "You cannot delete your own account!";
    } else {
        // Delete the admin
        $delete_admin = "DELETE FROM admins WHERE admin_id='$delete_id'";
        $run_delete = mysqli_query($con, $delete_admin);
        
        if ($run_delete) {
            $success_message = "Admin has been deleted successfully!";
        } else {
            $error_message = "Failed to delete admin: " . mysqli_error($con);
        }
    }
}

// Pagination
$per_page = 10;

if (isset($_GET['page'])) {
    $page = (int)$_GET['page'];
} else {
    $page = 1;
}

$start_from = ($page - 1) * $per_page;

// Get admins with pagination
$get_admins = "SELECT * FROM admins ORDER BY admin_id DESC LIMIT $start_from, $per_page";
$run_admins = mysqli_query($con, $get_admins);

if (!$run_admins) {
    die("Query failed: " . mysqli_error($con));
}

// Debug: Log the number of rows
$num_rows = mysqli_num_rows($run_admins);
error_log("Number of admins fetched: $num_rows");

// Get total number of admins
$count_query = "SELECT COUNT(*) as total FROM admins";
$count_result = mysqli_query($con, $count_query);

if (!$count_result) {
    die("Count query failed: " . mysqli_error($con));
}

$count_row = mysqli_fetch_assoc($count_result);
$total_admins = $count_row['total'];
$total_pages = ceil($total_admins / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechMarket - View Admins</title>
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
                <li class = active><a href="view-admins.php">View Admins</a></li>
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
          <h1>View Admins</h1>
        </div>
        <div class="header-right">
          <div class="search-container">
            <input type="search" placeholder="Search Admins..." id="searchAdmins" onkeyup="searchAdmins()">
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
            <h3 class="card-title">Admin List</h3>
            <div class="table-actions">
              <button class="btn btn-outline" onclick="exportAdmins()">
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
            <table class="data-table" id="adminsTable">
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
                  <th>Contact</th>
                  <th>Job</th>
                  <th>About</th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  if (mysqli_num_rows($run_admins) > 0) {
                    while ($row_admin = mysqli_fetch_assoc($run_admins)) {
                      $admin_id = $row_admin['admin_id'];
                      $admin_name = $row_admin['admin_name'];
                      $admin_email = $row_admin['admin_email'];
                      $admin_image = $row_admin['admin_image'];
                      $admin_contact = $row_admin['admin_contact'];
                      $admin_country = $row_admin['admin_country'];
                      $admin_job = $row_admin['admin_job'];
                      $admin_about = $row_admin['admin_about'];
                ?>
                <tr>
                  <td class="checkbox-column">
                    <input type="checkbox" class="admin-checkbox" value="<?php echo $admin_id; ?>">
                  </td>
                  <td><?php echo $admin_id; ?></td>
                  <td>
                    <img src="<?php echo htmlspecialchars(getImagePath($admin_image)); ?>" alt="<?php echo htmlspecialchars($admin_name); ?>" width="40" height="40" style="border-radius: 35%;">
                  </td>
                  <td><?php echo htmlspecialchars($admin_name); ?></td>
                  <td><?php echo htmlspecialchars($admin_email); ?></td>
                  <td><?php echo htmlspecialchars($admin_country); ?></td>
                  <td><?php echo htmlspecialchars($admin_contact); ?></td>
                  <td><?php echo htmlspecialchars($admin_job); ?></td>
                  <td><?php echo htmlspecialchars($admin_about); ?></td>
                  <td class="actions-column">
                    <div class="action-buttons">
                      <a href="profile.php?id=<?php echo $admin_id; ?>" class="btn-icon" title="View Profile">
                        <i class="fa-solid fa-eye"></i>
                      </a>
                      <a href="edit-admins.php?id=<?php echo $admin_id; ?>" class="btn-icon" title="Edit">
                        <i class="fa-solid fa-edit"></i>
                      </a>
                      <a href="view-admins.php?delete=<?php echo $admin_id; ?>" class="btn-icon" title="Delete" onclick="return confirm('Are you sure you want to delete this admin?')">
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
                  <td colspan="10" style="text-align: center; padding: 20px;">No admins found</td>
                </tr>
                <?php
                  }
                ?>
              </tbody>
            </table>
            
            <div class="pagination" style="margin-top: 20px; display: flex; justify-content: center;">
              <?php if ($page > 1): ?>
                <a href="view-admins.php?page=<?php echo ($page - 1); ?>" class="btn btn-outline">Previous</a>
              <?php else: ?>
                <button class="btn btn-outline" disabled>Previous</button>
              <?php endif; ?>
              
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                  <a href="view-admins.php?page=<?php echo $i; ?>" class="btn btn-outline" style="margin: 0 5px; background-color: rgba(74, 222, 128, 0.1); color: var(--accent-color);"><?php echo $i; ?></a>
                <?php else: ?>
                  <a href="view-admins.php?page=<?php echo $i; ?>" class="btn btn-outline" style="margin: 0 5px;"><?php echo $i; ?></a>
                <?php endif; ?>
              <?php endfor; ?>
              
              <?php if ($page < $total_pages): ?>
                <a href="view-admins.php?page=<?php echo ($page + 1); ?>" class="btn btn-outline">Next</a>
              <?php else: ?>
                <button class="btn btn-outline" disabled>Next</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

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
          var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex));
          csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
          return csv;
        } catch (e) {
          console.error(e);
          return "";
        }
      }
      return gk_fileData[filename] || "";
    }
  </script>

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
    
    // Search admins function
    function searchAdmins() {
      const input = document.getElementById('searchAdmins');
      const filter = input.value.toUpperCase();
      const table = document.getElementById('adminsTable');
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
      const checkboxes = document.getElementsByClassName('admin-checkbox');
      
      for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = selectAll.checked;
      }
    }
    
    // Export admins function (placeholder)
    function exportAdmins() {
      alert('Export functionality would be implemented here');
    }
    
    // Toggle filters function (placeholder)
    function toggleFilters() {
      alert('Filter functionality would be implemented here');
    }
    });
  </script>
</body>
</html>
<?php
// Close the database connection
mysqli_close($con);
?>