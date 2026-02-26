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
                <?php foreach ($kpis as $kpi): ?>
                    <?php
                        $displayValue = $kpi['period_value'];
                        if ($kpi['period_type'] === 'month') {
                            $monthDate = DateTime::createFromFormat('Y-m', $kpi['period_value']);
                            $displayValue = $monthDate ? strtoupper($monthDate->format('M')) : 'DEC';
                        }
                        $inputId = $kpi['period_name'];
                    ?>
                    <article class="admin-card kpi-card">
                        <div class="kpi-top">
                            <label class="kpi-pill" data-type="<?php echo htmlspecialchars($kpi['period_type']); ?>" for="<?php echo htmlspecialchars($inputId); ?>">
                                <span class="kpi-text"><?php echo htmlspecialchars($displayValue); ?></span>
                                <i class="fa-regular fa-calendar"></i>
                            </label>
                            <?php if ($kpi['period_type'] === 'month'): ?>
                                <input
                                    class="kpi-input"
                                    type="month"
                                    id="<?php echo htmlspecialchars($inputId); ?>"
                                    name="<?php echo htmlspecialchars($kpi['period_name']); ?>"
                                    value="<?php echo htmlspecialchars($kpi['period_value']); ?>"
                                    aria-label="Monthly sales period"
                                >
                            <?php else: ?>
                                <input
                                    class="kpi-input"
                                    type="year"
                                    id="<?php echo htmlspecialchars($inputId); ?>"
                                    name="<?php echo htmlspecialchars($kpi['period_name']); ?>"
                                    value="<?php echo htmlspecialchars($kpi['period_value']); ?>"
                                    min="2000"
                                    max="2100"
                                    step="1"
                                    aria-label="Yearly sales period"
                                >
                            <?php endif; ?>
                        </div>
                        <div class="kpi-icon">
                            <i class="fa-solid <?php echo htmlspecialchars($kpi['icon']); ?>"></i>
                        </div>
                        <div class="kpi-meta"><?php echo htmlspecialchars($kpi['unit']); ?></div>
                        <div class="kpi-value"><?php echo htmlspecialchars($kpi['value']); ?></div>
                        <div class="kpi-label"><?php echo htmlspecialchars($kpi['label']); ?></div>
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
                    <svg viewBox="0 0 520 220" role="img" aria-label="Sales chart" data-series="sales">
                        <defs>
                            <linearGradient id="salesFill" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#5da9ff" stop-opacity="0.5" />
                                <stop offset="100%" stop-color="#5da9ff" stop-opacity="0.05" />
                            </linearGradient>
                        </defs>
                        <polyline
                            points="20,160 60,120 90,140 130,100 170,130 210,90 250,120 290,80 330,95 360,70 400,90 440,60 500,50"
                            fill="none"
                            stroke="#3f8ff5"
                            stroke-width="4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <polygon
                            points="20,160 60,120 90,140 130,100 170,130 210,90 250,120 290,80 330,95 360,70 400,90 440,60 500,50 500,200 20,200"
                            fill="url(#salesFill)"
                        />
                    </svg>
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
                    <svg viewBox="0 0 520 220" role="img" aria-label="Trending brands chart" data-series="trending">
                        <rect x="40" y="120" width="40" height="70" rx="6" fill="#5cc59a" />
                        <rect x="100" y="80" width="40" height="110" rx="6" fill="#5cc59a" />
                        <rect x="160" y="60" width="40" height="130" rx="6" fill="#5cc59a" />
                        <rect x="220" y="40" width="40" height="150" rx="6" fill="#5cc59a" />
                        <rect x="280" y="70" width="40" height="120" rx="6" fill="#5cc59a" />
                        <rect x="340" y="55" width="40" height="135" rx="6" fill="#5cc59a" />
                        <rect x="400" y="90" width="40" height="100" rx="6" fill="#5cc59a" />
                        <polyline
                            points="60,110 120,70 180,85 240,55 300,70 360,45 420,85"
                            fill="none"
                            stroke="#7b5ad9"
                            stroke-width="3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
            </article>

            <article class="admin-card chart-card best-card">
                <div class="card-header">
                    <h2>Best Sellers</h2>
                </div>
                <div class="chart-visual horizontal-bars">
                    <svg viewBox="0 0 520 220" role="img" aria-label="Best sellers chart" data-series="best-sellers">
                        <rect x="80" y="40" width="300" height="18" rx="6" fill="#4b93ff" />
                        <rect x="80" y="70" width="260" height="18" rx="6" fill="#4b93ff" />
                        <rect x="80" y="100" width="220" height="18" rx="6" fill="#4b93ff" />
                        <rect x="80" y="130" width="180" height="18" rx="6" fill="#4b93ff" />
                        <rect x="80" y="160" width="140" height="18" rx="6" fill="#4b93ff" />
                    </svg>
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

<script src="/admin/assets/js/index.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
