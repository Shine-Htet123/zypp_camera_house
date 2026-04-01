<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

function admin_notifications_ensure_table(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $pdo = get_database_connection();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_notifications (
            notification_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(60) NOT NULL,
            permission_key VARCHAR(60) NOT NULL,
            title VARCHAR(255) NOT NULL,
            body_text TEXT NOT NULL,
            target_url VARCHAR(255) NOT NULL DEFAULT "",
            payload_json LONGTEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (notification_id),
            KEY idx_admin_notifications_permission_created (permission_key, created_at, notification_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_notification_reads (
            notification_id BIGINT(20) UNSIGNED NOT NULL,
            admin_id BIGINT(20) UNSIGNED NOT NULL,
            read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (notification_id, admin_id),
            KEY idx_admin_notification_reads_admin (admin_id, read_at),
            CONSTRAINT fk_admin_notification_reads_notification
                FOREIGN KEY (notification_id) REFERENCES admin_notifications(notification_id)
                ON DELETE CASCADE,
            CONSTRAINT fk_admin_notification_reads_admin
                FOREIGN KEY (admin_id) REFERENCES admins(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );

    $ensured = true;
}

function admin_notifications_format_money(float|int|string $amount): string
{
    return number_format((float) $amount) . ' MMK';
}

function admin_notifications_format_datetime(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d/m/Y H:i', $timestamp);
}

function admin_notifications_build_marker(?string $timestamp, int $notificationId): string
{
    $timestamp = trim((string) $timestamp);
    if ($timestamp === '' || $notificationId <= 0) {
        return '';
    }

    return $timestamp . '|' . $notificationId;
}

function admin_notifications_parse_marker(?string $marker): array
{
    $marker = trim((string) $marker);
    if ($marker === '') {
        return [null, 0];
    }

    $separator = strrpos($marker, '|');
    if ($separator === false) {
        return [$marker, 0];
    }

    $timestamp = trim(substr($marker, 0, $separator));
    $notificationId = (int) substr($marker, $separator + 1);

    return [$timestamp !== '' ? $timestamp : null, max(0, $notificationId)];
}

function admin_notifications_permission_list(array $permissions): array
{
    return array_values(array_unique(array_filter(
        array_map(static fn ($permission): string => trim((string) $permission), $permissions),
        static fn (string $permission): bool => $permission !== ''
    )));
}

function admin_notifications_prepare_permission_clause(array $permissions, string $prefix = 'permission'): array
{
    $permissions = admin_notifications_permission_list($permissions);
    if ($permissions === []) {
        return ['1 = 0', []];
    }

    $bindings = [];
    $placeholders = [];
    foreach ($permissions as $index => $permission) {
        $placeholder = ':' . $prefix . '_' . $index;
        $placeholders[] = $placeholder;
        $bindings[$placeholder] = $permission;
    }

    return ['permission_key IN (' . implode(', ', $placeholders) . ')', $bindings];
}

function admin_notifications_normalize_row(array $row): array
{
    return [
        'notification_id' => (int) ($row['notification_id'] ?? 0),
        'event_type' => trim((string) ($row['event_type'] ?? '')),
        'permission_key' => trim((string) ($row['permission_key'] ?? '')),
        'title' => trim((string) ($row['title'] ?? '')),
        'body' => trim((string) ($row['body_text'] ?? '')),
        'target_url' => trim((string) ($row['target_url'] ?? '')),
        'created_at' => trim((string) ($row['created_at'] ?? '')),
        'created_at_display' => admin_notifications_format_datetime((string) ($row['created_at'] ?? '')),
        'is_unread' => !empty($row['is_unread']),
    ];
}

function admin_notifications_record(
    string $eventType,
    string $permissionKey,
    string $title,
    string $body,
    string $targetUrl = '',
    ?array $payload = null
): int {
    admin_notifications_ensure_table();

    $title = trim($title);
    $body = trim($body);
    $permissionKey = trim($permissionKey);
    if ($title === '' || $body === '' || $permissionKey === '') {
        return 0;
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'INSERT INTO admin_notifications (event_type, permission_key, title, body_text, target_url, payload_json)
         VALUES (:event_type, :permission_key, :title, :body_text, :target_url, :payload_json)'
    );
    $statement->execute([
        ':event_type' => trim($eventType) !== '' ? trim($eventType) : 'general',
        ':permission_key' => $permissionKey,
        ':title' => mb_substr($title, 0, 255),
        ':body_text' => $body,
        ':target_url' => mb_substr(trim((string) $targetUrl), 0, 255),
        ':payload_json' => $payload !== null ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
    ]);

    return (int) $pdo->lastInsertId();
}

function admin_notifications_fetch_snapshot(array $permissions, int $adminId, ?string $sinceMarker = null, int $limit = 12): array
{
    admin_notifications_ensure_table();

    [$permissionClause, $permissionBindings] = admin_notifications_prepare_permission_clause($permissions);
    if ($permissionBindings === [] || $adminId <= 0) {
        return [
            'total_count' => 0,
            'unread_count' => 0,
            'latest_marker' => '',
            'new_count' => 0,
            'notifications' => [],
            'new_notifications' => [],
            'recent_unread_notifications' => [],
        ];
    }

    $pdo = get_database_connection();
    $limit = max(1, min(50, $limit));

    $countBindings = array_merge(
        $permissionBindings,
        [':read_admin_id' => $adminId]
    );
    $countStatement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM admin_notifications
         WHERE ' . $permissionClause
    );
    $countStatement->execute($permissionBindings);
    $totalCount = (int) $countStatement->fetchColumn();

    $unreadCountStatement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM admin_notifications n
         LEFT JOIN admin_notification_reads nr
            ON nr.notification_id = n.notification_id
           AND nr.admin_id = :read_admin_id
         WHERE ' . str_replace('permission_key', 'n.permission_key', $permissionClause) . '
           AND nr.notification_id IS NULL'
    );
    $unreadCountStatement->execute($countBindings);
    $unreadCount = (int) $unreadCountStatement->fetchColumn();

    $latestStatement = $pdo->prepare(
        'SELECT notification_id, created_at
         FROM admin_notifications
         WHERE ' . $permissionClause . '
         ORDER BY created_at DESC, notification_id DESC
         LIMIT 1'
    );
    $latestStatement->execute($permissionBindings);
    $latestRow = $latestStatement->fetch() ?: null;
    $latestMarker = $latestRow
        ? admin_notifications_build_marker((string) ($latestRow['created_at'] ?? ''), (int) ($latestRow['notification_id'] ?? 0))
        : '';

    $listStatement = $pdo->prepare(
        'SELECT
            n.notification_id,
            n.event_type,
            n.permission_key,
            n.title,
            n.body_text,
            n.target_url,
            n.created_at,
            CASE WHEN nr.notification_id IS NULL THEN 1 ELSE 0 END AS is_unread
         FROM admin_notifications n
         LEFT JOIN admin_notification_reads nr
            ON nr.notification_id = n.notification_id
           AND nr.admin_id = :read_admin_id
         WHERE ' . str_replace('permission_key', 'n.permission_key', $permissionClause) . '
         ORDER BY n.created_at DESC, n.notification_id DESC
         LIMIT ' . $limit
    );
    $listStatement->execute($countBindings);
    $notifications = array_map('admin_notifications_normalize_row', $listStatement->fetchAll() ?: []);

    [$sinceTimestamp, $sinceNotificationId] = admin_notifications_parse_marker($sinceMarker);
    $newNotifications = [];
    if ($sinceTimestamp !== null) {
        $newBindings = $countBindings;
        $newBindings[':since_timestamp'] = $sinceTimestamp;
        $newBindings[':since_notification_id'] = $sinceNotificationId;

        $newStatement = $pdo->prepare(
            'SELECT
                n.notification_id,
                n.event_type,
                n.permission_key,
                n.title,
                n.body_text,
                n.target_url,
                n.created_at,
                CASE WHEN nr.notification_id IS NULL THEN 1 ELSE 0 END AS is_unread
             FROM admin_notifications n
             LEFT JOIN admin_notification_reads nr
                ON nr.notification_id = n.notification_id
               AND nr.admin_id = :read_admin_id
             WHERE ' . str_replace('permission_key', 'n.permission_key', $permissionClause) . '
               AND (
                    n.created_at > :since_timestamp
                    OR (n.created_at = :since_timestamp AND n.notification_id > :since_notification_id)
               )
             ORDER BY n.created_at DESC, n.notification_id DESC
             LIMIT ' . $limit
        );
        $newStatement->execute($newBindings);
        $newNotifications = array_map('admin_notifications_normalize_row', $newStatement->fetchAll() ?: []);
    }

    $recentUnreadStatement = $pdo->prepare(
        'SELECT
            n.notification_id,
            n.event_type,
            n.permission_key,
            n.title,
            n.body_text,
            n.target_url,
            n.created_at,
            1 AS is_unread
         FROM admin_notifications n
         LEFT JOIN admin_notification_reads nr
            ON nr.notification_id = n.notification_id
           AND nr.admin_id = :read_admin_id
         WHERE ' . str_replace('permission_key', 'n.permission_key', $permissionClause) . '
           AND nr.notification_id IS NULL
         ORDER BY n.created_at DESC, n.notification_id DESC
         LIMIT 5'
    );
    $recentUnreadStatement->execute($countBindings);
    $recentUnreadNotifications = array_map('admin_notifications_normalize_row', $recentUnreadStatement->fetchAll() ?: []);

    return [
        'total_count' => $totalCount,
        'unread_count' => $unreadCount,
        'latest_marker' => $latestMarker,
        'new_count' => count($newNotifications),
        'notifications' => $notifications,
        'new_notifications' => $newNotifications,
        'recent_unread_notifications' => $recentUnreadNotifications,
    ];
}

function admin_notifications_mark_read(int $notificationId, int $adminId, array $permissions): bool
{
    admin_notifications_ensure_table();

    if ($notificationId <= 0 || $adminId <= 0) {
        return false;
    }

    [$permissionClause, $permissionBindings] = admin_notifications_prepare_permission_clause($permissions);
    if ($permissionBindings === []) {
        return false;
    }

    $pdo = get_database_connection();
    $existsStatement = $pdo->prepare(
        'SELECT notification_id
         FROM admin_notifications
         WHERE notification_id = :notification_id
           AND ' . $permissionClause . '
         LIMIT 1'
    );
    $existsStatement->execute(array_merge(
        [':notification_id' => $notificationId],
        $permissionBindings
    ));
    if (!$existsStatement->fetchColumn()) {
        return false;
    }

    $statement = $pdo->prepare(
        'INSERT INTO admin_notification_reads (notification_id, admin_id)
         VALUES (:notification_id, :admin_id)
         ON DUPLICATE KEY UPDATE read_at = read_at'
    );
    $statement->execute([
        ':notification_id' => $notificationId,
        ':admin_id' => $adminId,
    ]);

    return true;
}

function admin_notifications_delete(int $notificationId, array $permissions): bool
{
    admin_notifications_ensure_table();

    if ($notificationId <= 0) {
        return false;
    }

    [$permissionClause, $permissionBindings] = admin_notifications_prepare_permission_clause($permissions);
    if ($permissionBindings === []) {
        return false;
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'DELETE FROM admin_notifications
         WHERE notification_id = :notification_id
           AND ' . $permissionClause . '
         LIMIT 1'
    );
    $statement->execute(array_merge(
        [':notification_id' => $notificationId],
        $permissionBindings
    ));

    return $statement->rowCount() > 0;
}

function admin_notifications_record_new_order(array $confirmation, array $user): int
{
    $publicOrderId = trim((string) ($confirmation['public_order_id'] ?? ''));
    if ($publicOrderId === '') {
        return 0;
    }

    $customerName = trim((string) ($user['name'] ?? ''));
    if ($customerName === '') {
        $customerName = trim(implode(' ', array_filter([
            trim((string) ($confirmation['first_name'] ?? '')),
            trim((string) ($confirmation['last_name'] ?? '')),
        ])));
    }
    if ($customerName === '') {
        $customerName = 'A customer';
    }

    return admin_notifications_record(
        'new_order',
        'manage_orders',
        'New order #' . $publicOrderId,
        $customerName . ' placed a new order totaling ' . admin_notifications_format_money($confirmation['grand_total'] ?? 0) . '.',
        app_path('/admin/orders.php?q=' . rawurlencode($publicOrderId)),
        [
            'public_order_id' => $publicOrderId,
        ]
    );
}

function admin_notifications_record_payment_proof_upload(array $confirmation, bool $isReupload = false): int
{
    $publicOrderId = trim((string) ($confirmation['public_order_id'] ?? ''));
    if ($publicOrderId === '') {
        return 0;
    }

    $publicPaymentId = trim((string) ($confirmation['public_payment_id'] ?? ''));
    $paymentMethod = trim((string) ($confirmation['payment_method'] ?? ''));
    if ($paymentMethod === '') {
        $paymentMethod = trim((string) ($confirmation['payment_method_label'] ?? 'Payment'));
    }

    $title = $isReupload
        ? 'Payment proof re-uploaded for #' . $publicOrderId
        : 'New payment proof for #' . $publicOrderId;
    $body = $isReupload
        ? 'A customer re-uploaded a payment proof' . ($paymentMethod !== '' ? ' via ' . $paymentMethod : '') . '.'
        : 'A customer uploaded a payment proof' . ($paymentMethod !== '' ? ' via ' . $paymentMethod : '') . '.';

    $searchToken = $publicPaymentId !== '' ? $publicPaymentId : $publicOrderId;

    return admin_notifications_record(
        'payment_proof_upload',
        'manage_payment_proofs',
        $title,
        $body,
        app_path('/admin/payment-proof-uploads.php?q=' . rawurlencode($searchToken)),
        [
            'public_order_id' => $publicOrderId,
            'public_payment_id' => $publicPaymentId,
            'is_reupload' => $isReupload,
        ]
    );
}

function admin_notifications_record_order_status_update(
    int $orderId,
    string $orderStatus,
    ?string $paymentStatus,
    array $admin
): int {
    if ($orderId <= 0) {
        return 0;
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT public_order_id
         FROM orders
         WHERE id = :order_id
         LIMIT 1'
    );
    $statement->execute([':order_id' => $orderId]);
    $publicOrderId = trim((string) $statement->fetchColumn());
    if ($publicOrderId === '') {
        return 0;
    }

    $adminName = trim((string) ($admin['full_name'] ?? ''));
    if ($adminName === '') {
        $adminName = trim((string) ($admin['email'] ?? 'An admin'));
    }
    if ($adminName === '') {
        $adminName = 'An admin';
    }

    $body = 'Updated to ' . trim($orderStatus) . ' by ' . $adminName . '.';
    $paymentStatus = trim((string) $paymentStatus);
    if ($paymentStatus !== '') {
        $body .= ' Payment status: ' . $paymentStatus . '.';
    }

    return admin_notifications_record(
        'order_status_update',
        'manage_orders',
        'Order #' . $publicOrderId . ' status updated',
        $body,
        app_path('/admin/orders.php?q=' . rawurlencode($publicOrderId)),
        [
            'order_id' => $orderId,
            'public_order_id' => $publicOrderId,
            'order_status' => $orderStatus,
            'payment_status' => $paymentStatus,
            'admin_name' => $adminName,
        ]
    );
}

function admin_notifications_record_wholesale_submission(array $survey): int
{
    $companyName = trim((string) ($survey['company_name'] ?? ''));
    if ($companyName === '') {
        $companyName = 'A business';
    }

    return admin_notifications_record(
        'wholesale_survey',
        'manage_wholesale',
        'New wholesale survey',
        $companyName . ' submitted a wholesale inquiry.',
        app_path('/admin/wholesale-survey.php'),
        [
            'answer_id' => (int) ($survey['answer_id'] ?? 0),
            'company_name' => $companyName,
        ]
    );
}
