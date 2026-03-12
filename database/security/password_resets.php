<?php

require_once __DIR__ . '/../../config/database.php';

function password_reset_ensure_table(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $pdo = get_database_connection();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_reset_tokens (
            token_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            account_type ENUM("customer", "admin") NOT NULL,
            account_id BIGINT(20) UNSIGNED NOT NULL,
            email VARCHAR(255) NOT NULL,
            selector VARCHAR(32) NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            requested_ip VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (token_id),
            UNIQUE KEY uniq_password_reset_selector (selector),
            KEY idx_password_reset_account (account_type, account_id),
            KEY idx_password_reset_email (account_type, email),
            KEY idx_password_reset_expiry (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );

    $ensured = true;
}

function password_reset_purge_expired(): void
{
    password_reset_ensure_table();

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'DELETE FROM password_reset_tokens
         WHERE used_at IS NOT NULL
            OR expires_at < NOW()
            OR created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'
    );
    $statement->execute();
}

function password_reset_create_token(string $accountType, int $accountId, string $email, array $meta = []): array
{
    password_reset_ensure_table();
    password_reset_purge_expired();

    if (!in_array($accountType, ['customer', 'admin'], true)) {
        throw new InvalidArgumentException('Unsupported account type.');
    }

    $pdo = get_database_connection();
    $selector = bin2hex(random_bytes(8));
    $verifier = bin2hex(random_bytes(32));
    $tokenHash = password_hash($verifier, PASSWORD_DEFAULT);
    $expiryStatement = $pdo->query('SELECT DATE_ADD(NOW(), INTERVAL 1 HOUR) AS expires_at');
    $expiresAt = (string) ($expiryStatement->fetchColumn() ?: '');
    if ($expiresAt === '') {
        throw new RuntimeException('Unable to create password reset expiry time.');
    }

    $deleteStatement = $pdo->prepare(
        'DELETE FROM password_reset_tokens
         WHERE account_type = :account_type
           AND account_id = :account_id'
    );
    $deleteStatement->execute([
        ':account_type' => $accountType,
        ':account_id' => $accountId,
    ]);

    $insertStatement = $pdo->prepare(
        'INSERT INTO password_reset_tokens (
            account_type,
            account_id,
            email,
            selector,
            token_hash,
            requested_ip,
            user_agent,
            expires_at
         ) VALUES (
            :account_type,
            :account_id,
            :email,
            :selector,
            :token_hash,
            :requested_ip,
            :user_agent,
            :expires_at
         )'
    );
    $insertStatement->execute([
        ':account_type' => $accountType,
        ':account_id' => $accountId,
        ':email' => mb_strtolower(trim($email)),
        ':selector' => $selector,
        ':token_hash' => $tokenHash,
        ':requested_ip' => trim((string) ($meta['ip'] ?? '')) ?: null,
        ':user_agent' => trim((string) ($meta['user_agent'] ?? '')) ?: null,
        ':expires_at' => $expiresAt,
    ]);

    return [
        'selector' => $selector,
        'token' => $verifier,
        'expires_at' => $expiresAt,
    ];
}

function password_reset_find_token(string $accountType, string $selector, string $verifier): ?array
{
    password_reset_ensure_table();
    password_reset_purge_expired();

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT *
         FROM password_reset_tokens
         WHERE account_type = :account_type
           AND selector = :selector
           AND used_at IS NULL
           AND expires_at >= NOW()
         LIMIT 1'
    );
    $statement->execute([
        ':account_type' => $accountType,
        ':selector' => trim($selector),
    ]);
    $row = $statement->fetch();
    if (!$row) {
        return null;
    }

    if (!password_verify(trim($verifier), (string) ($row['token_hash'] ?? ''))) {
        return null;
    }

    return $row;
}

function password_reset_mark_used(int $tokenId): void
{
    password_reset_ensure_table();

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'UPDATE password_reset_tokens
         SET used_at = NOW()
         WHERE token_id = :token_id'
    );
    $statement->execute([':token_id' => $tokenId]);
}

function password_reset_delete_for_account(string $accountType, int $accountId): void
{
    password_reset_ensure_table();

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'DELETE FROM password_reset_tokens
         WHERE account_type = :account_type
           AND account_id = :account_id'
    );
    $statement->execute([
        ':account_type' => $accountType,
        ':account_id' => $accountId,
    ]);
}
