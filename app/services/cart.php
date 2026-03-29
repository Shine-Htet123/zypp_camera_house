<?php

require_once __DIR__ . '/../../config/customer_bootstrap.php';
require_once __DIR__ . '/customer_auth.php';
require_once __DIR__ . '/../../database/user/cart.php';
require_once __DIR__ . '/../../database/bundles.php';

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

function customer_cart_line_total(int $subtotal, int $lineDiscount): int
{
    return max($subtotal - $lineDiscount, 0);
}

function customer_cart_sort_bundles_for_pricing(array $bundles): array
{
    usort($bundles, static function (array $left, array $right): int {
        $leftPriority = (int) ($left['priority'] ?? PHP_INT_MAX);
        $rightPriority = (int) ($right['priority'] ?? PHP_INT_MAX);
        if ($leftPriority !== $rightPriority) {
            return $leftPriority <=> $rightPriority;
        }

        return (int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0);
    });

    return $bundles;
}

function customer_cart_allocate_bundle_discount(array $participants, int $bundleDiscount): array
{
    if ($participants === [] || $bundleDiscount <= 0) {
        return [];
    }

    $totalSubtotal = array_sum(array_map(
        static fn (array $participant): int => (int) ($participant['subtotal'] ?? 0),
        $participants
    ));
    if ($totalSubtotal <= 0) {
        return [];
    }

    $allocations = [];
    $remaining = $bundleDiscount;
    $lastIndex = count($participants) - 1;

    foreach ($participants as $index => $participant) {
        $productId = (int) ($participant['product_id'] ?? 0);
        if ($productId <= 0) {
            continue;
        }

        if ($index === $lastIndex) {
            $allocation = $remaining;
        } else {
            $allocation = (int) round(($bundleDiscount * (int) ($participant['subtotal'] ?? 0)) / $totalSubtotal);
            $allocation = max(0, min($allocation, $remaining));
        }

        $allocations[$productId] = ($allocations[$productId] ?? 0) + $allocation;
        $remaining -= $allocation;
    }

    return $allocations;
}

