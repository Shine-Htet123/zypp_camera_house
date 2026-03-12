<?php

require_once __DIR__ . '/../../config/database.php';

function admin_membership_build_range(float $minSpent, ?float $maxSpent): string
{
    $minText = number_format($minSpent) . ' MMK';
    if ($maxSpent === null) {
        return '>' . $minText;
    }

    if ($minSpent <= 0) {
        return '<' . number_format($maxSpent) . ' MMK';
    }

    return $minText . ' - ' . number_format($maxSpent) . ' MMK';
}

function admin_fetch_membership_tiers(): array
{
    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT id, tier_name, min_spent, max_spent, referral_discount_type, referral_discount_value
         FROM membership_tiers
         ORDER BY min_spent ASC, id ASC'
    );

    $tiers = $statement->fetchAll() ?: [];
    foreach ($tiers as &$tier) {
        $minSpent = (float) $tier['min_spent'];
        $maxSpent = $tier['max_spent'] !== null ? (float) $tier['max_spent'] : null;
        $tier['range'] = admin_membership_build_range($minSpent, $maxSpent);
        $tier['referral_discount_type_label'] = $tier['referral_discount_type'] !== null
            ? ucfirst((string) $tier['referral_discount_type'])
            : '';
    }

    return $tiers;
}

function admin_membership_validate(array $input, ?int $currentTierId = null): array
{
    $tierName = trim((string) ($input['tier_name'] ?? ''));
    $minRaw = str_replace(',', '', trim((string) ($input['min_spent'] ?? '')));
    $maxRaw = str_replace(',', '', trim((string) ($input['max_spent'] ?? '')));
    $refTypeRaw = strtolower(trim((string) ($input['referral_discount_type'] ?? '')));
    $refValueRaw = str_replace(',', '', trim((string) ($input['referral_discount_value'] ?? '')));

    if ($tierName === '') {
        throw new InvalidArgumentException('Member tier name is required.');
    }

    if ($minRaw === '') {
        $minSpent = 0.0;
    } else {
        if (!is_numeric($minRaw) || (float) $minRaw < 0) {
            throw new InvalidArgumentException('Min spent must be a valid non-negative number.');
        }
        $minSpent = (float) $minRaw;
    }
    $maxSpent = null;
    if ($maxRaw !== '') {
        if (!is_numeric($maxRaw) || (float) $maxRaw < 0) {
            throw new InvalidArgumentException('Max spent must be a valid non-negative number.');
        }
        $maxSpent = (float) $maxRaw;
        if ($maxSpent < $minSpent) {
            throw new InvalidArgumentException('Max spent must be greater than or equal to min spent.');
        }
    }

    $refType = $refTypeRaw !== '' ? $refTypeRaw : null;
    if ($refType !== null && !in_array($refType, ['percentage', 'fixed'], true)) {
        throw new InvalidArgumentException('Referral discount type is invalid.');
    }

    $refValue = null;
    if ($refValueRaw !== '') {
        if (!is_numeric($refValueRaw) || (float) $refValueRaw < 0) {
            throw new InvalidArgumentException('Referral discount value must be a valid non-negative number.');
        }
        $refValue = (float) $refValueRaw;
    }

    if (($refType === null) xor ($refValue === null)) {
        throw new InvalidArgumentException('Referral discount type and value must be provided together.');
    }

    if ($refType === 'percentage' && $refValue !== null && $refValue > 100) {
        throw new InvalidArgumentException('Percentage referral discount cannot exceed 100%.');
    }

    admin_membership_assert_range_available($minSpent, $maxSpent, $currentTierId);

    return [
        'tier_name' => $tierName,
        'min_spent' => $minSpent,
        'max_spent' => $maxSpent,
        'referral_discount_type' => $refType,
        'referral_discount_value' => $refValue,
    ];
}

function admin_membership_assert_range_available(float $minSpent, ?float $maxSpent, ?int $currentTierId = null): void
{
    $pdo = get_database_connection();
    if ($currentTierId !== null) {
        $statement = $pdo->prepare(
            'SELECT id, min_spent, max_spent
             FROM membership_tiers
             WHERE id <> :current_id'
        );
        $statement->execute([':current_id' => $currentTierId]);
    } else {
        $statement = $pdo->query(
            'SELECT id, min_spent, max_spent
             FROM membership_tiers'
        );
    }

    $newMax = $maxSpent ?? INF;
    foreach ($statement->fetchAll() as $tier) {
        $otherMin = (float) $tier['min_spent'];
        $otherMax = $tier['max_spent'] !== null ? (float) $tier['max_spent'] : INF;
        if ($minSpent < $otherMax && $otherMin < $newMax) {
            throw new InvalidArgumentException('Membership tier spending ranges cannot overlap.');
        }
    }
}

function admin_membership_save(array $input): int
{
    $tierId = isset($input['tier_id']) ? (int) $input['tier_id'] : 0;
    $validated = admin_membership_validate($input, $tierId > 0 ? $tierId : null);
    $pdo = get_database_connection();

    if ($tierId > 0) {
        $statement = $pdo->prepare(
            'UPDATE membership_tiers
             SET tier_name = :tier_name,
                 min_spent = :min_spent,
                 max_spent = :max_spent,
                 referral_discount_type = :referral_discount_type,
                 referral_discount_value = :referral_discount_value
             WHERE id = :id'
        );
        $statement->execute([
            ':tier_name' => $validated['tier_name'],
            ':min_spent' => $validated['min_spent'],
            ':max_spent' => $validated['max_spent'],
            ':referral_discount_type' => $validated['referral_discount_type'],
            ':referral_discount_value' => $validated['referral_discount_value'],
            ':id' => $tierId,
        ]);

        return $tierId;
    }

    $statement = $pdo->prepare(
        'INSERT INTO membership_tiers (tier_name, min_spent, max_spent, referral_discount_type, referral_discount_value)
         VALUES (:tier_name, :min_spent, :max_spent, :referral_discount_type, :referral_discount_value)'
    );
    $statement->execute([
        ':tier_name' => $validated['tier_name'],
        ':min_spent' => $validated['min_spent'],
        ':max_spent' => $validated['max_spent'],
        ':referral_discount_type' => $validated['referral_discount_type'],
        ':referral_discount_value' => $validated['referral_discount_value'],
    ]);

    return (int) $pdo->lastInsertId();
}

function admin_membership_delete(int $tierId): void
{
    if ($tierId <= 0) {
        throw new InvalidArgumentException('Membership tier not found.');
    }

    $pdo = get_database_connection();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM membership_discounts WHERE tier_id = :tier_id')
            ->execute([':tier_id' => $tierId]);

        $pdo->prepare('UPDATE users SET membership_tier_id = NULL WHERE membership_tier_id = :tier_id')
            ->execute([':tier_id' => $tierId]);

        $statement = $pdo->prepare('DELETE FROM membership_tiers WHERE id = :tier_id');
        $statement->execute([':tier_id' => $tierId]);
        if ($statement->rowCount() === 0) {
            throw new RuntimeException('Membership tier not found.');
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
