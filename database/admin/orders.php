<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/services/mailer.php';

function admin_orders_table_exists(string $table): bool
{
    static $cache = [];

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
         LIMIT 1'
    );
    $statement->execute([':table_name' => $table]);
    $cache[$table] = (bool) $statement->fetchColumn();

    return $cache[$table];
}

function admin_orders_column_exists(string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name
         LIMIT 1'
    );
    $statement->execute([
        ':table_name' => $table,
        ':column_name' => $column,
    ]);
    $cache[$key] = (bool) $statement->fetchColumn();

    return $cache[$key];
}

function admin_orders_payment_status_options(): array
{
    return ['Unpaid', 'Paid', 'Pending'];
}

function admin_orders_order_status_options(): array
{
    return ['Pending', 'Cancelled', 'Confirmed', 'Delivered', 'Shipped'];
}

function admin_orders_normalize_payment_status(string $status): string
{
    $status = strtolower(trim($status));
    return match ($status) {
        'paid' => 'Paid',
        'pending' => 'Pending',
        default => 'Unpaid',
    };
}

function admin_orders_normalize_order_status(string $status): string
{
    $status = strtolower(trim($status));
    return match ($status) {
        'cancelled' => 'Cancelled',
        'confirmed' => 'Confirmed',
        'delivered' => 'Delivered',
        'shipped' => 'Shipped',
        default => 'Pending',
    };
}

function admin_orders_format_mmk(float|int|string $amount): string
{
    return number_format((float) $amount) . ' MMK';
}

function admin_orders_format_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d.m.Y H:i:s', $timestamp);
}

function admin_orders_format_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d/m/Y', $timestamp);
}

function admin_orders_notification_delivery_email_select(): string
{
    if (admin_orders_table_exists('order_delivery_details') && admin_orders_column_exists('order_delivery_details', 'email')) {
        return 'COALESCE(NULLIF(TRIM(odd.email), ""), u.email)';
    }

    return 'u.email';
}

function admin_orders_fetch_notification_targets(array $orderIds): array
{
    $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0)));
    if ($orderIds === []) {
        return [];
    }

    $pdo = get_database_connection();
    $placeholders = implode(', ', array_fill(0, count($orderIds), '?'));
    $hasDeliveryDetails = admin_orders_table_exists('order_delivery_details');

    $hasReuploadRequested = admin_orders_column_exists('payments', 'reupload_requested');

    $sql = 'SELECT
                o.id,
                o.public_order_id,
                o.status,
                o.payment_status,
                u.name AS customer_name,
                ' . admin_orders_notification_delivery_email_select() . ' AS customer_email,
                ' . ($hasReuploadRequested ? 'COALESCE(pay.reupload_requested, 0)' : '0') . ' AS reupload_requested
            FROM orders o
            INNER JOIN users u ON u.id = o.user_id
            ' . admin_orders_build_payment_join() . '
            ' . ($hasDeliveryDetails ? 'LEFT JOIN order_delivery_details odd ON odd.order_id = o.id' : '') . '
            WHERE o.id IN (' . $placeholders . ')';

    $statement = $pdo->prepare($sql);
    $statement->execute($orderIds);
    $rows = $statement->fetchAll() ?: [];

    $indexed = [];
    foreach ($rows as $row) {
        $indexed[(int) $row['id']] = $row;
    }

    return $indexed;
}

