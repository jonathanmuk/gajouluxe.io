<?php
session_start();
include 'check_admin.php';
include 'db_connection.php';

function logDebug($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'debug_log.txt');
}


// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to log errors
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'error_log.txt');
}

// Check if the user is logged in and has admin privileges
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
    try {
        logDebug("Starting product insertion process");

        // Start transaction
        $pdo->beginTransaction();
        logDebug("Transaction started");

        $productName = $_POST['product_name'];
        $productDescription = $_POST['product_description'];
        $productPrice = $_POST['product_price'];
        $category_id = $_POST['category_id'];
        $subcategoryId = $_POST['subcategory_id'];
        $isSale = isset($_POST['is_sale']) ? 1 : 0;
        $salePrice = $isSale ? $_POST['sale_price'] : null;
        $isNew = isset($_POST['is_new']) ? 1 : 0;

        logDebug("Product data: " . json_encode($_POST));

        // Insert product
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, subcategory_id, is_sale, sale_price, is_new) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$productName, $productDescription, $productPrice, $category_id, $subcategoryId, $isSale, $salePrice, $isNew]);
        
        if ($result) {
            $productId = $pdo->lastInsertId();
            logDebug("Product inserted with ID: " . $productId);
        } else {
            logDebug("Failed to insert product. Error: " . json_encode($stmt->errorInfo()));
            throw new Exception("Failed to insert product");
        }

        $primaryImagePath = null;
        $primaryImageVariant = isset($_POST['primary_image_variant']) ? $_POST['primary_image_variant'] : null;
        $primaryImageIndex = isset($_POST['primary_image_index']) ? $_POST['primary_image_index'] : null;

        // Handle colors/textures, their images, and sizes
        foreach ($_POST['variants'] as $variantIndex => $variant) {
            logDebug("Processing variant: " . json_encode($variant));
            
            // Check product type and handle accordingly
            if ($_POST['product_type'] === 'texture') {
                // Handle texture-based product
                if (isset($_FILES['variants']['name'][$variantIndex]['texture_sample'])) {
                    $textureName = $variant['color_name']; // Using color_name field for texture name
                    $textureSampleFile = $_FILES['variants']['name'][$variantIndex]['texture_sample'];
                    $textureTmp = $_FILES['variants']['tmp_name'][$variantIndex]['texture_sample'];
                    
                    $uploadDir = 'uploads/textures/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $textureExtension = pathinfo($textureSampleFile, PATHINFO_EXTENSION);
                    $uniqueTextureName = uniqid() . '_texture.' . $textureExtension;
                    $targetPath = $uploadDir . $uniqueTextureName;
                    
                    if (move_uploaded_file($textureTmp, $targetPath)) {
                        // Insert into product_textures
                        $stmt = $pdo->prepare("INSERT INTO product_textures (product_id, texture_name, texture_sample_path) VALUES (?, ?, ?)");
                        $stmt->execute([$productId, $textureName, $targetPath]);
                        $textureId = $pdo->lastInsertId();
                        
                        // Use texture_id for subsequent operations
                        $variantId = $textureId;
                    }
                }
            } else {
                // Handle color-based product
                $colorName = $variant['color_name'];
                $colorCode = $variant['color_code'];
                
                $stmt = $pdo->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
                $stmt->execute([$productId, $colorName, $colorCode]);
                $variantId = $pdo->lastInsertId();
            }
        
            // Handle images
            if (isset($_FILES['variants']['name'][$variantIndex]['images'])) {
                $images = $_FILES['variants']['name'][$variantIndex]['images'];
                $tmpNames = $_FILES['variants']['tmp_name'][$variantIndex]['images'];
                
                foreach ($images as $key => $imageName) {
                    $tmpName = $tmpNames[$key];
                    $uploadDir = 'uploads/products/';
                    $imageExtension = pathinfo($imageName, PATHINFO_EXTENSION);
                    $uniqueImageName = uniqid() . '.' . $imageExtension;
                    $targetPath = $uploadDir . $uniqueImageName;
                    
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $isPrimary = ($variantIndex == $_POST['primary_image_variant'] && 
                                     $key == $_POST['primary_image_index']) ? 1 : 0;
                        
                        // Store the primary image path if this is the primary image
                        if ($isPrimary) {
                            $primaryImagePath = $targetPath;
                        }
                        
                        // Insert into product_images with appropriate ID
                        if ($_POST['product_type'] === 'texture') {
                            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, texture_id, image_path, is_primary) VALUES (?, ?, ?, ?)");
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, color_id, image_path, is_primary) VALUES (?, ?, ?, ?)");
                        }
                        $stmt->execute([$productId, $variantId, $targetPath, $isPrimary]);
                    }
                }
            }
        
            // Handle sizes
            foreach ($variant['sizes'] as $size => $quantity) {
                if ($quantity > 0) {
                    // Insert into product_sizes with appropriate ID
                    if ($_POST['product_type'] === 'texture') {
                        $stmt = $pdo->prepare("INSERT INTO product_sizes (product_id, texture_id, size) VALUES (?, ?, ?)");
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO product_sizes (product_id, color_id, size) VALUES (?, ?, ?)");
                    }
                    $stmt->execute([$productId, $variantId, $size]);
                    $sizeId = $pdo->lastInsertId();
        
                    // Insert into product_variants with appropriate ID
                    if ($_POST['product_type'] === 'texture') {
                        $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, texture_id, size_id, size, stock_quantity) VALUES (?, ?, ?, ?, ?)");
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, color_id, size_id, size, stock_quantity) VALUES (?, ?, ?, ?, ?)");
                    }
                    $stmt->execute([$productId, $variantId, $sizeId, $size, $quantity]);
                }
            }
        }

        logDebug("Primary image data: variant=" . $_POST['primary_image_variant'] . ", index=" . $_POST['primary_image_index']);


       // Update product with primary image path
