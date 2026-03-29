<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/admin/delivery_methods.php';

$setFlash = static function (string $message, string $type = 'success'): void {
    $_SESSION['admin_delivery_methods_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): ?array {
    $flash = $_SESSION['admin_delivery_methods_flash'] ?? null;
    unset($_SESSION['admin_delivery_methods_flash']);
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
        $action = trim((string) ($_POST['delivery_method_action'] ?? 'save'));
        $message = 'Delivery method saved successfully.';
        if ($action === 'delete') {
            admin_delivery_method_delete((int) ($_POST['delivery_method_id'] ?? 0));
            $message = 'Delivery method deleted successfully.';
        } else {
            admin_delivery_method_save($_POST);
            $message = 'Delivery method saved successfully.';
        }

        if ($isAjaxRequest) {
            $respondJson([
                'success' => true,
                'message' => $message,
                'delivery_methods' => admin_fetch_delivery_methods(),
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

    header('Location: ' . app_path('/admin/delivery-methods.php'));
    exit;
}

$flash = $consumeFlash();
$deliveryMethods = admin_fetch_delivery_methods();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/delivery-methods.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content delivery-methods-page">
        <header class="delivery-methods-header">
            <h1>Delivery Methods</h1>
        </header>

        <section class="delivery-methods-layout">
            <div class="delivery-methods-left">
                <div class="delivery-method-cards">
                    <?php foreach ($deliveryMethods as $method): ?>
                        <article
                            class="delivery-method-card"
                            data-delivery-method-id="<?php echo (int) $method['delivery_method_id']; ?>"
                            data-delivery-method-name="<?php echo htmlspecialchars((string) $method['name']); ?>"
                            data-supports-free-shipping="<?php echo $method['supports_free_shipping'] ? '1' : '0'; ?>"
                            data-free-shipping-threshold="<?php echo htmlspecialchars($method['free_shipping_threshold'] !== null ? (string) $method['free_shipping_threshold'] : ''); ?>"
                            data-is-active="<?php echo $method['is_active'] ? '1' : '0'; ?>"
                        >
                            <div class="delivery-method-icon">
                                <i class="fa-solid fa-truck-fast"></i>
                            </div>
                            <h3><?php echo htmlspecialchars((string) $method['name']); ?></h3>
                            <p>
                                <?php if ($method['supports_free_shipping']): ?>
                                    <?php if ($method['free_shipping_threshold_label'] !== ''): ?>
                                        Free shipping at <?php echo htmlspecialchars((string) $method['free_shipping_threshold_label']); ?>
                                    <?php else: ?>
                                        Free shipping enabled
                                    <?php endif; ?>
                                <?php else: ?>
                                    Paid delivery only
                                <?php endif; ?>
                            </p>
                            <span class="delivery-method-status <?php echo $method['is_active'] ? 'is-active' : 'is-inactive'; ?>">
                                <?php echo $method['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <div class="delivery-method-actions">
                                <button type="button" class="icon-btn edit-method" aria-label="Edit delivery method">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="icon-btn delete delete-method" aria-label="Delete delivery method">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                            <form class="delivery-method-delete-form" method="post">
                                <input type="hidden" name="delivery_method_action" value="delete">
                                <input type="hidden" name="delivery_method_id" value="<?php echo (int) $method['delivery_method_id']; ?>">
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="delivery-methods-divider"></div>

            <div class="delivery-methods-right">
                <article class="delivery-method-form-card">
                    <h2>Create Delivery Method</h2>
                    <form class="delivery-method-form" method="post">
                        <input type="hidden" name="delivery_method_action" value="save">
                        <input type="hidden" name="delivery_method_id" value="0">
                        <label>
                            <span>Name:</span>
                            <input type="text" class="delivery-method-input" name="name" value="">
                        </label>
                        <label class="checkbox-label">
                            <span>Free Shipping:</span>
                            <input type="checkbox" name="supports_free_shipping" value="1" data-threshold-toggle="create-threshold-row">
                        </label>
                        <label id="create-threshold-row" class="threshold-row is-hidden">
                            <span>Threshold:</span>
                            <div class="suffix-field">
                                <input type="text" class="delivery-method-input" name="free_shipping_threshold" value="">
                                <span class="suffix">MMK</span>
                            </div>
                        </label>
                        <label>
                            <span>Status:</span>
                            <select class="delivery-method-input delivery-method-select" name="is_active">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </label>
                    </form>
                    <div class="delivery-method-form-actions">
                        <button type="button" class="btn-create" data-create-submit>Create</button>
                        <button type="button" class="btn-reset">Reset</button>
                    </div>
                </article>
            </div>
        </section>

        <template id="deliveryMethodEditTemplate">
            <form class="delivery-method-edit" method="post">
                <input type="hidden" name="delivery_method_action" value="save">
                <input type="hidden" name="delivery_method_id" class="edit-id" value="">
                <h3 class="delivery-method-edit-title">Edit Delivery Method</h3>
                <div class="delivery-method-form">
                    <label>
                        <span>Name:</span>
                        <input type="text" class="delivery-method-input edit-name" name="name" value="">
                    </label>
                    <label class="checkbox-label">
                        <span>Free Shipping:</span>
                        <input type="checkbox" class="edit-supports-free-shipping" name="supports_free_shipping" value="1" data-threshold-toggle="edit-threshold-row">
                    </label>
                    <label id="edit-threshold-row" class="threshold-row is-hidden">
                        <span>Threshold:</span>
                        <div class="suffix-field">
                            <input type="text" class="delivery-method-input edit-threshold" name="free_shipping_threshold" value="">
                            <span class="suffix">MMK</span>
                        </div>
                    </label>
                    <label>
                        <span>Status:</span>
                        <select class="delivery-method-input delivery-method-select edit-is-active" name="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </label>
                </div>
                <div class="delivery-method-form-actions">
                    <button type="submit" class="btn-confirm">Confirm</button>
                    <button type="button" class="btn-cancel">Cancel</button>
                </div>
            </form>
        </template>
    </main>

    <script>
        window.adminDeliveryMethodsFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/delivery-methods.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>
