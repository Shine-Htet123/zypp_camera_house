<?php

require_once __DIR__ . '/../../config/database.php';

function admin_fetch_delivery_methods(): array
{
    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT delivery_method_id, name, supports_free_shipping, free_shipping_threshold, is_active, created_at
         FROM delivery_methods
         ORDER BY delivery_method_id ASC'
    );

    $methods = $statement->fetchAll() ?: [];
    foreach ($methods as &$method) {
        $method['supports_free_shipping'] = (bool) $method['supports_free_shipping'];
        $method['is_active'] = (bool) $method['is_active'];
        $method['free_shipping_threshold_label'] = $method['free_shipping_threshold'] !== null
            ? number_format((float) $method['free_shipping_threshold']) . ' MMK'
            : '';
    }

    return $methods;
}

function admin_delivery_method_validate(array $input, ?int $currentId = null): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $supportsFreeShipping = !empty($input['supports_free_shipping']);
    $thresholdRaw = str_replace(',', '', trim((string) ($input['free_shipping_threshold'] ?? '')));
    $isActive = !array_key_exists('is_active', $input) || !empty($input['is_active']);

    if ($name === '') {
        throw new InvalidArgumentException('Delivery method name is required.');
    }

    $pdo = get_database_connection();
    if ($currentId !== null && $currentId > 0) {
        $statement = $pdo->prepare(
            'SELECT delivery_method_id
             FROM delivery_methods
             WHERE LOWER(name) = LOWER(:name)
               AND delivery_method_id <> :delivery_method_id
             LIMIT 1'
        );
        $statement->execute([
            ':name' => $name,
            ':delivery_method_id' => $currentId,
        ]);
    } else {
        $statement = $pdo->prepare(
            'SELECT delivery_method_id
             FROM delivery_methods
             WHERE LOWER(name) = LOWER(:name)
             LIMIT 1'
        );
        $statement->execute([':name' => $name]);
    }

    if ($statement->fetch()) {
        throw new InvalidArgumentException('A delivery method with this name already exists.');
    }

    $threshold = null;
    if ($thresholdRaw !== '') {
        if (!is_numeric($thresholdRaw) || (float) $thresholdRaw < 0) {
            throw new InvalidArgumentException('Free shipping threshold must be a valid non-negative number.');
        }
        $threshold = (float) $thresholdRaw;
    }

    if (!$supportsFreeShipping) {
        $threshold = null;
    }

    return [
        'name' => $name,
        'supports_free_shipping' => $supportsFreeShipping ? 1 : 0,
        'free_shipping_threshold' => $threshold,
        'is_active' => $isActive ? 1 : 0,
    ];
}

function admin_delivery_method_save(array $input): int
{
    $deliveryMethodId = isset($input['delivery_method_id']) ? (int) $input['delivery_method_id'] : 0;
    $validated = admin_delivery_method_validate($input, $deliveryMethodId > 0 ? $deliveryMethodId : null);
    $pdo = get_database_connection();

    if ($deliveryMethodId > 0) {
        $statement = $pdo->prepare(
            'UPDATE delivery_methods
             SET name = :name,
                 supports_free_shipping = :supports_free_shipping,
                 free_shipping_threshold = :free_shipping_threshold,
                 is_active = :is_active
             WHERE delivery_method_id = :delivery_method_id'
        );
        $statement->execute([
            ':name' => $validated['name'],
            ':supports_free_shipping' => $validated['supports_free_shipping'],
            ':free_shipping_threshold' => $validated['free_shipping_threshold'],
            ':is_active' => $validated['is_active'],
            ':delivery_method_id' => $deliveryMethodId,
        ]);

        return $deliveryMethodId;
    }

    $statement = $pdo->prepare(
        'INSERT INTO delivery_methods (name, supports_free_shipping, free_shipping_threshold, is_active)
         VALUES (:name, :supports_free_shipping, :free_shipping_threshold, :is_active)'
    );
    $statement->execute([
        ':name' => $validated['name'],
        ':supports_free_shipping' => $validated['supports_free_shipping'],
        ':free_shipping_threshold' => $validated['free_shipping_threshold'],
        ':is_active' => $validated['is_active'],
    ]);

    return (int) $pdo->lastInsertId();
}

function admin_delivery_method_delete(int $deliveryMethodId): void
{
    if ($deliveryMethodId <= 0) {
        throw new InvalidArgumentException('Delivery method not found.');
    }

    $pdo = get_database_connection();
    $ordersCount = 0;
    if (admin_delivery_method_table_exists('orders')) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM orders WHERE delivery_method_id = :delivery_method_id'
        );
        $statement->execute([':delivery_method_id' => $deliveryMethodId]);
        $ordersCount = (int) $statement->fetchColumn();
    }

    if ($ordersCount > 0) {
        throw new RuntimeException('This delivery method is already used by orders. Set it inactive instead of deleting it.');
    }

    $statement = $pdo->prepare(
        'DELETE FROM delivery_methods WHERE delivery_method_id = :delivery_method_id'
    );
    $statement->execute([':delivery_method_id' => $deliveryMethodId]);

    if ($statement->rowCount() === 0) {
        throw new RuntimeException('Delivery method not found.');
    }
}

function admin_delivery_method_table_exists(string $table): bool
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('SHOW TABLES LIKE :table_name');
    $statement->execute([':table_name' => $table]);

    return (bool) $statement->fetchColumn();
}
