<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/admin/discounts.php';

$setFlash = static function (string $message, string $type = 'success'): void {
    $_SESSION['admin_discount_stack_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): ?array {
    $flash = $_SESSION['admin_discount_stack_flash'] ?? null;
    unset($_SESSION['admin_discount_stack_flash']);
    return is_array($flash) ? $flash : null;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $priorityOrder = array_values(array_filter(array_map('trim', explode(',', (string) ($_POST['priority_order'] ?? '')))));
        $rawRules = json_decode((string) ($_POST['stack_rules'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
        admin_save_discount_priority_types($priorityOrder);
        admin_save_discount_stack_rules(is_array($rawRules) ? $rawRules : []);
        $setFlash('Discount priority and stack rules saved successfully.');
    } catch (Throwable $exception) {
        $setFlash($exception->getMessage(), 'error');
    }

    header('Location: /admin/discount-stack-rules.php');
    exit;
}

$flash = $consumeFlash();
$priorityTypes = admin_fetch_discount_priority_types();
$stackRules = admin_fetch_discount_stack_rules();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/discount-stack-rules.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content discount-stack-page">
        <form method="post" data-stack-form>
        <input type="hidden" name="priority_order" value="" data-priority-order>
        <input type="hidden" name="stack_rules" value="" data-stack-rules>
        <div class="stack-layout">
            <section class="priority-section">
                <h1>Discount Priority</h1>
                <p class="priority-subtitle">Drag and Drop the discount type to manage priority</p>

                <div class="priority-card">
                    <div class="priority-head">
                        <span>Priority</span>
                        <span>Discount Type</span>
                    </div>
                    <ul class="priority-list" id="priorityList">
                        <?php foreach ($priorityTypes as $index => $type): ?>
                            <li class="priority-item" draggable="true" data-type="<?php echo htmlspecialchars((string) $type['key']); ?>">
                                <span class="priority-number"><?php echo $index + 1; ?></span>
                                <span class="priority-tag"><?php echo htmlspecialchars((string) $type['label']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <section class="stack-section">
                <h2>Discount Stack Rules</h2>
                <div class="stack-card">
                    <div class="stack-table table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Discount Type A</th>
                                    <th>Discount Type B</th>
                                    <th>Allowed</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stackRules as $rule): ?>
                                <tr
                                    data-rule-id="<?php echo (int) $rule['id']; ?>"
                                    data-type-a="<?php echo htmlspecialchars((string) $rule['type_a_key']); ?>"
                                    data-type-b="<?php echo htmlspecialchars((string) $rule['type_b_key']); ?>"
                                    data-allowed="<?php echo $rule['allowed'] ? 'Yes' : 'No'; ?>"
                                >
                                    <td><?php echo htmlspecialchars((string) $rule['no']); ?>.</td>
                                    <td><?php echo htmlspecialchars((string) $rule['type_a']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $rule['type_b']); ?></td>
                                    <td class="allowed-cell">
                                        <span class="allowed-pill <?php echo $rule['allowed'] ? 'yes' : 'no'; ?>">
                                            <?php echo $rule['allowed'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td class="stack-actions">
                                        <button class="icon-btn edit" type="button" aria-label="Edit">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <div class="stack-footer">
            <span>Unsaved data will be deleted</span>
            <div class="stack-actions-row">
                <button class="btn-save" type="submit">Save</button>
                <button class="btn-discard" type="button">Discard</button>
            </div>
        </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.adminDiscountStackFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/discount-stack-rules.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

