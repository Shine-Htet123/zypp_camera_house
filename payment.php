<?php
session_start();

function format_mmk($value) {
    return number_format((int) $value);
}

$orderItems = [
    [
        'name' => 'Canon EOS R6 Mark II',
        'quantity' => 1,
        'price' => 3000000,
    ],
    [
        'name' => 'Canon EOS R6 Mark II',
        'quantity' => 1,
        'price' => 3000000,
    ],
];

$subtotal = array_sum(array_map(static fn($item) => $item['price'] * $item['quantity'], $orderItems));
$discount = 60000;
$grandTotal = $subtotal - $discount;
$postedDeliveryMethod = $_POST['delivery_method'] ?? '';
$paymentQrCardImage = '/storage/uploads/contents/logo.png';

if ($postedDeliveryMethod !== '') {
    $_SESSION['checkout_delivery_method'] = $postedDeliveryMethod;
}

$selectedDeliveryMethod = $_SESSION['checkout_delivery_method'] ?? '';
$hasFreeDelivery = $selectedDeliveryMethod === 'royal' && $subtotal >= 1000000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './head.php'; ?>
    <link rel="stylesheet" href="./assets/css/payment.css">
</head>
<body>
    <?php include './navbar.php'; ?>

    <main class="payment-page">
        <section class="checkout-steps">
            <span class="step done">Cart</span>
            <span class="step-line"></span>
            <span class="step done">Delivery</span>
            <span class="step-line"></span>
            <span class="step active" data-step-payment>Payment</span>
            <span class="step-line"></span>
            <span class="step" data-step-confirmation>Confirmation</span>
        </section>

        <h1 class="payment-title">Order Confirmation</h1>

        <section class="payment-card" data-payment-card>
            <header class="payment-header">
                <h2>ZYPP Camera House</h2>
                <p><strong>Order No.</strong> <span>#202601260001</span></p>
                <p><strong>Order Date -</strong> <span>20/12/2025</span></p>
                <p><strong>Order Time -</strong> <span class="muted">12:00:00</span></p>
            </header>

            <h3 class="summary-title">Order Summary</h3>

            <div class="payment-summary">
                <div class="payment-row payment-head">
                    <span class="cell no">No.</span>
                    <span class="cell item">items</span>
                    <span class="cell qty">Qty.</span>
                    <span class="cell price">Price</span>
                </div>

                <?php foreach ($orderItems as $index => $item): ?>
                    <div class="payment-row">
                        <span class="cell no"><?php echo $index + 1; ?></span>
                        <span class="cell item"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="cell qty"><?php echo (int) $item['quantity']; ?></span>
                        <span class="cell price"><?php echo format_mmk($item['price']); ?> MMK</span>
                    </div>
                <?php endforeach; ?>

                <div class="payment-total-row">
                    <span class="cell total-label">Subtotal</span>
                    <span class="cell total-value"><?php echo format_mmk($subtotal); ?> MMK</span>
                </div>
                <div class="payment-total-row discount">
                    <span class="cell total-label">Total Discount</span>
                    <span class="cell total-value">- <?php echo format_mmk($discount); ?> MMK</span>
                </div>
                <div class="payment-total-row">
                    <span class="cell total-label">Grand Total</span>
                    <span class="cell total-value"><?php echo format_mmk($grandTotal); ?> MMK</span>
                </div>
            </div>

            <?php if ($hasFreeDelivery): ?>
                <p class="delivery-note">Congratulations! You got <span>Free of Charges</span> for delivery!</p>
            <?php endif; ?>

            <div class="payment-details" data-payment-details>
                <div class="detail-group">
                    <h4>Delivery Information</h4>
                    <p>Kyaw Ko Ko</p>
                    <p>kyawkokko@gmail.com</p>
                    <p>09771751530</p>
                    <p>No. 96, Pyay Road, Hlaing Township, Yangon</p>
                </div>

                <div class="detail-group">
                    <h4>Additional Note</h4>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore</p>
                </div>
            </div>

            <div class="payment-actions" data-payment-actions>
                <a class="back-link" href="/delivery.php">Back</a>
                <button type="button" class="pay-btn" data-open-payment-modal>Pay Now</button>
            </div>

            <div class="payment-confirmed" data-payment-confirmed hidden>
                <div class="payment-confirmed-box">
                    Thank you for your purchase.
                    <br>
                    A confirmation email will be sent once
                    <br>
                    your order is confirmed.
                </div>
                <div class="payment-confirmed-actions">
                    <a class="pay-btn receipt-btn" href="/check-order.php">Download E-receipt</a>
                    <a class="continue-shopping-link" href="/products.php">Continue Shopping</a>
                </div>
            </div>
        </section>
    </main>

    <div class="payment-modal-overlay" data-payment-overlay hidden>
        <div class="payment-modal payment-modal-main" data-payment-modal aria-modal="true" role="dialog" aria-labelledby="paymentModalTitle">
            <div class="payment-modal-view" data-payment-view="form">
            <button type="button" class="payment-modal-close" data-close-payment-modal aria-label="Close payment dialog">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <h2 id="paymentModalTitle" class="payment-modal-title">Pay Here</h2>

            <div class="payment-qr-card">
                <img src="<?php echo htmlspecialchars($paymentQrCardImage); ?>" alt="KBZPay payment card">
            </div>

            <div class="payment-account-meta">
                <div class="payment-account-row">
                    <span class="label">Account No.</span>
                    <span class="value">09123456789</span>
                </div>
                <div class="payment-account-row">
                    <span class="label">Account Name.</span>
                    <span class="value">Shine Htet</span>
                </div>
            </div>

            <div class="payment-upload-section">
                <h3>Note</h3>
                <p>
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut
                    labore et dolore magna aliqua. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
                    eiusmod tempor incididunt ut labore et dolore magna aliqua.
                </p>
                <p class="payment-phone"><strong>Phone Number:</strong> 09123456789</p>

                <h3>Upload Payment Proof</h3>

                <div class="payment-upload-box" data-upload-box>
                    <input type="file" id="paymentProofInput" class="payment-file-input" accept="image/*" data-payment-proof-input>
                    <label for="paymentProofInput" class="payment-upload-label">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Drag & Drop to Upload</span>
                    </label>
                    <div class="payment-upload-preview" data-payment-preview hidden>
                        <img src="" alt="Uploaded payment proof" data-payment-preview-image>
                    </div>
                    <button type="button" class="payment-upload-btn" data-trigger-payment-upload>Upload</button>
                </div>
            </div>

            <div class="payment-modal-actions">
                <button type="button" class="back-link modal-back-link" data-close-payment-modal>Back</button>
                <button type="button" class="pay-btn modal-pay-btn" data-submit-payment-proof>I Have Paid</button>
            </div>
            </div>

            <div class="payment-modal-view payment-modal-success" data-payment-view="success" hidden>
                <div class="payment-success-illustration" aria-hidden="true">
                    <video autoplay muted loop playsinline>
                        <source src="/storage/uploads/contents/video/sandy-loading.mp4" type="video/mp4">
                    </video>
                </div>
                <h2 id="paymentSuccessTitle">Upload successful!</h2>
                <p>
                    Our team will verify the information and contact you shortly.
                    <br>
                    You can continue shopping.
                </p>
                <button type="button" class="pay-btn success-continue-btn" data-confirm-payment>Confirm</button>
            </div>
        </div>
    </div>

    <?php include './footer.php'; ?>
    <script src="./assets/js/payment.js"></script>
</body>
</html>
