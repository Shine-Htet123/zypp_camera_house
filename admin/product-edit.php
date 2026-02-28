<?php
$product = [
    'name' => 'Product Name',
    'brand' => 'Brand Name',
    'price' => '1000000',
    'stock' => 23,
    'category' => 'Camera',
    'subcategory' => 'Mirrorless Camera',
    'featured' => 'Yes',
    'visibility' => 'Visible',
    'description' => 'This is the description of the product',
];

$images = [
    '/storage/uploads/products/camera.png',
    '/storage/uploads/products/camera2.png',
    '/storage/uploads/products/camera3.png',
    '/storage/uploads/products/camera4.png',
    '/storage/uploads/products/camera5.png',
];

$specs = [
    'Specification 1',
    'Specification 2',
    'Specification 3',
    'Specification 4',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/product-edit.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-product-add">
        <header class="page-header">
            <h1>Edit Product</h1>
        </header>

        <form class="product-form" action="#" method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-left">
                    <label class="field">
                        <span>Name:</span>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </label>

                    <label class="field select">
                        <span>Brand:</span>
                        <select name="brand" required>
                            <option><?php echo htmlspecialchars($product['brand']); ?></option>
                            <option>Canon</option>
                            <option>Sony</option>
                            <option>Nikon</option>
                            <option>DJI</option>
                        </select>
                    </label>

                    <label class="field price">
                        <span>Price:</span>
                        <div class="price-input">
                            <input type="number" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" min="0" step="1" required>
                            <span class="suffix">MMK</span>
                        </div>
                    </label>

                    <label class="field textarea block">
                        <span>Description:</span>
                        <textarea name="description" rows="7"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </label>
                </div>

                <div class="form-right">
                    <label class="field">
                        <span>Stock:</span>
                        <input type="number" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" min="0" step="1" required>
                    </label>

                    <label class="field select">
                        <span>Category:</span>
                        <select name="category" required>
                            <option><?php echo htmlspecialchars($product['category']); ?></option>
                            <option>Camera</option>
                            <option>Lenses</option>
                            <option>Tripods</option>
                            <option>Lighting</option>
                        </select>
                    </label>

                    <label class="field select">
                        <span>Sub-Category:</span>
                        <select name="subcategory">
                            <option><?php echo htmlspecialchars($product['subcategory']); ?></option>
                            <option>Accessories</option>
                        </select>
                    </label>

                    <div class="field inline block">
                        <div class="inline-field">
                            <span>Is Featured:</span>
                            <select name="featured">
                                <option><?php echo htmlspecialchars($product['featured']); ?></option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="inline-field">
                            <span>Visibility:</span>
                            <select name="visibility">
                                <option><?php echo htmlspecialchars($product['visibility']); ?></option>
                                <option>Hidden</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="images-edit">
                <div class="images-header">
                    <span>Images</span>
                </div>
                <input type="file" id="imageUploadInput" accept="image/*" hidden>
                <div class="image-grid" id="editImageGrid">
                    <?php foreach ($images as $idx => $img): ?>
                        <div class="image-tile<?php echo $idx === 0 ? ' active' : ''; ?>" data-index="<?php echo $idx; ?>">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Product image">
                            <div class="image-tools">
                                <button type="button" class="icon-btn reupload" title="Reupload">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                                <label class="primary-radio" title="Set primary">
                                    <input type="radio" name="primary_image" <?php echo $idx === 0 ? 'checked' : ''; ?>>
                                    <span></span>
                                </label>
                                <button type="button" class="icon-btn delete" title="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="image-tile upload-tile" id="imageUploadTile">
                        <i class="fa-solid fa-upload"></i>
                    </div>
                </div>
            </div>

            <div class="specs-edit">
                <span>Specifications</span>
                <ul class="spec-list">
                    <?php foreach ($specs as $idx => $spec): ?>
                        <li class="spec-item" data-spec-id="spec-<?php echo $idx; ?>">
                            <span class="spec-dot"></span>
                            <span class="spec-text"><?php echo htmlspecialchars($spec); ?></span>
                            <span class="spec-actions">
                                <button type="button" class="icon-btn edit"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button type="button" class="icon-btn delete"><i class="fa-regular fa-trash-can"></i></button>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="spec-row">
                    <span class="spec-dot"></span>
                    <input type="text" id="specInput" placeholder="New Specification...">
                    <button type="button" class="btn-spec-add" id="specAdd">Add</button>
                </div>
            </div>

            <div class="form-footer">
                <div class="unsaved-note">Unsaved data will be deleted</div>
                <div class="footer-actions">
                    <button type="submit" class="btn-footer save">Save</button>
                    <button type="button" class="btn-footer discard">Discard</button>
                </div>
            </div>
        </form>
    </main>

    </div>
</div>

<script src="/admin/assets/js/product-edit.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
