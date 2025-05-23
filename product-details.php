<?php
include 'C:\xampp\htdocs\PPD\php\config.php';
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get product ID or URL from query string
$productId = isset($_GET['id']) ? $_GET['id'] : '';
$productUrl = isset($_GET['url']) ? $_GET['url'] : '';

// If no product ID or URL is provided, redirect to homepage
if (empty($productId) && empty($productUrl)) {
    header("Location: index.php");
    exit;
}

// Build query based on what we have (ID or URL)
if (!empty($productId) && is_numeric($productId)) {
    // Query by ID
    $query = "SELECT p.*, pc.p_cat_title, m.manufacturer_title 
            FROM products p
            LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
            LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
            WHERE p.product_id = '$productId'";
} else {
    // Query by URL
    $query = "SELECT p.*, pc.p_cat_title, m.manufacturer_title 
            FROM products p
            LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
            LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
            WHERE p.product_url = '" . mysqli_real_escape_string($conn, $productUrl) . "'"; // Sanitize input to prevent SQL injection
}

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $product = $result->fetch_assoc();
    
    // Calculate discount percentage if sale price exists
    $discountPercentage = 0;
    if ($product['product_psp_price'] > 0 && $product['product_price'] > 0) {
        $discountPercentage = round((1 - $product['product_psp_price'] / $product['product_price']) * 100);
    }
    
    // Format prices for display
    $currentPrice = $product['product_psp_price'] > 0 ? $product['product_psp_price'] : $product['product_price'];
    $currentPriceFormatted = number_format($currentPrice, 0) . ' D.A';
    $originalPriceFormatted = number_format($product['product_price'], 0) . ' D.A';
    
    // Parse features into array
    $features = explode("|", $product['product_features']);
    $featuresArray = [];
    foreach ($features as $feature) {
        if (trim($feature)) {
            $featuresArray[] = trim($feature);
        }
    }
    
    // Get related products (using related_products column)
    $relatedProducts = [];
    if (!empty($product['related_products'])) {
        $relatedIds = explode(',', $product['related_products']);
        $relatedIds = array_map('intval', $relatedIds); // Sanitize IDs
        $relatedIdsString = implode(',', $relatedIds);
        
        // Updated query to not select product_img1 directly
        $relatedProductsQuery = "SELECT p.product_id, p.product_title, p.product_url, 
                            p.product_price, p.product_psp_price, m.manufacturer_title 
                        FROM products p
                        LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
                        WHERE p.product_id IN ($relatedIdsString)
                        AND p.status = 'product'
                        LIMIT 4";
        
        $relatedResult = $conn->query($relatedProductsQuery);
        
        if ($relatedResult && $relatedResult->num_rows > 0) {
            while ($relatedProduct = $relatedResult->fetch_assoc()) {
                // Format prices for related products
                $relatedProduct['price'] = $relatedProduct['product_psp_price'] > 0 ? 
                    number_format($relatedProduct['product_psp_price'], 0) . ' D.A' : 
                    number_format($relatedProduct['product_price'], 0) . ' D.A';
                
                $relatedProduct['original_price'] = number_format($relatedProduct['product_price'], 0) . ' D.A';
                
                // Get product image from product_images table
                $rel_product_id = $relatedProduct['product_id'];
                $rel_image_query = "SELECT image_path FROM product_images WHERE product_id = $rel_product_id ORDER BY is_primary DESC LIMIT 1";
                $rel_img_result = $conn->query($rel_image_query);
                if ($rel_img_result && $rel_img_result->num_rows > 0) {
                    $rel_img_row = $rel_img_result->fetch_assoc();
                    $rel_product_img = $rel_img_row['image_path'];
                    // Check if the path already includes 'product_images/'
                    if (strpos($rel_product_img, 'product_images/') === false) {
                        $relatedProduct['product_img1'] = 'product_images/' . $rel_product_img;
                    } else {
                        $relatedProduct['product_img1'] = $rel_product_img;
                    }
                } else {
                    $relatedProduct['product_img1'] = 'https://via.placeholder.com/150x200';
                }
                
                $relatedProducts[] = $relatedProduct;
            }
        }
    }
} else {
    // Product not found, redirect to homepage
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $product['product_title']; ?> - TechMarket</title>
<link rel="stylesheet" href="styles.css">
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            </div>

            <!-- Search Bar -->
            <div class="searchBox">
                <input class="searchInput" type="text" name="" placeholder="Search Something">
                <button class="searchButton">
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

<!-- Breadcrumb -->
<div class="container breadcrumb-container">
    <div class="breadcrumb" id="breadcrumb">
        <a href="index.php">Home</a> > 
        <a href="category.php?cat=<?php echo $product['p_cat_id']; ?>"><?php echo $product['p_cat_title']; ?></a> > 
        <span><?php echo $product['product_title']; ?></span>
    </div>
</div>

<!-- Product Details -->
<main class="product-details-container">
    <div class="container">
        <div class="product-details">
            <!-- Product Gallery -->
            <div class="product-gallery">
                <div class="main-image">
                    <?php
                    // Get primary product image
                    $primary_image_query = "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC LIMIT 1";
                    $primary_img_stmt = mysqli_prepare($conn, $primary_image_query);
                    mysqli_stmt_bind_param($primary_img_stmt, "i", $productId);
                    mysqli_stmt_execute($primary_img_stmt);
                    $primary_img_result = mysqli_stmt_get_result($primary_img_stmt);
                    
                    if ($primary_img_result && mysqli_num_rows($primary_img_result) > 0) {
                        $primary_img = mysqli_fetch_assoc($primary_img_result);
                        $main_image = $primary_img['image_path'];
                        // Check if the path already includes 'product_images/'
                        if (strpos($main_image, 'product_images/') === false) {
                            $main_image = 'product_images/' . $main_image;
                        }
                    } else {
                        $main_image = 'https://via.placeholder.com/400x400';
                    }
                    ?>
                    <img src="<?php echo $main_image; ?>" alt="<?php echo $product['product_title']; ?>" id="main-product-image">
                </div>
                <div class="thumbnail-container" id="thumbnail-container">
                    <?php
                    // Get all product images
                    $images_query = "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, image_id";
                    $images_stmt = mysqli_prepare($conn, $images_query);
                    mysqli_stmt_bind_param($images_stmt, "i", $productId);
                    mysqli_stmt_execute($images_stmt);
                    $images_result = mysqli_stmt_get_result($images_stmt);
                    
                    $first = true;
                    while ($image = mysqli_fetch_assoc($images_result)) {
                        $image_path = $image['image_path'];
                        // Check if the path already includes 'product_images/'
                        if (strpos($image_path, 'product_images/') === false) {
                            $image_path = 'product_images/' . $image_path;
                        }
                    ?>
                    <div class="thumbnail <?php echo $first ? 'active' : ''; ?>" onclick="changeImage(this, '<?php echo $image_path; ?>')">
                        <img src="<?php echo $image_path; ?>" alt="<?php echo $product['product_title']; ?> - View">
                    </div>
                    <?php
                        $first = false;
                    }
                    
                    // If no images found, show placeholder
                    if (mysqli_num_rows($images_result) == 0) {
                        $placeholder = 'https://via.placeholder.com/400x400';
                    ?>
                    <div class="thumbnail active" onclick="changeImage(this, '<?php echo $placeholder; ?>')">
                        <img src="<?php echo $placeholder; ?>" alt="<?php echo $product['product_title']; ?> - Placeholder">
                    </div>
                    <?php } ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <h1 class="product-title"><?php echo $product['product_title']; ?></h1>
                <div class="product-brand">Brand: <?php echo $product['manufacturer_title']; ?></div>
                
                <div class="product-rating">
                    <div class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <div class="review-count">4.5 (128 reviews)</div>
                </div>
                
                <div class="product-price-container">
                    <div class="current-price"><?php echo $currentPriceFormatted; ?></div>
                    <?php if($product['product_psp_price'] > 0): ?>
                    <div class="original-price-details"><?php echo $originalPriceFormatted; ?></div>
                    <div class="discount">-<?php echo $discountPercentage; ?>%</div>
                    <?php endif; ?>
                </div>
                
                <div class="availability">
                    <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock']; ?> units)
                </div>
                
                <div class="product-description">
                    <p><?php echo $product['product_desc']; ?></p>
                </div>
                
                <div class="product-features">
                    <h3>Key Features</h3>
                    <ul class="features-list">
                        <?php 
                        $keyFeatures = array_slice($featuresArray, 0, 5); // Show first 5 as key features
                        foreach($keyFeatures as $feature): 
                            $parts = explode(':', $feature, 2);
                            $featureText = count($parts) == 2 ? trim($parts[1]) : $feature;
                        ?>
                        <li><i class="fas fa-check"></i> <?php echo $featureText; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="quantity-selector">
                    <span>Quantity:</span>
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="decrementQuantity()">-</button>
                        <input type="number" class="quantity-input" value="1" min="1" max="<?php echo $product['stock']; ?>" id="quantity">
                        <button class="quantity-btn" onclick="incrementQuantity()">+</button>
                    </div>
                </div>
                
                <!-- Action buttons -->
                <div class="action-buttons">
                    <!-- Add to Cart Button -->
                    <div data-tooltip="Add to your cart" class="button add-to-cart-btn" id="add-to-cart-btn">
                        <div class="button-wrapper">
                            <div class="text">Add to Cart</div>
                            <span class="icon">
                                <svg viewBox="0 0 16 16" class="bi bi-cart-plus" fill="currentColor" height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 5.5a.5.5 0 0 0-1 0V7H6.5a.5.5 0 0 0 0 1H8v1.5a.5.5 0 0 0 1 0V8h1.5a.5.5 0 0 0 0-1H9V5.5z"/>
                                    <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 0h7a2 2 0 1 0 0 0h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zm3.915 10L3.102 4h10.796l-1.313 7h-8.17zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0z"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Buy Now Button -->
                    <div data-tooltip="Price: <?php echo $currentPriceFormatted; ?>" class="button buy-now-btn" id="buy-now-btn">
                        <div class="button-wrapper">
                            <div class="text">Buy Now</div>
                            <span class="icon">
                                <svg viewBox="0 0 16 16" class="bi bi-cart2" fill="currentColor" height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5zM3.14 5l1.25 5h8.22l1.25-5H3.14zM5 13a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0zm9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0z"></path>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Specifications -->
        <div class="specifications">
            <h2>Specifications</h2>
            <table class="specs-table">
                <?php
                foreach($featuresArray as $feature) {
                    $parts = explode(':', $feature, 2);
                    if(count($parts) == 2) {
                        echo "<tr><th>" . trim($parts[0]) . "</th><td>" . trim($parts[1]) . "</td></tr>";
                    } else {
                        echo "<tr><th>Feature</th><td>" . $feature . "</td></tr>";
                    }
                }
                ?>
            </table>
        </div>
        
        <!-- Customer Feedback -->
        <div class="customer-feedback">
            <h2>Customer Reviews</h2>
            <div class="feedback-summary">
                <div class="average-rating">
                    <span>4.5</span>
                    <div class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <span>(128 reviews)</span>
                </div>
            </div>
            <div class="feedback-list">
                <div class="feedback-item">
                    <div class="feedback-header">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <span class="reviewer-name">Ahmed K.</span>
                        <span class="review-date">May 1, 2025</span>
                    </div>
                    <p class="feedback-comment">Fantastic product! The quality exceeded my expectations, and the delivery was super fast. Highly recommend!</p>
                </div>
                <div class="feedback-item">
                    <div class="feedback-header">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                        </div>
                        <span class="reviewer-name">Sara M.</span>
                        <span class="review-date">April 28, 2025</span>
                    </div>
                    <p class="feedback-comment">Really good product, but I had some issues with the setup instructions. Customer support was helpful in resolving it.</p>
                </div>
                <div class="feedback-item">
                    <div class="feedback-header">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <span class="reviewer-name">Omar L.</span>
                        <span class="review-date">April 25, 2025</span>
                    </div>
                    <p class="feedback-comment">Great value for the price. The product performs well, and I'm satisfied with my purchase.</p>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if(!empty($relatedProducts)): ?>
        <div class="related-products">
            <h2>You May Also Like</h2>
            <button class="scroll-button prev">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="scroll-button next">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="product-grid">
                <?php foreach($relatedProducts as $relatedProduct): ?>
                <div class="product-card">
                    <a href="product-details.php?id=<?php echo $relatedProduct['product_id']; ?>" class="product-link">
                        <div class="product-image">
                            <span class="product-badge"><?php echo $relatedProduct['manufacturer_title']; ?></span>
                            <img src="<?php echo $relatedProduct['product_img1']; ?>" alt="<?php echo $relatedProduct['product_title']; ?>">
                        </div>
                        <div class="product-info">
                            <h3><?php echo $relatedProduct['product_title']; ?></h3>
                            <div class="product-price"><?php echo $relatedProduct['price']; ?></div>
                            <div class="product-price-info">
                                <?php if($relatedProduct['product_psp_price'] > 0): ?>
                                <span class="original-price"><?php echo $relatedProduct['original_price']; ?></span>
                                <?php endif; ?>
                                <span class="new-price">New: <?php echo $relatedProduct['price']; ?></span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-column">
                <h3>Shop</h3>
                <ul>
                    <li><a href="#">Phones</a></li>
                    <li><a href="#">Laptops</a></li>
                    <li><a href="#">Accessories</a></li>
                    <li><a href="#">New Arrivals</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Shipping Policy</a></li>
                    <li><a href="#">Returns & Exchanges</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>About</h3>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Connect With Us</h3>
                <div class="social-icons">
                    <a href="#" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" aria-label="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
                
                <div class="newsletter">
                    <h4>Subscribe to our newsletter</h4>
                    <div class="newsletter-form">
                        <input type="email" placeholder="Your email">
                        <button type="button">Subscribe</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© 2024 TechMarket. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Add a notification element for cart messages -->
<div id="cart-notification" class="cart-notification">
    <div class="notification-content">
        <i class="fas fa-check-circle"></i>
        <span id="notification-message"></span>
    </div>
</div>

<script>
    // Function to change the main product image
    function changeImage(thumbnail, newSrc) {
        // Update main image
        document.getElementById('main-product-image').src = newSrc;
        
        // Update active thumbnail
        const thumbnails = document.querySelectorAll('.thumbnail');
        thumbnails.forEach(thumb => {
            thumb.classList.remove('active');
        });
        thumbnail.classList.add('active');
    }
    
    // Quantity functions
    function incrementQuantity() {
        const quantityInput = document.getElementById('quantity');
        const currentValue = parseInt(quantityInput.value);
        const maxValue = parseInt(quantityInput.max);
        
        if (currentValue < maxValue) {
            quantityInput.value = currentValue + 1;
        }
    }
    
    function decrementQuantity() {
        const quantityInput = document.getElementById('quantity');
        const currentValue = parseInt(quantityInput.value);
        
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    }
    
    // Function to show notification
    function showNotification(message, isSuccess = true) {
        const notification = document.getElementById('cart-notification');
        const notificationMessage = document.getElementById('notification-message');
        
        // Set message and class
        notificationMessage.textContent = message;
        notification.className = isSuccess ? 'cart-notification success' : 'cart-notification error';
        
        // Show notification
        notification.style.display = 'flex';
        
        // Hide after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.style.display = 'none';
                notification.style.opacity = '1';
            }, 500);
        }, 3000);
    }
    
    // Add event listeners for buttons
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listeners for buttons
        const addToCartBtn = document.getElementById('add-to-cart-btn');
        const buyNowBtn = document.getElementById('buy-now-btn');
        
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function() {
                const quantity = document.getElementById('quantity').value;
                const productId = <?php echo $product['product_id']; ?>;
                const price = <?php echo $currentPrice; ?>;
                
                // Show loading state
                this.classList.add('loading');
                this.querySelector('.text').textContent = 'Adding...';
                
                // Create form data
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', quantity);
                formData.append('price', price);
                
                // Log the data being sent
                console.log('Sending data:', {
                    product_id: productId,
                    quantity: quantity,
                    price: price
                });
                
                // AJAX request to add to cart
                fetch('add-to-cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    // Reset button state
                    this.classList.remove('loading');
                    this.querySelector('.text').textContent = 'Add to Cart';
                    
                    if (data.success) {
                        showNotification('Product added to cart successfully!');
                    } else {
                        showNotification('Error: ' + data.message, false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Reset button state
                    this.classList.remove('loading');
                    this.querySelector('.text').textContent = 'Add to Cart';
                    
                    showNotification('An error occurred while adding the product to cart. Check console for details.', false);
                });
            });
        }

        if (buyNowBtn) {
            buyNowBtn.addEventListener('click', function() {
                const quantity = document.getElementById('quantity').value;
                const productId = <?php echo $product['product_id']; ?>;
                const price = <?php echo $currentPrice; ?>;
                
                // Show loading state
                this.classList.add('loading');
                this.querySelector('.text').textContent = 'Processing...';
                
                // Create form data
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', quantity);
                formData.append('price', price);
                formData.append('buy_now', '1'); // Add buy_now parameter
                
                // Log the data being sent
                console.log('Sending data (buy now):', {
                    product_id: productId,
                    quantity: quantity,
                    price: price,
                    buy_now: 1
                });
                
                // AJAX request to add to cart and redirect to checkout
                fetch('add-to-cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    
                    if (data.success) {
                        // Redirect to checkout page
                        window.location.href = 'checkout.php';
                    } else {
                        // Reset button state
                        this.classList.remove('loading');
                        this.querySelector('.text').textContent = 'Buy Now';
                        showNotification('Error: ' + data.message, false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Reset button state
                    this.classList.remove('loading');
                    this.querySelector('.text').textContent = 'Buy Now';
                    
                    showNotification('An error occurred while processing your request. Check console for details.', false);
                });
            });
        }
        
        // Add scroll functionality for related products
        const relatedProductsGrid = document.querySelector('.related-products .product-grid');
        const prevButton = document.querySelector('.related-products .scroll-button.prev');
        const nextButton = document.querySelector('.related-products .scroll-button.next');
        
        if (prevButton && nextButton && relatedProductsGrid) {
            // Scroll amount (width of one product card plus gap)
            const scrollAmount = 220; // 200px card width + 20px gap
            
            prevButton.addEventListener('click', () => {
                relatedProductsGrid.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            });
            
            nextButton.addEventListener('click', () => {
                relatedProductsGrid.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            });
            
            // Hide/show scroll buttons based on scroll position
            const updateScrollButtons = () => {
                prevButton.style.display = relatedProductsGrid.scrollLeft > 0 ? 'flex' : 'none';
                nextButton.style.display = 
                    relatedProductsGrid.scrollLeft < (relatedProductsGrid.scrollWidth - relatedProductsGrid.clientWidth) 
                    ? 'flex' : 'none';
            };
            
            relatedProductsGrid.addEventListener('scroll', updateScrollButtons);
            window.addEventListener('resize', updateScrollButtons);
            
            // Initial check after products are loaded
            setTimeout(updateScrollButtons, 500);
        }
    });
