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

// Get counts for dashboard
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM customers"))['count'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM customer_orders"))['count'];
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM customer_orders WHERE order_status = 'pending'"))['count'];
$completed_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM customer_orders WHERE order_status = 'completed'"))['count'];
$cancelled_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM customer_orders WHERE order_status = 'cancelled'"))['count'];

// Get total revenue
$revenue_query = "SELECT SUM(due_amount) as total FROM customer_orders WHERE order_status = 'completed'";
$revenue_result = mysqli_query($conn, $revenue_query);
$total_revenue = mysqli_fetch_assoc($revenue_result)['total'] ?? 0;

// Get recent orders
$recent_orders_query = "
    SELECT co.*, c.customer_name, c.email 
    FROM customer_orders co
    JOIN customers c ON co.customer_id = c.customer_id
    ORDER BY co.order_date DESC
    LIMIT 5";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);
$recent_orders = [];
while ($order = mysqli_fetch_assoc($recent_orders_result)) {
    $recent_orders[] = $order;
}

// Get top selling products
$top_products_query = "
    SELECT p.product_id, p.product_title, COUNT(oi.product_id) as order_count, SUM(oi.quantity) as total_quantity
    FROM products p
    JOIN order_items oi ON p.product_id = oi.product_id
    JOIN customer_orders co ON oi.order_id = co.order_id
    WHERE co.order_status = 'completed'
    GROUP BY p.product_id
    ORDER BY total_quantity DESC
    LIMIT 5";
