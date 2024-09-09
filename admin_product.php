<?php
session_start();
include 'check_admin.php';
include 'db_connection.php';

// Check if the user is logged in and has admin privileges
// You should implement proper authentication and authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch categories for the dropdown
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch subcategories for the dropdown
$stmt = $pdo->query("SELECT * FROM subcategories");
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    $productName = $_POST['product_name'];
    $productDescription = $_POST['product_description'];
    $productPrice = $_POST['product_price'];
    $category_id = $_POST['category_id'];
    $subcategoryId = $_POST['subcategory_id'];

    // Insert product
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, subcategory_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$productName, $productDescription, $productPrice, $subcategoryId]);
    $productId = $pdo->lastInsertId();

    // Handle product images
    $uploadDir = 'uploads/';
    foreach ($_FILES['product_images']['tmp_name'] as $key => $tmpName) {
        $fileName = basename($_FILES['product_images']['name'][$key]);
        $targetFilePath = $uploadDir . $fileName;
        if (move_uploaded_file($tmpName, $targetFilePath)) {
            $isPrimary = ($key === 0) ? 1 : 0; // Set the first image as primary
            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
            $stmt->execute([$productId, $targetFilePath, $isPrimary]);
        }
    }

    // Handle colors
    foreach ($_POST['colors'] as $key => $colorName) {
        $colorCode = $_POST['color_codes'][$key];
        $stmt = $pdo->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
        $stmt->execute([$productId, $colorName, $colorCode]);
    }

    // Handle sizes
    foreach ($_POST['sizes'] as $size) {
        $stmt = $pdo->prepare("INSERT INTO product_sizes (product_id, size) VALUES (?, ?)");
        $stmt->execute([$productId, $size]);
    }

    // Handle variants
    foreach ($_POST['variants'] as $variant) {
        $colorId = $variant['color_id'];
        $sizeId = $variant['size_id'];
        $stockQuantity = $variant['stock_quantity'];
        $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, color_id, size_id, stock_quantity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$productId, $colorId, $sizeId, $stockQuantity]);
    }

    // Redirect to a success page or refresh the current page
    header('Location: admin_product.php?success=1');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add New Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            display: inline-block;
            margin-left: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<a href="logout.php" class="btn btn-danger">Logout</a>
    <div class="admin-container">
        <h1 class="mb-4">Add New Product</h1>
        <?php if (isset($_GET['delete_success'])): ?>
    <div class="alert alert-success" role="alert">
        Product deleted successfully!
    </div>
<?php endif; ?>

<?php if (isset($_GET['delete_error'])): ?>
    <div class="alert alert-danger" role="alert">
        An error occurred while deleting the product. Please try again.
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" role="alert">
        Product updated successfully!
    </div>
<?php endif; ?>
<form id="productForm" method="POST" enctype="multipart/form-data">
    <div class="form-step active" data-step="1">
        <h3>Basic Information</h3>
        <div class="mb-3">
            <label for="product_name" class="form-label">Product Name</label>
            <input type="text" class="form-control" id="product_name" name="product_name" required>
        </div>
        <div class="mb-3">
            <label for="product_description" class="form-label">Description</label>
            <textarea class="form-control" id="product_description" name="product_description" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label for="product_price" class="form-label">Price</label>
            <input type="number" class="form-control" id="product_price" name="product_price" step="0.01" required>
        </div>
        <div class="mb-3">
            <label for="category_id" class="form-label">Category</label>
            <div class="input-group">
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Add New</button>
            </div>
        </div>
        <div class="mb-3">
            <label for="subcategory_id" class="form-label">Subcategory</label>
            <div class="input-group">
                <select class="form-select" id="subcategory_id" name="subcategory_id" required>
                    <option value="">Select a subcategory</option>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?php echo $subcategory['id']; ?>"><?php echo htmlspecialchars($subcategory['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#addSubcategoryModal">Add New</button>
            </div>
        </div>
        <button type="button" class="btn btn-primary next-step">Next</button>
    </div>


            <div class="form-step" data-step="2">
                <h3>Images</h3>
                <div class="mb-3">
                    <label for="product_images" class="form-label">Product Images</label>
                    <input type="file" class="form-control" id="product_images" name="product_images[]" multiple accept="image/*" required>
                </div>
                <button type="button" class="btn btn-secondary prev-step">Previous</button>
                <button type="button" class="btn btn-primary next-step">Next</button>
            </div>

            <div class="form-step" data-step="3">
                <h3>Colors and Sizes</h3>
                <div id="colorContainer">
                    <div class="mb-3 color-group">
                        <label class="form-label">Color</label>
                        <div class="input-group">
                            <input type="text" class="form-control color-name" name="colors[]" placeholder="Color Name" required>
                            <input type="color" class="form-control form-control-color color-code" name="color_codes[]" required>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary mb-3" id="addColor">Add Color</button>

                <div id="sizeContainer">
                    <div class="mb-3 size-group">
                        <label class="form-label">Size</label>
                        <input type="text" class="form-control" name="sizes[]" required>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary mb-3" id="addSize">Add Size</button>

                <button type="button" class="btn btn-secondary prev-step">Previous</button>
                <button type="button" class="btn btn-primary next-step">Next</button>
            </div>

            <div class="form-step" data-step="4">
                <h3>Variants</h3>
                <div id="variantContainer"></div>
                <button type="button" class="btn btn-secondary prev-step">Previous</button>
                <button type="submit" class="btn btn-success">Save Product</button>
            </div>
        </form>
    </div>

    <!-- Add New Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
    <form id="addCategoryForm">
      <div class="mb-3">
        <label for="newCategoryName" class="form-label">Category Name</label>
        <input type="text" class="form-control" id="newCategoryName" required>
      </div>
      <div class="mb-3">
        <label for="newCategorySlug" class="form-label">Category Slug</label>
        <input type="text" class="form-control" id="newCategorySlug" required>
      </div>
    </form>
  </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveNewCategory">Save Category</button>
      </div>
    </div>
  </div>
