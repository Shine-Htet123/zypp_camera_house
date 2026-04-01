<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/services/mailer.php';

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

function membership_find_tier_by_id(array $tiers, ?int $tierId): ?array
{
    if ($tierId === null || $tierId <= 0) {
        return null;
    }

    foreach ($tiers as $tier) {
        if ((int) ($tier['id'] ?? 0) === $tierId) {
            return $tier;
        }
    }

    return null;
}

function membership_is_upgrade(?array $previousTier, ?array $nextTier): bool
{
    if ($previousTier === null || $nextTier === null) {
        return false;
    }

    return (float) ($nextTier['min_spent'] ?? 0) > (float) ($previousTier['min_spent'] ?? 0);
}

function membership_send_upgrade_email(array $user, array $previousTier, array $currentTier, float $spent): void
{
    if (!mailer_is_configured()) {
        return;
    }

    $recipientEmail = trim((string) ($user['email'] ?? ''));
    if ($recipientEmail === '') {
        return;
    }

    $customerName = trim((string) ($user['name'] ?? 'Creator'));
    $previousTierName = trim((string) ($previousTier['tier_name'] ?? 'Previous Tier'));
    $currentTierName = trim((string) ($currentTier['tier_name'] ?? 'New Tier'));
    $spentLabel = number_format($spent) . ' MMK';
    $profileUrl = app_url('/user-profile.php#membership');

    $safeCustomerName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
    $safePreviousTierName = htmlspecialchars($previousTierName, ENT_QUOTES, 'UTF-8');
    $safeCurrentTierName = htmlspecialchars($currentTierName, ENT_QUOTES, 'UTF-8');
    $safeSpentLabel = htmlspecialchars($spentLabel, ENT_QUOTES, 'UTF-8');
    $safeProfileUrl = htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8');

    mailer_send([
        'to_email' => $recipientEmail,
        'to_name' => $customerName,
        'subject' => 'You just unlocked ' . $currentTierName . ' on ZYPP',
        'html' => '
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;border-spacing:0;">
                <tr>
                    <td style="padding:0;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="dm-bg" bgcolor="#1b1b20" style="border-collapse:separate;border-spacing:0;background:#1b1b20;border:1px solid #3b2f2a;border-radius:24px;">
                            <tr>
                                <td style="padding:30px 24px 26px;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;border-spacing:0;">
                                        <tr>
                                            <td class="dm-badge force-muted" bgcolor="#6f594f" style="background:#6f594f;border-radius:999px;padding:8px 14px;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#f5e5d6;-webkit-text-fill-color:#f5e5d6;">
                                                MEMBERSHIP UPGRADE
                                            </td>
                                        </tr>
                                    </table>

                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                        <tr>
                                            <td class="force-white" style="padding:18px 0 10px;font-size:32px;line-height:1.12;font-weight:800;color:#fff8f2;-webkit-text-fill-color:#fff8f2;">
                                                Congratulations,<br>' . $safeCustomerName . '!
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="force-muted" style="padding:0 0 22px;font-size:16px;line-height:1.75;color:#ead9cb;-webkit-text-fill-color:#ead9cb;">
                                                Your ZYPP membership has been upgraded from <strong class="force-white" style="color:#fff8f2;-webkit-text-fill-color:#fff8f2;">' . $safePreviousTierName . '</strong> to <strong class="force-accent" style="color:#ffd7a8;-webkit-text-fill-color:#ffd7a8;">' . $safeCurrentTierName . '</strong>.
                                            </td>
                                        </tr>
                                    </table>

                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="dm-surface" bgcolor="#121317" style="border-collapse:separate;border-spacing:0;background:#121317;border:2px solid #c8b6aa;border-radius:20px;">
                                        <tr>
                                            <td style="padding:22px 20px;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                                    <tr>
                                                        <td class="force-label" style="padding:0 0 8px;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;font-weight:700;color:#d8c0ab;-webkit-text-fill-color:#d8c0ab;">
                                                            NEW TIER
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="force-white" style="padding:0 0 18px;font-size:24px;line-height:1.3;font-weight:800;color:#fff8f2;-webkit-text-fill-color:#fff8f2;">
                                                            ' . $safeCurrentTierName . '
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="border-top:2px solid #c8b6aa;font-size:1px;line-height:1px;">&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="force-label" style="padding:18px 0 8px;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;font-weight:700;color:#d8c0ab;-webkit-text-fill-color:#d8c0ab;">
                                                            ELIGIBLE SPEND
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="force-white" style="padding:0;font-size:22px;line-height:1.25;font-weight:800;color:#fff8f2;-webkit-text-fill-color:#fff8f2;">
                                                            ' . $safeSpentLabel . '
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                        <tr>
                                            <td class="force-soft" style="padding:22px 0 22px;font-size:15px;line-height:1.75;color:#dbc8ba;-webkit-text-fill-color:#dbc8ba;">
                                                Your new membership level may unlock better benefits, offers, and member experiences across your ZYPP journey.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0 0 18px;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;border-spacing:0;">
                                                    <tr>
                                                        <td class="dm-button" bgcolor="#e0bb97" style="background:#e0bb97;border-radius:12px;">
                                                            <a href="' . $safeProfileUrl . '" class="dm-button" style="display:inline-block;padding:13px 20px;border-radius:12px;background:#e0bb97;color:#211814 !important;-webkit-text-fill-color:#211814 !important;text-decoration:none;font-size:15px;font-weight:800;">
                                                                View My Membership
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="force-dim" style="padding:0;font-size:13px;line-height:1.7;color:#bda999;-webkit-text-fill-color:#bda999;">
                                                Keep going and enjoy the next step in the creator journey with ZYPP Camera House.
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>',
        'text' => "Congratulations, {$customerName}!\n\nYour ZYPP membership has been upgraded from {$previousTierName} to {$currentTierName}.\nEligible spend: {$spentLabel}\n\nView your membership:\n{$profileUrl}",
    ]);
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
    $statement = $pdo->prepare('SELECT id, name, email, created_at, membership_tier_id FROM users WHERE id = :user_id LIMIT 1');
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
    $storedTier = membership_find_tier_by_id($tiers, $storedTierId);
    $justUpgraded = membership_is_upgrade($storedTier, $currentTier);

    if ($storedTierId !== $resolvedTierId) {
        membership_sync_user_tier_id($userId, $currentTier);
        if ($justUpgraded) {
            try {
                membership_send_upgrade_email($user, $storedTier, $currentTier, $spent);
            } catch (Throwable $exception) {
                error_log('[ZYPP] Failed to send membership upgrade email: ' . $exception->getMessage());
            }
        }
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
        'just_upgraded' => $justUpgraded,
        'previous_title' => $storedTier['tier_name'] ?? '',
    ];
}
