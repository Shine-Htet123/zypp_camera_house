<?php

require_once __DIR__ . '/../app/services/cart.php';
require_once __DIR__ . '/../database/bundles.php';

if (!function_exists('customer_cart_add_bundle')) {
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
                max(1, (int) ($bundleItem['qty'] ?? 1)) * $quantity,
                $bundleId
            );
        }

        return [
            'bundle' => $bundle,
            'cart_count' => cart_count_items_for_user($userId),
        ];
    }
}

$input = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST;

try {
    $action = trim((string) ($input['action'] ?? 'add_item'));
    $bundleId = (int) ($input['bundle_id'] ?? 0);
    $productId = (int) ($input['product_id'] ?? 0);

    // Accept bundle adds by bundle id as a fallback so bundle requests still
    // work even if the action flag is missing or stale client code is loaded.
    $isBundleAdd = $action === 'add_bundle' || ($bundleId > 0 && $productId <= 0);
    $payload = $isBundleAdd ? customer_cart_add_bundle($input) : customer_cart_add($input);
    $successMessage = $isBundleAdd ? 'Bundle added to cart.' : 'Product added to cart.';

    if (customer_auth_is_json_request()) {
        customer_auth_json([
            'success' => true,
            'message' => $successMessage,
            'payload' => $payload,
        ]);
    }

    customer_cart_set_flash($successMessage, 'success');
    customer_auth_redirect('/cart.php');
} catch (Throwable $exception) {
    if (customer_auth_is_json_request()) {
        $status = str_contains(strtolower($exception->getMessage()), 'log in') ? 401 : 422;
        customer_auth_json([
            'success' => false,
            'message' => $exception->getMessage(),
            'login_required' => $status === 401,
        ], $status);
    }

    customer_cart_set_flash($exception->getMessage(), 'error');
    customer_auth_redirect('/cart.php');
}
