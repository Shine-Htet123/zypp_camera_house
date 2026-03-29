<?php

require_once __DIR__ . '/../../config/customer_bootstrap.php';
require_once __DIR__ . '/../../config/oauth.php';
require_once __DIR__ . '/../../database/user/auth.php';
require_once __DIR__ . '/../../database/user/membership.php';
require_once __DIR__ . '/../../database/security/password_resets.php';
require_once __DIR__ . '/mailer.php';

function customer_auth_normalize_redirect(?string $target, string $fallback = '/'): string
{
    $target = trim((string) $target);
    if ($target === '' || $target[0] !== '/' || str_starts_with($target, '//')) {
        return $fallback;
    }

    return $target;
}

function customer_auth_set_flash(string $panel, string $message, string $type = 'error'): void
{
    $_SESSION['customer_auth_flash'] = [
        'panel' => $panel,
        'message' => $message,
        'type' => $type,
    ];
}

function customer_profile_set_flash(string $message, string $type = 'info'): void
{
    $_SESSION['customer_profile_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function customer_profile_consume_flash(): ?array
{
    $flash = $_SESSION['customer_profile_flash'] ?? null;
    unset($_SESSION['customer_profile_flash']);

    return is_array($flash) ? $flash : null;
}

function customer_auth_consume_flash(): ?array
{
    $flash = $_SESSION['customer_auth_flash'] ?? null;
    unset($_SESSION['customer_auth_flash']);

    return is_array($flash) ? $flash : null;
}

function customer_auth_redirect(string $target): never
{
    header('Location: ' . customer_auth_normalize_redirect($target));
    exit;
}

function customer_auth_is_json_request(): bool
{
    $requestedWith = strtolower(trim((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')));
    if ($requestedWith === 'xmlhttprequest') {
        return true;
    }

    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    return str_contains($accept, 'application/json');
}

function customer_auth_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function customer_auth_login_user(array $user): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['customer_user_id'] = (int) $user['id'];
}

function customer_auth_logout_user(): void
{
    unset(
        $_SESSION['customer_user_id'],
        $_SESSION['customer_auth_flash'],
        $_SESSION['customer_profile_flash'],
        $_SESSION['oauth_state'],
        $_SESSION['oauth_redirect_to'],
        $_SESSION['oauth_provider'],
        $_SESSION['oauth_mode']
    );
}

function customer_auth_current_user(): ?array
{
    $userId = isset($_SESSION['customer_user_id']) ? (int) $_SESSION['customer_user_id'] : 0;
    if ($userId <= 0) {
        return null;
    }

    $user = auth_user_by_id($userId);
    if (!$user) {
        customer_auth_logout_user();
        return null;
    }

    if (($user['status'] ?? '') !== 'active') {
        customer_auth_logout_user();
        customer_auth_set_flash('login', 'Your account is not active. Please contact support.');
        return null;
    }

    return $user;
}

function customer_auth_require_login(string $redirectAfter = '/user-profile.php'): void
{
    if (customer_auth_current_user()) {
        return;
    }

    customer_auth_set_flash('login', 'Please log in to continue.');
    customer_auth_redirect(customer_auth_normalize_redirect($redirectAfter, '/'));
}

function customer_auth_validate_registration(array $input): array
{
    $firstName = trim((string) ($input['firstname'] ?? ''));
    $lastName = trim((string) ($input['lastname'] ?? ''));
    $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
    $password = trim((string) ($input['password'] ?? ''));

    if ($firstName === '' || $lastName === '') {
        throw new InvalidArgumentException('First name and last name are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)) {
        throw new InvalidArgumentException('Password must be 8+ chars with upper, lower, number, and special character.');
    }

    if (auth_user_by_email($email)) {
        throw new InvalidArgumentException('An account with this email already exists.');
    }

    return [
        'name' => $firstName . ' ' . $lastName,
        'email' => $email,
        'password' => $password,
    ];
}

function customer_auth_resolve_referrer_from_token(?string $token): ?array
{
    $token = trim((string) $token);
    if ($token === '') {
        return null;
    }

    $referrer = auth_user_by_referral_token($token);
    if (!$referrer) {
        throw new InvalidArgumentException('Referral link is invalid.');
    }

    return $referrer;
}

function customer_auth_resolve_referrer_from_input(array $input): ?array
{
    return customer_auth_resolve_referrer_from_token(
        (string) ($input['ref'] ?? $input['referral_token'] ?? '')
    );
}

function customer_auth_resolve_referrer_from_redirect_target(?string $target): ?array
{
    $target = trim((string) $target);
    if ($target === '') {
        return null;
    }

    $query = parse_url($target, PHP_URL_QUERY);
    if (!is_string($query) || $query === '') {
        return null;
    }

    parse_str($query, $params);
    return customer_auth_resolve_referrer_from_token((string) ($params['ref'] ?? ''));
}

function customer_auth_register(array $input): array
{
    $payload = customer_auth_validate_registration($input);
    $initialTier = membership_resolve_tier_for_spent(0.0);
    $referrer = customer_auth_resolve_referrer_from_input($input);

    $user = auth_create_user([
        'public_user_id' => auth_generate_public_user_id(),
        'name' => $payload['name'],
        'email' => $payload['email'],
        'password' => password_hash($payload['password'], PASSWORD_DEFAULT),
        'membership_tier_id' => $initialTier !== null ? (int) $initialTier['id'] : null,
        'status' => 'active',
        'referral_token' => auth_generate_referral_token(),
        'referred_by_user_id' => $referrer !== null ? (int) $referrer['id'] : null,
        'google_provider_id' => null,
    ]);

    if (!$user) {
        throw new RuntimeException('Failed to create account.');
    }

    customer_auth_login_user($user);

    return $user;
}

function customer_auth_validate_password_input(string $password, string $confirmPassword): void
{
    if (!auth_password_is_strong($password)) {
        throw new InvalidArgumentException('Password must be 8+ chars with upper, lower, number, and special character.');
    }

    if ($password !== $confirmPassword) {
        throw new InvalidArgumentException('Password confirmation does not match.');
    }
}

function customer_auth_login(array $input): array
{
    $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
    $password = trim((string) ($input['password'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        throw new InvalidArgumentException('Incorrect email or password.');
    }

    $user = auth_user_by_email($email);
    if (!$user || !auth_verify_user_password($user, $password)) {
        throw new InvalidArgumentException('Incorrect email or password.');
    }

    if (($user['status'] ?? '') === 'suspended') {
        throw new RuntimeException('Your account is suspended.');
    }

    if (($user['status'] ?? '') === 'banned') {
        throw new RuntimeException('Your account is banned.');
    }

    customer_auth_login_user($user);

    return $user;
}

function customer_auth_send_password_reset_email(array $user, string $selector, string $token, string $expiresAt): void
{
    $resetUrl = app_url('/reset-password.php?selector=' . rawurlencode($selector) . '&token=' . rawurlencode($token));
    $customerName = trim((string) ($user['name'] ?? 'Customer'));

    mailer_send([
        'to_email' => (string) ($user['email'] ?? ''),
        'to_name' => $customerName,
        'subject' => 'Reset your ZYPP account password',
        'html' => '
            <div style="font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#222;">
                <h2 style="margin:0 0 16px;">Password Reset Request</h2>
                <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                <p>We received a request to reset your ZYPP account password.</p>
                <p style="margin:24px 0;">
                    <a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#6c5548;color:#fff;text-decoration:none;padding:12px 20px;border-radius:8px;display:inline-block;">Reset Password</a>
                </p>
                <p>If the button does not work, open this link:</p>
                <p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '</a></p>
                <p>This link expires on ' . htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') . '.</p>
                <p>If you did not request this, you can ignore this email.</p>
            </div>',
        'text' => "Hello {$customerName},\n\nWe received a request to reset your ZYPP account password.\n\nReset password:\n{$resetUrl}\n\nThis link expires on {$expiresAt}.\nIf you did not request this, you can ignore this email.",
    ]);
}

function customer_auth_request_password_reset(array $input): void
{
    $email = mb_strtolower(trim((string) ($input['email'] ?? $input['reset_email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    if (!mailer_is_configured()) {
        throw new RuntimeException('Password reset email is not configured yet.');
    }

    $user = auth_user_by_email($email);
    if (!$user) {
        return;
    }

    $token = password_reset_create_token('customer', (int) $user['id'], (string) $user['email'], [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);

    customer_auth_send_password_reset_email($user, $token['selector'], $token['token'], (string) $token['expires_at']);
}

function customer_auth_validate_password_reset_token(string $selector, string $token): array
{
    $selector = trim($selector);
    $token = trim($token);
    if ($selector === '' || $token === '') {
        throw new InvalidArgumentException('Password reset token is missing.');
    }

    $row = password_reset_find_token('customer', $selector, $token);
    if (!$row) {
        throw new RuntimeException('This password reset link is invalid or expired.');
    }

    $user = auth_user_by_id((int) ($row['account_id'] ?? 0));
    if (!$user) {
        throw new RuntimeException('The account for this password reset link no longer exists.');
    }

    return [
        'token_id' => (int) $row['token_id'],
        'user' => $user,
        'email' => (string) ($row['email'] ?? $user['email'] ?? ''),
        'expires_at' => (string) ($row['expires_at'] ?? ''),
    ];
}

function customer_auth_reset_password(array $input): void
{
    $password = trim((string) ($input['password'] ?? ''));
    $confirmPassword = trim((string) ($input['confirm_password'] ?? ''));
    $tokenData = customer_auth_validate_password_reset_token(
        (string) ($input['selector'] ?? ''),
        (string) ($input['token'] ?? '')
    );

    customer_auth_validate_password_input($password, $confirmPassword);

    auth_update_user_password((int) $tokenData['user']['id'], password_hash($password, PASSWORD_DEFAULT));
    password_reset_mark_used((int) $tokenData['token_id']);
    password_reset_delete_for_account('customer', (int) $tokenData['user']['id']);
}

function customer_auth_change_password(int $userId, array $input): array
{
    $currentPassword = trim((string) ($input['current_password'] ?? ''));
    $newPassword = trim((string) ($input['new_password'] ?? ''));
    $confirmPassword = trim((string) ($input['confirm_password'] ?? ''));

    if ($currentPassword === '') {
        throw new InvalidArgumentException('Current password is required.');
    }

    customer_auth_validate_password_input($newPassword, $confirmPassword);

    $user = auth_user_by_id($userId);
    if (!$user) {
        throw new RuntimeException('Account not found.');
    }

    if (!auth_verify_user_password($user, $currentPassword)) {
        throw new InvalidArgumentException('Current password is incorrect.');
    }

    if (hash_equals($currentPassword, $newPassword)) {
        throw new InvalidArgumentException('New password must be different from your current password.');
    }

    auth_update_user_password($userId, password_hash($newPassword, PASSWORD_DEFAULT));
    password_reset_delete_for_account('customer', $userId);

    return auth_user_by_id($userId) ?? $user;
}

function customer_auth_http_post(string $url, array $data, array $headers = []): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            throw new RuntimeException(curl_error($ch));
        }
        curl_close($ch);

        return ['status' => $status, 'body' => $response];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", array_merge(['Content-Type: application/x-www-form-urlencoded'], $headers)),
            'content' => http_build_query($data),
            'ignore_errors' => true,
        ],
    ]);
    $body = file_get_contents($url, false, $context);

    return ['status' => 200, 'body' => $body ?: ''];
}

function customer_auth_http_get_json(string $url, array $headers = []): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            throw new RuntimeException(curl_error($ch));
        }
        curl_close($ch);
        $decoded = json_decode($response, true);

        return ['status' => $status, 'data' => is_array($decoded) ? $decoded : []];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
        ],
    ]);
    $body = file_get_contents($url, false, $context);
    $decoded = json_decode($body ?: '', true);

    return ['status' => 200, 'data' => is_array($decoded) ? $decoded : []];
}

