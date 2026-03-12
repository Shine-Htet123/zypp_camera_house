<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/auth.php';

function admin_my_account_has_profiles_table(PDO $pdo): bool
{
    return admin_auth_has_profiles_table($pdo);
}

function admin_role_label(string $role): string
{
    $role = trim($role);
    if ($role === '') {
        return 'Admin';
    }

    return ucfirst(str_replace('_', '-', strtolower($role)));
}

function admin_default_full_name(string $email): string
{
    $local = trim((string) strstr($email, '@', true));
    if ($local === '') {
        return 'Admin User';
    }

    $parts = preg_split('/[._-]+/', $local) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts), static fn (string $part): bool => $part !== ''));
    if ($parts === []) {
        return 'Admin User';
    }

    return implode(' ', array_map(static fn (string $part): string => ucfirst($part), $parts));
}

function admin_get_current_admin_id(): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!empty($_SESSION['admin_id'])) {
        return (int) $_SESSION['admin_id'];
    }

    throw new RuntimeException('Admin session not found.');
}

function admin_ensure_profile_row(PDO $pdo, int $adminId): void
{
    if (!admin_my_account_has_profiles_table($pdo)) {
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO admin_profiles (admin_id)
         VALUES (:admin_id)
         ON DUPLICATE KEY UPDATE admin_id = VALUES(admin_id)'
    );
    $statement->execute([':admin_id' => $adminId]);
}

function admin_fetch_current_profile(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $pdo = get_database_connection();
    $adminId = admin_get_current_admin_id();
    $hasProfiles = admin_my_account_has_profiles_table($pdo);

    if ($hasProfiles) {
        admin_ensure_profile_row($pdo, $adminId);
    }

    $query = $hasProfiles
        ? "SELECT
                a.id,
                a.email,
                a.role,
                a.profile_img,
                a.created_at,
                ap.full_name,
                ap.phone,
                ap.address,
                ap.township,
                ap.city,
                ap.recovery_email,
                ap.recovery_phone,
                ap.last_password_changed_at
           FROM admins a
           LEFT JOIN admin_profiles ap ON ap.admin_id = a.id
           WHERE a.id = :admin_id
           LIMIT 1"
        : "SELECT
                a.id,
                a.email,
                a.role,
                a.profile_img,
                a.created_at,
                NULL AS full_name,
                NULL AS phone,
                NULL AS address,
                NULL AS township,
                NULL AS city,
                NULL AS recovery_email,
                NULL AS recovery_phone,
                NULL AS last_password_changed_at
           FROM admins a
           WHERE a.id = :admin_id
           LIMIT 1";

    $statement = $pdo->prepare($query);
    $statement->execute([':admin_id' => $adminId]);
    $row = $statement->fetch();
    if (!$row) {
        throw new RuntimeException('Admin account not found.');
    }

    $fullName = trim((string) ($row['full_name'] ?? ''));
    if ($fullName === '') {
        $fullName = admin_default_full_name((string) $row['email']);
    }

    $recoveryEmail = trim((string) ($row['recovery_email'] ?? ''));
    if ($recoveryEmail === '') {
        $recoveryEmail = (string) $row['email'];
    }

    $recoveryPhone = trim((string) ($row['recovery_phone'] ?? ''));
    $phone = trim((string) ($row['phone'] ?? ''));
    if ($recoveryPhone === '') {
        $recoveryPhone = $phone;
    }

    return [
        'id' => (int) $row['id'],
        'full_name' => $fullName,
        'role' => admin_role_label((string) $row['role']),
        'role_key' => (string) $row['role'],
        'email' => (string) $row['email'],
        'phone' => $phone,
        'address' => trim((string) ($row['address'] ?? '')),
        'township' => trim((string) ($row['township'] ?? '')),
        'city' => trim((string) ($row['city'] ?? '')),
        'created_at' => (string) $row['created_at'],
        'avatar' => trim((string) ($row['profile_img'] ?? '')),
        'recovery_email' => $recoveryEmail,
        'recovery_phone' => $recoveryPhone,
        'last_password_changed_at' => $row['last_password_changed_at'] !== null ? (string) $row['last_password_changed_at'] : null,
        'profiles_table_available' => $hasProfiles,
    ];
}

function admin_my_account_sync_session(array $profile): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['admin_id'] = (int) ($profile['id'] ?? 0);
    $_SESSION['admin_name'] = (string) ($profile['full_name'] ?? '');
    $_SESSION['admin_role'] = (string) ($profile['role'] ?? '');
    $_SESSION['admin_avatar'] = (string) ($profile['avatar'] ?? '');
}

