<?php
$kpis = [
    [
        'period_type' => 'year',
        'period_name' => 'sales_year',
        'period_value' => '2026',
        'icon' => 'fa-chart-column',
        'unit' => 'MMK',
        'value' => '1.04 M',
        'label' => 'Yearly Sales',
    ],
    [
        'period_type' => 'month',
        'period_name' => 'sales_month',
        'period_value' => '2026-12',
        'icon' => 'fa-chart-line',
        'unit' => 'MMK',
        'value' => '300,000',
        'label' => 'Monthly Sales',
    ],
];

$salesRanges = ['1M', '3M', '6M', '1Y', 'ALL'];
$salesRangeActive = '1Y';

$stockItems = [
    ['name' => 'Product Name', 'quantity' => 10, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 23, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 3, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 10, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 0, 'status' => 'Out of Stock', 'status_class' => 'out-stock'],
    ['name' => 'Product Name', 'quantity' => 3, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 0, 'status' => 'Out of Stock', 'status_class' => 'out-stock'],
    ['name' => 'Product Name', 'quantity' => 23, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 3, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 10, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 0, 'status' => 'Out of Stock', 'status_class' => 'out-stock'],
    ['name' => 'Product Name', 'quantity' => 3, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 3, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 10, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Product Name', 'quantity' => 0, 'status' => 'Out of Stock', 'status_class' => 'out-stock'],
    ['name' => 'Product Name', 'quantity' => 3, 'status' => 'In Stock', 'status_class' => 'in-stock'],
];

$recentOrders = [
    ['order_no' => '#10011', 'customer' => 'ZC-0021', 'name' => 'John Doe', 'status' => 'Pending', 'status_class' => 'pending', 'time' => '12.02.2025 12:00:00'],
    ['order_no' => '#10011', 'customer' => 'ZC-0021', 'name' => 'John Doe', 'status' => 'Pending', 'status_class' => 'pending', 'time' => '12.02.2025 12:00:00'],
    ['order_no' => '#10011', 'customer' => 'ZC-0021', 'name' => 'John Doe', 'status' => 'Pending', 'status_class' => 'pending', 'time' => '12.02.2025 12:00:00'],
    ['order_no' => '#10011', 'customer' => 'ZC-0021', 'name' => 'John Doe', 'status' => 'Pending', 'status_class' => 'pending', 'time' => '12.02.2025 12:00:00'],
    ['order_no' => '#10011', 'customer' => 'ZC-0021', 'name' => 'John Doe', 'status' => 'Pending', 'status_class' => 'pending', 'time' => '12.02.2025 12:00:00'],
    ['order_no' => '#10011', 'customer' => 'ZC-0021', 'name' => 'John Doe', 'status' => 'Pending', 'status_class' => 'pending', 'time' => '12.02.2025 12:00:00'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/index.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-dashboard">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
        </div>

        <section class="dashboard-grid">
            <div class="kpi-stack">
                <?php
                    $monthInputId = 'sales_month';
                    $monthValue = '2026-12';
                    $monthDate = DateTime::createFromFormat('Y-m', $monthValue);
                    $monthLabel = $monthDate ? strtoupper($monthDate->format('M Y')) : 'DEC 2026';
                ?>
                <div class="kpi-filter">
                    <label class="kpi-filter-pill" for="<?php echo htmlspecialchars($monthInputId); ?>">
                        <span class="kpi-filter-text"><?php echo htmlspecialchars($monthLabel); ?></span>
                        <i class="fa-regular fa-calendar"></i>
                        <input
                            class="kpi-filter-input"
                            type="month"
                            id="<?php echo htmlspecialchars($monthInputId); ?>"
                            name="<?php echo htmlspecialchars($monthInputId); ?>"
                            value="<?php echo htmlspecialchars($monthValue); ?>"
                            aria-label="Sales period"
                        >
                    </label>
                </div>

                <?php foreach ($kpis as $kpi): ?>
                    <article class="admin-card kpi-card">
                        <div class="kpi-title"><?php echo htmlspecialchars($kpi['label']); ?></div>
                        <div class="kpi-icon">
                            <i class="fa-solid <?php echo htmlspecialchars($kpi['icon']); ?>"></i>
                        </div>
                        <div class="kpi-meta"><?php echo htmlspecialchars($kpi['unit']); ?></div>
                        <div class="kpi-value"><?php echo htmlspecialchars($kpi['value']); ?></div>
                    </article>
                <?php endforeach; ?>
            </div>

            <article class="admin-card sales-card">
                <div class="card-header">
                    <h2>Sales</h2>
                    <div class="range-tabs">
                        <?php foreach ($salesRanges as $range): ?>
                            <button type="button" class="range-tab<?php echo $range === $salesRangeActive ? ' active' : ''; ?>">
                                <?php echo htmlspecialchars($range); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="chart-visual">
                    <div id="salesChart" class="chart-canvas" aria-label="Sales chart"></div>
                </div>
            </article>

            <article class="admin-card stock-card">
                <div class="card-header">
                    <h2>Product Stocks</h2>
                </div>
                <div class="stock-table">
                    <div class="stock-row stock-head">
                        <span>Name</span>
                        <span>Quantity</span>
                        <span>Status</span>
                    </div>
                    <div class="stock-body">
                        <?php foreach ($stockItems as $item): ?>
                            <div class="stock-row">
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                <span><?php echo htmlspecialchars($item['quantity']); ?></span>
                                <span class="status <?php echo htmlspecialchars($item['status_class']); ?>">
                                    <?php echo htmlspecialchars($item['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <a class="card-link" href="#">All Products</a>
            </article>

            <article class="admin-card chart-card trend-card">
                <div class="card-header">
                    <h2>Trending Brands</h2>
                </div>
                <div class="chart-visual bars">
                    <div id="trendingChart" class="chart-canvas" aria-label="Trending brands chart"></div>
                </div>
            </article>

            <article class="admin-card chart-card best-card">
                <div class="card-header">
                    <h2>Best Sellers</h2>
                </div>
                <div class="chart-visual horizontal-bars">
                    <div id="bestSellersChart" class="chart-canvas" aria-label="Best sellers chart"></div>
                </div>
            </article>

            <article class="admin-card orders-card">
                <div class="card-header">
                    <h2>Recent Orders</h2>
                </div>
                <div class="orders-table">
                    <div class="orders-row orders-head">
                        <span>Order No.</span>
                        <span>Customer</span>
                        <span>Name</span>
                        <span>Status</span>
                        <span>Order Time</span>
                    </div>
                    <div class="orders-body">
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="orders-row">
                                <span><?php echo htmlspecialchars($order['order_no']); ?></span>
                                <span><?php echo htmlspecialchars($order['customer']); ?></span>
                                <span><?php echo htmlspecialchars($order['name']); ?></span>
                                <span class="status <?php echo htmlspecialchars($order['status_class']); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                                <span class="time"><?php echo htmlspecialchars($order['time']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <a class="card-link" href="#">See All Orders</a>
            </article>
        </section>
    </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="/admin/assets/js/index.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
