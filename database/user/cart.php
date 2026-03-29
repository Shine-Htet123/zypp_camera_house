<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../catalog.php';

function cart_fetch_cart_record(int $userId): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('SELECT cart_id, user_id FROM carts WHERE user_id = :user_id LIMIT 1');
    $statement->execute([':user_id' => $userId]);
    $row = $statement->fetch();

    return $row ?: null;
}

function cart_get_or_create_cart_id(int $userId): int
{
    $existing = cart_fetch_cart_record($userId);
    if ($existing) {
        return (int) $existing['cart_id'];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare('INSERT INTO carts (user_id) VALUES (:user_id)');
    $statement->execute([':user_id' => $userId]);

    return (int) $pdo->lastInsertId();
}

function cart_fetch_product_for_cart(int $productId): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT
            p.product_id,
            p.name,
            p.price,
            p.stock_quantity,
            p.visibility,
            p.is_featured,
            p.category_id,
            p.brand_id,
            p.sub_category_id,
            b.name AS brand_name,
            c.name AS category_name,
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
         WHERE p.product_id = :product_id
         LIMIT 1'
    );
    $statement->execute([':product_id' => $productId]);
    $row = $statement->fetch();

    return $row ?: null;
}

function cart_fetch_user_discount_context(int $userId): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT id, membership_tier_id
         FROM users
         WHERE id = :user_id
         LIMIT 1'
    );
    $statement->execute([':user_id' => $userId]);
    $row = $statement->fetch();

    return [
        'user_id' => (int) ($row['id'] ?? $userId),
        'membership_tier_id' => $row && $row['membership_tier_id'] !== null ? (int) $row['membership_tier_id'] : null,
        'standard_tier_ids' => cart_fetch_standard_discount_tier_ids(),
    ];
}

function cart_discount_parse_valid_users(?string $value): array
{
    $value = trim((string) $value);
    if ($value === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== ''));
}

function cart_discount_is_valid_for_user(array $discount, array $userContext): bool
{
    $validUsers = cart_discount_parse_valid_users($discount['valid_user'] ?? null);
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

function cart_calculate_unit_discount_amount(float $unitPrice, array $discount): float
{
    $valueType = strtolower((string) ($discount['value_type'] ?? 'percentage'));
    $value = (float) ($discount['value'] ?? 0);

    if ($valueType === 'fixed') {
        return max(min($value, $unitPrice), 0);
    }

    return max(min($unitPrice * ($value / 100), $unitPrice), 0);
}

function cart_fetch_applicable_discount_for_product(int $userId, array $product): ?array
{
    $pdo = get_database_connection();
    $userContext = cart_fetch_user_discount_context($userId);

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
        $statement->bindValue(':sub_category_id', null, PDO::PARAM_NULL);
    }
    $statement->bindValue(':brand_id', (int) $product['brand_id'], PDO::PARAM_INT);
    $statement->execute();

    $applicable = [];
    foreach ($statement->fetchAll() as $discount) {
        if (!cart_discount_is_valid_for_user($discount, $userContext)) {
            continue;
        }

        $discount['unit_discount_amount'] = cart_calculate_unit_discount_amount((float) ($product['price'] ?? $product['unit_price'] ?? 0), $discount);
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

        return ((int) ($left['id'] ?? 0)) <=> ((int) ($right['id'] ?? 0));
    });

    return $applicable[0];
}

function cart_fetch_standard_discount_tier_ids(): array
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

function cart_fetch_item_record(int $cartId, int $productId): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT cart_items_id, cart_id, product_id, quantity
         FROM cart_items
         WHERE cart_id = :cart_id AND product_id = :product_id
         LIMIT 1'
    );
    $statement->execute([
        ':cart_id' => $cartId,
        ':product_id' => $productId,
    ]);
    $row = $statement->fetch();

    return $row ?: null;
}

