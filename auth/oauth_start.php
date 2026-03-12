<?php

require_once __DIR__ . '/../app/services/customer_auth.php';

$provider = strtolower(trim((string) ($_GET['provider'] ?? '')));
$redirectTo = customer_auth_normalize_redirect($_GET['redirect_to'] ?? '/', '/');
$mode = strtolower(trim((string) ($_GET['mode'] ?? 'login')));

try {
    $url = customer_auth_start_oauth($provider, $redirectTo, $mode);
    header('Location: ' . $url);
    exit;
} catch (Throwable $exception) {
    if ($mode === 'connect') {
        customer_profile_set_flash($exception->getMessage(), 'error');
    } else {
        customer_auth_set_flash('login', $exception->getMessage());
    }
    customer_auth_redirect($redirectTo);
}
