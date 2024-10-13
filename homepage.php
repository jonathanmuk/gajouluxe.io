<?php
session_start();
include 'db_connection.php';

// Initialize cart and wishlist in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// Function to get cart item count
function getCartItemCount() {
    return isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
}

// Function to get wishlist item count
function getWishlistItemCount() {
    return isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
}

// Function to check if a product is in the wishlist
function isInWishlist($pdo, $userId, $productId) {
    $stmt = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    return $stmt->rowCount() > 0;
}

// Function to check if a product is in the cart
function isInCart($cartItems, $productId) {
    return isset($cartItems[$productId]) && $cartItems[$productId] > 0;
}

// Get initial counts
$initialCartCount = getCartItemCount();
$initialWishlistCount = getWishlistItemCount();


$product = null;
$is_in_wishlist = false;
$is_in_cart = false;



if (isset($_SESSION['user_id'])) {
    // Fetch a sample product or the first product from your database
    $sample_product_query = "SELECT * FROM products LIMIT 1";
    $stmt = $pdo->query($sample_product_query);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Check if the product is in the wishlist
        $is_in_wishlist = isInWishlist($pdo, $_SESSION['user_id'], $product['id']);

        // Check if the product is in the cart
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            $is_in_cart = isset($_SESSION['cart'][$product['id']]) && $_SESSION['cart'][$product['id']] > 0;
        }
    }
}
?>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gajou Luxe - le luxe d'ambre</title>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/magic-ui@latest/dist/magic-ui.min.css">
<!-- AOS CSS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<!-- Add Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<style>
body {
font-family: 'Poppins', sans-serif;
background-color: #f8f9fa;
color: #333;
}
.product-card {
height: 100%;
transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.product-card:hover {
transform: translateY(-5px);
box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.product-card img {
transition: transform 0.3s ease;
height: 200px;
object-fit: cover;
}
.product-card:hover img {
transform: scale(1.05);
}
.product-card .card-title {
text-align: center;
font-weight: bold;
}
.media-icons a {
font-size: 24px;
color: #333;
margin-right: 15px;
transition: color 0.3s ease;
}
.media-icons a:hover {
color: #2272FF;
}
.deals-container {
background-color: #f1f1f1;
padding: 20px;
margin-top: 30px;
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
.categories-section {
padding: 80px 0;
background-color: #f8f9fa;
}
.category-item {
position: relative;
overflow: hidden;
border-radius: 10px;
height: 250px;
box-shadow: 0 10px 20px rgba(0,0,0,0.1);
transition: all 0.3s ease;
}
.category-item img {
width: 100%;
height: 100%;
object-fit: cover;
transition: transform 0.3s ease;
}
.category-item:hover img {
transform: scale(1.1);
}
.category-overlay {
position: absolute;
top: 0;
left: 0;
right: 0;
bottom: 0;
background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%);
display: flex;
align-items: flex-end;
justify-content: center;
opacity: 0;
padding: 20px;
transition: opacity 0.3s ease;
}
.category-overlay {
opacity: 1;
}
.category-item:hover {
opacity: 1;
transform: translateY(-10px);
box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}
.category-overlay h3 {
color: white;
font-weight: bold;
font-size: 1.2rem;
margin: 0;
}
.deals-of-day {
background-color: #f8f9fa;
padding: 60px 0;
}
.section-title {
font-size: 2.5rem;
font-weight: 300;
color: #333;
margin-bottom: 20px;
text-transform: uppercase;
letter-spacing: 1px;
}


.deal-card {
background: #fff;
border: 1px solid #e0e0e0;
border-radius: 8px;
overflow: hidden;
height: 100%;
transition: all 0.3s ease;
}


.deal-card:hover {
transform: translateY(-5px);
box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.deal-image {
position: relative;
overflow: hidden;
}
.deal-image img {
width: 100%;
height: 200px;
object-fit: cover;
transition: transform 0.3s ease;
}
.deal-card:hover .deal-image img {
transform: scale(1.05);
}
.deal-tag {
position: absolute;
top: 10px;
right: 10px;
background-color: #ff4136;
color: #fff;
padding: 5px 10px;
font-size: 0.8rem;
font-weight: bold;
border-radius: 4px;
}
.deal-content {
padding: 20px;
text-align: center;
}
.deal-title {
font-size: 1.2rem;
font-weight: 600;
margin-bottom: 10px;
color: #333;
}


.deal-description {
font-size: 0.9rem;
color: #666;
margin-bottom: 15px;
}


.btn-outline-dark {
border-color: #333;
color: #333;
transition: all 0.3s ease;
}


.btn-outline-dark:hover {
background-color: #333;
color: #fff;
}

.product-grid {
display: grid;
grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
gap: 30px;
}


.product-card {
position: relative;
transition: transform 0.3s ease, box-shadow 0.3s ease;
height: 100%;
overflow: hidden;
cursor: pointer;
max-width: 250px;
margin: 0 auto;
}


.product-card:hover {
transform: translateY(-5px);
box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}


.product-image {
height: 180px;
background-size: cover;
background-position: center;
transition: transform 0.3s ease;
position: relative;
}
.card-body {
    padding: 15px;
    text-align: center;
}

.card-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}


.product-card:hover .product-image {
transform: scale(1.05);
}
.category {
    font-size: 0.8rem;
    color: #999;
    margin-bottom: 10px;
}

.price {
    font-size: 0.9rem;
    font-weight: 600;
    color: #000000;
}


.product-item {
background: white;
border-radius: 10px;
overflow: hidden;
box-shadow: 0 5px 15px rgba(0,0,0,0.1);
transition: transform 0.3s ease;
}


.product-item:hover {
transform: scale(1.05);
}


.product-info {
padding: 15px;
}


.text-glow {
text-shadow: 0 0 10px rgba(255,255,255,0.8);
}
.hero-section {
background: #D9D9D9;
padding: 20px 0 100px;
position: relative;
overflow: hidden;
min-height: 100vh;
display: flex;
flex-direction: column;
padding-top: 100px;
}


.hero-content {
position: relative;
z-index: 2;
flex-grow: 1;
display: flex;
flex-direction: column;
justify-content: center;
}
.hero-nav {
display: flex;
justify-content: space-between;
align-items: center;
padding: 20px 0;
position: relative;
z-index: 10;
}


.hero-nav .navbar-brand {
color: #fff;
font-size: 2rem;
font-weight: 700;
text-decoration: none;
}


.hero-title {
font-size: 4rem;
font-weight: 700;
margin-bottom: 20px;
color: black;
text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}
.nav-buttons {
display: flex;
gap: 10px;
}


.hero-subtitle {
font-size: 1.5rem;
margin-bottom: 30px;
color: black;
}


.nav-btn {
background-color: #44c7d4;
color: black;
border: none;
padding: 8px 16px;
font-size: 1rem;
border-radius: 20px;
transition: all 0.3s ease;
text-decoration: none;
margin-left: 0px;
}
.nav-btn:hover {
background-color: black;
color: #44c7d4;
}


.hero-btn:hover {
background-color: #ff9a9e;
color: #fff;
transform: translateY(-3px);
box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}


.hero-image {
position: absolute;
top: 0px;
left: 0;
width: 100%;
height: 100%;
background-size: cover;
background-position: center;
opacity: 0;
transition: opacity 1s ease-in-out, transform 6s ease-in-out;
transform: scale(1.1);
}
.hero-image-carousel {
position: absolute;
right: -100px;
top: 50%;
transform: translateY(-50%);
width: 600px;
height: 600px;
border-radius: 50%;
overflow: hidden;
box-shadow: 0 20px 40px rgba(0,0,0,0.1);
animation: float 6s ease-in-out infinite;
}
.hero-image-carousel {
animation: float 6s ease-in-out infinite;
}
.hero-image.active {
opacity: 1;
transform: scale(1);
}


@keyframes float {
0% { transform: translateY(-50%) translateX(0); }
50% { transform: translateY(-50%) translateX(-20px); }
100% { transform: translateY(-50%) translateX(0); }
}
.category-grid {
display: grid;
grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
gap: 20px;
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
.header {
background-color: #ffffff;
box-shadow: 0 2px 4px rgba(0,0,0,0.1);
padding: 15px 0;
position: fixed;
width: 100%;
top: 0;
z-index: 1000;
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
}


.nav-buttons {
display: flex;
gap: 20px;
}
.nav-link {
color: #333;
text-decoration: none;
font-size: 16px;
transition: color 0.3s ease;
}
.nav-link:hover {
color: #44c7d4;
}
.search-section {
flex-grow: 1;
margin: 0 30px;
position: relative;
}


.search-input {
width: 100%;
padding: 8px 15px 8px 40px;
border: 1px solid #ddd;
border-radius: 20px;
background-color: #D9D9D9;
}
.search-icon {
position: absolute;
left: 15px;
top: 50%;
transform: translateY(-50%);
color: #666;
}


.icon-button {
background: none;
border: none;
color: #333;
cursor: pointer;
margin-left: 15px;
display: flex;
align-items: center;
justify-content: center;
position: relative;
transition: color 0.3s ease;
}
.icon-button:hover {
color: #44c7d4;
}
.material-icons {
font-size: 24px;
}
.cart-badge {
position: absolute;
top: -8px;
right: -8px;
background-color: #44c7d4;
color: black;
border-radius: 50%;
padding: 2px 6px;
font-size: 12px;
}
.wishlist-badge {
position: absolute;
top: -8px;
right: -8px;
background-color: #44c7d4;
color: black;
border-radius: 50%;
padding: 2px 6px;
font-size: 12px;
}
.user-menu {
position: relative;
}


.user-dropdown {
display: none;
position: absolute;
right: 0;
top: 100%;
background-color: white;
box-shadow: 0 2px 10px rgba(0,0,0,0.1);
border-radius: 4px;
padding: 10px;
z-index: 1000;
}


.user-dropdown.show {
display: block;
}


.user-dropdown a {
display: block;
padding: 5px 10px;
color: #333;
text-decoration: none;
transition: background-color 0.3s ease;
}


.user-dropdown a:hover {
background-color: #f0f0f0;
}


.username {
margin-right: 10px;
}
.flaticon {
width: 24px;
height: 24px;
display: inline-block;
vertical-align: middle;
}
.hero-content .btn {
display: inline-block;
width: auto;
color:#44c7d4;
}
.hero-content .btn:hover{
color:white;
width: auto;
}
.search-results {
position: absolute;
top: 100%;
left: 0;
right: 0;
background-color: white;
border: 1px solid #ddd;
border-top: none;
border-radius: 0 0 20px 20px;
max-height: 300px;
overflow-y: auto;
display: none;
z-index: 1000;
}


.search-result-item {
padding: 10px;
cursor: pointer;
}


.search-result-item:hover {
background-color: #f0f0f0;
}
.category-button {
background-color: #44c7d4;
color: black;
border: none;
padding: 8px 16px;
font-size: 1rem;
border-radius: 20px;
transition: all 0.3s ease;
text-decoration: none;
margin-top: 20px;
}


.category-button:hover {
background-color: black;
color: #44c7d4;
}




.wishlist-btn:hover .wishlist-icon,
.add-to-cart-btn:hover .cart-icon,
.wishlist-btn:hover .wishlist-icon-active,
.add-to-cart-btn:hover .cart-icon-active {
transform: scale(1.1);
}
.badge {
position: absolute;
top: 10px;
left: 10px;
padding: 5px 10px;
border-radius: 20px;
font-size: 0.7rem;
font-weight: 600;
}


.badge-new {
background: #4CAF50;
color: white;
}


.badge-sale {
background: #f44336;
color: white;
}


.sale-price {
color: #e74c3c;
}
.product-actions {
    position: absolute;
    bottom: 10px;
    right: 10px;
    display: flex;
    justify-content: space-between;
    gap: 160px;
}

.wishlist-btn, .add-to-cart-btn {
    background-color: rgba(255, 255, 255, 0.8);
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.wishlist-btn:hover, .add-to-cart-btn:hover {
    background-color: rgba(255, 255, 255, 1);
}

.wishlist-icon, .cart-icon, .wishlist-icon-active, .cart-icon-active {
    color: #333;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}
.message-popup {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    padding: 10px 20px;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 300px;
    text-align: center;
}
#message-container {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    max-width: 300px;
    width: 100%;
}

.alert {
    text-align: center;
}
.wishlist-icon-active, .cart-icon-active {
    display: none;
}
.btn-primary {
    background-color: black;
    border-color: black;
    color: #44c7d4;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: #44c7d4;
    border-color: #44c7d4;
    color: black;
}

<style>
/* Add this to your existing styles */
.modal-content {
    border-radius: 15px;
}

.modal-header {
    border-bottom: none;
    padding-bottom: 0;
}

.modal-body {
    padding-top: 0;
}

#productImageCarousel .carousel-item img {
    border-radius: 10px;
    object-fit: cover;
    height: 300px;
}

.color-option, .size-option {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 10px;
    cursor: pointer;
}

.size-option {
    width: auto;
    height: auto;
    border-radius: 5px;
    padding: 5px 10px;
}

.review-stars {
    color: #ffc107;
}
</style>












</style>
</head>
<body>
<header class="header">
<div class="container">
<div class="header-content">
<div class="company-name">Gajou Luxe</div>
<div class="search-section">
<span class="material-icons search-icon">search</span>
<input type="text" class="search-input" id="searchInput" placeholder="Search...">
<div id="searchResults" class="search-results"></div>
</div>


<nav class="nav-buttons">
<a class="nav-link" href="products.php">Products</a>
<a class="nav-link" href="#contact">Contact</a>
</nav>
<a href="wishlist.php" class="icon-button" id="wishlistButton">
    <img src="icons/heart.png" alt="Wishlist" class="flaticon">
    <span class="wishlist-badge" id="wishlistBadge"><?php echo $initialWishlistCount; ?></span>
</a>
<a href="cart.php" class="icon-button" id="cartButton">
<img src="icons/shopping-cart.png" alt="Cart" class="flaticon">
<span class="cart-badge" id="cartBadge"><?php echo $initialCartCount; ?></span>
</a>
<div class="user-menu">
<button class="icon-button" id="userButton">
<span class="username" id="usernameDisplay"></span>
<img src="icons/user.png" alt="User" class="flaticon">
</button>
<div class="user-dropdown" id="userDropdown">
<a href="account_settings.php">Account Settings</a>
<a href="logout.php">Logout</a>
</div>
</div>
</div>
</div>
</header>

<div id="message-container" style="position: fixed; top: 0; left: 0; right: 0; z-index: 9999;"></div>


<main>
<section class="hero-section">
<div class="container">
<div class="row">
<div class="col-lg-6 hero-content">
<h1 class="hero-title" data-aos="fade-up">Welcome to Luxury <br>Gajou Luxe</h1>
<p class="hero-subtitle" data-aos="fade-up" data-aos-delay="200">Discover the luxury of amber in our exquisite collection.</p>
<div data-aos="fade-up" data-aos-delay="400">
<a href="products.php" class="btn btn-dark btn-lg">Shop Now</a>
</div>
</div>
</div>
</div>
<div class="hero-image-carousel" data-aos="fade-left" data-aos-delay="600">
<div class="hero-image active" style="background-image: url('images/photo_2024-08-07_22-12-23.jpg');"></div>
<div class="hero-image" style="background-image: url('images/InShot_20240930_160819465.jpg');"></div>
<div class="hero-image" style="background-image: url('images/photo_2024-08-08_17-00-32.jpg');"></div>
</div>
</section>



<!-- Categories Section -->
<section class="categories-section" id="categories">
<div class="container">
<h2 class="text-center mb-5" data-aos="fade-up">Shop by Category</h2>
<div class="category-grid" id="categoryGrid">
<?php
$category_query = "SELECT * FROM categories LIMIT 5";
try {
$stmt = $pdo->query($category_query);
if ($stmt) {
while ($category = $stmt->fetch()) {
echo '<div class="category-item" data-aos="zoom-in">';
echo '<img src="' . htmlspecialchars($category['image_url']) . '" class="img-fluid" alt="' . htmlspecialchars($category['name']) . '">';
echo '<div class="category-overlay">';
echo '<h3>' . htmlspecialchars($category['name']) . '</h3>';
echo '</div>';
echo '</div>';
}
}
} catch (PDOException $e) {
echo "Error: " . $e->getMessage();
}
?>
</div>
<div class="text-center mt-4">
<button id="toggleCategories" class="category-button">
More Categories <i class="fas fa-chevron-right"></i>
</button>
</div>
</div>
</section>






<!-- Deals of the Day Section -->
<section class="deals-of-day">
<div class="container">
<h2 class="section-title text-center mb-5" data-aos="fade-up">Deals of the Day</h2>
<div class="row">
<div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
<div class="deal-card">
<div class="deal-image">
<img src="images/InShot_20240930_160908194.jpg" alt="Deal 1">
<div class="deal-tag">50% OFF</div>
</div>
<div class="deal-content">
<h5 class="deal-title">Summer Collection</h5>
<p class="deal-description">Limited time offer on our latest summer styles!</p>
<a href="#" class="btn btn-outline-dark btn-sm">Shop Now</a>
</div>
</div>
</div>
<div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
<div class="deal-card">
<div class="deal-image">
<img src="images/photo_2024-08-09_18-05-28.jpg" alt="Deal 2">
<div class="deal-tag">Buy 2 Get 1</div>
</div>
<div class="deal-content">
<h5 class="deal-title">Accessories & Jewelry</h5>
<p class="deal-description">On all accessories and jewelry items!</p>
<a href="#" class="btn btn-outline-dark btn-sm">Shop Now</a>
</div>
</div>
</div>
<div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
<div class="deal-card">
<div class="deal-image">
<img src="images/photo_2024-08-08_17-00-32.jpg" alt="Deal 3">
<div class="deal-tag">Free Shipping</div>
</div>
<div class="deal-content">
<h5 class="deal-title">Orders $100+</h5>
<p class="deal-description">Limited time offer for all domestic orders!</p>
<a href="#" class="btn btn-outline-dark btn-sm">Shop Now</a>
</div>
</div>
</div>
</div>
</div>
</section>




<!-- Product Grid Section -->
<!-- Products Section -->
<section class="products-section py-5" id="products">
    <div class="container">
        <h2 class="text-center mb-5" data-aos="fade-up">Our Products</h2>
        <div class="row" id="productGrid">
            <?php
                $product_query = "SELECT p.*, c.name AS category_name FROM products p 
                JOIN categories c ON p.category_id = c.id 
                ORDER BY p.created_at DESC LIMIT 8";
                try {
                    $stmt = $pdo->query($product_query);
                    if ($stmt) {
                        while ($product = $stmt->fetch()) {
                        $is_in_wishlist = isset($_SESSION['user_id']) ? isInWishlist($pdo, $_SESSION['user_id'], $product['id']) : false;
                        $is_in_cart = isset($_SESSION['cart']) ? isInCart($_SESSION['cart'], $product['id']) : false;
                    if (isset($_SESSION['user_id'])) {
                        $wishlist_check = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
                        $wishlist_check->execute([$_SESSION['user_id'], $product['id']]);
                        $is_in_wishlist = $wishlist_check->rowCount() > 0;
                    }
            ?>
            <div class="col-md-3 mb-4" data-aos="fade-up">
                <div class="card product-card" data-product-id="<?php echo $product['id']; ?>">
                    <div class="product-image" style="background-image: url(<?php echo htmlspecialchars($product['image_url']); ?>);">
                        <?php if ($product['is_new']): ?>
                            <span class="badge badge-new">New</span>
                        <?php endif; ?>
                        <?php if ($product['is_sale']): ?>
                            <span class="badge badge-sale">Sale</span>
                        <?php endif; ?>
                        <div class="product-actions">
                            <button class="wishlist-btn" data-product-id="<?php echo $product['id']; ?>">
                                <i class="far fa-heart wishlist-icon" style="<?php echo $is_in_wishlist ? 'display: none;' : ''; ?>"></i>
                                <i class="fas fa-heart wishlist-icon-active" style="<?php echo $is_in_wishlist ? '' : 'display: none;'; ?>"></i>
                            </button>
                            <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-cart-plus cart-icon" style="<?php echo $is_in_cart ? 'display: none;' : ''; ?>"></i>
                                <i class="fas fa-check cart-icon-active" style="<?php echo $is_in_cart ? 'display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;' : 'display: none;'; ?>"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p class="price">
                        <?php if ($product['is_sale']): ?>
                            <s>Shs <?php echo number_format($product['price']); ?></s>
                            <span class="sale-price">Shs <?php echo number_format($product['sale_price']); ?></span>
                            <?php else: ?>Shs <?php echo number_format($product['price']); ?>
                        <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

        <?php
}
}
} catch (PDOException $e) {
echo "Error: " . $e->getMessage();
}
?>
</div>
<div class="text-center mt-4">
<a href="products.php" class="btn btn-primary">View All Products</a>
</div>
</div>
</section>




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


 
</main>


<footer>
<div class="container">
<div class="row">
<div class="col-md-3 mb-4">
<h5 class="footer-title">Quick Links</h5>
<ul class="list-unstyled footer-links">
<li><a href="#">Products</a></li>
<li><a href="#">About Us</a></li>
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
            <div id="productImageCarousel" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner">
                <!-- Images will be dynamically inserted here -->
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#productImageCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#productImageCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
              </button>
            </div>
          </div>
          <div class="col-md-6">
            <h2 id="modalProductName"></h2>
            <p id="modalProductPrice" class="fs-4 fw-bold"></p>
            <p id="modalProductDescription"></p>
            <div id="modalProductColors" class="mb-3">
              <h5>Colors:</h5>
              <!-- Colors will be dynamically inserted here -->
            </div>
            <div id="modalProductSizes" class="mb-3">
              <h5>Sizes:</h5>
              <!-- Sizes will be dynamically inserted here -->
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <button id="modalAddToCart" class="btn btn-primary">Add to Cart</button>
              <button id="modalAddToWishlist" class="btn btn-outline-secondary">
                <i class="far fa-heart"></i> Add to Wishlist
              </button>
            </div>
          </div>
        </div>
        <div class="row mt-4">
          <div class="col-12">
            <h4>Reviews</h4>
            <div id="modalProductReviews">
              <!-- Reviews will be dynamically inserted here -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>





<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- AOS JS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
AOS.init({
duration: 1000,
once: true
});




// Hero image carousel
const heroImages = document.querySelectorAll('.hero-image');
let currentImageIndex = 0;


function changeHeroImage() {
heroImages[currentImageIndex].classList.remove('active');
currentImageIndex = (currentImageIndex + 1) % heroImages.length;
heroImages[currentImageIndex].classList.add('active');
}


setInterval(changeHeroImage, 5000); // Change image every 5 seconds
</script>
<script>
$(document).ready(function() {
    function updateUserStatus() {
        $.ajax({
            url: 'get_user_status.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.loggedIn) {
                    $('#usernameDisplay').text(response.username);
                    $('#accountLink, #logoutLink').show();
                    $('#cartBadge').text(response.cartItems).toggle(response.cartItems > 0);
                } else {
                    $('#usernameDisplay').text('');
                    $('#accountLink, #logoutLink').hide();
                    $('#cartBadge').hide();
                }
            }
        });
    }

    updateUserStatus();

    $('#userButton').click(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'get_user_status.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.loggedIn) {
                    $('#userDropdown').toggleClass('show');
                } else {
                    window.location.href = 'login.php';
                }
            }
        });
    });

    $(document).click(function(event) {
        if (!$(event.target).closest('.user-menu').length) {
            $('#userDropdown').removeClass('show');
        }
    });

    // Search functionality
    $('#searchInput').on('input', function() {
        var query = $(this).val();
        if (query.length > 2) {
            $.ajax({
                url: 'search_products.php',
                method: 'GET',
                data: { query: query },
                success: function(response) {
                    $('#searchResults').html(response).show();
                }
            });
        } else {
            $('#searchResults').hide();
        }
    });

    $(document).on('click', '.search-result-item', function() {
        var productId = $(this).data('id');
        updateProductGrid(productId);
        $('#searchResults').hide();
        $('#searchInput').val('');
        $('html, body').animate({
            scrollTop: $("#products").offset().top
        }, 1000);
    });

    function updateProductGrid(productId) {
        $.ajax({
            url: 'get_product.php',
            method: 'GET',
            data: { id: productId },
            success: function(response) {
                $('.product-grid').html(response);
            }
        });
    }

    // Toggle categories functionality
    let showingAllCategories = false;
    $('#toggleCategories').click(function() {
        if (!showingAllCategories) {
            $.ajax({
                url: 'get_all_categories.php',
                method: 'GET',
                success: function(response) {
                    $('#categoryGrid').html(response);
                    $('#toggleCategories').html('Show Less <i class="fas fa-chevron-up"></i>');
                    showingAllCategories = true;
                }
            });
        } else {
            $.ajax({
                url: 'get_limited_categories.php',
                method: 'GET',
                success: function(response) {
                    $('#categoryGrid').html(response);
                    $('#toggleCategories').html('More Categories <i class="fas fa-chevron-right"></i>');
                    showingAllCategories = false;
                }
            });
        }
    });

    // Initial update of icons
    $('.wishlist-btn').each(function() {
        let $button = $(this);
        let isAdded = $button.find('.wishlist-icon-active').is(':visible');
        updateWishlistIcon($button, isAdded);
    });

    $('.add-to-cart-btn').each(function() {
        let $button = $(this);
        let isAdded = $button.find('.cart-icon-active').is(':visible');
        updateCartIcon($button, isAdded);
    });

    // Wishlist button click handler
    $(document).on('click', '.wishlist-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let $button = $(this);
        let productId = $button.data('product-id');

        console.log('Wishlist button clicked for product ID:', productId);

        $.ajax({
            url: 'wishlist_toggle_ajax.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                console.log('Wishlist AJAX response:', response);
                if (response.success) {
                    updateWishlistIcon($button, response.action === 'added');
                    updateBadges(response);
                    showMessage(response.action === 'added' ? 'Item added to wishlist' : 'Item removed from wishlist');
                } else {
                    showMessage('Error: ' + response.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Wishlist AJAX error:', textStatus, errorThrown);
                showMessage('An error occurred. Please try again.');
            }
        });
    });


    // Add to cart button click handler
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let $button = $(this);
        let productId = $button.data('product-id');

        console.log('Add to cart button clicked for product ID:', productId);

        $.ajax({
            url: 'cart_update_ajax.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                console.log('Cart AJAX response:', response);
                if (response.success) {
                    updateCartIcon($button, response.action === 'added');
                    updateBadges(response);
                    showMessage(response.action === 'added' ? 'Item added to cart' : 'Item removed from cart');
                } else {
                    showMessage('Error: ' + response.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Cart AJAX error:', textStatus, errorThrown);
                showMessage('An error occurred. Please try again.');
            }
        });
    });

