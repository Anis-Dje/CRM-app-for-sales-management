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

// Fetch payments with invoice details and discount information
$payments_query = "
    SELECT p.*, co.invoice_no, d.code as discount_code, d.discount_type, d.discount_value
    FROM payments p
    LEFT JOIN customer_orders co ON p.order_id = co.order_id
    LEFT JOIN discounts d ON co.discount_id = d.id
    ORDER BY p.payment_date DESC";
$payments_result = mysqli_query($conn, $payments_query);

$payments = [];
if ($payments_result && mysqli_num_rows($payments_result) > 0) {
    while ($payment = mysqli_fetch_assoc($payments_result)) {
        $payments[] = $payment;
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechMarket - View Payments</title>
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
                        
                        <li class="active">
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
                    <h1>View Payments</h1>
                </div>
                <div class="header-right">
                    <div class="search-container">
                        <input type="search" placeholder="Search...">
                        <i class="fa-solid fa-search"></i>
                    </div>
                </div>
            </header>

            <div class="content-wrapper">
                <!-- Payments Table -->
                <div class="table-section">
                    <div class="card">
                        <div class="card-header table-header">
                            <div class="tabs">
                                <button class="tab active" data-tab="payments">Payments</button>
                            </div>
                            <div class="table-actions">
                                <button class="btn btn-outline">
                                    <i class="fa-solid fa-columns"></i>
                                    Customize Columns
                                </button>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="tab-content active" id="payments">
                                <?php if (empty($payments)): ?>
                                    <div class="empty-state">No payments found.</div>
                                <?php else: ?>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Payment ID</th>
                                                <th>Order ID</th>
                                                <th>Invoice No</th>
                                                <th>Amount (D.A)</th>
                                                <th>Payment Mode</th>
                                                <th>Reference No</th>
                                                <th>Discount</th>
                                                <th>Payment Date</th>
                                                <th class="actions-column"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($payment['payment_id']); ?></td>
                                                    <td>#<?php echo htmlspecialchars($payment['order_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($payment['invoice_no'] ?: 'N/A'); ?></td>
                                                    <td><?php echo number_format($payment['amount'], 0); ?></td>
                                                    <td><?php echo htmlspecialchars($payment['payment_mode']); ?></td>
                                                    <td><?php echo htmlspecialchars($payment['ref_no']); ?></td>
                                                    <td>
                                                        <?php if (!empty($payment['discount_code'])): ?>
                                                            <?php echo htmlspecialchars($payment['discount_code']); ?>
                                                            (<?php echo $payment['discount_type'] === 'percentage' ? $payment['discount_value'] . '%' : number_format($payment['discount_value'], 0) . ' D.A'; ?>)
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                                    <td class="actions-column">
                                                        <button class="btn-icon" disabled>
                                                            <i class="fa-solid fa-ellipsis-h"></i>
                                                        </button>
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
        });
    </script>
</body>
</html>
