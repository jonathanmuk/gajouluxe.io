<?php
session_start();
include 'db_connection.php';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Debug information
echo "<!-- Debug: Session data = " . json_encode($_SESSION) . " -->";
echo "<!-- Debug: User ID = " . ($userId ?? 'null') . " -->";

function getWishlistItems($pdo, $userId = null) {
    if ($userId) {
        // For logged-in users
        $stmt = $pdo->prepare("
            SELECT p.*, w.id as wishlist_id, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
            FROM wishlist w
            JOIN products p ON w.product_id = p.id
            LEFT JOIN reviews r ON p.id = r.product_id
            WHERE w.user_id = ?
            GROUP BY p.id
        ");
        $stmt->execute([$userId]);
    } else {
        // For non-logged-in users
        if (!isset($_SESSION['wishlist']) || empty($_SESSION['wishlist'])) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($_SESSION['wishlist']), '?'));
        $stmt = $pdo->prepare("
            SELECT p.*, p.id as wishlist_id, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
            FROM products p
            LEFT JOIN reviews r ON p.id = r.product_id
            WHERE p.id IN ($placeholders)
            GROUP BY p.id
        ");
        $stmt->execute(array_values($_SESSION['wishlist']));

    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getCartItemCount($pdo, $userId = null) {
    if ($userId) {
        // For logged-in users (no change)
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetchColumn();
        echo "<!-- Debug: Logged-in user cart count query result = " . ($count ?? 'null') . " -->";
        return $count ? intval($count) : 0;
    } else {
        // For non-logged-in users
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            echo "<!-- Debug: Non-logged-in user, no cart in session -->";
            return 0;
        }
        $count = count($_SESSION['cart']); // Count the number of items, not their sum
        echo "<!-- Debug: Non-logged-in user cart count = $count -->";
        return $count;
    }
}

$cartCount = getCartItemCount($pdo, $userId);
echo "<!-- Debug: Final Cart Count = $cartCount -->";

if ($userId) {
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- Debug: Cart Items = " . json_encode($cartItems) . " -->";
} else {
    echo "<!-- Debug: Session Cart = " . json_encode($_SESSION['cart'] ?? []) . " -->";
}




$wishlistItems = getWishlistItems($pdo, $userId);

// Fetch related products
$relatedProductIds = array_column($wishlistItems, 'id');
if (!empty($relatedProductIds)) {
    $placeholders = implode(',', array_fill(0, count($relatedProductIds), '?'));
    $stmt = $pdo->prepare("
        SELECT p.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
        FROM products p
        LEFT JOIN reviews r ON p.id = r.product_id
        WHERE p.category_id IN (SELECT category_id FROM products WHERE id IN ($placeholders))
        AND p.id NOT IN ($placeholders)
        GROUP BY p.id
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt->execute(array_merge($relatedProductIds, $relatedProductIds));
    $relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $relatedProducts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Gajou Luxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-left: 0;
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
            text-decoration:none;
        }
        .nav-buttons {
            display: flex;
            align-items: center;
        }
        .nav-link {
            color: #333;
            text-decoration: none;
            margin-left: 10px;
            margin-right: 10px;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #44c7d4;
        }
        .icon-button {
            background: none;
            border: none;
            color: #333;
            cursor: pointer;
            margin-left: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s ease;
            position:relative;
        }
        .icon-button:hover {
            color: #44c7d4;
        }
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #44c7d4;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }
        .wishlist-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 30px;
        }
        .wishlist-item {
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .remove-from-wishlist {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .remove-from-wishlist:hover {
            background: #ff9a9e;
            color: white;
        }
        .card {
            border: none;
            border-radius: 8px;
            overflow: hidden;
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .card-body {
            padding: 15px;
        }
        .card-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .card-text {
            font-size: 14px;
            color: #666;
        }
        .btn-primary {
            background-color: #44c7d4;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #3ab4c1;
            transform: translateY(-2px);
        }
        .username-link {
        color: #333;
        text-decoration: none;
        margin-left: 10px;
        transition: color 0.3s ease;
        }
        .user-info {
        display: flex;
        align-items: center;
        margin-left: 20px;
        }
        .username-link:hover {
        color: #44c7d4;
        }
        .sidebar {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        footer {
background-color: #e9ecef;
color: #333;
padding: 60px 0 10px;
}
.footer-title {
font-size: 1.2rem;
font-weight: 700;
margin-bottom: 20px;
color: #444;
}
.footer-text {
font-size: 1rem;
line-height: 1.6;
}
.footer-links a {
color: #666;
text-decoration: none;
transition: color 0.3s ease;
display: block;
margin-bottom: 10px;
}
.footer-links a:hover {
color: #44c7d4;
}
.social-icons a {
color: #666;
font-size: 1.5rem;
margin-right: 15px;
transition: color 0.3s ease;
}
.social-icons a:hover {
color: #44c7d4;
}
.newsletter-form .input-group {
margin-top: 15px;
}
.newsletter-form .form-control {
border-top-right-radius: 0;
border-bottom-right-radius: 0;
}


.newsletter-form .btn {
border-top-left-radius: 0;
border-bottom-left-radius: 0;
background-color: #44c7d4;
border-color: #44c7d4;
}
.newsletter-form .btn:hover {
background-color: #3ab4c1;
border-color: #3ab4c1;
}


.footer-divider {
margin: 30px 0;
border-top: 1px solid #ddd;
}
.copyright {
font-size: 0.9rem;
color: #777;
}
        .wishlist-note {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .wishlist-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .social-share {
            margin-top: 10px;
        }
        .social-share a {
            margin-right: 10px;
            color: #333;
            font-size: 18px;
        }
        .rating {
            color: #ffc107;
        }
        .shipping-info {
            font-size: 12px;
            color: #6c757d;
        }
        .wishlist-summary {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}
.summary-title {
    font-size: 1.4rem;
    color: #333;
    margin-bottom: 15px;
    text-align: center;
    font-weight: 600;
}
.summary-content p {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    font-size: 1.1rem;
    color: #555;
}
.summary-content i {
    margin-right: 10px;
    color: #44c7d4;
}
.share-wishlist {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}
.share-title {
    font-size: 1.2rem;
    color: #333;
    margin-bottom: 15px;
    text-align: center;
    font-weight: 600;
}
.social-share {
    display: flex;
    justify-content: center;
}
.social-share a {
    margin: 0 10px;
    font-size: 1.5rem;
    color: #44c7d4;
    transition: color 0.3s ease;
}
.social-share a:hover {
    color: #3ab4c1;
}
        .sidebar-deals {
        margin-top: 20px;
        padding: 20px;
        background-color: #ffffff;
        border-radius: 10px;
        }
        .deals-title {
    font-size: 1.2rem;
    color: #333;
    margin-bottom: 15px;
    text-align: center;
    font-weight: 600;
}

        .deal-slideshow {
        border-radius: 10px;
        position: relative;
        height: 240px;
        overflow: hidden;
        }

        .deal-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 0.5s ease-in-out;
        }

        .deal-slide.active {
        opacity: 1;
        }

        .deal-slide img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 10px;
        }

        .deal-slide p {
        margin-top: 10px;
        text-align: center;
        font-weight: bold;
        color: #44c7d4;
        }
        .flaticon {
            width: 24px;
            height: 24px;
            display: inline-block;
            vertical-align: middle;
        }

        .product-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .product-actions button {
            background: none;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .product-actions button:hover {
            transform: scale(1.1);
        }
        .social-icons a{
            margin-right: 15px;
        }
        .wishlist-container .text-center {
    padding: 50px 0;
}
.empty-wishlist-message {
    padding: 50px 0;
    text-align: center;
}

.empty-wishlist-message h2 {
    font-size: 48px;
    color: #44c7d4;
    margin-bottom: 20px;
}

.empty-wishlist-message h3 {
    margin-bottom: 15px;
}

.empty-wishlist-message p {
    margin-bottom: 20px;
}

.empty-wishlist-message .btn-dark {
    padding: 10px 30px;
    font-size: 18px;
    color:#44c7d4;
}
@media (min-width: 500px) {
    .container {
        max-width: 98%;
        margin-left: 2.5%;
        margin-right: 2.5%;
    }
}

@media (max-width: 700px) {
    .row {
        flex-direction: column;
    }
    
    .col-md-3 {
        order: -1;
        width: 100%;
        margin-bottom: 20px;
    }
    
    .col-md-9 {
        width: 100%;
    }
    
    .sidebar {
        position: static;
    }
}
@media (max-width: 700px) {
    .wishlist-item {
        width: 100%;
    }
}
@media (max-width: 700px) {
    .wishlist-item {
        width: 100%;
        margin-bottom: 20px;
    }
}






    </style>
</head>
<body>
<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="homepage.php" class="company-name">Gajou Luxe</a>
            <nav class="nav-buttons">
                <a class="nav-link" href="homepage.php">Home</a>
                <a class="nav-link" href="products.php">Products</a>
                <a href="cart.php" class="icon-button">
                    <img src="icons\shopping-cart.png" alt="Cart" class="flaticon">
                    <span class="cart-badge"><?php echo $cartCount; ?></span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info">
                        <a href="account_settings.php" class="icon-button">
                            <img src="icons\user.png" alt="User" class="flaticon">
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

<div class="container mt-4">
    <div class="wishlist-note">
        <p class="mb-0"><i class="fas fa-info-circle"></i> Click on this icon <img src="icons\heart.png" alt="Heart" class="flaticon"> on product pages to add items to your wishlist!</p>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-3">
            <?php if (!empty($wishlistItems)): ?>
                <div class="sidebar">
                    <div class="wishlist-summary">
                        <h4 class="summary-title">Wishlist Summary</h4>
                        <div class="summary-content">
                            <p><i class="fas fa-heart"></i> Total Items: <?php echo count($wishlistItems); ?></p>
                            <p><i class="fas fa-tag"></i> Estimated Total: Shs.<?php echo number_format(array_sum(array_column($wishlistItems, 'price'))); ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="share-wishlist">
                        <h5 class="share-title">Share Your Wishlist</h5>
                        <div class="social-share">
                            <a href="#" onclick="shareWishlist('facebook')"><img src="icons/facebook.png" alt="Facebook" class="flaticon"></a>
                            <a href="#" onclick="shareWishlist('instagram')"><img src="icons/instagram (1).png" alt="Instagram" class="flaticon"></a>
                            <a href="#" onclick="shareWishlist('whatsapp')"><img src="icons/whatsapp.png" alt="WhatsApp" class="flaticon"></a>
                            <a href="#" onclick="shareWishlist('snapchat')"><img src="icons/snapchat.png" alt="Snapchat" class="flaticon"></a>
                            <a href="#" onclick="shareWishlist('tiktok')"><img src="icons/tik-tok.png" alt="Tiktok" class="flaticon"></a>
                        </div>
                    </div>
                    <div class="sidebar-deals">
                        <h4 class="deals-title">Today's Deals</h4>
                        <div class="deal-slideshow">
                            <div class="deal-slide">
                                <img src="images/InShot_20240930_160908194.jpg" alt="Deal 1">
                                <p>Summer Collection 50% Off</p>
                            </div>
                            <div class="deal-slide">
                                <img src="images/photo_2024-08-09_18-05-28.jpg" alt="Deal 2">
                                <p>Buy 2 Get 1 Free</p>
                            </div>
                            <div class="deal-slide">
                                <img src="images/photo_2024-08-08_17-00-36.jpg" alt="Deal 3">
                                <p>Free Shipping on Orders $100+</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
  

    <?php if (empty($wishlistItems)): ?>
        
            <div class="empty-wishlist-message">
                <h2><i class="fas fa-heart"></i></h2>
                <h3>Wishlist is Empty</h3>
                <p>Check out our products and add to your wishlist</p>
                <a href="products.php" class="btn btn-dark">Shop Now</a>
            </div>
        
    <?php else: ?>
            <div class="col-md-9">
                <div class="wishlist-container">
                    
                        <h2 class="wishlist-title">My Wishlist</h2>
                        <div class="row">
                            <?php foreach ($wishlistItems as $item): ?>
                                <?php
                                    echo "<!-- Debug: wishlist_id for this item: " . htmlspecialchars($item['wishlist_id']) . " -->";
                                ?>
                                <div class="col-md-4 col-sm-6 wishlist-item" data-aos="fade-up">
                                    <div class="card h-100" onclick="showProductModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                        <img src="<?php echo $item['image_url']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <button class="remove-from-wishlist" data-wishlist-id="<?php echo htmlspecialchars($item['wishlist_id']); ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?php echo $item['name']; ?></h5>
                                            <p class="card-text flex-grow-1"><?php echo substr($item['description'], 0, 50) . '...'; ?></p>
                                            <p class="card-text fw-bold">Shs.<?php echo number_format($item['price']); ?></p>
                                            <p class="shipping-info mt-2">Estimated delivery: 3-5 business days</p>
                                            <button class="add-to-cart" data-product-id="<?php echo $item['id']; ?>">
                                                <img src="icons/shopping-cart.png" alt="Add to Cart" class="flaticon">
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($wishlistItems)): ?>
                    <div class="mt-5">
                        <h3 class="text-center">You May Also Like</h3>
                        <div class="row" id="related-products">
                            <?php foreach ($relatedProducts as $product): ?>
                                <div class="col-md-3 col-sm-6 mb-4">
                                    <div class="card h-100">
                                        <img src="<?php echo $product['image_url']; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?php echo $product['name']; ?></h5>
                                            <p class="card-text fw-bold">Shs.<?php echo number_format($product['price']); ?></p>
                                            <div class="product-actions">
                                                <button class="add-to-wishlist" data-product-id="<?php echo $product['id']; ?>">
                                                    <img src="icons/heart.png" alt="Add to Wishlist" class="flaticon">
                                                </button>
                                                <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                                    <img src="icons/shopping-cart.png" alt="Add to Cart" class="flaticon">
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
    <div class="container">
        <div class="row">
            <div class="col-md-3 mb-4">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="list-unstyled footer-links">
                    <li><a href="products.php">Products</a></li>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5 class="footer-title">FAQ</h5>
                <ul class="list-unstyled footer-links">
                    <li><a href="#">Payment Options</a></li>
                    <li><a href="#">Delivery Time</a></li>
                    <li><a href="#">Return Policy</a></li>
                    <li><a href="#">Warranty</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5 class="footer-title">Follow Us</h5>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <h5 class="footer-title">Newsletter</h5>
                <p>Stay updated with our latest offers and products.</p>
                <form class="newsletter-form">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email" required>
                        <button class="btn btn-primary text-dark" type="submit">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <hr class="footer-divider">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <p class="copyright">&copy; 2024 Gajou Luxe. All Rights Reserved.</p>
            </div>
        </div>
    </div>
</footer>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        initDealSlideshow();
        });

        $(document).ready(function() {
        AOS.init();

        // Event delegation for removing items from wishlist
        $(document).on('click', '.remove-from-wishlist', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const wishlistId = $(this).data('wishlist-id');
        const itemElement = $(this).closest('.wishlist-item');
        
        $.ajax({
            url: 'remove_from_wishlist.php',
            method: 'POST',
            data: { wishlist_id: wishlistId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    itemElement.fadeOut(300, function() { 
                        $(this).remove();
                        updateWishlistSummary();
                        checkEmptyWishlist();
                    });
                } else {
                // If the server-side removal fails, we'll remove it from the UI anyway
                // This handles the case for newly added items that might not be in the database yet
                itemElement.fadeOut(300, function() { 
                    $(this).remove();
                    updateWishlistSummary();
                    checkEmptyWishlist();
                });
                console.log('Server reported failure to remove item:', response.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX error:', textStatus, errorThrown);
            // Even if there's an error, we'll remove the item from the UI
            itemElement.fadeOut(300, function() { 
                $(this).remove();
                updateWishlistSummary();
            });
        }
        });
    });


// Event delegation for adding items to cart
$(document).on('click', '.add-to-cart', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const productId = $(this).data('product-id');
        
        $.ajax({
            url: 'add_to_cart.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Product added to cart successfully!');
                    $('.cart-badge').text(response.cartCount);
                } else {
                    alert('Failed to add product to cart. ' + (response.message || 'Please try again.'));
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });

// Event delegation for adding items to wishlist
$(document).on('click', '.add-to-wishlist', function(e) {
        e.preventDefault();
        const productId = $(this).data('product-id');
        const button = $(this);
        
        $.ajax({
            url: 'add_to_wishlist.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Product added to wishlist successfully!');
                    button.closest('.col-md-3').fadeOut(300, function() { $(this).remove(); });
                    addProductToWishlist(response.product);
                    updateRelatedProducts(response.relatedProducts);
                    updateWishlistSummary();
                } else {
                    alert('Failed to add product to wishlist. ' + (response.message || 'Please try again.'));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred. Please try again.');
            }
        });
    });

        function shareWishlist(platform) {
            const wishlistUrl = encodeURIComponent(window.location.href);
            const message = encodeURIComponent("Check out my wishlist on Gajou Luxe!");
            let shareUrl;

            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${wishlistUrl}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${wishlistUrl}&text=${message}`;
                    break;
                case 'email':
                    shareUrl = `mailto:?subject=My Gajou Luxe Wishlist&body=${message}%0A%0A${wishlistUrl}`;
                    break;
            }

            window.open(shareUrl, '_blank');
        }


        function updateRelatedProducts(relatedProducts) {
    const relatedProductsContainer = $('#related-products');
    relatedProductsContainer.empty();

    relatedProducts.forEach(product => {
        const productHtml = `
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card h-100" onclick="showProductModal(${JSON.stringify(product)})">
                    <img src="${product.image_url}" class="card-img-top" alt="${product.name}">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${product.name}</h5>
                        <p class="card-text fw-bold">Shs.${parseInt(product.price).toLocaleString('en-US')}</p>
                        <div class="product-actions">
                            <button class="add-to-wishlist" data-product-id="${product.id}">
                                <img src="icons/heart.png" alt="Add to Wishlist" class="flaticon">
                            </button>
                            <button class="add-to-cart" data-product-id="${product.id}">
                                <img src="icons/shopping-cart.png" alt="Add to Cart" class="flaticon">
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        relatedProductsContainer.append(productHtml);
    });
}