if (isset($primaryImagePath)) {
    $stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");
    $result = $stmt->execute([$primaryImagePath, $productId]);

    if ($result) {
        logDebug("Product updated with primary image: " . $primaryImagePath);
    } else {
        logDebug("Failed to update product with primary image. Error: " . json_encode($stmt->errorInfo()));
        throw new Exception("Failed to update product with primary image");
    }
} else {
    logDebug("No primary image selected for product ID: " . $productId);
}


        // Commit transaction
        $pdo->commit();
        logDebug("Transaction committed successfully");

        // Redirect to a success page or refresh the current page
        header('Location: admin_product.php?success=1');
        exit;
    } catch (Exception $e) {
        // Rollback the transaction in case of any error
        $pdo->rollBack();
        logError("Error: " . $e->getMessage());
        logDebug("Transaction rolled back due to error: " . $e->getMessage());
        header('Location: admin_product.php?error=1');
        exit;
    }
}
?>


<html>
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
        .texture-sample-preview {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border: 1px solid #ced4da;
        border-radius: 5px;
        margin-top: 10px;
}
    </style>
</head>
<body>
    <a href="logout.php" class="btn btn-danger">Logout</a>
        <div class="admin-container">
            <h1 class="mb-4">Add New Product</h1>

            <?php if (isset($_GET['delete_success'])): ?>
                <div class="alert alert-success" role="alert">Product deleted successfully!</div>
            <?php endif; ?>

            <?php if (isset($_GET['delete_error'])): ?>
                <div class="alert alert-danger" role="alert">An error occurred while deleting the product. Please try again.</div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" role="alert">Product updated successfully!</div>
            <?php endif; ?>

            <form id="productForm" method="POST" enctype="multipart/form-data">
                <!-- Add these hidden fields right after the form opening tag -->
                <input type="hidden" name="primary_image_variant" value="">
                <input type="hidden" name="primary_image_index" value="">
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
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_sale" name="is_sale">
                            <label class="form-check-label" for="is_sale">Is Sale Item</label>
                        </div>
                    </div>
                    <div class="mb-3" id="sale_price_container" style="display: none;">
                        <label for="sale_price" class="form-label">Sale Price</label>
                        <input type="number" class="form-control" id="sale_price" name="sale_price" step="0.01">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_new" name="is_new">
                            <label class="form-check-label" for="is_new">Is New Item</label>
                        </div>
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
                    <div class="mb-3">
                        <label for="product_type" class="form-label">Product Type</label>
                        <select class="form-select" id="product_type" name="product_type" required>
                            <option value="color">Color-based</option>
                            <option value="texture">Texture-based</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary next-step">Next</button>
                </div>

                <div class="form-step" data-step="2">
                    <h3>Colors/Textures and Sizes</h3>
                    <div id="variantContainer">
                <div class="variant-group mb-4">
                    <h4 class="variant-title">Color/Texture 1</h4>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="variants[0][color_name]" required>
                    </div>
                    <div class="mb-3 color-input">
                        <label class="form-label">Color Code</label>
                        <input type="color" class="form-control form-control-color" name="variants[0][color_code]">
                    </div>
                    <!-- Texture input (shown for texture-based products) -->
                    <div class="mb-3 texture-input" style="display: none;">
                        <label class="form-label">Texture Sample</label>
                        <input type="file" class="form-control texture-sample" name="variants[0][texture_sample]" accept="image/*">
                        <small class="form-text text-muted">Upload a small sample image of the texture pattern (recommended size: 100x100px)</small>
                        <div class="texture-preview mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Images</label>
                        <input type="file" class="form-control variant-images" name="variants[0][images][]" multiple required>
                    </div>
                    <div class="mb-3">
                        <h5>Sizes and Quantities</h5>
                        <div class="row">
                            <div class="col">
                                <label class="form-label">Small</label>
                                <input type="number" class="form-control" name="variants[0][sizes][S]" min="0" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label">Medium</label>
                                <input type="number" class="form-control" name="variants[0][sizes][M]" min="0" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label">Large</label>
                                <input type="number" class="form-control" name="variants[0][sizes][L]" min="0" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label">X-Large</label>
                                <input type="number" class="form-control" name="variants[0][sizes][XL]" min="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary mb-3" id="addVariant">Add Color/Texture</button>

            <div class="mb-3">
    <label for="primary_image" class="form-label">Primary Image</label>
    <select class="form-select" id="primary_image" name="primary_image" required>
        <option value="">Select primary image</option>
    </select>