function admin_orders_send_status_update_email(array $order, bool $paymentChanged, bool $orderChanged, bool $reuploadRequested = false): void
{
    $toEmail = trim((string) ($order['customer_email'] ?? ''));
    if ($toEmail === '' || (!$paymentChanged && !$orderChanged && !$reuploadRequested)) {
        return;
    }

    $orderNo = '#' . (string) ($order['public_order_id'] ?? '');
    $orderStatusLabel = admin_orders_normalize_order_status((string) ($order['status'] ?? 'pending'));
    $paymentStatusLabel = admin_orders_normalize_payment_status((string) ($order['payment_status'] ?? 'pending'));
    $customerName = trim((string) ($order['customer_name'] ?? 'Customer'));
    $summaryLines = [];

    if ($orderChanged) {
        $summaryLines[] = 'Order status: ' . $orderStatusLabel;
    }

    if ($paymentChanged) {
        $summaryLines[] = 'Payment status: ' . $paymentStatusLabel;
    }

    if ($reuploadRequested) {
        $summaryLines[] = 'Payment proof: Re-upload requested';
    }

    $orderDetail = admin_fetch_order_detail((int) ($order['id'] ?? 0));
    $summaryText = implode("\n", $summaryLines);
    $summaryHtml = implode('<br>', array_map('htmlspecialchars', $summaryLines));
    $invoiceHtml = '';
    $invoiceText = '';
    $reuploadUrl = app_url('/check-order.php?order=' . rawurlencode((string) ($order['public_order_id'] ?? '')));
    $reuploadHtml = '';
    $reuploadText = '';

    if ($orderDetail) {
        $itemRowsHtml = '';
        $itemLinesText = [];

        foreach (($orderDetail['items'] ?? []) as $index => $item) {
            $qty = (int) ($item['qty'] ?? 0);
            $name = (string) ($item['name'] ?? '');
            $priceDisplay = (string) ($item['price_display'] ?? admin_orders_format_mmk($item['price'] ?? 0));

            $itemRowsHtml .= '
                <tr>
                    <td style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;text-align:center;">' . ($index + 1) . '</td>
                    <td style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;text-align:center;">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>
                    <td style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;text-align:center;">' . $qty . '</td>
                    <td style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;text-align:center;">' . htmlspecialchars($priceDisplay, ENT_QUOTES, 'UTF-8') . '</td>
                </tr>';

            $itemLinesText[] = ($index + 1) . '. ' . $name . ' | Qty: ' . $qty . ' | Price: ' . $priceDisplay;
        }

        $discountDisplay = admin_orders_format_mmk((float) ($orderDetail['total_discount'] ?? 0));
        $deliveryInfoLines = [
            (string) ($orderDetail['delivery_name'] ?? '-'),
            (string) ($orderDetail['delivery_email'] ?? '-'),
            (string) ($orderDetail['delivery_phone'] ?? '-'),
            (string) ($orderDetail['delivery_address'] ?? '-'),
        ];

        $invoiceHtml = '
            <div style="margin-top:24px;padding:28px 24px;border:1px solid #d8d8d8;border-radius:14px;background:#ffffff;text-align:center;">
                <h3 style="margin:0 0 8px;color:#000;font-size:18px;">Order No. ' . htmlspecialchars($orderNo, ENT_QUOTES, 'UTF-8') . '</h3>
                <p style="margin:0 0 4px;color:#000;">Order Date - ' . htmlspecialchars((string) ($orderDetail['order_date_display'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:0 0 18px;color:#000;">Order Time - ' . htmlspecialchars((string) ($orderDetail['order_time_display'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</p>
                <h4 style="margin:0 0 14px;color:#000;font-size:16px;">Order Summary</h4>
                <table style="width:100%;border-collapse:separate;border-spacing:8px;margin:0 auto 18px;">
                    <thead>
                        <tr>
                            <th style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;background:#fff;text-align:center;">No.</th>
                            <th style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;background:#fff;text-align:center;">Product Name</th>
                            <th style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;background:#fff;text-align:center;">Qty.</th>
                            <th style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;background:#fff;text-align:center;">Price</th>
                        </tr>
                    </thead>
                    <tbody>' . $itemRowsHtml . '</tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;background:#fff;font-weight:700;text-align:center;">Subtotal</td>
                            <td style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;background:#fff;text-align:center;">' . htmlspecialchars((string) ($orderDetail['subtotal_display'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;background:#fff;font-weight:700;text-align:center;">Discount</td>
                            <td style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;background:#fff;text-align:center;color:#e53935;">-' . htmlspecialchars($discountDisplay, ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;background:#fff;font-weight:700;text-align:center;">Grand Total</td>
                            <td style="padding:10px 8px;border:1px solid #3b3b3b;border-radius:4px;background:#fff;text-align:center;">' . htmlspecialchars((string) ($orderDetail['total_display'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                    </tfoot>
                </table>
                <div style="text-align:left;margin-top:18px;">
                    <h4 style="margin:0 0 10px;color:#000;font-size:16px;">Delivery Information</h4>
                    <p style="margin:0 0 6px;">' . htmlspecialchars($deliveryInfoLines[0], ENT_QUOTES, 'UTF-8') . '</p>
                    <p style="margin:0 0 6px;">' . htmlspecialchars($deliveryInfoLines[1], ENT_QUOTES, 'UTF-8') . '</p>
                    <p style="margin:0 0 6px;">' . htmlspecialchars($deliveryInfoLines[2], ENT_QUOTES, 'UTF-8') . '</p>
                    <p style="margin:0 0 18px;">' . htmlspecialchars($deliveryInfoLines[3], ENT_QUOTES, 'UTF-8') . '</p>
                    <p style="margin:0 0 6px;"><strong>Order Status</strong> ' . htmlspecialchars($orderStatusLabel, ENT_QUOTES, 'UTF-8') . '</p>
                    <p style="margin:0 0 6px;"><strong>Payment Status</strong> <span style="color:' . (strtolower($paymentStatusLabel) === 'paid' ? '#56b356' : (strtolower($paymentStatusLabel) === 'unpaid' ? '#e53935' : '#f5a623')) . ';">' . htmlspecialchars($paymentStatusLabel, ENT_QUOTES, 'UTF-8') . '</span></p>
                    <p style="margin:0 0 6px;"><strong>Additional Note</strong></p>
                    <p style="margin:0;">' . htmlspecialchars((string) ($orderDetail['additional_note'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</p>
                </div>
            </div>';

        $invoiceText = "\n\nE-Invoice\n"
            . "Order No.: {$orderNo}\n"
            . 'Order Date: ' . ((string) ($orderDetail['order_date_display'] ?? '-')) . "\n"
            . 'Order Time: ' . ((string) ($orderDetail['order_time_display'] ?? '-')) . "\n"
            . "Customer: {$customerName}\n\n"
            . implode("\n", $itemLinesText) . "\n\n"
            . 'Subtotal: ' . ((string) ($orderDetail['subtotal_display'] ?? '-')) . "\n"
            . 'Discount: -' . $discountDisplay . "\n"
            . 'Grand Total: ' . ((string) ($orderDetail['total_display'] ?? '-')) . "\n"
            . 'Delivery Information: ' . ((string) ($orderDetail['delivery_name'] ?? '-')) . "\n"
            . ((string) ($orderDetail['delivery_email'] ?? '-')) . "\n"
            . ((string) ($orderDetail['delivery_phone'] ?? '-')) . "\n"
            . ((string) ($orderDetail['delivery_address'] ?? '-')) . "\n"
            . 'Order Status: ' . $orderStatusLabel . "\n"
            . 'Payment Status: ' . $paymentStatusLabel . "\n"
            . 'Additional Note: ' . ((string) ($orderDetail['additional_note'] ?? '-'));
    }

    if ($reuploadRequested) {
        $reuploadHtml = '
            <div style="margin-top:18px;padding:16px 18px;border:1px solid rgba(229,57,53,0.25);border-radius:12px;background:#fff7f7;">
                <p style="margin:0 0 10px;color:#2f241f;">Admin requested a new payment screenshot for order <strong>' . htmlspecialchars($orderNo, ENT_QUOTES, 'UTF-8') . '</strong>.</p>
                <a href="' . htmlspecialchars($reuploadUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:10px 16px;border-radius:8px;background:#52b44b;color:#ffffff;text-decoration:none;font-weight:700;">Re-upload Payment Proof</a>
            </div>';
        $reuploadText = "\n\nRe-upload requested:\n" . $reuploadUrl;
    }

    try {
        mailer_send([
            'to_email' => $toEmail,
            'to_name' => $customerName,
            'subject' => 'Order Update for ' . $orderNo,
            'html' => '
                <div style="font-family:Arial,sans-serif;line-height:1.6;color:#2f241f">
                    <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                    <p>Your order <strong>' . htmlspecialchars($orderNo, ENT_QUOTES, 'UTF-8') . '</strong> has been updated.</p>
                    <p>' . $summaryHtml . '</p>
                    ' . $reuploadHtml . '
                    ' . $invoiceHtml . '
                    <p>ZYPP Camera House</p>
                </div>',
            'text' => "Hello {$customerName},\n\nYour order {$orderNo} has been updated.\n\n{$summaryText}{$reuploadText}{$invoiceText}\n\nZYPP Camera House",
        ]);
    } catch (Throwable $exception) {
        error_log('[ZYPP] Failed to send order status email: ' . $exception->getMessage());
    }
}

function admin_orders_build_payment_join(): string
{
    return 'LEFT JOIN (
                SELECT p.*
                FROM payments p
                INNER JOIN (
                    SELECT order_id, MAX(payment_id) AS latest_payment_id
                    FROM payments
                    GROUP BY order_id
                ) latest_payment
                    ON latest_payment.latest_payment_id = p.payment_id
            ) pay ON pay.order_id = o.id';
}

function admin_fetch_orders(array $filters = []): array
{
    $pdo = get_database_connection();
    $hasPublicPaymentId = admin_orders_column_exists('payments', 'public_payment_id');

    $sql = 'SELECT
                o.id,
                o.public_order_id,
                o.status AS order_status,
                o.payment_status AS order_payment_status,
                o.subtotal,
                o.total_discount,
                o.grand_total,
                o.created_at,
                u.public_user_id,
                u.name AS customer_name,
                COALESCE(pay.payment_method, CASE WHEN LOWER(o.payment_status) = "unpaid" THEN "COD" ELSE "-" END) AS payment_method,
                COALESCE(pay.payment_status, o.payment_status) AS payment_status,
                pay.payment_id' .
                ($hasPublicPaymentId ? ', pay.public_payment_id' : ', NULL AS public_payment_id') . '
            FROM orders o
            INNER JOIN users u ON u.id = o.user_id
            ' . admin_orders_build_payment_join() . '
            WHERE 1 = 1';

    $bindings = [];
    $searchQuery = trim((string) ($filters['q'] ?? ''));
    if ($searchQuery !== '') {
        $searchBinding = '%' . $searchQuery . '%';
        $sql .= ' AND (
            o.public_order_id LIKE :query_order_id
            OR u.public_user_id LIKE :query_customer_id
            OR u.name LIKE :query_customer_name
        )';
        $bindings[':query_order_id'] = $searchBinding;
        $bindings[':query_customer_id'] = $searchBinding;
        $bindings[':query_customer_name'] = $searchBinding;
    }

    $sql .= ' ORDER BY o.id DESC';

    $statement = $pdo->prepare($sql);
    $statement->execute($bindings);
    $rows = $statement->fetchAll() ?: [];

    foreach ($rows as $index => &$row) {
        $row['no'] = $index + 1;
        $row['order_no_display'] = '#' . (string) $row['public_order_id'];
        $row['payment_status_label'] = admin_orders_normalize_payment_status((string) $row['payment_status']);
        $row['order_status_label'] = admin_orders_normalize_order_status((string) $row['order_status']);
        $row['payment_method_label'] = trim((string) $row['payment_method']) !== '' ? (string) $row['payment_method'] : '-';
        $row['total_display'] = admin_orders_format_mmk($row['grand_total'] ?? 0);
        $row['time_display'] = admin_orders_format_datetime($row['created_at'] ?? null);
    }
    unset($row);

    return $rows;
}

function admin_orders_shipping_fee(array $order): float
{
    $subtotal = (float) ($order['subtotal'] ?? 0);
    $totalDiscount = (float) ($order['total_discount'] ?? 0);
    $grandTotal = (float) ($order['grand_total'] ?? 0);
    $shipping = $grandTotal - ($subtotal - $totalDiscount);

    return $shipping > 0 ? $shipping : 0.0;
}

function admin_fetch_order_detail(int $orderId): ?array
{
    if ($orderId <= 0) {
        return null;
    }

    $pdo = get_database_connection();
    $hasDeliveryDetails = admin_orders_table_exists('order_delivery_details');
    $hasPublicPaymentId = admin_orders_column_exists('payments', 'public_payment_id');
    $hasPaymentProofFile = admin_orders_column_exists('payments', 'payment_proof_file');

    $sql = 'SELECT
                o.id,
                o.public_order_id,
                o.status AS order_status,
                o.payment_status AS order_payment_status,
                o.subtotal,
                o.total_discount,
                o.grand_total,
                o.created_at,
                u.public_user_id,
                u.name AS customer_name,
                u.email AS customer_email,
                COALESCE(pay.payment_method, CASE WHEN LOWER(o.payment_status) = "unpaid" THEN "COD" ELSE "-" END) AS payment_method,
                COALESCE(pay.payment_status, o.payment_status) AS payment_status,
                pay.payment_id' .
                ($hasPublicPaymentId ? ', pay.public_payment_id' : ', NULL AS public_payment_id') .
                ($hasPaymentProofFile ? ', pay.payment_proof_file' : ', NULL AS payment_proof_file') .
                ($hasDeliveryDetails ? ',
                odd.first_name,
                odd.last_name,
                odd.company,
                odd.phone,
                odd.email,
                odd.address,
                odd.state,
                odd.city,
                odd.township,
                odd.postal_code,
                odd.delivery_type,
                odd.additional_note' : ',
                NULL AS first_name,
                NULL AS last_name,
                NULL AS company,
                NULL AS phone,
                NULL AS email,
                NULL AS address,
                NULL AS state,
                NULL AS city,
                NULL AS township,
                NULL AS postal_code,
                NULL AS delivery_type,
                NULL AS additional_note') . '
            FROM orders o
            INNER JOIN users u ON u.id = o.user_id
            ' . admin_orders_build_payment_join() . '
            ' . ($hasDeliveryDetails ? 'LEFT JOIN order_delivery_details odd ON odd.order_id = o.id' : '') . '
            WHERE o.id = :order_id
            LIMIT 1';

    $statement = $pdo->prepare($sql);
    $statement->execute([':order_id' => $orderId]);
    $order = $statement->fetch();

    if (!$order) {
        return null;
    }

    $itemsStatement = $pdo->prepare(
        'SELECT oi.order_item_id, oi.qty, oi.price, oi.discount_amount, oi.final_price, p.name
         FROM order_items oi
         INNER JOIN products p ON p.product_id = oi.product_id
         WHERE oi.order_id = :order_id
         ORDER BY oi.order_item_id ASC'
    );
    $itemsStatement->execute([':order_id' => $orderId]);
    $items = $itemsStatement->fetchAll() ?: [];

    $order['items'] = array_map(static function (array $item): array {
        return [
            'qty' => (int) $item['qty'],
            'name' => (string) $item['name'],
            'price' => (float) $item['final_price'],
            'price_display' => admin_orders_format_mmk($item['final_price']),
        ];
    }, $items);

    $firstName = trim((string) ($order['first_name'] ?? ''));
    $lastName = trim((string) ($order['last_name'] ?? ''));
    $deliveryName = trim($firstName . ' ' . $lastName);
    if ($deliveryName === '') {
        $deliveryName = (string) $order['customer_name'];
    }

    $addressParts = array_values(array_filter([
        trim((string) ($order['address'] ?? '')),
        trim((string) ($order['township'] ?? '')),
        trim((string) ($order['city'] ?? '')),
    ], static fn (string $part): bool => $part !== ''));

    $order['order_no_display'] = '#' . (string) $order['public_order_id'];
    $order['order_date_display'] = admin_orders_format_date($order['created_at'] ?? null);
    $order['order_time_display'] = admin_orders_format_datetime($order['created_at'] ?? null);
    $order['order_status_label'] = admin_orders_normalize_order_status((string) $order['order_status']);
    $order['payment_status_label'] = admin_orders_normalize_payment_status((string) $order['payment_status']);
    $order['subtotal_display'] = admin_orders_format_mmk($order['subtotal'] ?? 0);
    $order['shipping_fee'] = admin_orders_shipping_fee($order);
    $order['shipping_fee_display'] = admin_orders_format_mmk($order['shipping_fee']);
    $order['total_display'] = admin_orders_format_mmk($order['grand_total'] ?? 0);
    $order['delivery_name'] = $deliveryName;
    $order['delivery_email'] = trim((string) ($order['email'] ?? '')) !== '' ? (string) $order['email'] : (string) $order['customer_email'];
    $order['delivery_phone'] = (string) ($order['phone'] ?? '');
    $order['delivery_address'] = $addressParts !== [] ? implode(', ', $addressParts) : '-';
    $order['additional_note'] = trim((string) ($order['additional_note'] ?? '')) !== '' ? (string) $order['additional_note'] : '-';

    return $order;
}

function admin_update_order_status_only(int $orderId, string $orderStatus): void
{
    $orderStatus = strtolower(trim($orderStatus));

    if (!in_array(admin_orders_normalize_order_status($orderStatus), admin_orders_order_status_options(), true)) {
        throw new InvalidArgumentException('Order status is invalid.');
    }

    $pdo = get_database_connection();
    $before = admin_orders_fetch_notification_targets([$orderId]);
    $previous = $before[$orderId] ?? null;
    $pdo->beginTransaction();

    try {
        $statement = $pdo->prepare(
            'UPDATE orders
             SET status = :order_status,
                 updated_at = NOW()
             WHERE id = :order_id'
        );
        $statement->execute([
            ':order_status' => $orderStatus,
            ':order_id' => $orderId,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    $after = admin_orders_fetch_notification_targets([$orderId]);
    $updated = $after[$orderId] ?? null;
    if ($updated) {
        $orderChanged = !$previous || strtolower((string) ($previous['status'] ?? '')) !== strtolower((string) ($updated['status'] ?? ''));
        admin_orders_send_status_update_email($updated, false, $orderChanged);
    }
}

function admin_fetch_payment_proofs(array $filters = []): array
{
    if (!admin_orders_column_exists('payments', 'payment_proof_file')) {
        return [];
    }

    $pdo = get_database_connection();
    $hasPublicPaymentId = admin_orders_column_exists('payments', 'public_payment_id');
    $hasReuploadRequested = admin_orders_column_exists('payments', 'reupload_requested');
    $sql = 'SELECT
                p.payment_id,
                ' . ($hasPublicPaymentId ? 'p.public_payment_id' : 'NULL AS public_payment_id') . ',
                p.payment_method,
                p.payment_status,
                p.payment_proof_file,
                ' . ($hasReuploadRequested ? 'p.reupload_requested' : '0') . ' AS reupload_requested,
                o.id AS order_id,
                o.public_order_id,
                o.status AS order_status,
                o.grand_total,
                o.created_at,
                u.public_user_id
            FROM payments p
            INNER JOIN orders o ON o.id = p.order_id
            INNER JOIN users u ON u.id = o.user_id
            WHERE p.payment_proof_file IS NOT NULL
              AND p.payment_proof_file <> ""';

    $bindings = [];
    $searchQuery = trim((string) ($filters['q'] ?? ''));
    if ($searchQuery !== '') {
        $searchBinding = '%' . $searchQuery . '%';
        $sql .= ' AND (
            u.public_user_id LIKE :query_customer_id
            OR o.public_order_id LIKE :query_order_id
            OR COALESCE(p.public_payment_id, CAST(p.payment_id AS CHAR)) LIKE :query_payment_id
        )';
        $bindings[':query_customer_id'] = $searchBinding;
        $bindings[':query_order_id'] = $searchBinding;
        $bindings[':query_payment_id'] = $searchBinding;
    }

    $sql .= ' ORDER BY p.payment_id DESC';

    $statement = $pdo->prepare($sql);
    $statement->execute($bindings);
    $rows = $statement->fetchAll() ?: [];
    foreach ($rows as $index => &$row) {
        $row['no'] = $index + 1;
        $row['payment_id_display'] = trim((string) ($row['public_payment_id'] ?? '')) !== ''
            ? (string) $row['public_payment_id']
            : 'PAY-' . str_pad((string) $row['payment_id'], 6, '0', STR_PAD_LEFT);
        $row['order_no_display'] = '#' . (string) $row['public_order_id'];
        $row['amount_display'] = admin_orders_format_mmk($row['grand_total'] ?? 0);
        $row['status_label'] = admin_orders_normalize_payment_status((string) $row['payment_status']);
        $row['order_status_label'] = admin_orders_normalize_order_status((string) $row['order_status']);
        $row['order_date_display'] = admin_orders_format_date($row['created_at'] ?? null);
        $row['reupload_requested'] = !empty($row['reupload_requested']);
    }
    unset($row);

    return $rows;
}

function admin_fetch_payment_proof_notification_count(): int
{
    if (!admin_orders_column_exists('payments', 'payment_proof_file')) {
        return 0;
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM payments
         WHERE payment_proof_file IS NOT NULL
           AND payment_proof_file <> ""
           AND LOWER(COALESCE(payment_status, "")) = "pending"'
    );
    $statement->execute();

    return (int) $statement->fetchColumn();
}

function admin_update_payment_proof_statuses(array $paymentIds, string $action): int
{
    $paymentIds = array_values(array_unique(array_filter(array_map('intval', $paymentIds), static fn (int $id): bool => $id > 0)));
    if ($paymentIds === []) {
        throw new InvalidArgumentException('Please select at least one payment proof.');
    }

    $mappedStatus = match ($action) {
        'approve' => 'paid',
        'reject' => 'unpaid',
        'request' => 'pending',
        default => throw new InvalidArgumentException('Unsupported payment proof action.'),
    };

    $pdo = get_database_connection();
    $hasReuploadRequested = admin_orders_column_exists('payments', 'reupload_requested');
    $hasReuploadRequestedAt = admin_orders_column_exists('payments', 'reupload_requested_at');

    if ($action === 'request' && !$hasReuploadRequested) {
        throw new RuntimeException('Payment re-upload columns are required. Run the re-upload migration first.');
    }

    $placeholders = implode(', ', array_fill(0, count($paymentIds), '?'));
    $beforeOrderIds = [];
    $beforeStatuses = [];
    $beforeReuploadFlags = [];

    $beforeOrdersStatement = $pdo->prepare(
        "SELECT DISTINCT order_id, payment_status" .
        ($hasReuploadRequested ? ', reupload_requested' : ', 0 AS reupload_requested') . "
         FROM payments
         WHERE payment_id IN ($placeholders)"
    );
    $beforeOrdersStatement->execute($paymentIds);
    foreach (($beforeOrdersStatement->fetchAll() ?: []) as $row) {
        $orderId = (int) $row['order_id'];
        $beforeOrderIds[] = $orderId;
        $beforeStatuses[$orderId] = strtolower((string) ($row['payment_status'] ?? ''));
        $beforeReuploadFlags[$orderId] = !empty($row['reupload_requested']);
    }

    $pdo->beginTransaction();
    try {
        $setParts = ['payment_status = ?'];
        $params = [$mappedStatus];

        if ($hasReuploadRequested) {
            if ($action === 'request') {
                $setParts[] = 'reupload_requested = 1';
                if ($hasReuploadRequestedAt) {
                    $setParts[] = 'reupload_requested_at = NOW()';
                }
            } else {
                $setParts[] = 'reupload_requested = 0';
                if ($hasReuploadRequestedAt) {
                    $setParts[] = 'reupload_requested_at = NULL';
                }
            }
        }

        $paymentStatement = $pdo->prepare(
            "UPDATE payments
             SET " . implode(', ', $setParts) . "
             WHERE payment_id IN ($placeholders)"
        );
        $paymentStatement->execute(array_merge($params, $paymentIds));

        $orderIdsStatement = $pdo->prepare(
            "SELECT DISTINCT order_id
             FROM payments
             WHERE payment_id IN ($placeholders)"
        );
        $orderIdsStatement->execute($paymentIds);
        $orderIds = array_values(array_unique(array_map('intval', array_column($orderIdsStatement->fetchAll() ?: [], 'order_id'))));

        if ($orderIds !== []) {
            $orderPlaceholders = implode(', ', array_fill(0, count($orderIds), '?'));
            $orderStatement = $pdo->prepare(
                "UPDATE orders
                 SET payment_status = ?,
                     updated_at = NOW()
                 WHERE id IN ($orderPlaceholders)"
            );
            $orderStatement->execute(array_merge([$mappedStatus], $orderIds));
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    if ($beforeOrderIds !== []) {
        $afterOrders = admin_orders_fetch_notification_targets($beforeOrderIds);
        foreach ($afterOrders as $orderId => $order) {
            $paymentChanged = ($beforeStatuses[$orderId] ?? '') !== strtolower((string) ($order['payment_status'] ?? ''));
            $reuploadRequested = $action === 'request'
                && (!($beforeReuploadFlags[$orderId] ?? false))
                && !empty($order['reupload_requested']);
            admin_orders_send_status_update_email($order, $paymentChanged, false, $reuploadRequested);
        }
    }

    return count($paymentIds);
}

