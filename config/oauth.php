<?php

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/env.php';

function oauth_local_overrides(): array
{
    $appEnv = strtolower((string) (env('APP_ENV', 'production') ?: 'production'));
    if (!in_array($appEnv, ['local', 'development'], true)) {
        return [];
    }

    $localConfigPath = __DIR__ . '/oauth.local.php';
    if (!is_file($localConfigPath)) {
        return [];
    }

    $config = require $localConfigPath;
    return is_array($config) ? $config : [];
}

function oauth_config(): array
{
    $config = [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID', '') ?: '',
            'client_secret' => env('GOOGLE_CLIENT_SECRET', '') ?: '',
            'redirect_uri' => env('GOOGLE_REDIRECT_URI', app_url('/auth/oauth_callback.php?provider=google')) ?: app_url('/auth/oauth_callback.php?provider=google'),
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
            'scopes' => ['openid', 'email', 'profile'],
        ],
    ];

    $localOverrides = oauth_local_overrides();
    foreach ($localOverrides as $provider => $providerConfig) {
        if (!isset($config[$provider]) || !is_array($providerConfig)) {
            continue;
        }

        $config[$provider] = array_merge($config[$provider], $providerConfig);
    }

    return $config;
}
