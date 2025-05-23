<?php
session_start();

// Database configuration
$host = 'localhost:3307';
$user = 'root';
$pass = '';
$dbname = 'ecom_store';

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize message variables
$login_error = '';
$register_message = '';

// Handle login form submission
if (isset($_POST['login_submit'])) {
    $email = trim($_POST['customer_email']);
    $password = $_POST['customer_password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        $login_error = "Email and password are required.";
    } else {
        // Sanitize email
        $email = $conn->real_escape_string($email);

        // Check if the user is an admin
        $admin_stmt = $conn->prepare("SELECT admin_id, admin_name, admin_email, admin_pass, admin_image FROM admins WHERE admin_email = ?");
        $admin_stmt->bind_param("s", $email);
        $admin_stmt->execute();
        $admin_result = $admin_stmt->get_result();

        if ($admin_result->num_rows > 0) {
            $admin = $admin_result->fetch_assoc();
            // Verify plain text password (as per database dump)
            if ($password === $admin['admin_pass']) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                // Set admin session variables
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['admin_name'];
                $_SESSION['admin_email'] = $admin['admin_email'];
                $_SESSION['admin_image'] = $admin['admin_image'];
                $admin_stmt->close();
                header("Location: dashboard.php");
                exit;
            } else {
                $login_error = "Invalid password for admin account.";
            }
        } else {
            // Check customers table for non-admin users
            $customer_stmt = $conn->prepare("SELECT customer_id, customer_name, email, password FROM customers WHERE email = ?");
            $customer_stmt->bind_param("s", $email);
            $customer_stmt->execute();
            $customer_result = $customer_stmt->get_result();

            if ($customer_result->num_rows > 0) {
                $customer = $customer_result->fetch_assoc();
                // Verify hashed password for customers
                if (password_verify($password, $customer['password'])) {
                    // Regenerate session ID
                    session_regenerate_id(true);
                    // Set customer session variables
                    $_SESSION['customer_id'] = $customer['customer_id'];
                    $_SESSION['customer_name'] = $customer['customer_name'];
                    $_SESSION['customer_email'] = $customer['email'];
                    $customer_stmt->close();
                    header("Location: index.php");
                    exit;
                } else {
                    $login_error = "Invalid password.";
                }
            } else {
                $login_error = "No account found with that email.";
            }
            $customer_stmt->close();
        }
        $admin_stmt->close();
    }
}

