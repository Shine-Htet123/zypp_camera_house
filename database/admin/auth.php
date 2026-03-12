<?php

require_once __DIR__ . '/../../config/database.php';

function admin_auth_columns(): array
{
    static $columns = null;

    if (is_array($columns)) {
        return $columns;
    }

    $pdo = get_database_connection();
    $statement = $pdo->query('SHOW COLUMNS FROM admins');

    $columns = [];
    foreach ($statement->fetchAll() as $column) {
        $field = $column['Field'] ?? null;
        if (is_string($field) && $field !== '') {
            $columns[$field] = true;
        }
    }

    return $columns;
}

function admin_auth_role_options(): array
{
    return [
        'super_admin' => 'Super-Admin',
        'admin' => 'Admin',
        'support' => 'Support',
    ];
}

function admin_auth_status_options(): array
{
    return [
        'active' => 'Active',
        'suspended' => 'Suspended',
        'banned' => 'Banned',
    ];
}

function admin_auth_role_label(string $role): string
{
    $options = admin_auth_role_options();
    return $options[$role] ?? ucfirst(str_replace('_', ' ', $role));
}

function admin_auth_status_label(string $status): string
{
    $options = admin_auth_status_options();
    return $options[$status] ?? ucfirst($status);
}

function admin_auth_has_profiles_table(PDO $pdo): bool
{
    static $hasTable = null;
    if ($hasTable !== null) {
        return $hasTable;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_profiles (
            admin_id BIGINT(20) UNSIGNED NOT NULL,
            full_name VARCHAR(150) NULL,
            phone VARCHAR(30) NULL,
            address VARCHAR(255) NULL,
            township VARCHAR(100) NULL,
            city VARCHAR(100) NULL,
            recovery_email VARCHAR(255) NULL,
            recovery_phone VARCHAR(30) NULL,
            last_password_changed_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (admin_id),
            CONSTRAINT fk_admin_profiles_admin
                FOREIGN KEY (admin_id) REFERENCES admins(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );
    $hasTable = true;

    return $hasTable;
}

function admin_auth_default_full_name(string $email): string
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

function admin_auth_fetch_admin_row_by(string $column, string|int $value): ?array
{
    $allowed = ['id', 'email'];

    if (!in_array($column, $allowed, true)) {
        throw new InvalidArgumentException('Unsupported admin lookup column.');
    }

    $pdo = get_database_connection();
    $hasProfiles = admin_auth_has_profiles_table($pdo);

    $query = $hasProfiles
        ? "SELECT a.*, ap.full_name, ap.recovery_email, ap.recovery_phone, ap.last_password_changed_at
           FROM admins a
           LEFT JOIN admin_profiles ap ON ap.admin_id = a.id
           WHERE a.{$column} = :value
           LIMIT 1"
        : "SELECT a.*, NULL AS full_name, NULL AS recovery_email, NULL AS recovery_phone, NULL AS last_password_changed_at
           FROM admins a
           WHERE a.{$column} = :value
           LIMIT 1";

    $statement = $pdo->prepare($query);
    $statement->execute([':value' => $value]);
    $row = $statement->fetch();

    return $row ?: null;
}

function admin_auth_admin_by_id(int $id): ?array
{
    $row = admin_auth_fetch_admin_row_by('id', $id);
    return $row ? admin_auth_map_admin_row($row) : null;
}

function admin_auth_admin_by_email(string $email): ?array
{
    $row = admin_auth_fetch_admin_row_by('email', mb_strtolower(trim($email)));
    return $row ? admin_auth_map_admin_row($row) : null;
}

function admin_auth_map_admin_row(array $row): array
{
    $fullName = trim((string) ($row['full_name'] ?? ''));
    if ($fullName === '') {
        $fullName = admin_auth_default_full_name((string) ($row['email'] ?? ''));
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'email' => (string) ($row['email'] ?? ''),
        'password' => (string) ($row['password'] ?? ''),
        'role_key' => (string) ($row['role'] ?? ''),
        'role' => admin_auth_role_label((string) ($row['role'] ?? '')),
        'status_key' => (string) ($row['status'] ?? ''),
        'status' => admin_auth_status_label((string) ($row['status'] ?? '')),
        'status_card_class' => strtolower((string) ($row['status'] ?? '')),
        'profile_img' => trim((string) ($row['profile_img'] ?? '')),
        'full_name' => $fullName,
        'recovery_email' => trim((string) ($row['recovery_email'] ?? '')),
        'recovery_phone' => trim((string) ($row['recovery_phone'] ?? '')),
        'last_password_changed_at' => $row['last_password_changed_at'] !== null ? (string) $row['last_password_changed_at'] : null,
        'created_at' => (string) ($row['created_at'] ?? ''),
    ];
}

function admin_auth_password_is_strong(string $password): bool
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password);
}

