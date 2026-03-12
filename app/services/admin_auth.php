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
        $_SESSION['admin_auth_flash']
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
