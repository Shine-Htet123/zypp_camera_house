<?php
$customers = [
    [
        'id' => 'ZCU202601260001',
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'phone' => '09123456789',
        'township' => 'North Oakkalapa',
        'city' => 'Yangon',
        'member_level' => 'Gold',
        'status' => 'Suspended',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'id' => 'ZCU202601260001',
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'phone' => '09123456789',
        'township' => 'North Oakkalapa',
        'city' => 'Yangon',
        'member_level' => 'Platinum',
        'status' => 'Active',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'id' => 'ZCU202601260001',
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'phone' => '09123456789',
        'township' => 'North Oakkalapa',
        'city' => 'Yangon',
        'member_level' => 'Diamond',
        'status' => 'Active',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'id' => 'ZCU202601260001',
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'phone' => '09123456789',
        'township' => 'North Oakkalapa',
        'city' => 'Yangon',
        'member_level' => 'Platinum',
        'status' => 'Suspended',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'id' => 'ZCU202601260001',
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'phone' => '09123456789',
        'township' => 'North Oakkalapa',
        'city' => 'Yangon',
        'member_level' => 'Diamond',
        'status' => 'Active',
        'joined' => '12.02.2025 12:00:00',
    ],
    [
        'id' => 'ZCU202601260001',
        'name' => 'John Doe',
        'email' => 'example@email.com',
        'phone' => '09123456789',
        'township' => 'North Oakkalapa',
        'city' => 'Yangon',
        'member_level' => 'Gold',
        'status' => 'Cancelled',
        'joined' => '12.02.2025 12:00:00',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/customers.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content customers-page">
        <header class="customers-header">
            <h1>Customers</h1>
            <div class="customers-search">
                <div class="search-field">
                    <input type="text" placeholder="Customer ID/Customer Name/Email">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <button type="button" class="btn-search">Search</button>
                <div class="filter-wrapper">
                    <button type="button" class="btn-filter" aria-label="Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" id="customersFilterDropdown" aria-hidden="true">
                        <div class="filter-row">
                            <label for="filterMemberLevel">Member Level</label>
                            <select id="filterMemberLevel">
                                <option value="">All</option>
                                <option value="Gold">Gold</option>
                                <option value="Platinum">Platinum</option>
                                <option value="Diamond">Diamond</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label for="filterCustomerStatus">Status</label>
                            <select id="filterCustomerStatus">
                                <option value="">All</option>
                                <option value="Active">Active</option>
                                <option value="Suspended">Suspended</option>
                                <option value="Cancelled">Cancelled</option>
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

        <section class="customers-table">
            <div class="customers-scroll">
                <div class="customers-head">
                    <span>ID</span>
                    <span>Name</span>
                    <span>Email</span>
                    <span>Phone</span>
                    <span>Township</span>
                    <span>City</span>
                    <span>Member Level</span>
                    <span>Status</span>
                    <span>Actions</span>
                    <span>Joined Since</span>
                </div>
                <div class="customers-body">
                    <?php foreach ($customers as $customer): ?>
                        <div
                            class="customers-row"
                            data-id="<?php echo htmlspecialchars($customer['id']); ?>"
                            data-name="<?php echo htmlspecialchars($customer['name']); ?>"
                            data-email="<?php echo htmlspecialchars($customer['email']); ?>"
                            data-phone="<?php echo htmlspecialchars($customer['phone']); ?>"
                            data-township="<?php echo htmlspecialchars($customer['township']); ?>"
                            data-city="<?php echo htmlspecialchars($customer['city']); ?>"
                            data-member-level="<?php echo htmlspecialchars($customer['member_level']); ?>"
                            data-status="<?php echo htmlspecialchars($customer['status']); ?>"
                            data-joined="<?php echo htmlspecialchars($customer['joined']); ?>"
                        >
                            <span class="customer-link customer-id-link"><?php echo htmlspecialchars($customer['id']); ?></span>
                            <span><?php echo htmlspecialchars($customer['name']); ?></span>
                            <span><?php echo htmlspecialchars($customer['email']); ?></span>
                            <span><?php echo htmlspecialchars($customer['phone']); ?></span>
                            <span><?php echo htmlspecialchars($customer['township']); ?></span>
                            <span><?php echo htmlspecialchars($customer['city']); ?></span>
                            <span class="member-level <?php echo strtolower($customer['member_level']); ?>">
                                <?php echo htmlspecialchars($customer['member_level']); ?>
                            </span>
                            <span class="status customer-status <?php echo strtolower($customer['status']); ?>">
                                <?php echo htmlspecialchars($customer['status']); ?>
                            </span>
                            <span class="customer-actions">
                                <i class="fa-regular fa-pen-to-square edit-status"></i>
                            </span>
                            <span class="customer-joined"><?php echo htmlspecialchars($customer['joined']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <script src="/admin/assets/js/customers.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>

