<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/cart.php';

function checkout_table_exists(string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
         LIMIT 1'
    );
    $statement->execute([':table_name' => $table]);
    $cache[$table] = (bool) $statement->fetchColumn();

    return $cache[$table];
}

function checkout_column_exists(string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name
         LIMIT 1'
    );
    $statement->execute([
        ':table_name' => $table,
        ':column_name' => $column,
    ]);

    $cache[$key] = (bool) $statement->fetchColumn();

    return $cache[$key];
}

function checkout_fetch_delivery_methods(): array
{
    if (!checkout_table_exists('delivery_methods')) {
        return [];
    }

    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT delivery_method_id, name, supports_free_shipping, free_shipping_threshold
         FROM delivery_methods
         WHERE is_active = 1
         ORDER BY delivery_method_id ASC'
    );

    return $statement->fetchAll() ?: [];
}

function checkout_find_delivery_method(int $deliveryMethodId): ?array
{
    if ($deliveryMethodId <= 0 || !checkout_table_exists('delivery_methods')) {
        return null;
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT delivery_method_id, name, supports_free_shipping, free_shipping_threshold
         FROM delivery_methods
         WHERE delivery_method_id = :delivery_method_id
           AND is_active = 1
         LIMIT 1'
    );
    $statement->execute([':delivery_method_id' => $deliveryMethodId]);

    $row = $statement->fetch();
    return $row ?: null;
}

function checkout_order_statuses_that_count(): array
{
    return ['pending', 'confirmed', 'shipped', 'delivered'];
}

function checkout_generate_public_order_id(): string
{
    $pdo = get_database_connection();
    $datePrefix = date('Ymd');
    $like = $datePrefix . '%';

    $statement = $pdo->prepare(
        'SELECT public_order_id
         FROM orders
         WHERE public_order_id LIKE :prefix
         ORDER BY id DESC
         LIMIT 1'
    );
    $statement->execute([':prefix' => $like]);
    $last = (string) $statement->fetchColumn();

    $nextSequence = 1;
    if ($last !== '' && preg_match('/(\d{4})$/', $last, $matches)) {
        $nextSequence = ((int) $matches[1]) + 1;
    }

    return $datePrefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
}

function checkout_generate_public_payment_id(): string
{
    $pdo = get_database_connection();
    $datePrefix = 'PAY' . date('Ymd');
    $like = $datePrefix . '%';

    $statement = $pdo->prepare(
        'SELECT public_payment_id
         FROM payments
         WHERE public_payment_id LIKE :prefix
         ORDER BY payment_id DESC
         LIMIT 1'
    );
    $statement->execute([':prefix' => $like]);
    $last = (string) $statement->fetchColumn();

    $nextSequence = 1;
    if ($last !== '' && preg_match('/(\d{4})$/', $last, $matches)) {
        $nextSequence = ((int) $matches[1]) + 1;
    }

    return $datePrefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
}

function checkout_store_payment_proof(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Payment proof upload failed.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Payment proof upload failed.');
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Payment proof must be an image.');
    }

    $directory = app_project_path('storage/uploads/payment-proofs');
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Failed to prepare payment proof storage.');
    }

    $filename = 'payment-proof-' . date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.' . $extension;
    $destination = $directory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Failed to store payment proof.');
    }

    return '/storage/uploads/payment-proofs/' . $filename;
}

function checkout_delete_payment_proof(?string $path): void
{
    $path = trim((string) $path);
    if ($path === '' || !str_starts_with($path, '/storage/uploads/payment-proofs/')) {
        return;
    }

    $absolute = app_project_path(ltrim($path, '/'));
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

function checkout_fetch_default_address(int $userId): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT address_id, city, township, street, phone, postal_code, is_default
         FROM user_addresses
         WHERE user_id = :user_id
         ORDER BY is_default DESC, created_at ASC, address_id ASC
         LIMIT 1'
    );
    $statement->execute([':user_id' => $userId]);

    $row = $statement->fetch();
    return $row ?: null;
}