function admin_auth_verify_admin_password(array $admin, string $password, bool $upgradeLegacy = false): bool
{
    $storedPassword = (string) ($admin['password'] ?? '');
    if ($storedPassword === '') {
        return false;
    }

    $isPasswordValid = false;

    if (password_get_info($storedPassword)['algo'] !== null) {
        $isPasswordValid = password_verify($password, $storedPassword);
        if ($isPasswordValid && password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
            admin_auth_update_password((int) ($admin['id'] ?? 0), password_hash($password, PASSWORD_DEFAULT));
        }
    } else {
        $isPasswordValid = hash_equals($storedPassword, $password);
        if ($isPasswordValid && $upgradeLegacy) {
            admin_auth_update_password((int) ($admin['id'] ?? 0), password_hash($password, PASSWORD_DEFAULT));
        }
    }

    return $isPasswordValid;
}

function admin_auth_update_password(int $adminId, string $passwordHash): void
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('UPDATE admins SET password = :password WHERE id = :admin_id');
    $statement->execute([
        ':password' => $passwordHash,
        ':admin_id' => $adminId,
    ]);

    if (admin_auth_has_profiles_table($pdo)) {
        $profileStatement = $pdo->prepare(
            'INSERT INTO admin_profiles (admin_id, last_password_changed_at)
             VALUES (:admin_id, NOW())
             ON DUPLICATE KEY UPDATE last_password_changed_at = VALUES(last_password_changed_at)'
        );
        $profileStatement->execute([':admin_id' => $adminId]);
    }
}

function admin_auth_create_admin(array $payload): array
{
    $pdo = get_database_connection();
    $columns = ['email', 'password', 'role', 'profile_img', 'status'];
    $bindings = [
        ':email' => mb_strtolower(trim((string) $payload['email'])),
        ':password' => (string) $payload['password'],
        ':role' => (string) $payload['role'],
        ':profile_img' => $payload['profile_img'] ?? null,
        ':status' => (string) ($payload['status'] ?? 'active'),
    ];

    $statement = $pdo->prepare(
        sprintf(
            'INSERT INTO admins (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', array_keys($bindings))
        )
    );
    $statement->execute($bindings);

    $adminId = (int) $pdo->lastInsertId();

    $fullName = trim((string) ($payload['full_name'] ?? ''));
    if ($adminId > 0 && $fullName !== '' && admin_auth_has_profiles_table($pdo)) {
        $profileStatement = $pdo->prepare(
            'INSERT INTO admin_profiles (admin_id, full_name)
             VALUES (:admin_id, :full_name)
             ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)'
        );
        $profileStatement->execute([
            ':admin_id' => $adminId,
            ':full_name' => $fullName,
        ]);
    }

    return admin_auth_admin_by_id($adminId) ?? [];
}

function admin_auth_delete_existing_invites_for_email(string $email): void
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('DELETE FROM admin_invites WHERE email = :email AND used = 0');
    $statement->execute([':email' => mb_strtolower(trim($email))]);
}

