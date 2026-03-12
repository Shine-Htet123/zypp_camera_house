<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';
require_once __DIR__ . '/../database/bundles.php';

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$bundles = bundle_fetch_admin_bundles();
$products = catalog_fetch_admin_products();
$bundleDiscounts = bundle_fetch_discount_options(false);
$categories = catalog_fetch_category_options();
$brands = catalog_fetch_brand_options();
$bundlesApiPath = app_path('/admin/bundles-api.php');

$renderBundleProductCards = static function (array $products, array $selectedItems = []): string {
    ob_start();
    ?>
    <div class="bundle-products-grid">
        <?php foreach ($products as $product): ?>
            <?php
            $productId = (int) ($product['product_id'] ?? 0);
            $quantity = max(0, (int) ($selectedItems[$productId] ?? 0));
            ?>
            <div
                class="bundle-product-card<?php echo $quantity > 0 ? ' selected' : ''; ?>"
                data-product-id="<?php echo $productId; ?>"
                data-product-name="<?php echo htmlspecialchars((string) ($product['name'] ?? '')); ?>"
                data-product-category="<?php echo htmlspecialchars((string) ($product['category_name'] ?? '')); ?>"
                data-product-brand="<?php echo htmlspecialchars((string) ($product['brand_name'] ?? '')); ?>"
                data-product-availability="<?php echo (int) ($product['stock_quantity'] ?? 0) > 0 ? 'in-stock' : 'out-of-stock'; ?>"
            >
                <img src="<?php echo htmlspecialchars((string) ($product['image_url'] ?? '')); ?>" alt="<?php echo htmlspecialchars((string) ($product['name'] ?? '')); ?>">
                <div class="product-info">
                    <h4><?php echo htmlspecialchars((string) ($product['name'] ?? '')); ?></h4>
                    <p class="product-meta"><?php echo htmlspecialchars((string) ($product['brand_name'] ?? '')); ?> / <?php echo htmlspecialchars((string) ($product['category_name'] ?? '')); ?></p>
                    <div class="qty-control">
                        <span>Qty:</span>
                        <button type="button" class="qty-btn minus">-</button>
                        <span class="qty-value"><?php echo $quantity; ?></span>
                        <button type="button" class="qty-btn plus">+</button>
                    </div>
                </div>
                <input type="hidden" name="items[<?php echo $productId; ?>]" value="<?php echo $quantity; ?>" class="bundle-item-input">
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return (string) ob_get_clean();
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/bundles.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content bundles-page">
        <header class="bundles-header">
            <h1>Bundle Management</h1>
            <?php if ($bundleDiscounts === []): ?>
                <p class="bundles-hint">Create a discount with type <strong>Bundle</strong> in Discount Management before saving bundles.</p>
            <?php endif; ?>
            <div class="bundles-actions">
                <form class="bundles-search-form admin-search-form" method="get" action="<?php echo htmlspecialchars(app_path('/admin/bundles.php')); ?>">
                    <div class="admin-search-box">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Bundle Name / ID">
                        <button type="submit" class="search-icon" aria-label="Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                    <button type="submit" class="admin-search-submit">Search</button>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/bundles.php')); ?>" class="admin-show-all">Show All</a>
                </form>
                <button type="button" class="btn-new" id="openAddBundle">
                    <i class="fa-solid fa-plus"></i>
                    New
                </button>
            </div>
        </header>

        <section class="bundles-table">
            <div class="bundles-scroll table-scroll">
                <table class="admin-table bundles-table-grid">
                    <thead>
                        <tr class="bundles-head">
                            <th>ID</th>
                            <th>Name</th>
                            <th>Discount</th>
                            <th>Products</th>
                            <th>Item Qty</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bundles === []): ?>
                            <tr class="bundles-empty-row">
                                <td colspan="6">No bundles found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bundles as $bundle): ?>
                                <tr
                                    class="bundles-row"
                                    data-bundle-id="<?php echo (int) $bundle['id']; ?>"
                                    data-bundle-public-id="<?php echo htmlspecialchars((string) $bundle['public_bundle_id']); ?>"
                                    data-bundle-name="<?php echo htmlspecialchars((string) $bundle['bundle_name']); ?>"
                                    data-discount-id="<?php echo (int) ($bundle['discount_id'] ?? 0); ?>"
                                    data-bundle-items="<?php echo htmlspecialchars((string) json_encode($bundle['items_map'], JSON_UNESCAPED_SLASHES)); ?>"
                                >
                                    <td><?php echo htmlspecialchars((string) $bundle['public_bundle_id']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $bundle['bundle_name']); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($bundle['discount_display'] ?? '-')); ?></td>
                                    <td><?php echo (int) ($bundle['products'] ?? 0); ?></td>
                                    <td><?php echo (int) ($bundle['qty'] ?? 0); ?></td>
                                    <td class="created"><?php echo htmlspecialchars((string) ($bundle['created_at_display'] ?? '-')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="addBundleModal" aria-hidden="true">
        <div class="modal-card bundle-modal" role="dialog" aria-modal="true">
            <button type="button" class="modal-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2 class="modal-title">Add Bundle</h2>

            <form class="bundle-form-shell" data-bundle-form data-mode="create">
                <input type="hidden" name="action" value="save_bundle">
                <div class="bundle-form">
                    <div class="bundle-name-row">
                        <span>Name:</span>
                        <input type="text" class="bundle-input" name="bundle_name" value="">
                    </div>
                    <div class="bundle-name-row">
                        <span>Discount:</span>
                        <select class="bundle-input" name="discount_id">
                            <option value="">Select bundle discount</option>
                            <?php foreach ($bundleDiscounts as $discount): ?>
                                <option value="<?php echo (int) $discount['id']; ?>">
                                    <?php echo htmlspecialchars((string) $discount['name'] . ' (' . $discount['display_label'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php if ($bundleDiscounts === []): ?>
                    <p class="bundle-discount-note">No bundle discounts available yet.</p>
                <?php endif; ?>

                <div class="bundle-products">
                    <h3>Select Products</h3>
                    <div class="bundle-search-row">
                        <div class="search-field">
                            <input type="text" placeholder="Product Name" data-bundle-search>
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <div class="filter-wrapper">
                            <button type="button" class="btn-filter" aria-label="Filter">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <div class="filter-dropdown" aria-hidden="true">
                                <div class="filter-row">
                                    <label>Category</label>
                                    <select data-bundle-category-filter>
                                        <option value="">All</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars((string) $category['name']); ?>"><?php echo htmlspecialchars((string) $category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-row">
                                    <label>Brand</label>
                                    <select data-bundle-brand-filter>
                                        <option value="">All</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo htmlspecialchars((string) $brand['name']); ?>"><?php echo htmlspecialchars((string) $brand['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-row">
                                    <label>Availability</label>
                                    <select data-bundle-availability-filter>
                                        <option value="">All</option>
                                        <option value="in-stock">In Stock</option>
                                        <option value="out-of-stock">Out of Stock</option>
                                    </select>
                                </div>
                                <div class="filter-actions">
                                    <button type="button" class="btn-filter-apply">Apply</button>
                                    <button type="button" class="btn-filter-clear">Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php echo $renderBundleProductCards($products); ?>
                </div>

                <p class="bundle-feedback" data-bundle-feedback aria-live="polite"></p>

                <div class="modal-footer">
                    <span class="modal-note">Unsaved data will be deleted</span>
                    <div class="modal-actions">
                        <button type="submit" class="btn-footer save">Save</button>
                        <button type="button" class="btn-footer discard">Discard</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editBundleModal" aria-hidden="true">
        <div class="modal-card bundle-modal" role="dialog" aria-modal="true">
            <button type="button" class="modal-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2 class="modal-title">Edit Bundle</h2>
            <p class="modal-subtitle">ID: <span id="editBundleId">-</span></p>

            <form class="bundle-form-shell" data-bundle-form data-mode="edit">
                <input type="hidden" name="action" value="save_bundle">
                <input type="hidden" name="bundle_id" value="">
                <div class="bundle-form">
                    <div class="bundle-name-row">
                        <span>Name:</span>
                        <input type="text" class="bundle-input" name="bundle_name" value="">
                    </div>
                    <div class="bundle-name-row">
                        <span>Discount:</span>
                        <select class="bundle-input" name="discount_id">
                            <option value="">Select bundle discount</option>
                            <?php foreach ($bundleDiscounts as $discount): ?>
                                <option value="<?php echo (int) $discount['id']; ?>">
                                    <?php echo htmlspecialchars((string) $discount['name'] . ' (' . $discount['display_label'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php if ($bundleDiscounts === []): ?>
                    <p class="bundle-discount-note">No bundle discounts available yet.</p>
                <?php endif; ?>

                <div class="bundle-products">
                    <h3>Select Products</h3>
                    <div class="bundle-search-row">
                        <div class="search-field">
                            <input type="text" placeholder="Product Name" data-bundle-search>
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <div class="filter-wrapper">
                            <button type="button" class="btn-filter" aria-label="Filter">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <div class="filter-dropdown" aria-hidden="true">
                                <div class="filter-row">
                                    <label>Category</label>
                                    <select data-bundle-category-filter>
                                        <option value="">All</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars((string) $category['name']); ?>"><?php echo htmlspecialchars((string) $category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-row">
                                    <label>Brand</label>
                                    <select data-bundle-brand-filter>
                                        <option value="">All</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo htmlspecialchars((string) $brand['name']); ?>"><?php echo htmlspecialchars((string) $brand['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-row">
                                    <label>Availability</label>
                                    <select data-bundle-availability-filter>
                                        <option value="">All</option>
                                        <option value="in-stock">In Stock</option>
                                        <option value="out-of-stock">Out of Stock</option>
                                    </select>
                                </div>
                                <div class="filter-actions">
                                    <button type="button" class="btn-filter-apply">Apply</button>
                                    <button type="button" class="btn-filter-clear">Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php echo $renderBundleProductCards($products); ?>
                </div>

                <p class="bundle-feedback" data-bundle-feedback aria-live="polite"></p>

                <div class="modal-footer">
                    <span class="modal-note">Unsaved data will be deleted</span>
                    <div class="modal-actions">
                        <button type="button" class="btn-footer delete" id="deleteBundleButton">Delete</button>
                        <button type="submit" class="btn-footer save">Save</button>
                        <button type="button" class="btn-footer discard">Discard</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.adminBundlesConfig = <?php echo json_encode([
            'apiUrl' => $bundlesApiPath,
            'searchQuery' => $searchQuery,
        ], JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/bundles.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>
