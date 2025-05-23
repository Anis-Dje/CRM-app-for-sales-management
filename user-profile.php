<?php
session_start();

// Database connection
$con = mysqli_connect("localhost:3307", "root", "", "ecom_store");

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: auth.php");
    exit();
}

// Fetch user details
$customer_id = $_SESSION['customer_id'];
$query = "SELECT customer_name, email, customer_country, customer_city, customer_address, customer_image, customer_contact, customer_points 
          FROM customers 
          WHERE customer_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: auth.php");
    exit();
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechMarket - User Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #333;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .main-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f0f2f5;
        }

        .navbar {
            background-color: #0e2a3b;
            color: white;
            padding: 40px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar .container {
            display: flex;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 35px;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }

        .logo i {
            margin-right: 10px;
            color: #4ade80;
        }

        .text-green {
            color: #4ade80;
        }

        .tagline {
            margin-left: 15px;
            font-size: 20px;
            padding-left: 15px;
            border-left: 1px solid rgba(255, 255, 255, 0.3);
        }

        .profile-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .profile-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            padding: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 28px;
        }

        .profile-info {
            margin-bottom: 20px;
        }

        .profile-info label {
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }

        .profile-info p {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            color: #333;
        }

        .profile-image {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-image img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0e2a3b;
        }

        .btn-logout {
            width: 100%;
            padding: 12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-logout:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .navbar .container {
                flex-direction: column;
                align-items: flex-start;
            }

            .tagline {
                margin-left: 0;
                margin-top: 5px;
                padding-left: 0;
                border-left: none;
            }

            .profile-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <nav class="navbar">
            <div class="container">
                <a href="index.php" class="logo">
                    <i class="fas fa-shopping-cart"></i>
                    <span><span class="text-green">Tech</span>Market</span>
                </a>
                <div class="tagline">Your Gateway to Tech Innovation</div>
            </div>
        </nav>

        <div class="profile-wrapper">
            <div class="profile-container">
                <h1>User Profile</h1>
                <div class="profile-image">
                    <img src="<?php echo !empty($user['customer_image']) ? 'customer_images/' . htmlspecialchars($user['customer_image']) : 'https://via.placeholder.com/150'; ?>" alt="Profile Image">
                </div>
                <div class="profile-info">
                    <label>Name</label>
                    <p><?php echo htmlspecialchars($user['customer_name']); ?></p>
                </div>
                <div class="profile-info">
                    <label>Email</label>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="profile-info">
                    <label>Country</label>
                    <p><?php echo htmlspecialchars($user['customer_country'] ?: 'Not specified'); ?></p>
                </div>
                <div class="profile-info">
                    <label>City</label>
                    <p><?php echo htmlspecialchars($user['customer_city'] ?: 'Not specified'); ?></p>
                </div>
                <div class="profile-info">
                    <label>Address</label>
                    <p><?php echo htmlspecialchars($user['customer_address'] ?: 'Not specified'); ?></p>
                </div>
                <div class="profile-info">
                    <label>Contact</label>
                    <p><?php echo htmlspecialchars($user['customer_contact'] ?: 'Not specified'); ?></p>
                </div>
                <div class="profile-info">
                    <label>Points</label>
                    <p><?php echo htmlspecialchars($user['customer_points']); ?></p>
                </div>
                <form method="post" action="">
                    <button type="submit" name="logout" class="btn-logout">Logout</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>