<?php
session_start();
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

// Delete manufacturer if requested
if (isset($_GET['delete'])) {
    $delete_id = mysqli_real_escape_string($con, $_GET['delete']);
    
    // Delete the manufacturer
    $delete_manufacturer = "DELETE FROM manufacturers WHERE manufacturer_id='$delete_id'";
    $run_delete = mysqli_query($con, $delete_manufacturer);
    
    if ($run_delete) {
        $success_message = "Manufacturer has been deleted successfully!";
    } else {
        $error_message = "Failed to delete manufacturer: " . mysqli_error($con);
    }
}

// Pagination
$per_page = 10;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($page - 1) * $per_page;

// Get manufacturers with pagination
$get_manufacturers = "SELECT * FROM manufacturers ORDER BY manufacturer_id DESC LIMIT $start_from, $per_page";
$run_manufacturers = mysqli_query($con, $get_manufacturers);

// Get total number of manufacturers
$count_query = "SELECT COUNT(*) as total FROM manufacturers";
$count_result = mysqli_query($con, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_manufacturers = $count_row['total'];
$total_pages = ceil($total_manufacturers / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Manufacturers | Admin Dashboard</title>
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
                <li class = active><a href="view-manufacturers.php">View Manufacturer</a></li>
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
                    <h1>View Manufacturers</h1>
                </div>
                <div class="header-right">
                    <div class="search-container">
                        <input type="search" placeholder="Search manufacturers..." id="manufacturersearch" onkeyup="searchManufacturers()">
                        <i class="fa-solid fa-search"></i>
                    </div>
                </div>
            </header>

            <div class="content-wrapper">
                <?php if (isset($error_message)): ?>
                    <div class="alert" style="background-color: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert" style="background-color: rgba(74, 222, 128, 0.2); color: var(--accent-color); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Manufacturers List</h3>
                        <div class="table-actions">
                            <button class="btn btn-outline" onclick="exportManufacturers()">
                                <i class="fa-solid fa-download"></i>
                                Export
                            </button>
                        </div>
                    </div>
                    <div class="card-content">
                        <table class="data-table" id="manufacturersTable">
                            <thead>
                                <tr>
                                    <th class="checkbox-column">
                                        <input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes()">
                                    </th>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Top Manufacturer</th>
                                    <th class="actions-column">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (mysqli_num_rows($run_manufacturers) > 0) {
                                    while ($row_manufacturer = mysqli_fetch_assoc($run_manufacturers)) {
                                        $manufacturer_id = $row_manufacturer['manufacturer_id'];
                                        $manufacturer_title = $row_manufacturer['manufacturer_title'];
                                        $manufacturer_top = $row_manufacturer['manufacturer_top'] == 'yes' ? 'Yes' : 'No';
                                        $manufacturer_image = $row_manufacturer['manufacturer_image'];
                                ?>
                                    <tr>
                                        <td class="checkbox-column">
                                            <input type="checkbox" class="manufacturer-checkbox" value="<?php echo htmlspecialchars($manufacturer_id); ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($manufacturer_id); ?></td>
                                        <td>
                                            <img src="manufacturers_images/<?php echo htmlspecialchars($manufacturer_image); ?>" alt="<?php echo htmlspecialchars($manufacturer_title); ?>" width="40" height="40" style="border-radius: 50%;">
                                        </td>
                                        <td><?php echo htmlspecialchars($manufacturer_title); ?></td>
                                        <td><?php echo htmlspecialchars($manufacturer_top); ?></td>
                                        <td class="actions-column">
                                            <div class="action-buttons">
                                                <a href="edit-manufacturers.php?id=<?php echo htmlspecialchars($manufacturer_id); ?>" class="btn-icon" title="Edit">
                                                    <i class="fa-solid fa-edit"></i>
                                                </a>
                                                <a href="view-manufacturers.php?delete=<?php echo htmlspecialchars($manufacturer_id); ?>" class="btn-icon" title="Delete" onclick="return confirm('Are you sure you want to delete this manufacturer?')">
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
                                        <td colspan="6" style="text-align: center; padding: 20px;">No manufacturers found</td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                        
                        <div class="pagination" style="margin-top: 20px; display: flex; justify-content: center;">
                            <?php if ($page > 1): ?>
                                <a href="view-manufacturers.php?page=<?php echo ($page - 1); ?>" class="btn btn-outline">Previous</a>
                            <?php else: ?>
                                <button class="btn btn-outline" disabled>Previous</button>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <a href="view-manufacturers.php?page=<?php echo $i; ?>" class="btn btn-outline" style="margin: 0 5px; background-color: rgba(74, 222, 128, 0.1); color: var(--accent-color);"><?php echo $i; ?></a>
                                <?php else: ?>
                                    <a href="view-manufacturers.php?page=<?php echo $i; ?>" class="btn btn-outline" style="margin: 0 5px;"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="view-manufacturers.php?page=<?php echo ($page + 1); ?>" class="btn btn-outline">Next</a>
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
                // Check if this menu item links to current page or if item is already marked active
                if (itemPage === currentPage || item.parentElement.classList.contains('active')) {
                // Find parent submenu and open it
                const parentSubmenu = item.closest('.has-submenu');
                if (parentSubmenu) {
                    parentSubmenu.classList.add('open');
                }
                }
            });
        
        // Search manufacturers function
        function searchManufacturers() {
            const input = document.getElementById('manufacturersearch');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('manufacturersTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                let found = false;
                const td = tr[i].getElementsByTagName('td');
                
                // Search in Title and Top Manufacturer columns
                for (let j = 3; j <= 4; j++) {
                    const txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }
        
        // Toggle all checkboxes
        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.getElementsByClassName('manufacturer-checkbox');
            
            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = selectAll.checked;
            }
        }
        
        // Export manufacturers as CSV
        function exportManufacturers() {
            const table = document.getElementById('manufacturersTable');
            const rows = Array.from(table.querySelectorAll('tr'));
            const csv = [];
            
            // Add header row
            const headers = Array.from(rows[0].querySelectorAll('th')).slice(1, -1).map(th => th.textContent.trim());
            csv.push(headers.join(','));
            
            // Add data rows (skip the checkbox and actions columns)
            for (let i = 1; i < rows.length; i++) {
                const cols = Array.from(rows[i].querySelectorAll('td')).slice(1, -1);
                const row = cols.map(col => {
                    let text = col.textContent.trim();
                    // If the column contains an image, skip it
                    if (col.querySelector('img')) {
                        return '';
                    }
                    // Escape quotes in the text
                    return `"${text.replace(/"/g, '""')}"`;
                });
                csv.push(row.join(','));
            }
            
            // Create a downloadable CSV file
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'manufacturers.csv';
            link.click();
        }
        });
    </script>
</body>
</html>

<?php
// Close the database connection
mysqli_close($con);
?>