function addProductToWishlist(product) {
    const wishlistItem = `
        <div class="col-md-4 col-sm-6 wishlist-item" data-aos="fade-up">
            <div class="card h-100" onclick="showProductModal(${JSON.stringify(product)})">
                <img src="${product.image_url}" class="card-img-top" alt="${product.name}">
                <button class="remove-from-wishlist" data-wishlist-id="${product.id}">
                    <i class="fas fa-times"></i>
                </button>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${product.name}</h5>
                    <p class="card-text flex-grow-1">${product.description.substring(0, 50)}...</p>
                    <p class="card-text fw-bold">Shs.${product.price}</p>
                    <p class="shipping-info mt-2">Estimated delivery: 3-5 business days</p>
                    <button class="add-to-cart" data-product-id="${product.id}">
                        <img src="icons/shopping-cart.png" alt="Add to Cart" class="flaticon">
                    </button>
                </div>
            </div>
        </div>
    `;
    $('.wishlist-container .row').append(wishlistItem);
    updateWishlistSummary();
}


    function getRatingStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += i <= rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
        }
        return stars;
    }

    function updateWishlistSummary() {
    const itemCount = $('.wishlist-item').length;
    if (itemCount === 0) {
        $('.sidebar').hide();
    } else {
        $('.sidebar').show();
        const totalPrice = Array.from($('.wishlist-item .card-text.fw-bold')).reduce((sum, el) => {
        // Remove 'Shs.' prefix and any commas, then parse as an integer
        const price = parseInt(el.textContent.replace(/[^0-9]/g, ''),10);
        return sum + (isNaN(price) ? 0 : price);
    }, 0);
    
    $('.sidebar p:first-of-type').text(`Total Items: ${itemCount}`);
    $('.sidebar p:nth-of-type(2)').text(`Estimated Total: Shs. ${totalPrice.toLocaleString('en-US')}`);
    }
}
function initDealSlideshow() {
    const slides = document.querySelectorAll('.deal-slide');
    let currentSlide = 0;

    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        slides[index].classList.add('active');
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    showSlide(currentSlide);
    setInterval(nextSlide, 4000); // Change slide every 5 seconds
}
initDealSlideshow();

