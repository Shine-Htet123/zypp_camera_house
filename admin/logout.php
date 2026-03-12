<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/services/admin_auth.php';

admin_auth_logout_admin();
header('Location: ' . admin_auth_normalize_redirect($_GET['redirect_to'] ?? '/', '/'));
exit;
