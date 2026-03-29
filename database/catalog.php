<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

function catalog_table_exists(string $tableName): bool
{
    static $tableMap = [];

    if (array_key_exists($tableName, $tableMap)) {
        return $tableMap[$tableName];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
    );
    $statement->execute([':table_name' => $tableName]);

    return $tableMap[$tableName] = ((int) $statement->fetchColumn()) > 0;
}

function catalog_usp_sections_table(): string
{
    return 'unique_selling_point_sections';
}

function catalog_usp_items_table(): string
{
    return 'unique_selling_point_items';
}

function catalog_ensure_unique_selling_point_tables(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $pdo = get_database_connection();
    $uspSectionsTable = catalog_usp_sections_table();
    $uspItemsTable = catalog_usp_items_table();

    $tableExists = static function (string $tableName) use ($pdo): bool {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
        );
        $statement->execute([':table_name' => $tableName]);
        return ((int) $statement->fetchColumn()) > 0;
    };

    $hasUspSections = $tableExists($uspSectionsTable);
    $hasUspItems = $tableExists($uspItemsTable);
    $hasLegacySections = $tableExists('cms_sections');
    $hasLegacyItems = $tableExists('cms_items');

    if (!$hasUspSections && !$hasUspItems && $hasLegacySections && $hasLegacyItems) {
        $pdo->exec(
            'RENAME TABLE cms_sections TO ' . $uspSectionsTable . ', cms_items TO ' . $uspItemsTable
        );
        $hasUspSections = true;
        $hasUspItems = true;
    }

    if (!$hasUspSections) {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . $uspSectionsTable . ' (
                section_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                page VARCHAR(100) NOT NULL,
                type VARCHAR(100) NOT NULL,
                position INT(11) NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (section_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
        );
    }

    if (!$hasUspItems) {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . $uspItemsTable . ' (
                item_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                section_id BIGINT(20) UNSIGNED NOT NULL,
                title VARCHAR(255) DEFAULT NULL,
                title_color VARCHAR(30) DEFAULT NULL,
                subtitle VARCHAR(255) DEFAULT NULL,
                subtitle_color VARCHAR(30) DEFAULT NULL,
                link_url VARCHAR(255) DEFAULT NULL,
                button1_url VARCHAR(255) DEFAULT NULL,
                button1_background_color VARCHAR(30) DEFAULT NULL,
                button1_text_color VARCHAR(30) DEFAULT NULL,
                button2_url VARCHAR(255) DEFAULT NULL,
                button2_background_color VARCHAR(30) DEFAULT NULL,
                button2_text_color VARCHAR(30) DEFAULT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (item_id),
                KEY fk_unique_selling_point_items_section (section_id),
                CONSTRAINT fk_unique_selling_point_items_section
                    FOREIGN KEY (section_id) REFERENCES ' . $uspSectionsTable . ' (section_id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
        );
    }

    $ensured = true;
}

function catalog_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    return trim($value, '-');
}

