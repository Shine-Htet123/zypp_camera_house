<?php
require_once __DIR__ . '/app/services/cart.php';

function cart_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cart_render_row(array $row): string
{
    $rowType = (string) ($row['row_type'] ?? 'product');
    $rowName = cart_escape((string) ($row['name'] ?? 'Item'));
    $rowImage = cart_escape((string) ($row['image'] ?? ''));
    $quantity = max(1, (int) ($row['quantity'] ?? 1));
    $stock = max($quantity, (int) ($row['stock'] ?? $quantity));
    $discountSummary = trim((string) ($row['discount_summary'] ?? ''));
    $discountSummary = $discountSummary !== '' ? $discountSummary : 'No discount';
    $stockNote = trim((string) ($row['stock_note'] ?? ''));
    $rowAttributes = [
        'class="summary-row cart-row' . ($rowType === 'bundle' ? ' bundle-cart-row' : '') . '"',
        'data-cart-row',
        'data-row-type="' . cart_escape($rowType) . '"',
        'data-stock="' . $stock . '"',
    ];

    if ($rowType === 'bundle') {
        $rowAttributes[] = 'data-bundle-id="' . (int) ($row['bundle_id'] ?? 0) . '"';
    } else {
        $rowAttributes[] = 'data-cart-item-id="' . (int) ($row['cart_item_id'] ?? 0) . '"';
        $rowAttributes[] = 'data-product-id="' . (int) ($row['product_id'] ?? 0) . '"';
    }

    ob_start();
    ?>
    <div <?php echo implode(' ', $rowAttributes); ?>>
        <div class="item-cell summary-cell">
            <?php if ($rowType === 'bundle'): ?>
                <?php if (!empty($row['has_custom_image']) && $rowImage !== ''): ?>
                    <img src="<?php echo $rowImage; ?>" alt="<?php echo $rowName; ?>" class="bundle-cart-cover-image">
                <?php else: ?>
                    <?php $stackItems = array_slice((array) ($row['included_items'] ?? []), 0, 4); ?>
                    <div class="bundle-cart-image-stack bundle-cart-image-stack--count-<?php echo count($stackItems); ?>" aria-hidden="true">
                        <?php foreach ($stackItems as $index => $item): ?>
                            <div class="bundle-cart-stack-item" style="--stack-index: <?php echo (int) $index; ?>;">
                                <img src="<?php echo cart_escape((string) ($item['image_url'] ?? '')); ?>" alt="">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <img src="<?php echo $rowImage; ?>" alt="<?php echo $rowName; ?>">
            <?php endif; ?>
            <div class="item-info">
                <span class="item-name"><?php echo $rowName; ?></span>
                <?php if ($rowType === 'bundle'): ?>
                    <span class="item-bundle-note">Included items</span>
                    <span class="bundle-includes">
                        <?php
                        $included = array_map(
                            static fn (array $item): string => cart_escape((string) ($item['name'] ?? 'Item')) . ' x' . max(1, (int) ($item['qty'] ?? 1)),
                            (array) ($row['included_items'] ?? [])
                        );
                        echo implode(', ', $included);
                        ?>
                    </span>
                <?php elseif (!empty($row['matched_bundle_names'])): ?>
                    <span class="item-bundle-note">
                        <?php echo cart_escape(implode(', ', (array) $row['matched_bundle_names'])); ?> applied
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="quantity-cell summary-cell">
            <div class="qty-stack">
                <button type="button" class="qty-btn" data-qty-action="decrease">-</button>
                <input
                    type="number"
                    class="qty-value"
                    value="<?php echo $quantity; ?>"
                    min="1"
                    max="<?php echo $stock; ?>"
                    inputmode="numeric"
                    data-qty-input
                >
                <button type="button" class="qty-btn" data-qty-action="increase">+</button>
            </div>
            <span class="qty-note<?php echo $stockNote !== '' ? '' : ' is-hidden'; ?>" data-stock-note>
                <?php echo $stockNote !== '' ? cart_escape($stockNote) : ''; ?>
            </span>
        </div>
        <div class="cell summary-cell">
            <span class="money-line">
                <span data-price-value><?php echo customer_cart_format_mmk((int) ($row['unit_price'] ?? 0)); ?></span>
                <span class="currency-unit">MMK</span>
            </span>
        </div>
        <div class="cell summary-cell discount-cell">
            <span class="discount-main" data-discount-text><?php echo cart_escape($discountSummary); ?></span>
        </div>
        <div class="cell summary-cell">
            <span class="money-line">
                <span data-line-total><?php echo customer_cart_format_mmk((int) ($row['line_total'] ?? 0)); ?></span>
                <span class="currency-unit">MMK</span>
            </span>
        </div>
        <div class="action-cell summary-cell">
            <button type="button" class="delete-btn" aria-label="Remove" data-remove-row>
                <i class="fa-regular fa-trash-can"></i>
            </button>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
}

