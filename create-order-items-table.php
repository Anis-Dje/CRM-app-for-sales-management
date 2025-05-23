<?php
session_start();
include 'C:/xampp/htdocs/PPD/php/config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Database Setup Script</h2>";

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    echo "<p style='color: red;'>❌ You must be logged in as an admin to run this script</p>";
    echo "<p><a href='auth.php'>Login here</a></p>";
    exit;
}

// Create order_items table if it doesn't exist
$check_order_items = $conn->query("SHOW TABLES LIKE 'order_items'");
if ($check_order_items->num_rows == 0) {
    echo "<p>Creating order_items table...</p>";
    
    $create_order_items = "CREATE TABLE `order_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_id` INT(11) NOT NULL,
        `product_id` INT(11) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `quantity` INT(11) NOT NULL,
        `status` VARCHAR(20) DEFAULT 'ORDERED',
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($create_order_items)) {
        echo "<p style='color: green;'>✅ order_items table created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create order_items table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ order_items table already exists</p>";
}

// Check if customer_orders table has the required structure
$check_customer_orders = $conn->query("SHOW COLUMNS FROM customer_orders LIKE 'discount_id'");
if ($check_customer_orders->num_rows == 0) {
    echo "<p>Updating customer_orders table structure...</p>";
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Add new columns
        $conn->query("ALTER TABLE `customer_orders` ADD COLUMN `discount_id` INT(11) NULL AFTER `discount_applied`");
        $conn->query("ALTER TABLE `customer_orders` ADD COLUMN `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `discount_amount`");
        $conn->query("ALTER TABLE `customer_orders` ADD COLUMN `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 500 AFTER `subtotal`");
        $conn->query("ALTER TABLE `customer_orders` ADD COLUMN `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `shipping_cost`");
        
        // Update existing records
        $conn->query("UPDATE `customer_orders` SET `subtotal` = `due_amount`, `total_amount` = `due_amount`");
        
        // Update discount_id from discount_code
        $conn->query("UPDATE `customer_orders` co 
                     LEFT JOIN `discounts` d ON co.discount_code = d.code 
                     SET co.discount_id = d.id 
                     WHERE co.discount_code IS NOT NULL AND co.discount_code != ''");
        
        // Rename discount_applied to has_discount
        $conn->query("ALTER TABLE `customer_orders` CHANGE `discount_applied` `has_discount` TINYINT(1) NOT NULL DEFAULT 0");
        
        // Add foreign key constraint
        $conn->query("ALTER TABLE `customer_orders` 
                     ADD CONSTRAINT `fk_orders_discount` 
                     FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) 
                     ON UPDATE CASCADE ON DELETE SET NULL");
        
        // Commit transaction
        $conn->commit();
        echo "<p style='color: green;'>✅ customer_orders table updated successfully</p>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red;'>❌ Failed to update customer_orders table: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ customer_orders table already has the updated structure</p>";
}

// Check if discounts table exists
$check_discounts = $conn->query("SHOW TABLES LIKE 'discounts'");
if ($check_discounts->num_rows == 0) {
    echo "<p>Creating discounts table...</p>";
    
    $create_discounts = "CREATE TABLE `discounts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `code` VARCHAR(50) NOT NULL,
        `discount_type` ENUM('percentage', 'fixed') NOT NULL,
        `discount_value` DECIMAL(10,2) NOT NULL,
        `min_order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `max_discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `usage_limit` INT(11) NOT NULL DEFAULT 0,
        `usage_count` INT(11) NOT NULL DEFAULT 0,
        `expiry_date` DATE NULL DEFAULT NULL,
        `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($create_discounts)) {
        echo "<p style='color: green;'>✅ discounts table created successfully</p>";
        
        // Add sample discounts
        $sample_discounts = "INSERT INTO `discounts` 
            (`code`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `status`) VALUES
            ('WELCOME10', 'percentage', 10, 0, 0, 'active'),
            ('SUMMER20', 'percentage', 20, 5000, 2000, 'active'),
            ('FLAT500', 'fixed', 500, 2000, 0, 'active')";
        
        if ($conn->query($sample_discounts)) {
            echo "<p style='color: green;'>✅ Sample discounts added</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to create discounts table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ discounts table already exists</p>";
}

$conn->close();

echo "<br><hr>";
echo "<p><a href='debug-checkout.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Check Checkout Debug</a></p>";
echo "<p><a href='checkout.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Checkout</a></p>";
echo "<p><a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Back to Homepage</a></p>";
?>
