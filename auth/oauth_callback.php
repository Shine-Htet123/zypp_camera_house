<?php

require_once __DIR__ . '/../app/services/customer_auth.php';

$provider = strtolower(trim((string) ($_GET['provider'] ?? $_POST['provider'] ?? '')));
$redirectTo = customer_auth_redirect_after_oauth();
$mode = strtolower(trim((string) ($_SESSION['oauth_mode'] ?? 'login')));

try {
    customer_auth_handle_oauth_callback($provider, $_GET, $_POST);
} catch (Throwable $exception) {
    if ($mode === 'connect') {
        customer_profile_set_flash($exception->getMessage(), 'error');
    } else {
        customer_auth_set_flash('login', $exception->getMessage());
    }
    customer_auth_redirect($redirectTo);
}

customer_auth_redirect($redirectTo);