function customer_auth_base64url_encode(string $input): string
{
    return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

function customer_auth_base64url_decode(string $input): string
{
    $remainder = strlen($input) % 4;
    if ($remainder !== 0) {
        $input .= str_repeat('=', 4 - $remainder);
    }

    return (string) base64_decode(strtr($input, '-_', '+/'));
}

function customer_auth_parse_jwt_payload(string $jwt): array
{
    $parts = explode('.', $jwt);
    if (count($parts) < 2) {
        throw new RuntimeException('Invalid token payload.');
    }

    $payload = json_decode(customer_auth_base64url_decode($parts[1]), true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid token payload.');
    }

    return $payload;
}

function customer_auth_provider_config(string $provider): array
{
    $config = oauth_config();
    if (!isset($config[$provider])) {
        throw new InvalidArgumentException('Unsupported provider.');
    }

    return $config[$provider];
}

function customer_auth_start_oauth(string $provider, string $redirectTo, string $mode = 'login'): string
{
    $config = customer_auth_provider_config($provider);
    if (($config['client_id'] ?? '') === '' || ($config['redirect_uri'] ?? '') === '') {
        throw new RuntimeException(ucfirst($provider) . ' login is not configured yet.');
    }

    if (!in_array($mode, ['login', 'connect'], true)) {
        throw new InvalidArgumentException('Unsupported OAuth mode.');
    }

    if ($mode === 'connect' && !customer_auth_current_user()) {
        throw new RuntimeException('Please log in before connecting a social account.');
    }

    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_provider'] = $provider;
    $_SESSION['oauth_redirect_to'] = customer_auth_normalize_redirect($redirectTo, '/');
    $_SESSION['oauth_mode'] = $mode;

    if ($provider === 'google') {
        $query = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $config['scopes']),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'select_account',
        ]);

        return $config['authorize_url'] . '?' . $query;
    }

    throw new InvalidArgumentException('Unsupported provider.');
}

