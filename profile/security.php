<?php

require_once __DIR__ . '/../app/services/customer_auth.php';

customer_auth_require_login('/user-profile.php#settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    customer_auth_json([
        'success' => false,
        'message' => 'Method not allowed.',
    ], 405);
}

$currentUser = customer_auth_current_user();
$userId = (int) ($currentUser['id'] ?? 0);
$action = trim((string) ($_POST['action'] ?? ''));

try {
    if ($action !== 'change_password') {
        throw new InvalidArgumentException('Unsupported action.');
    }

    customer_auth_change_password($userId, $_POST);
    customer_auth_json([
        'success' => true,
        'message' => 'Password updated successfully.',
    ]);
} catch (Throwable $exception) {
    customer_auth_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);
}