</div>

            <button type="submit" class="btn btn-success">Save Product</button>
            <button type="button" class="btn btn-secondary prev-step">Previous</button>
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
                    <div class="mb-3">
                        <label for="newCategoryImage" class="form-label">Category Image Path</label>
                        <input type="file" class="form-control" id="newCategoryImage" name="category_image" required>
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

    // Step navigation
    $('.next-step').click(function() {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    });

    $('.prev-step').click(function() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    // Variant addition
    let variantCount = 1;
    $('#addVariant').click(function() {
    variantCount++;
    const newVariant = $('.variant-group').first().clone();
    newVariant.find('input').val('');
    newVariant.find('textarea').val('');
    newVariant.find('.texture-preview').empty();
    
    const productType = $('#product_type').val();
    newVariant.find('.variant-title').text(productType === 'texture' ? `Texture ${variantCount}` : `Color ${variantCount}`);
    
    newVariant.find('input, textarea').each(function() {
        const name = $(this).attr('name');
        $(this).attr('name', name.replace('[0]', `[${variantCount - 1}]`));
    });
    
    $('#variantContainer').append(newVariant);
    updatePrimaryImageOptions();
});


    // Update primary image options when images or color names change
    function updatePrimaryImageOptions() {
    const $primaryImageSelect = $('#primary_image');
    $primaryImageSelect.empty().append('<option value="">Select primary image</option>');

    $('.variant-images').each(function(variantIndex) {
        const variantName = $(this).closest('.variant-group').find('input[name$="[color_name]"]').val() || `Color/Texture ${variantIndex + 1}`;
        const files = this.files;
        
        if (files.length > 0) {
            for (let i = 0; i < files.length; i++) {
                $primaryImageSelect.append(`<option value="${variantIndex},${i}">${variantName} - Image ${i + 1}</option>`);
            }
        } else {
            $primaryImageSelect.append(`<option value="" disabled>${variantName} - No images uploaded</option>`);
        }
    });
}


    // Re-run image option update whenever a new image is added or changed
    $(document).on('change', '.variant-images, input[name$="[color_name]"]', updatePrimaryImageOptions);

    // Handle primary image selection and set hidden fields for primary image variant and index
    $('#primary_image').change(function() {
    const selectedValue = $(this).val();
    if (selectedValue) {
        const [variantIndex, imageIndex] = selectedValue.split(',');
        $('input[name="primary_image_variant"]').val(variantIndex);
        $('input[name="primary_image_index"]').val(imageIndex);
    }
});

    // Form submission with primary image data
    $('#productForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);

        // Add primary image selection to form data
    const primaryImageSelection = $('#primary_image').val();
    if (primaryImageSelection) {
        const [variantIndex, imageIndex] = primaryImageSelection.split(',');
        formData.set('primary_image_variant', variantIndex);
        formData.set('primary_image_index', imageIndex);
    }

        $.ajax({
            url: 'admin_product.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                window.location.href = 'admin_product.php?success=1';
            },
            error: function(xhr, status, error) {
            console.error('Error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            alert('An error occurred while saving the product. Please check the console for more details.');
        }
        });
    });

    // Update subcategories based on category selection
    $('#category_id').change(function() {
        const categoryId = $(this).val();
        if (categoryId) {
            $.ajax({
                url: 'get_subcategories.php',
                method: 'GET',
                data: { category_id: categoryId },
                dataType: 'json',
                success: function(response) {
                    if (response.subcategories) {
                        let options = '<option value="">Select a subcategory</option>';
                        response.subcategories.forEach(function(subcategory) {
                            options += `<option value="${subcategory.id}">${subcategory.name}</option>`;
                        });
                        $('#subcategory_id').html(options);
                    } else if (response.error) {
                        console.error('Error:', response.error);
                        alert('Error loading subcategories. Please try again.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    alert('Error loading subcategories. Please try again.');
                }
            });
        } else {
            $('#subcategory_id').html('<option value="">Select a subcategory</option>');
        }
    });

    // Product type change (hide color picker for 'texture' types)
    $('#product_type').change(function() {
    const productType = $(this).val();
    if (productType === 'texture') {
        $('.color-input').hide();
        $('.texture-input').show();
        $('.variant-name-label').text('Texture Name');
        $('.variant-title').each(function(index) {
            $(this).text(`Texture ${index + 1}`);
        });
    } else {
        $('.color-input').show();
        $('.texture-input').hide();
        $('.variant-name-label').text('Color Name');
        $('.variant-title').each(function(index) {
            $(this).text(`Color ${index + 1}`);
        });
    }
});
// Add texture preview functionality
$(document).on('change', '.texture-sample', function(e) {
    const file = e.target.files[0];
    const preview = $(this).siblings('.texture-preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.html(`<img src="${e.target.result}" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ced4da; border-radius: 5px;">`);
        }
        reader.readAsDataURL(file);
    } else {
        preview.empty();
    }
});

    // Save new category via modal
    $('#saveNewCategory').click(function() {
        const formData = new FormData();
        formData.append('name', $('#newCategoryName').val());
        formData.append('slug', $('#newCategorySlug').val());
        formData.append('category_image', $('#newCategoryImage')[0].files[0]);

        $.ajax({
            url: 'add_category.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    $('#category_id').append(`<option value="${data.id}">${data.name}</option>`);
                    $('#category_id').val(data.id);
                    $('#addCategoryModal').modal('hide');
                    $('#newCategoryName').val('');
                    $('#newCategorySlug').val('');
                    $('#newCategoryImage').val('');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', jqXHR.responseText);
                alert('AJAX error: ' + textStatus + ' - ' + errorThrown + '\n' + jqXHR.responseText);
            }
        });
    });

    // Sale price toggle
    $('#is_sale').change(function() {
        if ($(this).is(':checked')) {
            $('#sale_price_container').show();
        } else {
            $('#sale_price_container').hide();
            $('#sale_price').val('');
        }
    });
});




        // Initialize the modal
var addSubcategoryModal = new bootstrap.Modal(document.getElementById('addSubcategoryModal'));

// Show the modal when the "Add New" button is clicked
$('[data-bs-target="#addSubcategoryModal"]').click(function() {
    addSubcategoryModal.show();
});

// Handle saving the new subcategory
$('#saveNewSubcategory').click(function() {
    const subcategoryName = $('#newSubcategoryName').val();
    const subcategorySlug = $('#newSubcategorySlug').val();
    const categoryId = $('#category_id').val();
    if (subcategoryName && subcategorySlug && categoryId) {
        $.ajax({
            url: 'add_subcategory.php',
            method: 'POST',
            data: { name: subcategoryName, slug: subcategorySlug, category_id: categoryId },
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    $('#subcategory_id').append(`<option value="${data.id}">${data.name}</option>`);
                    $('#subcategory_id').val(data.id);
                    addSubcategoryModal.hide();
                    $('#newSubcategoryName').val('');
                    $('#newSubcategorySlug').val('');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('AJAX error: ' + textStatus + ' - ' + errorThrown);
            }
        });
    } else {
        alert('Please fill all fields and select a category');
    }
});



    </script>
</body>
</html>



