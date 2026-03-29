<?php

require_once __DIR__ . '/../../config/database.php';

function membership_fetch_tiers(): array
{
    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT id, tier_name, min_spent, max_spent, referral_discount_type, referral_discount_value
         FROM membership_tiers
         ORDER BY min_spent ASC, id ASC'
    );

    return $statement->fetchAll() ?: [];
}

function membership_fetch_user_total_spent(int $userId): float
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT COALESCE(SUM(grand_total), 0)
         FROM orders
         WHERE user_id = :user_id
           AND status = "delivered"
           AND LOWER(COALESCE(payment_status, "")) = "paid"'
    );
    $statement->execute([':user_id' => $userId]);

    return (float) $statement->fetchColumn();
}

function membership_find_tier_for_spent(array $tiers, float $spent): ?array
{
    $matched = null;
    foreach ($tiers as $tier) {
        $minSpent = (float) $tier['min_spent'];
        $maxSpent = $tier['max_spent'] !== null ? (float) $tier['max_spent'] : null;
        if ($spent < $minSpent) {
            continue;
        }
        if ($maxSpent !== null && $spent > $maxSpent) {
            continue;
        }
        $matched = $tier;
    }

    return $matched;
}

function membership_resolve_tier_for_spent(float $spent, ?array $tiers = null): ?array
{
    return membership_find_tier_for_spent($tiers ?? membership_fetch_tiers(), $spent);
}

function membership_find_next_tier(array $tiers, float $spent): ?array
{
    foreach ($tiers as $tier) {
        if ((float) $tier['min_spent'] > $spent) {
            return $tier;
        }
    }

    return null;
}

function membership_sync_user_tier_id(int $userId, ?array $tier): void
{
    $pdo = get_database_connection();
    $tierId = $tier !== null ? (int) $tier['id'] : null;
    $statement = $pdo->prepare('UPDATE users SET membership_tier_id = :tier_id WHERE id = :user_id');
    $statement->bindValue(':tier_id', $tierId, $tierId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $statement->execute();
}

function membership_fetch_user_summary(int $userId): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('SELECT id, created_at, membership_tier_id FROM users WHERE id = :user_id LIMIT 1');
    $statement->execute([':user_id' => $userId]);
    $user = $statement->fetch();
    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $spent = membership_fetch_user_total_spent($userId);
    $tiers = membership_fetch_tiers();
    $currentTier = membership_resolve_tier_for_spent($spent, $tiers);
    $storedTierId = $user['membership_tier_id'] !== null ? (int) $user['membership_tier_id'] : null;
    $resolvedTierId = $currentTier !== null ? (int) $currentTier['id'] : null;

    if ($storedTierId !== $resolvedTierId) {
        membership_sync_user_tier_id($userId, $currentTier);
    }

    $nextTier = membership_find_next_tier($tiers, $spent);
    $currentMin = $currentTier !== null ? (float) $currentTier['min_spent'] : 0.0;
    if ($nextTier !== null) {
        $targetMin = (float) $nextTier['min_spent'];
        $needed = max($targetMin - $spent, 0.0);
        $span = max($targetMin - $currentMin, 1.0);
        $progress = (int) round(max(min(($spent - $currentMin) / $span, 1), 0) * 100);
    } else {
        $needed = 0.0;
        $progress = 100;
    }

    return [
        'current_title' => $currentTier['tier_name'] ?? 'Standard Customer',
        'current_tier' => $currentTier,
        'next_tier' => $nextTier,
        'next_title' => $nextTier['tier_name'] ?? '',
        'required_amount' => $needed,
        'joined_since' => date('d.m.Y', strtotime((string) $user['created_at'])),
        'total_spent' => $spent,
        'progress_percent' => $progress,
    ];
}
