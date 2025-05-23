<?php
session_start();

// Check if payment info exists
if (!isset($_SESSION['payment_info'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$conn = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get payment info
$payment_info = $_SESSION['payment_info'];
$order_id = $payment_info['order_id'];
$invoice_no = $payment_info['invoice_no'];
$total_amount = $payment_info['total_amount'];
$discount_applied = $payment_info['discount_applied'];
$final_total = $payment_info['final_total'];
$payment_method = $payment_info['payment_method'];
$customer_name = $payment_info['customer_name'];
$customer_email = $payment_info['customer_email'];
$customer_address = $payment_info['customer_address'];
$order_date = $payment_info['order_date'];

// Get order items
$items_query = "SELECT po.*, p.product_title, p.product_price 
               FROM pending_orders po
               JOIN products p ON po.product_id = p.product_id
               WHERE po.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = [];
while ($row = $items_result->fetch_assoc()) {
    $order_items[] = $row;
}
$stmt->close();

// Handle email receipt
if (isset($_POST['email_receipt'])) {
    // In a real application, you would use a library like PHPMailer to send emails
    // For this example, we'll just show a success message
    $email_sent = true;
}

// Handle PDF download
if (isset($_POST['download_pdf'])) {
    // In a real application, you would use a library like FPDF or TCPDF to generate PDFs
    // For this example, we'll just show a success message
    $pdf_downloaded = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - TechMarket</title>
    <link rel="stylesheet" href="style/styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .receipt-card {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .receipt-logo {
            display: flex;
            align-items: center;
        }
        .receipt-logo i {
            font-size: 2rem;
            margin-right: 10px;
            color: #4CAF50;
        }
        .receipt-logo span {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .receipt-logo .text-green {
            color: #4CAF50;
        }
        .receipt-status {
            background-color: #4CAF50;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .info-group h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #666;
            font-size: 1rem;
        }
        .info-group p {
            margin: 0;
            font-size: 1.1rem;
        }
        .receipt-items {
            margin-bottom: 30px;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .receipt-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .receipt-table tr:last-child td {
            border-bottom: none;
        }
        .receipt-summary {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 1.2rem;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
        .discount-row {
            color: #4CAF50;
        }
        .receipt-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .action-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease;
        }
        .action-btn i {
            margin-right: 10px;
        }
        .email-btn {
            background-color: #4CAF50;
            color: white;
        }
        .email-btn:hover {
            background-color: #388E3C;
        }
        .pdf-btn {
            background-color: #FF9800;
            color: white;
        }
        .pdf-btn:hover {
            background-color: #F57C00;
        }
        .back-btn {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        .back-btn:hover {
            background-color: #e9ecef;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .thank-you {
            text-align: center;
            margin-top: 30px;
            color: #4CAF50;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <div class="logo">
                    <i class="fas fa-shopping-cart"></i>
                    <span><span class="text-green">Tech</span>Market</span>
                </div>

                <!-- Search Bar -->
                <div class="searchBox">
                    <form action="search.php" method="post">
                        <input type="text" class="searchInput" placeholder="What are you looking for?" name="search">
                        <button type="submit" class="searchButton" name="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="29" height="29" viewBox="0 0 29 29" fill="none">
                                <g clip-path="url(#clip0_2_17)">
                                    <g filter="url(#filter0_d_2_17)">
                                        <path d="M23.7953 23.9182L19.0585 19.1814M19.0585 19.1814C19.8188 18.4211 20.4219 17.5185 20.8333 16.5251C21.2448 15.5318 21.4566 14.4671 21.4566 13.3919C21.4566 12.3167 21.2448 11.252 20.8333 10.2587C20.4219 9.2653 19.8188 8.36271 19.0585 7.60242C18.2982 6.84214 17.3956 6.23905 16.4022 5.82759C15.4089 5.41612 14.3442 5.20435 13.269 5.20435C12.1938 5.20435 11.1291 5.41612 10.1358 5.82759C9.1424 6.23905 8.23981 6.84214 7.47953 7.60242C5.94407 9.13789 5.08145 11.2204 5.08145 13.3919C5.08145 15.5634 5.94407 17.6459 7.47953 19.1814C9.01499 20.7168 11.0975 21.5794 13.269 21.5794C15.4405 21.5794 17.523 20.7168 19.0585 19.1814Z" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" shape-rendering="crispEdges"></path>
                                    </g>
                                </g>
                                <defs>
                                    <filter id="filter0_d_2_17" x="-0.418549" y="3.70435" width="29.7139" height="29.7139" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                        <feFlood flood-opacity="0" result="BackgroundImageFix"></feFlood>
                                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"></feColorMatrix>
                                        <feOffset dy="4"></feOffset>
                                        <feGaussianBlur stdDeviation="2"></feGaussianBlur>
                                        <feComposite in2="hardAlpha" operator="out"></feComposite>
                                        <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.25 0"></feColorMatrix>
                                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2_17"></feBlend>
                                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2_17" result="shape"></feBlend>
                                    </filter>
                                    <clipPath id="clip0_2_17">
                                        <rect width="28.0702" height="28.0702" fill="white" transform="translate(0.403503 0.526367)"></rect>
                                    </clipPath>
                                </defs>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Icons -->
                <div class="header-icons">
                    <a href="cart.php"><i class="fas fa-shopping-bag"></i> Product Cart</a>
                    <a href="<?php echo isset($_SESSION['customer_id']) ? 'user-profile.php' : 'auth.php'; ?>">
                        <i class="fas fa-user"></i>
                        <?php echo isset($_SESSION['customer_id']) ? 'My Profile' : 'User Account'; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Category Navigation -->
    <nav class="category-nav">
        <div class="container">
            <div class="nav-items">
                <div class="nav-item">
                    <a href="newproducts.php"><i class="fas fa-box"></i> New Products</a>
                </div>
                <div class="nav-item">
                    <a href="bestsales.php"><i class="fas fa-check-square"></i> Best Sales</a>
                    <span class="badge hot">Hot</span>
                </div>
                <div class="nav-item">
                    <a href="specialoffers.php"><i class="fas fa-sun"></i> Special Offers</a>
                </div>
                <div class="nav-item">
                    <a href="fidelity_offers.php"><i class="fas fa-gift"></i> Fidelity Offers</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="receipt-container">
            <h1>Payment Receipt</h1>
            
            <?php if (isset($email_sent)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Receipt has been sent to your email.
                </div>
            <?php endif; ?>
            
            <?php if (isset($pdf_downloaded)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Receipt has been downloaded as PDF.
                </div>
            <?php endif; ?>
            
            <div class="receipt-card">
                <div class="receipt-header">
                    <div class="receipt-logo">
                        <i class="fas fa-shopping-cart"></i>
                        <span><span class="text-green">Tech</span>Market</span>
                    </div>
                    <div class="receipt-status">Payment Successful</div>
                </div>
                
                <div class="receipt-info">
                    <div class="info-group">
                        <h3>Order Information</h3>
                        <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                        <p><strong>Invoice Number:</strong> <?php echo $invoice_no; ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order_date)); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo ucfirst($payment_method); ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Customer Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($customer_name); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer_email); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($customer_address); ?></p>
                    </div>
                </div>
                
                <div class="receipt-items">
                    <h3>Order Items</h3>
                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_title']); ?></td>
                                    <td><?php echo $item['qty']; ?></td>
                                    <td><?php echo number_format($item['product_price']); ?> D.A</td>
                                    <td><?php echo number_format($item['product_price'] * $item['qty']); ?> D.A</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="receipt-summary">
                    <div class="summary-row">
                        <div>Subtotal:</div>
                        <div><?php echo number_format($total_amount); ?> D.A</div>
                    </div>
                    <?php if ($discount_applied > 0): ?>
                        <div class="summary-row discount-row">
                            <div>Fidelity Discount:</div>
                            <div>-<?php echo number_format($discount_applied); ?> D.A</div>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row total">
                        <div>Total:</div>
                        <div><?php echo number_format($final_total); ?> D.A</div>
                    </div>
                </div>
                
                <div class="receipt-actions">
                    <form method="post">
                        <button type="submit" name="email_receipt" class="action-btn email-btn">
                            <i class="fas fa-envelope"></i> Email Receipt
                        </button>
                    </form>
                    <form method="post">
                        <button type="submit" name="download_pdf" class="action-btn pdf-btn">
                            <i class="fas fa-file-pdf"></i> Download PDF
                        </button>
                    </form>
                    <a href="index.php" class="action-btn back-btn">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
                
                <div class="thank-you">
                    <p>Thank you for shopping with TechMarket!</p>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 TechMarket. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php 
// Clear payment info after displaying receipt
// Uncomment this line in production to prevent receipt from being viewed multiple times
// unset($_SESSION['payment_info']);
mysqli_close($conn); 
?>
