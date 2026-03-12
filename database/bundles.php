<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/catalog.php';

function bundle_build_public_id(array $bundle): string
{
    $bundleId = (int) ($bundle['id'] ?? 0);
    $createdAt = trim((string) ($bundle['created_at'] ?? ''));
    $timestamp = $createdAt !== '' ? strtotime($createdAt) : false;
    $datePart = $timestamp !== false ? date('Ymd', $timestamp) : date('Ymd');

    return 'BUN' . $datePart . str_pad((string) max(1, $bundleId), 4, '0', STR_PAD_LEFT);
}

function bundle_format_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return (string) $value;
    }

    return date('d.m.Y H:i:s', $timestamp);
}

function bundle_discount_is_active(array $discount): bool
{
    if (strtolower((string) ($discount['status'] ?? 'inactive')) !== 'active') {
        return false;
    }

    $startDate = trim((string) ($discount['start_date'] ?? ''));
    if ($startDate !== '' && strtotime($startDate) > time()) {
        return false;
    }

    $endDate = trim((string) ($discount['end_date'] ?? ''));
    if ($endDate !== '' && strtotime($endDate) < time()) {
        return false;
    }

    return true;
}

function bundle_calculate_discount_amount(float $subtotal, array $discount): float
{
    $valueType = strtolower((string) ($discount['value_type'] ?? 'percentage'));
    $value = (float) ($discount['value'] ?? 0);

    if ($subtotal <= 0 || $value <= 0) {
        return 0.0;
    }

    if ($valueType === 'fixed') {
        return max(min($value, $subtotal), 0);
    }

    return max(min($subtotal * ($value / 100), $subtotal), 0);
}

function bundle_fetch_discount_options(bool $activeOnly = false): array
{
    $pdo = get_database_connection();
    $sql = 'SELECT
                d.id,
                d.public_discount_id,
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
            WHERE LOWER(d.discount_type) = "bundle"';

    if ($activeOnly) {
        $sql .= ' AND LOWER(d.status) = "active"
                  AND (d.start_date IS NULL OR d.start_date <= NOW())
                  AND (d.end_date IS NULL OR d.end_date >= NOW())';
    }

    $sql .= ' ORDER BY d.priority ASC, d.id ASC';

    $statement = $pdo->query($sql);
    $discounts = $statement->fetchAll() ?: [];

    foreach ($discounts as &$discount) {
        $valueType = strtolower((string) ($discount['value_type'] ?? 'percentage'));
        $value = (float) ($discount['value'] ?? 0);
        $discount['display_label'] = $valueType === 'fixed'
            ? number_format($value) . ' MMK'
            : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%';
    }
    unset($discount);

    return $discounts;
}

function bundle_fetch_discount_row(int $discountId): ?array
{
    if ($discountId <= 0) {
        return null;
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT
            d.id,
            d.public_discount_id,
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
         WHERE d.id = :discount_id
           AND LOWER(d.discount_type) = "bundle"
         LIMIT 1'
    );
    $statement->execute([':discount_id' => $discountId]);
    $discount = $statement->fetch();

    return $discount ?: null;
}

function bundle_fetch_rows(array $filters = []): array
{
    $pdo = get_database_connection();
    $sql = 'SELECT
                bu.id,
                bu.discount_id,
                bu.bundle_name,
                bu.created_at,
                d.public_discount_id,
                d.name AS discount_name,
                d.value_type,
                d.value,
                d.valid_user,
                d.priority,
                d.start_date,
                d.end_date,
                d.status
            FROM bundles bu
            INNER JOIN discounts d ON d.id = bu.discount_id
            ORDER BY bu.id DESC';

    $statement = $pdo->query($sql);
    $bundles = $statement->fetchAll() ?: [];

    $query = strtolower(trim((string) ($filters['q'] ?? '')));
    if ($query === '') {
        return $bundles;
    }

    return array_values(array_filter($bundles, static function (array $bundle) use ($query): bool {
        $publicId = strtolower(bundle_build_public_id($bundle));
        return str_contains($publicId, $query)
            || str_contains(strtolower((string) ($bundle['bundle_name'] ?? '')), $query)
            || str_contains(strtolower((string) ($bundle['discount_name'] ?? '')), $query)
            || str_contains(strtolower((string) ($bundle['public_discount_id'] ?? '')), $query);
    }));
}

