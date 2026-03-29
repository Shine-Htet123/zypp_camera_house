<?php
require_once __DIR__ . '/app/services/cart.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/cart.css')); ?>">
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
            <?php if ($cartData['requires_login']): ?>
                <div class="cart-empty-message">Please log in to view your cart.</div>
            <?php elseif ($cartItems === []): ?>
                <div class="cart-empty-message">Your cart is empty.</div>
            <?php else: ?>
            <div class="summary-table">
                <div class="summary-row summary-head">
                    <span class="head-cell">Items</span>
                    <span class="head-cell">Quantity</span>
                    <span class="head-cell">Item Price</span>
                    <span class="head-cell">Discount</span>
                    <span class="head-cell">Price</span>
                    <span class="head-cell action-head"></span>
                </div>

                <?php foreach ($cartItems as $item): ?>
                    <div
                        class="summary-row cart-row"
                        data-cart-row
                        data-product-id="<?php echo (int) $item['product_id']; ?>"
                        data-stock="<?php echo (int) $item['stock']; ?>"
                        data-unit-price="<?php echo (int) $item['unit_price']; ?>"
                        data-discount-value="<?php echo (int) $item['discount_value']; ?>"
                        data-discount-type="<?php echo htmlspecialchars($item['discount_type']); ?>"
                        data-line-subtotal="<?php echo (int) $item['line_subtotal']; ?>"
                        data-line-discount="<?php echo (int) $item['line_discount']; ?>"
                        data-line-total="<?php echo (int) $item['line_total']; ?>"
                    >
                        <input type="hidden" name="cart[<?php echo (int) $item['product_id']; ?>][product_id]" value="<?php echo (int) $item['product_id']; ?>">
                        <div class="item-cell summary-cell">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="item-info">
                                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <?php if (!empty($item['matched_bundle_names'])): ?>
                                    <span class="item-bundle-note">
                                        <?php echo htmlspecialchars(implode(', ', (array) $item['matched_bundle_names'])); ?> applied
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="quantity-cell summary-cell">
                            <div class="qty-stack">
                                <button type="button" class="qty-btn" data-qty-action="increase">+</button>
                                <input
                                    type="number"
                                    class="qty-value"
                                    name="cart[<?php echo (int) $item['product_id']; ?>][quantity]"
                                    value="<?php echo (int) $item['quantity']; ?>"
                                    min="1"
                                    max="<?php echo (int) $item['stock']; ?>"
                                    inputmode="numeric"
                                    data-qty-input
                                >
                                <button type="button" class="qty-btn" data-qty-action="decrease">-</button>
                            </div>
                            <span class="qty-note<?php echo $item['show_low_stock'] ? '' : ' is-hidden'; ?>" data-stock-note>
                                Hurry! Only <span data-stock-count><?php echo (int) $item['stock']; ?></span> Left!
                            </span>
                        </div>
                        <span class="cell summary-cell">
                            <span data-price-value><?php echo customer_cart_format_mmk($item['unit_price']); ?></span> MMK
                        </span>
                        <span class="cell summary-cell">
                            <span data-discount-text><?php echo htmlspecialchars((string) ($item['discount_summary'] ?? '')); ?></span>
                            <span class="discount-detail"><?php echo htmlspecialchars((string) ($item['discount_detail'] ?? '')); ?></span>
                        </span>
                        <span class="cell summary-cell">
                            <span data-line-total><?php echo customer_cart_format_mmk($item['line_total']); ?></span> MMK
                        </span>
                        <div class="action-cell summary-cell">
                            <button type="button" class="delete-btn" aria-label="Remove" data-remove-row>
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <strong><span data-cart-items-total><?php echo customer_cart_format_mmk($itemsTotal); ?></span> MMK</strong>
                </div>
                <div class="total-row discount">
                    <span>Total Discount:</span>
                    <strong>- <span data-cart-discount><?php echo customer_cart_format_mmk($totalDiscount); ?></span> MMK</strong>
                </div>
            </div>

            <div class="note-row">
                <span class="note-label">Additional Note:</span>
                <textarea name="additional_note" data-cart-note placeholder="Add a note for your order (e.g. delivery instructions, product preferences, or special requests)"><?php echo htmlspecialchars($cartData['additional_note']); ?></textarea>
            </div>
            <?php endif; ?>
        </form>

        <?php if (!$cartData['requires_login'] && $cartItems !== []): ?>
            <div class="checkout-actions">
                    <a class="btn-checkout" href="<?php echo htmlspecialchars(app_path('/delivery.php')); ?>">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
    <?php if (is_array($cartFlash) && !empty($cartFlash['message'])): ?>
        <script>
            window.__cartFlash = <?php echo json_encode($cartFlash, JSON_UNESCAPED_SLASHES); ?>;
        </script>
    <?php endif; ?>
    <script src="<?php echo htmlspecialchars(app_path('/assets/js/cart.js')); ?>"></script>
</body>
</html>
