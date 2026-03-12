<?php

require_once __DIR__ . '/../../config/customer_bootstrap.php';
require_once __DIR__ . '/../../database/catalog.php';
require_once __DIR__ . '/customer_auth.php';

function products_current_customer_id(): ?int
{
    $user = customer_auth_current_user();
    if (!$user) {
        return null;
    }

    return (int) $user['id'];
}
