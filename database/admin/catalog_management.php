<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../catalog.php';

function admin_catalog_record_exists(PDO $pdo, string $table, string $column, int $id): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $column . ' = :id');
    $statement->execute([':id' => $id]);
    return ((int) $statement->fetchColumn()) > 0;
}

function admin_product_validate_input(PDO $pdo, array $input, array $files, bool $isCreate): void
{
    $name = trim((string) ($input['name'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $brandId = (int) ($input['brand'] ?? 0);
    $categoryId = (int) ($input['category'] ?? 0);
    $subCategoryId = (($input['subcategory'] ?? '') !== '') ? (int) $input['subcategory'] : 0;
    $priceRaw = trim((string) ($input['price'] ?? ''));
    $stockRaw = trim((string) ($input['stock'] ?? ''));

    if ($name === '') {
        throw new InvalidArgumentException('Product name is required.');
    }

    if ($description === '') {
        throw new InvalidArgumentException('Product description is required.');
    }

    if ($brandId <= 0) {
        throw new InvalidArgumentException('Please select a brand.');
    }

    if (!admin_catalog_record_exists($pdo, 'brands', 'brand_id', $brandId)) {
        throw new InvalidArgumentException('Selected brand is invalid.');
    }

    if ($categoryId <= 0) {
        throw new InvalidArgumentException('Please select a category.');
    }

    if (!admin_catalog_record_exists($pdo, 'categories', 'category_id', $categoryId)) {
        throw new InvalidArgumentException('Selected category is invalid.');
    }

    if ($subCategoryId > 0) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM sub_categories WHERE sub_category_id = :sub_category_id AND category_id = :category_id'
        );
        $statement->execute([
            ':sub_category_id' => $subCategoryId,
            ':category_id' => $categoryId,
        ]);

        if ((int) $statement->fetchColumn() === 0) {
            throw new InvalidArgumentException('Selected sub-category does not belong to the chosen category.');
        }
    }

    if ($priceRaw === '') {
        throw new InvalidArgumentException('Product price is required.');
    }

    if (!is_numeric($priceRaw) || (float) $priceRaw < 0) {
        throw new InvalidArgumentException('Product price must be a valid non-negative number.');
    }

    if ($stockRaw === '') {
        throw new InvalidArgumentException('Product stock is required.');
    }

    if (filter_var($stockRaw, FILTER_VALIDATE_INT) === false || (int) $stockRaw < 0) {
        throw new InvalidArgumentException('Product stock must be a valid non-negative integer.');
    }

    if ($isCreate) {
        $hasImage = isset($files['images']['name']) && is_array($files['images']['name'])
            && count(array_filter($files['images']['name'], static fn ($name): bool => trim((string) $name) !== '')) > 0;

        if (!$hasImage) {
            throw new InvalidArgumentException('Please upload at least one product image.');
        }
    }
}

function admin_catalog_storage_directory(string $type): string
{
    $map = [
        'category' => 'storage/uploads/categories',
        'brand' => 'storage/uploads/brands',
        'product' => 'storage/uploads/products',
        'usp' => 'storage/uploads/usp',
        'content' => 'storage/uploads/contents',
    ];

    return app_project_path($map[$type] ?? 'storage/uploads');
}

function admin_catalog_store_upload(?array $file, string $type): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return null;
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        throw new InvalidArgumentException('Unsupported image format.');
    }

    $directory = admin_catalog_storage_directory($type);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Failed to create upload directory.');
    }

    $fileName = $type . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.' . $extension;
    $destination = $directory . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return '/' . trim(str_replace('\\', '/', substr($destination, strlen(app_project_path()))), '/');
}

