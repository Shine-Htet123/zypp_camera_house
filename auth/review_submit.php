<?php

require_once __DIR__ . '/../config/customer_bootstrap.php';
require_once __DIR__ . '/../app/services/customer_auth.php';
require_once __DIR__ . '/../database/home.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    customer_auth_json([
        'success' => false,
        'message' => 'Invalid request method.',
    ], 405);
}

$currentUser = customer_auth_current_user();
if (!$currentUser) {
    customer_auth_json([
        'success' => false,
        'message' => 'Please log in to leave a review.',
        'login_required' => true,
    ], 401);
}

try {
    $review = home_submit_review((int) $currentUser['id'], [
        'rating' => $_POST['rating'] ?? null,
        'topic' => $_POST['topic'] ?? null,
        'comment' => $_POST['comment'] ?? null,
    ]);

    customer_auth_json([
        'success' => true,
        'message' => 'Thank you for your review.',
        'payload' => [
            'review' => $review,
        ],
    ]);
} catch (Throwable $exception) {
    customer_auth_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);
}
