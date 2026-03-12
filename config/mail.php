<?php

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/env.php';

function mail_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }

    $config = [
        'from_email' => env('MAIL_FROM_ADDRESS', '') ?: '',
        'from_name' => env('MAIL_FROM_NAME', 'ZYPP Camera House') ?: 'ZYPP Camera House',
        'smtp' => [
            'host' => env('MAIL_HOST', '') ?: '',
            'port' => (int) (env('MAIL_PORT', '587') ?: 587),
            'username' => env('MAIL_USERNAME', '') ?: '',
            'password' => env('MAIL_PASSWORD', '') ?: '',
            'encryption' => strtolower((string) (env('MAIL_ENCRYPTION', 'tls') ?: 'tls')),
            'auth' => env_bool('MAIL_SMTP_AUTH', true),
        ],
    ];

    $appEnv = strtolower((string) (env('APP_ENV', 'production') ?: 'production'));
    $localPath = __DIR__ . '/mail.local.php';
    if (in_array($appEnv, ['local', 'development'], true) && is_file($localPath)) {
        $local = require $localPath;
        if (is_array($local)) {
            $config = array_replace_recursive($config, $local);
        }
    }

    return $config;
}
