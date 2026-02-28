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
                <button type="button" class="btn-new" data-modal="category">
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
                        <div class="data-row cols-category" data-type="Category" data-name="<?php echo htmlspecialchars($row['name']); ?>">
                            <span><?php echo htmlspecialchars($row['id']); ?></span>
                            <span><?php echo htmlspecialchars($row['name']); ?></span>
                            <span class="image-placeholder">
                                <i class="fa-regular fa-image"></i>
                            </span>
                            <span class="status <?php echo $row['featured'] ? 'yes' : 'no'; ?>">
                                <i class="fa-solid <?php echo $row['featured'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                            </span>
                            <span class="action-buttons">
                                <button type="button" class="icon-btn edit" aria-label="Edit category" data-edit="category">
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
                <button type="button" class="btn-new" data-modal="subcategory">
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
                        <div class="data-row cols-subcategory" data-type="Sub-Category" data-name="<?php echo htmlspecialchars($row['name']); ?>">
                            <span><?php echo htmlspecialchars($row['id']); ?></span>
                            <span><?php echo htmlspecialchars($row['name']); ?></span>
                            <span><?php echo htmlspecialchars($row['category']); ?></span>
                            <span class="status <?php echo $row['featured'] ? 'yes' : 'no'; ?>">
                                <i class="fa-solid <?php echo $row['featured'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                            </span>
                            <span class="action-buttons">
                                <button type="button" class="icon-btn edit" aria-label="Edit sub-category" data-edit="subcategory">
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
                <button type="button" class="btn-new" data-modal="brand">
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
                        <div class="data-row cols-brand" data-type="Brand" data-name="<?php echo htmlspecialchars($row['name']); ?>">
                            <span><?php echo htmlspecialchars($row['id']); ?></span>
                            <span><?php echo htmlspecialchars($row['name']); ?></span>
                            <span class="image-placeholder">
                                <i class="fa-regular fa-image"></i>
                            </span>
                            <span class="status <?php echo $row['featured'] ? 'yes' : 'no'; ?>">
                                <i class="fa-solid <?php echo $row['featured'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                            </span>
                            <span class="action-buttons">
                                <button type="button" class="icon-btn edit" aria-label="Edit brand" data-edit="brand">
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
        <div class="modal-overlay" id="categoryModal" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="categoryModalTitle">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 id="categoryModalTitle">Add Category</h2>
                <form class="modal-form">
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
                        <input type="file" id="categoryImageInput" accept="image/*" hidden>
                        <button type="button" class="btn-upload" data-upload="categoryImageInput">
                            <i class="fa-solid fa-upload"></i>
                            Upload
                        </button>
                    </div>
                    <div class="modal-footer">
                        <span class="modal-note">Unsaved data will be deleted</span>
                        <div class="modal-actions">
                            <button type="button" class="btn-footer save">Save</button>
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
                <form class="modal-form">
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
                        <input type="file" id="brandImageInput" accept="image/*" hidden>
                        <button type="button" class="btn-upload" data-upload="brandImageInput">
                            <i class="fa-solid fa-upload"></i>
                            Upload
                        </button>
                    </div>
                    <div class="modal-footer">
                        <span class="modal-note">Unsaved data will be deleted</span>
                        <div class="modal-actions">
                            <button type="button" class="btn-footer save">Save</button>
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
                <form class="modal-form">
                    <label class="modal-field">
                        <span>Category:</span>
                        <input type="text" name="subCategoryParent">
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
                        <span class="modal-note">Unsaved data will be deleted</span>
                        <div class="modal-actions">
                            <button type="button" class="btn-footer save">Save</button>
                            <button type="button" class="btn-footer discard">Discard</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/admin/assets/js/category-brand.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