function bundle_fetch_item_rows(array $bundleIds): array
{
    $bundleIds = array_values(array_unique(array_filter(array_map('intval', $bundleIds), static fn (int $id): bool => $id > 0)));
    if ($bundleIds === []) {
        return [];
    }

    $pdo = get_database_connection();
    $placeholders = implode(', ', array_fill(0, count($bundleIds), '?'));
    $statement = $pdo->prepare(
        'SELECT
            bi.bundle_id,
            bi.product_id,
            bi.qty,
            p.name,
            p.description,
            p.price,
            p.stock_quantity,
            p.visibility,
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
         FROM bundle_items bi
         INNER JOIN products p ON p.product_id = bi.product_id
         INNER JOIN brands b ON b.brand_id = p.brand_id
         INNER JOIN categories c ON c.category_id = p.category_id
         LEFT JOIN sub_categories sc ON sc.sub_category_id = p.sub_category_id
         WHERE bi.bundle_id IN (' . $placeholders . ')
         ORDER BY bi.bundle_id ASC, bi.id ASC'
    );
    $statement->execute($bundleIds);

    $grouped = [];
    foreach ($statement->fetchAll() ?: [] as $row) {
        $bundleId = (int) $row['bundle_id'];
        $row['qty'] = max(1, (int) ($row['qty'] ?? 1));
        $row['price'] = (float) ($row['price'] ?? 0);
        $row['stock_quantity'] = (int) ($row['stock_quantity'] ?? 0);
        $row['visibility'] = (int) ($row['visibility'] ?? 0);
        $row['image_url'] = catalog_public_file_url($row['image_file'] ?? null);
        $row['detail_url'] = app_path('/product-details.php?id=' . (int) $row['product_id']);
        $grouped[$bundleId][] = $row;
    }

    return $grouped;
}

function bundle_hydrate_admin_rows(array $bundles): array
{
    if ($bundles === []) {
        return [];
    }

    $itemsByBundle = bundle_fetch_item_rows(array_column($bundles, 'id'));

    foreach ($bundles as &$bundle) {
        $bundleId = (int) $bundle['id'];
        $items = $itemsByBundle[$bundleId] ?? [];
        $bundle['public_bundle_id'] = bundle_build_public_id($bundle);
        $bundle['products'] = count($items);
        $bundle['qty'] = array_sum(array_map(static fn (array $item): int => (int) $item['qty'], $items));
        $bundle['created_at_display'] = bundle_format_datetime((string) ($bundle['created_at'] ?? ''));
        $bundle['discount_display'] = (string) ($bundle['discount_name'] ?? '');
        $bundle['items'] = $items;
        $bundle['items_map'] = [];
        foreach ($items as $item) {
            $bundle['items_map'][(int) $item['product_id']] = (int) $item['qty'];
        }
    }
    unset($bundle);

    return $bundles;
}

function bundle_fetch_admin_bundles(array $filters = []): array
{
    return bundle_hydrate_admin_rows(bundle_fetch_rows($filters));
}

function bundle_fetch_admin_bundle(int $bundleId): ?array
{
    $bundles = bundle_hydrate_admin_rows(array_values(array_filter(
        bundle_fetch_rows(),
        static fn (array $bundle): bool => (int) ($bundle['id'] ?? 0) === $bundleId
    )));

    return $bundles[0] ?? null;
}