function customer_cart_apply_bundle_pricing(int $userId, array $items): array
{
    if ($userId <= 0 || $items === []) {
        return [
            'items' => $items,
            'has_bundle_pricing' => false,
        ];
    }

    $bundles = customer_cart_sort_bundles_for_pricing(bundle_fetch_customer_bundles($userId));
    if ($bundles === []) {
        return [
            'items' => $items,
            'has_bundle_pricing' => false,
        ];
    }

    $itemIndexByProductId = [];
    $availableQuantities = [];

    foreach ($items as $index => &$item) {
        $productId = (int) ($item['product_id'] ?? 0);
        $itemIndexByProductId[$productId] = $index;
        $availableQuantities[$productId] = (int) ($item['quantity'] ?? 0);
        $item['bundle_reserved_quantity'] = 0;
        $item['bundle_line_discount'] = 0;
        $item['matched_bundle_names'] = [];
    }
    unset($item);

    $hasBundlePricing = false;

    foreach ($bundles as $bundle) {
        $participants = [];
        $bundleApplications = null;

        foreach ((array) ($bundle['items'] ?? []) as $bundleItem) {
            $productId = (int) ($bundleItem['product_id'] ?? 0);
            $requiredQty = max(1, (int) ($bundleItem['qty'] ?? 1));
            $itemIndex = $itemIndexByProductId[$productId] ?? null;

            if ($productId <= 0 || $itemIndex === null) {
                $bundleApplications = 0;
                break;
            }

            $availableQty = max(0, (int) ($availableQuantities[$productId] ?? 0));
            $applicationsForItem = intdiv($availableQty, $requiredQty);
            $bundleApplications = $bundleApplications === null
                ? $applicationsForItem
                : min($bundleApplications, $applicationsForItem);
        }

        if (($bundleApplications ?? 0) <= 0) {
            continue;
        }

        foreach ((array) ($bundle['items'] ?? []) as $bundleItem) {
            $productId = (int) ($bundleItem['product_id'] ?? 0);
            $requiredQty = max(1, (int) ($bundleItem['qty'] ?? 1)) * $bundleApplications;
            $item = $items[$itemIndexByProductId[$productId]];
            $lineSubtotal = customer_cart_line_subtotal((float) ($item['unit_price'] ?? 0), $requiredQty);

            $participants[] = [
                'product_id' => $productId,
                'quantity' => $requiredQty,
                'subtotal' => $lineSubtotal,
            ];
        }

        $bundleSubtotal = array_sum(array_column($participants, 'subtotal'));
        $bundleDiscount = (int) round(bundle_calculate_discount_amount($bundleSubtotal, $bundle));
        if ($bundleDiscount <= 0) {
            continue;
        }

        $allocations = customer_cart_allocate_bundle_discount($participants, $bundleDiscount);
        if ($allocations === []) {
            continue;
        }

        foreach ($participants as $participant) {
            $productId = (int) ($participant['product_id'] ?? 0);
            $itemIndex = $itemIndexByProductId[$productId];

            $items[$itemIndex]['bundle_reserved_quantity'] += (int) ($participant['quantity'] ?? 0);
            $items[$itemIndex]['bundle_line_discount'] += (int) ($allocations[$productId] ?? 0);
            $items[$itemIndex]['matched_bundle_names'][] = (string) ($bundle['bundle_name'] ?? 'Bundle');
            $availableQuantities[$productId] = max(
                0,
                (int) ($availableQuantities[$productId] ?? 0) - (int) ($participant['quantity'] ?? 0)
            );
        }

        $hasBundlePricing = true;
    }

    foreach ($items as &$item) {
        $lineSubtotal = customer_cart_line_subtotal((float) ($item['unit_price'] ?? 0), (int) ($item['quantity'] ?? 0));
        $bundleQuantity = min((int) ($item['quantity'] ?? 0), max(0, (int) ($item['bundle_reserved_quantity'] ?? 0)));
        $regularQuantity = max(0, (int) ($item['quantity'] ?? 0) - $bundleQuantity);
        $regularSubtotal = customer_cart_line_subtotal((float) ($item['unit_price'] ?? 0), $regularQuantity);
        $baseDiscount = $regularQuantity > 0
            ? customer_cart_line_discount(
                $regularSubtotal,
                (int) ($item['discount_value'] ?? 0),
                (string) ($item['discount_type'] ?? 'percentage'),
                $regularQuantity
            )
            : 0;
        $bundleDiscount = max(0, (int) ($item['bundle_line_discount'] ?? 0));
        $lineDiscount = min($lineSubtotal, $baseDiscount + $bundleDiscount);

        $item['base_line_discount'] = $baseDiscount;
        $item['bundle_line_discount'] = $bundleDiscount;
        $item['line_subtotal'] = $lineSubtotal;
        $item['line_discount'] = $lineDiscount;
        $item['line_total'] = customer_cart_line_total($lineSubtotal, $lineDiscount);
        $item['matched_bundle_names'] = array_values(array_unique(array_filter(
            array_map('trim', (array) ($item['matched_bundle_names'] ?? [])),
            static fn (string $name): bool => $name !== ''
        )));
        $item['has_bundle_discount'] = $bundleDiscount > 0;
        $item['discount_summary'] = $bundleDiscount > 0
            ? '- ' . customer_cart_format_mmk($lineDiscount) . ' MMK total'
            : '- ' . customer_cart_format_mmk((int) ($item['discount_value'] ?? 0)) . ' ' . ((string) ($item['discount_type'] ?? 'percentage') === 'fixed' ? 'MMK' : '%') . ' each';
        $item['discount_detail'] = $bundleDiscount > 0
            ? ($baseDiscount > 0 ? 'Bundle + product discount' : 'Bundle discount applied')
            : 'Product discount';
        $item['show_low_stock'] = (int) ($item['stock'] ?? 0) > 0 && (int) ($item['stock'] ?? 0) <= 10;
    }
    unset($item);

    return [
        'items' => $items,
        'has_bundle_pricing' => $hasBundlePricing,
    ];
}