function cart_render_dynamic_content(array $cartData): string
{
    if (!empty($cartData['requires_login'])) {
        return '<div class="cart-empty-message">Please log in to view your cart.</div>';
    }

    $displayRows = (array) ($cartData['display_rows'] ?? []);
    if ($displayRows === []) {
        return '<div class="cart-empty-message">Your cart is empty.</div>';
    }

    ob_start();
    ?>
    <div class="summary-table">
        <div class="summary-row summary-head">
            <span class="head-cell">Items</span>
            <span class="head-cell">Quantity</span>
            <span class="head-cell">Item Price</span>
            <span class="head-cell">Discount</span>
            <span class="head-cell">Price</span>
            <span class="head-cell action-head"></span>
        </div>

        <?php foreach ($displayRows as $row): ?>
            <?php echo cart_render_row($row); ?>
        <?php endforeach; ?>
    </div>

    <div class="summary-divider"></div>

    <div class="summary-totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <strong><span data-cart-items-total><?php echo customer_cart_format_mmk((int) ($cartData['items_total'] ?? 0)); ?></span> MMK</strong>
        </div>
        <div class="total-row discount">
            <span>Total Discount:</span>
            <strong>- <span data-cart-discount><?php echo customer_cart_format_mmk((int) ($cartData['total_discount'] ?? 0)); ?></span> MMK</strong>
        </div>
    </div>

    <div class="note-row">
        <span class="note-label">Additional Note:</span>
        <textarea name="additional_note" data-cart-note placeholder="Add a note for your order (e.g. delivery instructions, product preferences, or special requests)"><?php echo cart_escape((string) ($cartData['additional_note'] ?? '')); ?></textarea>
    </div>
    <?php

    return (string) ob_get_clean();
}

function cart_render_checkout_actions(array $cartData): string
{
    $displayRows = (array) ($cartData['display_rows'] ?? []);
    if (!empty($cartData['requires_login']) || $displayRows === []) {
        return '';
    }

    return '<a class="btn-checkout" href="' . cart_escape(app_path('/delivery.php')) . '">Proceed to Checkout</a>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    try {
        if ($action === 'update_item') {
            $payload = customer_cart_update($_POST);
        } elseif ($action === 'remove_item') {
            $payload = customer_cart_remove($_POST);
        } elseif ($action === 'save_note') {
            $payload = customer_cart_save_note($_POST);
        } else {
            throw new InvalidArgumentException('Invalid cart request.');
        }

        if (customer_auth_is_json_request()) {
            $cartState = customer_cart_fetch_view_model();
            $payload = array_merge(
                is_array($payload) ? $payload : [],
                [
                    'cart_count' => customer_cart_count(),
                    'cart_html' => cart_render_dynamic_content($cartState),
                    'checkout_html' => cart_render_checkout_actions($cartState),
                ]
            );
            customer_auth_json([
                'success' => true,
                'message' => 'Cart updated.',
                'payload' => $payload,
            ]);
        }

        customer_cart_set_flash('Cart updated.', 'success');
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
}

$cartData = customer_cart_fetch_view_model();
$cartItems = $cartData['items'];
$itemsTotal = $cartData['items_total'];
$totalDiscount = $cartData['total_discount'];
$cartFlash = customer_cart_consume_flash();
$cartCssVersion = @filemtime(__DIR__ . '/assets/css/cart.css') ?: time();
$cartJsVersion = @filemtime(__DIR__ . '/assets/js/cart.js') ?: time();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php include __DIR__ . '/head.php'; ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/cart.css?v=' . (int) $cartCssVersion)); ?>">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="cart-page">
        <div class="checkout-steps">
            <span class="step active">Cart</span>
            <span class="step-line"></span>
            <span class="step">Delivery</span>
            <span class="step-line"></span>
            <span class="step">Payment</span>
            <span class="step-line"></span>
            <span class="step">Confirmation</span>
        </div>

        <h2 class="cart-title">Cart Summary</h2>

        <form
            class="summary-card"
            action="/cart.php"
            method="post"
            data-cart-has-bundle-pricing="<?php echo !empty($cartData['has_bundle_pricing']) ? '1' : '0'; ?>"
        >
            <div data-cart-dynamic><?php echo cart_render_dynamic_content($cartData); ?></div>
        </form>

        <div class="checkout-actions" data-cart-checkout-actions><?php echo cart_render_checkout_actions($cartData); ?></div>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
    <?php if (is_array($cartFlash) && !empty($cartFlash['message'])): ?>
        <script>
            window.__cartFlash = <?php echo json_encode($cartFlash, JSON_UNESCAPED_SLASHES); ?>;
        </script>
    <?php endif; ?>
    <script src="<?php echo htmlspecialchars(app_path('/assets/js/cart.js?v=' . (int) $cartJsVersion)); ?>"></script>
</body>
</html>
