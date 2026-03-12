<?php

require_once __DIR__ . '/../config/customer_bootstrap.php';
require_once __DIR__ . '/../app/services/customer_auth.php';
require_once __DIR__ . '/../database/user/wholesale.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    customer_auth_json([
        'success' => false,
        'message' => 'Invalid request method.',
    ], 405);
}

try {
    $survey = wholesale_submit_survey($_POST);

    customer_auth_json([
        'success' => true,
        'message' => 'Your wholesale inquiry has been submitted.',
        'payload' => [
            'survey' => $survey,
        ],
    ]);
} catch (Throwable $exception) {
    customer_auth_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);
}
