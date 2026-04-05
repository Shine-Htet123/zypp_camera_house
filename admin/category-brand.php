<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/catalog.php';
require_once __DIR__ . '/../database/admin/catalog_management.php';

$setFlash = static function (string $message, string $type = 'success'): void {
    $_SESSION['admin_category_brand_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): ?array {
    $flash = $_SESSION['admin_category_brand_flash'] ?? null;
    unset($_SESSION['admin_category_brand_flash']);
    return is_array($flash) ? $flash : null;
};

$isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$respondJson = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['entity_action'] ?? '');
        $message = 'Saved successfully.';
        switch ($action) {
            case 'save_category':
                admin_category_save($_POST, $_FILES);
                $message = 'Category saved successfully.';
                break;
            case 'delete_category':
                admin_category_delete((int) ($_POST['entity_id'] ?? 0));
                $message = 'Category deleted successfully.';
                break;
            case 'save_brand':
                admin_brand_save($_POST, $_FILES);
                $message = 'Brand saved successfully.';
                break;
            case 'delete_brand':
                admin_brand_delete((int) ($_POST['entity_id'] ?? 0));
                $message = 'Brand deleted successfully.';
                break;
            case 'save_subcategory':
                admin_sub_category_save($_POST);
                $message = 'Sub-category saved successfully.';
                break;
            case 'delete_subcategory':
                admin_sub_category_delete((int) ($_POST['entity_id'] ?? 0));
                $message = 'Sub-category deleted successfully.';
                break;
            default:
                throw new RuntimeException('Unknown action.');
        }

        if ($isAjaxRequest) {
            $categories = array_map(static function (array $row): array {
                $row['image_url'] = !empty($row['category_img'])
                    ? catalog_public_file_url($row['category_img'], '/storage/uploads/contents/logo.png')
                    : '';
                return $row;
            }, catalog_fetch_category_options());
            $subCategories = catalog_fetch_sub_category_options();
            $brands = array_map(static function (array $row): array {
                $row['image_url'] = !empty($row['logo_file'])
                    ? catalog_public_file_url($row['logo_file'], '/storage/uploads/contents/logo.png')
                    : '';
                return $row;
            }, catalog_fetch_brand_options());

            $respondJson([
                'success' => true,
                'message' => $message,
                'categories' => $categories,
                'sub_categories' => $subCategories,
                'brands' => $brands,
            ]);
        }

        $setFlash($message);
    } catch (Throwable $exception) {
        if ($isAjaxRequest) {
            $respondJson([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $setFlash($exception->getMessage(), 'error');
    }

    header('Location: ' . app_path('/admin/category-brand.php'));
    exit;
}

$flash = $consumeFlash();
$categories = catalog_fetch_category_options();
$subCategories = catalog_fetch_sub_category_options();
$brands = catalog_fetch_brand_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/category-brand.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-category-brand">
        <section class="data-section">
            <header class="section-header">
                <h1>Category</h1>
                <button type="button" class="btn-new" data-modal="category">
                    <i class="fa-solid fa-plus"></i>
                    New
                </button>
            </header>

            <div class="data-card table-scroll">
                <table class="admin-table data-table cols-category">
                    <thead>
                        <tr class="data-head cols-category">
                            <th>No.</th>
                            <th>Category</th>
                            <th>Image</th>
                            <th>Featured</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $row): ?>
                            <tr
                                class="data-row cols-category"
                                data-type="Category"
                                data-id="<?php echo (int) $row['category_id']; ?>"
                                data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                data-featured="<?php echo !empty($row['featured']) ? 'yes' : 'no'; ?>"
                            >
                                <td><?php echo (int) $row['category_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="image-cell">
                                    <?php if (!empty($row['category_img'])): ?>
                                        <img class="table-image" src="<?php echo htmlspecialchars(catalog_public_file_url($row['category_img'], '/storage/uploads/contents/logo.png')); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                    <?php else: ?>
                                        <div class="image-placeholder"><i class="fa-regular fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td class="status <?php echo !empty($row['featured']) ? 'yes' : 'no'; ?>">
                                    <i class="fa-solid <?php echo !empty($row['featured']) ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                                </td>
                                <td class="action-buttons">
                                    <button type="button" class="icon-btn edit" aria-label="Edit category" data-edit="category">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="icon-btn delete" aria-label="Delete category" data-delete-type="category">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="data-section">
            <header class="section-header">
                <h1>Sub-Category</h1>
                <button type="button" class="btn-new" data-modal="subcategory">
                    <i class="fa-solid fa-plus"></i>
                    New
                </button>
            </header>

            <div class="data-card table-scroll">
                <table class="admin-table data-table cols-subcategory">
                    <thead>
                        <tr class="data-head cols-subcategory">
                            <th>No.</th>
                            <th>Sub-Category</th>
                            <th>Category</th>
                            <th>Featured</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subCategories as $row): ?>
                            <tr
                                class="data-row cols-subcategory"
                                data-type="Sub-Category"
                                data-id="<?php echo (int) $row['sub_category_id']; ?>"
                                data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                data-parent-id="<?php echo (int) $row['category_id']; ?>"
                                data-parent-name="<?php echo htmlspecialchars($row['category_name']); ?>"
                                data-featured="<?php echo !empty($row['featured']) ? 'yes' : 'no'; ?>"
                            >
                                <td><?php echo (int) $row['sub_category_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td class="status <?php echo !empty($row['featured']) ? 'yes' : 'no'; ?>">
                                    <i class="fa-solid <?php echo !empty($row['featured']) ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                                </td>
                                <td class="action-buttons">
                                    <button type="button" class="icon-btn edit" aria-label="Edit sub-category" data-edit="subcategory">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="icon-btn delete" aria-label="Delete sub-category" data-delete-type="subcategory">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="data-section">
            <header class="section-header">
                <h1>Brand</h1>
                <button type="button" class="btn-new" data-modal="brand">
                    <i class="fa-solid fa-plus"></i>
                    New
                </button>
            </header>

            <div class="data-card table-scroll">
                <table class="admin-table data-table cols-brand">
                    <thead>
                        <tr class="data-head cols-brand">
                            <th>No.</th>
                            <th>Brand</th>
                            <th>Image</th>
                            <th>Featured</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brands as $row): ?>
                            <tr
                                class="data-row cols-brand"
                                data-type="Brand"
                                data-id="<?php echo (int) $row['brand_id']; ?>"
                                data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                data-featured="<?php echo !empty($row['featured']) ? 'yes' : 'no'; ?>"
                            >
                                <td><?php echo (int) $row['brand_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="image-cell">
                                    <?php if (!empty($row['logo_file'])): ?>
                                        <img class="table-image" src="<?php echo htmlspecialchars(catalog_public_file_url($row['logo_file'], '/storage/uploads/contents/logo.png')); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                    <?php else: ?>
                                        <div class="image-placeholder"><i class="fa-regular fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td class="status <?php echo !empty($row['featured']) ? 'yes' : 'no'; ?>">
                                    <i class="fa-solid <?php echo !empty($row['featured']) ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                                </td>
                                <td class="action-buttons">
                                    <button type="button" class="icon-btn edit" aria-label="Edit brand" data-edit="brand">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="icon-btn delete" aria-label="Delete brand" data-delete-type="brand">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="modal-overlay" id="categoryModal" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="categoryModalTitle">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 id="categoryModalTitle">Add Category</h2>
                <form class="modal-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="entity_action" value="save_category">
                    <input type="hidden" name="entity_id" value="0">
                    <label class="modal-field">
                        <span>Category:</span>
                        <input type="text" name="categoryName" placeholder="">
                    </label>
                    <label class="modal-field select">
                        <span>Is Featured:</span>
                        <select name="categoryFeatured">
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </label>
                    <div class="modal-upload">
                        <div class="modal-image" id="categoryPreview">
                            <i class="fa-regular fa-image"></i>
                        </div>
                        <input type="file" id="categoryImageInput" name="categoryImage" accept="image/*" hidden>
                        <button type="button" class="btn-upload" data-upload="categoryImageInput">
                            <i class="fa-solid fa-upload"></i>
                            Upload
                        </button>
                    </div>
                    <div class="modal-footer">
                        <span class="modal-note">Unsaved changes will be deleted</span>
                        <div class="modal-actions">
                            <button type="submit" class="btn-footer save">Save</button>
                            <button type="button" class="btn-footer discard">Discard</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="brandModal" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="brandModalTitle">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 id="brandModalTitle">Add Brand</h2>
                <form class="modal-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="entity_action" value="save_brand">
                    <input type="hidden" name="entity_id" value="0">
                    <label class="modal-field">
                        <span>Brand:</span>
                        <input type="text" name="brandName">
                    </label>
                    <label class="modal-field select">
                        <span>Is Featured:</span>
                        <select name="brandFeatured">
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </label>
                    <div class="modal-upload">
                        <div class="modal-image" id="brandPreview">
                            <i class="fa-regular fa-image"></i>
                        </div>
                        <input type="file" id="brandImageInput" name="brandImage" accept="image/*" hidden>
                        <button type="button" class="btn-upload" data-upload="brandImageInput">
                            <i class="fa-solid fa-upload"></i>
                            Upload
                        </button>
                    </div>
                    <div class="modal-footer">
                        <span class="modal-note">Unsaved changes will be deleted</span>
                        <div class="modal-actions">
                            <button type="submit" class="btn-footer save">Save</button>
                            <button type="button" class="btn-footer discard">Discard</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="subcategoryModal" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="subcategoryModalTitle">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 id="subcategoryModalTitle">Add Sub-Category</h2>
                <form class="modal-form" method="post">
                    <input type="hidden" name="entity_action" value="save_subcategory">
                    <input type="hidden" name="entity_id" value="0">
                    <label class="modal-field select">
                        <span>Category:</span>
                        <select name="subCategoryParentId">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="modal-field">
                        <span>Sub-Category:</span>
                        <input type="text" name="subCategoryName">
                    </label>
                    <label class="modal-field select">
                        <span>Is Featured:</span>
                        <select name="subCategoryFeatured">
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </label>
                    <div class="modal-footer">
                        <span class="modal-note">Unsaved changes will be deleted</span>
                        <div class="modal-actions">
                            <button type="submit" class="btn-footer save">Save</button>
                            <button type="button" class="btn-footer discard">Discard</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <form id="categoryBrandDeleteForm" method="post" hidden>
        <input type="hidden" name="entity_action" value="">
        <input type="hidden" name="entity_id" value="">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.categoryBrandFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/category-brand.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