function catalog_public_file_url(?string $path, string $fallback = '/storage/uploads/products/placeholder-camera.png'): string
{
    $path = trim((string) $path);
    if ($path === '') {
        $path = $fallback;
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    $normalizedPath = '/' . ltrim($path, '/');
    $basePath = app_base_path();

    if ($basePath !== '' && ($normalizedPath === $basePath || str_starts_with($normalizedPath, $basePath . '/'))) {
        return $normalizedPath;
    }

    return app_path($normalizedPath);
}

function catalog_fetch_unique_selling_points_for_product_detail(): array
{
    catalog_ensure_unique_selling_point_tables();

    $uspSectionsTable = catalog_usp_sections_table();
    $uspItemsTable = catalog_usp_items_table();
    if (!catalog_table_exists($uspSectionsTable) || !catalog_table_exists($uspItemsTable)) {
        return [];
    }

    $pdo = get_database_connection();

    try {
        $statement = $pdo->prepare(
            'SELECT
                ci.item_id,
                ci.title,
                ci.subtitle,
                ci.link_url,
                ci.active
             FROM ' . $uspItemsTable . ' ci
             INNER JOIN ' . $uspSectionsTable . ' cs ON cs.section_id = ci.section_id
             WHERE cs.page = :page
               AND cs.type = :type
               AND cs.active = 1
               AND ci.active = 1
             ORDER BY ci.item_id ASC'
        );
        $statement->execute([
            ':page' => 'home',
            ':type' => 'unique_selling_points',
        ]);

        $items = $statement->fetchAll();
        foreach ($items as &$item) {
            $item['icon_url'] = catalog_public_file_url($item['link_url'], '');
        }
        unset($item);

        return $items;
    } catch (Throwable $exception) {
        return [];
    }
}

function catalog_fetch_brand_options(): array
{
    $pdo = get_database_connection();
    $statement = $pdo->query('SELECT brand_id, name, logo_file, featured FROM brands ORDER BY brand_id ASC');

    return $statement->fetchAll();
}

function catalog_fetch_category_options(): array
{
    $pdo = get_database_connection();
    $statement = $pdo->query('SELECT category_id, name, featured, category_img FROM categories ORDER BY category_id ASC');

    return $statement->fetchAll();
}

function catalog_fetch_sub_category_options(?int $categoryId = null): array
{
    $pdo = get_database_connection();
    $sql = '
        SELECT sc.sub_category_id, sc.category_id, sc.name, sc.featured, c.name AS category_name
        FROM sub_categories sc
        INNER JOIN categories c ON c.category_id = sc.category_id
    ';
    $bindings = [];
    if ($categoryId !== null && $categoryId > 0) {
        $sql .= ' WHERE sc.category_id = :category_id';
        $bindings[':category_id'] = $categoryId;
    }
    $sql .= ' ORDER BY sc.sub_category_id ASC';

    $statement = $pdo->prepare($sql);
    $statement->execute($bindings);
    return $statement->fetchAll();
}

function catalog_fetch_nav_categories(): array
{
    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT
            c.category_id,
            c.name AS category_name,
            sc.sub_category_id,
            sc.name AS sub_category_name
        FROM categories c
        LEFT JOIN sub_categories sc ON sc.category_id = c.category_id
        ORDER BY c.category_id ASC, sc.sub_category_id ASC'
    );

    $categories = [];
    foreach ($statement->fetchAll() as $row) {
        $categoryId = (int) $row['category_id'];
        if (!isset($categories[$categoryId])) {
            $categories[$categoryId] = [
                'category_id' => $categoryId,
                'name' => (string) $row['category_name'],
                'slug' => catalog_slug((string) $row['category_name']),
                'sub_categories' => [],
            ];
        }

        if (!empty($row['sub_category_id'])) {
            $categories[$categoryId]['sub_categories'][] = [
                'sub_category_id' => (int) $row['sub_category_id'],
                'name' => (string) $row['sub_category_name'],
                'slug' => catalog_slug((string) $row['sub_category_name']),
            ];
        }
    }

    return array_values($categories);
}

function catalog_fetch_nav_brands(): array
{
    $brands = catalog_fetch_brand_options();
    foreach ($brands as &$brand) {
        $brand['slug'] = catalog_slug((string) $brand['name']);
    }

    return $brands;
}

function catalog_fetch_admin_products(array $filters = []): array
{
    $pdo = get_database_connection();
    $sql = 'SELECT
            p.product_id,
            p.name,
            p.price,
            p.stock_quantity,
            p.visibility,
            p.is_featured,
            b.name AS brand_name,
            c.name AS category_name,
            sc.name AS sub_category_name,
            (
                SELECT pi.image_file
                FROM product_images pi
                WHERE pi.product_id = p.product_id
                ORDER BY pi.is_primary DESC, pi.image_id ASC
                LIMIT 1
            ) AS image_file
         FROM products p
         INNER JOIN brands b ON b.brand_id = p.brand_id
         INNER JOIN categories c ON c.category_id = p.category_id
         LEFT JOIN sub_categories sc ON sc.sub_category_id = p.sub_category_id
         WHERE 1 = 1';

    $bindings = [];
    $query = trim((string) ($filters['q'] ?? ''));
    if ($query !== '') {
        $sql .= ' AND (
            p.name LIKE :query_name
            OR b.name LIKE :query_brand
            OR c.name LIKE :query_category
            OR COALESCE(sc.name, "") LIKE :query_sub_category
        )';
        $queryBinding = '%' . $query . '%';
        $bindings[':query_name'] = $queryBinding;
        $bindings[':query_brand'] = $queryBinding;
        $bindings[':query_category'] = $queryBinding;
        $bindings[':query_sub_category'] = $queryBinding;
    }

    $sql .= ' ORDER BY p.product_id ASC';

    $statement = $pdo->prepare($sql);
    $statement->execute($bindings);

    $products = $statement->fetchAll();
    foreach ($products as &$product) {
        $product['image_url'] = catalog_public_file_url($product['image_file']);
    }

    return $products;
}

