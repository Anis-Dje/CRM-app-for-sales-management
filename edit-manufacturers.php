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
// Include database connection
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

// Check if manufacturer ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid manufacturer ID')</script>";
    echo "<script>window.open('view-manufacturers.php','_self')</script>";
    exit();
}

$manufacturer_id = intval($_GET['id']);

// Get manufacturer data
$stmt = $conn->prepare("SELECT * FROM manufacturers WHERE manufacturer_id = ?");
$stmt->bind_param("i", $manufacturer_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Manufacturer not found')</script>";
    echo "<script>window.open('view-manufacturers.php','_self')</script>";
    exit();
}

$manufacturer = $result->fetch_assoc();
$stmt->close();

// Function to handle file upload
function uploadImage($file, $target_dir = "manufacturer_images/") {
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Generate unique filename
    $filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $filename;
    
    // Check if image file is an actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return false;
    }
    
    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        return false;
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $filename; // Return just the filename, not the full path
    } else {
        return false;
    }
}

// Process form submission
if (isset($_POST['update'])) {
    // Sanitize form data
    $manufacturer_title = trim($_POST['manufacturer_title']);
    $manufacturer_top = $_POST['manufacturer_top'];
    
    // Handle image upload - only update if a new image is uploaded
    $manufacturer_image = $manufacturer['manufacturer_image'];
    
    if (isset($_FILES['manufacturer_image']) && $_FILES['manufacturer_image']['error'] == 0) {
        $new_image = uploadImage($_FILES['manufacturer_image']);
        if ($new_image) {
            $manufacturer_image = $new_image;
        } else {
            echo "<script>alert('Error uploading image. Manufacturer will be updated without changing the image.')</script>";
        }
    }
    
    // Update manufacturer in database using prepared statement
    $stmt = $conn->prepare("UPDATE manufacturers SET 
                            manufacturer_title = ?, 
                            manufacturer_image = ?, 
                            manufacturer_top = ?
                            WHERE manufacturer_id = ?");
    $stmt->bind_param("sssi", $manufacturer_title, $manufacturer_image, $manufacturer_top, $manufacturer_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Manufacturer has been updated successfully')</script>";
        echo "<script>window.open('view-manufacturers.php','_self')</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "')</script>";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Manufacturer - Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom styles for image preview */
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
        
        .current-image {
            margin-top: 10px;
            margin-bottom: 5px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .current-image-preview {
            width: 100%;
            height: 100px;
            border-radius: 4px;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .current-image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        input[type="file"] {
            width: 100%;
            padding: 8px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-secondary);
        }
        
        @media (max-width: 768px) {
            .image-upload-container {
                flex-direction: column;
            }
            
            .image-upload {
                max-width: 100%;
            }
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
                <li class="active"><a href="edit-manufacturers.php">Edit Manufacturer</a></li>
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
                    <h1>Edit Manufacturer</h1>
                </div>
                <div class="header-right">
                    <div class="search-container">
                        <input type="search" placeholder="Search...">
                        <i class="fa-solid fa-search"></i>
                    </div>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit Manufacturer: <?php echo htmlspecialchars($manufacturer['manufacturer_title']); ?></h3>
                        <a href="view-manufacturers.php" class="btn btn-outline">
                            <i class="fa-solid fa-arrow-left"></i> Back to Manufacturers
                        </a>
                    </div>
                    <div class="card-content">
                        <form action="" method="post" enctype="multipart/form-data" class="product-form">
                            <div class="form-group">
                                <label for="manufacturer_title">Manufacturer Title</label>
                                <input type="text" id="manufacturer_title" name="manufacturer_title" value="<?php echo htmlspecialchars($manufacturer['manufacturer_title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="manufacturer_image">Manufacturer Image</label>
                                <div class="image-upload-container">
                                    <div class="image-upload">
                                        <?php if (!empty($manufacturer['manufacturer_image'])): ?>
                                            <div class="current-image">Current image:</div>
                                            <div class="current-image-preview">
                                                <img src="<?php echo 'manufacturer_images/' . htmlspecialchars($manufacturer['manufacturer_image']); ?>" alt="Current Manufacturer Image">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" id="manufacturer_image" name="manufacturer_image" accept="image/*">
                                        <small>Leave empty to keep current image</small>
                                        <div class="image-preview" id="preview"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="manufacturer_top">Top Manufacturer</label>
                                <select id="manufacturer_top" name="manufacturer_top" required>
                                    <option value="yes" <?php echo $manufacturer['manufacturer_top'] == 'yes' ? 'selected' : ''; ?>>Yes</option>
                                    <option value="no" <?php echo $manufacturer['manufacturer_top'] == 'no' ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i> Update Manufacturer
                                </button>
                                <a href="view-manufacturers.php" class="btn btn-outline">
                                    <i class="fa-solid fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
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
            
            // Image preview
            function previewImage(input, previewId) {
                const preview = document.getElementById(previewId);
                preview.innerHTML = '';
                
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        preview.appendChild(img);
                    }
                    
                    reader.readAsDataURL(input.files[0]);
                }
            }
            
            document.getElementById('manufacturer_image').addEventListener('change', function() {
                previewImage(this, 'preview');
            });
        });
    </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>