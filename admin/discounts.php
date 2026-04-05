<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/admin/discounts.php';

$setFlash = static function (string $message, string $type = 'success'): void {
    $_SESSION['admin_discounts_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): ?array {
    $flash = $_SESSION['admin_discounts_flash'] ?? null;
    unset($_SESSION['admin_discounts_flash']);
    return is_array($flash) ? $flash : null;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_discount_save($_POST);
        $setFlash('Discount saved successfully.');
        header('Location: ' . app_path('/admin/discounts.php'));
        exit;
    } catch (Throwable $exception) {
        $setFlash($exception->getMessage(), 'error');
        header('Location: ' . app_path('/admin/discounts.php'));
        exit;
    }
}

$flash = $consumeFlash();
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$discounts = admin_fetch_discounts();
$validUsers = admin_fetch_discount_valid_user_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/discounts.css?v=20260310')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content discounts-page">
        <header class="discounts-header">
            <h1>Discounts</h1>
            <div class="discounts-toolbar">
                <form class="discounts-search-form admin-search-form" method="get" action="<?php echo htmlspecialchars(app_path('/admin/discounts.php')); ?>">
                    <div class="admin-search-box">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Title/Discount ID">
                        <button type="submit" class="search-icon" aria-label="Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                    <button type="submit" class="admin-search-submit">Search</button>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/discounts.php')); ?>" class="admin-show-all">Show All</a>
                </form>
                <button type="button" class="btn-new" data-modal-open="add-discount">+ New</button>
                <div class="filter-wrapper">
                    <button type="button" class="btn-filter" aria-label="Filter" aria-expanded="false">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" aria-hidden="true">
                        <div class="filter-group">
                            <div class="filter-title">Status</div>
                            <label><input type="checkbox" value="Active"> Active</label>
                            <label><input type="checkbox" value="Inactive"> Inactive</label>
                        </div>
                        <div class="filter-group">
                            <div class="filter-title">Discount Type</div>
                            <label><input type="checkbox" value="Membership"> Membership</label>
                            <label><input type="checkbox" value="Promotion"> Promotion</label>
                            <label><input type="checkbox" value="Bundle"> Bundle</label>
                        </div>
                        <div class="filter-group">
                            <div class="filter-title">Value Type</div>
                            <label><input type="checkbox" value="Percentage"> Percentage</label>
                            <label><input type="checkbox" value="Fixed"> Fixed</label>
                        </div>
                        <div class="filter-actions">
                            <button type="button" class="filter-clear">Clear</button>
                            <button type="button" class="filter-apply">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="discounts-table-card">
            <div class="discounts-table-scroll table-scroll">
                <table class="discounts-table admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Discount<br>Type</th>
                            <th>Value<br>Type</th>
                            <th>Value</th>
                            <th>Valid Users</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($discounts === []): ?>
                            <tr class="discounts-empty-row">
                                <td colspan="9">No discounts found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($discounts as $discount): ?>
                                <tr
                                    class="discount-row"
                                    data-row-id="<?php echo (int) $discount['id']; ?>"
                                    data-id="<?php echo htmlspecialchars((string) $discount['public_discount_id']); ?>"
                                    data-title="<?php echo htmlspecialchars((string) $discount['name']); ?>"
                                    data-discount-type="<?php echo htmlspecialchars((string) $discount['discount_type_label']); ?>"
                                    data-discount-type-key="<?php echo htmlspecialchars(strtolower((string) $discount['discount_type'])); ?>"
                                    data-value-type="<?php echo htmlspecialchars((string) $discount['value_type_label']); ?>"
                                    data-value="<?php echo htmlspecialchars((string) $discount['value']); ?>"
                                    data-users="<?php echo htmlspecialchars(implode('|', $discount['valid_user_keys'])); ?>"
                                    data-start="<?php echo htmlspecialchars((string) $discount['start_input']); ?>"
                                    data-end="<?php echo htmlspecialchars((string) $discount['end_input']); ?>"
                                    data-status="<?php echo htmlspecialchars((string) $discount['status_label']); ?>"
                                >
                                    <td class="discount-id"><?php echo htmlspecialchars((string) $discount['public_discount_id']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $discount['name']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $discount['discount_type_label']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $discount['value_type_label']); ?></td>
                                    <td>
                                        <?php
                                        if (strtolower((string) $discount['value_type']) === 'percentage') {
                                            echo htmlspecialchars((string) $discount['display_value']) . ' %';
                                        } else {
                                            echo htmlspecialchars((string) $discount['display_value']);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="valid-users">
                                            <?php if ($discount['users'] === []): ?>
                                                <span>-</span>
                                            <?php else: ?>
                                                <?php foreach ($discount['users'] as $user): ?>
                                                    <span><?php echo htmlspecialchars((string) $user); ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) $discount['start_display']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $discount['end_display']); ?></td>
                                    <td>
                                        <span class="status-pill <?php echo htmlspecialchars(strtolower((string) $discount['status_label'])); ?>">
                                            <?php echo htmlspecialchars((string) $discount['status_label']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal-overlay" data-modal-overlay>
        <div class="discount-modal" data-modal="add-discount" aria-hidden="true">
            <button class="modal-close" data-modal-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2>Add Discount</h2>
            <form class="discount-form" method="post">
                <input type="hidden" name="discount_id" value="0">
                <div class="form-row">
                    <label>Title:</label>
                    <input type="text" name="title" placeholder="">
                </div>
                <div class="form-row">
                    <label>Discount Type:</label>
                    <select name="discount_type">
                        <option value="">Select type</option>
                        <option value="membership">Membership</option>
                        <option value="promotion">Promotion</option>
                        <option value="bundle">Bundle</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Value Type:</label>
                    <select class="value-type" name="value_type">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </div>
                <div class="form-row value-row">
                    <label>Value:</label>
                    <div class="value-input">
                        <input type="text" name="value" placeholder="">
                        <span class="value-suffix">%</span>
                    </div>
                </div>
                <div class="form-row">
                    <label>Start Date (Optional):</label>
                    <div class="date-field">
                        <input type="date" name="start_date">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="form-row">
                    <label>End Date (Optional):</label>
                    <div class="date-field">
                        <input type="date" name="end_date">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="form-row">
                    <label>Status:</label>
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-row users-row">
                    <label>Valid Users:</label>
                    <div class="users-box">
                        <?php foreach ($validUsers as $user): ?>
                            <label class="user-check">
                                <input type="checkbox" name="valid_users[]" value="<?php echo htmlspecialchars((string) $user['key']); ?>">
                                <span><?php echo htmlspecialchars((string) $user['label']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <span>Unsaved changes will be deleted</span>
                    <div class="modal-actions">
                        <button type="submit" class="btn-save">Add</button>
                        <button type="button" class="btn-discard">Discard</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="discount-modal" data-modal="edit-discount" aria-hidden="true">
            <button class="modal-close" data-modal-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2>Edit Discount</h2>
            <p class="discount-id-text">ID: <span data-discount-id>--</span></p>
            <form class="discount-form" method="post">
                <input type="hidden" name="discount_id" value="0">
                <div class="form-row">
                    <label>Title:</label>
                    <input type="text" name="title" data-field="title">
                </div>
                <div class="form-row">
                    <label>Discount Type:</label>
                    <select name="discount_type" data-field="discount-type">
                        <option value="membership">Membership</option>
                        <option value="promotion">Promotion</option>
                        <option value="bundle">Bundle</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Value Type:</label>
                    <select class="value-type" name="value_type" data-field="value-type">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </div>
                <div class="form-row value-row">
                    <label>Value:</label>
                    <div class="value-input">
                        <input type="text" name="value" data-field="value">
                        <span class="value-suffix">%</span>
                    </div>
                </div>
                <div class="form-row">
                    <label>Start Date (Optional):</label>
                    <div class="date-field">
                        <input type="date" name="start_date" data-field="start">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="form-row">
                    <label>End Date (Optional):</label>
                    <div class="date-field">
                        <input type="date" name="end_date" data-field="end">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="form-row">
                    <label>Status:</label>
                    <select name="status" data-field="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-row users-row">
                    <label>Valid Users:</label>
                    <div class="users-box" data-field="users">
                        <?php foreach ($validUsers as $user): ?>
                            <label class="user-check">
                                <input type="checkbox" name="valid_users[]" value="<?php echo htmlspecialchars((string) $user['key']); ?>">
                                <span><?php echo htmlspecialchars((string) $user['label']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" class="btn-apply">Apply Discount</button>
                <div class="modal-footer">
                    <span>Unsaved changes will be deleted</span>
                    <div class="modal-actions">
                        <button type="submit" class="btn-save">Save</button>
                        <button type="button" class="btn-discard">Discard</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.adminDiscountsFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/discounts.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>



