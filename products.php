<?php
session_start();
include 'db_connection.php';
// Function to get cart item count
function getCartItemCount() {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return array_sum($_SESSION['cart']);
    }
    return 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gajou Luxe - Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/14.6.3/nouislider.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/14.6.3/nouislider.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .header {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            padding:15px 0;
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
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            overflow: hidden;
            cursor: pointer;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            transition: transform 0.3s ease;
            position: relative;
        }
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        .category-btn {
            transition: all 0.3s ease;
        }
        .category-btn:hover, .category-btn.active {
            background-color: black;
            color: #44c7d4;
        }
        .subcategory-btn {
            transition: all 0.3s ease;
        }
        .subcategory-btn:hover, .subcategory-btn.active {
            background-color: black;
            color: #44c7d4;
        }
        .wishlist-btn, .add-to-cart-btn {
        background-color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        }
        .wishlist-btn:hover, .add-to-cart-btn:hover {
        background-color: #f8f9fa;
        transform: scale(1.1);
        }
        .wishlist-btn i {
        color: #ccc;
        transition: all 0.3s ease;
        }

        .wishlist-btn.active i {
         color: #e74c3c;
        }
        .badge {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        }
        .card-body {
        padding: 0.5rem;
        }
        .badge-new, .badge-sale {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
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
        #priceRange {
            margin-top: 20px;
        }
        .sidebar {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 15px;
            height: calc(100vh - 100px);
            position: sticky;
            top: 20px;
            overflow-y: auto;
        }
        .filter-header {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 15px;
        color: #333;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 8px;
    }
        .filter-section {
            margin-bottom: 20px;
        }
        .filter-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #555;
            font-size: 0.9 rem;
        }
        .color-filters, .size-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
        .color-filter input[type="checkbox"] {
            display: none;
        }
        .color-filter label {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px #ced4da;
            transition: all 0.3s ease;
        }
        .color-filter input[type="checkbox"]:checked + label {
            box-shadow: 0 0 0 2px #6e8efb;
            transform: scale(1.1);
        }
        .size-filter input[type="checkbox"] {
            display: none;
        }
        .size-filter label {
            display: inline-block;
            font-size: 0.8rem;
            padding: 3px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .size-filter input[type="checkbox"]:checked + label {
            background-color: #6e8efb;
            color: #fff;
            border-color: #6e8efb;
        }
        #applyFilters {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        #applyFilters:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .price-inputs {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        }

        .price-input {
        width: 45%;
        padding: 5px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        font-size: 0.9rem;
        text-align: center;
        }
        .noUi-connect {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
        }
        .noUi-handle {
        border: 1px solid #6e8efb;
        background: #fff;
        border-radius: 50%;
        cursor: pointer;
        width: 18px !important;
        height: 18px !important;
        right: -9px !important;
        top: -5px !important;
        }
        .noUi-handle:before, .noUi-handle:after {
            display: none;
        }
        .noUi-tooltip {
        display: none;
        display: none;
        font-size: 0.8rem;
        padding: 2px 5px;           
        }
        .noUi-active .noUi-tooltip {
        display: block;
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
        .search-container {
        position: relative;
        max-width: 300px;
        margin: 0 auto 20px;
        }

        #searchInput {
        width: 100%;
        padding: 10px 40px 10px 20px;
        border-radius: 25px;
        border: 1px solid #ddd;
        transition: all 0.3s ease;
        }

        #searchInput:focus {
        outline: none;
        box-shadow: 0 0 5px rgba(81, 203, 238, 1);
        border: 1px solid rgba(81, 203, 238, 1);
        }

        .search-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
        }
        .card-text.category {
        font-size: 0.9em;
        color: #666;
        }

        .card-text.price {
        font-weight: bold;
        }
        .no-results {
        text-align: center;
        font-size: 1.2em;
        color: #666;
        margin-top: 20px;
        }
        .wishlist-badge {
        position: absolute;
        top: -8px;
        right: -8px;
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
        .flaticon {
            width: 24px;
            height: 24px;
            display: inline-block;
            vertical-align: middle;
        }

        .product-actions {
            position: absolute;
            bottom: 10px;
            right: 10px;
            left: 10px;
            display: flex;
            justify-content: space-between;
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
        .card-title {
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
        }

        .card-text {
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
        }
        .noUi-target {
        height: 8px;
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="wishlist.php" class="icon-button">
                        <img src="icons/heart.png" alt="wishlist" class="flaticon">
                        <span class="wishlist-badge">0</span>
                    </a>
                <?php endif; ?>
                <a href="cart.php" class="icon-button">
                   <img src="icons/shopping-cart.png" alt="Cart" class="flaticon">
                    <span class="cart-badge"><?php echo getCartItemCount(); ?></span>
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



    <div class="container-fluid mt-5">
        <div class="row">
            <!-- Sidebar for filters -->
            <div class="col-md-3">
    <div class="sidebar">
        <h4 class="filter-header">Refine Your Search</h4>
        <div class="filter-section">
            <h5 class="filter-title">Price Range</h5>
            <div id="priceRange"></div>
            <div class="price-inputs">
                <input type="text" id="priceMin" class="price-input" readonly>
                <input type="text" id="priceMax" class="price-input" readonly>
            </div>
        </div>
        <div class="filter-section">
            <h5 class="filter-title">Colors</h5>
            <div id="colorFilters" class="color-filters"></div>
        </div>
        <div class="filter-section">
            <h5 class="filter-title">Sizes</h5>
            <div id="sizeFilters" class="size-filters"></div>
        </div>
        <button id="applyFilters" class="btn btn-primary w-100">Apply Filters</button>
    </div>
</div>

            <!-- Main content -->
            <div class="col-md-9">
            <div class="search-container mb-3">
                <input type="text" id="searchInput" placeholder="Search products...">
                <i class="fas fa-search search-icon"></i>
            </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-center" id="categoryButtons">
                            <!-- Category buttons will be dynamically added here -->
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-center" id="subcategoryButtons">
                            <!-- Subcategory buttons will be dynamically added here -->
                        </div>
                    </div>
                </div>

                <div class="row" id="productGrid">
                    <!-- Product cards will be dynamically added here -->
                </div>

                <!-- Pagination -->
                <nav aria-label="Product navigation">
                    <ul class="pagination justify-content-center">
                        <!-- Pagination will be dynamically added here -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/nouislider@14.6.3/distribute/nouislider.min.js"></script>
    <script>
        AOS.init();

        let selectedColor = null;
        let selectedSize = null;
        $(document).ready(function() {
    loadProducts();
    loadFilters();
    loadCategories();
    updateCartBadge();
    updateWishlistBadge();

    // Wishlist button click handler
    $(document).on('click', '.wishlist-btn', function(e) {
    e.stopPropagation();
    let productId = $(this).data('product-id');
    let button = $(this);

    $.ajax({
        url: 'toggle_wishlist.php',
        method: 'POST',
        data: { product_id: productId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                button.toggleClass('active');
                if (response.action === 'added') {
                    alert('Item added to wishlist!');
                } else {
                    alert('Item removed from wishlist!');
                }
                updateWishlistBadge();
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert('An error occurred. Please try again.');
        }
    });
});


    // Search functionality
    $('#searchInput').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'search_autocomplete.php',
                method: 'GET',
                data: { query: request.term },
                success: function(data) {
                    response(JSON.parse(data));
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            loadProducts(1, { search: ui.item.value });
        }
    });

    $('#searchInput').on('keyup', function(e) {
        if (e.keyCode === 13) {
            loadProducts(1, { search: $(this).val() });
        }
    });

    // Apply filters button click handler
    $('#applyFilters').on('click', function() {
        let selectedColors = $('.color-checkbox:checked').map(function() {
            return this.value;
        }).get();

        let selectedSizes = $('.size-checkbox:checked').map(function() {
            return this.value;
        }).get();

        let priceRange = $('#priceRange')[0].noUiSlider.get();

        let filters = {
            colors: selectedColors,
            sizes: selectedSizes,
            minPrice: Math.round(priceRange[0]),
            maxPrice: Math.round(priceRange[1])
        };

        loadProducts(1, filters);
    });

    // Pagination click handler
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        let page = $(this).data('page');
        loadProducts(page);
    });

    // Product card click handler
    $(document).on('click', '.product-card', function(e) {
        if (!$(e.target).hasClass('add-to-cart-btn') && !$(e.target).hasClass('wishlist-btn')) {
            let productId = $(this).data('product-id');
            window.location.href = 'product_details.php?id=' + productId;
        }
    });

    // Add to cart button click handler
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.stopPropagation();
        let productId = $(this).data('product-id');
        addToCart(productId);
    });

    // Category button click handler
    $(document).on('click', '.category-btn', function() {
        $('.category-btn').removeClass('active');
        $(this).addClass('active');
        let categoryId = $(this).data('category-id');
        loadSubcategories(categoryId);
        loadProducts(1, { category_id: categoryId });
    });

    // Subcategory button click handler
    $(document).on('click', '.subcategory-btn', function() {
        $('.subcategory-btn').removeClass('active');
        $(this).addClass('active');
        let subcategoryId = $(this).data('subcategory-id');
        loadProducts(1, { subcategory_id: subcategoryId });
    });
});

