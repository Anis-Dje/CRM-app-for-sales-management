<?php
session_start(); // Start session to check login status
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
    <title>TechMarket</title>
    <link rel="stylesheet" href="style/styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Reset search bar styles */
    .searchBox {
        width: 100%;
        max-width: 500px;
        height: 50px;
        position: relative;
        margin: 0 20px;
    }

    .searchInput {
        width: 100%;
        height: 100%;
        border: none;
        border-radius: 50px;
        background: #2f3640;
        padding: 0 60px 0 20px;
        font-size: 15px;
        color: white;
        transition: all 0.3s ease;
        position: static;
        right: auto;
    }

    .searchInput::placeholder {
        color: #666;
    }

    .searchInput:focus {
        outline: none;
        box-shadow: 0 0 5px rgba(42, 245, 152, 0.5);
    }

    .searchButton {
        position: absolute;
        right: 5px;
        top: 5px;
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 50%;
        background: #00d9b1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .searchButton:hover {
        background: #00c4a0;
        transform: scale(0.95);
    }

    .searchButton svg {
        width: 20px;
        height: 20px;
        fill: white;
    }

    /* Other styles */
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
    /* Carousel Styles */
    .product-carousel-container {
        position: relative;
        overflow: hidden;
    }
    .product-carousel {
        display: flex;
        overflow-x: auto;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE and Edge */
    }
    .product-carousel::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
    }
    .product-card {
        flex: 0 0 auto;
        margin-right: 20px; /* Space between cards */
        width: 200px; /* Adjust as needed */
    }
    .carousel-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        padding: 10px;
        cursor: pointer;
        z-index: 10;
    }
    .prev-arrow {
        left: 0;
    }
    .next-arrow {
        right: 0;
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
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
        <div class="container">
            <!-- Smartphones Section -->
            <section class="product-section">
                <h2>Smartphones:</h2>
                <div class="product-carousel-container">
                    <button class="carousel-arrow prev-arrow"><i class="fas fa-chevron-left"></i></button>
                    <div class="product-carousel">
                        <?php
                        // Query for Smartphones (p_cat_id = 4)
                        $query_smartphones = "SELECT p.*, pc.p_cat_title, m.manufacturer_title 
                                             FROM products p
                                             LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
                                             LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
                                             WHERE p.p_cat_id = 4
                                             ORDER BY p.date DESC";
                        $result_smartphones = mysqli_query($con, $query_smartphones);

                        if ($result_smartphones && mysqli_num_rows($result_smartphones) > 0) {
                            while ($row = mysqli_fetch_assoc($result_smartphones)) {
                                // Format prices
                                $product_price = number_format($row['product_price'], 0) . ' D.A';
                                $product_psp_price = $row['product_psp_price'] > 0 ? number_format($row['product_psp_price'], 0) . ' D.A' : $product_price;

                                // Get product image from product_images table
                                $product_id = $row['product_id'];
                                $image_query = "SELECT image_path FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC LIMIT 1";
                                $img_result = mysqli_query($con, $image_query);
                                if ($img_result && mysqli_num_rows($img_result) > 0) {
                                    $img_row = mysqli_fetch_assoc($img_result);
                                    $product_img1 = $img_row['image_path'];
                                    // Check if the path already includes 'product_images/'
                                    if (strpos($product_img1, 'product_images/') === false) {
                                        $product_img1 = 'product_images/' . $product_img1;
                                    }
                                } else {
                                    $product_img1 = 'https://via.placeholder.com/150x200';
                                }

                                // Parse product_features into an array and filter only desired fields
                                $fiche_technique = [];
                                $desired_fields = ['Display', 'Processor', 'Rear Camera', 'Battery']; // Specify desired fields
                                if (!empty($row['product_features'])) {
                                    $features = explode('|', $row['product_features']);
                                    foreach ($features as $feature) {
                                        $parts = explode(':', $feature, 2);
                                        if (count($parts) === 2 && in_array(trim($parts[0]), $desired_fields)) {
                                            $fiche_technique[trim($parts[0])] = trim($parts[1]);
                                        }
                                    }
                                }
                                ?>
                                <div class="product-card" data-product-id="<?php echo htmlspecialchars($row['product_id']); ?>">
                                    <a href="javascript:void(0);" class="product-link" onclick="openProductDetails('<?php echo htmlspecialchars($row['product_id']); ?>')">
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
                            echo '<p>No smartphones found.</p>';
                        }
                        ?>
                    </div>
                    <button class="carousel-arrow next-arrow"><i class="fas fa-chevron-right"></i></button>
                </div>
            </section>

            <!-- Laptops Section -->
            <section class="product-section">
                <h2>Laptops:</h2>
                <div class="product-carousel-container">
                    <button class="carousel-arrow prev-arrow"><i class="fas fa-chevron-left"></i></button>
                    <div class="product-carousel">
                        <?php
                        // Query for Laptops (p_cat_id = 8)
                        $query_laptops = "SELECT p.*, pc.p_cat_title, m.manufacturer_title 
                                         FROM products p
                                         LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
                                         LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
                                         WHERE p.p_cat_id = 8
                                         ORDER BY p.date DESC";
                        $result_laptops = mysqli_query($con, $query_laptops);

                        if ($result_laptops && mysqli_num_rows($result_laptops) > 0) {
                            while ($row = mysqli_fetch_assoc($result_laptops)) {
                                // Format prices
                                $product_price = number_format($row['product_price'], 0) . ' D.A';
                                $product_psp_price = $row['product_psp_price'] > 0 ? number_format($row['product_psp_price'], 0) . ' D.A' : $product_price;

                                // Get product image from product_images table
                                $product_id = $row['product_id'];
                                $image_query = "SELECT image_path FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC LIMIT 1";
                                $img_result = mysqli_query($con, $image_query);
                                if ($img_result && mysqli_num_rows($img_result) > 0) {
                                    $img_row = mysqli_fetch_assoc($img_result);
                                    $product_img1 = $img_row['image_path'];
                                    // Check if the path already includes 'product_images/'
                                    if (strpos($product_img1, 'product_images/') === false) {
                                        $product_img1 = 'product_images/' . $product_img1;
                                    }
                                } else {
                                    $product_img1 = 'https://via.placeholder.com/150x200';
                                }

                                // Parse product_features into an array and filter only desired fields
                                $fiche_technique = [];
                                $desired_fields = ['Display', 'Processor', 'Graphics', 'Battery']; // Adjusted for laptops
                                if (!empty($row['product_features'])) {
                                    $features = explode('|', $row['product_features']);
                                    foreach ($features as $feature) {
                                        $parts = explode(':', $feature, 2);
                                        if (count($parts) === 2 && in_array(trim($parts[0]), $desired_fields)) {
                                            $fiche_technique[trim($parts[0])] = trim($parts[1]);
                                        }
                                    }
                                }
                                ?>
                                <div class="product-card" data-product-id="<?php echo htmlspecialchars($row['product_id']); ?>">
                                    <a href="javascript:void(0);" class="product-link" onclick="openProductDetails('<?php echo htmlspecialchars($row['product_id']); ?>')">
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
                            echo '<p>No laptops found.</p>';
                        }
                        ?>
                    </div>
                    <button class="carousel-arrow next-arrow"><i class="fas fa-chevron-right"></i></button>
                </div>
            </section>

            <!-- Accessories Section -->
            <section class="product-section">
                <h2>Accessories:</h2>
                <div class="product-carousel-container">
                    <button class="carousel-arrow prev-arrow"><i class="fas fa-chevron-left"></i></button>
                    <div class="product-carousel">
                        <?php
                        // Query for Accessories (p_cat_id = 9)
                        $query_accessories = "SELECT p.*, pc.p_cat_title, m.manufacturer_title 
                                             FROM products p
                                             LEFT JOIN product_categories pc ON p.p_cat_id = pc.p_cat_id
                                             LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id
                                             WHERE p.p_cat_id = 9
                                             ORDER BY p.date DESC";
                        $result_accessories = mysqli_query($con, $query_accessories);

                        if ($result_accessories && mysqli_num_rows($result_accessories) > 0) {
                            while ($row = mysqli_fetch_assoc($result_accessories)) {
                                // Format prices
                                $product_price = number_format($row['product_price'], 0) . ' D.A';
                                $product_psp_price = $row['product_psp_price'] > 0 ? number_format($row['product_psp_price'], 0) . ' D.A' : $product_price;

                                // Get product image from product_images table
                                $product_id = $row['product_id'];
                                $image_query = "SELECT image_path FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC LIMIT 1";
                                $img_result = mysqli_query($con, $image_query);
                                if ($img_result && mysqli_num_rows($img_result) > 0) {
                                    $img_row = mysqli_fetch_assoc($img_result);
                                    $product_img1 = $img_row['image_path'];
                                    // Check if the path already includes 'product_images/'
                                    if (strpos($product_img1, 'product_images/') === false) {
                                        $product_img1 = 'product_images/' . $product_img1;
                                    }
                                } else {
                                    $product_img1 = 'https://via.placeholder.com/150x200';
                                }

                                // Parse product_features into an array
                                $fiche_technique = [];
                                if (!empty($row['product_features'])) {
                                    $features = explode('|', $row['product_features']);
                                    foreach ($features as $feature) {
                                        $parts = explode(':', $feature, 2);
                                        if (count($parts) === 2) {
                                            $fiche_technique[trim($parts[0])] = trim($parts[1]);
                                        }
                                    }
                                }
                                ?>
                                <div class="product-card" data-product-id="<?php echo htmlspecialchars($row['product_id']); ?>">
                                    <a href="javascript:void(0);" class="product-link" onclick="openProductDetails('<?php echo htmlspecialchars($row['product_id']); ?>')">
                                        <div class="product-image">
                                            <span class="product-badge"><?php echo htmlspecialchars($row['product_title']); ?></span>
                                            <img src="<?php echo htmlspecialchars($product_img1); ?>" alt="<?php echo htmlspecialchars($row['product_title']); ?>">
                                        </div>
                                        <div class="product-info">
                                            <h3><?php echo htmlspecialchars($row['product_title']); ?></h3>
                                            <div class="product-price"><?php echo htmlspecialchars($product_psp_price); ?></div>
                                            <div class="product-price-info">
                                                <span class="original-price"><?php echo htmlspecialchars($row['product_price']); ?></span>
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
                            echo '<p>No accessories found.</p>';
                        }
                        ?>
                    </div>
                    <button class="carousel-arrow next-arrow"><i class="fas fa-chevron-right"></i></button>
                </div>
            </section>
        </div>
    </main>

    <!-- JavaScript for product details redirection and carousel scrolling -->
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

        // Product details redirection
        function openProductDetails(productId) {
            window.location.href = `product-details.php?id=${productId}`;
        }

        // Carousel scrolling
        document.querySelectorAll('.product-carousel-container').forEach(carouselContainer => {
            const carousel = carouselContainer.querySelector('.product-carousel');
            const prevButton = carouselContainer.querySelector('.prev-arrow');
            const nextButton = carouselContainer.querySelector('.next-arrow');

            // Scroll left (previous)
            prevButton.addEventListener('click', () => {
                carousel.scrollBy({
                    left: -220, // Adjust based on product-card width (200px) + margin (20px)
                    behavior: 'smooth'
                });
            });

            // Scroll right (next)
            nextButton.addEventListener('click', () => {
                carousel.scrollBy({
                    left: 220, // Adjust based on product-card width (200px) + margin (20px)
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
<?php
mysqli_close($con); // Close the database connection
?>
