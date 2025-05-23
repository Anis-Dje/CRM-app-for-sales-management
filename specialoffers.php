<?php
// Database connection
$con = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// Function to safely get product image
function getProductImage($product, $default = 'https://via.placeholder.com/150x200') {
    // Check if product_img1 exists in the product array
    if (!isset($product['product_img1']) || empty($product['product_img1'])) {
        // Try to get image from product_images table
        global $con;
        $product_id = $product['product_id'];
        $image_query = "SELECT image_path FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC LIMIT 1";
        $img_result = mysqli_query($con, $image_query);
        
        if ($img_result && mysqli_num_rows($img_result) > 0) {
            $img_row = mysqli_fetch_assoc($img_result);
            $image_path = $img_row['image_path'];
            
            // Check if the path already includes 'product_images/'
            if (strpos($image_path, 'product_images/') === false) {
                return 'product_images/' . $image_path;
            } else {
                return $image_path;
            }
        }
        
        // If no image found in product_images table, return default
        return $default;
    }
    
    // If product_img1 exists but is a URL, return it directly
    if (filter_var($product['product_img1'], FILTER_VALIDATE_URL)) {
        return $product['product_img1'];
    }
    
    // Otherwise, prepend 'product_images/' if needed
    if (strpos($product['product_img1'], 'product_images/') === false) {
        return 'product_images/' . $product['product_img1'];
    }
    
    return $product['product_img1'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offers - TechMarket</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fiche-technique {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .product-card:hover .fiche-technique {
            display: block;
        }
        .fiche-technique h4 {
            margin: 0 0 10px;
            font-size: 14px;
            color: #333;
        }
        .fiche-technique ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .fiche-technique li {
            font-size: 12px;
            margin-bottom: 5px;
            color: #555;
        }
        .fiche-technique li strong {
            color: #000;
        }
        .product-image img {
            width: 100%;
            height: 200px;
            object-fit: contain;
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
                                        <path d="M23.7953 23.9182L19.0585 19.1814M19.0585 19.1814C19.8188 18.4211 20.4219 17.5185 20.8333 16.5251C21.2448 15.5318 21.4566 14.4671 21.4566 13.3919C21.4566 12.3167 21.2448 11.252 20.8333 10.2587C20.4219 9.2653 19.8188 8.36271 19.0585 7.60242C18.2982 6.84214 17.3956 6.23905 16.4022 5.82759C15.4089 5.41612 14.3442 5. costitue20435 13.269 5.20435C12.1938 5.20435 11.1291 5.41612 10.1358 5.82759C9.1424 6.23905 8.23981 6.84214 7.47953 7.60242C5.94407 9.13789 5.08145 11.2204 5.08145 13.3919C5.08145 15.5634 5.94407 17.6459 7.47953 19.1814C9.01499 20.7168 11.0975 21.5794 13.269 21.5794C15.4405 21.5794 17.523 20.7168 19.0585 19.1814Z" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" shape-rendering="crispEdges"></path>
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
        <div class="container">
            <section class="product-section">
                <h2>Special Offers</h2>
                <div class="product-grid-container">
                    <button class="scroll-button prev"><i class="fas fa-chevron-left"></i></button>
                    <div class="product-grid">
                        <?php
                        // Query for products labeled as sale, including category
                        $sql = "SELECT p.*, pc.p_cat_id 
                                FROM products p
                                LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
                                WHERE p.product_label = 'sale' 
                                ORDER BY p.date DESC";
                        $result = mysqli_query($con, $sql);

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Format prices
                                $product_price = number_format($row['product_price'], 0) . ' D.A';
                                $product_psp_price = $row['product_psp_price'] > 0 ? number_format($row['product_psp_price'], 0) . ' D.A' : $product_price;

                                // Get product image using the helper function
                                $product_img1 = getProductImage($row);

                                // Parse product_features into an array and filter based on category
                                $fiche_technique = [];
                                $p_cat_id = $row['p_cat_id'];
                                $desired_fields = [];
                                if ($p_cat_id == 4) {
                                    $desired_fields = ['Display', 'Processor', 'Rear Camera', 'Battery'];
                                } elseif ($p_cat_id == 8) {
                                    $desired_fields = ['Display', 'Processor', 'Graphics', 'Battery'];
                                }
                                if (!empty($row['product_features'])) {
                                    $features = explode('|', $row['product_features']);
                                    foreach ($features as $feature) {
                                        $parts = explode(':', $feature, 2);
                                        if (count($parts) === 2) {
                                            $key = trim($parts[0]);
                                            $value = trim($parts[1]);
                                            if (empty($desired_fields) || in_array($key, $desired_fields)) {
                                                $fiche_technique[$key] = $value;
                                            }
                                        }
                                    }
                                }
                                ?>
                                <div class="product-card">
                                    <a href="javascript:void(0);" onclick="openProductDetails('<?php echo htmlspecialchars($row['product_id']); ?>')">
                                        <div class="product-image">
                                            <span class="product-badge"><?php echo htmlspecialchars($row['product_title']); ?></span>
                                            <img src="<?php echo htmlspecialchars($product_img1); ?>" alt="<?php echo htmlspecialchars($row['product_title']); ?>">
                                        </div>
                                        <div class="product-info">
                                            <h3><?php echo htmlspecialchars($row['product_title']); ?></h3>
                                            <div class="product-price"><?php echo htmlspecialchars($product_psp_price); ?></div>
                                            <div class="product-price-info">
                                                <span class="original-price"><?php echo htmlspecialchars($product_price); ?></span>
                                                <span class="new-price">New: <?php echo htmlspecialchars($product_psp_price); ?></span>
                                            </div>
                                            <?php if (!empty($fiche_technique)) { ?>
                                                <div class="fiche-technique">
                                                    <h4>Fiche Technique</h4>
                                                    <ul>
                                                        <?php foreach ($fiche_technique as $key => $value) { ?>
                                                            <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </a>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p>No special offers found.</p>';
                        }
                        ?>
                    </div>
                    <button class="scroll-button next"><i class="fas fa-chevron-right"></i></button>
                </div>
            </section>
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

    <!-- JavaScript for product details and scrolling -->
    <script>
        function openProductDetails(productId) {
            window.location.href = `product-details.php?id=${productId}`;
        }

        // Scroll functionality for product grid
        document.addEventListener('DOMContentLoaded', () => {
            const grid = document.querySelector('.product-grid');
            const prevBtn = document.querySelector('.scroll-button.prev');
            const nextBtn = document.querySelector('.scroll-button.next');

            prevBtn.addEventListener('click', () => {
                grid.scrollBy({ left: -250, behavior: 'smooth' });
            });

            nextBtn.addEventListener('click', () => {
                grid.scrollBy({ left: 250, behavior: 'smooth' });
            });

            // Show/hide scroll buttons based on scroll position
            const updateButtonVisibility = () => {
                prevBtn.style.display = grid.scrollLeft > 0 ? 'flex' : 'none';
                nextBtn.style.display = grid.scrollLeft < (grid.scrollWidth - grid.clientWidth) ? 'flex' : 'none';
            };

            grid.addEventListener('scroll', updateButtonVisibility);
            updateButtonVisibility(); // Initial check
        });
    </script>
</body>
</html>
<?php
mysqli_close($con);
?>
