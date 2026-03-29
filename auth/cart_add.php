<?php

require_once __DIR__ . '/../app/services/cart.php';

$input = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST;

try {
    $action = trim((string) ($input['action'] ?? 'add_item'));
    $isBundleAdd = $action === 'add_bundle';
    $payload = $isBundleAdd ? customer_cart_add_bundle($input) : customer_cart_add($input);
    $successMessage = $isBundleAdd ? 'Bundle added to cart.' : 'Product added to cart.';

    if (customer_auth_is_json_request()) {
        customer_auth_json([
            'success' => true,
            'message' => $successMessage,
            'payload' => $payload,
        ]);
    }

    customer_cart_set_flash($successMessage, 'success');
    customer_auth_redirect('/cart.php');
} catch (Throwable $exception) {
    if (customer_auth_is_json_request()) {
        $status = str_contains(strtolower($exception->getMessage()), 'log in') ? 401 : 422;
        customer_auth_json([
            'success' => false,
            'message' => $exception->getMessage(),
            'login_required' => $status === 401,
        ], $status);
    }

    customer_cart_set_flash($exception->getMessage(), 'error');
    customer_auth_redirect('/cart.php');
}
