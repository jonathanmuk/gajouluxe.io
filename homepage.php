<?php
$product = null;
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
<link rel = "stylesheet" href="homepagestyle.css">
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

<div id="message-container"></div>


<main>
<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 hero-content">
                <h1 class="hero-title" data-aos="fade-up">Welcome to Luxury <br>Gajou Luxe</h1>
                <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="200">Discover the luxury of amber in our exquisite collection.</p>
                <div data-aos="fade-up" data-aos-delay="400">
                    <button class="cta" onclick="window.location.href='products.php'">
                        <span>Shop Now</span>
                        <svg width="15px" height="10px" viewBox="0 0 13 10">
                            <path d="M1,5 L11,5"></path>
                            <polyline points="8 1 12 5 8 9"></polyline>
                        </svg>
                    </button>
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
                            <?php
                                if (is_array($product) && isset($product['id'])) {
                                    $productId = $product['id'];
                                } else {
                                    // If $product is not set or not an array, use a default value
                                    $productId = 'unknown';
                                }
                            ?>
                            <button class="add-to-cart-btn2 btn btn-primary" data-product-id="<?php echo htmlspecialchars($productId); ?>">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            <button id="modalAddToWishlist" class="btn btn-outline-secondary">
                                <i class="far fa-heart"></i> Add to Wishlist
                            </button>
                        </div>
                        <div id="modalMessage" class="mt-2"></div>
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


function showMessage(message) {
        // Remove any existing messages
        $('#message-container').empty();
        $('<div>')
            .addClass('alert alert-info')
            .text(message)
            .appendTo('#message-container')
            .fadeIn('slow')
            .delay(1000)
            .fadeOut('slow', function() { $(this).remove(); });
    }
    function updateCartIcon($button, isAdded) {
        $button.find('.cart-icon').toggle(!isAdded);
        $button.find('.cart-icon-active').toggle(isAdded);
    }
    function updateBadges(data) { 
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
    function updateWishlistIcon($button, isAdded) {
        $button.find('.wishlist-icon').toggle(!isAdded);
        $button.find('.wishlist-icon-active').toggle(isAdded);
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




});

