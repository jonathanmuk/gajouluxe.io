<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT p.*, w.id as wishlist_id, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE w.user_id = ?
    GROUP BY p.id
");
$stmt->execute([$userId]);
$wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getCartItemCount() {
    global $pdo, $userId;
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: 0;
}


// Fetch related products
$relatedProductIds = array_column($wishlistItems, 'id');
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
            padding: 20px;
            border-radius: 8px;
            position: sticky;
            top: 20px;
        }
        .footer {
            background-color: #e9ecef;
            color: black;
            padding: 30px 0;
            margin-top: 50px;
        }
        .footer a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer a:hover {
            color: #44c7d4;
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
        .sidebar-deals {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
        }

        .deal-slideshow {
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
        }

        .deal-slide p {
        margin-top: 10px;
        text-align: center;
        font-weight: bold;
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

    </style>
</head>
<body>
<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="homepage.html" class="company-name">Gajou Luxe</a>
            <nav class="nav-buttons">
                <a class="nav-link" href="homepage.html">Home</a>
                <a class="nav-link" href="products.php">Products</a>
                <a href="cart.php" class="icon-button">
                <img src="icons\shopping-cart.png" alt="Cart" class="flaticon">
                    <span class="cart-badge"><?php echo getCartItemCount(); ?></span>
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
                <div class="sidebar">
                    <h4>Wishlist Summary</h4>
                    <p>Total Items: <?php echo count($wishlistItems); ?></p>
                    <p>Estimated Total: $<?php echo number_format(array_sum(array_column($wishlistItems, 'price')), 2); ?></p>
                    <hr>
                    <h5>Share Your Wishlist</h5>
                    <div class="social-share">
                        <a href="#" onclick="shareWishlist('facebook')"><img src="icons/facebook.png" alt="Facebook" class="flaticon"></a>
                        <a href="#" onclick="shareWishlist('instagram')"><img src="icons/instagram (1).png" alt="Instagram" class="flaticon"></a>
                        <a href="#" onclick="shareWishlist('whatsapp')"><img src="icons/whatsapp.png" alt="WhatsApp" class="flaticon"></a>
                        <a href="#" onclick="shareWishlist('snapchat')"><img src="icons/snapchat.png" alt="Snapchat" class="flaticon"></a>
                        <a href="#" onclick="shareWishlist('tiktok')"><img src="icons/tik-tok.png" alt="Tiktok" class="flaticon"></a>
                    </div>
                    <div class="sidebar-deals">
    <h4>Today's Deals</h4>
    <div class="deal-slideshow">
        <div class="deal-slide">
            <img src="images/photo_2024-08-08_17-00-32.jpg" alt="Deal 1">
            <p>Summer Collection 50% Off</p>
        </div>
        <div class="deal-slide">
            <img src="images/photo_2024-08-09_18-05-27.jpg" alt="Deal 2">
            <p>Buy 2 Get 1 Free</p>
        </div>
        <div class="deal-slide">
            <img src="images/photo_2024-08-09_18-10-11.jpg" alt="Deal 3">
            <p>Free Shipping on Orders $100+</p>
        </div>
    </div>
</div>


                </div>
            </div>
            <div class="col-md-9">
                <div class="wishlist-container">
                <h2 class="wishlist-title">My Wishlist</h2>
                    <div class="row">
                        <?php foreach ($wishlistItems as $item): ?>
                            <div class="col-md-4 col-sm-6 wishlist-item" data-aos="fade-up">
                                <div class="card h-100">
                                    <img src="<?php echo $item['image_url']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>">
                                    <button class="remove-from-wishlist" data-wishlist-id="<?php echo $item['wishlist_id']; ?>">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo $item['name']; ?></h5>
                                        <p class="card-text flex-grow-1"><?php echo substr($item['description'], 0, 50) . '...'; ?></p>
                                        <p class="card-text fw-bold">$<?php echo number_format($item['price'], 2); ?></p>
                                        <div class="rating">
                                            <?php
                                            $rating = round($item['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                            <span class="ms-2">(<?php echo $item['review_count']; ?> reviews)</span>
                                        </div>
                                        <p class="shipping-info mt-2">Estimated delivery: 3-5 business days</p>
                                        <button class="add-to-cart" data-product-id="<?php echo $item['id']; ?>">
                                        <img src="icons/shopping-cart.png" alt="Add to Cart" class="flaticon">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-5">
                <h3 class="text-center">You May Also Like</h3>
                    <div class="row" id="related-products">
                        <?php foreach ($relatedProducts as $product): ?>
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="card h-100">
                                    <img src="<?php echo $product['image_url']; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                                        <p class="card-text fw-bold">$<?php echo number_format($product['price'], 2); ?></p>
                                        <div class="rating">
                                            <?php
                                            $rating = round($product['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                            <span class="ms-2">(<?php echo $product['review_count']; ?> reviews)</span>
                                        </div>
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
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Gajou Luxe</h5>
                    <p>Discover luxury fashion at its finest with Gajou Luxe.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="homepage.html">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="account_settings.php">My Account</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <div class="social-icons">
                        <a href="#"><img src="icons/instagram (1).png" alt="Instagram" class="flaticon"></a>
                        <a href="#"><img src="icons/whatsapp.png" alt="WhatsApp" class="flaticon"></a>
                        <a href="#"><img src="icons/snapchat.png" alt="Snapchat" class="flaticon"></a>
                        <a href="#"><img src="icons/tik-tok.png" alt="Tiktok" class="flaticon"></a>
                    </div>
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

        
        $(document).on('click', '.remove-from-wishlist', function() {
    const wishlistId = $(this).data('wishlist-id');
    const itemElement = $(this).closest('.wishlist-item');
    
    $.ajax({
        url: 'remove_from_wishlist.php',
        method: 'POST',
        data: { wishlist_id: wishlistId },
        success: function(response) {
            if (response.success) {
                itemElement.fadeOut(300, function() { 
                    $(this).remove();
                    updateWishlistSummary();
                });
            } else {
                alert('Failed to remove item from wishlist. ' + (response.message || 'Please try again.'));
            }
        },
        error: function() {
            alert('An error occurred. Please try again.');
        }
    });
});


        $('.add-to-cart').on('click', function() {
            const productId = $(this).data('product-id');
            const color = $(this).data('color') || null;
            const size = $(this).data('size') || null;
            
            $.ajax({
                url: 'add_to_cart.php',
                method: 'POST',
                data: { 
                    product_id: productId,
                    color: color,
                    size: size
                },
                success: function(response) {
                    if (response.success) {
                        alert('Product added to cart successfully!');
                    } else {
                        alert('Failed to add product to cart. ' + (response.message || 'Please try again.'));
                    }
                },
                error: function() {
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

        $(document).on('click', '.add-to-wishlist', function() {
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
                // Remove the product from "You May Also Like" section
                button.closest('.col-md-3').fadeOut(300, function() { $(this).remove(); });
                // Add the product to the wishlist section
                addProductToWishlist(response.product);
                // Update "You May Also Like" section with new related products
                updateRelatedProducts(response.relatedProducts);
                // Update wishlist summary
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


function updateRelatedProducts(relatedProducts) {
    const relatedProductsContainer = $('#related-products');
    relatedProductsContainer.empty();

    relatedProducts.forEach(product => {
        const productHtml = `
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card h-100">
                    <img src="${product.image_url}" class="card-img-top" alt="${product.name}">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${product.name}</h5>
                        <p class="card-text fw-bold">$${parseFloat(product.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                        <div class="rating">
                            ${getRatingStars(product.avg_rating)}
                            <span class="ms-2">(${product.review_count} reviews)</span>
                        </div>
                        <button class="btn btn-outline-primary mt-auto add-to-wishlist" data-product-id="${product.id}">Add to Wishlist</button>
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
            <div class="card h-100">
                <img src="${product.image_url}" class="card-img-top" alt="${product.name}">
                <button class="remove-from-wishlist" data-wishlist-id="${product.wishlist_id}">
                    <i class="fas fa-times"></i>
                </button>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${product.name}</h5>
                    <p class="card-text flex-grow-1">${product.description.substring(0, 50)}...</p>
                    <p class="card-text fw-bold">$${product.price}</p>
                    <div class="rating">
                        ${getRatingStars(product.avg_rating)}
                        <span class="ms-2">(${product.review_count} reviews)</span>
                    </div>
                    <p class="shipping-info mt-2">Estimated delivery: 3-5 business days</p>
                    <button class="btn btn-primary mt-auto add-to-cart" data-product-id="${product.id}">Add to Cart</button>
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
    const totalPrice = Array.from($('.wishlist-item .card-text.fw-bold')).reduce((sum, el) => {
        // Remove the dollar sign and any commas, then parse as a float
        const price = parseFloat(el.textContent.replace(/[$,]/g, ''));
        return sum + price;
    }, 0);
    
    $('.sidebar p:first-of-type').text(`Total Items: ${itemCount}`);
    $('.sidebar p:nth-of-type(2)').text(`Estimated Total: $${totalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
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
    setInterval(nextSlide, 5000); // Change slide every 5 seconds
}

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


    </script>
</body>
</html>
