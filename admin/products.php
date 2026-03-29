<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/catalog.php';
require_once __DIR__ . '/../database/admin/catalog_management.php';

$setFlash = static function (string $message, string $type = 'success'): void {
    $_SESSION['admin_products_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): ?array {
    $flash = $_SESSION['admin_products_flash'] ?? null;
    unset($_SESSION['admin_products_flash']);
    return is_array($flash) ? $flash : null;
};

$isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$respondJson = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['product_action'] ?? '') === 'delete') {
    try {
        $productId = (int) ($_POST['product_id'] ?? 0);
        admin_product_delete($productId);
        if ($isAjaxRequest) {
            $respondJson([
                'success' => true,
                'message' => 'Product deleted successfully.',
                'product_id' => $productId,
            ]);
        }

        $setFlash('Product deleted successfully.');
    } catch (Throwable $exception) {
        if ($isAjaxRequest) {
            $respondJson([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $setFlash($exception->getMessage(), 'error');
    }

    header('Location: ' . app_path('/admin/products.php'));
    exit;
}

$flash = $consumeFlash();
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$products = catalog_fetch_admin_products();
$categories = catalog_fetch_category_options();
$brands = catalog_fetch_brand_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/products.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-products">
        <header class="products-header">
            <h1>Products</h1>
            <div class="products-controls">
                <form class="products-search-form admin-search-form" method="get" action="<?php echo htmlspecialchars(app_path('/admin/products.php')); ?>">
                    <div class="search-box admin-search-box">
                        <input type="search" id="productSearch" name="q" placeholder="Search your product..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="submit" class="search-icon" id="productSearchBtn" aria-label="Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                    <button type="submit" class="btn btn-primary search-trigger admin-search-submit" id="productSearchTrigger">Search</button>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/products.php')); ?>" class="btn admin-show-all">Show All</a>
                </form>
                <a href="<?php echo htmlspecialchars(app_path('/admin/product-add.php')); ?>" class="btn btn-success btn-new-product">+ New Product</a>
                <div class="filter-wrapper">
                    <button type="button" class="btn btn-filter" aria-label="Filter" id="filterToggle">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" id="filterDropdown">
                        <form class="filter-form" id="filterForm">
                            <div class="filter-group">
                                <div class="filter-title">Category</div>
                                <?php foreach ($categories as $category): ?>
                                    <label><input type="checkbox" name="cat[]" value="<?php echo htmlspecialchars($category['name']); ?>"> <?php echo htmlspecialchars($category['name']); ?></label>
                                <?php endforeach; ?>
                            </div>
                            <div class="filter-group">
                                <div class="filter-title">Brand</div>
                                <?php foreach ($brands as $brand): ?>
                                    <label><input type="checkbox" name="brand[]" value="<?php echo htmlspecialchars($brand['name']); ?>"> <?php echo htmlspecialchars($brand['name']); ?></label>
                                <?php endforeach; ?>
                            </div>
                            <div class="filter-group">
                                <div class="filter-title">Stock</div>
                                <label><input type="checkbox" name="stock[]" value="in"> In Stock</label>
                                <label><input type="checkbox" name="stock[]" value="low"> Low Stock</label>
                                <label><input type="checkbox" name="stock[]" value="out"> Out of Stock</label>
                            </div>
                            <div class="filter-actions">
                                <button type="button" class="filter-clear" id="filterClear">Clear</button>
                                <button type="submit" class="filter-apply">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <?php if (!empty($products)): ?>
            <section class="products-grid" id="productsGrid">
                <?php foreach ($products as $product): ?>
                    <article
                        class="product-card"
                        data-product-id="<?php echo (int) $product['product_id']; ?>"
                        data-category="<?php echo htmlspecialchars($product['category_name']); ?>"
                        data-brand="<?php echo htmlspecialchars($product['brand_name']); ?>"
                        data-stock="<?php echo (int) $product['stock_quantity']; ?>"
                    >
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="product-body">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-brand"><?php echo htmlspecialchars($product['brand_name']); ?></div>
                            <div class="product-price"><?php echo htmlspecialchars(number_format((float) $product['price']) . ' MMK'); ?></div>
                            <div class="product-stock">
                                <span class="label">Stock:</span>
                                <span class="value"><?php echo (int) $product['stock_quantity']; ?></span>
                            </div>
                        </div>
                        <div class="product-actions">
                            <a href="<?php echo htmlspecialchars(app_path('/admin/product-edit.php?id=' . (int) $product['product_id'])); ?>" class="icon-btn edit" aria-label="Edit product">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <button type="button" class="icon-btn delete" data-product-id="<?php echo (int) $product['product_id']; ?>">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <nav class="products-pagination" aria-label="Products pagination">
                <button class="pager-btn prev" aria-label="Previous page">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button class="page-number active">1</button>
                <button class="page-number">2</button>
                <button class="page-number">3</button>
                <button class="page-number">4</button>
                <button class="page-number">5</button>
                <button class="pager-btn next" aria-label="Next page">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </nav>
        <?php else: ?>
            <div class="products-empty-state">No products found.</div>
        <?php endif; ?>
    </main>

    <form id="productDeleteForm" method="post" hidden>
        <input type="hidden" name="product_action" value="delete">
        <input type="hidden" name="product_id" value="">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.adminProductsFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/products.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>


