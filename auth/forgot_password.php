<?php

require_once __DIR__ . '/../app/services/customer_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    customer_auth_json([
        'success' => false,
        'message' => 'Method not allowed.',
    ], 405);
}

$redirectTo = customer_auth_normalize_redirect($_POST['redirect_to'] ?? '/', '/');
$successMessage = 'If an account matches that email, a reset link has been sent.';

try {
    customer_auth_request_password_reset($_POST);

    if (customer_auth_is_json_request()) {
        customer_auth_json([
            'success' => true,
            'message' => $successMessage,
        ]);
    }

    customer_auth_set_flash('login', $successMessage, 'success');
    customer_auth_redirect($redirectTo);
} catch (Throwable $exception) {
    if (customer_auth_is_json_request()) {
        customer_auth_json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }

    customer_auth_set_flash('login', $exception->getMessage());
    customer_auth_redirect($redirectTo);
}
