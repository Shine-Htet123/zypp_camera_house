<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../database/catalog.php';
require_once __DIR__ . '/orders.php';

function admin_dashboard_parse_month(?string $value): DateTimeImmutable
{
    $value = trim((string) $value);
    $parsed = $value !== '' ? DateTimeImmutable::createFromFormat('!Y-m', $value) : false;
    if (!$parsed) {
        $parsed = new DateTimeImmutable('first day of this month');
    }

    return $parsed->setTime(0, 0, 0);
}

function admin_dashboard_normalize_range(?string $value): string
{
    $value = strtolower(trim((string) $value));
    return in_array($value, ['1m', '3m', '6m', '1y', 'all'], true) ? $value : '1y';
}

function admin_dashboard_realized_statuses(): array
{
    return ['confirmed', 'shipped', 'delivered'];
}

function admin_dashboard_compact_number(float $amount): string
{
    $abs = abs($amount);
    if ($abs >= 1000000000) {
        return number_format($amount / 1000000000, 2) . ' B';
    }

    if ($abs >= 1000000) {
        return number_format($amount / 1000000, 2) . ' M';
    }

    if ($abs >= 1000) {
        return number_format($amount / 1000, 2) . ' K';
    }

    return number_format($amount, 0);
}

function admin_dashboard_query_sum(string $start, string $end): float
{
    $pdo = get_database_connection();
    $placeholders = implode(', ', array_fill(0, count(admin_dashboard_realized_statuses()), '?'));
    $statement = $pdo->prepare(
        'SELECT COALESCE(SUM(grand_total), 0)
         FROM orders
         WHERE status IN (' . $placeholders . ')
           AND created_at >= ?
           AND created_at < ?'
    );
    $bindings = array_merge(admin_dashboard_realized_statuses(), [$start, $end]);
    $statement->execute($bindings);

    return (float) $statement->fetchColumn();
}

function admin_dashboard_month_labels(): array
{
    return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
}

function admin_dashboard_sales_series(DateTimeImmutable $selectedMonth): array
{
    $pdo = get_database_connection();
    $yearStart = $selectedMonth->modify('first day of january')->setTime(0, 0, 0);
    $yearEnd = $yearStart->modify('+1 year');
    $placeholders = implode(', ', array_fill(0, count(admin_dashboard_realized_statuses()), '?'));

    $statement = $pdo->prepare(
        'SELECT MONTH(created_at) AS sales_month, COALESCE(SUM(grand_total), 0) AS total
         FROM orders
         WHERE status IN (' . $placeholders . ')
           AND created_at >= ?
           AND created_at < ?
         GROUP BY MONTH(created_at)'
    );
    $statement->execute(array_merge(
        admin_dashboard_realized_statuses(),
        [$yearStart->format('Y-m-d H:i:s'), $yearEnd->format('Y-m-d H:i:s')]
    ));

    $totals = array_fill(1, 12, 0.0);
    foreach ($statement->fetchAll() as $row) {
        $month = (int) ($row['sales_month'] ?? 0);
        if ($month >= 1 && $month <= 12) {
            $totals[$month] = (float) ($row['total'] ?? 0);
        }
    }

    return array_values(array_map(static fn (float $total): float => round($total, 2), $totals));
}

