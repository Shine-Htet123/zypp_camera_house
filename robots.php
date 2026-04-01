<?php

require_once __DIR__ . '/config/app.php';

header('Content-Type: text/plain; charset=UTF-8');

$lines = [
    'User-agent: *',
    'Allow: /',
    'Disallow: /admin/',
    'Disallow: /auth/',
    'Disallow: /profile/',
    'Disallow: /cart.php',
    'Disallow: /check-order.php',
    'Disallow: /delivery.php',
    'Disallow: /payment.php',
    'Disallow: /receipt.php',
    'Disallow: /register.php',
    'Disallow: /reset-password.php',
    'Disallow: /user-profile.php',
    'Sitemap: ' . app_url('/sitemap.xml'),
];

echo implode("\n", $lines) . "\n";