function customer_auth_create_social_user(string $provider, string $providerId, string $email, string $name, ?array $referrer = null): array
{
    $initialTier = membership_resolve_tier_for_spent(0.0);

    $user = auth_create_user([
        'public_user_id' => auth_generate_public_user_id(),
        'name' => $name !== '' ? $name : ucfirst($provider) . ' User',
        'email' => mb_strtolower(trim($email)),
        'password' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
        'membership_tier_id' => $initialTier !== null ? (int) $initialTier['id'] : null,
        'status' => 'active',
        'referral_token' => auth_generate_referral_token(),
        'referred_by_user_id' => $referrer !== null ? (int) $referrer['id'] : null,
        'google_provider_id' => $provider === 'google' ? $providerId : null,
    ]);

    if (!$user) {
        throw new RuntimeException('Failed to create social account.');
    }

    return $user;
}

function customer_auth_finish_provider_login(string $provider, string $providerId, string $email, string $name, ?array $referrer = null): array
{
    $providerUser = auth_user_by_provider_id($provider, $providerId);
    if ($providerUser) {
        if (($providerUser['status'] ?? '') !== 'active') {
            throw new RuntimeException('Your account is not active. Please contact support.');
        }
        customer_auth_login_user($providerUser);
        return $providerUser;
    }

    if ($email === '') {
        throw new RuntimeException('The provider did not return an email address.');
    }

    $existingEmailUser = auth_user_by_email($email);
    if ($existingEmailUser) {
        throw new RuntimeException('This email already exists. Please log in with your email and password first.');
    }

    $user = customer_auth_create_social_user($provider, $providerId, $email, $name, $referrer);
    customer_auth_login_user($user);

    return $user;
}