function catalog_fetch_dashboard_stock_items(int $limit = 12): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT
            p.product_id,
            p.name,
            p.stock_quantity
         FROM products p
         ORDER BY
            CASE
                WHEN p.stock_quantity <= 0 THEN 0
                WHEN p.stock_quantity <= 10 THEN 1
                ELSE 2
            END ASC,
            p.stock_quantity ASC,
            p.product_id ASC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $statement->execute();

    $items = $statement->fetchAll();
    foreach ($items as &$item) {
        $quantity = (int) $item['stock_quantity'];
        if ($quantity <= 0) {
            $item['status'] = 'Out of Stock';
            $item['status_class'] = 'out-stock';
        } elseif ($quantity <= 10) {
            $item['status'] = 'Low Stock';
            $item['status_class'] = 'low-stock';
        } else {
            $item['status'] = 'In Stock';
            $item['status_class'] = 'in-stock';
        }
    }

    return $items;
}

function catalog_fetch_product_images(int $productId): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT image_id, image_file, is_primary
         FROM product_images
         WHERE product_id = :product_id
         ORDER BY is_primary DESC, image_id ASC'
    );
    $statement->execute([':product_id' => $productId]);
    $images = $statement->fetchAll();

    foreach ($images as &$image) {
        $image['url'] = catalog_public_file_url($image['image_file']);
    }

    return $images;
}

function catalog_fetch_product_specs(int $productId): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT spec_id, spec_name, spec_value
         FROM product_specifications
         WHERE product_id = :product_id
         ORDER BY spec_id ASC'
    );
    $statement->execute([':product_id' => $productId]);
    return $statement->fetchAll();
}

function catalog_fetch_product_colors(int $productId): array
{
    if (!catalog_table_exists('product_colors')) {
        return [];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT color_id, color_name, sort_order
         FROM product_colors
         WHERE product_id = :product_id
         ORDER BY sort_order ASC, color_id ASC'
    );
    $statement->execute([':product_id' => $productId]);
    return $statement->fetchAll();
}

function catalog_fetch_product_sizes(int $productId): array
{
    if (!catalog_table_exists('product_sizes')) {
        return [];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT size_id, size_name, sort_order
         FROM product_sizes
         WHERE product_id = :product_id
         ORDER BY sort_order ASC, size_id ASC'
    );
    $statement->execute([':product_id' => $productId]);
    return $statement->fetchAll();
}

function catalog_fetch_product_row(int $productId, bool $includeHidden = false): ?array
{
    $pdo = get_database_connection();
    $sql = '
        SELECT
            p.product_id,
            p.name,
            p.description,
            p.price,
            p.stock_quantity,
            p.category_id,
            p.brand_id,
            p.sub_category_id,
            p.is_featured,
            p.visibility,
            p.created_at,
            b.name AS brand_name,
            c.name AS category_name,
            sc.name AS sub_category_name
        FROM products p
        INNER JOIN brands b ON b.brand_id = p.brand_id
        INNER JOIN categories c ON c.category_id = p.category_id
        LEFT JOIN sub_categories sc ON sc.sub_category_id = p.sub_category_id
        WHERE p.product_id = :product_id
    ';

    if (!$includeHidden) {
        $sql .= ' AND p.visibility = 1';
    }

    $sql .= ' LIMIT 1';

    $statement = $pdo->prepare($sql);
    $statement->execute([':product_id' => $productId]);
    $product = $statement->fetch();

    return $product ?: null;
}

