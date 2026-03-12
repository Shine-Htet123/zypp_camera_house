<?php

require_once __DIR__ . '/app/services/products.php';
require_once __DIR__ . '/config/app.php';

$currentCustomerId = products_current_customer_id();
$availableCategories = catalog_fetch_category_options();
$availableBrands = catalog_fetch_brand_options();
$options = ['Best Sellers', 'New Arrivals', 'Limited-time Sales'];
$availability = ['In Stock', 'Out of Stock'];

$selectedCategory = trim((string) ($_GET['category'] ?? ''));
$selectedBrand = trim((string) ($_GET['brand'] ?? ''));
$selectedSubCategory = trim((string) ($_GET['sub_category'] ?? ''));
$searchQuery = trim((string) ($_GET['q'] ?? ''));

$breadcrumb = 'Shop';
if ($selectedSubCategory !== '') {
    $breadcrumb .= ' / ' . $selectedSubCategory;
} elseif ($selectedCategory !== '') {
    $breadcrumb .= ' / ' . $selectedCategory;
} elseif ($selectedBrand !== '') {
    $breadcrumb .= ' / ' . $selectedBrand;
} elseif ($searchQuery !== '') {
    $breadcrumb .= ' / Search';
}

$products = catalog_fetch_customer_products([
    'category' => $selectedCategory,
    'brand' => $selectedBrand,
    'sub_category' => $selectedSubCategory,
    'q' => $searchQuery,
    'user_id' => $currentCustomerId,
]);

$productsCssPath = app_path('/assets/css/products.css');
$productsJsPath = app_path('/assets/js/products.js');
$productDetailsPath = app_path('/product-details.php');

$maxPrice = 0;
foreach ($products as $product) {
    $maxPrice = max($maxPrice, (float) ($product['price_value'] ?? 0));
}
$maxPrice = $maxPrice > 0 ? (int) ceil($maxPrice / 100000) * 100000 : 1000000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($productsCssPath); ?>">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="shop-page">
        <div class="breadcrumb"><?php echo htmlspecialchars($breadcrumb); ?></div>

        <div class="shop-layout">
            <aside class="filter-card">
                <h2>Filter By</h2>

                <div class="filter-group">
                    <h3>Category</h3>
                    <?php foreach ($availableCategories as $category): ?>
                        <label class="filter-option">
                            <input
                                type="checkbox"
                                value="<?php echo htmlspecialchars($category['name']); ?>"
                                data-filter-group="category"
                                <?php echo $selectedCategory === $category['name'] ? 'checked' : ''; ?>
                            >
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h3>Brand</h3>
                    <?php foreach ($availableBrands as $brand): ?>
                        <label class="filter-option">
                            <input
                                type="checkbox"
                                value="<?php echo htmlspecialchars($brand['name']); ?>"
                                data-filter-group="brand"
                                <?php echo $selectedBrand === $brand['name'] ? 'checked' : ''; ?>
                            >
                            <span><?php echo htmlspecialchars($brand['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h3>Other Options</h3>
                    <?php foreach ($options as $option): ?>
                        <label class="filter-option">
                            <input type="checkbox" value="<?php echo htmlspecialchars($option); ?>" data-filter-group="option">
                            <span><?php echo htmlspecialchars($option); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h3>Price Range</h3>
                    <div class="range-row">
                        <input type="range" id="priceRange" min="0" max="<?php echo $maxPrice; ?>" value="<?php echo $maxPrice; ?>" step="100000">
                    </div>
                    <div class="range-labels">
                        <span>0 MMK</span>
                        <span id="priceMaxLabel"><?php echo htmlspecialchars(number_format($maxPrice) . ' MMK'); ?></span>
                    </div>
                </div>

                <div class="filter-group">
                    <h3>Availability</h3>
                    <?php foreach ($availability as $status): ?>
                        <label class="filter-option">
                            <input type="checkbox" value="<?php echo htmlspecialchars($status); ?>" data-filter-group="availability">
                            <span><?php echo htmlspecialchars($status); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </aside>

            <section class="product-grid">
                <?php foreach ($products as $product): ?>
                    <article
                        class="product-card"
                        data-category="<?php echo htmlspecialchars($product['category']); ?>"
                        data-brand="<?php echo htmlspecialchars($product['brand']); ?>"
                        data-tags="<?php echo htmlspecialchars(implode(',', $product['tags'])); ?>"
                        data-availability="<?php echo htmlspecialchars($product['availability']); ?>"
                        data-price="<?php echo htmlspecialchars((string) $product['price_value']); ?>"
                    >
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="brand"><?php echo htmlspecialchars($product['brand']); ?></p>
                        <div class="price-row">
                            <?php if (!empty($product['original'])): ?>
                                <span class="original"><?php echo htmlspecialchars($product['original']); ?></span>
                            <?php endif; ?>
                            <span class="price"><?php echo htmlspecialchars($product['price']); ?></span>
                        </div>
                        <?php if (!empty($product['discount'])): ?>
                            <div class="discount-pill"><?php echo htmlspecialchars($product['discount']); ?></div>
                        <?php endif; ?>
                        <a class="btn-add" href="<?php echo htmlspecialchars($productDetailsPath . '?id=' . (int) $product['product_id']); ?>">View Detail</a>
                    </article>
                <?php endforeach; ?>
                <div class="product-loading" aria-hidden="true">
                    <div class="spinner" aria-hidden="true"></div>
                    <span>Loading...</span>
                </div>
            </section>
        </div>

        <div class="pagination" data-pagination></div>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
    <script src="<?php echo htmlspecialchars($productsJsPath); ?>"></script>
</body>
</html>


