<?php

require_once __DIR__ . '/../app/services/cart.php';

try {
    $payload = customer_cart_add($_POST);

    if (customer_auth_is_json_request()) {
        customer_auth_json([
            'success' => true,
            'message' => 'Product added to cart.',
            'payload' => $payload,
        ]);
    }

    customer_cart_set_flash('Product added to cart.', 'success');
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