function admin_catalog_delete_file(?string $path): void
{
    $path = trim((string) $path);
    if ($path === '' || !str_starts_with($path, '/storage/uploads/')) {
        return;
    }

    $fullPath = app_project_path(ltrim($path, '/'));
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function admin_category_save(array $input, array $files): void
{
    $pdo = get_database_connection();
    $id = (int) ($input['entity_id'] ?? 0);
    $name = trim((string) ($input['categoryName'] ?? ''));
    $featured = (($input['categoryFeatured'] ?? 'no') === 'yes') ? 1 : 0;
    if ($name === '') {
        throw new InvalidArgumentException('Category name is required.');
    }

    $imagePath = admin_catalog_store_upload($files['categoryImage'] ?? null, 'category');

    if ($id > 0) {
        $current = $pdo->prepare('SELECT category_img FROM categories WHERE category_id = :id');
        $current->execute([':id' => $id]);
        $existing = $current->fetchColumn();

        $statement = $pdo->prepare(
            'UPDATE categories
            SET name = :name, featured = :featured, category_img = :image
            WHERE category_id = :id'
        );
        $statement->execute([
            ':name' => $name,
            ':featured' => $featured,
            ':image' => $imagePath ?: $existing,
            ':id' => $id,
        ]);

        if ($imagePath && is_string($existing) && $existing !== '') {
            admin_catalog_delete_file($existing);
        }
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO categories (name, featured, category_img) VALUES (:name, :featured, :image)'
    );
    $statement->execute([
        ':name' => $name,
        ':featured' => $featured,
        ':image' => $imagePath,
    ]);
}

function admin_category_delete(int $id): void
{
    $pdo = get_database_connection();
    $current = $pdo->prepare('SELECT category_img FROM categories WHERE category_id = :id');
    $current->execute([':id' => $id]);
    $image = $current->fetchColumn();

    $pdo->prepare('DELETE FROM categories WHERE category_id = :id')->execute([':id' => $id]);
    if (is_string($image) && $image !== '') {
        admin_catalog_delete_file($image);
    }
}

function admin_brand_save(array $input, array $files): void
{
    $pdo = get_database_connection();
    $id = (int) ($input['entity_id'] ?? 0);
    $name = trim((string) ($input['brandName'] ?? ''));
    $featured = (($input['brandFeatured'] ?? 'no') === 'yes') ? 1 : 0;
    if ($name === '') {
        throw new InvalidArgumentException('Brand name is required.');
    }

    $imagePath = admin_catalog_store_upload($files['brandImage'] ?? null, 'brand');

    if ($id > 0) {
        $current = $pdo->prepare('SELECT logo_file FROM brands WHERE brand_id = :id');
        $current->execute([':id' => $id]);
        $existing = $current->fetchColumn();

        $statement = $pdo->prepare(
            'UPDATE brands
             SET name = :name, featured = :featured, logo_file = :image
             WHERE brand_id = :id'
        );
        $statement->execute([
            ':name' => $name,
            ':featured' => $featured,
            ':image' => $imagePath ?: $existing,
            ':id' => $id,
        ]);

        if ($imagePath && is_string($existing) && $existing !== '') {
            admin_catalog_delete_file($existing);
        }
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO brands (name, logo_file, featured) VALUES (:name, :image, :featured)'
    );
    $statement->execute([
        ':name' => $name,
        ':image' => $imagePath,
        ':featured' => $featured,
    ]);
}

function admin_brand_delete(int $id): void
{
    $pdo = get_database_connection();
    $current = $pdo->prepare('SELECT logo_file FROM brands WHERE brand_id = :id');
    $current->execute([':id' => $id]);
    $image = $current->fetchColumn();

    $pdo->prepare('DELETE FROM brands WHERE brand_id = :id')->execute([':id' => $id]);
    if (is_string($image) && $image !== '') {
        admin_catalog_delete_file($image);
    }
}

function admin_sub_category_save(array $input): void
{
    $pdo = get_database_connection();
    $id = (int) ($input['entity_id'] ?? 0);
    $categoryId = (int) ($input['subCategoryParentId'] ?? 0);
    $name = trim((string) ($input['subCategoryName'] ?? ''));
    $featured = (($input['subCategoryFeatured'] ?? 'no') === 'yes') ? 1 : 0;

    if ($categoryId <= 0 || $name === '') {
        throw new InvalidArgumentException('Sub-category and parent category are required.');
    }

    if ($id > 0) {
        $statement = $pdo->prepare(
            'UPDATE sub_categories
             SET category_id = :category_id, name = :name, featured = :featured
             WHERE sub_category_id = :id'
        );
        $statement->execute([
            ':category_id' => $categoryId,
            ':name' => $name,
            ':featured' => $featured,
            ':id' => $id,
        ]);
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO sub_categories (category_id, name, featured)
         VALUES (:category_id, :name, :featured)'
    );
    $statement->execute([
        ':category_id' => $categoryId,
        ':name' => $name,
        ':featured' => $featured,
    ]);
}

function admin_sub_category_delete(int $id): void
{
    $pdo = get_database_connection();
    $pdo->prepare('DELETE FROM sub_categories WHERE sub_category_id = :id')->execute([':id' => $id]);
}

function admin_product_save_specs(PDO $pdo, int $productId, array $names): void
{
    $pdo->prepare('DELETE FROM product_specifications WHERE product_id = :product_id')
        ->execute([':product_id' => $productId]);

    $statement = $pdo->prepare(
        'INSERT INTO product_specifications (product_id, spec_name, spec_value)
         VALUES (:product_id, :spec_name, :spec_value)'
    );

    foreach ($names as $name) {
        $name = trim((string) $name);
        if ($name === '') {
            continue;
        }

        $statement->execute([
            ':product_id' => $productId,
            ':spec_name' => $name,
            ':spec_value' => $name,
        ]);
    }
}

function admin_product_save_named_options(PDO $pdo, string $table, string $valueColumn, int $productId, array $values): void
{
    if (!catalog_table_exists($table)) {
        return;
    }

    $pdo->prepare('DELETE FROM ' . $table . ' WHERE product_id = :product_id')
        ->execute([':product_id' => $productId]);

    $statement = $pdo->prepare(
        'INSERT INTO ' . $table . ' (product_id, ' . $valueColumn . ', sort_order)
         VALUES (:product_id, :value_name, :sort_order)'
    );

    $sortOrder = 0;
    $seen = [];
    foreach ($values as $value) {
        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }

        $normalized = strtolower($value);
        if (isset($seen[$normalized])) {
            continue;
        }

        $seen[$normalized] = true;
        $statement->execute([
            ':product_id' => $productId,
            ':value_name' => $value,
            ':sort_order' => $sortOrder++,
        ]);
    }
}