function customer_cart_fetch_view_model(): array
{
    $user = customer_auth_current_user();
    if (!$user) {
        return [
            'items' => [],
            'items_total' => 0,
            'subtotal' => 0,
            'total_discount' => 0,
            'additional_note' => (string) ($_SESSION['cart_additional_note'] ?? ''),
            'requires_login' => true,
            'has_bundle_pricing' => false,
        ];
    }

    $items = cart_fetch_items_for_user((int) $user['id']);
    $bundlePricing = customer_cart_apply_bundle_pricing((int) $user['id'], $items);
    $items = $bundlePricing['items'];
    $itemsTotal = 0;
    $subtotal = 0;
    $totalDiscount = 0;

    foreach ($items as &$item) {
        if (!array_key_exists('line_subtotal', $item)) {
            $item['base_line_discount'] = customer_cart_line_discount(
                customer_cart_line_subtotal((float) ($item['unit_price'] ?? 0), (int) ($item['quantity'] ?? 0)),
                (int) ($item['discount_value'] ?? 0),
                (string) ($item['discount_type'] ?? 'percentage'),
                (int) ($item['quantity'] ?? 0)
            );
            $item['bundle_line_discount'] = 0;
            $item['line_subtotal'] = customer_cart_line_subtotal((float) ($item['unit_price'] ?? 0), (int) ($item['quantity'] ?? 0));
            $item['line_discount'] = (int) $item['base_line_discount'];
            $item['line_total'] = customer_cart_line_total((int) $item['line_subtotal'], (int) $item['line_discount']);
            $item['matched_bundle_names'] = [];
            $item['has_bundle_discount'] = false;
            $item['discount_summary'] = '- ' . customer_cart_format_mmk((int) ($item['discount_value'] ?? 0)) . ' ' . ((string) ($item['discount_type'] ?? 'percentage') === 'fixed' ? 'MMK' : '%') . ' each';
            $item['discount_detail'] = 'Product discount';
            $item['show_low_stock'] = (int) ($item['stock'] ?? 0) > 0 && (int) ($item['stock'] ?? 0) <= 10;
        }

        $itemsTotal += (int) $item['line_subtotal'];
        $subtotal += (int) $item['line_total'];
        $totalDiscount += (int) $item['line_discount'];
    }
    unset($item);

    return [
        'items' => $items,
        'items_total' => $itemsTotal,
        'subtotal' => $subtotal,
        'total_discount' => $totalDiscount,
        'additional_note' => (string) ($_SESSION['cart_additional_note'] ?? ''),
        'requires_login' => false,
        'has_bundle_pricing' => !empty($bundlePricing['has_bundle_pricing']),
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

function customer_cart_add_bundle(array $input): array
{
    $userId = customer_cart_require_user_id();
    $bundleId = (int) ($input['bundle_id'] ?? 0);
    $quantity = max(1, (int) ($input['quantity'] ?? 1));

    if ($bundleId <= 0) {
        throw new InvalidArgumentException('Invalid bundle selected.');
    }

    $bundle = bundle_fetch_customer_bundle($bundleId, $userId);
    if (!$bundle) {
        throw new RuntimeException('This bundle is unavailable right now.');
    }

    if ((int) ($bundle['availability_count'] ?? 0) <= 0) {
        throw new RuntimeException('This bundle is currently out of stock.');
    }

    $existingQuantities = [];
    foreach (cart_fetch_items_for_user($userId) as $item) {
        $existingQuantities[(int) ($item['product_id'] ?? 0)] = (int) ($item['quantity'] ?? 0);
    }

    foreach ((array) ($bundle['items'] ?? []) as $bundleItem) {
        $productId = (int) ($bundleItem['product_id'] ?? 0);
        $requiredQty = max(1, (int) ($bundleItem['qty'] ?? 1)) * $quantity;
        $stockQuantity = max(0, (int) ($bundleItem['stock_quantity'] ?? 0));
        $existingQty = (int) ($existingQuantities[$productId] ?? 0);

        if ($stockQuantity < $existingQty + $requiredQty) {
            throw new RuntimeException('Not enough stock is available to add that bundle.');
        }
    }

    foreach ((array) ($bundle['items'] ?? []) as $bundleItem) {
        cart_add_item(
            $userId,
            (int) ($bundleItem['product_id'] ?? 0),
            max(1, (int) ($bundleItem['qty'] ?? 1)) * $quantity
        );
    }

    return [
        'bundle' => $bundle,
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
        'items_total' => $cart['items_total'],
        'subtotal' => $cart['subtotal'],
        'total_discount' => $cart['total_discount'],
        'requires_refresh' => !empty($cart['has_bundle_pricing']),
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
        'items_total' => $cart['items_total'],
        'subtotal' => $cart['subtotal'],
        'total_discount' => $cart['total_discount'],
        'requires_refresh' => !empty($cart['has_bundle_pricing']),
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
