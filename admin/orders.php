<?php
$orders = [
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'COD',
        'payment_status' => 'Unpaid',
        'order_status' => 'Pending',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'KBZPay',
        'payment_status' => 'Paid',
        'order_status' => 'Cancelled',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'COD',
        'payment_status' => 'Unpaid',
        'order_status' => 'Confirmed',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'KBZ Bank',
        'payment_status' => 'Paid',
        'order_status' => 'Pending',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'COD',
        'payment_status' => 'Pending',
        'order_status' => 'Cancelled',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'COD',
        'payment_status' => 'Unpaid',
        'order_status' => 'Pending',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'COD',
        'payment_status' => 'Pending',
        'order_status' => 'Cancelled',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'COD',
        'payment_status' => 'Unpaid',
        'order_status' => 'Pending',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'AYAPay',
        'payment_status' => 'Paid',
        'order_status' => 'Delivered',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'COD',
        'payment_status' => 'Pending',
        'order_status' => 'Pending',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'COD',
        'payment_status' => 'Unpaid',
        'order_status' => 'Cancelled',
        'time' => '12.02.2025 12:00:00',
    ],
    [
        'order_no' => '#202601260001',
        'customer' => 'ZCU202601260001',
        'name' => 'John Doe',
        'total' => '3,000,000',
        'payment' => 'UAB Bank',
        'payment_status' => 'Paid',
        'order_status' => 'Shipped',
        'time' => '12.02.2025 12:00:00',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/orders.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content orders-page">
        <header class="orders-header">
            <h1>Orders</h1>
            <div class="orders-search">
                <div class="search-field">
                    <input type="text" placeholder="Order ID/Customer ID">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <button type="button" class="btn-search">Search</button>
                <div class="filter-wrapper">
                    <button type="button" class="btn-filter" aria-label="Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <div class="filter-dropdown" id="ordersFilterDropdown" aria-hidden="true">
                        <div class="filter-row">
                            <label for="filterPaymentStatus">Payment Status</label>
                            <select id="filterPaymentStatus">
                                <option value="">All</option>
                                <option value="Paid">Paid</option>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label for="filterOrderStatus">Order Status</label>
                            <select id="filterOrderStatus">
                                <option value="">All</option>
                                <option value="Pending">Pending</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Shipped">Shipped</option>
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
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr
                                class="orders-row"
                                data-order-no="<?php echo htmlspecialchars($order['order_no']); ?>"
                                data-customer="<?php echo htmlspecialchars($order['customer']); ?>"
                                data-name="<?php echo htmlspecialchars($order['name']); ?>"
                                data-total="<?php echo htmlspecialchars($order['total']); ?>"
                                data-payment="<?php echo htmlspecialchars($order['payment']); ?>"
                                data-payment-status="<?php echo htmlspecialchars($order['payment_status']); ?>"
                                data-order-status="<?php echo htmlspecialchars($order['order_status']); ?>"
                                data-order-time="<?php echo htmlspecialchars($order['time']); ?>"
                            >
                                <td class="order-link order-id-link"><?php echo htmlspecialchars($order['order_no']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer']); ?></td>
                                <td><?php echo htmlspecialchars($order['name']); ?></td>
                                <td><?php echo htmlspecialchars($order['total']); ?></td>
                                <td><?php echo htmlspecialchars($order['payment']); ?></td>
                                <td>
                                    <span class="status payment <?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo htmlspecialchars($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status order <?php echo strtolower($order['order_status']); ?>">
                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                    </span>
                                </td>
                                <td class="order-actions">
                                    <i class="fa-regular fa-pen-to-square edit-status"></i>
                                </td>
                                <td class="order-time"><?php echo htmlspecialchars($order['time']); ?></td>
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
                    Order No. <span id="modalOrderNo">#202601260001</span>
                </h2>
                <p class="modal-subtitle">
                    Order Date - <span id="modalOrderDate">20/12/2025</span>
                </p>
                <h3 class="modal-section-title">Order Summary</h3>

                <div class="summary-table">
                    <div class="summary-head">
                        <span>Qty.</span>
                        <span>Product Name</span>
                        <span>Price</span>
                    </div>
                    <div class="summary-row">
                        <span>1</span>
                        <span>Canon EOS R6 Mark II</span>
                        <span>3,000,000 MMK</span>
                    </div>
                    <div class="summary-row">
                        <span>2</span>
                        <span>Canon EOS R6 Mark II</span>
                        <span>3,000,000 MMK</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Subtotal</span>
                        <span>6,000,000 MMK</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Shipping Fees</span>
                        <span>5,000 MMK</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span>6,005,000 MMK</span>
                    </div>
                </div>

                <div class="shipping-info">
                    <h4>Shipping Info</h4>
                    <p id="modalShipName">Kyaw Ko Ko</p>
                    <p id="modalShipEmail">kyawko@gmail.com</p>
                    <p id="modalShipPhone">09771751530</p>
                    <p id="modalShipAddress">No. 96, Pyay Road, Hlaing Township, Yangon</p>
                </div>

                <div class="status-info">
                    <p>
                        <strong>Order Status</strong>
                        <span class="status order pending" id="modalOrderStatus">Pending</span>
                    </p>
                    <p>
                        <strong>Payment Status</strong>
                        <span class="status payment paid" id="modalPaymentStatus">Paid</span>
                    </p>
                </div>

                <div class="note-section">
                    <h4>Additional Note</h4>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore</p>
                </div>

                <button type="button" class="btn-download">Download E-receipt</button>
            </div>
        </div>
    </main>

    </div>
</div>

<script src="/admin/assets/js/orders.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
