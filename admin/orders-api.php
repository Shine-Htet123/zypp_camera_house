<?php

require_once __DIR__ . '/../config/admin_bootstrap.php';
require_once __DIR__ . '/../database/admin/orders.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $action = trim((string) ($_REQUEST['action'] ?? ''));

    if ($action === 'detail') {
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

    if ($action === 'update-status') {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            throw new InvalidArgumentException('Order is required.');
        }

        $orderStatus = admin_orders_normalize_order_status((string) ($_POST['order_status'] ?? ''));
        $paymentStatus = null;
        if (isset($_POST['payment_status']) && trim((string) $_POST['payment_status']) !== '') {
            $paymentStatus = admin_orders_normalize_payment_status((string) $_POST['payment_status']);
        }

        $result = admin_update_order_statuses($orderId, $orderStatus, $paymentStatus);

        echo json_encode([
            'success' => true,
            'data' => [
                'order_status' => $result['order_status'],
                'payment_status' => $result['payment_status'],
            ],
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported orders action.']);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
}
