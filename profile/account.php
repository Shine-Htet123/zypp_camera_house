<?php

require_once __DIR__ . '/../app/services/customer_auth.php';
require_once __DIR__ . '/../database/user/profile.php';

customer_auth_require_login('/user-profile.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    customer_auth_json([
        'success' => false,
        'message' => 'Method not allowed.',
    ], 405);
}

$currentUser = customer_auth_current_user();
$userId = (int) ($currentUser['id'] ?? 0);

try {
    $user = profile_update_user_identity($userId, $_POST);

    customer_auth_json([
        'success' => true,
        'message' => 'Account details updated.',
        'user' => $user,
    ]);
} catch (Throwable $exception) {
    customer_auth_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);
}
