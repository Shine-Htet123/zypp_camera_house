<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../database/admin/auth.php';
require_once __DIR__ . '/../../database/security/password_resets.php';
require_once __DIR__ . '/mailer.php';

function admin_auth_normalize_redirect(?string $target, string $fallback = ''): string
{
    if ($fallback === '') {
        $fallback = app_path('/admin/index.php');
    }

    $target = trim((string) $target);
    if ($target === '' || $target[0] !== '/' || str_starts_with($target, '//')) {
        return $fallback;
    }

    return $target;
}

function admin_auth_set_flash(string $message, string $type = 'error'): void
{
    $_SESSION['admin_auth_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function admin_auth_consume_flash(): ?array
{
    $flash = $_SESSION['admin_auth_flash'] ?? null;
    unset($_SESSION['admin_auth_flash']);

    return is_array($flash) ? $flash : null;
}

function admin_auth_is_json_request(): bool
{
    $requestedWith = strtolower(trim((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')));
    if ($requestedWith === 'xmlhttprequest') {
        return true;
    }

    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    return str_contains($accept, 'application/json');
}

function admin_auth_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function admin_auth_permission_matrix(): array
{
    static $matrix = null;

    if (is_array($matrix)) {
        return $matrix;
    }

    $matrix = [
        'super_admin' => [
            'view_dashboard',
            'manage_profile',
            'manage_catalog',
            'manage_discounts',
            'manage_discount_rules',
            'manage_orders',
            'manage_payment_proofs',
            'manage_delivery_methods',
            'manage_admins',
            'manage_customers',
            'manage_content',
            'manage_wholesale',
            'manage_media',
            'manage_membership_tiers',
            'manage_bundles',
        ],
        'admin' => [
            'view_dashboard',
            'manage_profile',
            'manage_catalog',
            'manage_discounts',
            'manage_orders',
            'manage_payment_proofs',
            'manage_delivery_methods',
            'manage_customers',
            'manage_content',
            'manage_wholesale',
            'manage_media',
            'manage_bundles',
        ],
        'support' => [
            'view_dashboard',
            'manage_profile',
            'manage_orders',
            'manage_payment_proofs',
            'manage_customers',
            'manage_wholesale',
        ],
    ];

    return $matrix;
}

function admin_auth_permission_label(string $permission): string
{
    static $labels = [
        'view_dashboard' => 'Dashboard',
        'manage_profile' => 'your account',
        'manage_catalog' => 'products and catalog setup',
        'manage_discounts' => 'discount management',
        'manage_discount_rules' => 'discount priority and stack rules',
        'manage_orders' => 'orders',
        'manage_payment_proofs' => 'payment proof uploads',
        'manage_delivery_methods' => 'delivery methods',
        'manage_admins' => 'admin accounts',
        'manage_customers' => 'customer records',
        'manage_content' => 'content management',
        'manage_wholesale' => 'wholesale survey',
        'manage_media' => 'media',
        'manage_membership_tiers' => 'membership tiers',
        'manage_bundles' => 'bundles',
    ];

    return $labels[$permission] ?? strtolower(str_replace('_', ' ', $permission));
}

function admin_auth_has_permission(array|string|null $adminOrRole, string $permission): bool
{
    if ($permission === '') {
        return true;
    }

    $roleKey = '';
    if (is_array($adminOrRole)) {
        $roleKey = (string) ($adminOrRole['role_key'] ?? $adminOrRole['role'] ?? '');
    } elseif (is_string($adminOrRole)) {
        $roleKey = trim($adminOrRole);
    }

    if ($roleKey === '') {
        return false;
    }

    $permissions = admin_auth_permission_matrix()[$roleKey] ?? [];
    return in_array($permission, $permissions, true);
}

function admin_auth_can_current_admin(string $permission): bool
{
    return admin_auth_has_permission(admin_auth_current_admin(), $permission);
}

function admin_auth_route_permission_map(): array
{
    static $map = null;

    if (is_array($map)) {
        return $map;
    }

    $map = [
        'index.php' => 'view_dashboard',
        'my-account.php' => 'manage_profile',
        'products.php' => 'manage_catalog',
        'product-add.php' => 'manage_catalog',
        'product-edit.php' => 'manage_catalog',
        'category-brand.php' => 'manage_catalog',
        'unique-selling-points.php' => 'manage_catalog',
        'discounts.php' => 'manage_discounts',
        'apply-discounts.php' => 'manage_discounts',
        'discount-stack-rules.php' => 'manage_discount_rules',
        'orders.php' => 'manage_orders',
        'orders-api.php' => 'manage_orders',
        'payment-proof-uploads.php' => 'manage_payment_proofs',
        'payment-proof-api.php' => 'manage_payment_proofs',
        'delivery-methods.php' => 'manage_delivery_methods',
        'admins.php' => 'manage_admins',
        'customers.php' => 'manage_customers',
        'content-management.php' => 'manage_content',
        'wholesale-survey.php' => 'manage_wholesale',
        'unboxing-influencers.php' => 'manage_media',
        'membership-tiers.php' => 'manage_membership_tiers',
        'bundles.php' => 'manage_bundles',
        'bundles-api.php' => 'manage_bundles',
    ];

    return $map;
}

function admin_auth_request_basename(string $requestUri): string
{
    $path = (string) parse_url($requestUri, PHP_URL_PATH);
    if ($path === '') {
        $path = $requestUri;
    }

    $path = str_replace('\\', '/', $path);
    return basename($path);
}

function admin_auth_is_admin_request_path(string $requestUri): bool
{
    $path = (string) parse_url($requestUri, PHP_URL_PATH);
    if ($path === '') {
        $path = $requestUri;
    }

    $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
    $adminBasePath = rtrim(app_path('/admin'), '/');

    return $adminBasePath !== '' && ($path === $adminBasePath || str_starts_with($path, $adminBasePath . '/'));
}

function admin_auth_permission_for_request(string $requestUri): ?string
{
    if (!admin_auth_is_admin_request_path($requestUri)) {
        return null;
    }

    $basename = admin_auth_request_basename($requestUri);
    if ($basename === '') {
        return null;
    }

    return admin_auth_route_permission_map()[$basename] ?? null;
}

function admin_auth_can_access_request(array|string|null $adminOrRole, string $requestUri): bool
{
    $permission = admin_auth_permission_for_request($requestUri);
    if ($permission === null) {
        return true;
    }

    return admin_auth_has_permission($adminOrRole, $permission);
}

function admin_auth_allowed_redirect_for_admin(array|string|null $adminOrRole, ?string $target, string $fallback = ''): string
{
    $normalized = admin_auth_normalize_redirect($target, $fallback);

    if (!admin_auth_is_admin_request_path($normalized)) {
        return $fallback !== '' ? $fallback : app_path('/admin/index.php');
    }

    if (!admin_auth_can_access_request($adminOrRole, $normalized)) {
        return $fallback !== '' ? $fallback : app_path('/admin/index.php');
    }

    return $normalized;
}

function admin_auth_render_access_denied(?array $admin, string $permission): never
{
    $message = 'Your account does not have access to ' . admin_auth_permission_label($permission) . '.';

    $requestBasename = admin_auth_request_basename($_SERVER['REQUEST_URI'] ?? '');
    if (admin_auth_is_json_request() || str_ends_with($requestBasename, '-api.php')) {
        admin_auth_json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');

    $adminName = htmlspecialchars((string) ($admin['full_name'] ?? $admin['email'] ?? 'Admin'), ENT_QUOTES, 'UTF-8');
    $roleLabel = htmlspecialchars((string) ($admin['role'] ?? 'Admin'), ENT_QUOTES, 'UTF-8');
    $dashboardUrl = htmlspecialchars(app_path('/admin/index.php'), ENT_QUOTES, 'UTF-8');
    $logoutUrl = htmlspecialchars(app_path('/admin/logout.php?redirect_to=' . rawurlencode(app_path('/index.php'))), ENT_QUOTES, 'UTF-8');
    $messageHtml = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <style>
        body{margin:0;font-family:Inter,Arial,sans-serif;background:#f5f5f5;color:#2f2118;display:grid;place-items:center;min-height:100vh;padding:24px}
        .card{width:min(100%,520px);background:#fff;border-radius:20px;padding:32px;box-shadow:0 20px 40px rgba(0,0,0,.08)}
        h1{margin:0 0 12px;font-size:1.9rem}
        p{margin:0 0 12px;line-height:1.6}
        .meta{color:#7d6d63;font-size:.95rem}
        .actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:24px}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 18px;border-radius:10px;text-decoration:none;font-weight:700}
        .btn-primary{background:#5D4E47;color:#fff}
        .btn-secondary{background:#f0ece9;color:#5D4E47}
    </style>
</head>
<body>
    <main class="card">
        <h1>Access denied</h1>
        <p>' . $messageHtml . '</p>
        <p class="meta">Signed in as ' . $adminName . ' (' . $roleLabel . ').</p>
        <div class="actions">
            <a class="btn btn-primary" href="' . $dashboardUrl . '">Go to dashboard</a>
            <a class="btn btn-secondary" href="' . $logoutUrl . '">Log out</a>
        </div>
    </main>
</body>
</html>';
    exit;
}

function admin_auth_authorize_request(array $admin, string $requestUri): void
{
    $permission = admin_auth_permission_for_request($requestUri);
    if ($permission === null) {
        return;
    }

    if (!admin_auth_has_permission($admin, $permission)) {
        admin_auth_render_access_denied($admin, $permission);
    }
}

function admin_auth_login_admin(array $admin, bool $regenerateSessionId = false): void
{
    if ($regenerateSessionId && session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['admin_id'] = (int) ($admin['id'] ?? 0);
    $_SESSION['admin_name'] = (string) ($admin['full_name'] ?? '');
    $_SESSION['admin_role'] = (string) ($admin['role'] ?? '');
    $_SESSION['admin_avatar'] = (string) ($admin['profile_img'] ?? '');
}

function admin_auth_logout_admin(): void
{
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_role'],
        $_SESSION['admin_avatar'],
        $_SESSION['admin_auth_flash'],
        $_SESSION['admin_show_fullscreen_loader_once']
    );
}

function admin_auth_current_admin(): ?array
{
    $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
    if ($adminId <= 0) {
        return null;
    }

    $admin = admin_auth_admin_by_id($adminId);
    if (!$admin) {
        admin_auth_logout_admin();
        return null;
    }

    if (($admin['status_key'] ?? '') !== 'active') {
        admin_auth_logout_admin();
        admin_auth_set_flash('Your admin account is not active.');
        return null;
    }

    admin_auth_login_admin($admin, false);
    return $admin;
}

function admin_auth_require_login(string $redirectAfter = ''): array
{
    if ($redirectAfter === '') {
        $redirectAfter = app_path('/admin/index.php');
    }

    $admin = admin_auth_current_admin();
    if ($admin) {
        return $admin;
    }

    $loginUrl = app_path('/admin/login.php?redirect_to=' . rawurlencode(admin_auth_normalize_redirect($redirectAfter, app_path('/admin/index.php'))));
    header('Location: ' . $loginUrl);
    exit;
}

function admin_auth_require_super_admin(): array
{
    $admin = admin_auth_require_login($_SERVER['REQUEST_URI'] ?? app_path('/admin/index.php'));
    if (($admin['role_key'] ?? '') !== 'super_admin') {
        throw new RuntimeException('Only super-admins can perform that action.');
    }

    return $admin;
}

function admin_auth_login(array $input): array
{
    $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
    $password = trim((string) ($input['password'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        throw new InvalidArgumentException('Incorrect email or password.');
    }

    $admin = admin_auth_admin_by_email($email);
    if (!$admin) {
        throw new InvalidArgumentException('Incorrect email or password.');
    }

    $isPasswordValid = admin_auth_verify_admin_password($admin, $password, true);
    if ($isPasswordValid) {
        $admin = admin_auth_admin_by_id((int) $admin['id']) ?? $admin;
    }

    if (!$isPasswordValid) {
        throw new InvalidArgumentException('Incorrect email or password.');
    }

    if (($admin['status_key'] ?? '') === 'suspended') {
        throw new RuntimeException('Your admin account is suspended.');
    }

    if (($admin['status_key'] ?? '') === 'banned') {
        throw new RuntimeException('Your admin account is banned.');
    }

    admin_auth_login_admin($admin, true);
    return $admin;
}

function admin_auth_validate_password_input(string $password, string $confirmPassword): void
{
    if (!admin_auth_password_is_strong($password)) {
        throw new InvalidArgumentException('Password must be 8+ chars with upper, lower, number, and special character.');
    }

    if ($password !== $confirmPassword) {
        throw new InvalidArgumentException('Password confirmation does not match.');
    }
}

function admin_auth_validate_invite(array $input): array
{
    $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
    $role = trim((string) ($input['role'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    if (!isset(admin_auth_role_options()[$role])) {
        throw new InvalidArgumentException('Please choose a valid admin role.');
    }

    if (admin_auth_admin_by_email($email)) {
        throw new InvalidArgumentException('An admin with that email already exists.');
    }

    return [
        'email' => $email,
        'role' => $role,
    ];
}

function admin_auth_create_invite_link(array $input): array
{
    $currentAdmin = admin_auth_require_super_admin();
    $validated = admin_auth_validate_invite($input);

    admin_auth_delete_existing_invites_for_email($validated['email']);

    $token = bin2hex(random_bytes(24));
    $expiresAt = (new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s');

    $invite = admin_auth_create_invite([
        'email' => $validated['email'],
        'role' => $validated['role'],
        'token' => $token,
        'invited_by' => (int) $currentAdmin['id'],
        'expires_at' => $expiresAt,
    ]);

    $invite['invite_url'] = app_url('/admin/invite-accept.php?token=' . rawurlencode($token));
    admin_auth_send_invite_email($invite, $currentAdmin);

    return $invite;
}

function admin_auth_send_invite_email(array $invite, array $sender): void
{
    $inviteUrl = (string) ($invite['invite_url'] ?? '');
    if ($inviteUrl === '') {
        throw new RuntimeException('Invite URL could not be generated.');
    }

    $roleLabel = admin_auth_role_label((string) ($invite['role_key'] ?? $invite['role'] ?? 'admin'));
    $senderName = trim((string) ($sender['full_name'] ?? $sender['email'] ?? 'ZYPP Camera House'));
    $expiresAt = trim((string) ($invite['expires_at'] ?? ''));

    $subject = 'You have been invited to the ZYPP admin panel';
    $html = '
        <div style="font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#222;">
            <h2 style="margin:0 0 16px;">Admin Invitation</h2>
            <p>You have been invited by <strong>' . htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') . '</strong> to join the ZYPP admin panel as <strong>' . htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') . '</strong>.</p>
            <p>Click the button below to accept the invite and set your password.</p>
            <p style="margin:24px 0;">
                <a href="' . htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#6c5548;color:#fff;text-decoration:none;padding:12px 20px;border-radius:8px;display:inline-block;">Accept Invite</a>
            </p>
            <p>If the button does not work, use this link:</p>
            <p><a href="' . htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8') . '</a></p>
            ' . ($expiresAt !== '' ? '<p><strong>Expires:</strong> ' . htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') . '</p>' : '') . '
        </div>';
    $text = "You have been invited by {$senderName} to join the ZYPP admin panel as {$roleLabel}.\n\nAccept invite:\n{$inviteUrl}\n\n" .
        ($expiresAt !== '' ? "Expires: {$expiresAt}\n" : '');

    mailer_send([
        'to_email' => (string) ($invite['email'] ?? ''),
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ]);
}

function admin_auth_password_reset_target_email(array $admin): string
{
    $recoveryEmail = trim((string) ($admin['recovery_email'] ?? ''));
    if ($recoveryEmail !== '' && filter_var($recoveryEmail, FILTER_VALIDATE_EMAIL)) {
        return $recoveryEmail;
    }

    return (string) ($admin['email'] ?? '');
}

function admin_auth_send_password_reset_email(array $admin, string $selector, string $token, string $expiresAt): void
{
    $targetEmail = admin_auth_password_reset_target_email($admin);
    $resetUrl = app_url('/admin/reset-password.php?selector=' . rawurlencode($selector) . '&token=' . rawurlencode($token));
    $adminName = trim((string) ($admin['full_name'] ?? $admin['email'] ?? 'Admin'));

    mailer_send([
        'to_email' => $targetEmail,
        'to_name' => $adminName,
        'subject' => 'Reset your ZYPP admin password',
        'html' => '
            <div style="font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#222;">
                <h2 style="margin:0 0 16px;">Admin Password Reset</h2>
                <p>Hello ' . htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') . ',</p>
                <p>We received a request to reset your ZYPP admin password.</p>
                <p style="margin:24px 0;">
                    <a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#6c5548;color:#fff;text-decoration:none;padding:12px 20px;border-radius:8px;display:inline-block;">Reset Password</a>
                </p>
                <p>If the button does not work, open this link:</p>
                <p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '</a></p>
                <p>This link expires on ' . htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') . '.</p>
                <p>If you did not request this, you can ignore this email.</p>
            </div>',
        'text' => "Hello {$adminName},\n\nWe received a request to reset your ZYPP admin password.\n\nReset password:\n{$resetUrl}\n\nThis link expires on {$expiresAt}.\nIf you did not request this, you can ignore this email.",
    ]);
}

function admin_auth_request_password_reset(array $input): void
{
    $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    if (!mailer_is_configured()) {
        throw new RuntimeException('Password reset email is not configured yet.');
    }

    $admin = admin_auth_admin_by_email($email);
    if (!$admin) {
        return;
    }

    $token = password_reset_create_token('admin', (int) $admin['id'], (string) $admin['email'], [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);

    admin_auth_send_password_reset_email($admin, $token['selector'], $token['token'], (string) $token['expires_at']);
}

function admin_auth_validate_password_reset_token(string $selector, string $token): array
{
    $selector = trim($selector);
    $token = trim($token);
    if ($selector === '' || $token === '') {
        throw new InvalidArgumentException('Password reset token is missing.');
    }

    $row = password_reset_find_token('admin', $selector, $token);
    if (!$row) {
        throw new RuntimeException('This password reset link is invalid or expired.');
    }

    $admin = admin_auth_admin_by_id((int) ($row['account_id'] ?? 0));
    if (!$admin) {
        throw new RuntimeException('The admin account for this reset link no longer exists.');
    }

    return [
        'token_id' => (int) $row['token_id'],
        'admin' => $admin,
        'email' => (string) ($row['email'] ?? $admin['email'] ?? ''),
        'expires_at' => (string) ($row['expires_at'] ?? ''),
    ];
}

function admin_auth_reset_password(array $input): void
{
    $password = trim((string) ($input['password'] ?? ''));
    $confirmPassword = trim((string) ($input['confirm_password'] ?? ''));
    $tokenData = admin_auth_validate_password_reset_token(
        (string) ($input['selector'] ?? ''),
        (string) ($input['token'] ?? '')
    );

    admin_auth_validate_password_input($password, $confirmPassword);

    admin_auth_update_password((int) $tokenData['admin']['id'], password_hash($password, PASSWORD_DEFAULT));
    password_reset_mark_used((int) $tokenData['token_id']);
    password_reset_delete_for_account('admin', (int) $tokenData['admin']['id']);
}

function admin_auth_validate_invite_token(string $token): array
{
    $token = trim($token);
    if ($token === '') {
        throw new InvalidArgumentException('Invite token is missing.');
    }

    $invite = admin_auth_invite_by_token($token);
    if (!$invite) {
        throw new RuntimeException('This invite link is invalid.');
    }

    if (!empty($invite['used'])) {
        throw new RuntimeException('This invite link has already been used.');
    }

    $expiresAt = strtotime((string) $invite['expires_at']);
    if ($expiresAt !== false && $expiresAt < time()) {
        throw new RuntimeException('This invite link has expired.');
    }

    return $invite;
}

function admin_auth_accept_invite(array $invite, array $input): array
{
    $fullName = trim((string) ($input['full_name'] ?? ''));
    $password = trim((string) ($input['password'] ?? ''));
    $confirmPassword = trim((string) ($input['confirm_password'] ?? ''));

    if ($fullName === '') {
        throw new InvalidArgumentException('Full name is required.');
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)) {
        throw new InvalidArgumentException('Password must be 8+ chars with upper, lower, number, and special character.');
    }

    if ($password !== $confirmPassword) {
        throw new InvalidArgumentException('Password confirmation does not match.');
    }

    if (admin_auth_admin_by_email((string) $invite['email'])) {
        throw new RuntimeException('An admin account with this email already exists.');
    }

    $admin = admin_auth_create_admin([
        'email' => (string) $invite['email'],
        'full_name' => $fullName,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => (string) $invite['role_key'],
        'profile_img' => null,
        'status' => 'active',
    ]);

    admin_auth_mark_invite_used((int) $invite['invite_id']);
    admin_auth_login_admin($admin, true);

    return $admin;
}

function admin_auth_delete_admin_account(int $adminId): void
{
    $currentAdmin = admin_auth_require_super_admin();
    if ($adminId <= 0) {
        throw new InvalidArgumentException('Invalid admin id.');
    }

    if ($adminId === (int) $currentAdmin['id']) {
        throw new RuntimeException('You cannot delete your own admin account.');
    }

    $target = admin_auth_admin_by_id($adminId);
    if (!$target) {
        throw new RuntimeException('Admin account not found.');
    }

    if (($target['role_key'] ?? '') === 'super_admin' && admin_auth_count_super_admins() <= 1) {
        throw new RuntimeException('You cannot delete the last super-admin account.');
    }

    admin_auth_delete_admin($adminId);
}
