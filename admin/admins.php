<?php
$admins = [
    [
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'role' => 'Super-Admin',
        'status' => 'Online',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'role' => 'Admin',
        'status' => 'Offline',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'role' => 'Admin',
        'status' => 'Online',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'role' => 'Admin',
        'status' => 'Offline',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'role' => 'Admin',
        'status' => 'Offline',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'role' => 'Admin',
        'status' => 'Offline',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'role' => 'Admin',
        'status' => 'Offline',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'role' => 'Admin',
        'status' => 'Offline',
        'joined' => '12.02.2025 12:00:00',
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/admins.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admins-page">
        <header class="admins-header">
            <h1>Admins</h1>

            <section class="invite-bar">
                <div class="invite-bar-main">
                    <h2 class="invite-title">Invite New Admin</h2>
                    <div class="invite-fields">
                        <div class="invite-field invite-email">
                            <label for="inviteEmail">Email:</label>
                            <input id="inviteEmail" type="email" placeholder="Admin email">
                        </div>
                        <div class="invite-field invite-role">
                            <label for="inviteRole">Role:</label>
                            <select id="inviteRole">
                                <option value="">Select role</option>
                                <option value="Super-Admin">Super-Admin</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-invite">+ Invite</button>
            </section>

            <div class="admins-search">
                <div class="search-field">
                    <input type="text" placeholder="Admin ID/Admin name/Email">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <button type="button" class="btn-search">Search</button>
                <div class="filter-wrapper">
                    <button type="button" class="btn-filter" aria-label="Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" id="adminsFilterDropdown" aria-hidden="true">
                        <div class="filter-row">
                            <label for="filterAdminRole">Role</label>
                            <select id="filterAdminRole">
                                <option value="">All</option>
                                <option value="Super-Admin">Super-Admin</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label for="filterAdminStatus">Status</label>
                            <select id="filterAdminStatus">
                                <option value="">All</option>
                                <option value="Active">Active</option>
                                <option value="Suspended">Suspended</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="button" class="btn-filter-apply">Apply</button>
                            <button type="button" class="btn-filter-clear">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="admins-grid">
            <?php foreach ($admins as $admin): ?>
                <?php
                $statusLabel = $admin['status'];
                if (strtolower($admin['status']) === 'online') {
                    $statusLabel = 'Active';
                } elseif (strtolower($admin['status']) === 'offline') {
                    $statusLabel = 'Inactive';
                }
                ?>
                <article
                    class="admin-card"
                    data-name="<?php echo htmlspecialchars($admin['name']); ?>"
                    data-email="<?php echo htmlspecialchars($admin['email']); ?>"
                    data-role="<?php echo htmlspecialchars($admin['role']); ?>"
                    data-status="<?php echo htmlspecialchars($admin['status']); ?>"
                    data-joined="<?php echo htmlspecialchars($admin['joined']); ?>"
                >
                    <div class="admin-avatar">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <h2 class="admin-name"><?php echo htmlspecialchars($admin['name']); ?></h2>

                    <div class="admin-role-row">
                        <span class="label">Role</span>
                        <span class="admin-role <?php echo strtolower(str_replace(' ', '-', $admin['role'])); ?>">
                            <?php echo htmlspecialchars($admin['role']); ?>
                        </span>
                    </div>

                    <div class="admin-status-row">
                        <span class="label">Status:</span>
                        <span class="admin-status <?php echo strtolower($admin['status']); ?>">
                            <?php echo htmlspecialchars($statusLabel); ?>
                        </span>
                    </div>

                    <div class="admin-joined-row">
                        <span class="label">Joined Since:</span>
                        <span class="value"><?php echo htmlspecialchars($admin['joined']); ?></span>
                    </div>

                    <div class="admin-actions">
                        <button type="button" class="icon-btn-small delete-admin" aria-label="Delete admin">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <div class="admins-pagination">
            <button type="button" class="page-btn page-arrow"><i class="fa-solid fa-arrow-left"></i></button>
            <button type="button" class="page-btn page-number active">1</button>
            <button type="button" class="page-btn page-number">2</button>
            <button type="button" class="page-btn page-number">3</button>
            <button type="button" class="page-btn page-number">4</button>
            <button type="button" class="page-btn page-number">5</button>
            <button type="button" class="page-btn page-arrow"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </main>

    <script src="/admin/assets/js/admins.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
