<?php
// This script checks the schema of the products table to help with debugging

// Database connection
$conn = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get table schema
$schema_query = "DESCRIBE products";
$schema_result = mysqli_query($conn, $schema_query);

echo "<h2>Products Table Schema</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = mysqli_fetch_assoc($schema_result)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Get a sample product to see the actual data structure
$sample_query = "SELECT * FROM products LIMIT 1";
$sample_result = mysqli_query($conn, $sample_query);

if ($sample_result && mysqli_num_rows($sample_result) > 0) {
    $sample = mysqli_fetch_assoc($sample_result);
    
    echo "<h2>Sample Product Data</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    
    foreach ($sample as $field => $value) {
        echo "<tr>";
        echo "<td>" . $field . "</td>";
        echo "<td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No products found in the database.</p>";
}

mysqli_close($conn);
?>
