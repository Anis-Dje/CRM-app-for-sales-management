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
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                var filteredData = jsonData.filter(row => row.some(filledCell));
                var headerRowIndex = filteredData.findIndex((row, index) =>
                    row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                );
                if (headerRowIndex === -1 || headerRowIndex > 25) {
                    headerRowIndex = 0;
                }
                var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex));
                csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
                return csv;
            } catch (e) {
                console.error(e);
                return "";
            }
        }
        return gk_fileData[filename] || "";
    }
</script>
<?php
// Database connection
$con = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - TechMarket</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styling for product-grid to display 4 products per row */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 equal columns */
            gap: 20px; /* Space between products */
            padding: 20px 0;
        }
        .product-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            transition: transform 0.2s;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .product-image {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .product-card:hover .product-image img {
            transform: scale(1.1);
        }
        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #2ecc71;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            max-width: calc(100% - 20px);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .product-info h3 {
            font-size: 16px;
            margin: 10px 0;
            color: #333;
            line-height: 1.4;
        }
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #2ecc71;
            margin: 5px 0;
        }
        .product-price-info {
            font-size: 14px;
            color: #777;
            margin-bottom: 10px;
        }
        .original-price {
            text-decoration: line-through;
            margin-right: 10px;
            color: #999;
        }
        .new-price {
            color: #e74c3c;
            font-weight: bold;
        }
        /* Fiche Technique styles */
        .fiche-technique {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
            width: 100%;
            text-align: left;
        }
        .product-card:hover .fiche-technique {
            display: block;
        }
        .fiche-technique h4 {
            margin: 0 0 10px;
            font-size: 14px;
            color: #333;
            text-align: center;
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
            padding: 2px 0;
            border-bottom: 1px solid #eee;
        }
        .fiche-technique li:last-child {
            border-bottom: none;
        }
        .fiche-technique li strong {
            color: #000;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }
        .search-header {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .search-header h2 {
            margin: 0;
            color: #333;
        }
        .results-count {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        /* Responsive design: adjust for smaller screens */
        @media (max-width: 1024px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr); /* 3 products per row on medium screens */
            }
        }
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 products per row on small screens */
            }
        }
        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr; /* 1 product per row on very small screens */
            }
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
                        <input type="text" class="searchInput" placeholder="What are you looking for?" name="search" value="<?php echo isset($_POST['search']) ? htmlspecialchars($_POST['search']) : ''; ?>">
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
                    <a href="#"><i class="fas fa-shopping-bag"></i></a>
                    <a href="#"><i class="fas fa-globe"></i></a>
                    <a href="#"><i class="fas fa-user"></i></a>
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
                <?php
                $search_term = isset($_POST['search']) ? htmlspecialchars($_POST['search']) : '';
                $total_results = 0;
                ?>
                <div class="search-header">
                    <h2>Search Results for "<?php echo $search_term; ?>"</h2>
                    <div class="results-count" id="results-count"></div>
                </div>
                
                <div class="product-grid">
                    <?php
                    if (isset($_POST['submit']) && !empty($_POST['search'])) {
                        $search = mysqli_real_escape_string($con, $_POST['search']);
                        
                        // Modified query to JOIN with product_images table to get the primary image
                        $sql = "SELECT p.*, pc.p_cat_title, m.manufacturer_title,
                                       pi.image_path as primary_image
                                FROM products p
                                LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
                                LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
                                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                                WHERE (p.product_title LIKE '%$search%' 
                                OR p.product_keywords LIKE '%$search%' 
                                OR p.product_desc LIKE '%$search%'
                                OR m.manufacturer_title LIKE '%$search%'
                                OR pc.p_cat_title LIKE '%$search%')
                                AND p.status = 'product'
                                ORDER BY p.product_title ASC";
                        
                        $result = mysqli_query($con, $sql);

                        if ($result && mysqli_num_rows($result) > 0) {
                            $total_results = mysqli_num_rows($result);
                            
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Format prices
                                $product_price = number_format($row['product_price'], 0) . ' D.A';
                                $product_psp_price = $row['product_psp_price'] > 0 ? number_format($row['product_psp_price'], 0) . ' D.A' : $product_price;
                                $has_discount = $row['product_psp_price'] > 0 && $row['product_psp_price'] < $row['product_price'];

                                // Handle image path - use primary image from product_images table
                                $product_image = '/placeholder.svg?height=200&width=200';
                                if (!empty($row['primary_image'])) {
                                    $product_image = 'product_images/' . $row['primary_image'];
                                } else {
                                    // If no primary image, get the first available image for this product
                                    $img_query = "SELECT image_path FROM product_images WHERE product_id = " . $row['product_id'] . " LIMIT 1";
                                    $img_result = mysqli_query($con, $img_query);
                                    if ($img_result && mysqli_num_rows($img_result) > 0) {
                                        $img_row = mysqli_fetch_assoc($img_result);
                                        $product_image = 'product_images/' . $img_row['image_path'];
                                    }
                                }

                                // Parse product_features into an array and filter based on category
                                $fiche_technique = [];
                                $p_cat_id = $row['p_cat_id'];
                                $desired_fields = [];
                                
                                if ($p_cat_id == 4) { // Phones
                                    $desired_fields = ['Display', 'Processor', 'Memory', 'Rear Camera', 'Battery'];
                                } elseif ($p_cat_id == 8) { // Laptops
                                    $desired_fields = ['Display', 'Processor', 'Memory', 'Storage', 'Graphics', 'Battery'];
                                } elseif ($p_cat_id == 9) { // Accessories
                                    $desired_fields = ['Material', 'Connectivity', 'Features', 'Compatibility', 'Battery'];
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
                                                if (count($fiche_technique) >= 4) break; // Limit to 4 features
                                            }
                                        }
                                    }
                                }
                                
                                // Determine product label/badge
                                $badge_text = '';
                                $badge_class = '';
                                if (!empty($row['product_label'])) {
                                    switch(strtolower($row['product_label'])) {
                                        case 'new':
                                            $badge_text = 'New';
                                            $badge_class = 'badge-new';
                                            break;
                                        case 'hot':
                                            $badge_text = 'Hot';
                                            $badge_class = 'badge-hot';
                                            break;
                                        case 'sale':
                                            $badge_text = 'Sale';
                                            $badge_class = 'badge-sale';
                                            break;
                                    }
                                }
                                ?>
                                <div class="product-card">
                                    <a href="javascript:void(0);" onclick="openProductDetails('<?php echo htmlspecialchars($row['product_id']); ?>')">
                                        <div class="product-image">
                                            <?php if ($badge_text): ?>
                                                <span class="product-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                            <?php endif; ?>
                                            <img src="<?php echo htmlspecialchars($product_image); ?>" 
                                                 alt="<?php echo htmlspecialchars($row['product_title']); ?>"
                                                 onerror="this.src='/placeholder.svg?height=200&width=200'">
                                        </div>
                                        <div class="product-info">
                                            <h3><?php echo htmlspecialchars($row['product_title']); ?></h3>
                                            
                                            <?php if ($has_discount): ?>
                                                <div class="product-price-info">
                                                    <span class="original-price"><?php echo $product_price; ?></span>
                                                    <span class="new-price"><?php echo $product_psp_price; ?></span>
                                                </div>
                                            <?php else: ?>
                                                <div class="product-price"><?php echo $product_price; ?></div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($fiche_technique)): ?>
                                                <div class="fiche-technique">
                                                    <h4>Specifications</h4>
                                                    <ul>
                                                        <?php foreach ($fiche_technique as $key => $value): ?>
                                                            <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="no-results">';
                            echo '<i class="fas fa-search" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>';
                            echo '<p>No results found for "' . $search_term . '"</p>';
                            echo '<p>Try searching with different keywords or check your spelling.</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="no-results">';
                        echo '<i class="fas fa-search" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>';
                        echo '<p>Please enter a search query to find products.</p>';
                        echo '</div>';
                    }
                    ?>
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

    <!-- JavaScript for product details redirection and results count -->
    <script>
        function openProductDetails(productId) {
            window.location.href = `product-details.php?id=${productId}`;
        }
        
        // Update results count
        document.addEventListener('DOMContentLoaded', function() {
            const resultsCount = <?php echo $total_results; ?>;
            const resultsCountElement = document.getElementById('results-count');
            if (resultsCountElement && resultsCount > 0) {
                resultsCountElement.textContent = `Found ${resultsCount} product${resultsCount !== 1 ? 's' : ''}`;
            }
        });
    </script>

    <style>
        .badge-new {
            background: #3498db !important;
        }
        .badge-hot {
            background: #e74c3c !important;
        }
        .badge-sale {
            background: #f39c12 !important;
        }
        .header, .category-nav, .footer {
            /* Add your existing header, nav, and footer styles here */
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .main-content {
            min-height: 60vh;
            padding: 20px 0;
        }
    </style>
</body>
</html>
<?php
mysqli_close($con);
?>