function checkout_find_matching_address(PDO $pdo, int $userId, array $payload): ?array
{
    $statement = $pdo->prepare(
        'SELECT address_id, is_default
         FROM user_addresses
         WHERE user_id = :user_id
           AND city = :city
           AND township = :township
           AND street = :street
           AND phone = :phone
           AND COALESCE(postal_code, "") = COALESCE(:postal_code, "")
         LIMIT 1'
    );
    $statement->execute([
        ':user_id' => $userId,
        ':city' => $payload['city'],
        ':township' => $payload['township'],
        ':street' => $payload['address'],
        ':phone' => $payload['phone'],
        ':postal_code' => $payload['postal_code'] !== '' ? $payload['postal_code'] : null,
    ]);

    $row = $statement->fetch();
    return $row ?: null;
}

function checkout_count_addresses(PDO $pdo, int $userId): int
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id');
    $statement->execute([':user_id' => $userId]);

    return (int) $statement->fetchColumn();
}

function checkout_insert_or_update_address(PDO $pdo, int $userId, array $delivery): void
{
    if (($delivery['delivery_type'] ?? 'delivery') !== 'delivery') {
        return;
    }

    $address = trim((string) ($delivery['address'] ?? ''));
    $city = trim((string) ($delivery['city'] ?? ''));
    $township = trim((string) ($delivery['township'] ?? ''));
    $phone = trim((string) ($delivery['phone'] ?? ''));
    if ($address === '' || $city === '' || $township === '' || $phone === '') {
        return;
    }

    $match = checkout_find_matching_address($pdo, $userId, $delivery);
    $shouldBeDefault = !empty($delivery['set_default']);

    if ($match) {
        if ($shouldBeDefault) {
            $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id')
                ->execute([':user_id' => $userId]);
            $pdo->prepare(
                'UPDATE user_addresses
                 SET is_default = 1
                 WHERE user_id = :user_id AND address_id = :address_id'
            )->execute([
                ':user_id' => $userId,
                ':address_id' => (int) $match['address_id'],
            ]);
        }
        return;
    }

    $isDefault = $shouldBeDefault || checkout_count_addresses($pdo, $userId) === 0;
    if ($isDefault) {
        $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id')
            ->execute([':user_id' => $userId]);
    }

    $statement = $pdo->prepare(
        'INSERT INTO user_addresses (user_id, city, township, street, phone, postal_code, is_default)
         VALUES (:user_id, :city, :township, :street, :phone, :postal_code, :is_default)'
    );
    $statement->execute([
        ':user_id' => $userId,
        ':city' => $delivery['city'],
        ':township' => $delivery['township'],
        ':street' => $delivery['address'],
        ':phone' => $delivery['phone'],
        ':postal_code' => $delivery['postal_code'] !== '' ? $delivery['postal_code'] : null,
        ':is_default' => $isDefault ? 1 : 0,
    ]);
}

function checkout_insert_order_delivery_detail(PDO $pdo, int $orderId, array $draft): void
{
    if (!checkout_table_exists('order_delivery_details')) {
        throw new RuntimeException('order_delivery_details table is required. Run the checkout migration first.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO order_delivery_details (
            order_id, first_name, last_name, company, phone, email,
            address, state, city, township, postal_code, delivery_type, additional_note
         ) VALUES (
            :order_id, :first_name, :last_name, :company, :phone, :email,
            :address, :state, :city, :township, :postal_code, :delivery_type, :additional_note
         )'
    );
    $statement->execute([
        ':order_id' => $orderId,
        ':first_name' => $draft['first_name'],
        ':last_name' => $draft['last_name'],
        ':company' => $draft['company'] !== '' ? $draft['company'] : null,
        ':phone' => $draft['phone'],
        ':email' => $draft['email'],
        ':address' => $draft['address'] !== '' ? $draft['address'] : null,
        ':state' => $draft['state'] !== '' ? $draft['state'] : null,
        ':city' => $draft['city'] !== '' ? $draft['city'] : null,
        ':township' => $draft['township'] !== '' ? $draft['township'] : null,
        ':postal_code' => $draft['postal_code'] !== '' ? $draft['postal_code'] : null,
        ':delivery_type' => $draft['delivery_type'],
        ':additional_note' => trim((string) ($draft['additional_note'] ?? '')) !== '' ? trim((string) $draft['additional_note']) : null,
    ]);
}