function updateWishlistIcon($button, isAdded) {
        $button.find('.wishlist-icon').toggle(!isAdded);
        $button.find('.wishlist-icon-active').toggle(isAdded);
    }


function updateCartIcon($button, isAdded) {
        $button.find('.cart-icon').toggle(!isAdded);
        $button.find('.cart-icon-active').toggle(isAdded);
    }

    function updateBadges(data) {
        console.log('Updating badges with data:', data);
        
        if (data && typeof data === 'object') {
            if ('wishlistCount' in data) {
                $('#wishlistBadge').text(data.wishlistCount).toggle(data.wishlistCount > 0);
            }
            if ('cartCount' in data) {
                $('#cartBadge').text(data.cartCount).toggle(data.cartCount > 0);
            }

            if (Array.isArray(data.wishlistItems)) {
                $('.wishlist-btn').each(function() {
                    let productId = $(this).data('product-id');
                    updateWishlistIcon($(this), data.wishlistItems.includes(productId.toString()));
                });
            }

            if (Array.isArray(data.cartItems)) {
                $('.add-to-cart-btn').each(function() {
                    let productId = $(this).data('product-id');
                    updateCartIcon($(this), data.cartItems.includes(productId.toString()));
                });
            }
        } else {
            console.error('Invalid data received in updateBadges:', data);
        }
    }

    function showMessage(message) {
        $('<div>')
            .addClass('alert alert-info')
            .text(message)
            .appendTo('#message-container')
            .fadeIn('slow')
            .delay(3000)
            .fadeOut('slow', function() { $(this).remove(); });
    }

    // Initial update of badges
    function initialUpdateBadges() {
        let wishlistCount = parseInt($('#wishlistBadge').text());
        let cartCount = parseInt($('#cartBadge').text());

        $('#wishlistBadge').toggle(wishlistCount > 0);
        $('#cartBadge').toggle(cartCount > 0);
    }

    initialUpdateBadges();

    // Update badges every 0.5 seconds
    setInterval(function() {
        $.ajax({
            url: 'get_badge_counts.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                updateBadges(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error fetching badge counts:', textStatus, errorThrown);
            }
        });
    }, 500);


});

