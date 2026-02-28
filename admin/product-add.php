<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/product-add.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-product-add">
        <header class="page-header">
            <h1>Add New Product</h1>
        </header>

        <form class="product-form" action="#" method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-left">
                    <label class="field">
                        <span>Name:</span>
                        <input type="text" name="name" placeholder="Product Name" required>
                    </label>

                    <label class="field select">
                        <span>Brand:</span>
                        <select name="brand" required>
                            <option value="">Select brand</option>
                            <option>Canon</option>
                            <option>Sony</option>
                            <option>Nikon</option>
                            <option>DJI</option>
                        </select>
                    </label>

                    <label class="field price">
                        <span>Price:</span>
                        <div class="price-input">
                            <input type="number" name="price" placeholder="000000000" min="0" step="1" required>
                            <span class="suffix">MMK</span>
                        </div>
                    </label>

                    <label class="field textarea block">
                        <span>Description:</span>
                        <textarea name="description" rows="7" placeholder="This is the description of the product"></textarea>
                    </label>
                </div>

                <div class="form-right">
                    <label class="field">
                        <span>Stock:</span>
                        <input type="number" name="stock" placeholder="0000" min="0" step="1" required>
                    </label>

                    <label class="field select">
                        <span>Category:</span>
                        <select name="category" required>
                            <option value="">Select category</option>
                            <option>Cameras</option>
                            <option>Lenses</option>
                            <option>Tripods</option>
                            <option>Lighting</option>
                        </select>
                    </label>

                    <label class="field select">
                        <span>Sub-Category:</span>
                        <select name="subcategory">
                            <option value="">Select sub-category</option>
                            <option>Mirrorless</option>
                            <option>DSLR</option>
                            <option>Accessories</option>
                        </select>
                    </label>

                    <div class="field inline block">
                        <div class="inline-field">
                            <span>Is Featured:</span>
                            <select name="featured">
                                <option value="no">No</option>
                                <option value="yes">Yes</option>
                            </select>
                        </div>
                        <div class="inline-field">
                            <span>Visibility:</span>
                            <select name="visibility">
                                <option value="visible">Visible</option>
                                <option value="hidden">Hidden</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-bottom">
                <div class="field images block">
                    <span>Images</span>
                    <input type="file" id="addImagesInput" name="images[]" multiple accept="image/*" hidden>
                    <div class="image-previews image-grid" id="imagePreviews">
                        <div class="image-tile upload-tile" id="addUploadTile">
                            <i class="fa-solid fa-upload"></i>
                        </div>
                    </div>
                </div>

                    <div class="field specs block">
                        <span>Specifications</span>
                        <ul class="spec-list" id="specList"></ul>
                        <div class="spec-row">
                            <div class="spec-dot" aria-hidden="true"></div>
                            <input type="text" id="specInput" placeholder="New Specification...">
                            <button type="button" class="btn-spec-add" id="specAdd">Add</button>
                        </div>
                    </div>
            </div>

            <div class="form-footer">
                <div class="unsaved-note">Unsaved data will be deleted</div>
                <div class="footer-actions">
                    <button type="submit" class="btn-footer save">Add</button>
                    <button type="button" class="btn-footer discard">Discard</button>
                </div>
            </div>
        </form>
    </main>

    </div>
</div>

<script src="/admin/assets/js/product-add.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
