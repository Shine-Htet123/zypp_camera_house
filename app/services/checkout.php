<?php

require_once __DIR__ . '/customer_auth.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/../../database/site_content.php';
require_once __DIR__ . '/../../database/user/checkout.php';
require_once __DIR__ . '/../../database/user/profile.php';

function checkout_flash_set(string $message, string $type = 'error'): void
{
    $_SESSION['checkout_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function checkout_flash_consume(): ?array
{
    $flash = $_SESSION['checkout_flash'] ?? null;
    unset($_SESSION['checkout_flash']);

    return is_array($flash) ? $flash : null;
}

function checkout_get_payment_methods(): array
{
    $methods = [];
    foreach (site_content_get_payment_method_definitions(true) as $method) {
        $key = trim((string) ($method['key'] ?? ''));
        $label = trim((string) ($method['label'] ?? ''));
        if ($key === '' || $label === '') {
            continue;
        }

        $methods[$key] = $label;
    }

    return $methods;
}

function checkout_get_payment_method_definition(string $identifier): ?array
{
    return site_content_find_payment_method($identifier, true);
}

function checkout_payment_method_requires_proof(string $identifier): bool
{
    $method = checkout_get_payment_method_definition($identifier);
    return $method ? !empty($method['requires_payment_proof']) : true;
}

function checkout_split_name(string $fullName): array
{
    $fullName = trim($fullName);
    if ($fullName === '') {
        return ['first_name' => '', 'last_name' => ''];
    }

    $parts = preg_split('/\s+/', $fullName) ?: [$fullName];
    $firstName = array_shift($parts);
    $lastName = trim(implode(' ', $parts));

    return [
        'first_name' => $firstName,
        'last_name' => $lastName,
    ];
}

function checkout_get_prefill(): array
{
    $user = customer_auth_current_user();
    if (!$user) {
        return [];
    }

    $name = checkout_split_name((string) ($user['name'] ?? ''));
    $defaultAddress = checkout_fetch_default_address((int) $user['id']);
    $draft = $_SESSION['checkout_draft'] ?? [];
    $draft = is_array($draft) ? $draft : [];

    $deliveryMethods = checkout_fetch_delivery_methods();
    $defaultDeliveryMethodId = !empty($deliveryMethods) ? (int) $deliveryMethods[0]['delivery_method_id'] : 0;

    return [
        'first_name' => (string) ($draft['first_name'] ?? $name['first_name']),
        'last_name' => (string) ($draft['last_name'] ?? $name['last_name']),
        'company' => (string) ($draft['company'] ?? ''),
        'phone' => (string) ($draft['phone'] ?? ($defaultAddress['phone'] ?? '')),
        'email' => (string) ($draft['email'] ?? ($user['email'] ?? '')),
        'address' => (string) ($draft['address'] ?? ($defaultAddress['street'] ?? '')),
        'state' => (string) ($draft['state'] ?? ''),
        'city' => (string) ($draft['city'] ?? ($defaultAddress['city'] ?? '')),
        'township' => (string) ($draft['township'] ?? ($defaultAddress['township'] ?? '')),
        'postal_code' => (string) ($draft['postal_code'] ?? ($defaultAddress['postal_code'] ?? '')),
        'delivery_type' => (string) ($draft['delivery_type'] ?? 'delivery'),
        'delivery_method_id' => (int) ($draft['delivery_method_id'] ?? $defaultDeliveryMethodId),
        'payment_method' => (string) ($draft['payment_method'] ?? ''),
        'set_default' => !empty($draft['set_default']),
    ];
}

function checkout_validate_delivery_input(array $input): array
{
    $paymentMethods = checkout_get_payment_methods();
    $deliveryType = trim((string) ($input['delivery_type'] ?? 'delivery'));
    if (!in_array($deliveryType, ['delivery', 'pickup'], true)) {
        $deliveryType = 'delivery';
    }

    $payload = [
        'first_name' => trim((string) ($input['first_name'] ?? '')),
        'last_name' => trim((string) ($input['last_name'] ?? '')),
        'company' => trim((string) ($input['company'] ?? '')),
        'phone' => trim((string) ($input['phone'] ?? '')),
        'email' => mb_strtolower(trim((string) ($input['email'] ?? ''))),
        'address' => trim((string) ($input['address'] ?? '')),
        'state' => trim((string) ($input['state'] ?? '')),
        'city' => trim((string) ($input['city'] ?? '')),
        'township' => trim((string) ($input['township'] ?? '')),
        'postal_code' => trim((string) ($input['postal_code'] ?? '')),
        'delivery_type' => $deliveryType,
        'delivery_method_id' => (int) ($input['delivery_method'] ?? 0),
        'payment_method' => trim((string) ($input['payment_method'] ?? '')),
        'set_default' => !empty($input['set_default']),
        'additional_note' => trim((string) ($_SESSION['cart_additional_note'] ?? '')),
    ];

    if ($payload['first_name'] === '' || $payload['last_name'] === '') {
        throw new InvalidArgumentException('First name and last name are required.');
    }

    if ($payload['phone'] === '') {
        throw new InvalidArgumentException('Phone number is required.');
    }

    if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    $paymentMethod = checkout_get_payment_method_definition($payload['payment_method']);
    if (!$paymentMethod) {
        throw new InvalidArgumentException('Please choose a payment method.');
    }

    $payload['payment_method'] = (string) ($paymentMethod['key'] ?? $payload['payment_method']);
    $payload['payment_method_label'] = (string) ($paymentMethod['label'] ?? $paymentMethods[$payload['payment_method']] ?? '');
    $payload['requires_payment_proof'] = !empty($paymentMethod['requires_payment_proof']);
    $payload['payment_method_details'] = $paymentMethod;

    if ($payload['delivery_type'] === 'delivery') {
        if ($payload['address'] === '' || $payload['city'] === '' || $payload['township'] === '') {
            throw new InvalidArgumentException('Please complete the delivery address fields.');
        }

        $deliveryMethod = checkout_find_delivery_method($payload['delivery_method_id']);
        if (!$deliveryMethod) {
            throw new InvalidArgumentException('Please choose a delivery method.');
        }

        $payload['delivery_method_name'] = (string) $deliveryMethod['name'];
        $payload['supports_free_shipping'] = (bool) $deliveryMethod['supports_free_shipping'];
        $payload['free_shipping_threshold'] = $deliveryMethod['free_shipping_threshold'] !== null
            ? (float) $deliveryMethod['free_shipping_threshold']
            : null;
    } else {
        $payload['delivery_method_id'] = 0;
        $payload['delivery_method_name'] = '';
        $payload['supports_free_shipping'] = false;
        $payload['free_shipping_threshold'] = null;
    }

    return $payload;
}

function checkout_save_draft(array $input): array
{
    $user = customer_auth_current_user();
    if (!$user) {
        throw new RuntimeException('Please log in to continue.');
    }

    $cart = customer_cart_fetch_view_model();
    if ($cart['requires_login'] || $cart['items'] === []) {
        throw new RuntimeException('Your cart is empty.');
    }

    $payload = checkout_validate_delivery_input($input);
    $payload['user_id'] = (int) $user['id'];
    $payload['saved_at'] = time();

    $_SESSION['checkout_draft'] = $payload;

    return $payload;
}

function checkout_get_draft(): ?array
{
    $draft = $_SESSION['checkout_draft'] ?? null;
    return is_array($draft) ? $draft : null;
}

function checkout_clear_draft(): void
{
    unset(
        $_SESSION['checkout_draft'],
        $_SESSION['checkout_last_order_id'],
        $_SESSION['checkout_last_order_public_id']
    );
}

function checkout_require_draft(): array
{
    $user = customer_auth_current_user();
    if (!$user) {
        throw new RuntimeException('Please log in to continue.');
    }

    $draft = checkout_get_draft();
    if (!$draft || (int) ($draft['user_id'] ?? 0) !== (int) $user['id']) {
        throw new RuntimeException('Please complete delivery information first.');
    }

    $cart = customer_cart_fetch_view_model();
    if ($cart['requires_login'] || $cart['items'] === []) {
        throw new RuntimeException('Your cart is empty.');
    }

    return $draft;
}

function checkout_format_mmk(float|int $value): string
{
    return number_format((float) $value);
}

function checkout_build_payment_view_model(): array
{
    $draft = checkout_require_draft();
    $cart = customer_cart_fetch_view_model();
    $paymentMethod = checkout_get_payment_method_definition((string) ($draft['payment_method'] ?? ''))
        ?? (is_array($draft['payment_method_details'] ?? null) ? $draft['payment_method_details'] : null);
    $itemsTotal = (int) ($cart['items_total'] ?? 0);
    $totalDiscount = (int) ($cart['total_discount'] ?? 0);
    $subtotal = $itemsTotal;
    $grandTotal = max($subtotal - $totalDiscount, 0);
    $hasFreeDelivery = $draft['delivery_type'] === 'delivery'
        && !empty($draft['supports_free_shipping'])
        && $draft['free_shipping_threshold'] !== null
        && $itemsTotal >= (float) $draft['free_shipping_threshold'];
    $requiresPaymentProof = $paymentMethod ? !empty($paymentMethod['requires_payment_proof']) : true;

    return [
        'draft' => $draft,
        'payment_method' => $paymentMethod,
        'items' => $cart['items'],
        'items_total' => $itemsTotal,
        'subtotal' => $subtotal,
        'total_discount' => $totalDiscount,
        'grand_total' => $grandTotal,
        'has_free_delivery' => $hasFreeDelivery,
        'requires_payment_proof' => $requiresPaymentProof,
    ];
}

function checkout_normalize_order_status(array $paymentMethod): array
{
    if (empty($paymentMethod['requires_payment_proof'])) {
        return [
            'order_status' => 'pending',
            'payment_status' => 'unpaid',
        ];
    }

    return [
        'order_status' => 'pending',
        'payment_status' => 'pending',
    ];
}

function checkout_email_item_rows(array $items): string
{
    if ($items === []) {
        return '<tr><td colspan="3" style="padding:12px 10px;border-bottom:1px solid #e6dfd8;">No items found.</td></tr>';
    }

    $rows = '';
    foreach ($items as $item) {
        $name = htmlspecialchars((string) ($item['name'] ?? 'Item'), ENT_QUOTES, 'UTF-8');
        $qty = (int) ($item['qty'] ?? $item['quantity'] ?? 0);
        $price = htmlspecialchars(checkout_format_mmk((float) ($item['line_subtotal'] ?? $item['final_price'] ?? 0)) . ' MMK', ENT_QUOTES, 'UTF-8');
        $rows .= '
            <tr>
                <td style="padding:12px 10px;border-bottom:1px solid #e6dfd8;">' . $name . '</td>
                <td style="padding:12px 10px;border-bottom:1px solid #e6dfd8;text-align:center;">' . $qty . '</td>
                <td style="padding:12px 10px;border-bottom:1px solid #e6dfd8;text-align:right;">' . $price . '</td>
            </tr>';
    }

    return $rows;
}

function checkout_confirmation_email_recipient(array $user, array $confirmation): array
{
    $email = trim((string) ($confirmation['email'] ?? $user['email'] ?? ''));
    $name = trim(
        (string) ($confirmation['first_name'] ?? '') . ' ' . (string) ($confirmation['last_name'] ?? '')
    );
    if ($name === '') {
        $name = trim((string) ($user['name'] ?? 'Customer'));
    }

    return [
        'email' => $email,
        'name' => $name !== '' ? $name : 'Customer',
    ];
}

function checkout_receipt_items(array $confirmation): array
{
    $rows = [];
    foreach ((array) ($confirmation['items'] ?? []) as $item) {
        $rows[] = [
            'name' => trim((string) ($item['name'] ?? 'Item')),
            'qty' => (int) ($item['qty'] ?? $item['quantity'] ?? 0),
            'price' => checkout_format_mmk((float) ($item['line_subtotal'] ?? $item['final_price'] ?? 0)) . ' MMK',
        ];
    }

    return $rows;
}

function checkout_pdf_escape(string $value): string
{
    $value = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $value);
    return preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
}

function checkout_build_confirmation_receipt_pdf(array $confirmation): array
{
    $publicOrderId = trim((string) ($confirmation['public_order_id'] ?? ''));
    $items = checkout_receipt_items($confirmation);
    $pageWidth = 595;
    $pageHeight = 842;
    $content = "0.141 0.102 0.078 rg\n";

    $writeText = static function (string &$stream, string $font, float $size, float $x, float $topY, string $text) use ($pageHeight): void {
        $escaped = checkout_pdf_escape($text);
        $pdfY = $pageHeight - $topY;
        $stream .= sprintf("BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n", $font, $size, $x, $pdfY, $escaped);
    };

    $drawLine = static function (string &$stream, float $x1, float $topY1, float $x2, float $topY2) use ($pageHeight): void {
        $y1 = $pageHeight - $topY1;
        $y2 = $pageHeight - $topY2;
        $stream .= sprintf("0.90 0.87 0.84 RG 1 w %.2F %.2F m %.2F %.2F l S\n", $x1, $y1, $x2, $y2);
    };

    $wrapText = static function (string $value, int $limit = 70): array {
        $words = preg_split('/\s+/', trim($value)) ?: [];
        if ($words === []) {
            return ['-'];
        }

        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (strlen($candidate) > $limit && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    };

    $writeText($content, 'F2', 26, 50, 56, 'ZYPP Camera House');
    $writeText($content, 'F1', 15, 50, 84, 'Official E-Receipt');
    $writeText($content, 'F2', 15, 360, 56, 'Order No. #' . $publicOrderId);
    $writeText($content, 'F1', 13, 360, 78, 'Order Date ' . date('d.m.Y', strtotime((string) ($confirmation['created_at'] ?? 'now'))));
    $writeText($content, 'F1', 13, 360, 98, 'Order Time ' . date('H:i:s', strtotime((string) ($confirmation['created_at'] ?? 'now'))));
    $drawLine($content, 50, 118, 545, 118);

    $writeText($content, 'F2', 18, 50, 148, 'Order Summary');
    $writeText($content, 'F2', 12, 50, 176, 'No.');
    $writeText($content, 'F2', 12, 90, 176, 'Product Name');
    $writeText($content, 'F2', 12, 410, 176, 'Qty.');
    $writeText($content, 'F2', 12, 470, 176, 'Price');
    $drawLine($content, 50, 184, 545, 184);

    $y = 206;
    $maxItems = 10;
    foreach (array_slice($items, 0, $maxItems) as $index => $item) {
        $writeText($content, 'F1', 11, 50, $y, (string) ($index + 1));
        $writeText($content, 'F1', 11, 90, $y, mb_strimwidth($item['name'], 0, 54, '...'));
        $writeText($content, 'F1', 11, 420, $y, (string) $item['qty']);
        $writeText($content, 'F1', 11, 465, $y, $item['price']);
        $drawLine($content, 50, $y + 8, 545, $y + 8);
        $y += 24;
    }

    if (count($items) > $maxItems) {
        $writeText($content, 'F1', 10, 90, $y, '(+' . (count($items) - $maxItems) . ' more items)');
        $y += 24;
    }

    $totalsTop = $y + 24;
    $writeText($content, 'F1', 12, 370, $totalsTop, 'Subtotal');
    $writeText($content, 'F2', 12, 470, $totalsTop, checkout_format_mmk((float) ($confirmation['subtotal'] ?? 0)) . ' MMK');
    $writeText($content, 'F1', 12, 370, $totalsTop + 22, 'Total Discount');
    $content .= "0.702 0.071 0.071 rg\n";
    $writeText($content, 'F2', 12, 470, $totalsTop + 22, '-' . checkout_format_mmk((float) ($confirmation['total_discount'] ?? 0)) . ' MMK');
    $content .= "0.141 0.102 0.078 rg\n";
    $writeText($content, 'F1', 12, 370, $totalsTop + 44, 'Grand Total');
    $writeText($content, 'F2', 12, 470, $totalsTop + 44, checkout_format_mmk((float) ($confirmation['grand_total'] ?? 0)) . ' MMK');

    $deliveryTop = $totalsTop + 100;
    $writeText($content, 'F2', 16, 50, $deliveryTop, 'Delivery Information');
    $deliveryLines = array_merge(
        [trim(((string) ($confirmation['first_name'] ?? '')) . ' ' . ((string) ($confirmation['last_name'] ?? '')))],
        $wrapText((string) ($confirmation['email'] ?? ''), 70),
        $wrapText((string) ($confirmation['phone'] ?? ''), 70),
        $wrapText(
            implode(', ', array_filter([
                trim((string) ($confirmation['address'] ?? '')),
                trim((string) ($confirmation['township'] ?? '')),
                trim((string) ($confirmation['city'] ?? '')),
            ])) ?: 'Pick up at store',
            70
        )
    );
    $lineTop = $deliveryTop + 24;
    foreach ($deliveryLines as $line) {
        $writeText($content, 'F1', 11, 50, $lineTop, $line);
        $lineTop += 18;
    }

    $detailsX = 320;
    $writeText($content, 'F2', 16, $detailsX, $deliveryTop, 'Order Details');
    $detailLines = [
        'Order Status: ' . ucfirst((string) ($confirmation['status'] ?? 'pending')),
        'Payment Status: ' . ucfirst((string) (($confirmation['payment_row_status'] ?? $confirmation['payment_status']) ?? 'pending')),
        'Delivery Method: ' . ((string) (($confirmation['delivery_method_name'] ?? '') !== '' ? $confirmation['delivery_method_name'] : '-')),
        'Additional Note: ' . ((string) (($confirmation['additional_note'] ?? '') !== '' ? $confirmation['additional_note'] : '-')),
    ];
    $lineTop = $deliveryTop + 24;
    foreach ($detailLines as $line) {
        foreach ($wrapText($line, 42) as $wrappedLine) {
            $writeText($content, 'F1', 11, $detailsX, $lineTop, $wrappedLine);
            $lineTop += 18;
        }
    }

    $objects = [];
    $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 6 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[4] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objects[5] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";
    $objects[6] = "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ([1, 2, 3, 4, 5, 6] as $index) {
        $offsets[$index] = strlen($pdf);
        $pdf .= $objects[$index];
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 7\n0000000000 65535 f \n";
    foreach ([1, 2, 3, 4, 5, 6] as $index) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
    }
    $pdf .= "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

    return [
        'data' => $pdf,
        'mime' => 'application/pdf',
        'name' => 'zypp-receipt-' . $publicOrderId . '.pdf',
    ];
}

function checkout_send_confirmation_email(array $user, array $confirmation): void
{
    if (!mailer_is_configured()) {
        return;
    }

    $recipient = checkout_confirmation_email_recipient($user, $confirmation);
    if ($recipient['email'] === '') {
        return;
    }

    $publicOrderId = trim((string) ($confirmation['public_order_id'] ?? ''));
    if ($publicOrderId === '') {
        return;
    }

    $orderUrl = app_url('/check-order.php?order=' . rawurlencode($publicOrderId));
    $grandTotal = checkout_format_mmk((float) ($confirmation['grand_total'] ?? 0)) . ' MMK';
    $subtotal = checkout_format_mmk((float) ($confirmation['subtotal'] ?? 0)) . ' MMK';
    $discount = checkout_format_mmk((float) ($confirmation['total_discount'] ?? 0)) . ' MMK';
    $paymentStatus = ucfirst((string) (($confirmation['payment_row_status'] ?? $confirmation['payment_status']) ?? 'pending'));
    $orderStatus = ucfirst((string) ($confirmation['status'] ?? 'pending'));
    $customerName = htmlspecialchars($recipient['name'], ENT_QUOTES, 'UTF-8');
    $receiptPdf = checkout_build_confirmation_receipt_pdf($confirmation);
    $deliverySummary = htmlspecialchars(
        implode(', ', array_filter([
            trim((string) ($confirmation['address'] ?? '')),
            trim((string) ($confirmation['township'] ?? '')),
            trim((string) ($confirmation['city'] ?? '')),
        ])) ?: 'Pick up at store',
        ENT_QUOTES,
        'UTF-8'
    );
    $itemRows = checkout_email_item_rows((array) ($confirmation['items'] ?? []));

    mailer_send([
        'to_email' => $recipient['email'],
        'to_name' => $recipient['name'],
        'subject' => 'Your ZYPP order #' . $publicOrderId,
        'html' => '
            <h2 style="margin:0 0 16px;color:#241a14;">Order Received</h2>
            <p>Hello ' . $customerName . ',</p>
            <p>Thank you for your order. Your order has been recorded successfully.</p>
            <p><strong>Order No.</strong> #' . htmlspecialchars($publicOrderId, ENT_QUOTES, 'UTF-8') . '<br><strong>Order Status</strong> ' . htmlspecialchars($orderStatus, ENT_QUOTES, 'UTF-8') . '<br><strong>Payment Status</strong> ' . htmlspecialchars($paymentStatus, ENT_QUOTES, 'UTF-8') . '</p>
            <table style="width:100%;border-collapse:collapse;margin:20px 0 16px;background:#fff7f1;border-radius:14px;overflow:hidden;">
                <thead>
                    <tr style="background:#f4ece6;">
                        <th style="padding:12px 10px;text-align:left;">Item</th>
                        <th style="padding:12px 10px;text-align:center;">Qty.</th>
                        <th style="padding:12px 10px;text-align:right;">Price</th>
                    </tr>
                </thead>
                <tbody>' . $itemRows . '</tbody>
            </table>
            <div style="margin:0 0 18px;padding:16px 18px;background:#faf6f2;border-radius:14px;">
                <p style="margin:0 0 8px;"><strong>Subtotal:</strong> ' . htmlspecialchars($subtotal, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:0 0 8px;"><strong>Total Discount:</strong> -' . htmlspecialchars($discount, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:0;"><strong>Grand Total:</strong> ' . htmlspecialchars($grandTotal, ENT_QUOTES, 'UTF-8') . '</p>
            </div>
            <p><strong>Delivery Information</strong><br>' . $deliverySummary . '</p>
            <p style="margin:18px 0 0;">Your e-receipt is attached to this email as a PDF file.</p>
            <p style="margin:24px 0 0;">
                <a href="' . htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#b58463;color:#fff;text-decoration:none;padding:12px 18px;border-radius:10px;display:inline-block;">View Order Details</a>
            </p>
            <p style="margin:18px 0 0;">Order details link:</p>
            <p style="margin:0;"><a href="' . htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') . '</a></p>',
        'text' => "Hello {$recipient['name']},\n\nYour ZYPP order #{$publicOrderId} has been recorded.\nOrder status: {$orderStatus}\nPayment status: {$paymentStatus}\nSubtotal: {$subtotal}\nDiscount: -{$discount}\nGrand total: {$grandTotal}\n\nYour e-receipt is attached to this email as a PDF file.\n\nOrder details:\n{$orderUrl}",
        'attachments' => [$receiptPdf],
    ]);
}

function checkout_finalize_order(?array $proofUpload = null): array
{
    $user = customer_auth_current_user();
    if (!$user) {
        throw new RuntimeException('Please log in to continue.');
    }

    $viewModel = checkout_build_payment_view_model();
    $draft = $viewModel['draft'];
    $paymentMethod = $viewModel['payment_method'];
    $items = $viewModel['items'];

    if ($paymentMethod && !empty($paymentMethod['requires_payment_proof']) && $proofUpload === null) {
        throw new InvalidArgumentException('Please upload your payment proof before continuing.');
    }

    $proofPath = '';
    if ($proofUpload !== null) {
        $proofPath = checkout_store_payment_proof($proofUpload);
    }

    $statusMap = checkout_normalize_order_status($paymentMethod ?? ['requires_payment_proof' => true]);
    $pdo = get_database_connection();
    $pdo->beginTransaction();

    try {
        foreach ($items as $item) {
            $currentProduct = cart_fetch_product_for_cart((int) $item['product_id']);
            if (!$currentProduct) {
                throw new RuntimeException('A product in your cart is no longer available.');
            }
            if ((int) $currentProduct['stock_quantity'] < (int) $item['quantity']) {
                throw new RuntimeException('One or more cart items no longer have enough stock.');
            }
        }

        $publicOrderId = checkout_generate_public_order_id();
        $orderStatement = $pdo->prepare(
            'INSERT INTO orders (
                user_id, public_order_id, status, subtotal, total_discount, grand_total,
                delivery_method_id, payment_status, updated_at
             ) VALUES (
                :user_id, :public_order_id, :status, :subtotal, :total_discount, :grand_total,
                :delivery_method_id, :payment_status, NOW()
             )'
        );
        $orderStatement->execute([
            ':user_id' => (int) $user['id'],
            ':public_order_id' => $publicOrderId,
            ':status' => $statusMap['order_status'],
            ':subtotal' => $viewModel['items_total'],
            ':total_discount' => $viewModel['total_discount'],
            ':grand_total' => $viewModel['grand_total'],
            ':delivery_method_id' => $draft['delivery_type'] === 'delivery' ? $draft['delivery_method_id'] : null,
            ':payment_status' => $statusMap['payment_status'],
        ]);

        $orderId = (int) $pdo->lastInsertId();

        foreach ($items as $item) {
            $itemStatement = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, price, qty, discount_amount, final_price)
                 VALUES (:order_id, :product_id, :price, :qty, :discount_amount, :final_price)'
            );
            $itemStatement->execute([
                ':order_id' => $orderId,
                ':product_id' => (int) $item['product_id'],
                ':price' => (float) $item['unit_price'],
                ':qty' => (int) $item['quantity'],
                ':discount_amount' => (float) $item['line_discount'],
                ':final_price' => (float) $item['line_total'],
            ]);

            $stockStatement = $pdo->prepare(
                'UPDATE products
                 SET stock_quantity = GREATEST(stock_quantity - :qty, 0)
                 WHERE product_id = :product_id'
            );
            $stockStatement->execute([
                ':qty' => (int) $item['quantity'],
                ':product_id' => (int) $item['product_id'],
            ]);
        }

        checkout_insert_order_delivery_detail($pdo, $orderId, $draft);

        $paymentRow = checkout_insert_payment($pdo, $orderId, [
            'payment_method' => $draft['payment_method_label'],
            'payment_status' => $statusMap['payment_status'],
            'payment_proof_file' => $proofPath,
            'submitted_at' => $proofPath !== '' ? date('Y-m-d H:i:s') : null,
        ]);

        checkout_insert_or_update_address($pdo, (int) $user['id'], $draft);

        $cart = cart_fetch_cart_record((int) $user['id']);
        if ($cart) {
            $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id')
                ->execute([':cart_id' => (int) $cart['cart_id']]);
        }

        unset($_SESSION['cart_additional_note']);
        $_SESSION['checkout_last_order_id'] = $orderId;
        $_SESSION['checkout_last_order_public_id'] = $publicOrderId;
        $_SESSION['checkout_last_payment_id'] = $paymentRow['public_payment_id'] ?? null;
        unset($_SESSION['checkout_draft']);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    $confirmation = checkout_fetch_order_confirmation((int) $user['id'], $publicOrderId);
    if (!$confirmation) {
        throw new RuntimeException('Failed to prepare your order confirmation.');
    }

    try {
        checkout_send_confirmation_email($user, $confirmation);
    } catch (Throwable $exception) {
        error_log('Order confirmation email failed for order #' . $publicOrderId . ': ' . $exception->getMessage());
    }

    return $confirmation;
}

function checkout_fetch_latest_confirmation(): ?array
{
    $user = customer_auth_current_user();
    if (!$user) {
        return null;
    }

    $publicOrderId = trim((string) ($_GET['order'] ?? ($_SESSION['checkout_last_order_public_id'] ?? '')));

    return checkout_fetch_order_confirmation((int) $user['id'], $publicOrderId !== '' ? $publicOrderId : null);
}

function checkout_reupload_existing_payment_proof(string $publicOrderId, array $proofUpload): array
{
    $user = customer_auth_current_user();
    if (!$user) {
        throw new RuntimeException('Please log in to continue.');
    }

    return checkout_reupload_payment_proof((int) $user['id'], $publicOrderId, $proofUpload);
}