function admin_dashboard_resolve_range_bounds(DateTimeImmutable $selectedMonth, string $range): array
{
    $range = admin_dashboard_normalize_range($range);
    $end = $selectedMonth->modify('first day of next month')->setTime(0, 0, 0);

    return match ($range) {
        '1m' => [$selectedMonth->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')],
        '3m' => [$selectedMonth->modify('-2 months')->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')],
        '6m' => [$selectedMonth->modify('-5 months')->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')],
        'all' => admin_dashboard_all_time_bounds($end),
        default => [$selectedMonth->modify('-11 months')->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')],
    };
}

function admin_dashboard_all_time_bounds(DateTimeImmutable $end): array
{
    $pdo = get_database_connection();
    $statement = $pdo->query('SELECT MIN(created_at) FROM orders');
    $firstOrder = $statement->fetchColumn();
    $start = $firstOrder ? new DateTimeImmutable((string) $firstOrder) : $end->modify('-1 year');

    return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
}

function admin_dashboard_trending_brands(string $start, string $end, int $limit = 7): array
{
    $pdo = get_database_connection();
    $placeholders = implode(', ', array_fill(0, count(admin_dashboard_realized_statuses()), '?'));
    $statement = $pdo->prepare(
        'SELECT
            b.name AS brand_name,
            COALESCE(SUM(oi.qty), 0) AS sold_qty,
            COUNT(DISTINCT o.id) AS order_count
         FROM order_items oi
         INNER JOIN orders o ON o.id = oi.order_id
         INNER JOIN products p ON p.product_id = oi.product_id
         INNER JOIN brands b ON b.brand_id = p.brand_id
         WHERE o.status IN (' . $placeholders . ')
           AND o.created_at >= ?
           AND o.created_at < ?
         GROUP BY b.brand_id, b.name
         ORDER BY sold_qty DESC, order_count DESC, b.name ASC
         LIMIT ' . max(1, $limit)
    );
    $statement->execute(array_merge(admin_dashboard_realized_statuses(), [$start, $end]));
    $rows = $statement->fetchAll() ?: [];

    if ($rows === []) {
        return [
            'categories' => ['No Data'],
            'sold_qty' => [0],
            'order_count' => [0],
        ];
    }

    return [
        'categories' => array_map(static fn (array $row): string => (string) $row['brand_name'], $rows),
        'sold_qty' => array_map(static fn (array $row): int => (int) round((float) $row['sold_qty']), $rows),
        'order_count' => array_map(static fn (array $row): int => (int) ($row['order_count'] ?? 0), $rows),
    ];
}

function admin_dashboard_best_sellers(string $start, string $end, int $limit = 6): array
{
    $pdo = get_database_connection();
    $placeholders = implode(', ', array_fill(0, count(admin_dashboard_realized_statuses()), '?'));
    $statement = $pdo->prepare(
        'SELECT
            p.name AS product_name,
            COALESCE(SUM(oi.qty), 0) AS sold_qty
         FROM order_items oi
         INNER JOIN orders o ON o.id = oi.order_id
         INNER JOIN products p ON p.product_id = oi.product_id
         WHERE o.status IN (' . $placeholders . ')
           AND o.created_at >= ?
           AND o.created_at < ?
         GROUP BY p.product_id, p.name
         ORDER BY sold_qty DESC, p.name ASC
         LIMIT ' . max(1, $limit)
    );
    $statement->execute(array_merge(admin_dashboard_realized_statuses(), [$start, $end]));
    $rows = $statement->fetchAll() ?: [];

    if ($rows === []) {
        return [
            'categories' => ['No Data'],
            'sold_qty' => [0],
        ];
    }

    return [
        'categories' => array_map(static fn (array $row): string => (string) $row['product_name'], $rows),
        'sold_qty' => array_map(static fn (array $row): int => (int) round((float) $row['sold_qty']), $rows),
    ];
}

function admin_dashboard_recent_orders(int $limit = 6): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT
            o.public_order_id,
            o.status,
            o.created_at,
            u.public_user_id,
            u.name AS customer_name
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         ORDER BY o.id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $statement->execute();

    $rows = $statement->fetchAll() ?: [];
    foreach ($rows as &$row) {
        $row['order_no'] = '#' . (string) $row['public_order_id'];
        $row['status'] = admin_orders_normalize_order_status((string) $row['status']);
        $row['status_class'] = strtolower((string) $row['status']);
        $row['time'] = admin_orders_format_datetime((string) ($row['created_at'] ?? ''));
        $row['customer'] = (string) $row['public_user_id'];
        $row['name'] = (string) $row['customer_name'];
    }
    unset($row);

    return $rows;
}

function admin_dashboard_build(DateTimeImmutable $selectedMonth, string $range): array
{
    $yearStart = $selectedMonth->modify('first day of january')->setTime(0, 0, 0);
    $yearEnd = $yearStart->modify('+1 year');
    $monthEnd = $selectedMonth->modify('first day of next month')->setTime(0, 0, 0);
    [$rangeStart, $rangeEnd] = admin_dashboard_resolve_range_bounds($selectedMonth, $range);

    $yearlySales = admin_dashboard_query_sum($yearStart->format('Y-m-d H:i:s'), $yearEnd->format('Y-m-d H:i:s'));
    $monthlySales = admin_dashboard_query_sum($selectedMonth->format('Y-m-d H:i:s'), $monthEnd->format('Y-m-d H:i:s'));
    $salesSeries = admin_dashboard_sales_series($selectedMonth);
    $trending = admin_dashboard_trending_brands($rangeStart, $rangeEnd);
    $bestSellers = admin_dashboard_best_sellers($rangeStart, $rangeEnd);

    return [
        'selected_month' => $selectedMonth->format('Y-m'),
        'selected_range' => admin_dashboard_normalize_range($range),
        'kpis' => [
            [
                'icon' => 'fa-chart-column',
                'unit' => 'MMK',
                'value' => admin_dashboard_compact_number($yearlySales),
                'label' => 'Yearly Sales',
            ],
            [
                'icon' => 'fa-chart-line',
                'unit' => 'MMK',
                'value' => admin_dashboard_compact_number($monthlySales),
                'label' => 'Monthly Sales',
            ],
        ],
        'sales_chart' => [
            'categories' => admin_dashboard_month_labels(),
            'series' => $salesSeries,
        ],
        'trending_chart' => $trending,
        'best_sellers_chart' => $bestSellers,
        'recent_orders' => admin_dashboard_recent_orders(),
        'stock_items' => catalog_fetch_dashboard_stock_items(12),
    ];
}