function admin_my_account_validate_profile(array $input): array
{
    $fullName = trim((string) ($input['full_name'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $address = trim((string) ($input['address'] ?? ''));
    $township = trim((string) ($input['township'] ?? ''));
    $city = trim((string) ($input['city'] ?? ''));

    if ($fullName === '') {
        throw new InvalidArgumentException('Full name is required.');
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('A valid email address is required.');
    }

    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,30}$/', $phone)) {
        throw new InvalidArgumentException('Phone number is invalid.');
    }

    return [
        'full_name' => $fullName,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
        'township' => $township,
        'city' => $city,
    ];
}

function admin_update_profile(array $input): array
{
    $pdo = get_database_connection();
    if (!admin_my_account_has_profiles_table($pdo)) {
        throw new RuntimeException('admin_profiles table is required. Run the migration first.');
    }

    $adminId = admin_get_current_admin_id();
    $validated = admin_my_account_validate_profile($input);
    admin_ensure_profile_row($pdo, $adminId);

    $emailCheck = $pdo->prepare('SELECT id FROM admins WHERE email = :email AND id <> :admin_id LIMIT 1');
    $emailCheck->execute([
        ':email' => $validated['email'],
        ':admin_id' => $adminId,
    ]);
    if ($emailCheck->fetch()) {
        throw new InvalidArgumentException('That email address is already in use.');
    }

    $pdo->beginTransaction();
    try {
        $adminStatement = $pdo->prepare('UPDATE admins SET email = :email WHERE id = :admin_id');
        $adminStatement->execute([
            ':email' => $validated['email'],
            ':admin_id' => $adminId,
        ]);

        $profileStatement = $pdo->prepare(
            'UPDATE admin_profiles
             SET full_name = :full_name,
                 phone = :phone,
                 address = :address,
                 township = :township,
                 city = :city
             WHERE admin_id = :admin_id'
        );
        $profileStatement->execute([
            ':full_name' => $validated['full_name'],
            ':phone' => $validated['phone'] !== '' ? $validated['phone'] : null,
            ':address' => $validated['address'] !== '' ? $validated['address'] : null,
            ':township' => $validated['township'] !== '' ? $validated['township'] : null,
            ':city' => $validated['city'] !== '' ? $validated['city'] : null,
            ':admin_id' => $adminId,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    $profile = admin_fetch_current_profile();
    admin_my_account_sync_session($profile);

    return $profile;
}

function admin_update_security_contacts(array $input): array
{
    $pdo = get_database_connection();
    if (!admin_my_account_has_profiles_table($pdo)) {
        throw new RuntimeException('admin_profiles table is required. Run the migration first.');
    }

    $adminId = admin_get_current_admin_id();
    admin_ensure_profile_row($pdo, $adminId);

    $recoveryEmail = trim((string) ($input['recovery_email'] ?? ''));
    $recoveryPhone = trim((string) ($input['recovery_phone'] ?? ''));
    $currentPassword = trim((string) ($input['current_password'] ?? ''));
    $newPassword = trim((string) ($input['new_password'] ?? ''));
    $confirmPassword = trim((string) ($input['confirm_password'] ?? ''));

    if ($recoveryEmail === '' || !filter_var($recoveryEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('A valid recovery email is required.');
    }

    if ($recoveryPhone !== '' && !preg_match('/^[0-9+\-\s()]{6,30}$/', $recoveryPhone)) {
        throw new InvalidArgumentException('Recovery phone number is invalid.');
    }

    if ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '') {
        if ($currentPassword === '') {
            throw new InvalidArgumentException('Current password is required to change your password.');
        }

        admin_auth_validate_password_input($newPassword, $confirmPassword);

        $admin = admin_auth_admin_by_id($adminId);
        if (!$admin) {
            throw new RuntimeException('Admin account not found.');
        }

        if (!admin_auth_verify_admin_password($admin, $currentPassword, true)) {
            throw new InvalidArgumentException('Current password is incorrect.');
        }

        if (hash_equals($currentPassword, $newPassword)) {
            throw new InvalidArgumentException('New password must be different from your current password.');
        }

        admin_auth_update_password($adminId, password_hash($newPassword, PASSWORD_DEFAULT));
    }

    $statement = $pdo->prepare(
        'UPDATE admin_profiles
         SET recovery_email = :recovery_email,
             recovery_phone = :recovery_phone
         WHERE admin_id = :admin_id'
    );
    $statement->execute([
        ':recovery_email' => $recoveryEmail,
        ':recovery_phone' => $recoveryPhone !== '' ? $recoveryPhone : null,
        ':admin_id' => $adminId,
    ]);

    return admin_fetch_current_profile();
}

function admin_avatar_store_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Please choose an image to upload.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new InvalidArgumentException('Invalid upload.');
    }

    $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new InvalidArgumentException('Avatar must be a JPG, PNG, or WEBP image.');
    }

    $storageDir = dirname(__DIR__, 2) . '/storage/uploads/admins';
    if (!is_dir($storageDir) && !mkdir($storageDir, 0777, true) && !is_dir($storageDir)) {
        throw new RuntimeException('Unable to create avatar storage directory.');
    }

    $filename = 'admin-avatar-' . date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.' . $extension;
    $targetPath = $storageDir . '/' . $filename;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Failed to save the uploaded avatar.');
    }

    return '/storage/uploads/admins/' . $filename;
}

function admin_update_avatar(array $file): array
{
    $pdo = get_database_connection();
    $adminId = admin_get_current_admin_id();
    $profile = admin_fetch_current_profile();

    $newPath = admin_avatar_store_upload($file);

    $statement = $pdo->prepare('UPDATE admins SET profile_img = :profile_img WHERE id = :admin_id');
    $statement->execute([
        ':profile_img' => $newPath,
        ':admin_id' => $adminId,
    ]);

    $oldPath = trim((string) ($profile['avatar'] ?? ''));
    if ($oldPath !== '' && str_starts_with($oldPath, '/storage/uploads/admins/')) {
        $oldFile = dirname(__DIR__, 2) . $oldPath;
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    $updated = admin_fetch_current_profile();
    admin_my_account_sync_session($updated);

    return $updated;
}