function bundle_validate_items(array $input): array
{
    $rawItems = (array) ($input['items'] ?? []);
    $items = [];

    foreach ($rawItems as $productId => $qty) {
        $productId = (int) $productId;
        $qty = (int) $qty;
        if ($productId <= 0 || $qty <= 0) {
            continue;
        }

        $items[$productId] = $qty;
    }

    if ($items === []) {
        throw new InvalidArgumentException('Select at least one product for the bundle.');
    }

    $availableProducts = [];
    foreach (catalog_fetch_admin_products() as $product) {
        $availableProducts[(int) $product['product_id']] = true;
    }

    foreach (array_keys($items) as $productId) {
        if (!isset($availableProducts[$productId])) {
            throw new InvalidArgumentException('One or more selected products are invalid.');
        }
    }

    return $items;
}

function bundle_validate_admin_payload(array $input, int $bundleId = 0): array
{
    $bundleName = trim((string) ($input['bundle_name'] ?? ''));
    $discountId = (int) ($input['discount_id'] ?? 0);

    if ($bundleName === '') {
        throw new InvalidArgumentException('Bundle name is required.');
    }

    $discount = bundle_fetch_discount_row($discountId);
    if (!$discount) {
        throw new InvalidArgumentException('Please select a valid bundle discount.');
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT id
         FROM bundles
         WHERE discount_id = :discount_id
           AND id <> :bundle_id
         LIMIT 1'
    );
    $statement->execute([
        ':discount_id' => $discountId,
        ':bundle_id' => $bundleId,
    ]);
    if ($statement->fetch()) {
        throw new InvalidArgumentException('That bundle discount is already linked to another bundle.');
    }

    return [
        'bundle_name' => $bundleName,
        'discount_id' => $discountId,
        'items' => bundle_validate_items($input),
    ];
}

