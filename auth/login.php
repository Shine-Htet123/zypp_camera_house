<?php

require_once __DIR__ . '/../app/services/customer_auth.php';

$redirectTo = customer_auth_normalize_redirect($_POST['redirect_to'] ?? '/', '/');

try {
    $user = customer_auth_login($_POST);
} catch (Throwable $exception) {
    if (customer_auth_is_json_request()) {
        customer_auth_json([
            'success' => false,
            'panel' => 'login',
            'message' => $exception->getMessage(),
        ], 422);
    }
    customer_auth_set_flash('login', $exception->getMessage());
    customer_auth_redirect($redirectTo);
}

if (customer_auth_is_json_request()) {
    $firstName = trim((string) explode(' ', (string) ($user['name'] ?? ''))[0]);
    customer_auth_json([
        'success' => true,
        'panel' => 'login',
        'message' => $firstName !== '' ? 'Welcome back, ' . $firstName . '.' : 'Welcome back.',
        'redirect_to' => $redirectTo,
        'user' => [
            'name' => (string) ($user['name'] ?? ''),
        ],
    ]);
}

customer_auth_redirect($redirectTo);
