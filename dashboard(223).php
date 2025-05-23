<?php
// Database configuration
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "ecom_store";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Total Revenue
$revenue_query = "SELECT SUM(amount) as total_revenue FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())";
$revenue_result = $conn->query($revenue_query);
$total_revenue = 0;
$revenue_percentage = 0;

if($revenue_result && $revenue_result->num_rows > 0) {
    $revenue_data = $revenue_result->fetch_assoc();
    $total_revenue = $revenue_data['total_revenue'] ?? 0;
    
    // Calculate percentage change from previous month
    $prev_month_query = "SELECT SUM(amount) as prev_revenue FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)";
    $prev_month_result = $conn->query($prev_month_query);
    
    if($prev_month_result && $prev_month_result->num_rows > 0) {
        $prev_revenue = $prev_month_result->fetch_assoc()['prev_revenue'] ?? 0;
        if($prev_revenue > 0) {
            $revenue_percentage = (($total_revenue - $prev_revenue) / $prev_revenue) * 100;
        }
    }
}

// New Customers
$customers_query = "SELECT COUNT(*) as total_customers FROM customers WHERE MONTH(registration_date) = MONTH(CURRENT_DATE())";
$customers_result = $conn->query($customers_query);
$total_customers = 0;
$customers_percentage = 0;

if($customers_result && $customers_result->num_rows > 0) {
    $customers_data = $customers_result->fetch_assoc();
    $total_customers = $customers_data['total_customers'] ?? 0;
    
    // Calculate percentage change from previous month
    $prev_customers_query = "SELECT COUNT(*) as prev_customers FROM customers WHERE MONTH(registration_date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)";
    $prev_customers_result = $conn->query($prev_customers_query);
    
    if($prev_customers_result && $prev_customers_result->num_rows > 0) {
        $prev_customers = $prev_customers_result->fetch_assoc()['prev_customers'] ?? 0;
        if($prev_customers > 0) {
            $customers_percentage = (($total_customers - $prev_customers) / $prev_customers) * 100;
        }
    }
}

// Active Accounts
$accounts_query = "SELECT COUNT(*) as active_accounts FROM customers WHERE status = 'active'";
$accounts_result = $conn->query($accounts_query);
$active_accounts = 0;
$accounts_percentage = 0;

if($accounts_result && $accounts_result->num_rows > 0) {
    $accounts_data = $accounts_result->fetch_assoc();
    $active_accounts = $accounts_data['active_accounts'] ?? 0;
    
    // Calculate percentage change from previous month
    $prev_accounts_query = "SELECT COUNT(*) as prev_accounts FROM customers WHERE status = 'active' AND registration_date <= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)";
    $prev_accounts_result = $conn->query($prev_accounts_query);
    
    if($prev_accounts_result && $prev_accounts_result->num_rows > 0) {
        $prev_accounts = $prev_accounts_result->fetch_assoc()['prev_accounts'] ?? 0;
        if($prev_accounts > 0) {
            $accounts_percentage = (($active_accounts - $prev_accounts) / $prev_accounts) * 100;
        }
    }
}

// Growth Rate (calculated from revenue)
$growth_rate = $revenue_percentage;

// Get visitor data for chart (last 6 months)
$months = [];
$visitor_data = [];

for($i = 5; $i >= 0; $i--) {
    $month = date('F', strtotime("-$i months"));
    $months[] = $month;
    
    // In a real application, you would query your database for visitor data
    // For this example, we'll use random data
    $visitor_data[] = rand(10000, 30000);
}

// Get table data
$table_query = "SELECT * FROM dashboard_sections ORDER BY id LIMIT 4";
$table_result = $conn->query($table_query);
$table_data = [];

// If the table doesn't exist or is empty, use sample data
if(!$table_result || $table_result->num_rows == 0) {
    $table_data = [
        [
            'header' => 'Cover page',
            'section_type' => 'Cover page',
            'status' => 'In Process',
            'target' => 18,
            'limit' => 5,
            'reviewer' => 'Eddie Lake'
        ],
        [
            'header' => 'Table of contents',
            'section_type' => 'Table of contents',
            'status' => 'Done',
            'target' => 29,
            'limit' => 24,
            'reviewer' => 'Eddie Lake'
        ],
        [
            'header' => 'Executive summary',
            'section_type' => 'Narrative',
            'status' => 'Done',
            'target' => 10,
            'limit' => 13,
            'reviewer' => 'Eddie Lake'
        ],
        [
            'header' => 'Technical approach',
            'section_type' => 'Narrative',
            'status' => 'Done',
            'target' => 27,
            'limit' => 23,
            'reviewer' => 'Jamik Tashpulatov'
        ]
    ];
} else {
    while($row = $table_result->fetch_assoc()) {
        $table_data[] = $row;
    }
}