</script>

<style>
/* Add styles for the notification */
.cart-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-radius: 8px;
    padding: 15px 20px;
    display: none;
    align-items: center;
    z-index: 1000;
    transition: opacity 0.3s ease;
}

.cart-notification.success {
    border-left: 4px solid #4ade80;
}

.cart-notification.error {
    border-left: 4px solid #ef4444;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.cart-notification i {
    font-size: 20px;
}

.cart-notification.success i {
    color: #4ade80;
}

.cart-notification.error i {
    color: #ef4444;
}

#notification-message {
    font-size: 14px;
    font-weight: 500;
}

/* Loading state for buttons */
.button.loading {
    opacity: 0.7;
    cursor: wait;
}

.button.loading .icon {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Styles for customer feedback */
.customer-feedback {
    margin-top: 40px;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
}

.customer-feedback h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

.feedback-summary {
    margin-bottom: 20px;
}

.average-rating {
    display: flex;
    align-items: center;
    gap: 10px;
}

.average-rating span:first-child {
    font-size: 28px;
    font-weight: bold;
    color: #333;
}

.average-rating .stars {
    color: #f1c40f;
}

.average-rating span:last-child {
    font-size: 14px;
    color: #666;
}

.feedback-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.feedback-item {
    background-color: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.feedback-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.feedback-header .stars {
    color: #f1c40f;
}

.reviewer-name {
    font-weight: bold;
    color: #333;
}

.review-date {
    font-size: 12px;
    color: #666;
    margin-left: auto;
}

.feedback-comment {
    font-size: 14px;
    color: #444;
    line-height: 1.5;
}

/* Product gallery styles */
.product-gallery {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.main-image {
    width: 100%;
    height: 400px;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f9f9f9;
    border-radius: 8px;
    overflow: hidden;
}

.main-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.thumbnail-container {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 5px;
}

.thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color 0.2s;
}

.thumbnail.active {
    border-color: #00d9b1;
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Related products styles */
.related-products {
    margin-top: 40px;
    position: relative;
}

.related-products h2 {
    margin-bottom: 20px;
    font-size: 24px;
    color: #333;
}

.product-grid {
    display: flex;
    overflow-x: auto;
    gap: 20px;
    padding: 10px 0;
    scroll-behavior: smooth;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

.product-grid::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

.scroll-button {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #fff;
    border: 1px solid #ddd;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
}

.scroll-button.prev {
    left: -20px;
}

.scroll-button.next {
    right: -20px;
}

.scroll-button i {
    color: #333;
}

.product-card {
    flex: 0 0 auto;
    width: 200px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.product-link {
    text-decoration: none;
    color: inherit;
}

.product-image {
    position: relative;
    height: 200px;
    background-color: #f9f9f9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.product-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.product-info {
    padding: 15px;
}

.product-info h3 {
    margin: 0 0 10px;
    font-size: 16px;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-price {
    font-size: 18px;
    font-weight: bold;
    color: #00d9b1;
    margin-bottom: 5px;
}

.product-price-info {
    display: flex;
    flex-direction: column;
    font-size: 12px;
    color: #666;
}

.original-price {
    text-decoration: line-through;
}
</style>
</body>
</html>
