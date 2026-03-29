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
$action = strtolower(trim((string) ($_POST['action'] ?? '')));
$addressId = isset($_POST['address_id']) && $_POST['address_id'] !== '' ? (int) $_POST['address_id'] : null;

try {
    if ($action === 'save') {
        $result = profile_save_address_card($userId, $addressId, $_POST);
        customer_auth_json([
            'success' => true,
            'message' => 'Address saved.',
            'address' => $result['address'],
        ]);
    }

    if ($action === 'delete') {
        $result = profile_delete_address_card($userId, $addressId);
        customer_auth_json([
            'success' => true,
            'message' => 'Address removed.',
            'deleted_address_id' => $result['deleted_address_id'],
            'next_default_address_id' => $result['next_default_address_id'],
            'remaining_count' => $result['remaining_count'],
        ]);
    }

    if ($action === 'set_default') {
        $address = profile_set_default_address($userId, (int) $addressId);
        customer_auth_json([
            'success' => true,
            'message' => 'Default address updated.',
            'address' => $address,
        ]);
    }

    customer_auth_json([
        'success' => false,
        'message' => 'Unsupported action.',
    ], 400);
} catch (Throwable $exception) {
    customer_auth_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);
}
