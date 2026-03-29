<?php

require_once __DIR__ . '/../../config/database.php';

function customer_status_label(string $status): string
{
    return ucfirst(strtolower($status));
}

function customer_member_level(?string $tierName, int $referralCount): string
{
    $tierName = trim((string) $tierName);
    if ($tierName !== '') {
        return $tierName;
    }

    if ($referralCount > 0) {
        return 'Referral Customer';
    }

    return 'Standard Customer';
}

function update_customer_status_by_public_id(string $customerId, string $status): array
{
    $customerId = trim($customerId);
    $status = strtolower(trim($status));
    $allowedStatuses = ['active', 'suspended', 'banned'];

    if ($customerId === '' || !in_array($status, $allowedStatuses, true)) {
        throw new InvalidArgumentException('Invalid customer or status.');
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare('UPDATE users SET status = :status WHERE public_user_id = :customer_id');
    $statement->execute([
        ':status' => $status,
        ':customer_id' => $customerId,
    ]);

    if ($statement->rowCount() === 0) {
        $checkStatement = $pdo->prepare('SELECT id FROM users WHERE public_user_id = :customer_id LIMIT 1');
        $checkStatement->execute([':customer_id' => $customerId]);
        if (!$checkStatement->fetchColumn()) {
            throw new RuntimeException('Customer not found.');
        }
    }

    return [
        'success' => true,
        'status' => $status,
        'status_label' => customer_status_label($status),
    ];
}

function fetch_admin_customers(array $filters = []): array
{
    $pdo = get_database_connection();
    $query = <<<SQL
        SELECT
            u.public_user_id,
            u.name,
            u.email,
            u.status,
            u.created_at,
            mt.tier_name,
            COALESCE(default_address.phone, '') AS phone,
            COALESCE(default_address.township, '') AS township,
            COALESCE(default_address.city, '') AS city,
            COALESCE(referrals.referral_count, 0) AS referral_count
        FROM users u
        LEFT JOIN membership_tiers mt
            ON mt.id = u.membership_tier_id
        LEFT JOIN (
            SELECT ua.user_id, ua.phone, ua.township, ua.city
            FROM user_addresses ua
            WHERE ua.is_default = 1
        ) AS default_address
            ON default_address.user_id = u.id
        LEFT JOIN (
            SELECT referred_by_user_id, COUNT(*) AS referral_count
            FROM users
            WHERE referred_by_user_id IS NOT NULL
            GROUP BY referred_by_user_id
        ) AS referrals
            ON referrals.referred_by_user_id = u.id
        WHERE 1 = 1
    SQL;

    $bindings = [];
    $searchQuery = trim((string) ($filters['q'] ?? ''));
    if ($searchQuery !== '') {
        $searchBinding = '%' . $searchQuery . '%';
        $query .= ' AND (
            u.public_user_id LIKE :query_id
            OR u.name LIKE :query_name
            OR u.email LIKE :query_email
        )';
        $bindings[':query_id'] = $searchBinding;
        $bindings[':query_name'] = $searchBinding;
        $bindings[':query_email'] = $searchBinding;
    }

    $query .= ' ORDER BY u.created_at DESC, u.id DESC';
    $statement = $pdo->prepare($query);
    $statement->execute($bindings);

    $customers = [];
    $memberLevels = [];

    foreach (($statement->fetchAll() ?: []) as $row) {
        $memberLevel = customer_member_level(
            isset($row['tier_name']) ? (string) $row['tier_name'] : null,
            (int) $row['referral_count']
        );

        $customer = [
            'id' => $row['public_user_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'township' => $row['township'],
            'city' => $row['city'],
            'member_level' => $memberLevel,
            'status' => customer_status_label((string) $row['status']),
            'joined' => date('d.m.Y H:i:s', strtotime((string) $row['created_at'])),
        ];

        $customers[] = $customer;
        $memberLevels[$memberLevel] = true;
    }

    $memberLevelOptions = array_keys($memberLevels);
    sort($memberLevelOptions, SORT_NATURAL | SORT_FLAG_CASE);

    return [
        'customers' => $customers,
        'member_level_options' => $memberLevelOptions,
    ];
}

