<?php

require_once __DIR__ . '/../app/services/customer_auth.php';

$redirectTo = customer_auth_normalize_redirect($_GET['redirect_to'] ?? '/', '/');
customer_auth_logout_user();
customer_auth_redirect($redirectTo);
