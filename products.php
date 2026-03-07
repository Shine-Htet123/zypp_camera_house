<?php
$categories = ['Cameras', 'Lenses', 'Accessories'];
$brands = ['Canon', 'Sony', 'Nikon'];
$options = ['Best Sellers', 'New Arrivals', 'Limited-time Sales'];
$availability = ['In Stock', 'Out of Stock'];

$breadcrumb = 'Shop';
$breadcrumbSegment = '';
if (!empty($_GET['category'])) {
    $breadcrumbSegment = ucwords(str_replace(['-', '_'], ' ', (string) $_GET['category']));
} elseif (!empty($_GET['brand'])) {
    $breadcrumbSegment = ucwords(str_replace(['-', '_'], ' ', (string) $_GET['brand']));
}
if ($breadcrumbSegment) {
    $breadcrumb = $breadcrumb . ' / ' . $breadcrumbSegment;
}

$products = [
    [
        'name' => 'Canon EOS R6',
        'brand' => 'Canon',
        'category' => 'Cameras',
        'tags' => ['Best Sellers'],
        'availability' => 'In Stock',
        'price_value' => 9000000,
        'price' => '9,000,000 MMK',
        'original' => '10,000,000 MMK',
        'discount' => 'Save 1,000,000 MMK',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Sony A7 IV',
        'brand' => 'Sony',
        'category' => 'Cameras',
        'tags' => ['New Arrivals'],
        'availability' => 'In Stock',
        'price_value' => 10000000,
        'price' => '10,000,000 MMK',
        'original' => '11,500,000 MMK',
        'discount' => 'Save 1,500,000 MMK',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Nikon Z6 II',
        'brand' => 'Nikon',
        'category' => 'Cameras',
        'tags' => ['Limited-time Sales'],
        'availability' => 'In Stock',
        'price_value' => 8200000,
        'price' => '8,200,000 MMK',
        'original' => '10,000,000 MMK',
        'discount' => 'Save 1,800,000 MMK',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Canon RF 24-70',
        'brand' => 'Canon',
        'category' => 'Lenses',
        'tags' => ['Best Sellers'],
        'availability' => 'In Stock',
        'price_value' => 4600000,
        'price' => '4,600,000 MMK',
        'original' => '5,000,000 MMK',
        'discount' => 'Save 400,000 MMK',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Sony 50mm F1.8',
        'brand' => 'Sony',
        'category' => 'Lenses',
        'tags' => ['New Arrivals'],
        'availability' => 'Out of Stock',
        'price_value' => 1200000,
        'price' => '1,200,000 MMK',
        'original' => '',
        'discount' => '',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Nikon 85mm F1.4',
        'brand' => 'Nikon',
        'category' => 'Lenses',
        'tags' => ['Limited-time Sales'],
        'availability' => 'In Stock',
        'price_value' => 3800000,
        'price' => '3,800,000 MMK',
        'original' => '4,800,000 MMK',
        'discount' => 'Save 1,000,000 MMK',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Tripod Pro',
        'brand' => 'Canon',
        'category' => 'Accessories',
        'tags' => ['Best Sellers'],
        'availability' => 'In Stock',
        'price_value' => 600000,
        'price' => '600,000 MMK',
        'original' => '',
        'discount' => '',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Camera Bag',
        'brand' => 'Sony',
        'category' => 'Accessories',
        'tags' => ['New Arrivals'],
        'availability' => 'In Stock',
        'price_value' => 320000,
        'price' => '320,000 MMK',
        'original' => '',
        'discount' => '',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Lighting Kit',
        'brand' => 'Nikon',
        'category' => 'Accessories',
        'tags' => ['Limited-time Sales'],
        'availability' => 'Out of Stock',
        'price_value' => 2100000,
        'price' => '2,100,000 MMK',
        'original' => '2,600,000 MMK',
        'discount' => 'Save 500,000 MMK',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Canon EOS R5',
        'brand' => 'Canon',
        'category' => 'Cameras',
        'tags' => ['Best Sellers'],
        'availability' => 'In Stock',
        'price_value' => 11500000,
        'price' => '11,500,000 MMK',
        'original' => '12,500,000 MMK',
        'discount' => 'Save 1,000,000 MMK',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Sony A6400',
        'brand' => 'Sony',
        'category' => 'Cameras',
        'tags' => ['New Arrivals'],
        'availability' => 'In Stock',
        'price_value' => 5200000,
        'price' => '5,200,000 MMK',
        'original' => '',
        'discount' => '',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Nikon D7500',
        'brand' => 'Nikon',
        'category' => 'Cameras',
        'tags' => ['Limited-time Sales'],
        'availability' => 'Out of Stock',
        'price_value' => 6400000,
        'price' => '6,400,000 MMK',
        'original' => '',
        'discount' => '',
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/assets/css/products.css">
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
                    <?php foreach ($categories as $category): ?>
                        <label class="filter-option">
                            <input type="checkbox" value="<?php echo htmlspecialchars($category); ?>" data-filter-group="category">
                            <span><?php echo htmlspecialchars($category); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h3>Brand</h3>
                    <?php foreach ($brands as $brand): ?>
                        <label class="filter-option">
                            <input type="checkbox" value="<?php echo htmlspecialchars($brand); ?>" data-filter-group="brand">
                            <span><?php echo htmlspecialchars($brand); ?></span>
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
                        <input type="range" id="priceRange" min="0" max="12000000" value="12000000" step="100000">
                    </div>
                    <div class="range-labels">
                        <span>0 MMK</span>
                        <span id="priceMaxLabel">12,000,000 MMK</span>
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
                        <a class="btn-add" href="/product-details.php">View Detail</a>
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
    <script src="/assets/js/products.js"></script>
</body>
</html>