</script>
<script>
$(document).ready(function() {
  // Product card click handler
  $(document).on('click', '.product-card', function(e) {
    e.preventDefault();
    let productId = $(this).data('product-id');
    console.log('Product clicked, ID:', productId);

    if (!productId) {
        console.error('No product ID found');
        return;
    }

    $.ajax({
      url: 'fetch_product_modal_data.php',
      method: 'GET',
      data: { id: productId.toString() },
      dataType: 'json',
      success: function(product) {
        console.log('Product data received:', product);

        if (!product || typeof product !== 'object') {
          console.error('Invalid product data received:', product);
          return;
        }

        if (product.error) {
          console.error('Error:', product.error);
          return;
        }

        populateProductModal(product);
        $('#productModal').modal('show');
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.error('Error fetching product details:', textStatus, errorThrown);
        console.log('Response Text:', jqXHR.responseText);
      }
    });
  });

  function populateProductModal(product) {
  // Add null checks at the beginning of the function
  if (!product) {
        console.error('Product data is undefined');
        return;
    }
    // Initialize empty arrays if properties are undefined
    product.variants = product.variants || [];
    product.reviews = product.reviews || [];

    console.log('Full product data:', product);
    $('#modalProductName').text(product.name);
    
    if (product.is_sale) {
        $('#modalProductPrice').html(`
            <s class="text-danger">${product.formatted_price}</s>
            <span class="ms-2">${product.formatted_sale_price}</span>
        `);
    } else {
        $('#modalProductPrice').text(product.formatted_price);
    }
    
    $('#modalProductDescription').text(product.description);

    let carouselInner = $('#productImageCarousel .carousel-inner');
    let sizesContainer = $('#modalProductSizes');
    carouselInner.empty();
    sizesContainer.empty();

    let colorsContainer = $('#modalProductColors');
    colorsContainer.empty();
    colorsContainer.append('<h5>Available Options:</h5>');

    // Create a flex container for the options
    let coloroptionsContainer = $('<div class="d-flex flex-wrap gap-2"></div>').appendTo(colorsContainer);
    product.variants.forEach((variant, index) => {
        if (variant.texture_sample_path) {
            // For texture-based products
            coloroptionsContainer.append(`
                <div class="texture-option ${index === 0 ? 'active' : ''}" 
                     data-color-id="${variant.color_id}">
                    <img src="${variant.texture_sample_path}" 
                         alt="${variant.color_name}"
                         class="texture-sample"
                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; cursor: pointer; 
                                ${index === 0 ? 'border: 2px solid #007bff' : 'border: 1px solid #ddd'}">
                    <div class="color-name mt-1 text-center small">${variant.color_name}</div>
                </div>
            `);
        } else {
            // For color-based products
            coloroptionsContainer.append(`
                <div class="color-option ${index === 0 ? 'active' : ''}"
                     data-color-id="${variant.color_id}">
                    <div class="color-swatch" 
                         style="background-color: ${variant.color_code}; 
                                width: 40px; height: 40px; 
                                border-radius: 50%;
                                display: inline-block;
                                border: ${index === 0 ? '2px solid #007bff' : '1px solid #ddd'};
                                cursor: pointer;"></div>
                    <div class="color-name mt-1 text-center small">${variant.color_name}</div>
                </div>
            `);
        }

        if (index === 0) {
            updateSizesAndImages(variant);
        }
    });

    // Update click handlers for both texture and color options
    $('.texture-option, .color-option').click(function() {
        let $this = $(this);
        let colorId = $this.data('color-id');
        
        // Update active states
        $('.texture-option, .color-option').removeClass('active')
            .find('img, .color-swatch').css('border-color', '#ddd');
        
        $this.addClass('active')
            .find('img, .color-swatch').css('border-color', '#007bff');
        
        let selectedVariant = product.variants.find(v => v.color_id === colorId);
        if (selectedVariant) {
            updateSizesAndImages(selectedVariant);
        }
    });

    function updateSizesAndImages(variant) {
        console.log('Updating sizes and images for variant:', variant);
        
        // Update sizes
        sizesContainer.empty();
        if (Array.isArray(variant.sizes)) {
            variant.sizes.forEach(size => {
                sizesContainer.append(`
                    <button class="btn btn-outline-secondary btn-sm me-2 size-option" 
                            data-size="${size}">
                        ${size}
                    </button>
                `);
            });
        } else {
            console.error('variant.sizes is not an array:', variant.sizes);
        }

        // Update carousel
        carouselInner.empty();
        if (Array.isArray(variant.images) && variant.images.length > 0) {
            console.log('Variant images:', variant.images);
            variant.images.forEach((image, index) => {
                if (image && image.image_path) {
                    carouselInner.append(`
                        <div class="carousel-item ${index === 0 ? 'active' : ''}">
                            <img src="${image.image_path}" class="d-block w-100" alt="${product.name} - ${variant.color_name}">
                        </div>
                    `);
                } else {
                    console.error('Invalid image data:', image);
                }
            });
        } else {
            console.error('No images found for this variant:', variant);
            // Use the primary image as a fallback
            carouselInner.append(`
                <div class="carousel-item active">
                    <img src="${product.primary_image}" class="d-block w-100" alt="${product.name} - Primary Image">
                </div>
            `);
        }

        // Refresh the carousel
        $('.carousel').carousel('dispose');
        $('.carousel').carousel();
    }



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
    $('.add-to-cart-btn2').data('product-id', product.id);
    $('#modalAddToWishlist').data('product-id', product.id);

    console.log('Modal population complete');

    // Add quantity input
    let quantityContainer = $('<div class="mb-3">').appendTo($('#modalProductSizes').parent());
    $('<label for="quantityInput">Quantity:</label>').appendTo(quantityContainer);
    $('<input type="number" id="quantityInput" class="form-control" value="1" min="1">').appendTo(quantityContainer);

    // Add event listeners for color and size selection
    $('#modalProductColors').on('click', '.color-option', function() {
      $(this).addClass('active').siblings().removeClass('active');
      updateSizesAndImages(product.variants.find(v => v.color_id === $(this).data('color-id')));
    });

    $('#modalProductSizes').on('click', '.size-option', function() {
      $(this).addClass('active').siblings().removeClass('active');
    });
}

$(document).on('click', '.texture-option', function() {
    let $this = $(this);
    let colorId = $this.data('color-id');
    
    // Update active state
    $('.texture-option').removeClass('active');
    $this.addClass('active');
    
    // Find and update the variant
    let selectedVariant = product.variants.find(v => v.color_id === colorId);
    if (selectedVariant) {
        updateSizesAndImages(selectedVariant);
    }
});

 // Modal Add to Cart button click handler
$(document).on('click', '.add-to-cart-btn2', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    let $button = $(this);
    let productId = $button.data('product-id');
    let selectedColor = $('#modalProductColors .color-option.active, #modalProductColors .texture-option.active');
    let colorId = selectedColor.data('color-id');
    let size = $('#modalProductSizes .size-option.active').data('size');
    let quantity = parseInt($('#quantityInput').val());

    // Check if product has textures by looking for texture-option elements
    let isTextureProduct = $('#modalProductColors .texture-option').length > 0;

    // Validate selections
    if (!productId) {
        $('#modalMessage').text('Invalid product').removeClass('text-success').addClass('text-danger');
        return;
    }

    if (!colorId) {
        let message = isTextureProduct ? 'Please select a texture' : 'Please select a color';
        $('#modalMessage').text(message).removeClass('text-success').addClass('text-danger');
        return;
    }

    if (!size) {
        $('#modalMessage').text('Please select a size').removeClass('text-success').addClass('text-danger');
        return;
    }

    if (isNaN(quantity) || quantity < 1) {
        $('#modalMessage').text('Please enter a valid quantity').removeClass('text-success').addClass('text-danger');
        return;
    }

    // Toggle between add and remove
    if ($button.hasClass('added-to-cart')) {
        addToCart(productId, colorId, size, -quantity);
    } else {
        addToCart(productId, colorId, size, quantity);
    }
});




  // Modal Add to Wishlist button click handler
  $('#modalAddToWishlist').click(function() {
    let productId = $(this).data('product-id');
    addToWishlist(productId);
  });

  function addToCart(productId, colorId, size, quantity) {
    let $button = $('.add-to-cart-btn2[data-product-id="' + productId + '"]');

    // Disable the button to prevent multiple clicks
    $button.prop('disabled', true);
    $.ajax({
        url: 'modal_cart_update_ajax.php',
        method: 'POST',
        data: { 
            product_id: productId,
            color_id: colorId,
            size: size,
            quantity: quantity
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (response.action === 'added' || response.action === 'updated') {
                    $button.html('<i class="fas fa-check"></i> Added to Cart');
                    $button.removeClass('btn-primary').addClass('btn-success added-to-cart');
                    $('#modalMessage').text('Item added to cart').removeClass('text-danger').addClass('text-success');
                } else if (response.action === 'removed') {
                    $button.html('<i class="fas fa-cart-plus"></i> Add to Cart');
                    $button.removeClass('btn-success added-to-cart').addClass('btn-primary');
                    $('#modalMessage').text('Item removed from cart').removeClass('text-success').addClass('text-danger');
                }

                 // Update all product cards
                 updateProductCards(response.cartItems);

                // Update badges
                updateBadges(response);
                } else {
                $('#modalMessage').text('Error: ' + response.message).removeClass('text-success').addClass('text-danger');
                }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Cart AJAX error:', textStatus, errorThrown);
            $('#modalMessage').text('An error occurred. Please try again.').removeClass('text-success').addClass('text-danger');
        },
        complete: function() {
            // Re-enable the button after the request is complete
            setTimeout(function() {
                $button.prop('disabled', false);
            }, 1000); // Wait for 1 second before re-enabling
        }
    });
}

function updateProductCards(cartItems) {
    $('.add-to-cart-btn').each(function() {
        let cardProductId = $(this).data('product-id');
        if (cartItems.includes(cardProductId.toString())) {
            $(this).find('.cart-icon').hide();
            $(this).find('.cart-icon-active').show();
        } else {
            $(this).find('.cart-icon').show();
            $(this).find('.cart-icon-active').hide();
        }
    });
}


  function addToWishlist(productId) {
    $.ajax({
      url: 'wishlist_toggle_ajax.php',
      method: 'POST',
      data: { product_id: productId },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          updateWishlistIcon($('.wishlist-btn[data-product-id="' + productId + '"]'), response.action === 'added');
          updateBadges(response);
          showMessage(response.action === 'added' ? 'Item added to wishlist' : 'Item removed from wishlist');
          $('#productModal').modal('hide');
        } else {
          showMessage('Error: ' + response.message);
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.error('Wishlist AJAX error:', textStatus, errorThrown);
        showMessage('An error occurred. Please try again.');
      }
    });
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