// Call this function when the DOM is loaded
document.addEventListener('DOMContentLoaded', initDealSlideshow);
function shareWishlist(platform) {
        const wishlistUrl = encodeURIComponent(window.location.href);
        const message = encodeURIComponent("Check out my wishlist on Gajou Luxe!");
        let shareUrl;

        switch (platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${wishlistUrl}`;
                break;
            case 'instagram':
                // Instagram doesn't have a direct sharing API, so we'll just open the app
                shareUrl = 'instagram://';
                break;
            case 'whatsapp':
                shareUrl = `https://api.whatsapp.com/send?text=${message}%20${wishlistUrl}`;
                break;
            case 'snapchat':
                // Snapchat doesn't have a direct sharing API, so we'll just open the app
                shareUrl = 'snapchat://';
                break;
            case 'tik-tok':
                shareUrl = 'tiktok://';
                break;
        }

        window.open(shareUrl, '_blank');
    }

    function showProductModal(product) {
  const modal = new bootstrap.Modal(document.getElementById('productModal'));
  
  document.getElementById('modalProductImage').src = product.image_url;
  document.getElementById('modalProductName').textContent = product.name;
  document.getElementById('modalProductDescription').textContent = product.description;
  document.getElementById('modalProductPrice').textContent = `Shs.${product.price.toFixed(2)}`;
  
  // Populate colors, sizes, and reviews here
  // You'll need to modify your PHP code to include this data in the $item array
  
  document.getElementById('modalAddToCart').onclick = function() {
    addToCart(product.id);
    modal.hide();
  };
  
  modal.show();
}
});

function checkEmptyWishlist() {
    if ($('.wishlist-item').length === 0) {
        $('.col-md-9').html(`
            <div class="empty-wishlist-message">
                <h2><i class="fas fa-heart"></i></h2>
                <h3>Wishlist is Empty</h3>
                <p>Check out our products and add to your wishlist</p>
                <a href="products.php" class="btn btn-dark">Shop Now</a>
            </div>
        `);
        $('.sidebar').hide();
    }
}

    </script>
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productModalLabel">Product Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <img id="modalProductImage" src="" alt="Product Image" class="img-fluid">
          </div>
          <div class="col-md-6">
            <h3 id="modalProductName"></h3>
            <p id="modalProductDescription"></p>
            <p id="modalProductPrice"></p>
            <div id="modalProductColors"></div>
            <div id="modalProductSizes"></div>
            <div id="modalProductReviews"></div>
            <button id="modalAddToCart" class="btn btn-primary mt-3">Add to Cart</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
