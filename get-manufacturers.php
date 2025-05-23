<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "techstore";
$port = 3307; // As specified in your SQL file

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => "Connection failed: " . $conn->connect_error]));
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if manufacturers table exists
$result = $conn->query("SHOW TABLES LIKE 'manufacturers'");
if ($result->num_rows == 0) {
    // Create manufacturers table if it doesn't exist
    $sql = "CREATE TABLE manufacturers (
        manufacturer_id INT(10) NOT NULL AUTO_INCREMENT,
        manufacturer_title VARCHAR(255) NOT NULL,
        manufacturer_top TEXT NOT NULL,
        manufacturer_image TEXT NOT NULL,
        PRIMARY KEY (manufacturer_id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        // Add some sample manufacturers
        $sql = "INSERT INTO manufacturers (manufacturer_title, manufacturer_top, manufacturer_image) VALUES
            ('Apple', 'yes', 'apple.jpg'),
            ('Samsung', 'yes', 'samsung.jpg'),
            ('Nike', 'yes', 'nike.jpg'),
            ('Adidas', 'yes', 'adidas.jpg'),
            ('Other', 'no', 'other.jpg')";
        $conn->query($sql);
    }
}

// Query to get all manufacturers
$query = "SELECT * FROM manufacturers ORDER BY manufacturer_title";
$result = $conn->query($query);

// Prepare response
$manufacturers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $manufacturers[] = $row;
    }
}

// Return JSON response
echo json_encode($manufacturers);

$conn->close();
?>