<?php

require_once __DIR__ . '/../../config/database.php';

function auth_user_columns(): array
{
    static $columns = null;

    if (is_array($columns)) {
        return $columns;
    }

    $pdo = get_database_connection();
    $statement = $pdo->query('SHOW COLUMNS FROM users');

    $columns = [];
    foreach ($statement->fetchAll() as $column) {
        $field = $column['Field'] ?? null;
        if (is_string($field) && $field !== '') {
            $columns[$field] = true;
        }
    }

    return $columns;
}

function auth_provider_column(string $provider): string
{
    $columnMap = [
        'google' => 'google_provider_id',
    ];

    if (!isset($columnMap[$provider])) {
        throw new InvalidArgumentException('Unsupported provider.');
    }

    return $columnMap[$provider];
}

function auth_user_by_id(int $id): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $id]);
    $user = $statement->fetch();

    return $user ?: null;
}

function auth_user_by_email(string $email): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $statement->execute([':email' => mb_strtolower(trim($email))]);
    $user = $statement->fetch();

    return $user ?: null;
}

function auth_password_is_strong(string $password): bool
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password);
}

function auth_verify_user_password(array $user, string $password): bool
{
    $storedPassword = (string) ($user['password'] ?? '');
    if ($storedPassword === '') {
        return false;
    }

    if (password_get_info($storedPassword)['algo'] !== null) {
        return password_verify($password, $storedPassword);
    }

    return hash_equals($storedPassword, $password);
}

function auth_update_user_password(int $userId, string $passwordHash): void
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('UPDATE users SET password = :password WHERE id = :user_id');
    $statement->execute([
        ':password' => $passwordHash,
        ':user_id' => $userId,
    ]);
}

function auth_user_by_provider_id(string $provider, string $providerId): ?array
{
    $providerColumn = auth_provider_column($provider);
    if (!isset(auth_user_columns()[$providerColumn])) {
        throw new RuntimeException('Social login is not available until the user provider columns are added to the database.');
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        sprintf('SELECT * FROM users WHERE %s = :provider_id LIMIT 1', $providerColumn)
    );
    $statement->execute([':provider_id' => $providerId]);
    $user = $statement->fetch();

    return $user ?: null;
}

function auth_public_user_id_exists(string $publicUserId): bool
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('SELECT 1 FROM users WHERE public_user_id = :public_user_id LIMIT 1');
    $statement->execute([':public_user_id' => $publicUserId]);

    return (bool) $statement->fetchColumn();
}

function auth_next_public_user_sequence(string $dateStamp, string $prefix = 'ZCU'): int
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT public_user_id
         FROM users
         WHERE public_user_id LIKE :public_user_id_prefix
         ORDER BY public_user_id DESC
         LIMIT 1'
    );
    $statement->execute([
        ':public_user_id_prefix' => $prefix . $dateStamp . '%',
    ]);

    $lastId = (string) ($statement->fetchColumn() ?: '');
    if ($lastId === '') {
        return 1;
    }

    $sequence = (int) substr($lastId, -4);
    return $sequence + 1;
}

function auth_referral_token_exists(string $token): bool
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('SELECT 1 FROM users WHERE referral_token = :referral_token LIMIT 1');
    $statement->execute([':referral_token' => $token]);

    return (bool) $statement->fetchColumn();
}

function auth_generate_public_user_id(): string
{
    $prefix = 'ZCU';
    $dateStamp = date('Ymd');
    $sequence = auth_next_public_user_sequence($dateStamp, $prefix);

    do {
        $candidate = $prefix . $dateStamp . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        $sequence++;
    } while (auth_public_user_id_exists($candidate));

    return $candidate;
}

function auth_generate_referral_token(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    do {
        $segment = '';
        $maxIndex = strlen($alphabet) - 1;

        for ($i = 0; $i < 8; $i++) {
            $segment .= $alphabet[random_int(0, $maxIndex)];
        }

        $candidate = 'ZYPP-' . $segment;
    } while (auth_referral_token_exists($candidate));

    return $candidate;
}

function auth_create_user(array $payload): array
{
    $pdo = get_database_connection();
    $availableColumns = auth_user_columns();

    $insertable = [
        'public_user_id',
        'name',
        'email',
        'password',
        'membership_tier_id',
        'status',
        'referral_token',
        'referred_by_user_id',
        'google_provider_id',
    ];

    $columns = [];
    $bindings = [];

    foreach ($insertable as $column) {
        if (!isset($availableColumns[$column]) || !array_key_exists($column, $payload)) {
            continue;
        }

        $columns[] = $column;
        $bindings[':' . $column] = $payload[$column];
    }

    if ($columns === []) {
        throw new RuntimeException('Unable to determine insertable user columns.');
    }

    $statement = $pdo->prepare(
        sprintf(
            'INSERT INTO users (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', array_keys($bindings))
        )
    );
    $statement->execute($bindings);

    return auth_user_by_id((int) $pdo->lastInsertId());
}

function auth_update_provider_id(int $userId, string $provider, string $providerId): array
{
    $providerColumn = auth_provider_column($provider);
    if (!isset(auth_user_columns()[$providerColumn])) {
        throw new RuntimeException('Social login is not available until the user provider columns are added to the database.');
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        sprintf('UPDATE users SET %s = :provider_id WHERE id = :user_id', $providerColumn)
    );
    $statement->execute([
        ':provider_id' => $providerId,
        ':user_id' => $userId,
    ]);

    return auth_user_by_id($userId);
}
