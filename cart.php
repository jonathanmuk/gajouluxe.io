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

$totalCartPrice = array_sum(array_column($cartItems, 'total_price'));
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
        .cart-item {
            transition: all 0.3s ease;
        }
        .cart-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .quantity-input {
            width: 60px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">My Cart</h2>
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
                    <p class="card-text"><small class="text-muted">Color: <?php echo $item['color'] ?? 'N/A'; ?></small></p>
<p class="card-text"><small class="text-muted">Size: <?php echo $item['size'] ?? 'N/A'; ?></small></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="input-group quantity-input-group" style="width: 120px;">
                            <button class="btn btn-outline-secondary quantity-decrease" type="button">-</button>
                            <input type="text" class="form-control text-center quantity-input" value="<?php echo $item['quantity']; ?>" data-product-id="<?php echo $item['id']; ?>" data-color="<?php echo $item['color']; ?>" data-size="<?php echo $item['size']; ?>">
                            <button class="btn btn-outline-secondary quantity-increase" type="button">+</button>
                        </div>
                        <button class="btn btn-danger remove-from-cart" data-product-id="<?php echo $item['id']; ?>" data-color="<?php echo $item['color']; ?>" data-size="<?php echo $item['size']; ?>">Remove</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
            </div>
            <div class="col-md-4">
                <div class="card" data-aos="fade-left">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <p class="card-text">Total Items: <?php echo count($cartItems); ?></p>
                        <p class="card-text">Total Price: $<?php echo number_format($totalCartPrice, 2); ?></p>
                        <button class="btn btn-primary w-100">Proceed to Checkout</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                updateCart(input.data('product-id'), newQuantity);
            }
        }

        function updateCart(productId, quantity) {
            $.ajax({
                url: 'update_cart.php',
                method: 'POST',
                data: { product_id: productId, quantity: quantity },
                success: function(response) {
                    if (response.success) {
                        // Update total price
                        location.reload(); // For simplicity, we're reloading the page. In a real app, you'd update the UI dynamically.
                    }
                }
            });
        }

        $('.remove-from-cart').on('click', function() {
            const productId = $(this).data('product-id');
            const itemElement = $(this).closest('.cart-item');
            
            $.ajax({
                url: 'remove_from_cart.php',
                method: 'POST',
                data: { product_id: productId },
                success: function(response) {
                    if (response.success) {
                        itemElement.fadeOut(300, function() { $(this).remove(); });
                        location.reload(); // Update totals
                    }
                }
            });
        });
    </script>
</body>
</html>