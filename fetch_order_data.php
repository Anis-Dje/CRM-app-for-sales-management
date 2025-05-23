<?php
session_start();

// Database connection
$conn = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die(json_encode(['error' => 'Database connection failed']));
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the selected time period from the request
$period = isset($_GET['period']) ? $_GET['period'] : 'last_3_months';

$start_date = '';
$end_date = date('Y-m-d'); // Today as the end date
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

// Function to generate labels and date ranges based on period
function generateLabelsAndRanges($start_date, $end_date, $period) {
    $labels = [];
    $ranges = [];

    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day'); // Include the end date

    if ($period === 'last_3_months') {
        // Group by month
        $interval = new DateInterval('P1M'); // 1 month interval
        $periods = new DatePeriod($start, $interval, $end);
        foreach ($periods as $dt) {
            $monthStart = $dt->format('Y-m-01');
            $monthEnd = $dt->format('Y-m-t');
            $labels[] = $dt->format('M Y'); // e.g., "Feb 2025"
            $ranges[] = [
                'start' => $monthStart,
                'end' => $monthEnd
            ];
        }
    } elseif ($period === 'last_30_days') {
        // Group by week
        $interval = new DateInterval('P7D'); // 1 week interval
        $periods = new DatePeriod($start, $interval, $end);
        $weekNum = 1;
        foreach ($periods as $dt) {
            $weekStart = $dt->format('Y-m-d');
            $weekEndDt = clone $dt;
            $weekEndDt->modify('+6 days');
            if ($weekEndDt > $end) {
                $weekEndDt = $end;
            }
            $weekEnd = $weekEndDt->format('Y-m-d');
            $labels[] = "Week $weekNum";
            $ranges[] = [
                'start' => $weekStart,
                'end' => $weekEnd
            ];
            $weekNum++;
        }
    } else { // last_7_days
        // Daily
        $interval = new DateInterval('P1D'); // 1 day interval
        $periods = new DatePeriod($start, $interval, $end);
        foreach ($periods as $dt) {
            $date = $dt->format('Y-m-d');
            $labels[] = $dt->format('M d'); // e.g., "Apr 24"
            $ranges[] = [
                'start' => $date,
                'end' => $date
            ];
        }
    }

    // Ensure labels are not empty
    if (empty($labels)) {
        error_log("No labels generated for period: $period, start_date: $start_date, end_date: $end_date");
        $labels = [$period === 'last_3_months' ? 'No Data' : ($period === 'last_30_days' ? 'No Data' : 'No Data')];
        $ranges = [['start' => $start_date, 'end' => $end_date]];
    }

    return [$labels, $ranges];
}

// Generate labels and date ranges
list($labels, $ranges) = generateLabelsAndRanges($start_date, $end_date, $period);

// Initialize data arrays
$successful_data = array_fill(0, count($labels), 0);
$pending_data = array_fill(0, count($labels), 0);
$cart_data = array_fill(0, count($labels), 0);

// Fetch successful orders (completed status)
$query = "SELECT DATE_FORMAT(order_date, '%Y-%m-%d') as date, COUNT(*) as count 
          FROM customer_orders 
          WHERE order_status = 'completed' 
          AND order_date >= '$start_date' 
          AND order_date <= '$end_date'
          GROUP BY DATE(order_date) 
          ORDER BY order_date ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    error_log("Query failed (successful orders): " . mysqli_error($conn));
    die(json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]));
}

while ($row = mysqli_fetch_assoc($result)) {
    $date = $row['date'];
    $count = (int)$row['count'];
    foreach ($ranges as $index => $range) {
        if ($date >= $range['start'] && $date <= $range['end']) {
            $successful_data[$index] += $count;
            break;
        }
    }
}

// Fetch pending orders
$query = "SELECT DATE_FORMAT(order_date, '%Y-%m-%d') as date, COUNT(*) as count 
          FROM customer_orders 
          WHERE order_status = 'pending' 
          AND order_date >= '$start_date' 
          AND order_date <= '$end_date'
          GROUP BY DATE(order_date) 
          ORDER BY order_date ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    error_log("Query failed (pending orders): " . mysqli_error($conn));
    die(json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]));
}

while ($row = mysqli_fetch_assoc($result)) {
    $date = $row['date'];
    $count = (int)$row['count'];
    foreach ($ranges as $index => $range) {
        if ($date >= $range['start'] && $date <= $range['end']) {
            $pending_data[$index] += $count;
            break;
        }
    }
}

// Fetch cart items (not confirmed orders)
$query = "SELECT DATE_FORMAT(added_date, '%Y-%m-%d') as date, COUNT(*) as count 
          FROM cart 
          WHERE status = 'WAITING' 
          AND added_date >= '$start_date' 
          AND added_date <= '$end_date'
          GROUP BY DATE(added_date) 
          ORDER BY added_date ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    error_log("Query failed (cart items): " . mysqli_error($conn));
    die(json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]));
}

while ($row = mysqli_fetch_assoc($result)) {
    $date = $row['date'];
    $count = (int)$row['count'];
    foreach ($ranges as $index => $range) {
        if ($date >= $range['start'] && $date <= $range['end']) {
            $cart_data[$index] += $count;
            break;
        }
    }
}

// Check if all datasets are empty (no real data)
if (array_sum($successful_data) == 0 && array_sum($pending_data) == 0 && array_sum($cart_data) == 0) {
    // Use mock data
    if ($period === 'last_3_months') {
        $labels = ['Feb 2025', 'Mar 2025', 'Apr 2025'];
        $successful_data = [50, 70, 100];
        $pending_data = [30, 40, 60];
        $cart_data = [200, 180, 150];
    } elseif ($period === 'last_30_days') {
        $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
        $successful_data = [100, 80, 120, 90, 110];
        $pending_data = [60, 50, 70, 55, 65];
        $cart_data = [150, 140, 130, 120, 110];
    } else { // last_7_days
        $labels = ['Apr 24', 'Apr 25', 'Apr 26', 'Apr 27', 'Apr 28', 'Apr 29', 'Apr 30'];
        $successful_data = [15, 20, 10, 25, 18, 22, 30];
        $pending_data = [10, 12, 8, 15, 11, 14, 20];
        $cart_data = [135, 130, 125, 120, 115, 110, 105];
    }
}

mysqli_close($conn);

// Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'datasets' => [
        [
            'label' => 'Successful Orders',
            'data' => $successful_data,
            'borderColor' => '#1ed760', // Green
            'backgroundColor' => 'rgba(30, 215, 96, 0.2)',
        ],
        [
            'label' => 'Pending Orders',
            'data' => $pending_data,
            'borderColor' => '#ff9500', // Orange
            'backgroundColor' => 'rgba(255, 149, 0, 0.2)',
        ],
        [
            'label' => 'Not Confirmed (Cart Items)',
            'data' => $cart_data,
            'borderColor' => '#00c4ff', // Blue
            'backgroundColor' => 'rgba(0, 196, 255, 0.2)',
        ]
    ]
], JSON_PRETTY_PRINT);
?>