$top_products_result = mysqli_query($conn, $top_products_query);
$top_products = [];
if ($top_products_result) {
    while ($product = mysqli_fetch_assoc($top_products_result)) {
        $top_products[] = $product;
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechMarket - Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Enhanced Dashboard Statistics Styles */
        .dashboard-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 0;
        }

        .dashboard-stat-card {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, rgba(255, 255, 255, 0.05) 100%);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .dashboard-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-gradient, linear-gradient(90deg, #4ade80, #22c55e));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .dashboard-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: rgba(74, 222, 128, 0.3);
        }

        .dashboard-stat-card:hover::before {
            opacity: 1;
        }

        .dashboard-stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
            position: relative;
        }

        .dashboard-stat-icon::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 12px;
            background: inherit;
            opacity: 0.1;
        }

        .dashboard-stat-content {
            flex: 1;
            min-width: 0;
        }

        .dashboard-stat-content h3 {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            margin: 0 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dashboard-stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.2;
        }

        .dashboard-stat-trend {
            font-size: 12px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .trend-up {
            color: #22c55e;
        }

        .trend-down {
            color: #ef4444;
        }

        .trend-neutral {
            color: var(--text-secondary);
        }

        /* Specific card colors */
        .stat-products .dashboard-stat-icon {
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
            --accent-gradient: linear-gradient(90deg, #4ade80, #22c55e);
        }

        .stat-customers .dashboard-stat-icon {
            background: rgba(56, 189, 248, 0.2);
            color: #38bdf8;
            --accent-gradient: linear-gradient(90deg, #38bdf8, #0ea5e9);
        }

        .stat-orders .dashboard-stat-icon {
            background: rgba(251, 146, 60, 0.2);
            color: #fb923c;
            --accent-gradient: linear-gradient(90deg, #fb923c, #ea580c);
        }

        .stat-revenue .dashboard-stat-icon {
            background: rgba(168, 85, 247, 0.2);
            color: #a855f7;
            --accent-gradient: linear-gradient(90deg, #a855f7, #9333ea);
        }

        .stat-pending .dashboard-stat-icon {
            background: rgba(234, 179, 8, 0.2);
            color: #eab308;
            --accent-gradient: linear-gradient(90deg, #eab308, #ca8a04);
        }

        .stat-completed .dashboard-stat-icon {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            --accent-gradient: linear-gradient(90deg, #22c55e, #16a34a);
        }

        .stat-cancelled .dashboard-stat-icon {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            --accent-gradient: linear-gradient(90deg, #ef4444, #dc2626);
        }

        /* Responsive adjustments */
        @media (max-width: 1400px) {
            .dashboard-stats-row {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 1200px) {
            .dashboard-stats-row {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .dashboard-stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .dashboard-stats-row {
                grid-template-columns: 1fr;
            }
            
            .dashboard-stat-card {
                padding: 20px;
            }
            
            .dashboard-stat-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }
            
            .dashboard-stat-value {
                font-size: 24px;
            }
        }

        /* Remove old styles */
        .stats-grid,
        .order-status-grid {
            display: none;
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
                        <li class="active">
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
                    <h1>Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="search-container">
                        <input type="search" placeholder="Search...">
                        <i class="fa-solid fa-search"></i>
                    </div>
                </div>
            </header>

            <div class="content-wrapper">
                <!-- Enhanced Stats Row - All Statistics in One Row -->
                <div class="dashboard-stats-row">
                    <div class="dashboard-stat-card stat-products">
                        <div class="dashboard-stat-icon">
                            <i class="fa-solid fa-box"></i>
                        </div>
                        <div class="dashboard-stat-content">
                            <h3>Total Products</h3>
                            <p class="dashboard-stat-value"><?php echo number_format($total_products); ?></p>
                            <div class="dashboard-stat-trend trend-neutral">
                                <i class="fa-solid fa-minus"></i>
                                <span>Active inventory</span>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-stat-card stat-customers">
                        <div class="dashboard-stat-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="dashboard-stat-content">
                            <h3>Total Customers</h3>
                            <p class="dashboard-stat-value"><?php echo number_format($total_customers); ?></p>
                            <div class="dashboard-stat-trend trend-up">
                                <i class="fa-solid fa-arrow-up"></i>
                                <span>Growing base</span>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-stat-card stat-orders">
                        <div class="dashboard-stat-icon">
                            <i class="fa-solid fa-shopping-cart"></i>
                        </div>
                        <div class="dashboard-stat-content">
                            <h3>Total Orders</h3>
                            <p class="dashboard-stat-value"><?php echo number_format($total_orders); ?></p>
                            <div class="dashboard-stat-trend trend-up">
                                <i class="fa-solid fa-arrow-up"></i>
                                <span>All time</span>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-stat-card stat-revenue">
                        <div class="dashboard-stat-icon">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div class="dashboard-stat-content">
                            <h3>Total Revenue</h3>
                            <p class="dashboard-stat-value"><?php echo number_format($total_revenue, 0); ?> D.A</p>
                            <div class="dashboard-stat-trend trend-up">
                                <i class="fa-solid fa-arrow-up"></i>
                                <span>Completed orders</span>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-stat-card stat-pending">
                        <div class="dashboard-stat-icon">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="dashboard-stat-content">
                            <h3>Pending Orders</h3>
                            <p class="dashboard-stat-value"><?php echo number_format($pending_orders); ?></p>
                            <div class="dashboard-stat-trend trend-neutral">
                                <i class="fa-solid fa-clock"></i>
                                <span>Awaiting processing</span>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-stat-card stat-completed">
                        <div class="dashboard-stat-icon">
                            <i class="fa-solid fa-check-circle"></i>
                        </div>
                        <div class="dashboard-stat-content">
                            <h3>Completed Orders</h3>
                            <p class="dashboard-stat-value"><?php echo number_format($completed_orders); ?></p>
                            <div class="dashboard-stat-trend trend-up">
                                <i class="fa-solid fa-check"></i>
                                <span>Successfully delivered</span>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-stat-card stat-cancelled">
                        <div class="dashboard-stat-icon">
                            <i class="fa-solid fa-times-circle"></i>
                        </div>
                        <div class="dashboard-stat-content">
                            <h3>Cancelled Orders</h3>
                            <p class="dashboard-stat-value"><?php echo number_format($cancelled_orders); ?></p>
                            <div class="dashboard-stat-trend trend-down">
                                <i class="fa-solid fa-times"></i>
                                <span>Cancelled by users</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <div class="card chart-card">
                        <div class="card-header">
                            <h2>Order Statistics</h2>
                            <div class="chart-controls">
                                <select id="order-chart-period">
                                    <option value="last_7_days">Last 7 Days</option>
                                    <option value="last_30_days">Last 30 Days</option>
                                    <option value="last_3_months" selected>Last 3 Months</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-content">
                            <canvas id="orderChart"></canvas>
                        </div>
                    </div>
                    <div class="card chart-card">
                        <div class="card-header">
                            <h2>Successful Orders</h2>
                            <div class="chart-controls">
                                <select id="success-chart-period">
                                    <option value="last_7_days">Last 7 Days</option>
                                    <option value="last_30_days">Last 30 Days</option>
                                    <option value="last_3_months" selected>Last 3 Months</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-content">
                            <canvas id="successChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders Table -->
                <div class="table-section">
                    <div class="card">
                        <div class="card-header table-header">
                            <h2>Recent Orders</h2>
                            <a href="view-orders.php" class="btn btn-outline">View All</a>
                        </div>
                        <div class="card-content">
                            <?php if (empty($recent_orders)): ?>
                                <div class="empty-state">No recent orders found.</div>
                            <?php else: ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                                    <span style="color: var(--text-secondary); font-size: 12px;"><?php echo htmlspecialchars($order['email']); ?></span>
                                                </td>
                                                <td><?php echo number_format($order['due_amount'], 0) . ' D.A'; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo strtolower($order['order_status']); ?>">
                                                        <?php echo ucfirst($order['order_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Products Table -->
                <div class="table-section">
                    <div class="card">
                        <div class="card-header table-header">
                            <h2>Top Selling Products</h2>
                            <a href="view-products.php" class="btn btn-outline">View All Products</a>
                        </div>
                        <div class="card-content">
                            <?php if (empty($top_products)): ?>
                                <div class="empty-state">No product sales data available.</div>
                            <?php else: ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Product ID</th>
                                            <th>Product Name</th>
                                            <th>Orders</th>
                                            <th>Units Sold</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($product['product_id']); ?></td>
                                                <td><?php echo htmlspecialchars($product['product_title']); ?></td>
                                                <td><?php echo number_format($product['order_count']); ?></td>
                                                <td><?php echo number_format($product['total_quantity']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
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

            // Order Chart
            let orderChart;
            function fetchOrderData(period) {
                fetch(`fetch_order_data.php?period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        if (orderChart) {
                            orderChart.destroy();
                        }
                        
                        const ctx = document.getElementById('orderChart').getContext('2d');
                        orderChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.labels,
                                datasets: data.datasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(255, 255, 255, 0.1)'
                                        },
                                        ticks: {
                                            color: 'rgba(255, 255, 255, 0.7)'
                                        }
                                    },
                                    x: {
                                        grid: {
                                            color: 'rgba(255, 255, 255, 0.1)'
                                        },
                                        ticks: {
                                            color: 'rgba(255, 255, 255, 0.7)'
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        labels: {
                                            color: 'rgba(255, 255, 255, 0.7)'
                                        }
                                    }
                                }
                            }
                        });
                    })
                    .catch(error => console.error('Error fetching order data:', error));
            }

            // Success Chart
            let successChart;
            function fetchSuccessData(period) {
                fetch(`fetch_successful_orders.php?period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        if (successChart) {
                            successChart.destroy();
                        }
                        
                        const ctx = document.getElementById('successChart').getContext('2d');
                        successChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    label: 'Successful Orders',
                                    data: data.data,
                                    backgroundColor: 'rgba(74, 222, 128, 0.2)',
                                    borderColor: '#4ade80',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(255, 255, 255, 0.1)'
                                        },
                                        ticks: {
                                            color: 'rgba(255, 255, 255, 0.7)'
                                        }
                                    },
                                    x: {
                                        grid: {
                                            color: 'rgba(255, 255, 255, 0.1)'
                                        },
                                        ticks: {
                                            color: 'rgba(255, 255, 255, 0.7)'
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        labels: {
                                            color: 'rgba(255, 255, 255, 0.7)'
                                        }
                                    }
                                }
                            }
                        });
                    })
                    .catch(error => console.error('Error fetching success data:', error));
            }

            // Initial chart loads
            fetchOrderData('last_3_months');
            fetchSuccessData('last_3_months');

            // Chart period selectors
            document.getElementById('order-chart-period').addEventListener('change', function() {
                fetchOrderData(this.value);
            });

            document.getElementById('success-chart-period').addEventListener('change', function() {
                fetchSuccessData(this.value);
            });
        });
    </script>
</body>
</html>
