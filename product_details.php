<?php
session_start();
include 'db_connection.php';

$productId = $_GET['id'] ?? null;

if (!$productId) {
    header('Location: products.php');
    exit;
}

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Fetch product images
$stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$stmt->execute([$productId]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch product colors
$stmt = $pdo->prepare("SELECT * FROM product_colors WHERE product_id = ?");
$stmt->execute([$productId]);
$colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch product sizes
$stmt = $pdo->prepare("SELECT * FROM product_sizes WHERE product_id = ?");
$stmt->execute([$productId]);
$sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch related products
$stmt = $pdo->prepare("SELECT * FROM products WHERE subcategory_id = ? AND id != ? LIMIT 4");
$stmt->execute([$product['subcategory_id'], $productId]);
$relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> - Gajou Luxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .product-image-slider {
            height: 400px;
            width: 100%;
        }
        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .color-swatch {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .color-swatch.active {
            border-color: #007bff;
        }
        .size-btn {
            margin-right: 10px;
        }
        .related-product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .related-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <div class="swiper product-image-slider">
                    <div class="swiper-wrapper">
                        <?php foreach ($images as $image): ?>
                            <div class="swiper-slide">
                                <img src="<?php echo $image; ?>" alt="<?php echo $product['name']; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
            <div class="col-md-6">
                <h1><?php echo $product['name']; ?></h1>
                <p class="lead"><?php echo $product['description']; ?></p>
                <h2 class="mt-4">$<?php echo number_format($product['price'], 2); ?></h2>
                
                <?php if (!empty($colors)): ?>
                <div class="mt-4">
                    <h5>Colors:</h5>
                    <?php foreach ($colors as $color): ?>
                        <span class="color-swatch" style="background-color: <?php echo $color['color_code']; ?>" data-color-id="<?php echo $color['id']; ?>" data-color-name="<?php echo $color['color_name']; ?>"></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($sizes)): ?>
                <div class="mt-4">
                    <h5>Sizes:</h5>
                    <?php foreach ($sizes as $size): ?>
                        <button class="btn btn-outline-secondary size-btn" data-size-id="<?php echo $size['id']; ?>"><?php echo $size['size']; ?></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <button class="btn btn-primary btn-lg me-3" id="addToCartBtn">Add to Cart</button>
                    <button class="btn btn-outline-secondary btn-lg" id="addToWishlistBtn">
                        <i class="far fa-heart"></i> Add to Wishlist
                    </button>
                </div>
            </div>
        </div>

        <div class="row mt-5">
    <h3>Related Products</h3>
    <?php foreach ($relatedProducts as $relatedProduct): ?>
        <div class="col-md-3 mt-3">
            <div class="card related-product-card">
                <?php
                // Fetch the primary image for the related product
                $stmtImage = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
                $stmtImage->execute([$relatedProduct['id']]);
                $primaryImage = $stmtImage->fetchColumn();
                
                // Use a default image if no primary image is found
                $imageUrl = $primaryImage ?: 'path/to/default-image.jpg';
                ?>
                <img src="<?php echo htmlspecialchars($imageUrl); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($relatedProduct['name']); ?></h5>
                    <p class="card-text">$<?php echo number_format($relatedProduct['price'], 2); ?></p>
                    <a href="product_details.php?id=<?php echo $relatedProduct['id']; ?>" class="btn btn-primary">View Details</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            let selectedColorId = null;
            let selectedSizeId = null;

            const swiper = new Swiper('.product-image-slider', {
                loop: true,
                pagination: {
                    el: '.swiper-pagination',
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            });

            $('.color-swatch').on('click', function() {
                $('.color-swatch').removeClass('active');
                $(this).addClass('active');
                selectedColorId = $(this).data('color-id');
                
                // Load variant images for the selected color
                loadVariantImages(selectedColorId);
            });

            $('.size-btn').on('click', function() {
                $('.size-btn').removeClass('active');
                $(this).addClass('active');
                selectedSizeId = $(this).data('size-id');
            });

            $('#addToCartBtn').on('click', function() {
                if (!selectedColorId || !selectedSizeId) {
                    alert('Please select both color and size');
                    return;
                }
                addToCart(<?php echo $productId; ?>, selectedColorId, selectedSizeId);
            });

            $('#addToWishlistBtn').on('click', function() {
                if (!selectedColorId || !selectedSizeId) {
                    alert('Please select both color and size');
                    return;
                }
                addToWishlist(<?php echo $productId; ?>, selectedColorId, selectedSizeId);
            });

            function loadVariantImages(colorId) {
                $.ajax({
                    url: 'get_variant_images.php',
                    method: 'GET',
                    data: { product_id: <?php echo $productId; ?>, color_id: colorId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.images.length > 0) {
                            swiper.removeAllSlides();
                            response.images.forEach(function(image) {
                                swiper.appendSlide(`<div class="swiper-slide"><img src="${image}" alt="<?php echo $product['name']; ?>"></div>`);
                            });
                            swiper.update();
                        }
                    },
                    error: function() {
                        console.error('Failed to load variant images');
                    }
                });
            }

            function addToCart(productId, colorId, sizeId) {
                $.ajax({
                    url: 'add_to_cart.php',
                    method: 'POST',
                    data: { product_id: productId, color_id: colorId, size_id: sizeId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Product added to cart successfully!');
                        } else {
                            alert('Failed to add product to cart. Please try again.');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }

            function addToWishlist(productId, colorId, sizeId) {
                $.ajax({
                    url: 'add_to_wishlist.php',
                    method: 'POST',
                    data: { product_id: productId, color_id: colorId, size_id: sizeId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Product added to wishlist successfully!');
                        } else {
                            alert(response.message || 'Failed to add product to wishlist. Please try again.');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        });
    </script>
</body>
</html>