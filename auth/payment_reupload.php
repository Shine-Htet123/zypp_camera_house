<?php

require_once __DIR__ . '/../app/services/checkout.php';

customer_auth_require_login('/user-profile.php#orders');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    customer_auth_json([
        'success' => false,
        'message' => 'Invalid payment re-upload request.',
    ], 405);
}

try {
    $publicOrderId = trim((string) ($_POST['order_public_id'] ?? ''));
    $updatedOrder = checkout_reupload_existing_payment_proof($publicOrderId, $_FILES['payment_proof'] ?? []);

    customer_auth_json([
        'success' => true,
        'message' => 'Payment proof re-uploaded successfully.',
        'payload' => [
            'order_public_id' => (string) $updatedOrder['public_order_id'],
            'payment_status' => (string) (($updatedOrder['payment_row_status'] ?? $updatedOrder['payment_status']) ?? 'pending'),
            'reupload_requested' => !empty($updatedOrder['reupload_requested']),
        ],
    ]);
} catch (Throwable $exception) {
    customer_auth_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);
}
