<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/admin/membership_tiers.php';

$setFlash = static function (string $message, string $type = 'success'): void {
    $_SESSION['admin_membership_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): ?array {
    $flash = $_SESSION['admin_membership_flash'] ?? null;
    unset($_SESSION['admin_membership_flash']);
    return is_array($flash) ? $flash : null;
};

$isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$respondJson = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = trim((string) ($_POST['membership_action'] ?? 'save'));
        $message = 'Membership tier saved successfully.';
        if ($action === 'delete') {
            admin_membership_delete((int) ($_POST['tier_id'] ?? 0));
            $message = 'Membership tier deleted successfully.';
        } else {
            admin_membership_save($_POST);
            $message = 'Membership tier saved successfully.';
        }

        if ($isAjaxRequest) {
            $respondJson([
                'success' => true,
                'message' => $message,
                'tiers' => admin_fetch_membership_tiers(),
            ]);
        }

        $setFlash($message);
    } catch (Throwable $exception) {
        if ($isAjaxRequest) {
            $respondJson([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $setFlash($exception->getMessage(), 'error');
    }

    header('Location: /admin/membership-tiers.php');
    exit;
}

$flash = $consumeFlash();
$tiers = admin_fetch_membership_tiers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/membership-tiers.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content membership-page">
        <header class="membership-header">
            <h1>Membership Tiers</h1>
        </header>

        <section class="membership-layout">
            <div class="membership-left">
                <div class="tier-cards">
                    <?php foreach ($tiers as $tier): ?>
                        <article
                            class="tier-card"
                            data-tier-id="<?php echo (int) $tier['id']; ?>"
                            data-tier-title="<?php echo htmlspecialchars((string) $tier['tier_name']); ?>"
                            data-tier-range="<?php echo htmlspecialchars((string) $tier['range']); ?>"
                            data-tier-min="<?php echo htmlspecialchars((string) $tier['min_spent']); ?>"
                            data-tier-max="<?php echo htmlspecialchars($tier['max_spent'] !== null ? (string) $tier['max_spent'] : ''); ?>"
                            data-tier-ref-type="<?php echo htmlspecialchars((string) ($tier['referral_discount_type_label'] !== '' ? $tier['referral_discount_type_label'] : '')); ?>"
                            data-tier-ref-value="<?php echo htmlspecialchars($tier['referral_discount_value'] !== null ? (string) $tier['referral_discount_value'] : ''); ?>"
                        >
                            <h3><?php echo htmlspecialchars((string) $tier['tier_name']); ?></h3>
                            <p><?php echo htmlspecialchars((string) $tier['range']); ?></p>
                            <div class="tier-actions">
                                <button type="button" class="icon-btn edit-tier" aria-label="Edit tier">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="icon-btn delete delete-tier" aria-label="Delete tier">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                            <form class="tier-delete-form" method="post">
                                <input type="hidden" name="membership_action" value="delete">
                                <input type="hidden" name="tier_id" value="<?php echo (int) $tier['id']; ?>">
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="membership-divider"></div>

            <div class="membership-right">
                <article class="tier-form-card">
                    <h2>Create Member Tier</h2>
                    <form class="tier-form" method="post">
                        <input type="hidden" name="membership_action" value="save">
                        <input type="hidden" name="tier_id" value="0">
                        <label>
                            <span>Member Tier:</span>
                            <input type="text" class="tier-input" name="tier_name" placeholder="">
                        </label>
                        <label>
                            <span>Min Spent:</span>
                            <input type="text" class="tier-input" name="min_spent" placeholder="">
                        </label>
                        <label>
                            <span>Max Spent:</span>
                            <input type="text" class="tier-input" name="max_spent" placeholder="">
                        </label>
                        <label>
                            <span>Referral Discount Type:</span>
                            <select class="tier-select" id="createTierRefType" name="referral_discount_type" data-unit-target="createRefUnit">
                                <option value=""></option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed</option>
                            </select>
                        </label>
                        <label class="input-suffix">
                            <span>Referral Discount Value:</span>
                            <div class="suffix-field">
                                <input type="text" class="tier-input" name="referral_discount_value" placeholder="">
                                <span class="suffix" id="createRefUnit">%</span>
                            </div>
                        </label>
                    </form>
                    <div class="tier-form-actions">
                        <button type="button" class="btn-create" data-create-submit>Create</button>
                        <button type="button" class="btn-reset">Reset</button>
                    </div>
                </article>
            </div>
        </section>

        <template id="tierEditTemplate">
            <form class="tier-edit" method="post">
                <input type="hidden" name="membership_action" value="save">
                <input type="hidden" name="tier_id" class="edit-id" value="">
                <h3 class="tier-edit-title">Edit Member Tier</h3>
                <div class="tier-form">
                    <label>
                        <span>Member Tier:</span>
                        <input type="text" class="tier-input edit-name" name="tier_name" value="">
                    </label>
                    <label>
                        <span>Min Spent:</span>
                        <input type="text" class="tier-input edit-min" name="min_spent" value="">
                    </label>
                    <label>
                        <span>Max Spent:</span>
                        <input type="text" class="tier-input edit-max" name="max_spent" value="">
                    </label>
                    <label>
                        <span>Referral Discount Type:</span>
                        <select class="tier-select edit-ref-type" name="referral_discount_type" data-unit-target="editRefUnit">
                            <option value=""></option>
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed</option>
                        </select>
                    </label>
                    <label class="input-suffix">
                        <span>Referral Discount Value:</span>
                        <div class="suffix-field">
                            <input type="text" class="tier-input edit-ref-value" name="referral_discount_value" value="">
                            <span class="suffix" id="editRefUnit">%</span>
                        </div>
                    </label>
                </div>
                <div class="tier-form-actions">
                    <button type="submit" class="btn-confirm">Confirm</button>
                    <button type="button" class="btn-cancel">Cancel</button>
                </div>
            </form>
        </template>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.adminMembershipFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/membership-tiers.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

