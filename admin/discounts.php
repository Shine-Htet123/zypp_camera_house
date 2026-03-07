<?php
$discounts = [
    [
        'id' => 'DCT202601260001',
        'title' => 'Christmas Sale',
        'discount_type' => 'Membership',
        'value_type' => 'Percentage',
        'value' => '20',
        'users' => ['Starter Creator', 'Active Creator', 'ZYPP Pro Creator', 'Standard Customer'],
        'start' => '23/12/2025',
        'end' => '29/12/2025',
        'status' => 'Active'
    ],
    [
        'id' => 'DCT202601260001',
        'title' => 'Christmas Sale',
        'discount_type' => 'Promotion',
        'value_type' => 'Fixed',
        'value' => '300,000 MMK',
        'users' => ['Starter Creator', 'Active Creator', 'ZYPP Master Creator', 'Standard Customer'],
        'start' => '23/12/2025',
        'end' => '29/12/2025',
        'status' => 'Inactive'
    ],
    [
        'id' => 'DCT202601260001',
        'title' => 'Christmas Sale',
        'discount_type' => 'Bundle',
        'value_type' => 'Percentage',
        'value' => '30',
        'users' => ['Starter Creator', 'Active Creator', 'ZYPP Pro Creator', 'Standard Customer'],
        'start' => '23/12/2025',
        'end' => '29/12/2025',
        'status' => 'Active'
    ],
];

$validUsers = [
    'Standard Customer',
    'Starter Creator',
    'Active Creator',
    'ZYPP Pro Creator',
    'ZYPP Master Creator',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/discounts.css?v=20260307">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content discounts-page">
        <header class="discounts-header">
            <h1>Discounts</h1>
            <div class="discounts-toolbar">
                <div class="search-field">
                    <input type="text" placeholder="Title/Discount ID">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <button type="button" class="btn-search">Search</button>
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
                        <?php foreach ($discounts as $discount): ?>
                            <tr
                                class="discount-row"
                                data-id="<?php echo htmlspecialchars($discount['id']); ?>"
                                data-title="<?php echo htmlspecialchars($discount['title']); ?>"
                                data-discount-type="<?php echo htmlspecialchars($discount['discount_type']); ?>"
                                data-value-type="<?php echo htmlspecialchars($discount['value_type']); ?>"
                                data-value="<?php echo htmlspecialchars($discount['value']); ?>"
                                data-users="<?php echo htmlspecialchars(implode('|', $discount['users'])); ?>"
                                data-start="<?php echo htmlspecialchars($discount['start']); ?>"
                                data-end="<?php echo htmlspecialchars($discount['end']); ?>"
                                data-status="<?php echo htmlspecialchars($discount['status']); ?>"
                            >
                                <td class="discount-id"><?php echo htmlspecialchars($discount['id']); ?></td>
                                <td><?php echo htmlspecialchars($discount['title']); ?></td>
                                <td><?php echo htmlspecialchars($discount['discount_type']); ?></td>
                                <td><?php echo htmlspecialchars($discount['value_type']); ?></td>
                                <td>
                                    <?php
                                    if (strtolower($discount['value_type']) === 'percentage') {
                                        echo htmlspecialchars($discount['value']) . ' %';
                                    } else {
                                        echo htmlspecialchars($discount['value']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="valid-users">
                                        <?php foreach ($discount['users'] as $user): ?>
                                            <span><?php echo htmlspecialchars($user); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($discount['start']); ?></td>
                                <td><?php echo htmlspecialchars($discount['end']); ?></td>
                                <td>
                                    <span class="status-pill <?php echo strtolower($discount['status']); ?>">
                                        <?php echo htmlspecialchars($discount['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
            <form class="discount-form">
                <div class="form-row">
                    <label>Title:</label>
                    <input type="text" placeholder="">
                </div>
                <div class="form-row">
                    <label>Discount Type:</label>
                    <select>
                        <option value="">Select type</option>
                        <option value="Membership">Membership</option>
                        <option value="Promotion">Promotion</option>
                        <option value="Bundle">Bundle</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Value Type:</label>
                    <select class="value-type">
                        <option value="Percentage">Percentage</option>
                        <option value="Fixed">Fixed</option>
                    </select>
                </div>
                <div class="form-row value-row">
                    <label>Value:</label>
                    <div class="value-input">
                        <input type="text" placeholder="">
                        <span class="value-suffix">%</span>
                    </div>
                </div>
                <div class="form-row">
                    <label>Start Date:</label>
                    <div class="date-field">
                        <input type="date">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="form-row">
                    <label>End Date:</label>
                    <div class="date-field">
                        <input type="date">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="form-row">
                    <label>Status:</label>
                    <select>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-row users-row">
                    <label>Valid Users:</label>
                    <div class="users-box">
                        <?php foreach ($validUsers as $user): ?>
                            <label class="user-check">
                                <input type="checkbox">
                                <span><?php echo htmlspecialchars($user); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <span>Unsaved data will be deleted</span>
                    <div class="modal-actions">
                        <button type="button" class="btn-save">Add</button>
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
            <form class="discount-form">
                <div class="form-row">
                    <label>Title:</label>
                    <input type="text" data-field="title">
                </div>
                <div class="form-row">
                    <label>Discount Type:</label>
                    <select data-field="discount-type">
                        <option value="Membership">Membership</option>
                        <option value="Promotion">Promotion</option>
                        <option value="Bundle">Bundle</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Value Type:</label>
                    <select class="value-type" data-field="value-type">
                        <option value="Percentage">Percentage</option>
                        <option value="Fixed">Fixed</option>
                    </select>
                </div>
                <div class="form-row value-row">
                    <label>Value:</label>
                    <div class="value-input">
                        <input type="text" data-field="value">
                        <span class="value-suffix">%</span>
                    </div>
                </div>
                <div class="form-row">
                    <label>Start Date:</label>
                    <div class="date-field">
                        <input type="date" data-field="start">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="form-row">
                    <label>End Date:</label>
                    <div class="date-field">
                        <input type="date" data-field="end">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="form-row">
                    <label>Status:</label>
                    <select data-field="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-row users-row">
                    <label>Valid Users:</label>
                    <div class="users-box" data-field="users">
                        <?php foreach ($validUsers as $user): ?>
                            <label class="user-check">
                                <input type="checkbox" value="<?php echo htmlspecialchars($user); ?>">
                                <span><?php echo htmlspecialchars($user); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" class="btn-apply">Apply Discount</button>
                <div class="modal-footer">
                    <span>Unsaved data will be deleted</span>
                    <div class="modal-actions">
                        <button type="button" class="btn-save">Save</button>
                        <button type="button" class="btn-discard">Discard</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="/admin/assets/js/discounts.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