function loadProducts(page = 1, filters = {}) {
    $.ajax({
        url: 'get_products.php',
        method: 'GET',
        data: { page: page, ...filters },
        dataType: 'json',
        success: function(response) {
            if (response.totalProducts > 0) {
                let productsHtml = '';
                response.products.forEach(product => {
                    let salePrice = product.is_sale ? product.sale_price : product.price;
                    let wishlistClass = product.is_in_wishlist ? 'active' : '';
                    productsHtml += `
                        <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up">
                            <div class="card product-card" data-product-id="${product.id}">
                                <div class="product-image" style="background-image: url(${product.image_url});">
                                    ${product.is_new ? '<span class="badge badge-new">New</span>' : ''}
                                    ${product.is_sale ? '<span class="badge badge-sale">Sale</span>' : ''}
                                    <div class="product-actions">
                                        <button class="wishlist-btn ${wishlistClass}" data-product-id="${product.id}">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                        <button class="add-to-cart-btn" data-product-id="${product.id}">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">${product.name}</h5>
                                    <p class="card-text category">${product.category_name}</p>
                                    <p class="card-text price">
                                        ${product.is_sale 
                                            ? `<s>$${parseFloat(product.price).toFixed(2)}</s> <span class="sale-price">$${parseFloat(salePrice).toFixed(2)}</span>`
                                            : `$${parseFloat(product.price).toFixed(2)}`}
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#productGrid').html(productsHtml);
                updatePagination(response.totalPages, page);
            } else {
                $('#productGrid').html('<p class="no-results">No results found</p>');
                $('.pagination').empty();
            }
            AOS.refresh();
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            $('#productGrid').html('<p>Error loading products. Please try again later.</p>');
        }
    });
}


function updatePagination(totalPages, currentPage) {
    let paginationHtml = '';
    for (let i = 1; i <= totalPages; i++) {
        paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>`;
    }
    $('.pagination').html(paginationHtml);
}

function loadFilters() {
    $.ajax({
        url: 'get_filters.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.colors && response.sizes && response.minPrice !== undefined && response.maxPrice !== undefined) {
                populateColorFilters(response.colors);
                populateSizeFilters(response.sizes);
                initializePriceRangeSlider(response.minPrice, response.maxPrice);
            } else {
                console.error('Invalid filter data received:', response);
            }
        },
        error: function() {
            console.error('Error loading filters');
        }
    });
}

function populateColorFilters(colors) {
    let colorFiltersHtml = colors.map(color => `
        <div class="color-filter">
            <input type="checkbox" id="color-${color}" value="${color}" class="color-checkbox">
            <label for="color-${color}" style="background-color: ${color};"></label>
        </div>`
    ).join('');
    $('#colorFilters').html(colorFiltersHtml);
}

function populateSizeFilters(sizes) {
    let sizeFiltersHtml = sizes.map(size => `
        <div class="size-filter">
            <input type="checkbox" id="size-${size}" value="${size}" class="size-checkbox">
            <label for="size-${size}">${size}</label>
        </div>`
    ).join('');
    $('#sizeFilters').html(sizeFiltersHtml);
}

function initializePriceRangeSlider(minPrice, maxPrice) {
    let priceRange = document.getElementById('priceRange');
    if (priceRange.noUiSlider) {
        priceRange.noUiSlider.destroy();
    }
    noUiSlider.create(priceRange, {
        start: [minPrice, maxPrice],
        connect: true,
        range: {
            'min': minPrice,
            'max': maxPrice
        },
        step: 1,
        format: {
            to: function (value) {
                return '$' + Math.round(value);
            },
            from: function (value) {
                return Number(value.replace('$', ''));
            }
        }
    });

    let priceMinInput = document.getElementById('priceMin');
    let priceMaxInput = document.getElementById('priceMax');

    priceRange.noUiSlider.on('update', function (values, handle) {
        if (handle === 0) {
            priceMinInput.value = values[0];
        } else {
            priceMaxInput.value = values[1];
        }
    });
}

function addToCart(productId, color = null, size = null) {
    $.ajax({
        url: 'add_to_cart.php',
        method: 'POST',
        data: { product_id: productId, color: color, size: size },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Added to cart successfully!');
                updateCartBadge();
            } else {
                alert('Failed to add product to cart. Please try again.');
            }
        }
    });
}


function loadCategories() {
    $.ajax({
        url: 'get_categories.php',
        method: 'GET',
        success: function(response) {
            $('#categoryButtons').html(response);
        }
    });
}

function loadSubcategories(categoryId) {
    $.ajax({
        url: 'get_subcategories.php',
        method: 'GET',
        data: { category_id: categoryId },
        dataType: 'json',
        success: function(response) {
            if (response.subcategories) {
                let subcategoriesHtml = response.subcategories.map(subcategory => 
                    `<button class="btn btn-outline-secondary subcategory-btn me-2 mb-2" data-subcategory-id="${subcategory.id}">${subcategory.name}</button>`
                ).join('');
                $('#subcategoryButtons').html(subcategoriesHtml);
            } else {
                $('#subcategoryButtons').html('<p>No subcategories found</p>');
            }
        },
        error: function() {
            $('#subcategoryButtons').html('<p>Error loading subcategories</p>');
        }
    });
}
function updateCartBadge() {
    $.ajax({
        url: 'get_cart_count.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            $('.cart-badge').text(response.count);
        }
    });
}

function updateWishlistBadge() {
    $.ajax({
        url: 'get_wishlist_count.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            $('.wishlist-badge').text(response.count);
        }
    });
}


        loadCategories();
    </script>
</body>
</html>