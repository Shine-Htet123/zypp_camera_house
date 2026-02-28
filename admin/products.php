<?php
$products = [
    [
        'name' => 'Product Name',
        'brand' => 'Brand Name',
        'price' => '10,000,000 MMK',
        'stock' => 23,
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Product Name',
        'brand' => 'Brand Name',
        'price' => '10,000,000 MMK',
        'stock' => 23,
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Product Name',
        'brand' => 'Brand Name',
        'price' => '10,000,000 MMK',
        'stock' => 23,
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Product Name',
        'brand' => 'Brand Name',
        'price' => '10,000,000 MMK',
        'stock' => 23,
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Product Name',
        'brand' => 'Brand Name',
        'price' => '10,000,000 MMK',
        'stock' => 23,
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Product Name',
        'brand' => 'Brand Name',
        'price' => '10,000,000 MMK',
        'stock' => 23,
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Product Name',
        'brand' => 'Brand Name',
        'price' => '10,000,000 MMK',
        'stock' => 23,
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Product Name',
        'brand' => 'Brand Name',
        'price' => '10,000,000 MMK',
        'stock' => 23,
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
    [
        'name' => 'Product Name',
        'brand' => 'Brand Name',
        'price' => '10,000,000 MMK',
        'stock' => 23,
        'image' => '/storage/uploads/products/placeholder-camera.png',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/products.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-products">
        <header class="products-header">
            <h1>Products</h1>
            <div class="products-controls">
                <div class="search-box">
                    <input type="search" id="productSearch" placeholder="Search your product...">
                    <button type="button" class="search-icon" id="productSearchBtn" aria-label="Search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
                <button type="button" class="btn btn-primary">Search</button>
                <a href="/admin/product-add.php" class="btn btn-success">+ New Product</a>
                <div class="filter-wrapper">
                    <button type="button" class="btn btn-filter" aria-label="Filter" id="filterToggle">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" id="filterDropdown">
                        <form class="filter-form" id="filterForm">
                            <div class="filter-group">
                                <div class="filter-title">Category</div>
                                <label><input type="checkbox" name="cat[]" value="camera"> Cameras</label>
                                <label><input type="checkbox" name="cat[]" value="lenses"> Lenses</label>
                                <label><input type="checkbox" name="cat[]" value="tripods"> Tripods</label>
                                <label><input type="checkbox" name="cat[]" value="lighting"> Lighting</label>
                            </div>
                            <div class="filter-group">
                                <div class="filter-title">Brand</div>
                                <label><input type="checkbox" name="brand[]" value="canon"> Canon</label>
                                <label><input type="checkbox" name="brand[]" value="sony"> Sony</label>
                                <label><input type="checkbox" name="brand[]" value="nikon"> Nikon</label>
                                <label><input type="checkbox" name="brand[]" value="dji"> DJI</label>
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

        <section class="products-grid" id="productsGrid">
            <?php foreach ($products as $index => $product): ?>
                <article class="product-card" data-product-id="<?php echo $index + 1; ?>">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="product-body">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                        <div class="product-price"><?php echo htmlspecialchars($product['price']); ?></div>
                        <div class="product-stock">
                            <span class="label">Stock:</span>
                            <span class="value"><?php echo htmlspecialchars($product['stock']); ?></span>
                        </div>
                    </div>
                    <div class="product-actions">
                        <a href="/admin/product-edit.php" class="icon-btn edit" aria-label="Edit product">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </a>
                        <button type="button" class="icon-btn delete" data-product-id="<?php echo $index + 1; ?>">
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
    </main>

    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/admin/assets/js/products.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
