<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';
require_once __DIR__ . '/../database/admin/admins.php';

$isJsonRequest = admin_auth_is_json_request();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === 'invite_admin') {
            $invite = admin_invite_admin($_POST);

            if ($isJsonRequest) {
                admin_auth_json([
                    'success' => true,
                    'message' => 'Invite email sent successfully.',
                    'invite' => $invite,
                ]);
            }

            admin_auth_set_flash('Invite email sent successfully.', 'success');
            header('Location: ' . app_path('/admin/admins.php'));
            exit;
        }

        if ($action === 'delete_admin') {
            admin_remove_admin((int) ($_POST['admin_id'] ?? 0));

            if ($isJsonRequest) {
                admin_auth_json([
                    'success' => true,
                    'message' => 'Admin deleted successfully.',
                ]);
            }

            admin_auth_set_flash('Admin deleted successfully.', 'success');
            header('Location: ' . app_path('/admin/admins.php'));
            exit;
        }

        throw new InvalidArgumentException('Invalid request.');
    } catch (Throwable $exception) {
        if ($isJsonRequest) {
            admin_auth_json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        admin_auth_set_flash($exception->getMessage());
        header('Location: ' . app_path('/admin/admins.php'));
        exit;
    }
}

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$admins = admin_list_admin_cards();
$authAdmin = admin_auth_current_admin();
$flash = admin_auth_consume_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/admins.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admins-page" data-is-super-admin="<?php echo ($authAdmin['role_key'] ?? '') === 'super_admin' ? 'true' : 'false'; ?>">
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
                                <?php foreach (admin_auth_role_options() as $roleKey => $roleLabel): ?>
                                    <option value="<?php echo htmlspecialchars($roleKey); ?>"><?php echo htmlspecialchars($roleLabel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-invite" <?php echo ($authAdmin['role_key'] ?? '') === 'super_admin' ? '' : 'disabled'; ?>>+ Invite</button>
            </section>

            <div class="admins-search">
                <form class="admins-search-form admin-search-form" method="get" action="<?php echo htmlspecialchars(app_path('/admin/admins.php')); ?>">
                    <div class="admin-search-box">
                        <input type="text" id="adminsSearchInput" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Admin ID/Admin name/Email">
                        <button type="submit" class="search-icon" aria-label="Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                    <button type="submit" class="admin-search-submit">Search</button>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/admins.php')); ?>" class="admin-show-all">Show All</a>
                </form>
                <div class="filter-wrapper">
                    <button type="button" class="btn-filter" aria-label="Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" id="adminsFilterDropdown" aria-hidden="true">
                        <div class="filter-row">
                            <label for="filterAdminRole">Role</label>
                            <select id="filterAdminRole">
                                <option value="">All</option>
                                <?php foreach (admin_auth_role_options() as $roleKey => $roleLabel): ?>
                                    <option value="<?php echo htmlspecialchars($roleKey); ?>"><?php echo htmlspecialchars($roleLabel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label for="filterAdminStatus">Status</label>
                            <select id="filterAdminStatus">
                                <option value="">All</option>
                                <?php foreach (admin_auth_status_options() as $statusKey => $statusLabel): ?>
                                    <option value="<?php echo htmlspecialchars($statusKey); ?>"><?php echo htmlspecialchars($statusLabel); ?></option>
                                <?php endforeach; ?>
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
            <?php if ($admins === []): ?>
                <div class="admins-empty">No admins found.</div>
            <?php endif; ?>

            <?php foreach ($admins as $admin): ?>
                <article
                    class="admin-card"
                    data-admin-id="<?php echo (int) $admin['id']; ?>"
                    data-name="<?php echo htmlspecialchars($admin['full_name']); ?>"
                    data-email="<?php echo htmlspecialchars($admin['email']); ?>"
                    data-role="<?php echo htmlspecialchars($admin['role_key']); ?>"
                    data-status="<?php echo htmlspecialchars($admin['status_key']); ?>"
                    data-joined="<?php echo htmlspecialchars($admin['created_at']); ?>"
                >
                    <div class="admin-avatar">
                        <?php if (!empty($admin['profile_img'])): ?>
                            <img src="<?php echo htmlspecialchars($admin['profile_img']); ?>" alt="Admin profile">
                        <?php else: ?>
                            <i class="fa-regular fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <h2 class="admin-name"><?php echo htmlspecialchars($admin['full_name']); ?></h2>

                    <div class="admin-role-row">
                        <span class="label">Role</span>
                        <span class="admin-role <?php echo htmlspecialchars(str_replace('_', '-', strtolower($admin['role_key']))); ?>">
                            <?php echo htmlspecialchars($admin['role']); ?>
                        </span>
                    </div>

                    <div class="admin-status-row">
                        <span class="label">Status:</span>
                        <span class="admin-status <?php echo htmlspecialchars($admin['status_card_class']); ?>">
                            <?php echo htmlspecialchars($admin['status']); ?>
                        </span>
                    </div>

                    <div class="admin-joined-row">
                        <span class="label">Joined Since:</span>
                        <span class="value"><?php echo htmlspecialchars(date('d.m.Y H:i:s', strtotime((string) $admin['created_at']))); ?></span>
                    </div>

                    <div class="admin-actions">
                        <button
                            type="button"
                            class="icon-btn-small delete-admin"
                            aria-label="Delete admin"
                            <?php echo (($authAdmin['role_key'] ?? '') === 'super_admin' && (int) $authAdmin['id'] !== (int) $admin['id']) ? '' : 'disabled'; ?>
                        >
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if (count($admins) > 0): ?>
            <div class="admins-pagination">
                <button type="button" class="page-btn page-arrow"><i class="fa-solid fa-arrow-left"></i></button>
                <button type="button" class="page-btn page-number active">1</button>
                <button type="button" class="page-btn page-number">2</button>
                <button type="button" class="page-btn page-number">3</button>
                <button type="button" class="page-btn page-number">4</button>
                <button type="button" class="page-btn page-number">5</button>
                <button type="button" class="page-btn page-arrow"><i class="fa-solid fa-arrow-right"></i></button>
            </div>
        <?php endif; ?>
    </main>

    <?php if (is_array($flash) && !empty($flash['message'])): ?>
        <script>
            window.__adminFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
        </script>
    <?php endif; ?>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admins.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