// Format numbers for display
function formatNumber($number) {
    if($number >= 1000) {
        return number_format($number);
    }
    return $number;
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="admin-styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="dark-theme">
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">Admin Panel</div>
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
                <li><a href="insert-product.php">Insert product</a></li>
                <li><a href="view-products.php">View products</a></li>
              </ul>
            </li>
            
            <li class="has-submenu">
              <a href="#" class="submenu-toggle">
                <i class="fa-solid fa-user"></i>
                <span>Users</span>
                <i class="fa-solid fa-chevron-down submenu-icon"></i>
              </a>
              <ul class="submenu">
                <li><a href="insert-user.php">Insert user</a></li>
                <li><a href="view-users.php">View Users</a></li>
                <li><a href="edit-profile.php">Edit Profile</a></li>
              </ul>
            </li>
            
            <li class="has-submenu">
              <a href="#" class="submenu-toggle">
                <i class="fa-solid fa-pencil"></i>
                <span>Product Categories</span>
                <i class="fa-solid fa-chevron-down submenu-icon"></i>
              </a>
              <ul class="submenu">
                <li><a href="insert-category.php">Insert Category</a></li>
                <li><a href="view-categories.php">View Categories</a></li>
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
          <li>
            <a href="settings.php">
              <i class="fa-solid fa-gear"></i>
              <span>Settings</span>
            </a>
          </li>
          <li>
            <a href="help.php">
              <i class="fa-solid fa-circle-question"></i>
              <span>Get Help</span>
            </a>
          </li>
          <li>
            <a href="search.php">
              <i class="fa-solid fa-search"></i>
              <span>Search</span>
            </a>
          </li>
          <li class="user-profile">
            <a href="profile.php">
              <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-pScSjBqFkTZqiXLxSiwS3sa5IDaHqY.png" alt="User Profile" class="profile-image">
              <div class="user-info">
                <span class="user-name"><?php echo $_SESSION['admin_name'] ?? 'Admin User'; ?></span>
                <span class="user-email"><?php echo $_SESSION['admin_email'] ?? 'm@example.com'; ?></span>
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
        <!-- Metric Cards -->
        <div class="metric-cards">
          <div class="card metric-card">
            <div class="card-header">
              <h3 class="card-title">Total Revenue</h3>
              <span class="badge <?php echo $revenue_percentage >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo ($revenue_percentage >= 0 ? '+' : '') . number_format($revenue_percentage, 1) . '%'; ?>
              </span>
            </div>
            <div class="card-content">
              <div class="metric-value"><?php echo formatCurrency($total_revenue); ?></div>
              <p class="metric-description"><?php echo $revenue_percentage >= 0 ? 'Trending up' : 'Trending down'; ?> this month</p>
              <p class="metric-description">Revenue for the last month</p>
            </div>
          </div>

          <div class="card metric-card">
            <div class="card-header">
              <h3 class="card-title">New Customers</h3>
              <span class="badge <?php echo $customers_percentage >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo ($customers_percentage >= 0 ? '+' : '') . number_format($customers_percentage, 1) . '%'; ?>
              </span>
            </div>
            <div class="card-content">
              <div class="metric-value"><?php echo formatNumber($total_customers); ?></div>
              <p class="metric-description"><?php echo $customers_percentage >= 0 ? 'Up' : 'Down'; ?> <?php echo abs(number_format($customers_percentage, 1)); ?>% this period</p>
              <p class="metric-description"><?php echo $customers_percentage >= 0 ? 'Acquisition on track' : 'Acquisition needs attention'; ?></p>
            </div>
          </div>

          <div class="card metric-card">
            <div class="card-header">
              <h3 class="card-title">Active Accounts</h3>
              <span class="badge <?php echo $accounts_percentage >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo ($accounts_percentage >= 0 ? '+' : '') . number_format($accounts_percentage, 1) . '%'; ?>
              </span>
            </div>
            <div class="card-content">
              <div class="metric-value"><?php echo formatNumber($active_accounts); ?></div>
              <p class="metric-description"><?php echo $accounts_percentage >= 0 ? 'Strong user retention' : 'Retention needs work'; ?></p>
              <p class="metric-description"><?php echo $accounts_percentage >= 0 ? 'Engagement exceeds targets' : 'Engagement below targets'; ?></p>
            </div>
          </div>

          <div class="card metric-card">
            <div class="card-header">
              <h3 class="card-title">Growth Rate</h3>
              <span class="badge <?php echo $growth_rate >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo ($growth_rate >= 0 ? '+' : '') . number_format($growth_rate, 1) . '%'; ?>
              </span>
            </div>
            <div class="card-content">
              <div class="metric-value"><?php echo number_format($growth_rate, 1); ?>%</div>
              <p class="metric-description"><?php echo $growth_rate >= 3 ? 'Strong performance' : ($growth_rate >= 0 ? 'Steady performance' : 'Needs improvement'); ?></p>
              <p class="metric-description"><?php echo $growth_rate >= 3 ? 'Exceeds growth projections' : ($growth_rate >= 0 ? 'Meets growth projections' : 'Below growth projections'); ?></p>
            </div>
          </div>
        </div>

        <!-- Chart Section -->
        <div class="chart-section">
          <div class="card">
            <div class="card-header chart-header">
              <div>
                <h3 class="card-title">Total Visitors</h3>
                <p class="card-description">Total for the last 6 months</p>
              </div>
              <div class="time-filters">
                <button class="btn btn-outline active" data-period="6">Last 6 months</button>
                <button class="btn btn-outline" data-period="3">Last 3 months</button>
                <button class="btn btn-outline" data-period="1">Last month</button>
              </div>
            </div>
            <div class="card-content">
              <div class="chart-container">
                <canvas id="visitorChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Table Section -->
        <div class="table-section">
          <div class="card">
            <div class="card-header table-header">
              <div class="tabs">
                <button class="tab active" data-tab="outline">Outline</button>
                <button class="tab" data-tab="past-performance">
                  Past Performance
                  <span class="tab-badge">3</span>
                </button>
                <button class="tab" data-tab="key-personnel">
                  Key Personnel
                  <span class="tab-badge">2</span>
                </button>
                <button class="tab" data-tab="focus-documents">Focus Documents</button>
              </div>
              <div class="table-actions">
                <button class="btn btn-outline">
                  <i class="fa-solid fa-columns"></i>
                  Customize Columns
                </button>
                <button class="btn btn-outline">
                  <i class="fa-solid fa-plus"></i>
                  Add Section
                </button>
              </div>
            </div>
            <div class="card-content">
              <div class="tab-content active" id="outline">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="checkbox-column">
                        <input type="checkbox" id="select-all">
                      </th>
                      <th>Header</th>
                      <th>Section Type</th>
                      <th>Status</th>
                      <th>Target</th>
                      <th>Limit</th>
                      <th>Reviewer</th>
                      <th class="actions-column"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach($table_data as $row): ?>
                    <tr>
                      <td class="checkbox-column">
                        <input type="checkbox" class="row-checkbox">
                      </td>
                      <td><?php echo $row['header']; ?></td>
                      <td><span class="table-badge"><?php echo $row['section_type']; ?></span></td>
                      <td>
                        <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                          <?php echo $row['status']; ?>
                        </span>
                      </td>
                      <td><?php echo $row['target']; ?></td>
                      <td><?php echo $row['limit']; ?></td>
                      <td><?php echo $row['reviewer']; ?></td>
                      <td class="actions-column">
                        <button class="btn-icon">
                          <i class="fa-solid fa-ellipsis-h"></i>
                        </button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="tab-content" id="past-performance">
                <div class="empty-state">Past performance data will appear here</div>
              </div>
              <div class="tab-content" id="key-personnel">
                <div class="empty-state">Key personnel data will appear here</div>
              </div>
              <div class="tab-content" id="focus-documents">
                <div class="empty-state">Focus documents will appear here</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize chart with PHP data
      const ctx = document.getElementById('visitorChart').getContext('2d');
      const months = <?php echo json_encode($months); ?>;
      const visitorData = <?php echo json_encode($visitor_data); ?>;
      
      const chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: months,
          datasets: [{
            label: 'Visitors',
            data: visitorData,
            backgroundColor: 'rgba(74, 222, 128, 0.2)',
            borderColor: 'rgba(74, 222, 128, 1)',
            borderWidth: 2,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
      
      // Time filter buttons
      const timeFilters = document.querySelectorAll('.time-filters .btn');
      timeFilters.forEach(button => {
        button.addEventListener('click', function() {
          // Remove active class from all buttons
          timeFilters.forEach(btn => btn.classList.remove('active'));
          // Add active class to clicked button
          this.classList.add('active');
          
          // Update chart data based on selected period
          const period = parseInt(this.getAttribute('data-period'));
          
          // In a real application, you would fetch new data from the server
          // For this example, we'll just update the existing chart with random data
          const newData = [];
          const newLabels = [];
          
          for(let i = period - 1; i >= 0; i--) {
            const monthIndex = new Date().getMonth() - i;
            const year = new Date().getFullYear();
            const date = new Date(year, monthIndex, 1);
            newLabels.push(date.toLocaleString('default', { month: 'long' }));
            newData.push(Math.floor(Math.random() * 20000) + 10000);
          }
          
          chart.data.labels = newLabels;
          chart.data.datasets[0].data = newData;
          chart.update();
        });
      });
      
      // Tab switching
      const tabs = document.querySelectorAll('.tab');
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Remove active class from all tabs
          tabs.forEach(t => t.classList.remove('active'));
          // Add active class to clicked tab
          this.classList.add('active');
          
          // Hide all tab content
          const tabContents = document.querySelectorAll('.tab-content');
          tabContents.forEach(content => content.classList.remove('active'));
          
          // Show the corresponding tab content
          const tabId = this.getAttribute('data-tab');
          document.getElementById(tabId).classList.add('active');
        });
      });
      
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
      
      // Select all checkbox
      const selectAll = document.getElementById('select-all');
      if (selectAll) {
        selectAll.addEventListener('change', function() {
          const checkboxes = document.querySelectorAll('.row-checkbox');
          checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
          });
        });
      }
    });
  </script>
</body>
</html>