<?php
$priorityTypes = ['Membership', 'Promotion', 'Bundle'];
$stackRules = [
    ['no' => 1, 'type_a' => 'Membership', 'type_b' => 'Promotion', 'allowed' => 'Yes'],
    ['no' => 2, 'type_a' => 'Membership', 'type_b' => 'Bundle', 'allowed' => 'No'],
    ['no' => 3, 'type_a' => 'Promotion', 'type_b' => 'Membership', 'allowed' => 'No'],
    ['no' => 4, 'type_a' => 'Promotion', 'type_b' => 'Bundle', 'allowed' => 'No'],
    ['no' => 5, 'type_a' => 'Bundle', 'type_b' => 'Membership', 'allowed' => 'No'],
    ['no' => 6, 'type_a' => 'Bundle', 'type_b' => 'Promotion', 'allowed' => 'No'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/discount-stack-rules.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content discount-stack-page">
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
                            <li class="priority-item" draggable="true" data-type="<?php echo htmlspecialchars($type); ?>">
                                <span class="priority-number"><?php echo $index + 1; ?></span>
                                <span class="priority-tag"><?php echo htmlspecialchars($type); ?></span>
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
                                <tr data-allowed="<?php echo htmlspecialchars($rule['allowed']); ?>">
                                    <td><?php echo htmlspecialchars($rule['no']); ?>.</td>
                                    <td><?php echo htmlspecialchars($rule['type_a']); ?></td>
                                    <td><?php echo htmlspecialchars($rule['type_b']); ?></td>
                                    <td class="allowed-cell">
                                        <span class="allowed-pill <?php echo strtolower($rule['allowed']); ?>">
                                            <?php echo htmlspecialchars($rule['allowed']); ?>
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
                <button class="btn-save" type="button">Save</button>
                <button class="btn-discard" type="button">Discard</button>
            </div>
        </div>
    </main>

    <script src="/admin/assets/js/discount-stack-rules.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