function catalog_discount_parse_valid_users(?string $value): array
{
    $value = trim((string) $value);
    if ($value === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== ''));
}

function catalog_fetch_discount_user_context(?int $userId): array
{
    if (($userId ?? 0) <= 0) {
        return [
            'user_id' => 0,
            'membership_tier_id' => null,
            'standard_tier_ids' => catalog_fetch_standard_discount_tier_ids(),
        ];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT id, membership_tier_id
         FROM users
         WHERE id = :user_id
         LIMIT 1'
    );
    $statement->execute([':user_id' => (int) $userId]);
    $row = $statement->fetch();

    return [
        'user_id' => (int) ($row['id'] ?? $userId),
        'membership_tier_id' => $row && $row['membership_tier_id'] !== null ? (int) $row['membership_tier_id'] : null,
        'standard_tier_ids' => catalog_fetch_standard_discount_tier_ids(),
    ];
}

function catalog_discount_is_valid_for_user(array $discount, array $userContext): bool
{
    $validUsers = catalog_discount_parse_valid_users($discount['valid_user'] ?? null);
    if ($validUsers === []) {
        return true;
    }

    $membershipTierId = $userContext['membership_tier_id'] ?? null;
    if ($membershipTierId === null) {
        return in_array('standard', $validUsers, true);
    }

    if (in_array('tier:' . (int) $membershipTierId, $validUsers, true)) {
        return true;
    }

    return in_array('standard', $validUsers, true)
        && in_array((int) $membershipTierId, $userContext['standard_tier_ids'] ?? [], true);
}

function catalog_fetch_standard_discount_tier_ids(): array
{
    static $tierIds = null;
    if (is_array($tierIds)) {
        return $tierIds;
    }

    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT id, min_spent
         FROM membership_tiers
         ORDER BY min_spent ASC, id ASC'
    );

    $tierIds = [];
    $lowestMinSpent = null;
    foreach ($statement->fetchAll() as $tier) {
        $minSpent = (float) ($tier['min_spent'] ?? 0);
        if ($lowestMinSpent === null) {
            $lowestMinSpent = $minSpent;
        }

        if (abs($minSpent - $lowestMinSpent) > 0.00001) {
            break;
        }

        $tierIds[] = (int) $tier['id'];
    }

    return $tierIds;
}

function catalog_calculate_unit_discount_amount(float $unitPrice, array $discount): float
{
    $valueType = strtolower((string) ($discount['value_type'] ?? 'percentage'));
    $value = (float) ($discount['value'] ?? 0);

    if ($valueType === 'fixed') {
        return max(min($value, $unitPrice), 0);
    }

    return max(min($unitPrice * ($value / 100), $unitPrice), 0);
}

