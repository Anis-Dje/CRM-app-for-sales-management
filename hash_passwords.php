<?php
require_once 'config.php';

$result = mysqli_query($con, "SELECT customer_id, password FROM customers");
while ($row = mysqli_fetch_assoc($result)) {
    // Check if the password is already hashed (to avoid re-hashing)
    if (!password_get_info($row['password'])['algo']) {
        $hashed_password = password_hash($row['password'], PASSWORD_DEFAULT);
        $customer_id = $row['customer_id'];
        $update_query = "UPDATE customers SET password = ? WHERE customer_id = ?";
        $stmt = mysqli_prepare($con, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $customer_id);
        mysqli_stmt_execute($stmt);
    }
}

echo "Passwords have been hashed successfully.";
mysqli_close($con);
?>