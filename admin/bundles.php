<?php
$bundles = [
    [
        'id' => 'BUN202601230001',
        'name' => 'Bundle 1',
        'products' => 5,
        'qty' => 7,
        'created_at' => '12.02.2025 12:00:00',
    ],
    [
        'id' => 'BUN202601230001',
        'name' => 'Bundle 1',
        'products' => 5,
        'qty' => 7,
        'created_at' => '12.02.2025 12:00:00',
    ],
    [
        'id' => 'BUN202601230001',
        'name' => 'Bundle 1',
        'products' => 5,
        'qty' => 7,
        'created_at' => '12.02.2025 12:00:00',
    ],
    [
        'id' => 'BUN202601230001',
        'name' => 'Bundle 1',
        'products' => 5,
        'qty' => 7,
        'created_at' => '12.02.2025 12:00:00',
    ],
];

$products = [
    ['id' => 1, 'name' => 'Product Name', 'image' => '/storage/uploads/products/placeholder-camera.png'],
    ['id' => 2, 'name' => 'Product Name', 'image' => '/storage/uploads/products/placeholder-camera.png'],
    ['id' => 3, 'name' => 'Product Name', 'image' => '/storage/uploads/products/placeholder-camera.png'],
    ['id' => 4, 'name' => 'Product Name', 'image' => '/storage/uploads/products/placeholder-camera.png'],
    ['id' => 5, 'name' => 'Product Name', 'image' => '/storage/uploads/products/placeholder-camera.png'],
    ['id' => 6, 'name' => 'Product Name', 'image' => '/storage/uploads/products/placeholder-camera.png'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/bundles.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content bundles-page">
        <header class="bundles-header">
            <h1>Bundle Management</h1>
            <div class="bundles-actions">
                <div class="search-field">
                    <input type="text" placeholder="Bundle Name / ID">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <button type="button" class="btn-search">Search</button>
                <button type="button" class="btn-new" id="openAddBundle">
                    <i class="fa-solid fa-plus"></i>
                    New
                </button>
                <div class="filter-wrapper">
                    <button type="button" class="btn-filter" aria-label="Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" aria-hidden="true">
                        <div class="filter-row">
                            <label>Category</label>
                            <select>
                                <option value="">All</option>
                                <option value="Camera">Camera</option>
                                <option value="Lens">Lens</option>
                                <option value="Accessory">Accessory</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label>Brand</label>
                            <select>
                                <option value="">All</option>
                                <option value="Canon">Canon</option>
                                <option value="Sony">Sony</option>
                                <option value="DJI">DJI</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label>Availability</label>
                            <select>
                                <option value="">All</option>
                                <option value="InStock">In Stock</option>
                                <option value="OutOfStock">Out of Stock</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="button" class="btn-filter-apply">Apply</button>
                            <button type="button" class="btn-filter-clear">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="bundles-table">
            <div class="bundles-scroll table-scroll">
                <table class="admin-table bundles-table-grid">
                    <thead>
                        <tr class="bundles-head">
                            <th>ID</th>
                            <th>Name</th>
                            <th>Products</th>
                            <th>Item Qty</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bundles as $bundle): ?>
                            <tr
                                class="bundles-row"
                                data-bundle-id="<?php echo htmlspecialchars($bundle['id']); ?>"
                                data-bundle-name="<?php echo htmlspecialchars($bundle['name']); ?>"
                            >
                                <td><?php echo htmlspecialchars($bundle['id']); ?></td>
                                <td><?php echo htmlspecialchars($bundle['name']); ?></td>
                                <td><?php echo htmlspecialchars($bundle['products']); ?></td>
                                <td><?php echo htmlspecialchars($bundle['qty']); ?></td>
                                <td class="created"><?php echo htmlspecialchars($bundle['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    </div>
</div>

<div class="modal-overlay" id="addBundleModal" aria-hidden="true">
    <div class="modal-card bundle-modal" role="dialog" aria-modal="true">
        <button type="button" class="modal-close" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2 class="modal-title">Add Bundle</h2>

        <div class="bundle-form">
            <div class="bundle-name-row">
                <span>Name:</span>
                <input type="text" class="bundle-input" placeholder="">
            </div>
        </div>

        <div class="bundle-products">
            <h3>Select Products</h3>
            <div class="bundle-search-row">
                <div class="search-field">
                    <input type="text" placeholder="Product Name">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <button type="button" class="btn-search">Search</button>
                <div class="filter-wrapper">
                    <button type="button" class="btn-filter" aria-label="Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" aria-hidden="true">
                        <div class="filter-row">
                            <label>Category</label>
                            <select>
                                <option value="">All</option>
                                <option value="Camera">Camera</option>
                                <option value="Lens">Lens</option>
                                <option value="Accessory">Accessory</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label>Brand</label>
                            <select>
                                <option value="">All</option>
                                <option value="Canon">Canon</option>
                                <option value="Sony">Sony</option>
                                <option value="DJI">DJI</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label>Availability</label>
                            <select>
                                <option value="">All</option>
                                <option value="InStock">In Stock</option>
                                <option value="OutOfStock">Out of Stock</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="button" class="btn-filter-apply">Apply</button>
                            <button type="button" class="btn-filter-clear">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bundle-products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="bundle-product-card" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="product-info">
                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                            <div class="qty-control">
                                <span>Qty:</span>
                                <button type="button" class="qty-btn minus">-</button>
                                <span class="qty-value">0</span>
                                <button type="button" class="qty-btn plus">+</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="modal-footer">
            <span class="modal-note">Unsaved data will be deleted</span>
            <div class="modal-actions">
                <button type="button" class="btn-footer save">Save</button>
                <button type="button" class="btn-footer discard">Discard</button>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="editBundleModal" aria-hidden="true">
    <div class="modal-card bundle-modal" role="dialog" aria-modal="true">
        <button type="button" class="modal-close" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2 class="modal-title">Edit Bundle</h2>
        <p class="modal-subtitle">ID: <span id="editBundleId">BUN202601230001</span></p>

        <div class="bundle-form">
            <div class="bundle-name-row">
                <span>Name:</span>
                <input type="text" class="bundle-input" id="editBundleName" value="Bundle 1">
            </div>
        </div>

        <div class="bundle-products">
            <h3>Select Products</h3>
            <div class="bundle-search-row">
                <div class="search-field">
                    <input type="text" placeholder="Product Name">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <button type="button" class="btn-search">Search</button>
                <div class="filter-wrapper">
                    <button type="button" class="btn-filter" aria-label="Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" aria-hidden="true">
                        <div class="filter-row">
                            <label>Category</label>
                            <select>
                                <option value="">All</option>
                                <option value="Camera">Camera</option>
                                <option value="Lens">Lens</option>
                                <option value="Accessory">Accessory</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label>Brand</label>
                            <select>
                                <option value="">All</option>
                                <option value="Canon">Canon</option>
                                <option value="Sony">Sony</option>
                                <option value="DJI">DJI</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label>Availability</label>
                            <select>
                                <option value="">All</option>
                                <option value="InStock">In Stock</option>
                                <option value="OutOfStock">Out of Stock</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="button" class="btn-filter-apply">Apply</button>
                            <button type="button" class="btn-filter-clear">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bundle-products-grid">
                <?php foreach ($products as $index => $product): ?>
                    <div class="bundle-product-card<?php echo $index < 2 ? ' selected' : ''; ?>" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="product-info">
                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                            <div class="qty-control">
                                <span>Qty:</span>
                                <button type="button" class="qty-btn minus">-</button>
                                <span class="qty-value"><?php echo $index < 2 ? '1' : '0'; ?></span>
                                <button type="button" class="qty-btn plus">+</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="modal-footer">
            <span class="modal-note">Unsaved data will be deleted</span>
            <div class="modal-actions">
                <button type="button" class="btn-footer save">Save</button>
                <button type="button" class="btn-footer discard">Discard</button>
            </div>
        </div>
    </div>
</div>

<script src="/admin/assets/js/bundles.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