function catalog_fetch_applicable_discount_for_product(?int $userId, array $product): ?array
{
    $pdo = get_database_connection();
    $userContext = catalog_fetch_discount_user_context($userId);

    $statement = $pdo->prepare(
        'SELECT DISTINCT
            d.id,
            d.name,
            d.discount_type,
            d.value_type,
            d.value,
            d.valid_user,
            d.priority,
            d.start_date,
            d.end_date,
            d.status
         FROM discounts d
         LEFT JOIN discount_conditions dc ON dc.discount_id = d.id
         WHERE LOWER(d.status) = "active"
           AND LOWER(d.discount_type) <> "bundle"
           AND (d.start_date IS NULL OR d.start_date <= NOW())
           AND (d.end_date IS NULL OR d.end_date >= NOW())
           AND (
                dc.id IS NULL
                OR (dc.apply_to = "product" AND dc.ref_id = :product_id)
                OR (dc.apply_to = "category" AND dc.ref_id = :category_id)
                OR (:sub_category_present = 1 AND dc.apply_to = "sub_category" AND dc.ref_id = :sub_category_id)
                OR (dc.apply_to = "brand" AND dc.ref_id = :brand_id)
           )
         ORDER BY d.priority ASC, d.id ASC'
    );
    $statement->bindValue(':product_id', (int) $product['product_id'], PDO::PARAM_INT);
    $statement->bindValue(':category_id', (int) $product['category_id'], PDO::PARAM_INT);
    if (($product['sub_category_id'] ?? null) !== null) {
        $statement->bindValue(':sub_category_present', 1, PDO::PARAM_INT);
        $statement->bindValue(':sub_category_id', (int) $product['sub_category_id'], PDO::PARAM_INT);
    } else {
        $statement->bindValue(':sub_category_present', 0, PDO::PARAM_INT);
        $statement->bindValue(':sub_category_id', 0, PDO::PARAM_INT);
    }
    $statement->bindValue(':brand_id', (int) $product['brand_id'], PDO::PARAM_INT);
    $statement->execute();

    $rows = $statement->fetchAll();
    if ($rows === []) {
        return null;
    }

    $applicable = [];
    $unitPrice = (float) ($product['price'] ?? 0);
    foreach ($rows as $discount) {
        if (!catalog_discount_is_valid_for_user($discount, $userContext)) {
            continue;
        }

        $discount['unit_discount_amount'] = catalog_calculate_unit_discount_amount($unitPrice, $discount);
        if ((float) $discount['unit_discount_amount'] <= 0) {
            continue;
        }

        $applicable[] = $discount;
    }

    if ($applicable === []) {
        return null;
    }

    usort($applicable, static function (array $left, array $right): int {
        $leftPriority = isset($left['priority']) ? (int) $left['priority'] : PHP_INT_MAX;
        $rightPriority = isset($right['priority']) ? (int) $right['priority'] : PHP_INT_MAX;
        if ($leftPriority !== $rightPriority) {
            return $leftPriority <=> $rightPriority;
        }

        $leftAmount = (float) ($left['unit_discount_amount'] ?? 0);
        $rightAmount = (float) ($right['unit_discount_amount'] ?? 0);
        if ($leftAmount !== $rightAmount) {
            return $rightAmount <=> $leftAmount;
        }

        return ((int) $left['id']) <=> ((int) $right['id']);
    });

    return $applicable[0];
}

function catalog_apply_discount_display(array &$product, ?int $userId): void
{
    $originalPrice = (float) ($product['price'] ?? 0);
    $product['discount_name'] = '';
    $product['discount_value_type'] = '';
    $product['discount_value'] = 0;
    $product['discount_start_date'] = null;
    $product['discount_end_date'] = null;
    $product['unit_discount_amount'] = 0;
    $product['discounted_price_value'] = $originalPrice;
    $product['has_discount'] = false;

    $discount = catalog_fetch_applicable_discount_for_product($userId, $product);
    if ($discount) {
        $discountAmount = (float) ($discount['unit_discount_amount'] ?? 0);
        $discountedPrice = max($originalPrice - $discountAmount, 0);
        $product['discount_name'] = (string) ($discount['name'] ?? '');
        $product['discount_value_type'] = strtolower((string) ($discount['value_type'] ?? 'percentage'));
        $product['discount_value'] = (float) ($discount['value'] ?? 0);
        $product['discount_start_date'] = $discount['start_date'] ?? null;
        $product['discount_end_date'] = $discount['end_date'] ?? null;
        $product['unit_discount_amount'] = $discountAmount;
        $product['discounted_price_value'] = $discountedPrice;
        $product['has_discount'] = $discountAmount > 0;
    }

    $product['price_label'] = number_format($product['discounted_price_value']) . ' MMK';
    $product['original_price_label'] = $product['has_discount'] ? number_format($originalPrice) . ' MMK' : '';
    $product['discount_badge_label'] = $product['discount_name'];
}

function catalog_product_is_new_arrival(array $product, int $days = 30): bool
{
    $createdAt = trim((string) ($product['created_at'] ?? ''));
    if ($createdAt === '') {
        return false;
    }

    $createdTimestamp = strtotime($createdAt);
    if ($createdTimestamp === false) {
        return false;
    }

    return $createdTimestamp >= strtotime('-' . max(1, $days) . ' days');
}

function catalog_discount_has_limited_duration(array $product): bool
{
    if (empty($product['has_discount'])) {
        return false;
    }

    return trim((string) ($product['discount_end_date'] ?? '')) !== '';
}

