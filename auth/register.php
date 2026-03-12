<?php

require_once __DIR__ . '/../app/services/customer_auth.php';

$redirectTo = customer_auth_normalize_redirect($_POST['redirect_to'] ?? '/', '/');

try {
    $user = customer_auth_register($_POST);
} catch (Throwable $exception) {
    if (customer_auth_is_json_request()) {
        customer_auth_json([
            'success' => false,
            'panel' => 'register',
            'message' => $exception->getMessage(),
        ], 422);
    }
    customer_auth_set_flash('register', $exception->getMessage());
    customer_auth_redirect($redirectTo);
}

if (customer_auth_is_json_request()) {
    $firstName = trim((string) explode(' ', (string) ($user['name'] ?? ''))[0]);
    customer_auth_json([
        'success' => true,
        'panel' => 'register',
        'message' => $firstName !== '' ? 'Welcome, ' . $firstName . '. Please add your delivery information before placing your first order.' : 'Account created successfully. Please add your delivery information before placing your first order.',
        'redirect_to' => $redirectTo,
        'user' => [
            'name' => (string) ($user['name'] ?? ''),
        ],
    ]);
}

customer_auth_redirect($redirectTo);
