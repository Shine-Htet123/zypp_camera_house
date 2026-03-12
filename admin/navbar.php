<div class="admin-layout">
    <?php
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../database/admin/my_account.php';
    require_once __DIR__ . '/../database/admin/orders.php';
    $currentAdminProfile = admin_fetch_current_profile();
    admin_my_account_sync_session($currentAdminProfile);
    $adminAvatar = (string) ($currentAdminProfile['avatar'] ?? '');
    $adminAvatarUrl = $adminAvatar;
    if ($adminAvatar !== '' && !str_starts_with($adminAvatar, 'http://') && !str_starts_with($adminAvatar, 'https://')) {
        $adminAvatarUrl = app_path($adminAvatar);
    }
    $adminName = (string) ($currentAdminProfile['full_name'] ?? 'Admin User');
    $adminRole = (string) ($currentAdminProfile['role'] ?? 'Admin');
    $pendingPaymentProofCount = admin_fetch_payment_proof_notification_count();
    ?>
    <div class="admin-loading-screen" data-admin-loader aria-hidden="false">
        <div class="admin-loading-visual">
            <video class="admin-loading-video" autoplay muted playsinline preload="auto">
                <source src="<?php echo htmlspecialchars(app_path('/assets/images/Search-box-loading.webm')); ?>" type="video/webm">
            </video>
            <div class="admin-loading-fallback" aria-hidden="true">
                <img src="<?php echo htmlspecialchars(app_path('/storage/uploads/contents/logo.png')); ?>" alt="">
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
            <a href="<?php echo htmlspecialchars(app_path('/admin/payment-proof-uploads.php')); ?>" class="icon-btn notification-btn" aria-label="Notifications">
                <i class="fa-solid fa-bell"></i>
                <?php if ($pendingPaymentProofCount > 0): ?>
                    <span class="badge"><?php echo (int) $pendingPaymentProofCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo htmlspecialchars(app_path('/admin/my-account.php')); ?>" class="admin-user" aria-label="My account">
                <div class="avatar">
                    <img src="<?php echo htmlspecialchars($adminAvatarUrl); ?>" alt="Admin profile" <?php echo empty($adminAvatarUrl) ? 'hidden' : ''; ?>>
                    <div class="avatar-fallback">
                        <i class="fa-regular fa-user"></i>
                    </div>
                </div>
                <div class="user-info">
                    <span class="name"><?php echo htmlspecialchars($adminName); ?></span>
                    <span class="role super-admin-color"><?php echo htmlspecialchars($adminRole); ?></span>
                </div>
            </a>
        </div>
    </header>

    <div class="admin-body">
        <div class="admin-overlay" aria-hidden="true"></div>
        <aside class="admin-sidebar">
            <div class="sidebar-main">
                <div class="sidebar-title">Main Menu</div>
                <nav class="sidebar-nav">
                    <a href="<?php echo htmlspecialchars(app_path('/admin/index.php')); ?>" class="nav-link">Dashboard</a>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/my-account.php')); ?>" class="nav-link">My Account</a>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/products.php')); ?>" class="nav-link">Products</a>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/category-brand.php')); ?>" class="nav-link">Category &amp; Brand</a>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/unique-selling-points.php')); ?>" class="nav-link">Unique Selling Points</a>

                    <button class="nav-group-toggle" data-target="discounts" aria-expanded="false">
                        <span>Discounts</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="nav-submenu" id="discounts">
                        <a href="<?php echo htmlspecialchars(app_path('/admin/discounts.php')); ?>" class="nav-link sub">Discount Management</a>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/discount-stack-rules.php')); ?>" class="nav-link sub">Priority &amp; Stack Rules</a>
                    </div>

                    <a href="<?php echo htmlspecialchars(app_path('/admin/orders.php')); ?>" class="nav-link">Orders</a>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/delivery-methods.php')); ?>" class="nav-link">Delivery Methods</a>

                    <button class="nav-group-toggle" data-target="users" aria-expanded="false">
                        <span>Users</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="nav-submenu" id="users">
                        <a href="<?php echo htmlspecialchars(app_path('/admin/admins.php')); ?>" class="nav-link sub">Admins</a>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/customers.php')); ?>" class="nav-link sub">Customers</a>
                    </div>

                    <a href="<?php echo htmlspecialchars(app_path('/admin/content-management.php')); ?>" class="nav-link">Content Management</a>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/wholesale-survey.php')); ?>" class="nav-link">Wholesale Survey</a>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/unboxing-influencers.php')); ?>" class="nav-link">Media</a>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/membership-tiers.php')); ?>" class="nav-link">Membership Tiers</a>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/bundles.php')); ?>" class="nav-link">Bundles</a>
                    <a href="https://www.tidio.com/panel/" class="nav-link" target="_blank" rel="noopener noreferrer">TIDIO Dashboard</a>
                </nav>
            </div>

            <a class="logout-btn" href="<?php echo htmlspecialchars(app_path('/admin/logout.php?redirect_to=' . rawurlencode(app_path('/index.php')))); ?>">Log Out</a>
        </aside>