function catalog_format_limited_time_sale_label(array $product): string
{
    if (!catalog_discount_has_limited_duration($product)) {
        return '';
    }

    $startDate = trim((string) ($product['discount_start_date'] ?? ''));
    $endDate = trim((string) ($product['discount_end_date'] ?? ''));
    $startTimestamp = $startDate !== '' ? strtotime($startDate) : false;
    $endTimestamp = strtotime($endDate);
    if ($endTimestamp === false) {
        return '';
    }

    $startLabel = $startTimestamp !== false ? date('d.m.Y', $startTimestamp) : '';
    $endLabel = date('d.m.Y', $endTimestamp);

    if ($startLabel !== '') {
        return $startLabel . ' to ' . $endLabel;
    }

    return $endLabel;
}

function fetch_product_by_id(int $productId, bool $includeHidden = false, ?int $userId = null): ?array
{
    $product = catalog_fetch_product_row($productId, $includeHidden);
    if (!$product) {
        return null;
    }

    $product['images'] = catalog_fetch_product_images((int) $product['product_id']);
    if ($product['images'] === []) {
        $product['images'][] = [
            'image_id' => null,
            'image_file' => null,
            'is_primary' => 1,
            'url' => catalog_public_file_url(null),
        ];
    }

    $product['specs'] = catalog_fetch_product_specs((int) $product['product_id']);
    $product['colors'] = catalog_fetch_product_colors((int) $product['product_id']);
    $product['sizes'] = catalog_fetch_product_sizes((int) $product['product_id']);
    catalog_apply_discount_display($product, $userId);
    $stock = (int) $product['stock_quantity'];
    $product['availability_label'] = $stock > 0 ? 'In Stock' : 'Out of Stock';
    $product['stock_badge'] = $stock <= 0 ? 'Out of stock' : ($stock <= 10 ? 'Hurry! Only ' . $stock . ' left' : 'Available');
    $product['image_url'] = $product['images'][0]['url'];

    return $product;
}

function fetch_related_products(int $productId, int $categoryId, int $limit = 6, ?int $userId = null): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT
            p.product_id,
            p.name,
            p.price,
            p.is_featured,
            b.name AS brand_name,
            (
                SELECT pi.image_file
                FROM product_images pi
                WHERE pi.product_id = p.product_id
                ORDER BY pi.is_primary DESC, pi.image_id ASC
                LIMIT 1
            ) AS image_file
         FROM products p
         INNER JOIN brands b ON b.brand_id = p.brand_id
         WHERE p.visibility = 1
           AND p.category_id = :category_id
           AND p.product_id <> :product_id
         ORDER BY p.created_at DESC, p.product_id DESC
         LIMIT ' . max(1, (int) $limit)
    );
    $statement->execute([
        ':category_id' => $categoryId,
        ':product_id' => $productId,
    ]);

    $products = $statement->fetchAll();
    foreach ($products as &$product) {
        $product['image_url'] = catalog_public_file_url($product['image_file']);
        catalog_apply_discount_display($product, $userId);
    }

    return $products;
}

