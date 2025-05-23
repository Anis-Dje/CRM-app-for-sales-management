<?php
session_start();

// Database connection
$conn = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: auth.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Fetch customer points
$points_query = "SELECT customer_points FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($points_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer_points = $result->fetch_assoc()['customer_points'] ?? 0;
$stmt->close();

// Process gift redemption
if (isset($_POST['redeem_gift'])) {
    $gift_id = $_POST['gift_id'];
    
    // Get gift details
    $gift_query = "SELECT * FROM fidelity_gifts WHERE id = ?";
    $stmt = $conn->prepare($gift_query);
    $stmt->bind_param("i", $gift_id);
    $stmt->execute();
    $gift_result = $stmt->get_result();
    $gift = $gift_result->fetch_assoc();
    $stmt->close();
    
    if (!$gift) {
        $error = "Gift not found.";
    } else if ($customer_points < $gift['required_points']) {
        $error = "You don't have enough points to redeem this gift.";
    } else {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Deduct points from customer
            $update_points = "UPDATE customers SET customer_points = customer_points - ? WHERE customer_id = ?";
            $stmt = $conn->prepare($update_points);
            $stmt->bind_param("ii", $gift['required_points'], $customer_id);
            $stmt->execute();
            $stmt->close();
            
            if ($gift['type'] == 'discount') {
                // Add discount to customer
                $update_discount = "UPDATE customers SET fidelity_discount = fidelity_discount + ? WHERE customer_id = ?";
                $stmt = $conn->prepare($update_discount);
                $stmt->bind_param("ii", $gift['value'], $customer_id);
                $stmt->execute();
                $stmt->close();
                
                $success = "Discount of " . $gift['value'] . " D.A has been added to your account!";
            } else if ($gift['type'] == 'accessory') {
                // Map gift_id to product_id (assuming product_id = gift_id + 68 based on data)
                $product_id = $gift['id'] + 68;
                
                // Verify product exists
                $product_query = "SELECT product_id FROM products WHERE product_id = ?";
                $stmt = $conn->prepare($product_query);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $product_result = $stmt->get_result();
                
                if ($product_result->num_rows > 0) {
                    // Add to cart as free item
                    $check_cart = "SELECT * FROM cart WHERE customer_id = ? AND p_id = ?";
                    $stmt = $conn->prepare($check_cart);
                    $stmt->bind_param("ii", $customer_id, $product_id);
                    $stmt->execute();
                    $cart_result = $stmt->get_result();
                    
                    if ($cart_result->num_rows > 0) {
                        // Update existing cart item
                        $update_cart = "UPDATE cart SET qty = qty + 1 WHERE customer_id = ? AND p_id = ?";
                        $stmt = $conn->prepare($update_cart);
                        $stmt->bind_param("ii", $customer_id, $product_id);
                        $stmt->execute();
                    } else {
                        // Add new cart item
                        $insert_cart = "INSERT INTO cart (customer_id, p_id, qty, p_price, status) VALUES (?, ?, 1, 0, 'FIDELITY')";
                        $stmt = $conn->prepare($insert_cart);
                        $stmt->bind_param("ii", $customer_id, $product_id);
                        $stmt->execute();
                    }
                    
                    $success = "Free " . $gift['name'] . " has been added to your cart!";
                } else {
                    throw new Exception("Product not found for gift ID " . $gift['id']);
                }
                $stmt->close();
            }
            
            // Log the redemption
            $log_redemption = "INSERT INTO fidelity_redemptions (customer_id, gift_id, redeemed_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($log_redemption);
            $stmt->bind_param("ii", $customer_id, $gift['id']);
            $stmt->execute();
            $stmt->close();
            
            mysqli_commit($conn);
            
            // Refresh customer points
            $points_query = "SELECT customer_points FROM customers WHERE customer_id = ?";
            $stmt = $conn->prepare($points_query);
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $customer_points = $result->fetch_assoc()['customer_points'] ?? 0;
            $stmt->close();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch available gifts
$gifts_query = "SELECT * FROM fidelity_gifts ORDER BY required_points ASC";
$gifts_result = mysqli_query($conn, $gifts_query);
$gifts = [];
while ($row = mysqli_fetch_assoc($gifts_result)) {
    $gifts[] = $row;
}

// Fetch customer's discount
$discount_query = "SELECT fidelity_discount FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($discount_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$discount_result = $stmt->get_result();
$customer_discount = $discount_result->fetch_assoc()['fidelity_discount'] ?? 0;
$stmt->close();

// Fetch redemption history
$history_query = "SELECT fr.*, fg.name, fg.type, fg.value 
                 FROM fidelity_redemptions fr 
                 JOIN fidelity_gifts fg ON fr.gift_id = fg.id 
                 WHERE fr.customer_id = ? 
                 ORDER BY fr.redeemed_at DESC 
                 LIMIT 10";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$history_result = $stmt->get_result();
$redemption_history = [];
while ($row = mysqli_fetch_assoc($history_result)) {
    $redemption_history[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fidelity Offers - TechMarket</title>
    <link rel="stylesheet" href="style/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fidelity-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .points-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .points-info {
            display: flex;
            align-items: center;
        }
        .points-icon {
            font-size: 2.5rem;
            color: #4CAF50;
            margin-right: 15px;
        }
        .points-text h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        .points-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #4CAF50;
        }
        .discount-badge {
            background-color: #FF9800;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .gifts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .gift-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .gift-card:hover {
            transform: translateY(-5px);
        }
        .gift-header {
            padding: 15px;
            background-color: #4CAF50;
            color: white;
            position: relative;
        }
        .gift-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        .gift-body {
            padding: 15px;
        }
        .gift-points {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .gift-points i {
            color: #4CAF50;
            margin-right: 5px;
        }
        .gift-description {
            margin-bottom: 15px;
            color: #666;
        }
        .gift-action form {
            margin: 0;
        }
        .redeem-btn {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .redeem-btn:hover {
            background-color: #388E3C;
        }
        .redeem-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .section-title {
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .history-table th, .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .history-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .history-table tr:hover {
            background-color: #f5f5f5;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .discount-type {
            background-color: #FF9800;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.8rem;
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .accessory-type {
            background-color: #2196F3;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.8rem;
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .min-price-warning {
            font-size: 0.8rem;
            color: #f44336;
            margin-top: 5px;
        }
        .gift-filters {
            display: flex;
            margin-bottom: 20px;
            gap: 10px;
        }
        .filter-btn {
            padding: 8px 15px;
            background-color: #f1f1f1;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .filter-btn.active {
            background-color: #4CAF50;
            color: white;
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
                    <a href="index.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span><span class="text-green">Tech</span>Market</span>
                    </a>
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
        <div class="fidelity-container">
            <h1>Fidelity Rewards Program</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="points-card">
                <div class="points-info">
                    <div class="points-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="points-text">
                        <h2>Your Fidelity Points</h2>
                        <p>Earn points with every purchase and redeem them for rewards!</p>
                    </div>
                </div>
                <div class="points-value"><?php echo number_format($customer_points); ?></div>
                <?php if ($customer_discount > 0): ?>
                    <div class="discount-badge">
                        Available Discount: <?php echo number_format($customer_discount); ?> D.A
                    </div>
                <?php endif; ?>
            </div>
            
            <h2 class="section-title">Available Rewards</h2>
            
            <div class="gift-filters">
                <button class="filter-btn active" data-filter="all">All Rewards</button>
                <button class="filter-btn" data-filter="discount">Discounts</button>
                <button class="filter-btn" data-filter="accessory">Accessories</button>
            </div>
            
            <div class="gifts-grid">
                <?php foreach ($gifts as $gift): ?>
                    <div class="gift-card" data-type="<?php echo $gift['type']; ?>">
                        <div class="gift-header">
                            <h3><?php echo htmlspecialchars($gift['name']); ?></h3>
                            <span class="<?php echo $gift['type'] == 'discount' ? 'discount-type' : 'accessory-type'; ?>">
                                <?php echo ucfirst($gift['type']); ?>
                            </span>
                        </div>
                        <div class="gift-body">
                            <div class="gift-points">
                                <i class="fas fa-star"></i>
                                <span><strong><?php echo number_format($gift['required_points']); ?> points</strong> required</span>
                            </div>
                            <div class="gift-description">
                                <?php echo htmlspecialchars($gift['description']); ?>
                                <?php if ($gift['type'] == 'discount'): ?>
                                    <br><strong>Value: <?php echo $gift['type'] == 'discount' && strpos($gift['name'], '%') !== false ? $gift['name'] : number_format($gift['value']) . ' D.A'; ?></strong>
                                    <?php if (!empty($gift['min_product_price'])): ?>
                                        <div class="min-price-warning">
                                            <i class="fas fa-exclamation-circle"></i> 
                                            Not applicable for products under <?php echo number_format($gift['min_product_price']); ?> D.A
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="gift-action">
                                <form method="post">
                                    <input type="hidden" name="gift_id" value="<?php echo $gift['id']; ?>">
                                    <button type="submit" name="redeem_gift" class="redeem-btn" <?php echo $customer_points < $gift['required_points'] ? 'disabled' : ''; ?>>
                                        <?php echo $customer_points < $gift['required_points'] ? 'Not Enough Points' : 'Redeem Now'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <h2 class="section-title">Redemption History</h2>
            
            <?php if (empty($redemption_history)): ?>
                <p>You haven't redeemed any rewards yet.</p>
            <?php else: ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reward</th>
                            <th>Type</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($redemption_history as $history): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($history['redeemed_at'])); ?></td>
                                <td><?php echo htmlspecialchars($history['name']); ?></td>
                                <td>
                                    <span class="<?php echo $history['type'] == 'discount' ? 'discount-type' : 'accessory-type'; ?>">
                                        <?php echo ucfirst($history['type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($history['type'] == 'discount'): ?>
                                        <?php echo strpos($history['name'], '%') !== false ? $history['name'] : number_format($history['value']) . ' D.A'; ?>
                                    <?php else: ?>
                                        1 item
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <h2 class="section-title">How to Earn Points</h2>
            
            <div style="margin-bottom: 30px;">
                <p>Earn fidelity points with every purchase you make:</p>
                <ul>
                    <li>For every 100 D.A spent, you earn 5 points</li>
                    <li>Complete your profile to earn 50 bonus points</li>
                    <li>Write product reviews to earn 10 points per review</li>
                    <li>Refer a friend and earn 100 points when they make their first purchase</li>
                </ul>
                <p>The more you shop, the more rewards you can unlock!</p>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 TechMarket. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Filter gifts by type
        const filterButtons = document.querySelectorAll('.filter-btn');
        const giftCards = document.querySelectorAll('.gift-card');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                const filter = button.getAttribute('data-filter');
                giftCards.forEach(card => {
                    if (filter === 'all' || card.getAttribute('data-type') === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>