</div>

<!-- Add New Subcategory Modal -->
<div class="modal fade" id="addSubcategoryModal" tabindex="-1" aria-labelledby="addSubcategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addSubcategoryModalLabel">Add New Subcategory</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
    <form id="addSubcategoryForm">
      <div class="mb-3">
        <label for="newSubcategoryName" class="form-label">Subcategory Name</label>
        <input type="text" class="form-control" id="newSubcategoryName" required>
      </div>
      <div class="mb-3">
        <label for="newSubcategorySlug" class="form-label">Subcategory Slug</label>
        <input type="text" class="form-control" id="newSubcategorySlug" required>
      </div>
    </form>
  </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveNewSubcategory">Save Subcategory</button>
      </div>
    </div>
  </div>
</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            let currentStep = 1;
    const totalSteps = $('.form-step').length;

    function showStep(step) {
        $('.form-step').removeClass('active');
        $(`.form-step[data-step="${step}"]`).addClass('active');
    }

    $('.next-step').click(function() {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);

            if (currentStep === 4) {
                generateVariants();
            }
        }
    });

    $('.prev-step').click(function() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

            $('#addColor').click(function() {
                const colorGroup = $('.color-group').first().clone();
                colorGroup.find('input').val('');
                $('#colorContainer').append(colorGroup);
            });

            $('#addSize').click(function() {
                const sizeGroup = $('.size-group').first().clone();
                sizeGroup.find('input').val('');
                $('#sizeContainer').append(sizeGroup);
            });

            function generateVariants() {
        const colors = $('.color-name').map(function() { return $(this).val(); }).get();
        const sizes = $('input[name="sizes[]"]').map(function() { return $(this).val(); }).get();
        let variantHtml = '';

        colors.forEach(function(color, colorIndex) {
            sizes.forEach(function(size, sizeIndex) {
                variantHtml += `
                    <div class="mb-3">
                        <label class="form-label">${color} - ${size}</label>
                        <input type="number" class="form-control" name="variants[${colorIndex}_${sizeIndex}][stock_quantity]" placeholder="Stock Quantity" required>
                        <input type="hidden" name="variants[${colorIndex}_${sizeIndex}][color_id]" value="${colorIndex + 1}">
                        <input type="hidden" name="variants[${colorIndex}_${sizeIndex}][size_id]" value="${sizeIndex + 1}">
                    </div>
                `;
            });
        });

        $('#variantContainer').html(variantHtml);
    }

        

            $('#saveNewCategory').click(function() {
    const categoryName = $('#newCategoryName').val();
    const categorySlug = $('#newCategorySlug').val();
    if (categoryName && categorySlug) {
        $.ajax({
            url: 'add_category.php',
            method: 'POST',
            data: { name: categoryName, slug: categorySlug },
            success: function(data) {
                const newCategory = JSON.parse(data);
                $('#category_id').append(`<option value="${newCategory.id}">${newCategory.name}</option>`);
                $('#category_id').val(newCategory.id);
                $('#addCategoryModal').modal('hide');
                $('#newCategoryName').val('');
                $('#newCategorySlug').val('');
            }
        });
    }
});


$('#saveNewSubcategory').click(function() {
    const subcategoryName = $('#newSubcategoryName').val();
    const subcategorySlug = $('#newSubcategorySlug').val();
    const categoryId = $('#category_id').val();
    if (subcategoryName && subcategorySlug && categoryId) {
        $.ajax({
            url: 'add_subcategory.php',
            method: 'POST',
            data: { name: subcategoryName, slug: subcategorySlug, category_id: categoryId },
            success: function(data) {
                const newSubcategory = JSON.parse(data);
                $('#subcategory_id').append(`<option value="${newSubcategory.id}">${newSubcategory.name}</option>`);
                $('#subcategory_id').val(newSubcategory.id);
                $('#addSubcategoryModal').modal('hide');
                $('#newSubcategoryName').val('');
                $('#newSubcategorySlug').val('');
            }
        });
    } else {
        alert('Please fill all fields and select a category');
    }
});
});

        $('#category_id').change(function() {
    const categoryId = $(this).val();
    $.ajax({
        url: 'get_subcategories.php',
        method: 'POST',
        data: { category_id: categoryId },
        success: function(data) {
            $('#subcategory_id').html(data);
        }
    });
});

    </script>
</body>
</html>