function customer_auth_link_provider_to_current_user(string $provider, string $providerId): array
{
    $currentUser = customer_auth_current_user();
    if (!$currentUser) {
        throw new RuntimeException('Please log in before connecting a social account.');
    }

    $providerUser = auth_user_by_provider_id($provider, $providerId);
    if ($providerUser && (int) $providerUser['id'] !== (int) $currentUser['id']) {
        throw new RuntimeException('This social account is already connected to another user.');
    }

    if ($providerUser && (int) $providerUser['id'] === (int) $currentUser['id']) {
        customer_profile_set_flash(ucfirst($provider) . ' is already connected to your account.', 'info');
        return $currentUser;
    }

    $updatedUser = auth_update_provider_id((int) $currentUser['id'], $provider, $providerId);
    customer_auth_login_user($updatedUser);
    customer_profile_set_flash(ucfirst($provider) . ' connected successfully.', 'success');

    return $updatedUser;
}

function customer_auth_fetch_google_identity(array $config, string $code): array
{
    $tokenResponse = customer_auth_http_post($config['token_url'], [
        'code' => $code,
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code',
    ]);

    $tokenData = json_decode($tokenResponse['body'], true);
    if (!is_array($tokenData) || empty($tokenData['access_token'])) {
        throw new RuntimeException('Google token exchange failed.');
    }

    $profileResponse = customer_auth_http_get_json(
        $config['userinfo_url'],
        ['Authorization: Bearer ' . $tokenData['access_token']]
    );

    $profile = $profileResponse['data'];
    if (empty($profile['sub'])) {
        throw new RuntimeException('Google did not return a valid user profile.');
    }

    return [
        'provider' => 'google',
        'provider_id' => (string) $profile['sub'],
        'email' => (string) ($profile['email'] ?? ''),
        'name' => (string) ($profile['name'] ?? ''),
    ];
}

