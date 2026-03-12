<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';
require_once __DIR__ . '/../database/admin/orders.php';

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$orders = admin_fetch_orders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/orders.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content orders-page">
        <header class="orders-header">
            <h1>Orders</h1>
            <div class="orders-search">
                <form class="orders-search-form admin-search-form" method="get" action="<?php echo htmlspecialchars(app_path('/admin/orders.php')); ?>">
                    <div class="admin-search-box">
                        <input type="text" id="ordersSearchInput" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Order ID/Customer ID">
                        <button type="submit" class="search-icon" aria-label="Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                    <button type="submit" class="admin-search-submit" id="ordersSearchButton">Search</button>
                    <a href="<?php echo htmlspecialchars(app_path('/admin/orders.php')); ?>" class="admin-show-all">Show All</a>
                </form>
                <div class="filter-wrapper">
                    <button type="button" class="btn-filter" aria-label="Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" id="ordersFilterDropdown" aria-hidden="true">
                        <div class="filter-row">
                            <label for="filterPaymentStatus">Payment Status</label>
                            <select id="filterPaymentStatus">
                                <option value="">All</option>
                                <?php foreach (admin_orders_payment_status_options() as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label for="filterOrderStatus">Order Status</label>
                            <select id="filterOrderStatus">
                                <option value="">All</option>
                                <?php foreach (admin_orders_order_status_options() as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
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

        <section class="orders-table">
            <div class="orders-scroll table-scroll">
                <table class="admin-table orders-table-grid">
                    <thead>
                        <tr class="orders-head">
                            <th>Order No.</th>
                            <th>Customer</th>
                            <th>Name</th>
                            <th>Total<br>(MMK)</th>
                            <th>Payment</th>
                            <th>Payment<br>Status</th>
                            <th>Order<br>Status</th>
                            <th>Action</th>
                            <th>Order Time</th>
                        </tr>
                    </thead>
                    <tbody class="orders-body">
                        <tr class="orders-empty-row"<?php echo $orders === [] ? '' : ' style="display:none"'; ?>>
                            <td colspan="9">No orders found.</td>
                        </tr>
                        <?php foreach ($orders as $order): ?>
                            <tr
                                class="orders-row"
                                data-order-id="<?php echo (int) $order['id']; ?>"
                                data-order-no="<?php echo htmlspecialchars((string) $order['order_no_display']); ?>"
                                data-customer="<?php echo htmlspecialchars((string) $order['public_user_id']); ?>"
                                data-name="<?php echo htmlspecialchars((string) $order['customer_name']); ?>"
                                data-total="<?php echo htmlspecialchars((string) $order['total_display']); ?>"
                                data-payment="<?php echo htmlspecialchars((string) $order['payment_method_label']); ?>"
                                data-payment-status="<?php echo htmlspecialchars((string) $order['payment_status_label']); ?>"
                                data-order-status="<?php echo htmlspecialchars((string) $order['order_status_label']); ?>"
                                data-order-time="<?php echo htmlspecialchars((string) $order['time_display']); ?>"
                            >
                                <td class="order-link order-id-link"><?php echo htmlspecialchars((string) $order['order_no_display']); ?></td>
                                <td><?php echo htmlspecialchars((string) $order['public_user_id']); ?></td>
                                <td><?php echo htmlspecialchars((string) $order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars((string) $order['total_display']); ?></td>
                                <td><?php echo htmlspecialchars((string) $order['payment_method_label']); ?></td>
                                <td>
                                    <span class="status payment <?php echo strtolower((string) $order['payment_status_label']); ?>">
                                        <?php echo htmlspecialchars((string) $order['payment_status_label']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status order <?php echo strtolower((string) $order['order_status_label']); ?>">
                                        <?php echo htmlspecialchars((string) $order['order_status_label']); ?>
                                    </span>
                                </td>
                                <td class="order-actions">
                                    <i class="fa-regular fa-pen-to-square edit-status"></i>
                                </td>
                                <td class="order-time"><?php echo htmlspecialchars((string) $order['time_display']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="modal-overlay" id="orderModal" aria-hidden="true">
            <div class="modal-card order-modal" role="dialog" aria-modal="true">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 class="modal-title">
                    Order No. <span id="modalOrderNo">#-</span>
                </h2>
                <p class="modal-subtitle">
                    Order Date - <span id="modalOrderDate">-</span>
                </p>
                <h3 class="modal-section-title">Order Summary</h3>

                <div class="summary-table" id="orderSummaryTable">
                    <div class="summary-head">
                        <span>Qty.</span>
                        <span>Product Name</span>
                        <span>Price</span>
                    </div>
                </div>

                <div class="shipping-info">
                    <h4>Shipping Info</h4>
                    <p id="modalShipName">-</p>
                    <p id="modalShipEmail">-</p>
                    <p id="modalShipPhone">-</p>
                    <p id="modalShipAddress">-</p>
                </div>

                <div class="status-info">
                    <p>
                        <strong>Order Status</strong>
                        <span class="status order pending" id="modalOrderStatus">Pending</span>
                    </p>
                    <p>
                        <strong>Payment Status</strong>
                        <span class="status payment unpaid" id="modalPaymentStatus">Unpaid</span>
                    </p>
                </div>

                <div class="note-section">
                    <h4>Additional Note</h4>
                    <p id="modalAdditionalNote">-</p>
                </div>

                <button type="button" class="btn-download" id="modalReceiptButton">Download E-receipt</button>
            </div>
        </div>
    </main>

    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/orders.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