function catalog_fetch_customer_products(array $filters = []): array
{
    $pdo = get_database_connection();
    $sql = '
        SELECT
            p.product_id,
            p.name,
            p.description,
            p.price,
            p.stock_quantity,
            p.is_featured,
            p.created_at,
            p.category_id,
            p.brand_id,
            p.sub_category_id,
            b.name AS brand_name,
            c.name AS category_name,
            sc.name AS sub_category_name,
            (
                SELECT pi.image_file
                FROM product_images pi
                WHERE pi.product_id = p.product_id
                ORDER BY pi.is_primary DESC, pi.image_id ASC
                LIMIT 1
            ) AS image_file
        FROM products p
        INNER JOIN brands b ON b.brand_id = p.brand_id
        INNER JOIN categories c ON c.category_id = p.category_id
        LEFT JOIN sub_categories sc ON sc.sub_category_id = p.sub_category_id
        WHERE p.visibility = 1
    ';

    $bindings = [];

    if (!empty($filters['category'])) {
        $sql .= ' AND c.name = :category_name';
        $bindings[':category_name'] = $filters['category'];
    }

    if (!empty($filters['brand'])) {
        $sql .= ' AND b.name = :brand_name';
        $bindings[':brand_name'] = $filters['brand'];
    }

    if (!empty($filters['sub_category'])) {
        $sql .= ' AND sc.name = :sub_category_name';
        $bindings[':sub_category_name'] = $filters['sub_category'];
    }

    $query = trim((string) ($filters['q'] ?? ''));
    if ($query !== '') {
        $sql .= ' AND (
            p.name LIKE :query_name
            OR COALESCE(p.description, "") LIKE :query_description
            OR b.name LIKE :query_brand
            OR c.name LIKE :query_category
            OR COALESCE(sc.name, "") LIKE :query_sub_category
        )';
        $queryBinding = '%' . $query . '%';
        $bindings[':query_name'] = $queryBinding;
        $bindings[':query_description'] = $queryBinding;
        $bindings[':query_brand'] = $queryBinding;
        $bindings[':query_category'] = $queryBinding;
        $bindings[':query_sub_category'] = $queryBinding;
    }

    $sql .= ' ORDER BY p.created_at DESC, p.product_id DESC';

    $statement = $pdo->prepare($sql);
    $statement->execute($bindings);
    $products = $statement->fetchAll();
    $userId = isset($filters['user_id']) && (int) $filters['user_id'] > 0 ? (int) $filters['user_id'] : null;

    foreach ($products as &$product) {
        $product['image'] = catalog_public_file_url($product['image_file']);
        $product['brand'] = $product['brand_name'];
        $product['category'] = $product['category_name'];
        $product['sub_category'] = $product['sub_category_name'];
        $product['availability'] = (int) $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock';
        catalog_apply_discount_display($product, $userId);
        $product['tags'] = [];
        if (!empty($product['is_featured'])) {
            $product['tags'][] = 'Best Sellers';
        }
        if (catalog_product_is_new_arrival($product)) {
            $product['tags'][] = 'New Arrivals';
        }
        if (catalog_discount_has_limited_duration($product)) {
            $product['tags'][] = 'Limited-time Sales';
        }
        $product['limited_time_sale_label'] = catalog_format_limited_time_sale_label($product);
        $product['price_value'] = (float) $product['discounted_price_value'];
        $product['price'] = $product['price_label'];
        $product['original'] = $product['original_price_label'];
        $product['discount'] = $product['discount_badge_label'];
    }

    return $products;
}
function catalog_search_products(string $query, int $limit = 24): array
{
    $results = catalog_fetch_customer_products(['q' => $query]);

    if ($limit > 0 && count($results) > $limit) {
        return array_slice($results, 0, $limit);
    }

    return $results;
}

function catalog_fetch_search_index(int $limit = 200): array
{
    $products = catalog_fetch_customer_products([]);
    if ($limit > 0 && count($products) > $limit) {
        $products = array_slice($products, 0, $limit);
    }

    foreach ($products as &$product) {
        $specRows = catalog_fetch_product_specs((int) $product['product_id']);
        $specs = [];

        foreach ($specRows as $spec) {
            $name = trim((string) ($spec['spec_name'] ?? ''));
            $value = trim((string) ($spec['spec_value'] ?? ''));
            $label = $name !== '' && $value !== '' && $name !== $value
                ? $name . ': ' . $value
                : ($name !== '' ? $name : $value);

            if ($label === '') {
                continue;
            }

            $specs[] = $label;
            if (count($specs) >= 5) {
                break;
            }
        }

        $product['detail_url'] = 'product-details.php?id=' . (int) $product['product_id'];
        $product['search_blob'] = strtolower(implode(' ', array_filter([
            (string) ($product['name'] ?? ''),
            (string) ($product['description'] ?? ''),
            (string) ($product['brand_name'] ?? $product['brand'] ?? ''),
            (string) ($product['category_name'] ?? $product['category'] ?? ''),
            (string) ($product['sub_category_name'] ?? $product['sub_category'] ?? ''),
            implode(' ', $specs),
        ])));
        $product['spec_labels'] = $specs;
    }
    unset($product);

    return $products;
}



