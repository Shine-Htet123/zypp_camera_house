<?php

require_once __DIR__ . '/../../config/customer_bootstrap.php';
require_once __DIR__ . '/customer_auth.php';
require_once __DIR__ . '/../../database/user/cart.php';

function customer_cart_set_flash(string $message, string $type = 'info'): void
{
    $_SESSION['customer_cart_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function customer_cart_consume_flash(): ?array
{
    $flash = $_SESSION['customer_cart_flash'] ?? null;
    unset($_SESSION['customer_cart_flash']);

    return is_array($flash) ? $flash : null;
}

function customer_cart_require_user_id(): int
{
    $user = customer_auth_current_user();
    if (!$user) {
        throw new RuntimeException('Please log in to continue.');
    }

    return (int) $user['id'];
}

function customer_cart_count(): int
{
    $user = customer_auth_current_user();
    if (!$user) {
        return 0;
    }

    return cart_count_items_for_user((int) $user['id']);
}

function customer_cart_format_mmk(float|int $value): string
{
    return number_format((float) $value);
}

function customer_cart_line_subtotal(float|int $price, int $quantity): int
{
    return (int) round((float) $price * $quantity);
}

function customer_cart_line_discount(int $subtotal, int $discountValue, string $discountType, int $quantity): int
{
    if ($discountType === 'fixed') {
        return min($subtotal, $discountValue * $quantity);
    }

    return (int) round($subtotal * ($discountValue / 100));
}

function customer_cart_fetch_view_model(): array
{
    $user = customer_auth_current_user();
    if (!$user) {
        return [
            'items' => [],
            'subtotal' => 0,
            'total_discount' => 0,
            'additional_note' => (string) ($_SESSION['cart_additional_note'] ?? ''),
            'requires_login' => true,
        ];
    }

    $items = cart_fetch_items_for_user((int) $user['id']);
    $subtotal = 0;
    $totalDiscount = 0;

    foreach ($items as &$item) {
        $item['line_subtotal'] = customer_cart_line_subtotal((float) $item['unit_price'], (int) $item['quantity']);
        $item['line_discount'] = customer_cart_line_discount(
            (int) $item['line_subtotal'],
            (int) $item['discount_value'],
            (string) $item['discount_type'],
            (int) $item['quantity']
        );
        $item['show_low_stock'] = (int) $item['stock'] > 0 && (int) $item['stock'] <= 10;
        $subtotal += (int) $item['line_subtotal'];
        $totalDiscount += (int) $item['line_discount'];
    }
    unset($item);

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'total_discount' => $totalDiscount,
        'additional_note' => (string) ($_SESSION['cart_additional_note'] ?? ''),
        'requires_login' => false,
    ];
}

function customer_cart_add(array $input): array
{
    $userId = customer_cart_require_user_id();
    $productId = (int) ($input['product_id'] ?? 0);
    $quantity = max(1, (int) ($input['quantity'] ?? 1));

    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid product selected.');
    }

    $product = cart_add_item($userId, $productId, $quantity);

    return [
        'product' => $product,
        'cart_count' => cart_count_items_for_user($userId),
    ];
}

function customer_cart_update(array $input): array
{
    $userId = customer_cart_require_user_id();
    $productId = (int) ($input['product_id'] ?? 0);
    $quantity = (int) ($input['quantity'] ?? 1);

    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid cart item.');
    }

    cart_update_item_quantity($userId, $productId, $quantity);
    $cart = customer_cart_fetch_view_model();

    return [
        'cart_count' => cart_count_items_for_user($userId),
        'subtotal' => $cart['subtotal'],
        'total_discount' => $cart['total_discount'],
    ];
}

function customer_cart_remove(array $input): array
{
    $userId = customer_cart_require_user_id();
    $productId = (int) ($input['product_id'] ?? 0);

    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid cart item.');
    }

    cart_remove_item($userId, $productId);
    $cart = customer_cart_fetch_view_model();

    return [
        'cart_count' => cart_count_items_for_user($userId),
        'subtotal' => $cart['subtotal'],
        'total_discount' => $cart['total_discount'],
    ];
}

function customer_cart_save_note(array $input): array
{
    $user = customer_auth_current_user();
    if (!$user) {
        throw new RuntimeException('Please log in to continue.');
    }

    $_SESSION['cart_additional_note'] = trim((string) ($input['additional_note'] ?? ''));

    return [
        'additional_note' => (string) $_SESSION['cart_additional_note'],
    ];
}