</script>
<script>
$(document).ready(function() {
  // Product card click handler
  $(document).on('click', '.product-card', function(e) {
    e.preventDefault();
    let productId = $(this).data('product-id');
    console.log('Product clicked, element:', this);
    console.log('Product ID from data attribute:', productId);

    if (!productId) {
        console.error('No product ID found');
        return;
    }

    $.ajax({
      url: 'fetch_product_modal_data.php',
      method: 'GET',
      data: { id: productId.toString() },
      dataType: 'json',
      beforeSend: function(xhr, settings) {
        console.log('AJAX request data:', settings.data);
      },
      success: function(product) {
        console.log('Product data received:', product); 
        if (product.error) {
          console.error('Error:', product.error);
          return;
        }
        console.log('About to populate modal');
        populateProductModal(product);
        console.log('Modal populated, about to show');
        $('#productModal').modal('show');
        console.log('Modal should be visible now');
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.error('Error fetching product details:', textStatus, errorThrown);
        console.log('Response Text:', jqXHR.responseText);
      }
    });
  });

  function populateProductModal(product) {
    console.log('Populating modal with:', product);

    $('#modalProductName').text(product.name);
    $('#modalProductPrice').text('$' + parseFloat(product.price).toFixed(2));
    if (product.is_sale) {
    $('#modalProductPrice').html(`
        <s>$${parseFloat(product.price).toFixed(2)}</s>
        <span class="text-danger">Sale: $${parseFloat(product.sale_price).toFixed(2)}</span>
    `);
} else {
    $('#modalProductPrice').text('$' + parseFloat(product.price).toFixed(2));
}
    $('#modalProductDescription').text(product.description);

    // Populate image carousel
    let carouselInner = $('#productImageCarousel .carousel-inner');
    carouselInner.empty();

    // Populate colors and sizes
    let colorsContainer = $('#modalProductColors');
    let sizesContainer = $('#modalProductSizes');
    colorsContainer.empty();
    sizesContainer.empty();

    let defaultColor = product.default_color;

    product.variants.forEach((variant, index) => {
        // Add color option
        colorsContainer.append(`
            <button class="btn btn-sm me-2 color-option" 
                    style="background-color: ${variant.color_code};" 
                    data-color="${variant.color_name}">
            </button>
        `);

        // Add images to carousel
        variant.images.forEach((image, imgIndex) => {
            carouselInner.append(`
                <div class="carousel-item ${index === 0 && imgIndex === 0 ? 'active' : ''}" data-color="${variant.color_name}">
                    <img src="${image}" class="d-block w-100" alt="${product.name} - ${variant.color_name}">
                </div>
            `);
        });

        // Add sizes
        if (index === 0) {
            variant.sizes.forEach(size => {
                let quantity = product.stock[variant.color_name][size] || 0;
                sizesContainer.append(`
                    <button class="btn btn-outline-secondary btn-sm me-2 size-option" 
                            data-size="${size}" 
                            ${quantity === 0 ? 'disabled' : ''}>
                        ${size} ${quantity === 0 ? '(Out of Stock)' : ''}
                    </button>
                `);
            });
        }
    });

    // Add event listener for color selection
    $('.color-option').click(function() {
        let selectedColor = $(this).data('color');
        
        // Update carousel
        $('.carousel-item').removeClass('active').filter(`[data-color="${selectedColor}"]`).first().addClass('active');
        
        // Update sizes
        sizesContainer.empty();
        let variant = product.variants.find(v => v.color_name === selectedColor);
        variant.sizes.forEach(size => {
            let quantity = product.stock[selectedColor][size] || 0;
            sizesContainer.append(`
                <button class="btn btn-outline-secondary btn-sm me-2 size-option" 
                        data-size="${size}" 
                        ${quantity === 0 ? 'disabled' : ''}>
                    ${size} ${quantity === 0 ? '(Out of Stock)' : ''}
                </button>
            `);
        });
    });

    // Populate reviews
    let reviewsContainer = $('#modalProductReviews');
    reviewsContainer.empty();
    product.reviews.forEach(review => {
        reviewsContainer.append(`
            <div class="mb-3">
                <h5>${review.username}</h5>
                <div class="text-warning mb-2">
                    ${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}
                </div>
                <p>${review.comment}</p>
            </div>
        `);
    });

    // Update modal buttons
    $('#modalAddToCart').data('product-id', product.id);
    $('#modalAddToWishlist').data('product-id', product.id);

    console.log('Modal population complete');
  }

  // Modal Add to Cart button click handler
  $('#modalAddToCart').click(function() {
    let productId = $(this).data('product-id');
    addToCart(productId);
  });

  // Modal Add to Wishlist button click handler
  $('#modalAddToWishlist').click(function() {
    let productId = $(this).data('product-id');
    addToWishlist(productId);
  });

  function addToCart(productId) {
    // Implement your add to cart logic here
    console.log('Adding product to cart:', productId);
  }

  function addToWishlist(productId) {
    // Implement your add to wishlist logic here
    console.log('Adding product to wishlist:', productId);
  }

  // Check if modal is properly initialized
  var modalElement = document.getElementById('productModal');
  if (modalElement) {
    console.log('Modal element found');
    var modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
      console.log('Modal is already initialized');
    } else {
      console.log('Initializing modal');
      new bootstrap.Modal(modalElement);
    }
  } else {
    console.error('Modal element not found');
  }
});

var productModal = new bootstrap.Modal(document.getElementById('productModal'));

</script>
</body>
</html>