function bundle_save_admin(array $input): int
{
    $bundleId = (int) ($input['bundle_id'] ?? 0);
    $validated = bundle_validate_admin_payload($input, $bundleId);
    $pdo = get_database_connection();
    $pdo->beginTransaction();

    try {
        if ($bundleId > 0) {
            $statement = $pdo->prepare(
                'UPDATE bundles
                 SET discount_id = :discount_id,
                     bundle_name = :bundle_name
                 WHERE id = :bundle_id'
            );
            $statement->execute([
                ':discount_id' => $validated['discount_id'],
                ':bundle_name' => $validated['bundle_name'],
                ':bundle_id' => $bundleId,
            ]);

            $pdo->prepare('DELETE FROM bundle_items WHERE bundle_id = :bundle_id')
                ->execute([':bundle_id' => $bundleId]);
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO bundles (discount_id, bundle_name)
                 VALUES (:discount_id, :bundle_name)'
            );
            $statement->execute([
                ':discount_id' => $validated['discount_id'],
                ':bundle_name' => $validated['bundle_name'],
            ]);
            $bundleId = (int) $pdo->lastInsertId();
        }

        $insertItem = $pdo->prepare(
            'INSERT INTO bundle_items (bundle_id, product_id, qty)
             VALUES (:bundle_id, :product_id, :qty)'
        );
        foreach ($validated['items'] as $productId => $qty) {
            $insertItem->execute([
                ':bundle_id' => $bundleId,
                ':product_id' => $productId,
                ':qty' => $qty,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    return $bundleId;
}

function bundle_delete_admin(int $bundleId): void
{
    if ($bundleId <= 0) {
        throw new InvalidArgumentException('Invalid bundle selected.');
    }

    $pdo = get_database_connection();
    $pdo->beginTransaction();

    try {
        $pdo->prepare('DELETE FROM bundle_items WHERE bundle_id = :bundle_id')
            ->execute([':bundle_id' => $bundleId]);
        $pdo->prepare('DELETE FROM bundles WHERE id = :bundle_id')
            ->execute([':bundle_id' => $bundleId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function bundle_hydrate_customer_rows(array $bundles, ?int $userId = null): array
{
    if ($bundles === []) {
        return [];
    }

    $itemsByBundle = bundle_fetch_item_rows(array_column($bundles, 'id'));
    $userContext = catalog_fetch_discount_user_context($userId);
    $result = [];

    foreach ($bundles as $bundle) {
        if (!bundle_discount_is_active($bundle)) {
            continue;
        }

        if (!catalog_discount_is_valid_for_user($bundle, $userContext)) {
            continue;
        }

        $bundleId = (int) $bundle['id'];
        $items = $itemsByBundle[$bundleId] ?? [];
        if ($items === []) {
            continue;
        }

        $visibleItems = array_values(array_filter($items, static fn (array $item): bool => (int) ($item['visibility'] ?? 0) === 1));
        if (count($visibleItems) !== count($items)) {
            continue;
        }

        $subtotal = 0.0;
        $productCount = 0;
        $itemQty = 0;
        $bundleStock = null;

        foreach ($visibleItems as &$item) {
            $quantity = max(1, (int) $item['qty']);
            $lineSubtotal = (float) $item['price'] * $quantity;
            $item['line_subtotal_value'] = $lineSubtotal;
            $item['line_subtotal_label'] = number_format($lineSubtotal) . ' MMK';
            $item['unit_price_label'] = number_format((float) $item['price']) . ' MMK';
            $subtotal += $lineSubtotal;
            $productCount += 1;
            $itemQty += $quantity;

            $availableBundles = $quantity > 0 ? intdiv(max(0, (int) $item['stock_quantity']), $quantity) : 0;
            $bundleStock = $bundleStock === null ? $availableBundles : min($bundleStock, $availableBundles);
        }
        unset($item);

        $discountAmount = bundle_calculate_discount_amount($subtotal, $bundle);
        $finalTotal = max($subtotal - $discountAmount, 0);

        $bundle['public_bundle_id'] = bundle_build_public_id($bundle);
        $bundle['detail_url'] = app_path('/bundle-details.php?id=' . $bundleId);
        $bundle['items'] = $visibleItems;
        $bundle['product_count'] = $productCount;
        $bundle['item_qty'] = $itemQty;
        $bundle['subtotal_value'] = $subtotal;
        $bundle['discount_amount_value'] = $discountAmount;
        $bundle['final_total_value'] = $finalTotal;
        $bundle['subtotal_label'] = number_format($subtotal) . ' MMK';
        $bundle['discount_amount_label'] = number_format($discountAmount) . ' MMK';
        $bundle['final_total_label'] = number_format($finalTotal) . ' MMK';
        $bundle['discount_badge'] = trim((string) ($bundle['discount_name'] ?? ''));
        $bundle['discount_value_label'] = strtolower((string) ($bundle['value_type'] ?? 'percentage')) === 'fixed'
            ? number_format((float) ($bundle['value'] ?? 0)) . ' MMK OFF'
            : rtrim(rtrim(number_format((float) ($bundle['value'] ?? 0), 2, '.', ''), '0'), '.') . '% OFF';
        $bundle['image_url'] = $visibleItems[0]['image_url'];
        $bundle['availability_count'] = max(0, (int) $bundleStock);
        $bundle['availability_label'] = $bundle['availability_count'] > 0 ? 'Available' : 'Out of Stock';
        $bundle['availability_note'] = $bundle['availability_count'] > 0
            ? ($bundle['availability_count'] <= 5 ? 'Only ' . $bundle['availability_count'] . ' bundle(s) available' : 'Ready to order')
            : 'Currently unavailable';

        $result[] = $bundle;
    }

    return $result;
}

function bundle_fetch_customer_bundles(?int $userId = null, int $limit = 0): array
{
    $bundles = bundle_hydrate_customer_rows(bundle_fetch_rows(), $userId);
    if ($limit > 0 && count($bundles) > $limit) {
        return array_slice($bundles, 0, $limit);
    }

    return $bundles;
}

function bundle_fetch_customer_bundle(int $bundleId, ?int $userId = null): ?array
{
    $bundles = array_values(array_filter(
        bundle_fetch_customer_bundles($userId),
        static fn (array $bundle): bool => (int) ($bundle['id'] ?? 0) === $bundleId
    ));

    return $bundles[0] ?? null;
}
