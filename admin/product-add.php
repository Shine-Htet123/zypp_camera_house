<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/catalog.php';
require_once __DIR__ . '/../database/admin/catalog_management.php';

$isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$respondJson = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
};

$old = [
    'name' => '',
    'brand' => '',
    'price' => '',
    'description' => '',
    'stock' => '',
    'category' => '',
    'subcategory' => '',
    'featured' => 'no',
    'visibility' => 'visible',
    'color_names' => [],
    'size_names' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = array_merge($old, $_POST);
    try {
        $productId = admin_product_create($_POST, $_FILES);
        if ($isAjaxRequest) {
            $respondJson([
                'success' => true,
                'message' => 'Product added successfully.',
                'product_id' => $productId,
                'edit_url' => app_path('/admin/product-edit.php?id=' . $productId),
            ]);
        }

        header('Location: ' . app_path('/admin/products.php'));
        exit;
    } catch (Throwable $exception) {
        if ($isAjaxRequest) {
            $respondJson([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $_SESSION['admin_product_add_flash'] = [
            'message' => $exception->getMessage(),
            'type' => 'error',
        ];
    }
}

$flash = $_SESSION['admin_product_add_flash'] ?? null;
unset($_SESSION['admin_product_add_flash']);
$brands = catalog_fetch_brand_options();
$categories = catalog_fetch_category_options();
$subCategories = catalog_fetch_sub_category_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/product-add.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-product-add">
        <header class="page-header">
            <a href="<?php echo htmlspecialchars(app_path('/admin/products.php')); ?>" class="page-back" aria-label="Back to products">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <h1>Add New Product</h1>
        </header>

        <form id="productForm" class="product-form" action="<?php echo htmlspecialchars(app_path('/admin/product-add.php')); ?>" method="post" enctype="multipart/form-data" data-skip-loader>
            <input type="hidden" name="primary_image_index" id="primaryImageIndex" value="0">
            <div class="form-grid">
                <div class="form-left">
                    <label class="field">
                        <span>Name:</span>
                        <input type="text" name="name" placeholder="Product Name" value="<?php echo htmlspecialchars((string) $old['name']); ?>" required>
                    </label>

                    <label class="field select">
                        <span>Brand:</span>
                        <select name="brand" required>
                            <option value="">Select brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo (int) $brand['brand_id']; ?>" <?php echo ((string) $old['brand'] === (string) $brand['brand_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="field price">
                        <span>Price:</span>
                        <div class="price-input">
                            <input type="number" name="price" placeholder="000000000" min="0" step="1" value="<?php echo htmlspecialchars((string) $old['price']); ?>" required>
                            <span class="suffix">MMK</span>
                        </div>
                    </label>

                    <label class="field textarea block">
                        <span>Description:</span>
                        <textarea name="description" rows="7" placeholder="This is the description of the product"><?php echo htmlspecialchars((string) $old['description']); ?></textarea>
                    </label>
                </div>

                <div class="form-right">
                    <label class="field">
                        <span>Stock:</span>
                        <input type="number" name="stock" placeholder="0000" min="0" step="1" value="<?php echo htmlspecialchars((string) $old['stock']); ?>" required>
                    </label>

                    <label class="field select">
                        <span>Category:</span>
                        <select name="category" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['category_id']; ?>" <?php echo ((string) $old['category'] === (string) $category['category_id']) ? 'selected' : ''; ?>>
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
                                <option value="<?php echo (int) $subCategory['sub_category_id']; ?>" data-category-id="<?php echo (int) $subCategory['category_id']; ?>" <?php echo ((string) $old['subcategory'] === (string) $subCategory['sub_category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subCategory['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="field inline block">
                        <div class="inline-field">
                            <span>Is Featured:</span>
                            <select name="featured">
                                <option value="no" <?php echo ($old['featured'] ?? 'no') === 'no' ? 'selected' : ''; ?>>No</option>
                                <option value="yes" <?php echo ($old['featured'] ?? 'no') === 'yes' ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                        <div class="inline-field">
                            <span>Visibility:</span>
                            <select name="visibility">
                                <option value="visible" <?php echo ($old['visibility'] ?? 'visible') === 'visible' ? 'selected' : ''; ?>>Visible</option>
                                <option value="hidden" <?php echo ($old['visibility'] ?? 'visible') === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-bottom">
                <div class="field images block">
                    <span>Images</span>
                    <input type="file" id="addImagesInput" name="images[]" multiple accept="image/*" hidden>
                    <div class="image-previews image-grid" id="imagePreviews">
                        <div class="image-tile upload-tile" id="addUploadTile">
                            <i class="fa-solid fa-upload"></i>
                        </div>
                    </div>
                </div>

                <div class="field specs block">
                    <span>Specifications</span>
                    <ul class="spec-list" id="specList">
                        <?php foreach ((array) ($_POST['spec_names'] ?? []) as $specName): ?>
                            <?php if (trim((string) $specName) === '') { continue; } ?>
                            <li class="spec-item">
                                <span class="spec-dot"></span>
                                <span class="spec-text"><?php echo htmlspecialchars((string) $specName); ?></span>
                                <input type="hidden" name="spec_names[]" value="<?php echo htmlspecialchars((string) $specName); ?>">
                                <span class="spec-actions">
                                    <button type="button" class="icon-btn edit" aria-label="Edit spec"><i class="fa-regular fa-pen-to-square"></i></button>
                                    <button type="button" class="icon-btn delete" aria-label="Delete spec"><i class="fa-regular fa-trash-can"></i></button>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="spec-row">
                        <div class="spec-dot" aria-hidden="true"></div>
                        <input type="text" id="specInput" placeholder="New Specification...">
                        <button type="button" class="btn-spec-add" id="specAdd">Add</button>
                    </div>
                </div>

                <div class="field specs block">
                    <span>Colors</span>
                    <ul class="spec-list" id="colorList">
                        <?php foreach ((array) ($old['color_names'] ?? []) as $colorName): ?>
                            <?php if (trim((string) $colorName) === '') { continue; } ?>
                            <li class="spec-item">
                                <span class="spec-dot"></span>
                                <span class="spec-text"><?php echo htmlspecialchars((string) $colorName); ?></span>
                                <input type="hidden" name="color_names[]" value="<?php echo htmlspecialchars((string) $colorName); ?>">
                                <span class="spec-actions">
                                    <button type="button" class="icon-btn edit" aria-label="Edit color"><i class="fa-regular fa-pen-to-square"></i></button>
                                    <button type="button" class="icon-btn delete" aria-label="Delete color"><i class="fa-regular fa-trash-can"></i></button>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="spec-row">
                        <div class="spec-dot" aria-hidden="true"></div>
                        <input type="text" id="colorInput" placeholder="New Color...">
                        <button type="button" class="btn-spec-add" id="colorAdd">Add</button>
                    </div>
                </div>

                <div class="field specs block">
                    <span>Sizes</span>
                    <ul class="spec-list" id="sizeList">
                        <?php foreach ((array) ($old['size_names'] ?? []) as $sizeName): ?>
                            <?php if (trim((string) $sizeName) === '') { continue; } ?>
                            <li class="spec-item">
                                <span class="spec-dot"></span>
                                <span class="spec-text"><?php echo htmlspecialchars((string) $sizeName); ?></span>
                                <input type="hidden" name="size_names[]" value="<?php echo htmlspecialchars((string) $sizeName); ?>">
                                <span class="spec-actions">
                                    <button type="button" class="icon-btn edit" aria-label="Edit size"><i class="fa-regular fa-pen-to-square"></i></button>
                                    <button type="button" class="icon-btn delete" aria-label="Delete size"><i class="fa-regular fa-trash-can"></i></button>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="spec-row">
                        <div class="spec-dot" aria-hidden="true"></div>
                        <input type="text" id="sizeInput" placeholder="New Size...">
                        <button type="button" class="btn-spec-add" id="sizeAdd">Add</button>
                    </div>
                </div>
            </div>

        </form>
    </main>

    <div class="form-footer">
        <div class="unsaved-note">Unsaved data will be deleted</div>
        <div class="footer-actions">
            <button type="submit" form="productForm" class="btn-footer save">Add</button>
            <button type="button" class="btn-footer discard">Discard</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.adminProductAddFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/product-add.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

