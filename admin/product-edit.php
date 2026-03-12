<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/catalog.php';
require_once __DIR__ . '/../database/admin/catalog_management.php';

$productId = (int) ($_GET['id'] ?? $_POST['product_id'] ?? 0);
if ($productId <= 0) {
    header('Location: ' . app_path('/admin/products.php'));
    exit;
}

$setFlash = static function (string $message, string $type = 'success'): void {
    $_SESSION['admin_product_edit_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): ?array {
    $flash = $_SESSION['admin_product_edit_flash'] ?? null;
    unset($_SESSION['admin_product_edit_flash']);
    return is_array($flash) ? $flash : null;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_product_update($productId, $_POST, $_FILES);
        $setFlash('Product updated successfully.');
        header('Location: ' . app_path('/admin/product-edit.php?id=' . $productId));
        exit;
    } catch (Throwable $exception) {
        $setFlash($exception->getMessage(), 'error');
    }
}

$flash = $consumeFlash();
$product = fetch_product_by_id($productId, true);
if (!$product) {
    header('Location: ' . app_path('/admin/products.php'));
    exit;
}

$brands = catalog_fetch_brand_options();
$categories = catalog_fetch_category_options();
$subCategories = catalog_fetch_sub_category_options();
$images = catalog_fetch_product_images($productId);
$specs = catalog_fetch_product_specs($productId);
$colors = catalog_fetch_product_colors($productId);
$sizes = catalog_fetch_product_sizes($productId);
$primaryImageKey = !empty($images[0]['image_id']) ? 'existing:' . $images[0]['image_id'] : 'new:0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/product-edit.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-product-add">
        <header class="page-header">
            <a href="<?php echo htmlspecialchars(app_path('/admin/products.php')); ?>" class="page-back" aria-label="Back to products">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <h1>Edit Product</h1>
        </header>

        <form class="product-form" action="<?php echo htmlspecialchars(app_path('/admin/product-edit.php?id=' . $productId)); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
            <input type="hidden" name="primary_image_key" id="primaryImageKey" value="<?php echo htmlspecialchars($primaryImageKey); ?>">
            <div id="deletedImageInputs"></div>

            <div class="form-grid">
                <div class="form-left">
                    <label class="field">
                        <span>Name:</span>
                        <input type="text" name="name" value="<?php echo htmlspecialchars((string) $product['name']); ?>" required>
                    </label>

                    <label class="field select">
                        <span>Brand:</span>
                        <select name="brand" required>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo (int) $brand['brand_id']; ?>" <?php echo ((int) $product['brand_id'] === (int) $brand['brand_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="field price">
                        <span>Price:</span>
                        <div class="price-input">
                            <input type="number" name="price" value="<?php echo htmlspecialchars((string) $product['price']); ?>" min="0" step="1" required>
                            <span class="suffix">MMK</span>
                        </div>
                    </label>

                    <label class="field textarea block">
                        <span>Description:</span>
                        <textarea name="description" rows="7"><?php echo htmlspecialchars((string) $product['description']); ?></textarea>
                    </label>
                </div>

                <div class="form-right">
                    <label class="field">
                        <span>Stock:</span>
                        <input type="number" name="stock" value="<?php echo (int) $product['stock_quantity']; ?>" min="0" step="1" required>
                    </label>

                    <label class="field select">
                        <span>Category:</span>
                        <select name="category" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['category_id']; ?>" <?php echo ((int) $product['category_id'] === (int) $category['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="field select">
                        <span>Sub-Category:</span>
                        <select name="subcategory">
                            <option value="">Select sub-category</option>
                            <?php foreach ($subCategories as $subCategory): ?>
                                <option value="<?php echo (int) $subCategory['sub_category_id']; ?>" data-category-id="<?php echo (int) $subCategory['category_id']; ?>" <?php echo ((int) $product['sub_category_id'] === (int) $subCategory['sub_category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subCategory['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="field inline block">
                        <div class="inline-field">
                            <span>Is Featured:</span>
                            <select name="featured">
                                <option value="yes" <?php echo !empty($product['is_featured']) ? 'selected' : ''; ?>>Yes</option>
                                <option value="no" <?php echo empty($product['is_featured']) ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>
                        <div class="inline-field">
                            <span>Visibility:</span>
                            <select name="visibility">
                                <option value="visible" <?php echo !empty($product['visibility']) ? 'selected' : ''; ?>>Visible</option>
                                <option value="hidden" <?php echo empty($product['visibility']) ? 'selected' : ''; ?>>Hidden</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="images-edit">
                <div class="images-header">
                    <span>Images</span>
                </div>
                <input type="file" id="imageUploadInput" name="new_images[]" accept="image/*" multiple hidden>
                <div class="image-grid" id="editImageGrid">
                    <?php foreach ($images as $idx => $img): ?>
                        <div class="image-tile<?php echo $idx === 0 ? ' active' : ''; ?>" data-index="<?php echo $idx; ?>" data-image-id="<?php echo (int) $img['image_id']; ?>" data-image-key="existing:<?php echo (int) $img['image_id']; ?>">
                            <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="Product image">
                            <div class="image-tools">
                                <button type="button" class="icon-btn reupload" title="Upload new">
                                    <i class="fa-solid fa-upload"></i>
                                </button>
                                <label class="primary-radio" title="Set primary">
                                    <input type="radio" name="primary_image_marker" <?php echo !empty($img['is_primary']) ? 'checked' : ''; ?>>
                                    <span></span>
                                </label>
                                <button type="button" class="icon-btn delete" title="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="image-tile upload-tile" id="imageUploadTile">
                        <i class="fa-solid fa-upload"></i>
                    </div>
                </div>
            </div>

            <div class="specs-edit">
                <span>Specifications</span>
                <ul class="spec-list" id="editSpecList">
                    <?php foreach ($specs as $idx => $spec): ?>
                        <li class="spec-item" data-spec-id="spec-<?php echo $idx; ?>">
                            <span class="spec-dot"></span>
                            <span class="spec-text"><?php echo htmlspecialchars((string) $spec['spec_name']); ?></span>
                            <input type="hidden" name="spec_names[]" value="<?php echo htmlspecialchars((string) $spec['spec_name']); ?>">
                            <span class="spec-actions">
                                <button type="button" class="icon-btn edit"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button type="button" class="icon-btn delete"><i class="fa-regular fa-trash-can"></i></button>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="spec-row">
                    <span class="spec-dot"></span>
                    <input type="text" id="specInput" placeholder="New Specification...">
                    <button type="button" class="btn-spec-add" id="specAdd">Add</button>
                </div>
            </div>

            <div class="specs-edit">
                <span>Colors</span>
                <ul class="spec-list" id="colorList">
                    <?php foreach ($colors as $idx => $color): ?>
                        <li class="spec-item" data-color-id="color-<?php echo $idx; ?>">
                            <span class="spec-dot"></span>
                            <span class="spec-text"><?php echo htmlspecialchars((string) $color['color_name']); ?></span>
                            <input type="hidden" name="color_names[]" value="<?php echo htmlspecialchars((string) $color['color_name']); ?>">
                            <span class="spec-actions">
                                <button type="button" class="icon-btn edit"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button type="button" class="icon-btn delete"><i class="fa-regular fa-trash-can"></i></button>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="spec-row">
                    <span class="spec-dot"></span>
                    <input type="text" id="colorInput" placeholder="New Color...">
                    <button type="button" class="btn-spec-add" id="colorAdd">Add</button>
                </div>
            </div>

            <div class="specs-edit">
                <span>Sizes</span>
                <ul class="spec-list" id="sizeList">
                    <?php foreach ($sizes as $idx => $size): ?>
                        <li class="spec-item" data-size-id="size-<?php echo $idx; ?>">
                            <span class="spec-dot"></span>
                            <span class="spec-text"><?php echo htmlspecialchars((string) $size['size_name']); ?></span>
                            <input type="hidden" name="size_names[]" value="<?php echo htmlspecialchars((string) $size['size_name']); ?>">
                            <span class="spec-actions">
                                <button type="button" class="icon-btn edit"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button type="button" class="icon-btn delete"><i class="fa-regular fa-trash-can"></i></button>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="spec-row">
                    <span class="spec-dot"></span>
                    <input type="text" id="sizeInput" placeholder="New Size...">
                    <button type="button" class="btn-spec-add" id="sizeAdd">Add</button>
                </div>
            </div>

            <div class="form-footer">
                <div class="unsaved-note">Unsaved data will be deleted</div>
                <div class="footer-actions">
                    <button type="submit" class="btn-footer save">Save</button>
                    <button type="button" class="btn-footer discard">Discard</button>
                </div>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.adminProductEditFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/product-edit.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

