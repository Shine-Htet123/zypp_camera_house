<?php
$discount = [
    'id' => 'DCT202601260001',
    'title' => 'Christmas Sale',
    'discount_type' => 'Membership',
    'value_type' => 'Percentage',
    'value' => '20 %',
    'start' => '23/12/2025',
    'end' => '23/12/2025',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/apply-discounts.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content apply-discounts-page">
        <header class="apply-header">
            <a class="back-link" href="/admin/discounts.php" aria-label="Back to discounts">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <h1>Apply Discounts</h1>
        </header>

        <section class="apply-info">
            <div class="info-row">
                <span class="label">ID:</span>
                <span class="value" data-discount-id><?php echo htmlspecialchars($discount['id']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Title:</span>
                <span class="value"><?php echo htmlspecialchars($discount['title']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Discount Type:</span>
                <span class="value"><?php echo htmlspecialchars($discount['discount_type']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Value Type:</span>
                <span class="value"><?php echo htmlspecialchars($discount['value_type']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Value:</span>
                <span class="value"><?php echo htmlspecialchars($discount['value']); ?></span>
            </div>
            <div class="info-row date-row">
                <span class="label">Start Date:</span>
                <span class="value"><?php echo htmlspecialchars($discount['start']); ?></span>
            </div>
            <div class="info-row date-row">
                <span class="label">End Date:</span>
                <span class="value"><?php echo htmlspecialchars($discount['end']); ?></span>
            </div>
        </section>

        <section class="apply-targets">
            <h2>Apply To:</h2>
            <div class="apply-layout">
                <aside class="apply-sidebar">
                    <button class="apply-tab active" data-target="apply-product">
                        <span>Product</span><span class="count">50</span>
                    </button>
                    <button class="apply-tab" data-target="apply-category">
                        <span>Category</span><span class="count">50</span>
                    </button>
                    <button class="apply-tab" data-target="apply-subcategory">
                        <span>Sub-Category</span><span class="count">50</span>
                    </button>
                    <button class="apply-tab" data-target="apply-brand">
                        <span>Brand</span><span class="count">50</span>
                    </button>
                </aside>

                <div class="apply-content">
                    <div class="apply-pane active" id="apply-product">
                        <div class="apply-search">
                            <div class="search-field">
                                <input type="text" placeholder="Product Name">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <button type="button" class="btn-search">Search</button>
                        </div>
                        <label class="select-all">
                            <input type="checkbox" data-select-all="apply-product">
                            <span>Select All</span>
                        </label>
                        <div class="apply-grid product-grid">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <label class="apply-card selected">
                                    <input type="checkbox" checked>
                                    <div class="card-image">
                                        <img src="/storage/uploads/products/placeholder-camera.png" alt="Product">
                                    </div>
                                    <div class="card-info">
                                        <p class="name">Product Name</p>
                                        <p class="brand">Brand Name</p>
                                        <p class="price">10,000,000 MMK</p>
                                        <p class="stock">Stock: <span>23</span></p>
                                    </div>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="apply-pane" id="apply-category">
                        <label class="select-all">
                            <input type="checkbox" data-select-all="apply-category">
                            <span>Select All</span>
                        </label>
                        <div class="apply-grid tile-grid">
                            <?php foreach (['Camera', 'Lenses', 'Action Camera', 'Gimbal', 'Microphone', 'Lighting'] as $name): ?>
                                <label class="apply-card selected">
                                    <input type="checkbox" checked>
                                    <div class="tile">
                                        <div class="tile-icon"></div>
                                        <span><?php echo htmlspecialchars($name); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="apply-pane" id="apply-subcategory">
                        <label class="select-all">
                            <input type="checkbox" data-select-all="apply-subcategory">
                            <span>Select All</span>
                        </label>
                        <div class="group-row open">
                            <div class="group-header">
                                <label class="group-check">
                                    <input type="checkbox" checked>
                                    <span>Camera</span>
                                </label>
                                <button type="button" class="group-toggle" aria-label="Toggle Camera">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="apply-grid tile-grid">
                                <?php foreach (['Camera', 'Camera'] as $name): ?>
                                    <label class="apply-card selected">
                                        <input type="checkbox" checked>
                                        <div class="tile">
                                            <div class="tile-icon"></div>
                                            <span><?php echo htmlspecialchars($name); ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="group-row open">
                            <div class="group-header">
                                <label class="group-check">
                                    <input type="checkbox" checked>
                                    <span>Lenses</span>
                                </label>
                                <button type="button" class="group-toggle" aria-label="Toggle Lenses">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="apply-grid tile-grid">
                                <?php foreach (['Lenses', 'Lenses'] as $name): ?>
                                    <label class="apply-card selected">
                                        <input type="checkbox" checked>
                                        <div class="tile">
                                            <div class="tile-icon"></div>
                                            <span><?php echo htmlspecialchars($name); ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="apply-pane" id="apply-brand">
                        <label class="select-all">
                            <input type="checkbox" data-select-all="apply-brand">
                            <span>Select All</span>
                        </label>
                        <div class="apply-grid tile-grid">
                            <?php foreach (['Canon', 'Sony', 'DJI', 'Panasonic', 'Nikon', 'Fujifilm'] as $name): ?>
                                <label class="apply-card selected">
                                    <input type="checkbox" checked>
                                    <div class="tile">
                                        <div class="tile-icon"></div>
                                        <span><?php echo htmlspecialchars($name); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="apply-footer">
            <span>Unsaved data will be deleted</span>
            <div class="apply-actions">
                <button type="button" class="btn-save">Save</button>
                <button type="button" class="btn-discard">Discard</button>
            </div>
        </div>
    </main>

    <script src="/admin/assets/js/apply-discounts.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
