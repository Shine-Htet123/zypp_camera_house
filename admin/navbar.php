<div class="admin-layout">
    <header class="admin-header">
        <div class="admin-brand">
            <button type="button" class="admin-toggle" aria-label="Toggle menu" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>
            <img src="/storage/uploads/contents/logo.png" alt="ZYPP Camera House">
        </div>
        <div class="admin-actions">
            <a href="/admin/payment-proof-uploads.php" class="icon-btn notification-btn" aria-label="Notifications">
                <i class="fa-solid fa-bell"></i>
                <span class="badge">10</span>
            </a>
            <div class="admin-user">
                <div class="avatar">
                    <i class="fa-regular fa-user"></i>
                </div>
                <div class="user-info">
                    <span class="name">John Doe</span>
                    <span class="role super-admin-color">Super-admin</span>
                </div>
            </div>
        </div>
    </header>

    <div class="admin-body">
        <div class="admin-overlay" aria-hidden="true"></div>
        <aside class="admin-sidebar">
            <div class="sidebar-main">
                <div class="sidebar-title">Main Menu</div>
                <nav class="sidebar-nav">
                    <a href="/admin/index.php" class="nav-link">Dashboard</a>
                    <a href="/admin/my-account.php" class="nav-link">My Account</a>
                    <a href="/admin/products.php" class="nav-link">Products</a>
                    <a href="/admin/category-brand.php" class="nav-link">Category &amp; Brand</a>
                    <a href="/admin/unique-selling-points.php" class="nav-link">Unique Selling Points</a>

                    <button class="nav-group-toggle" data-target="discounts" aria-expanded="false">
                        <span>Discounts</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="nav-submenu" id="discounts">
                        <a href="/admin/discounts.php" class="nav-link sub">Discount Management</a>
                        <a href="/admin/discount-stack-rules.php" class="nav-link sub">Priority &amp; Stack Rules</a>
                    </div>

                    <a href="/admin/orders.php" class="nav-link">Orders</a>

                    <button class="nav-group-toggle" data-target="users" aria-expanded="false">
                        <span>Users</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="nav-submenu" id="users">
                        <a href="/admin/admins.php" class="nav-link sub">Admins</a>
                        <a href="/admin/customers.php" class="nav-link sub">Customers</a>
                    </div>

                    <a href="#" class="nav-link">Content Management</a>
                    <a href="/admin/wholesale-survey.php" class="nav-link">Wholesale Survey</a>
                    <a href="/admin/unboxing-influencers.php" class="nav-link">Media</a>
                    <a href="/admin/membership-tiers.php" class="nav-link">Membership Tiers</a>
                    <a href="#" class="nav-link">Bundles</a>
                    <a href="#" class="nav-link">TIDIO Dashboard</a>
                </nav>
            </div>

            <button class="logout-btn" type="button">Log Out</button>
        </aside>
