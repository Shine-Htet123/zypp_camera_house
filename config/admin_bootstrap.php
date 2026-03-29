<?php

require_once __DIR__ . '/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/services/admin_auth.php';

$currentAdmin = admin_auth_require_login($_SERVER['REQUEST_URI'] ?? app_path('/admin/index.php'));
admin_auth_authorize_request($currentAdmin, $_SERVER['REQUEST_URI'] ?? app_path('/admin/index.php'));