function checkout_insert_payment(PDO $pdo, int $orderId, array $payment): array
{
    $hasPublicPaymentId = checkout_column_exists('payments', 'public_payment_id');
    $hasPaymentProofFile = checkout_column_exists('payments', 'payment_proof_file');
    $hasSubmittedAt = checkout_column_exists('payments', 'submitted_at');

    $columns = ['order_id', 'payment_method', 'payment_status'];
    $placeholders = [':order_id', ':payment_method', ':payment_status'];
    $params = [
        ':order_id' => $orderId,
        ':payment_method' => $payment['payment_method'],
        ':payment_status' => $payment['payment_status'],
    ];

    $publicPaymentId = null;
    if ($hasPublicPaymentId) {
        $publicPaymentId = checkout_generate_public_payment_id();
        $columns[] = 'public_payment_id';
        $placeholders[] = ':public_payment_id';
        $params[':public_payment_id'] = $publicPaymentId;
    }

    if ($hasPaymentProofFile) {
        $columns[] = 'payment_proof_file';
        $placeholders[] = ':payment_proof_file';
        $params[':payment_proof_file'] = $payment['payment_proof_file'] !== '' ? $payment['payment_proof_file'] : null;
    }

    if ($hasSubmittedAt) {
        $columns[] = 'submitted_at';
        $placeholders[] = ':submitted_at';
        $params[':submitted_at'] = $payment['submitted_at'];
    }

    $sql = sprintf(
        'INSERT INTO payments (%s) VALUES (%s)',
        implode(', ', $columns),
        implode(', ', $placeholders)
    );

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return [
        'payment_id' => (int) $pdo->lastInsertId(),
        'public_payment_id' => $publicPaymentId,
    ];
}

function checkout_fetch_order_confirmation(int $userId, ?string $publicOrderId = null): ?array
{
    $pdo = get_database_connection();
    $hasDeliveryDetails = checkout_table_exists('order_delivery_details');
    $hasPublicPaymentId = checkout_column_exists('payments', 'public_payment_id');
    $hasPaymentProofFile = checkout_column_exists('payments', 'payment_proof_file');
    $hasReuploadRequested = checkout_column_exists('payments', 'reupload_requested');
    $hasReuploadRequestedAt = checkout_column_exists('payments', 'reupload_requested_at');

    $sql = 'SELECT
                o.id,
                o.public_order_id,
                o.status,
                o.subtotal,
                o.total_discount,
                o.grand_total,
                o.payment_status,
                o.created_at,
                dm.name AS delivery_method_name,
                ' . ($hasDeliveryDetails ? 'odd.first_name' : 'NULL AS first_name') . ',
                ' . ($hasDeliveryDetails ? 'odd.last_name' : 'NULL AS last_name') . ',
                ' . ($hasDeliveryDetails ? 'odd.company' : 'NULL AS company') . ',
                ' . ($hasDeliveryDetails ? 'odd.phone' : 'NULL AS phone') . ',
                ' . ($hasDeliveryDetails ? 'odd.email' : 'NULL AS email') . ',
                ' . ($hasDeliveryDetails ? 'odd.address' : 'NULL AS address') . ',
                ' . ($hasDeliveryDetails ? 'odd.state' : 'NULL AS state') . ',
                ' . ($hasDeliveryDetails ? 'odd.city' : 'NULL AS city') . ',
                ' . ($hasDeliveryDetails ? 'odd.township' : 'NULL AS township') . ',
                ' . ($hasDeliveryDetails ? 'odd.postal_code' : 'NULL AS postal_code') . ',
                ' . ($hasDeliveryDetails ? 'odd.delivery_type' : '\'delivery\' AS delivery_type') . ',
                ' . ($hasDeliveryDetails ? 'odd.additional_note' : 'NULL AS additional_note') . ',
                p.payment_method,
                p.payment_id,
                p.payment_status AS payment_row_status,
                ' . ($hasPublicPaymentId ? 'p.public_payment_id' : 'NULL AS public_payment_id') . ',
                ' . ($hasPaymentProofFile ? 'p.payment_proof_file' : 'NULL AS payment_proof_file') . ',
                ' . ($hasReuploadRequested ? 'COALESCE(p.reupload_requested, 0)' : '0') . ' AS reupload_requested,
                ' . ($hasReuploadRequestedAt ? 'p.reupload_requested_at' : 'NULL AS reupload_requested_at') . '
            FROM orders o
            LEFT JOIN delivery_methods dm ON dm.delivery_method_id = o.delivery_method_id
            ' . ($hasDeliveryDetails ? 'LEFT JOIN order_delivery_details odd ON odd.order_id = o.id' : '') . '
            LEFT JOIN (
                SELECT p1.*
                FROM payments p1
                INNER JOIN (
                    SELECT order_id, MAX(payment_id) AS latest_payment_id
                    FROM payments
                    GROUP BY order_id
                ) latest_payment
                    ON latest_payment.latest_payment_id = p1.payment_id
            ) p ON p.order_id = o.id
            WHERE o.user_id = :user_id';

    $params = [':user_id' => $userId];
    if ($publicOrderId !== null && $publicOrderId !== '') {
        $sql .= ' AND o.public_order_id = :public_order_id';
        $params[':public_order_id'] = $publicOrderId;
    } else {
        $sql .= ' ORDER BY o.id DESC LIMIT 1';
    }

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $order = $statement->fetch();
    if (!$order) {
        return null;
    }

    $itemsStatement = $pdo->prepare(
        'SELECT oi.order_item_id, oi.qty, oi.price, oi.discount_amount, oi.final_price, p.name
         FROM order_items oi
         INNER JOIN products p ON p.product_id = oi.product_id
         WHERE oi.order_id = :order_id
         ORDER BY oi.order_item_id ASC'
    );
    $itemsStatement->execute([':order_id' => (int) $order['id']]);
    $order['items'] = $itemsStatement->fetchAll() ?: [];
    $order['reupload_requested'] = !empty($order['reupload_requested']);

    return $order;
}

