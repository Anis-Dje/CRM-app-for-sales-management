<?php
session_start();

// Database connection
$conn = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Get the selected time period from the request
$period = isset($_GET['period']) ? $_GET['period'] : 'last_3_months';

$start_date = '';
switch ($period) {
    case 'last_7_days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'last_30_days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'last_3_months':
    default:
        $start_date = date('Y-m-d', strtotime('-3 months'));
        break;
}

// Fetch successful orders (completed status)
$query = "SELECT DATE_FORMAT(order_date, '%Y-%m-%d') as date, COUNT(*) as order_count 
          FROM customer_orders 
          WHERE order_status = 'completed' 
          AND order_date >= '$start_date' 
          GROUP BY DATE(order_date) 
          ORDER BY order_date ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    die(json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]));
}

$labels = [];
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $labels[] = $row['date'];
    $data[] = $row['order_count'];
}

// If no data in the database, use mock data for demonstration
if (empty($labels)) {
    if ($period === 'last_3_months') {
        $labels = ['2025-02-01', '2025-03-01', '2025-04-01'];
        $data = [50, 70, 100]; // Mock successful orders
    } elseif ($period === 'last_30_days') {
        $labels = ['2025-04-01', '2025-04-15', '2025-04-30'];
        $data = [100, 80, 120]; // Mock successful orders
    } else { // last_7_days
        $labels = ['2025-04-24', '2025-04-25', '2025-04-26', '2025-04-27', '2025-04-28', '2025-04-29', '2025-04-30'];
        $data = [15, 20, 10, 25, 18, 22, 30]; // Mock successful orders
    }
}

mysqli_close($conn);

// Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'data' => $data
]);
?>