function cart_count_items_for_user(int $userId): int
{
    $cart = cart_fetch_cart_record($userId);
    if (!$cart) {
        return 0;
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = :cart_id');
    $statement->execute([':cart_id' => (int) $cart['cart_id']]);

    return (int) $statement->fetchColumn();
}

function cart_add_item(int $userId, int $productId, int $quantity): array
{
    if ($userId <= 0) {
        throw new InvalidArgumentException('Invalid user id.');
    }

    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid product id.');
    }

    $product = cart_fetch_product_for_cart($productId);
    if (!$product) {
        throw new RuntimeException('Product not found.');
    }

    if (!(bool) $product['visibility']) {
        throw new RuntimeException('This product is not available.');
    }

    $stock = max(0, (int) $product['stock_quantity']);
    if ($stock <= 0) {
        throw new RuntimeException('This product is out of stock.');
    }

    $quantity = max(1, $quantity);
    $cartId = cart_get_or_create_cart_id($userId);
    $existing = cart_fetch_item_record($cartId, $productId);
    $nextQuantity = min($stock, $quantity + (int) ($existing['quantity'] ?? 0));

    $pdo = get_database_connection();
    if ($existing) {
        $statement = $pdo->prepare(
            'UPDATE cart_items
             SET quantity = :quantity
             WHERE cart_items_id = :cart_items_id'
        );
        $statement->execute([
            ':quantity' => $nextQuantity,
            ':cart_items_id' => (int) $existing['cart_items_id'],
        ]);
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO cart_items (cart_id, product_id, quantity)
             VALUES (:cart_id, :product_id, :quantity)'
        );
        $statement->execute([
            ':cart_id' => $cartId,
            ':product_id' => $productId,
            ':quantity' => $nextQuantity,
        ]);
    }

    return cart_fetch_product_for_cart($productId) ?: [];
}

function cart_update_item_quantity(int $userId, int $productId, int $quantity): void
{
    $cart = cart_fetch_cart_record($userId);
    if (!$cart) {
        throw new RuntimeException('Cart not found.');
    }

    $item = cart_fetch_item_record((int) $cart['cart_id'], $productId);
    if (!$item) {
        throw new RuntimeException('Cart item not found.');
    }

    if ($quantity <= 0) {
        cart_remove_item($userId, $productId);
        return;
    }

    $product = cart_fetch_product_for_cart($productId);
    if (!$product) {
        throw new RuntimeException('Product not found.');
    }

    $stock = max(1, (int) $product['stock_quantity']);
    $quantity = min(max(1, $quantity), $stock);

    $pdo = get_database_connection();
    $statement = $pdo->prepare('UPDATE cart_items SET quantity = :quantity WHERE cart_items_id = :cart_items_id');
    $statement->execute([
        ':quantity' => $quantity,
        ':cart_items_id' => (int) $item['cart_items_id'],
    ]);
}

function cart_remove_item(int $userId, int $productId): void
{
    $cart = cart_fetch_cart_record($userId);
    if (!$cart) {
        return;
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id');
    $statement->execute([
        ':cart_id' => (int) $cart['cart_id'],
        ':product_id' => $productId,
    ]);
}

function cart_fetch_items_for_user(int $userId): array
{
    $cart = cart_fetch_cart_record($userId);
    if (!$cart) {
        return [];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT
            ci.cart_items_id,
            ci.product_id,
            ci.quantity,
            p.name,
            p.price AS unit_price,
            p.stock_quantity,
            p.is_featured,
            p.category_id,
            p.brand_id,
            p.sub_category_id,
            (
                SELECT pi.image_file
                FROM product_images pi
                WHERE pi.product_id = p.product_id
                ORDER BY pi.is_primary DESC, pi.image_id ASC
                LIMIT 1
            ) AS image_file
         FROM cart_items ci
         INNER JOIN products p ON p.product_id = ci.product_id
         WHERE ci.cart_id = :cart_id
         ORDER BY ci.cart_items_id ASC'
    );
    $statement->execute([':cart_id' => (int) $cart['cart_id']]);
    $items = $statement->fetchAll();

    foreach ($items as &$item) {
        $item['image'] = catalog_public_file_url($item['image_file']);
        $item['stock'] = max(0, (int) $item['stock_quantity']);
        $item['quantity'] = min(max(1, (int) $item['quantity']), max(1, (int) $item['stock_quantity']));
        $discount = cart_fetch_applicable_discount_for_product($userId, $item);
        $item['discount_value'] = $discount ? (float) $discount['value'] : 0;
        $item['discount_type'] = $discount ? strtolower((string) $discount['value_type']) : 'percentage';
    }

    return $items;
}