function customer_auth_handle_google_callback(array $config, string $code, ?array $referrer = null): array
{
    $identity = customer_auth_fetch_google_identity($config, $code);

    return customer_auth_finish_provider_login(
        $identity['provider'],
        $identity['provider_id'],
        $identity['email'],
        $identity['name'],
        $referrer
    );
}

function customer_auth_handle_oauth_callback(string $provider, array $queryData, array $postData): array
{
    $storedState = $_SESSION['oauth_state'] ?? '';
    $storedProvider = $_SESSION['oauth_provider'] ?? '';
    $storedMode = $_SESSION['oauth_mode'] ?? 'login';
    $storedRedirectTo = $_SESSION['oauth_redirect_to'] ?? '/';
    $requestState = (string) ($queryData['state'] ?? $postData['state'] ?? '');
    $code = (string) ($queryData['code'] ?? $postData['code'] ?? '');

    if ($storedState === '' || !hash_equals($storedState, $requestState) || $storedProvider !== $provider) {
        throw new RuntimeException('Invalid OAuth state.');
    }

    if ($code === '') {
        throw new RuntimeException('Authorization code is missing.');
    }

    $config = customer_auth_provider_config($provider);
    $referrer = $storedMode === 'login'
        ? customer_auth_resolve_referrer_from_redirect_target((string) $storedRedirectTo)
        : null;

    try {
        if ($storedMode === 'connect') {
            if ($provider === 'google') {
                $identity = customer_auth_fetch_google_identity($config, $code);
                return customer_auth_link_provider_to_current_user($identity['provider'], $identity['provider_id']);
            }
        }

        if ($provider === 'google') {
            return customer_auth_handle_google_callback($config, $code, $referrer);
        }
    } finally {
        unset($_SESSION['oauth_state'], $_SESSION['oauth_provider'], $_SESSION['oauth_mode']);
    }

    throw new InvalidArgumentException('Unsupported provider.');
}

function customer_auth_redirect_after_oauth(): string
{
    $target = $_SESSION['oauth_redirect_to'] ?? '/';
    unset($_SESSION['oauth_redirect_to']);

    return customer_auth_normalize_redirect((string) $target, '/');
}
