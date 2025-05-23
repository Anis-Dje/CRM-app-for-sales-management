<?php
session_start();

// Check if user is authenticated
if (!isset($_SESSION['customer_id'])) {
    header("Location: auth.php");
    exit;
}

// Database connection
include 'C:/xampp/htdocs/PPD/php/config.php';

if (!$conn) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// Fetch cart items for the authenticated user
$customer_id = $_SESSION['customer_id'];
$query_cart = "
    SELECT c.*, p.product_title, p.product_price, p.stock 
    FROM cart c
    LEFT JOIN products p ON c.p_id = p.product_id
    WHERE c.customer_id = ?
    AND c.status = 'WAITING'";
$stmt = $conn->prepare($query_cart);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result_cart = $stmt->get_result();

$total_amount = 0; // To calculate total cart value

// Debug information
echo "<!-- Debug: Customer ID = $customer_id -->";
echo "<!-- Debug: Query result rows = " . $result_cart->num_rows . " -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechMarket - Your Cart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional cart-specific styles */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            color: #0e2a3b;
        }
        .cart-table th, .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            color: #0e2a3b;
        }
        .cart-table th {
            background-color: #0e2a3b;
            color: white;
        }
        .cart-table img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .cart-table .quantity {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #0e2a3b;
        }
        .cart-table .btn-remove {
            color: #dc3545;
            cursor: pointer;
            text-decoration: none;
        }
        .cart-table .btn-remove:hover {
            text-decoration: underline;
        }
        .cart-total {
            margin-top: 20px;
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #0e2a3b;
        }
        .checkout-btn {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 12px;
            background-color: #0e2a3b;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .checkout-btn:hover {
            background-color: #0a1f2d;
        }
        .empty-cart {
            text-align: center;
            color: #555;
            font-size: 18px;
            margin-top: 20px;
        }
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #0e2a3b;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
            vertical-align: middle;
            visibility: hidden;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Message styles */
        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .message.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .message.error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #ef4444;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #0e2a3b;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
            <div class="logo">
                    <a href="index.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span><span class="text-green">Tech</span>Market</span>
                    </a>
                </div>
                <div class="header-icons">
                    <a href="index.php"><i class="fas fa-home"></i>Home</a>
                    <a href="cart.php"><i class="fas fa-shopping-cart"></i>Cart</a>
                    <a href="user-profile.php"><i class="fas fa-user"></i><?php echo htmlspecialchars($_SESSION['customer_name']); ?></a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <h1>Your Cart</h1>
            
            <!-- Message container for notifications -->
            <div id="message-container"></div>
            
            <?php if ($result_cart->num_rows > 0): ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Image</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_cart->fetch_assoc()): 
                            // Get product image from product_images table
                            $cart_product_id = $row['p_id'];
                            $cart_image_query = "SELECT image_path FROM product_images WHERE product_id = $cart_product_id ORDER BY is_primary DESC LIMIT 1";
                            $cart_img_result = $conn->query($cart_image_query);
                            if ($cart_img_result && $cart_img_result->num_rows > 0) {
                                $cart_img_row = $cart_img_result->fetch_assoc();
                                $product_img1 = $cart_img_row['image_path'];
                                // Check if the path already includes 'product_images/'
                                if (strpos($product_img1, 'product_images/') === false) {
                                    $product_img1 = 'product_images/' . $product_img1;
                                }
                            } else {
                                $product_img1 = 'https://via.placeholder.com/80x80';
                            }
                            $subtotal = $row['p_price'] * $row['qty'];
                            $total_amount += $subtotal;
                        ?>
                            <tr id="cart-row-<?php echo $row['p_id']; ?>">
                                <td><?php echo htmlspecialchars($row['product_title']); ?></td>
                                <td><img src="<?php echo htmlspecialchars($product_img1); ?>" alt="<?php echo htmlspecialchars($row['product_title']); ?>"></td>
                                <td><?php echo number_format($row['p_price'], 0) . ' D.A'; ?></td>
                                <td>
                                    <input type="number" class="quantity" value="<?php echo htmlspecialchars($row['qty']); ?>" 
                                           min="1" max="<?php echo $row['stock']; ?>" 
                                           data-product-id="<?php echo htmlspecialchars($row['p_id']); ?>"
                                           data-price="<?php echo $row['p_price']; ?>">
                                    <span class="spinner" id="spinner-<?php echo $row['p_id']; ?>"></span>
                                </td>
                                <td class="subtotal" id="subtotal-<?php echo $row['p_id']; ?>"><?php echo number_format($subtotal, 0) . ' D.A'; ?></td>
                                <td>
                                    <a href="#" class="btn-remove" data-product-id="<?php echo htmlspecialchars($row['p_id']); ?>">Remove</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="cart-total" id="cart-total">
                    Total: <?php echo number_format($total_amount, 0) . ' D.A'; ?>
                </div>
                <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
            <?php else: ?>
                <p class="empty-cart">Your cart is empty. <a href="index.php">Start shopping now!</a></p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update quantity when input changes
            const quantityInputs = document.querySelectorAll('.quantity');
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const productId = this.getAttribute('data-product-id');
                    const quantity = parseInt(this.value);
                    const price = parseFloat(this.getAttribute('data-price'));
                    const maxStock = parseInt(this.getAttribute('max'));
                    
                    // Validate quantity
                    if (quantity < 1) {
                        this.value = 1;
                        return;
                    }
                    
                    if (quantity > maxStock) {
                        this.value = maxStock;
                        showMessage(`Maximum available stock is ${maxStock}`, 'error');
                        return;
                    }
                    
                    // Show spinner
                    const spinner = document.getElementById(`spinner-${productId}`);
                    spinner.style.visibility = 'visible';
                    
                    // Update cart via AJAX
                    updateCartQuantity(productId, quantity, price, spinner);
                });
            });
            
            // Remove item from cart
            const removeButtons = document.querySelectorAll('.btn-remove');
            removeButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const productId = this.getAttribute('data-product-id');
                    
                    // Confirm removal
                    if (confirm('Are you sure you want to remove this item from your cart?')) {
                        removeFromCart(productId);
                    }
                });
            });
            
            // Function to update cart quantity
            function updateCartQuantity(productId, quantity, price, spinner) {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', quantity);
                
                fetch('update-cart-quantity.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Hide spinner
                    spinner.style.visibility = 'hidden';
                    
                    if (data.success) {
                        // Update subtotal
                        const subtotal = price * quantity;
                        const subtotalElement = document.getElementById(`subtotal-${productId}`);
                        subtotalElement.textContent = formatPrice(subtotal);
                        
                        // Update total
                        document.getElementById('cart-total').textContent = `Total: ${data.total}`;
                        
                        // Show success message
                        showMessage('Cart updated successfully', 'success');
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    spinner.style.visibility = 'hidden';
                    showMessage('An error occurred while updating the cart', 'error');
                });
            }
            
            // Function to remove item from cart
            function removeFromCart(productId) {
                const formData = new FormData();
                formData.append('product_id', productId);
                
                fetch('remove-from-cart.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove row from table
                        const row = document.getElementById(`cart-row-${productId}`);
                        row.remove();
                        
                        // Update total
                        document.getElementById('cart-total').textContent = `Total: ${data.total}`;
                        
                        // Show success message
                        showMessage('Item removed from cart', 'success');
                        
                        // If cart is empty, refresh the page to show empty cart message
                        if (data.count === 0) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred while removing the item', 'error');
                });
            }
            
            // Format price as "X,XXX D.A"
            function formatPrice(price) {
                return price.toLocaleString() + ' D.A';
            }
            
            // Show message function
            function showMessage(message, type) {
                const messageContainer = document.getElementById('message-container');
                const messageElement = document.createElement('div');
                messageElement.className = `message ${type}`;
                messageElement.textContent = message;
                
                // Clear previous messages
                messageContainer.innerHTML = '';
                messageContainer.appendChild(messageElement);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    messageElement.style.opacity = '0';
                    messageElement.style.transition = 'opacity 0.5s';
                    setTimeout(() => {
                        messageContainer.removeChild(messageElement);
                    }, 500);
                }, 3000);
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
mysqli_close($conn);
?>