function admin_auth_create_invite(array $payload): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'INSERT INTO admin_invites (email, role, token, invited_by, expires_at, used)
         VALUES (:email, :role, :token, :invited_by, :expires_at, 0)'
    );
    $statement->execute([
        ':email' => mb_strtolower(trim((string) $payload['email'])),
        ':role' => (string) $payload['role'],
        ':token' => (string) $payload['token'],
        ':invited_by' => (int) $payload['invited_by'],
        ':expires_at' => (string) $payload['expires_at'],
    ]);

    return admin_auth_invite_by_token((string) $payload['token']) ?? [];
}

function admin_auth_invite_by_token(string $token): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT ai.*, a.email AS inviter_email
         FROM admin_invites ai
         LEFT JOIN admins a ON a.id = ai.invited_by
         WHERE ai.token = :token
         LIMIT 1'
    );
    $statement->execute([':token' => $token]);
    $row = $statement->fetch();
    if (!$row) {
        return null;
    }

    return [
        'invite_id' => (int) $row['invite_id'],
        'email' => (string) $row['email'],
        'role_key' => (string) $row['role'],
        'role' => admin_auth_role_label((string) $row['role']),
        'token' => (string) $row['token'],
        'invited_by' => (int) $row['invited_by'],
        'inviter_email' => (string) ($row['inviter_email'] ?? ''),
        'expires_at' => (string) $row['expires_at'],
        'used' => (bool) ($row['used'] ?? false),
        'created_at' => (string) ($row['created_at'] ?? ''),
    ];
}

function admin_auth_mark_invite_used(int $inviteId): void
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('UPDATE admin_invites SET used = 1 WHERE invite_id = :invite_id');
    $statement->execute([':invite_id' => $inviteId]);
}

function admin_auth_list_admins(array $filters = []): array
{
    $pdo = get_database_connection();
    $hasProfiles = admin_auth_has_profiles_table($pdo);

    $query = $hasProfiles
        ? 'SELECT a.*, ap.full_name
           FROM admins a
           LEFT JOIN admin_profiles ap ON ap.admin_id = a.id
           WHERE 1 = 1'
        : 'SELECT a.*, NULL AS full_name
           FROM admins a
           WHERE 1 = 1';

    $bindings = [];
    $searchQuery = trim((string) ($filters['q'] ?? ''));
    if ($searchQuery !== '') {
        $searchBinding = '%' . $searchQuery . '%';
        if ($hasProfiles) {
            $query .= ' AND (
                CAST(a.id AS CHAR) LIKE :query_id
                OR a.email LIKE :query_email
                OR COALESCE(ap.full_name, "") LIKE :query_full_name
            )';
            $bindings[':query_id'] = $searchBinding;
            $bindings[':query_email'] = $searchBinding;
            $bindings[':query_full_name'] = $searchBinding;
        } else {
            $query .= ' AND (
                CAST(a.id AS CHAR) LIKE :query_id
                OR a.email LIKE :query_email
            )';
            $bindings[':query_id'] = $searchBinding;
            $bindings[':query_email'] = $searchBinding;
        }
    }

    $query .= ' ORDER BY a.id ASC';
    $statement = $pdo->prepare($query);
    $statement->execute($bindings);
    $rows = $statement->fetchAll();
    return array_map('admin_auth_map_admin_row', $rows);
}

function admin_auth_count_super_admins(): int
{
    $pdo = get_database_connection();
    $statement = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin'");
    return (int) $statement->fetchColumn();
}

function admin_auth_delete_admin(int $adminId): void
{
    $pdo = get_database_connection();

    $profileDelete = $pdo->prepare('DELETE FROM admin_profiles WHERE admin_id = :admin_id');
    if (!admin_auth_has_profiles_table($pdo)) {
        $profileDelete = null;
    }

    $inviteDelete = $pdo->prepare('DELETE FROM admin_invites WHERE invited_by = :admin_id');
    $adminDelete = $pdo->prepare('DELETE FROM admins WHERE id = :admin_id');

    $pdo->beginTransaction();
    try {
        $inviteDelete->execute([':admin_id' => $adminId]);
        if ($profileDelete) {
            $profileDelete->execute([':admin_id' => $adminId]);
        }
        $adminDelete->execute([':admin_id' => $adminId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

