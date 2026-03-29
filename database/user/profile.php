<?php

require_once __DIR__ . '/../../config/database.php';

function profile_fetch_user_addresses(int $userId): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT address_id, user_id, city, township, street, phone, postal_code, is_default, created_at
         FROM user_addresses
         WHERE user_id = :user_id
         ORDER BY is_default DESC, created_at DESC, address_id DESC'
    );
    $statement->execute([
        ':user_id' => $userId,
    ]);

    return $statement->fetchAll() ?: [];
}

function profile_find_address(int $userId, int $addressId): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT address_id, user_id, city, township, street, phone, postal_code, is_default, created_at
         FROM user_addresses
         WHERE user_id = :user_id AND address_id = :address_id
         LIMIT 1'
    );
    $statement->execute([
        ':user_id' => $userId,
        ':address_id' => $addressId,
    ]);

    $address = $statement->fetch();
    return $address ?: null;
}

function profile_user_by_id(int $userId): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('SELECT id, public_user_id, name, email FROM users WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $userId]);

    $user = $statement->fetch();
    return $user ?: null;
}

function profile_email_exists_for_other_user(string $email, int $userId): bool
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('SELECT 1 FROM users WHERE email = :email AND id <> :user_id LIMIT 1');
    $statement->execute([
        ':email' => mb_strtolower(trim($email)),
        ':user_id' => $userId,
    ]);

    return (bool) $statement->fetchColumn();
}

function profile_normalize_identity_payload(array $input): array
{
    $fullName = trim((string) ($input['full_name'] ?? ''));
    $email = mb_strtolower(trim((string) ($input['email'] ?? '')));

    if ($fullName === '') {
        throw new InvalidArgumentException('Full name is required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    return [
        'full_name' => $fullName,
        'email' => $email,
    ];
}

function profile_normalize_address_payload(array $input): array
{
    $payload = [
        'phone' => trim((string) ($input['phone'] ?? '')),
        'address' => trim((string) ($input['address'] ?? '')),
        'township' => trim((string) ($input['township'] ?? '')),
        'city' => trim((string) ($input['city'] ?? '')),
        'postal_code' => trim((string) ($input['postal_code'] ?? '')),
    ];

    if ($payload['phone'] === '' || $payload['address'] === '' || $payload['township'] === '' || $payload['city'] === '') {
        throw new InvalidArgumentException('Please complete phone, address, township, and city.');
    }

    return $payload;
}

function profile_apply_user_identity_update(PDO $pdo, int $userId, string $fullName, string $email): void
{
    if (profile_email_exists_for_other_user($email, $userId)) {
        throw new InvalidArgumentException('This email is already used by another account.');
    }

    $statement = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :user_id');
    $statement->execute([
        ':name' => $fullName,
        ':email' => $email,
        ':user_id' => $userId,
    ]);
}

function profile_update_user_identity(int $userId, array $input): array
{
    $payload = profile_normalize_identity_payload($input);
    $pdo = get_database_connection();

    profile_apply_user_identity_update($pdo, $userId, $payload['full_name'], $payload['email']);

    return profile_user_by_id($userId) ?? [];
}

function profile_save_address_card(int $userId, ?int $addressId, array $input): array
{
    $payload = profile_normalize_address_payload($input);
    $pdo = get_database_connection();

    $pdo->beginTransaction();

    try {
        $savedAddress = null;
        $existingAddressCount = count(profile_fetch_user_addresses($userId));

        if ($addressId !== null && $addressId > 0) {
            $existingAddress = profile_find_address($userId, $addressId);
            if (!$existingAddress) {
                throw new RuntimeException('Address not found.');
            }

            $statement = $pdo->prepare(
                'UPDATE user_addresses
                 SET city = :city,
                     township = :township,
                     street = :street,
                     phone = :phone,
                     postal_code = :postal_code
                 WHERE user_id = :user_id AND address_id = :address_id'
            );
            $statement->execute([
                ':city' => $payload['city'],
                ':township' => $payload['township'],
                ':street' => $payload['address'],
                ':phone' => $payload['phone'],
                ':postal_code' => $payload['postal_code'] !== '' ? $payload['postal_code'] : null,
                ':user_id' => $userId,
                ':address_id' => $addressId,
            ]);

            $savedAddress = profile_find_address($userId, $addressId);
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO user_addresses (user_id, city, township, street, phone, postal_code, is_default)
                 VALUES (:user_id, :city, :township, :street, :phone, :postal_code, :is_default)'
            );
            $statement->execute([
                ':user_id' => $userId,
                ':city' => $payload['city'],
                ':township' => $payload['township'],
                ':street' => $payload['address'],
                ':phone' => $payload['phone'],
                ':postal_code' => $payload['postal_code'] !== '' ? $payload['postal_code'] : null,
                ':is_default' => $existingAddressCount === 0 ? 1 : 0,
            ]);

            $savedAddress = profile_find_address($userId, (int) $pdo->lastInsertId());
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    return [
        'address' => $savedAddress,
    ];
}

function profile_delete_address_card(int $userId, ?int $addressId): array
{
    if ($addressId === null || $addressId <= 0) {
        throw new InvalidArgumentException('There is no saved address to delete.');
    }

    $pdo = get_database_connection();
    $address = profile_find_address($userId, $addressId);
    if (!$address) {
        throw new RuntimeException('Address not found.');
    }

    $pdo->beginTransaction();

    try {
        $statement = $pdo->prepare('DELETE FROM user_addresses WHERE user_id = :user_id AND address_id = :address_id');
        $statement->execute([
            ':user_id' => $userId,
            ':address_id' => $addressId,
        ]);

        $remaining = profile_fetch_user_addresses($userId);
        $nextDefaultId = null;

        if ($remaining !== [] && (int) $address['is_default'] === 1) {
            $nextDefaultId = (int) $remaining[0]['address_id'];
            $setDefault = $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id');
            $setDefault->execute([':user_id' => $userId]);

            $markDefault = $pdo->prepare('UPDATE user_addresses SET is_default = 1 WHERE user_id = :user_id AND address_id = :address_id');
            $markDefault->execute([
                ':user_id' => $userId,
                ':address_id' => $nextDefaultId,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    return [
        'deleted_address_id' => $addressId,
        'next_default_address_id' => $nextDefaultId,
        'remaining_count' => count($remaining),
    ];
}

function profile_set_default_address(int $userId, int $addressId): array
{
    $address = profile_find_address($userId, $addressId);
    if (!$address) {
        throw new RuntimeException('Address not found.');
    }

    $pdo = get_database_connection();
    $pdo->beginTransaction();

    try {
        $clear = $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id');
        $clear->execute([':user_id' => $userId]);

        $set = $pdo->prepare('UPDATE user_addresses SET is_default = 1 WHERE user_id = :user_id AND address_id = :address_id');
        $set->execute([
            ':user_id' => $userId,
            ':address_id' => $addressId,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    return profile_find_address($userId, $addressId);
}
