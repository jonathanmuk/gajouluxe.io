<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'cart.php';
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT p.*, c.id as cart_id, c.quantity, c.color, c.size
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCartPrice = array_sum(array_map(function($item) {
    return $item['price'] * $item['quantity'];
}, $cartItems));

// Fetch saved for later items
$stmt = $pdo->prepare("
    SELECT p.*, s.id as saved_id
    FROM saved_for_later s
    JOIN products p ON s.product_id = p.id
    WHERE s.user_id = ?
");
$stmt->execute([$userId]);
$savedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getCartItemCount() {
    global $pdo, $userId;
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: 0;
}
// Function to get wishlist item count
function getWishlistItemCount() {
    global $pdo;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchColumn();
    }
    return 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Gajou Luxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #e9ecef;
        }
        .header {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            padding: 15px 0;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            text-decoration: none;
        }
        .nav-buttons {
            display: flex;
            align-items: center;
        }
        .nav-link {
            color: #333;
            text-decoration: none;
            margin-left: 20px;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #44c7d4;
        }
        .cart-item {
            transition: all 0.3s ease;
            background-color:#f8f9fa;
        }
        .cart-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .quantity-input {
            width: 40px;
            text-align: center;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin: 0 5px;
        }
        .security-badges {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .security-badge {
            margin: 0 10px;
        }
        .payment-methods {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .payment-method {
            margin: 0 10px;
        }
        .footer {
            background-color: #e9ecef;
            color: black;
            padding: 20px 0;
            margin-top: 50px;
        }
        .flaticon {
            width: 24px;
            height: 24px;
            display: inline-block;
            vertical-align: middle;
        }
        .payment-method{
            width: 40px;
            height: 40px;
            display: inline-block;
            vertical-align: middle;
        }
        .security-badge{
            width: 40px;
            height: 40px;
            display: inline-block;
            vertical-align: middle;
        }
        .PCI-badge{
            width: 90px;
            height: 35px;
            display: inline-block;
            vertical-align: middle;  
        }
        .wishlist-icon {
            position: relative;
            display: inline-block;
        }
        .wishlist-badge {
        position: absolute;
        top: -2px;
        right: 2px;
        background-color: #44c7d4;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        }
        .user-info {
        display: flex;
        align-items: center;
        margin-left: 20px;
        }

        .username-link {
        color: #333;
        text-decoration: none;
        margin-left: 10px;
        transition: color 0.3s ease;
        }

        .username-link:hover {
        color: #44c7d4;
        }
        .cart-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .cart-actions button {
            flex: 1;
            min-width: 120px;
        }
        .quantity-input-group {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .shipping-returns-info {
            background-color: #f8f9fa;
            padding: 40px 0;
            margin-top: 50px;
        }

        #shipping-option {
            appearance: menulist;
            -webkit-appearance: menulist;
            -moz-appearance: menulist;
        }
        .progress-bar-wrapper {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            padding: 20px;  
            border-radius: 5px;
            position: relative;
        }

        .progress-step {
            flex: 1;
            text-align: center;
            color: #6c757d;
            position:relaive;
            z-index:1; 
        }

        .progress-step.active {
            color: #000;
            font-weight: bold;
        }
        .progress-step::before {
            content: attr(data-step);
            display: block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            background-color: #6c757d;
            color: #fff;
            border-radius: 50%;
            margin: 0 auto 10px;
        }

        .progress-step.active::before {
            background-color: #1d8e4b;
        }
        .progress-line {
            height: 2px;
            background-color: #6c757d;
            position: absolute;
            top: 35px;
            left: 18%;
            right: 18%;
            z-index: -1;
        }

        .empty-cart {
            text-align: center;
            padding: 50px 0;
        }

        .empty-cart i {
            font-size: 64px;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .quantity-btn {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            padding: 0;
            font-size: 16px;
            line-height: 1;
        }

        .cart-actions button {
            padding: 5px 10px;
            font-size: 14px;
            width: auto;
            min-width:0;
        }
        .payment-security-section {
            background-color: #e9ecef;
            padding: 40px 0;
            margin-top: 50px;
            margin-bottom: 20px;
        }

        .payment-methods, .security-badges {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .saved-for-later {
            text-align: center;
            margin-top: 50px;
        }

        .saved-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .saved-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .saved-item:hover {
            transform: translateY(-5px);
        }

        .saved-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .saved-item-info {
            padding: 10px;
        }

        .saved-item-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .saved-item-price {
            color: #44c7d4;
            font-weight: bold;
        }
        .order-summary {
            border: 2px solid #000;
            border-radius: 8px;
            padding: 20px;
            background-color:#f8f9fa;
        }
        .payment-methodairtel{
            width: 40px;
            height: 45px;
            display: inline-block;
            vertical-align: middle;
            margin-left:10px;
        }
        .payment-methodmtn{
            width: 40px;
            height: 20px;
            display: inline-block;
            vertical-align: middle;
        }
        .payment-methodpaypal{
            width: 55px;
            height: 51px;
            display: inline-block;
            vertical-align: middle;
            margin-right:15px;
            margin-left:10px;
        }
        .subtotal {
            font-weight: bold;
            margin-top: 10px;
        }
        .footer a{
            text-decoration:none;
            color:black;
        }
        .footer a:hover{
             color:#44c7d4;
        }
        .performance-section {
            background-color: #e9ecef;
            padding: 40px 0;
        }
        .performance-item {
            text-align: center;
            transition: transform 0.3s ease;
        }
        .performance-item:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="index.php" class="company-name">Gajou Luxe</a>
            <nav class="nav-buttons">
                <a class="nav-link" href="homepage.html">Home</a>
                <a class="nav-link" href="products.php">Products</a> 
                <a href="<?php echo isset($_SESSION['user_id']) ? 'wishlist.php' : 'login.php'; ?>" class="nav-link wishlist-icon">
                    <img src="icons/heart.png" alt="wishlist" class="flaticon">
                    <span class="wishlist-badge"><?php echo isset($_SESSION['user_id']) ? getWishlistItemCount() : '0'; ?></span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info">
                        <a href="account_settings.php" class="icon-button">
                        <img src="icons/user.png" alt="User" class="flaticon">
                        </a>
                        <a href="account_settings.php" class="username-link"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
                    </div>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                    <a class="nav-link" href="signup.php">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>

<div class="container mt-5">
        <div class="progress-bar-wrapper">
            <div class="progress-step active" data-step="1">
                <i class="fas fa-shopping-cart"></i>
                <span>Shopping Cart</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" data-step="2">
                <i class="fas fa-credit-card"></i>
                <span>Checkout</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" data-step="3">
                <i class="fas fa-check-circle"></i>
                <span>Order Status</span>
            </div>
        </div>


        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your Shopping Cart is empty</h3>
                <a href="products.php" class="btn btn-dark mt-3">Shop Now</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="card mb-3 cart-item" data-aos="fade-right">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="<?php echo $item['image_url']; ?>" class="img-fluid rounded-start" alt="<?php echo $item['name']; ?>">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $item['name']; ?></h5>
                                        <p class="card-text"><?php echo $item['description']; ?></p>
                                        <p class="card-text"><small class="text-muted">Price: $<?php echo number_format($item['price'], 2); ?></small></p>
                                        
                                        <?php if ($item['color']): ?>
                                            <div class="form-group">
                                                <label for="color-<?php echo $item['cart_id']; ?>">Color:</label>
                                                <select class="form-control" id="color-<?php echo $item['cart_id']; ?>" name="color">
                                                    <?php
                                                    $colors = explode(',', $item['available_colors']);
                                                    foreach ($colors as $color) {
                                                        $selected = ($color == $item['color']) ? 'selected' : '';
                                                        echo "<option value='$color' $selected>$color</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($item['size']): ?>
                                            <div class="form-group">
                                                <label for="size-<?php echo $item['cart_id']; ?>">Size:</label>
                                                <select class="form-control" id="size-<?php echo $item['cart_id']; ?>" name="size">
                                                    <?php
                                                    $sizes = explode(',', $item['available_sizes']);
                                                    foreach ($sizes as $size) {
                                                        $selected = ($size == $item['size']) ? 'selected' : '';
                                                        echo "<option value='$size' $selected>$size</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php endif; ?>

                                        <div class="cart-actions">
                                            <div class="quantity-input-group">
                                                <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" data-product-id="<?php echo $item['id']; ?>" data-color="<?php echo $item['color']; ?>" data-size="<?php echo $item['size']; ?>">
                                            </div>
                                            <button class="btn btn-outline-danger btn-sm remove-from-cart" data-product-id="<?php echo $item['id']; ?>" data-color="<?php echo $item['color']; ?>" data-size="<?php echo $item['size']; ?>">Remove</button>
                                            <button class="btn btn-dark btn-sm save-for-later" data-product-id="<?php echo $item['id']; ?>">Save for Later</button>
                                        </div>
                                        <p class="subtotal">Subtotal: $<span class="item-subtotal"><?php echo number_format($item['price'] * $item['quantity'], 2); ?></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button class="btn btn-danger btn-sm clear-cart">Clear Cart</button>
                </div>

            <div class="col-md-4">
                <div class="card order-summary" data-aos="fade-left">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <p class="card-text">Total Items: <?php echo array_sum(array_column($cartItems, 'quantity')); ?></p>
                        <p class="card-text">Subtotal: $<?php echo number_format($totalCartPrice, 2); ?></p>
                        <div class="form-group">
                            <label for="shipping-option">Shipping:</label>
                            <select class="form-control" id="shipping-option">
                                <option value="standard">Standard Shipping - $5.99 (5-7 business days)</option>
                                <option value="express">Express Shipping - $12.99 (2-3 business days)</option>
                            </select>
                        </div>
                        <p class="card-text mt-3">Total: $<span id="total-price"><?php echo number_format($totalCartPrice + 5.99, 2); ?></span></p>
                        <a href = "checkout.php"><button class="btn btn-success w-100 mb-3">Proceed to Checkout</button></a>
                        <a href="products.php" class="btn btn-outline-dark w-100">Continue Shopping</a>
                    </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($savedItems)): ?>
    <div class="saved-for-later">
        <div class="container">
            <h3>Saved for Later</h3>
            <div class="saved-items-grid">
                <?php foreach ($savedItems as $item): ?>
                    <div class="saved-item">
                        <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>">
                        <div class="saved-item-info">
                            <div class="saved-item-title"><?php echo $item['name']; ?></div>
                            <div class="saved-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                            <button class="btn btn-primary btn-sm mt-2 move-to-cart" data-product-id="<?php echo $item['id']; ?>">Move to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
                </div>
    <!-- Performance Section -->
    <section class="performance-section wow fadeIn" data-wow-delay="0.3s">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="performance-item hover-effect">
                            <i class="fas fa-shipping-fast fa-3x mb-3"></i>
                            <h4>Fast Shipping</h4>
                            <p>We deliver your products in record time</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="performance-item hover-effect">
                            <i class="fas fa-user-shield fa-3x mb-3"></i>
                            <h4>Secure Payments</h4>
                            <p>Your transactions are always safe with us</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="performance-item hover-effect">
                            <i class="fas fa-gem fa-3x mb-3"></i>
                            <h4>Genuine Products</h4>
                            <p>We guarantee the authenticity of all our products</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>


    <div class="shipping-returns-info">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h4>Shipping Policy</h4>
                    <p>We offer the following shipping options:</p>
                    <ul>
                        <li>Standard Shipping: $5.99 (5-7 business days)</li>
                        <li>Express Shipping: $12.99 (2-3 business days)</li>
                    </ul>
                    <p>Free shipping on orders over $100. Please note that delivery fees may vary based on your location. For more detailed information, please contact our customer service.</p>
                </div>
                <div class="col-md-6">
                    <h4>Returns & Refunds</h4>
                    <p>We want you to be completely satisfied with your purchase. If you're not happy with your order, we offer easy returns within 30 days of purchase.</p>
                    <p>To be eligible for a return, your item must be unused and in the same condition that you received it. It must also be in the original packaging.</p>
                    <p>Once we receive your item, we will inspect it and notify you that we have received your returned item. We will immediately notify you on the status of your refund after inspecting the item.</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Gajou Luxe</h5>
                    <p>Luxury fashion at your fingertips.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="homepage.html">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="account_settings.php">My Account</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p>Email: info@gajouluxe.com</p>
                    <p>Phone: (123) 456-7890</p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-12 text-center">
                    <p>&copy; 2024 Gajou Luxe. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();

        $('.quantity-decrease').on('click', function() {
            updateQuantity($(this).siblings('.quantity-input'), -1);
        });

        $('.quantity-increase').on('click', function() {
            updateQuantity($(this).siblings('.quantity-input'), 1);
        });
        function updateQuantity(input, change) {
            let newQuantity = parseInt(input.val()) + change;
            if (newQuantity > 0) {
                input.val(newQuantity);
                updateCart(input.data('product-id'), newQuantity, input.data('color'), input.data('size'));
            }
        }

        function updateCart(productId, quantity, color, size) {
            $.ajax({
                url: 'update_cart.php',
                method: 'POST',
                data: { product_id: productId, quantity: quantity, color: color, size: size },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }

        $('.remove-from-cart').on('click', function() {
            const productId = $(this).data('product-id');
            const color = $(this).data('color');
            const size = $(this).data('size');
            const itemElement = $(this).closest('.cart-item');
            
            $.ajax({
                url: 'remove_from_cart.php',
                method: 'POST',
                data: { product_id: productId, color: color, size: size },
                success: function(response) {
                    if (response.success) {
                        itemElement.fadeOut(300, function() { $(this).remove(); });
                        location.reload();
                    }
                }
            });
        });

        $('.save-for-later').on('click', function() {
            const productId = $(this).data('product-id');
            const itemElement = $(this).closest('.cart-item');
            
            $.ajax({
                url: 'save_for_later.php',
                method: 'POST',
                data: { product_id: productId },
                success: function(response) {
                    if (response.success) {
                        itemElement.fadeOut(300, function() { $(this).remove(); });
                        location.reload();
                    }
                }
            });
        });

        $('.move-to-cart').on('click', function() {
            const productId = $(this).data('product-id');
            const itemElement = $(this).closest('.saved-item');
            
            $.ajax({
                url: 'move_to_cart.php',
                method: 'POST',
                data: { product_id: productId },
                success: function(response) {
                    if (response.success) {
                        itemElement.fadeOut(300, function() { $(this).remove(); });
                        location.reload();
                    }
                }
            });
        });

        $('#shipping-option').on('change', function() {
            const shippingCost = $(this).val() === 'express' ? 12.99 : 5.99;
            const subtotal = <?php echo $totalCartPrice; ?>;
            const total = subtotal + shippingCost;
            $('#total-price').text(total.toFixed(2));
        });

        $('select[name="color"], select[name="size"]').on('change', function() {
            const productId = $(this).closest('.cart-item').find('.quantity-input').data('product-id');
            const color = $(this).closest('.cart-item').find('select[name="color"]').val();
            const size = $(this).closest('.cart-item').find('select[name="size"]').val();
            const quantity = $(this).closest('.cart-item').find('.quantity-input').val();
            
            updateCart(productId, quantity, color, size);
        });
        $('.quantity-input').on('change', function() {
            let newQuantity = parseInt($(this).val());
            let price = parseFloat($(this).data('price'));
            if (newQuantity > 0) {
                updateCart($(this).data('product-id'), newQuantity, $(this).data('color'), $(this).data('size'));
                $(this).closest('.card-body').find('.item-subtotal').text((price * newQuantity).toFixed(2));
                updateOrderSummary();
            }
        });

        $('.clear-cart').on('click', function() {
            if (confirm('Are you sure you want to clear your cart?')) {
                $.ajax({
                    url: 'clear_cart.php',
                    method: 'POST',
                    success: function(response) {
                        if (response.success) {
                            $('#cart-content').html(`
                                <div class="empty-cart">
                                    <i class="fas fa-shopping-cart"></i>
                                    <h3>Your Shopping Cart is empty</h3>
                                    <a href="products.php" class="btn btn-primary mt-3">Shop Now</a>
                                </div>
                            `);
                        }
                    }
                });
            }
        });
        function updateOrderSummary() {
            let totalItems = 0;
            let subtotal = 0;
            $('.quantity-input').each(function() {
                totalItems += parseInt($(this).val());
                subtotal += parseFloat($(this).val()) * parseFloat($(this).data('price'));
            });
            $('#total-items').text(totalItems);
            $('#subtotal').text(subtotal.toFixed(2));
            updateTotal();
        }

        function updateTotal() {
            let subtotal = parseFloat($('#subtotal').text());
            let shippingCost = $('#shipping-option').val() === 'express' ? 12.99 : 5.99;
            let total = subtotal + shippingCost;
            $('#total-price').text(total.toFixed(2));
        }

        $('#shipping-option').on('change', updateTotal);
    </script>
</body>
</html>


        
