<?php

require_once __DIR__ . '/../config/admin_bootstrap.php';
require_once __DIR__ . '/../database/admin/notifications.php';

header('Content-Type: application/json; charset=utf-8');

$visibleNotificationPermissions = array_values(array_filter([
    admin_auth_has_permission($currentAdmin, 'manage_orders') ? 'manage_orders' : '',
    admin_auth_has_permission($currentAdmin, 'manage_payment_proofs') ? 'manage_payment_proofs' : '',
    admin_auth_has_permission($currentAdmin, 'manage_wholesale') ? 'manage_wholesale' : '',
]));

try {
    $action = trim((string) ($_REQUEST['action'] ?? 'snapshot'));
    $currentAdminId = (int) ($currentAdmin['id'] ?? 0);

    if ($action === 'snapshot') {
        $since = trim((string) ($_GET['since'] ?? ''));
        $snapshot = admin_notifications_fetch_snapshot($visibleNotificationPermissions, $currentAdminId, $since);

        echo json_encode([
            'success' => true,
            'data' => $snapshot,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'mark-read') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            throw new InvalidArgumentException('Notification is required.');
        }

        $marked = admin_notifications_mark_read($notificationId, $currentAdminId, $visibleNotificationPermissions);

        echo json_encode([
            'success' => true,
            'data' => [
                'marked' => $marked,
                'notification_id' => $notificationId,
            ],
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'delete') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            throw new InvalidArgumentException('Notification is required.');
        }

        $deleted = admin_notifications_delete($notificationId, $visibleNotificationPermissions);

        echo json_encode([
            'success' => true,
            'data' => [
                'deleted' => $deleted,
                'notification_id' => $notificationId,
            ],
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported notifications action.']);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
}