function admin_product_add_images(PDO $pdo, int $productId, array $files, int $primaryIndex = -1): array
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $statement = $pdo->prepare(
        'INSERT INTO product_images (product_id, image_file, is_primary)
         VALUES (:product_id, :image_file, :is_primary)'
    );
    $insertedIds = [];

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $single = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];

        $stored = admin_catalog_store_upload($single, 'product');
        if (!$stored) {
            continue;
        }

        $statement->execute([
            ':product_id' => $productId,
            ':image_file' => $stored,
            ':is_primary' => $i === $primaryIndex ? 1 : 0,
        ]);
        $insertedIds[] = (int) $pdo->lastInsertId();
    }

    return $insertedIds;
}

function admin_product_create(array $input, array $files): int
{
    $pdo = get_database_connection();
    admin_product_validate_input($pdo, $input, $files, true);
    $name = trim((string) ($input['name'] ?? ''));

    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            'INSERT INTO products
             (name, description, price, stock_quantity, category_id, brand_id, sub_category_id, is_featured, visibility)
             VALUES
             (:name, :description, :price, :stock_quantity, :category_id, :brand_id, :sub_category_id, :is_featured, :visibility)'
        );
        $statement->execute([
            ':name' => $name,
            ':description' => trim((string) ($input['description'] ?? '')),
            ':price' => (float) ($input['price'] ?? 0),
            ':stock_quantity' => max(0, (int) ($input['stock'] ?? 0)),
            ':category_id' => (int) ($input['category'] ?? 0),
            ':brand_id' => (int) ($input['brand'] ?? 0),
            ':sub_category_id' => (($input['subcategory'] ?? '') !== '') ? (int) $input['subcategory'] : null,
            ':is_featured' => (($input['featured'] ?? 'no') === 'yes') ? 1 : 0,
            ':visibility' => (($input['visibility'] ?? 'visible') === 'visible') ? 1 : 0,
        ]);

        $productId = (int) $pdo->lastInsertId();
        admin_product_save_specs($pdo, $productId, (array) ($input['spec_names'] ?? []));
        admin_product_save_named_options($pdo, 'product_colors', 'color_name', $productId, (array) ($input['color_names'] ?? []));
        admin_product_save_named_options($pdo, 'product_sizes', 'size_name', $productId, (array) ($input['size_names'] ?? []));
        admin_product_add_images($pdo, $productId, $files['images'] ?? [], max(0, (int) ($input['primary_image_index'] ?? 0)));

        $pdo->commit();
        return $productId;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function admin_product_delete_image_rows(PDO $pdo, array $imageIds): void
{
    if ($imageIds === []) {
        return;
    }

    $cleanIds = array_values(array_filter(array_map('intval', $imageIds), static fn (int $id): bool => $id > 0));
    if ($cleanIds === []) {
        return;
    }

    $placeholders = implode(', ', array_fill(0, count($cleanIds), '?'));
    $select = $pdo->prepare('SELECT image_file FROM product_images WHERE image_id IN (' . $placeholders . ')');
    $select->execute($cleanIds);
    foreach ($select->fetchAll(PDO::FETCH_COLUMN) as $path) {
        admin_catalog_delete_file((string) $path);
    }

    $delete = $pdo->prepare('DELETE FROM product_images WHERE image_id IN (' . $placeholders . ')');
    $delete->execute($cleanIds);
}

