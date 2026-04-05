<?php

require_once __DIR__ . '/../config/admin_bootstrap.php';
require_once __DIR__ . '/../database/bundles.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Method not allowed.');
    }

    $action = trim((string) ($_POST['action'] ?? ''));
    if ($action === 'save_bundle') {
        $bundleId = bundle_save_admin($_POST, $_FILES);
        echo json_encode([
            'success' => true,
            'message' => 'Bundle saved successfully.',
            'bundle_id' => $bundleId,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'delete_bundle') {
        bundle_delete_admin((int) ($_POST['bundle_id'] ?? 0));
        echo json_encode([
            'success' => true,
            'message' => 'Bundle deleted successfully.',
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    throw new InvalidArgumentException('Unsupported bundle action.');
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ], JSON_UNESCAPED_SLASHES);
}
