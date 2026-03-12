<?php

require_once __DIR__ . '/../../config/database.php';

function customer_orders_normalize_order_status(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'confirmed' => 'Confirmed',
        'shipped' => 'Shipped',
        default => 'Pending',
    };
}

function customer_orders_normalize_payment_status(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'paid' => 'Paid',
        'unpaid' => 'Unpaid',
        default => 'Pending',
    };
}

function customer_orders_status_class(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
        'confirmed' => 'confirmed',
        'shipped' => 'shipped',
        'paid' => 'paid',
        'unpaid' => 'unpaid',
        default => 'pending',
    };
}

function customer_orders_format_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return (string) $value;
    }

    return date('d F Y', $timestamp);
}

function customer_orders_format_mmk(float|int|string $value): string
{
    return number_format((float) $value) . ' MMK';
}

function customer_fetch_user_orders(int $userId): array
{
    if ($userId <= 0) {
        return [];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT
            o.id,
            o.public_order_id,
            o.status AS order_status,
            o.payment_status AS order_payment_status,
            o.grand_total,
            o.created_at,
            pay.payment_status AS payment_row_status
         FROM orders o
         LEFT JOIN (
            SELECT p.order_id, p.payment_status
            FROM payments p
            INNER JOIN (
                SELECT order_id, MAX(payment_id) AS latest_payment_id
                FROM payments
                GROUP BY order_id
            ) latest_payment
                ON latest_payment.latest_payment_id = p.payment_id
         ) pay ON pay.order_id = o.id
         WHERE o.user_id = :user_id
         ORDER BY o.id DESC'
    );
    $statement->execute([':user_id' => $userId]);

    $rows = $statement->fetchAll() ?: [];

    foreach ($rows as &$row) {
        $publicOrderId = trim((string) ($row['public_order_id'] ?? ''));
        $paymentStatus = (string) (($row['payment_row_status'] ?? '') !== '' ? $row['payment_row_status'] : ($row['order_payment_status'] ?? 'pending'));
        $orderStatus = (string) ($row['order_status'] ?? 'pending');

        $row['order_no_display'] = '#' . $publicOrderId;
        $row['date_display'] = customer_orders_format_date($row['created_at'] ?? null);
        $row['order_status_label'] = customer_orders_normalize_order_status($orderStatus);
        $row['order_status_class'] = customer_orders_status_class($orderStatus);
        $row['payment_status_label'] = customer_orders_normalize_payment_status($paymentStatus);
        $row['payment_status_class'] = customer_orders_status_class($paymentStatus);
        $row['total_display'] = customer_orders_format_mmk($row['grand_total'] ?? 0);
        $row['detail_url'] = '/check-order.php?order=' . rawurlencode($publicOrderId);
    }
    unset($row);

    return $rows;
}
