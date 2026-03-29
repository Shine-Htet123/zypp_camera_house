<?php

require_once __DIR__ . '/config/app.php';

$params = [];
$referralCode = trim((string) ($_GET['ref'] ?? ''));
if ($referralCode !== '') {
    $params['ref'] = $referralCode;
}
$params['register'] = '1';

$target = app_path('/index.php');
if ($params !== []) {
    $target .= '?' . http_build_query($params);
}

header('Location: ' . $target);
exit;