function checkout_reupload_payment_proof(int $userId, string $publicOrderId, array $proofUpload): array
{
    $publicOrderId = trim($publicOrderId);
    if ($userId <= 0 || $publicOrderId === '') {
        throw new InvalidArgumentException('Invalid order for payment re-upload.');
    }

    if (!checkout_column_exists('payments', 'reupload_requested')) {
        throw new RuntimeException('Payment re-upload columns are required. Run the re-upload migration first.');
    }

    $order = checkout_fetch_order_confirmation($userId, $publicOrderId);
    if (!$order) {
        throw new RuntimeException('Order not found.');
    }

    if ((int) ($order['payment_id'] ?? 0) <= 0) {
        throw new RuntimeException('Payment record not found for this order.');
    }

    if (empty($order['reupload_requested'])) {
        throw new RuntimeException('This order does not currently require payment proof re-upload.');
    }

    $proofPath = checkout_store_payment_proof($proofUpload);
    $pdo = get_database_connection();
    $hasSubmittedAt = checkout_column_exists('payments', 'submitted_at');
    $hasReuploadRequestedAt = checkout_column_exists('payments', 'reupload_requested_at');

    $pdo->beginTransaction();
    try {
        $setParts = [
            'payment_status = :payment_status',
            'payment_proof_file = :payment_proof_file',
            'reupload_requested = 0',
        ];
        $params = [
            ':payment_status' => 'pending',
            ':payment_proof_file' => $proofPath,
            ':payment_id' => (int) $order['payment_id'],
        ];

        if ($hasSubmittedAt) {
            $setParts[] = 'submitted_at = :submitted_at';
            $params[':submitted_at'] = date('Y-m-d H:i:s');
        }

        if ($hasReuploadRequestedAt) {
            $setParts[] = 'reupload_requested_at = NULL';
        }

        $paymentStatement = $pdo->prepare(
            'UPDATE payments
             SET ' . implode(', ', $setParts) . '
             WHERE payment_id = :payment_id'
        );
        $paymentStatement->execute($params);

        $orderStatement = $pdo->prepare(
            'UPDATE orders
             SET payment_status = :payment_status,
                 updated_at = NOW()
             WHERE id = :order_id'
        );
        $orderStatement->execute([
            ':payment_status' => 'pending',
            ':order_id' => (int) $order['id'],
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        checkout_delete_payment_proof($proofPath);
        throw $exception;
    }

    checkout_delete_payment_proof((string) ($order['payment_proof_file'] ?? ''));

    $updatedOrder = checkout_fetch_order_confirmation($userId, $publicOrderId);
    if (!$updatedOrder) {
        throw new RuntimeException('Failed to refresh the updated order.');
    }

    return $updatedOrder;
}
