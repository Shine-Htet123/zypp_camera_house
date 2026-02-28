<?php
$categories = [
    ['id' => 1, 'name' => 'Camera', 'featured' => true],
    ['id' => 2, 'name' => 'Lenses', 'featured' => false],
    ['id' => 3, 'name' => 'Accessories', 'featured' => true],
];

$subCategories = [
    ['id' => 1, 'name' => 'Camera', 'category' => 'Camera', 'featured' => true],
    ['id' => 2, 'name' => 'Lenses', 'category' => 'Lenses', 'featured' => false],
    ['id' => 3, 'name' => 'Accessories', 'category' => 'Accessories', 'featured' => true],
    ['id' => 4, 'name' => 'Audio & Video', 'category' => 'Audio & Video', 'featured' => false],
    ['id' => 5, 'name' => 'Lighting', 'category' => 'Lighting', 'featured' => true],
];

$brands = [
    ['id' => 1, 'name' => 'Canon', 'featured' => true],
    ['id' => 2, 'name' => 'DJI', 'featured' => false],
    ['id' => 3, 'name' => 'Sony', 'featured' => true],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/category-brand.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-category-brand">
        <section class="data-section">
            <header class="section-header">
                <h1>Category</h1>
                <button type="button" class="btn-new">
                    <i class="fa-solid fa-plus"></i>
                    New
                </button>
            </header>

            <div class="data-card">
                <div class="data-head cols-category">
                    <span>No.</span>
                    <span>Category</span>
                    <span>Image</span>
                    <span>Featured</span>
                    <span>Action</span>
                </div>
                <div class="data-body">
                    <?php foreach ($categories as $row): ?>
                        <div class="data-row cols-category">
                            <span><?php echo htmlspecialchars($row['id']); ?></span>
                            <span><?php echo htmlspecialchars($row['name']); ?></span>
                            <span class="image-placeholder">
                                <i class="fa-regular fa-image"></i>
                            </span>
                            <span class="status <?php echo $row['featured'] ? 'yes' : 'no'; ?>">
                                <i class="fa-solid <?php echo $row['featured'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                            </span>
                            <span class="action-buttons">
                                <button type="button" class="icon-btn edit" aria-label="Edit category">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete category">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="data-section">
            <header class="section-header">
                <h1>Sub-Category</h1>
                <button type="button" class="btn-new">
                    <i class="fa-solid fa-plus"></i>
                    New
                </button>
            </header>

            <div class="data-card">
                <div class="data-head cols-subcategory">
                    <span>No.</span>
                    <span>Sub-Category</span>
                    <span>Category</span>
                    <span>Featured</span>
                    <span>Action</span>
                </div>
                <div class="data-body">
                    <?php foreach ($subCategories as $row): ?>
                        <div class="data-row cols-subcategory">
                            <span><?php echo htmlspecialchars($row['id']); ?></span>
                            <span><?php echo htmlspecialchars($row['name']); ?></span>
                            <span><?php echo htmlspecialchars($row['category']); ?></span>
                            <span class="status <?php echo $row['featured'] ? 'yes' : 'no'; ?>">
                                <i class="fa-solid <?php echo $row['featured'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                            </span>
                            <span class="action-buttons">
                                <button type="button" class="icon-btn edit" aria-label="Edit sub-category">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete sub-category">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="data-section">
            <header class="section-header">
                <h1>Brand</h1>
                <button type="button" class="btn-new">
                    <i class="fa-solid fa-plus"></i>
                    New
                </button>
            </header>

            <div class="data-card">
                <div class="data-head cols-brand">
                    <span>No.</span>
                    <span>Brand</span>
                    <span>Image</span>
                    <span>Featured</span>
                    <span>Action</span>
                </div>
                <div class="data-body">
                    <?php foreach ($brands as $row): ?>
                        <div class="data-row cols-brand">
                            <span><?php echo htmlspecialchars($row['id']); ?></span>
                            <span><?php echo htmlspecialchars($row['name']); ?></span>
                            <span class="image-placeholder">
                                <i class="fa-regular fa-image"></i>
                            </span>
                            <span class="status <?php echo $row['featured'] ? 'yes' : 'no'; ?>">
                                <i class="fa-solid <?php echo $row['featured'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                            </span>
                            <span class="action-buttons">
                                <button type="button" class="icon-btn edit" aria-label="Edit brand">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete brand">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    </div>
</div>

<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
