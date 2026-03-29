<?php

require_once __DIR__ . '/../config/admin_bootstrap.php';
require_once __DIR__ . '/../database/admin/orders.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $action = trim((string) ($_REQUEST['action'] ?? ''));

    if ($action === 'order-detail') {
        $orderId = (int) ($_GET['order_id'] ?? 0);
        $detail = admin_fetch_order_detail($orderId);
        if (!$detail) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $detail], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'notifications') {
        $since = trim((string) ($_GET['since'] ?? ''));
        $snapshot = admin_fetch_payment_proof_notification_snapshot($since);

        echo json_encode([
            'success' => true,
            'data' => $snapshot,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'bulk-status') {
        $paymentIds = (array) ($_POST['payment_ids'] ?? []);
        $statusAction = trim((string) ($_POST['status_action'] ?? ''));
        $updated = admin_update_payment_proof_statuses($paymentIds, $statusAction);

        echo json_encode([
            'success' => true,
            'message' => 'Updated ' . $updated . ' payment proof(s).',
            'data' => [
                'payment_status' => admin_orders_normalize_payment_status(match ($statusAction) {
                    'approve' => 'paid',
                    'reject' => 'unpaid',
                    default => 'pending',
                }),
            ],
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported payment-proof action.']);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
}