function admin_product_update(int $productId, array $input, array $files): void
{
    $pdo = get_database_connection();
    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid product selected.');
    }
    admin_product_validate_input($pdo, $input, $files, false);

    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            'UPDATE products
             SET name = :name,
                 description = :description,
                 price = :price,
                 stock_quantity = :stock_quantity,
                 category_id = :category_id,
                 brand_id = :brand_id,
                 sub_category_id = :sub_category_id,
                 is_featured = :is_featured,
                 visibility = :visibility
             WHERE product_id = :product_id'
        );
        $statement->execute([
            ':name' => trim((string) ($input['name'] ?? '')),
            ':description' => trim((string) ($input['description'] ?? '')),
            ':price' => (float) ($input['price'] ?? 0),
            ':stock_quantity' => max(0, (int) ($input['stock'] ?? 0)),
            ':category_id' => (int) ($input['category'] ?? 0),
            ':brand_id' => (int) ($input['brand'] ?? 0),
            ':sub_category_id' => (($input['subcategory'] ?? '') !== '') ? (int) $input['subcategory'] : null,
            ':is_featured' => (($input['featured'] ?? 'no') === 'yes') ? 1 : 0,
            ':visibility' => (($input['visibility'] ?? 'visible') === 'visible') ? 1 : 0,
            ':product_id' => $productId,
        ]);

        admin_product_save_specs($pdo, $productId, (array) ($input['spec_names'] ?? []));
        admin_product_save_named_options($pdo, 'product_colors', 'color_name', $productId, (array) ($input['color_names'] ?? []));
        admin_product_save_named_options($pdo, 'product_sizes', 'size_name', $productId, (array) ($input['size_names'] ?? []));
        admin_product_delete_image_rows($pdo, (array) ($input['deleted_image_ids'] ?? []));

        $primaryKey = trim((string) ($input['primary_image_key'] ?? ''));
        $primaryExistingId = 0;
        $primaryNewIndex = null;
        if (str_starts_with($primaryKey, 'existing:')) {
            $primaryExistingId = (int) substr($primaryKey, 9);
        } elseif (str_starts_with($primaryKey, 'new:')) {
            $primaryNewIndex = (int) substr($primaryKey, 4);
        }

        if ($primaryExistingId > 0) {
            $pdo->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id')
                ->execute([':product_id' => $productId]);
            $pdo->prepare('UPDATE product_images SET is_primary = 1 WHERE product_id = :product_id AND image_id = :image_id')
                ->execute([':product_id' => $productId, ':image_id' => $primaryExistingId]);
        }

        $newImageIds = admin_product_add_images($pdo, $productId, $files['new_images'] ?? [], $primaryNewIndex ?? -1);

        if ($primaryNewIndex !== null && isset($newImageIds[$primaryNewIndex])) {
            $pdo->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id')
                ->execute([':product_id' => $productId]);
            $pdo->prepare('UPDATE product_images SET is_primary = 1 WHERE image_id = :image_id')
                ->execute([':image_id' => $newImageIds[$primaryNewIndex]]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function admin_product_delete(int $productId): void
{
    $pdo = get_database_connection();
    $pdo->beginTransaction();
    try {
        $paths = $pdo->prepare('SELECT image_file FROM product_images WHERE product_id = :product_id');
        $paths->execute([':product_id' => $productId]);
        foreach ($paths->fetchAll(PDO::FETCH_COLUMN) as $path) {
            admin_catalog_delete_file((string) $path);
        }

        $pdo->prepare('DELETE FROM product_images WHERE product_id = :product_id')->execute([':product_id' => $productId]);
        $pdo->prepare('DELETE FROM product_specifications WHERE product_id = :product_id')->execute([':product_id' => $productId]);
        if (catalog_table_exists('product_colors')) {
            $pdo->prepare('DELETE FROM product_colors WHERE product_id = :product_id')->execute([':product_id' => $productId]);
        }
        if (catalog_table_exists('product_sizes')) {
            $pdo->prepare('DELETE FROM product_sizes WHERE product_id = :product_id')->execute([':product_id' => $productId]);
        }
        $pdo->prepare('DELETE FROM unboxing_videos WHERE product_id = :product_id')->execute([':product_id' => $productId]);
        $pdo->prepare('DELETE FROM products WHERE product_id = :product_id')->execute([':product_id' => $productId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}
