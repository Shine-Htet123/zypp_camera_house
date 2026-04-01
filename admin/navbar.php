<div class="admin-layout">
    <?php
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../database/admin/my_account.php';
    require_once __DIR__ . '/../database/admin/notifications.php';
    require_once __DIR__ . '/../database/admin/orders.php';

    $authAdmin = isset($currentAdmin) && is_array($currentAdmin) ? $currentAdmin : admin_auth_current_admin();
    $currentAdminProfile = admin_fetch_current_profile();
    admin_my_account_sync_session($currentAdminProfile);

    $adminAvatar = (string) ($currentAdminProfile['avatar'] ?? '');
    $adminAvatarUrl = $adminAvatar;
    if ($adminAvatar !== '' && !str_starts_with($adminAvatar, 'http://') && !str_starts_with($adminAvatar, 'https://')) {
        $adminAvatarUrl = app_path($adminAvatar);
    }

    $adminName = (string) ($currentAdminProfile['full_name'] ?? $authAdmin['full_name'] ?? 'Admin User');
    $adminRole = (string) ($currentAdminProfile['role'] ?? $authAdmin['role'] ?? 'Admin');
    $adminRoleKey = (string) ($authAdmin['role_key'] ?? '');
    $adminRoleClass = match ($adminRoleKey) {
        'super_admin' => 'super-admin-color',
        'admin' => 'admin-color',
        default => 'support-color',
    };

    $can = static fn (string $permission): bool => admin_auth_has_permission($authAdmin, $permission);

    $canManageCatalog = $can('manage_catalog');
    $canManageDiscounts = $can('manage_discounts');
    $canManageDiscountRules = $can('manage_discount_rules');
    $canManageOrders = $can('manage_orders');
    $canManagePaymentProofs = $can('manage_payment_proofs');
    $canManageDeliveryMethods = $can('manage_delivery_methods');
    $canManageAdmins = $can('manage_admins');
    $canManageCustomers = $can('manage_customers');
    $canManageContent = $can('manage_content');
    $canManageWholesale = $can('manage_wholesale');
    $canManageMedia = $can('manage_media');
    $canManageMembershipTiers = $can('manage_membership_tiers');
    $canManageBundles = $can('manage_bundles');

    $showDiscountGroup = $canManageDiscounts || $canManageDiscountRules;
    $showUsersGroup = $canManageAdmins || $canManageCustomers;
    $showTidioDashboard = in_array($adminRoleKey, ['super_admin', 'admin'], true);
    $visibleNotificationPermissions = array_values(array_filter([
        $canManageOrders ? 'manage_orders' : '',
        $canManagePaymentProofs ? 'manage_payment_proofs' : '',
        $canManageWholesale ? 'manage_wholesale' : '',
    ]));
    $notificationSnapshot = $visibleNotificationPermissions !== []
        ? admin_notifications_fetch_snapshot($visibleNotificationPermissions, (int) ($authAdmin['id'] ?? 0), null, 12)
        : ['unread_count' => 0, 'latest_marker' => '', 'notifications' => [], 'recent_unread_notifications' => []];
    $notificationCount = (int) ($notificationSnapshot['unread_count'] ?? 0);
    $notificationMarker = (string) ($notificationSnapshot['latest_marker'] ?? '');
    $notificationItems = (array) ($notificationSnapshot['notifications'] ?? []);
    $notificationToastSeed = json_encode(
        (array) ($notificationSnapshot['recent_unread_notifications'] ?? []),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    $showFullscreenLoader = !empty($_SESSION['admin_show_fullscreen_loader_once']);
    unset($_SESSION['admin_show_fullscreen_loader_once']);
    ?>
    <div
        class="admin-loading-screen<?php echo $showFullscreenLoader ? '' : ' is-hidden'; ?>"
        data-admin-loader
        data-active="<?php echo $showFullscreenLoader ? 'true' : 'false'; ?>"
        aria-hidden="<?php echo $showFullscreenLoader ? 'false' : 'true'; ?>"
    >
        <div class="admin-loading-visual">
            <div class="orbit-loader orbit-loader--admin" aria-hidden="true">
                <span class="orbit-loader__core">
                    <img src="<?php echo htmlspecialchars(app_path('/storage/uploads/contents/logo.png')); ?>" alt="ZYPP Camera House">
                </span>
            </div>
        </div>
    </div>
    <header class="admin-header">
        <div class="admin-brand">
            <button type="button" class="admin-toggle" aria-label="Toggle menu" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>
            <img src="<?php echo htmlspecialchars(app_path('/storage/uploads/contents/logo.png')); ?>" alt="ZYPP Camera House">
        </div>
        <div class="admin-actions">
            <?php if ($visibleNotificationPermissions !== []): ?>
                <div
                    class="admin-notification-shell"
                    data-admin-notifications
                    data-api-url="<?php echo htmlspecialchars(app_path('/admin/notifications-api.php')); ?>"
                    data-initial-count="<?php echo $notificationCount; ?>"
                    data-initial-marker="<?php echo htmlspecialchars($notificationMarker); ?>"
                >
                    <button
                        type="button"
                        class="icon-btn notification-btn"
                        aria-label="Notifications"
                        aria-expanded="false"
                        data-admin-notification-toggle
                    >
                        <i class="fa-solid fa-bell"></i>
                        <span class="badge" <?php echo $notificationCount > 0 ? '' : 'hidden'; ?>><?php echo $notificationCount; ?></span>
                    </button>
                    <div class="admin-notification-dropdown" data-admin-notification-dropdown hidden>
                        <div class="admin-notification-dropdown__head">
                            <strong>Notifications</strong>
                        </div>
                        <div class="admin-notification-list" data-admin-notification-list>
                            <?php if ($notificationItems === []): ?>
                                <div class="admin-notification-empty" data-admin-notification-empty>No notifications yet.</div>
                            <?php else: ?>
                                <?php foreach ($notificationItems as $notification): ?>
                                    <article class="admin-notification-item<?php echo !empty($notification['is_unread']) ? ' is-unread' : ''; ?>" data-notification-id="<?php echo (int) ($notification['notification_id'] ?? 0); ?>">
                                        <a class="admin-notification-item__link" href="<?php echo htmlspecialchars((string) ($notification['target_url'] ?? app_path('/admin/index.php'))); ?>">
                                            <span class="admin-notification-item__dot" aria-hidden="true"></span>
                                            <strong><?php echo htmlspecialchars((string) ($notification['title'] ?? 'Notification')); ?></strong>
                                            <span><?php echo htmlspecialchars((string) ($notification['body'] ?? '')); ?></span>
                                            <time><?php echo htmlspecialchars((string) ($notification['created_at_display'] ?? '')); ?></time>
                                        </a>
                                        <button type="button" class="admin-notification-item__delete" aria-label="Delete notification" data-admin-notification-delete>&times;</button>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <script type="application/json" data-admin-notification-initial-toasts><?php echo $notificationToastSeed ?: '[]'; ?></script>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars(app_path('/admin/my-account.php')); ?>" class="admin-user" aria-label="My account">
                <div class="avatar">
                    <img src="<?php echo htmlspecialchars($adminAvatarUrl); ?>" alt="Admin profile" <?php echo empty($adminAvatarUrl) ? 'hidden' : ''; ?>>
                    <div class="avatar-fallback">
                        <i class="fa-regular fa-user"></i>
                    </div>
                </div>
                <div class="user-info">
                    <span class="name"><?php echo htmlspecialchars($adminName); ?></span>
                    <span class="role <?php echo htmlspecialchars($adminRoleClass); ?>"><?php echo htmlspecialchars($adminRole); ?></span>
                </div>
            </a>
        </div>
    </header>
    <div class="admin-progress" data-admin-progress aria-hidden="true">
        <span class="admin-progress__bar" data-admin-progress-bar></span>
    </div>

    <div class="admin-body">
        <div class="admin-overlay" aria-hidden="true"></div>
        <aside class="admin-sidebar">
            <div class="sidebar-main">
                <div class="sidebar-title">Main Menu</div>
                <nav class="sidebar-nav">
                    <a href="<?php echo htmlspecialchars(app_path('/admin/index.php')); ?>" class="nav-link">Dashboard</a>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/my-account.php')); ?>" class="nav-link">My Account</a>

                    <?php if ($canManageCatalog): ?>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/products.php')); ?>" class="nav-link">Products</a>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/category-brand.php')); ?>" class="nav-link">Category &amp; Brand</a>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/unique-selling-points.php')); ?>" class="nav-link">Unique Selling Points</a>
                    <?php endif; ?>

                    <?php if ($showDiscountGroup): ?>
                        <button class="nav-group-toggle" data-target="discounts" aria-expanded="false">
                            <span>Discounts</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="nav-submenu" id="discounts">
                            <?php if ($canManageDiscounts): ?>
                                <a href="<?php echo htmlspecialchars(app_path('/admin/discounts.php')); ?>" class="nav-link sub">Discount Management</a>
                            <?php endif; ?>
                            <?php if ($canManageDiscountRules): ?>
                                <a href="<?php echo htmlspecialchars(app_path('/admin/discount-stack-rules.php')); ?>" class="nav-link sub">Priority &amp; Stack Rules</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($canManageOrders): ?>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/orders.php')); ?>" class="nav-link">Orders</a>
                    <?php endif; ?>

                    <?php if ($canManagePaymentProofs): ?>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/payment-proof-uploads.php')); ?>" class="nav-link">Payment Proof Uploads</a>
                    <?php endif; ?>

                    <?php if ($canManageDeliveryMethods): ?>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/delivery-methods.php')); ?>" class="nav-link">Delivery Methods</a>
                    <?php endif; ?>

                    <?php if ($showUsersGroup): ?>
                        <button class="nav-group-toggle" data-target="users" aria-expanded="false">
                            <span>Users</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="nav-submenu" id="users">
                            <?php if ($canManageAdmins): ?>
                                <a href="<?php echo htmlspecialchars(app_path('/admin/admins.php')); ?>" class="nav-link sub">Admins</a>
                            <?php endif; ?>
                            <?php if ($canManageCustomers): ?>
                                <a href="<?php echo htmlspecialchars(app_path('/admin/customers.php')); ?>" class="nav-link sub">Customers</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($canManageContent): ?>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/content-management.php')); ?>" class="nav-link">Content Management</a>
                    <?php endif; ?>

                    <?php if ($canManageWholesale): ?>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/wholesale-survey.php')); ?>" class="nav-link">Wholesale Survey</a>
                    <?php endif; ?>

                    <?php if ($canManageMedia): ?>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/unboxing-influencers.php')); ?>" class="nav-link">Media</a>
                    <?php endif; ?>

                    <?php if ($canManageMembershipTiers): ?>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/membership-tiers.php')); ?>" class="nav-link">Membership Tiers</a>
                    <?php endif; ?>

                    <?php if ($canManageBundles): ?>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/bundles.php')); ?>" class="nav-link">Bundles</a>
                    <?php endif; ?>

                    <?php if ($showTidioDashboard): ?>
                        <a href="https://www.tidio.com/panel/" class="nav-link" target="_blank" rel="noopener noreferrer">TIDIO Dashboard</a>
                    <?php endif; ?>
                </nav>
            </div>

            <a class="logout-btn" href="<?php echo htmlspecialchars(app_path('/admin/logout.php?redirect_to=' . rawurlencode(app_path('/index.php')))); ?>">Log Out</a>
        </aside>
