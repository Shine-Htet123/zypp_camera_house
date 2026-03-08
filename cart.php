<?php
function format_mmk($value) {
    return number_format((int) $value);
}

function get_discount_label($value, $type) {
    $unit = $type === 'fixed' ? 'MMK' : '%';
    return sprintf('- %s %s each', format_mmk($value), $unit);
}

function calculate_line_subtotal($price, $quantity) {
    return (int) $price * (int) $quantity;
}

function calculate_line_discount($price, $quantity, $discountValue, $discountType) {
    $subtotal = calculate_line_subtotal($price, $quantity);

    if ($discountType === 'fixed') {
        return min((int) $discountValue * (int) $quantity, $subtotal);
    }

    return (int) round($subtotal * ((int) $discountValue / 100));
}

$cartItems = [
    [
        'id' => 101,
        'name' => 'Canon EOS R6 Mark II',
        'image' => '/storage/uploads/products/placeholder-camera.png',
        'quantity' => 10,
        'stock' => 10,
        'unit_price' => 3000000,
        'discount_value' => 10,
        'discount_type' => 'percentage',
    ],
    [
        'id' => 102,
        'name' => 'Canon EOS R6 Mark II',
        'image' => '/storage/uploads/products/placeholder-camera.png',
        'quantity' => 10,
        'stock' => 12,
        'unit_price' => 3000000,
        'discount_value' => 100000,
        'discount_type' => 'fixed',
    ],
];

$subtotal = 0;
$totalDiscount = 0;

foreach ($cartItems as &$item) {
    $item['line_subtotal'] = calculate_line_subtotal($item['unit_price'], $item['quantity']);
    $item['line_discount'] = calculate_line_discount($item['unit_price'], $item['quantity'], $item['discount_value'], $item['discount_type']);
    $item['show_low_stock'] = $item['stock'] > 0 && $item['stock'] <= 10;
    $subtotal += $item['line_subtotal'];
    $totalDiscount += $item['line_discount'];
}
unset($item);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/assets/css/cart.css">
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

        <form class="summary-card" action="/cart.php" method="post">
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
                        data-product-id="<?php echo (int) $item['id']; ?>"
                        data-stock="<?php echo (int) $item['stock']; ?>"
                        data-unit-price="<?php echo (int) $item['unit_price']; ?>"
                        data-discount-value="<?php echo (int) $item['discount_value']; ?>"
                        data-discount-type="<?php echo htmlspecialchars($item['discount_type']); ?>"
                    >
                        <input type="hidden" name="cart[<?php echo (int) $item['id']; ?>][product_id]" value="<?php echo (int) $item['id']; ?>">
                        <div class="item-cell summary-cell">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="item-info">
                                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            </div>
                        </div>
                        <div class="quantity-cell summary-cell">
                            <div class="qty-stack">
                                <button type="button" class="qty-btn" data-qty-action="increase">+</button>
                                <input
                                    type="number"
                                    class="qty-value"
                                    name="cart[<?php echo (int) $item['id']; ?>][quantity]"
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
                            <span data-price-value><?php echo format_mmk($item['unit_price']); ?></span> MMK
                        </span>
                        <span class="cell summary-cell">
                            - <span data-discount-value><?php echo format_mmk($item['discount_value']); ?></span> <span data-discount-unit><?php echo $item['discount_type'] === 'fixed' ? 'MMK' : '%'; ?></span> each
                        </span>
                        <span class="cell summary-cell">
                            <span data-line-subtotal><?php echo format_mmk($item['line_subtotal']); ?></span> MMK
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
                    <strong><span data-cart-subtotal><?php echo format_mmk($subtotal); ?></span> MMK</strong>
                </div>
                <div class="total-row discount">
                    <span>Total Discount:</span>
                    <strong>- <span data-cart-discount><?php echo format_mmk($totalDiscount); ?></span> MMK</strong>
                </div>
            </div>

            <div class="note-row">
                <span class="note-label">Additional Note:</span>
                <textarea name="additional_note" placeholder="Add a note for your order (e.g. delivery instructions, product preferences, or special requests)"></textarea>
            </div>
        </form>

        <div class="checkout-actions">
            <a class="btn-checkout" href="/delivery.php">Proceed to Checkout</a>
        </div>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
    <script src="/assets/js/cart.js"></script>
</body>
</html>
