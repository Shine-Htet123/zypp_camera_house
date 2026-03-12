<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

require_once __DIR__ . '/../database/user/customers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    header('Content-Type: application/json');

    try {
        echo json_encode(
            update_customer_status_by_public_id(
                (string) ($_POST['customer_id'] ?? ''),
                (string) ($_POST['status'] ?? '')
            )
        );
    } catch (Throwable $exception) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $exception->getMessage(),
        ]);
    }

    exit;
}

$customers = [];
$memberLevels = [];
$databaseError = null;
$searchQuery = trim((string) ($_GET['q'] ?? ''));

try {
    $customerData = fetch_admin_customers();
    $customers = $customerData['customers'];
    $memberLevels = array_fill_keys($customerData['member_level_options'], true);
} catch (Throwable $exception) {
    $databaseError = $exception->getMessage();
}

$memberLevelOptions = array_keys($memberLevels);
sort($memberLevelOptions, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/customers.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content customers-page">
        <header class="customers-header">
            <h1>Customers</h1>
                <div class="customers-search">
                    <form class="customers-search-form admin-search-form" method="get" action="<?php echo htmlspecialchars(app_path('/admin/customers.php')); ?>">
                        <div class="admin-search-box">
                            <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Customer ID/Customer Name/Email">
                            <button type="submit" class="search-icon" aria-label="Search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                        <button type="submit" class="admin-search-submit">Search</button>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/customers.php')); ?>" class="admin-show-all">Show All</a>
                    </form>
                <div class="filter-wrapper">
                    <button type="button" class="btn-filter" aria-label="Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" id="customersFilterDropdown" aria-hidden="true">
                        <div class="filter-row">
                            <label for="filterMemberLevel">Member Level</label>
                            <select id="filterMemberLevel">
                                <option value="">All</option>
                                <?php foreach ($memberLevelOptions as $memberLevelOption): ?>
                                    <option value="<?php echo htmlspecialchars($memberLevelOption); ?>">
                                        <?php echo htmlspecialchars($memberLevelOption); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label for="filterCustomerStatus">Status</label>
                            <select id="filterCustomerStatus">
                                <option value="">All</option>
                                <option value="Active">Active</option>
                                <option value="Suspended">Suspended</option>
                                <option value="Banned">Banned</option>
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

        <section class="customers-table" data-update-url="<?php echo htmlspecialchars(app_path('/admin/customers.php')); ?>">
            <div class="customers-scroll table-scroll">
                <table class="admin-table customers-table-grid">
                    <thead>
                        <tr class="customers-head">
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Township</th>
                            <th>City</th>
                            <th>Member Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                            <th>Joined Since</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($databaseError !== null): ?>
                            <tr>
                                <td colspan="10" class="customers-empty-state">
                                    Failed to load customers. <?php echo htmlspecialchars($databaseError); ?>
                                </td>
                            </tr>
                        <?php elseif (empty($customers)): ?>
                            <tr>
                                <td colspan="10" class="customers-empty-state">
                                    No customers found.
                                </td>
                            </tr>
                        <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr
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
                                <td class="customer-link customer-id-link"><?php echo htmlspecialchars($customer['id']); ?></td>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                <td><?php echo htmlspecialchars($customer['township']); ?></td>
                                <td><?php echo htmlspecialchars($customer['city']); ?></td>
                                <td>
                                    <span class="member-level <?php echo strtolower($customer['member_level']); ?>">
                                        <?php echo htmlspecialchars($customer['member_level']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status customer-status <?php echo strtolower($customer['status']); ?>">
                                        <?php echo htmlspecialchars($customer['status']); ?>
                                    </span>
                                </td>
                                <td class="customer-actions">
                                    <button type="button" class="icon-btn-small edit-status" aria-label="Edit status">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                </td>
                                <td class="customer-joined"><?php echo htmlspecialchars($customer['joined']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/customers.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>