// Handle registration form submission
if (isset($_POST['register_submit'])) {
    $name = trim($_POST['customer_name']);
    $email = trim($_POST['customer_email']);
    $password = $_POST['customer_password'];
    $country = trim($_POST['customer_country']);
    $city = trim($_POST['customer_city']);

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($country) || empty($city)) {
        $register_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_message = "Invalid email format.";
    } else {
        // Sanitize inputs
        $name = $conn->real_escape_string($name);
        $email = $conn->real_escape_string($email);
        $country = $conn->real_escape_string($country);
        $city = $conn->real_escape_string($city);

        // Check if email already exists in customers
        $check_email = $conn->prepare("SELECT email FROM customers WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $email_result = $check_email->get_result();

        if ($email_result->num_rows > 0) {
            $register_message = "Error: This email is already registered.";
        } else {
            // Hash password for customers
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            // Insert new customer
            $stmt = $conn->prepare("INSERT INTO customers (customer_name, email, password, customer_country, customer_city, customer_address, customer_image, customer_ip, customer_confirm_code, customer_points) VALUES (?, ?, ?, ?, ?, '', '', '', '', 0)");
            $stmt->bind_param("sssss", $name, $email, $password_hashed, $country, $city);

            if ($stmt->execute()) {
                $register_message = "Registration successful! Please login.";
                // Auto-login
                $customer_id = $conn->insert_id;
                session_regenerate_id(true);
                $_SESSION['customer_id'] = $customer_id;
                $_SESSION['customer_name'] = $name;
                $_SESSION['customer_email'] = $email;
                $stmt->close();
                header("Location: index.php");
                exit;
            } else {
                $register_message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_email->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechMarket - Login/Register</title>
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

        /* Main Container */
        .main-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f0f2f5;
        }

        /* Navbar Styles */
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

        /* Authentication Wrapper */
        .auth-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        /* Authentication Container */
        .auth-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 480px;
            padding: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 28px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group input {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 95%;
            padding-top: 12px;
            padding-right: 15px;
            padding-bottom: 12px;
            padding-left: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #e9ecef;
            font-size: 16px;
        }

        .input-group input:focus {
            outline: none;
            border-color: #0e2a3b;
            background-color: #fff;
        }

        .input-icon {
            position: absolute;
            right: -12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .checkbox-group input {
            margin-right: 10px;
            width: 18px;
            height: 18px;
        }

        .checkbox-group label {
            font-size: 14px;
            color: #555;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #0e2a3b;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #0a1f2d;
        }

        .error-message, .success-message {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
        }

        .form-switch {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #555;
        }

        .form-switch a {
            color: #0e2a3b;
            text-decoration: none;
            font-weight: bold;
        }

        .form-switch a:hover {
            text-decoration: underline;
        }

        /* Responsive Styles */
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
            
            .auth-container {
                padding-left: 35px;
                padding-right: 35px;
                padding-top: 50px;
                padding-bottom: 60px;
            }
        }
    </style>
</head>
<body>
    <!-- Main Container -->
    <div class="main-container">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <i class="fas fa-shopping-cart"></i>
                    <span><span class="text-green">Tech</span>Market</span>
                </div>
                <div class="tagline">Your Gateway to Tech Innovation</div>
            </div>
        </nav>
        
        <!-- Authentication Container -->
        <div class="auth-wrapper">
            <!-- Login Form -->
            <div class="auth-container" id="login-form">
                <h1>Login</h1>
                <?php if ($login_error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($login_error); ?></div>
                <?php endif; ?>
                <form method="post" action="">
                    <div class="input-group">
                        <input type="email" name="customer_email" placeholder="Email" required>
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    </div>
                    
                    <div class="input-group">
                        <input type="password" name="customer_password" placeholder="Password" required>
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                    </div>
                    
                    <button type="submit" name="login_submit" class="btn-submit">Login</button>
                </form>
                
                <p class="form-switch">Don't have an account? <a href="#" id="show-register">Register</a></p>
            </div>
            
            <!-- Registration Form -->
            <div class="auth-container" id="register-form" style="display: none;">
                <h1>Registration</h1>
                <?php if ($register_message): ?>
                    <div class="<?php echo strpos($register_message, 'Error') === false ? 'success-message' : 'error-message'; ?>">
                        <?php echo htmlspecialchars($register_message); ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="">
                    <div class="input-group">
                        <input type="text" name="customer_name" placeholder="Name" required>
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                    </div>
                    
                    <div class="input-group">
                        <input type="password" name="customer_password" placeholder="Password" required>
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                    </div>
                    
                    <div class="input-group">
                        <input type="text" name="customer_country" placeholder="Country" required>
                        <span class="input-icon"><i class="fas fa-globe"></i></span>
                    </div>
                    
                    <div class="input-group">
                        <input type="text" name="customer_city" placeholder="City" required>
                        <span class="input-icon"><i class="fas fa-globe"></i></span>
                    </div>
                    
                    <div class="input-group">
                        <input type="email" name="customer_email" placeholder="Email" required>
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">By continuing, you agree to the Tech Market Terms of Use</label>
                    </div>
                    
                    <button type="submit" name="register_submit" class="btn-submit">Register</button>
                </form>
                
                <p class="form-switch">Already have an account? <a href="#" id="show-login">Login</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle between login and register forms
        document.getElementById('show-register').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = 'block';
        });

        document.getElementById('show-login').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('register-form').style.display = 'none';
            document.getElementById('login-form').style.display = 'block';
        });
    </script>
</body>
</html>