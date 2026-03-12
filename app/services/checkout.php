<?php

require_once __DIR__ . '/customer_auth.php';
require_once __DIR__ . '/cart.php';
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
    return [
        'cod' => 'Cash on Delivery',
        'kbzpay' => 'KBZPay',
        'wave' => 'Wave Pay',
        'aya' => 'AYA Pay',
    ];
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

    if (!isset($paymentMethods[$payload['payment_method']])) {
        throw new InvalidArgumentException('Please choose a payment method.');
    }

    $payload['payment_method_label'] = $paymentMethods[$payload['payment_method']];

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
    $subtotal = (int) ($cart['subtotal'] ?? 0);
    $totalDiscount = (int) ($cart['total_discount'] ?? 0);
    $grandTotal = $subtotal - $totalDiscount;
    $hasFreeDelivery = $draft['delivery_type'] === 'delivery'
        && !empty($draft['supports_free_shipping'])
        && $draft['free_shipping_threshold'] !== null
        && $subtotal >= (float) $draft['free_shipping_threshold'];

    return [
        'draft' => $draft,
        'items' => $cart['items'],
        'subtotal' => $subtotal,
        'total_discount' => $totalDiscount,
        'grand_total' => $grandTotal,
        'has_free_delivery' => $hasFreeDelivery,
        'requires_payment_proof' => $draft['payment_method'] !== 'cod',
    ];
}

function checkout_normalize_order_status(string $paymentMethod): array
{
    if ($paymentMethod === 'cod') {
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

function checkout_finalize_order(?array $proofUpload = null): array
{
    $user = customer_auth_current_user();
    if (!$user) {
        throw new RuntimeException('Please log in to continue.');
    }

    $viewModel = checkout_build_payment_view_model();
    $draft = $viewModel['draft'];
    $items = $viewModel['items'];

    if ($draft['payment_method'] !== 'cod' && $proofUpload === null) {
        throw new InvalidArgumentException('Please upload your payment proof before continuing.');
    }

    $proofPath = '';
    if ($proofUpload !== null) {
        $proofPath = checkout_store_payment_proof($proofUpload);
    }

    $statusMap = checkout_normalize_order_status($draft['payment_method']);
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
            ':subtotal' => $viewModel['subtotal'],
            ':total_discount' => $viewModel['total_discount'],
            ':grand_total' => $viewModel['grand_total'],
            ':delivery_method_id' => $draft['delivery_type'] === 'delivery' ? $draft['delivery_method_id'] : null,
            ':payment_status' => $statusMap['payment_status'],
        ]);

        $orderId = (int) $pdo->lastInsertId();

        foreach ($items as $item) {
            $lineFinal = (int) $item['line_subtotal'] - (int) $item['line_discount'];
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
                ':final_price' => (float) $lineFinal,
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
