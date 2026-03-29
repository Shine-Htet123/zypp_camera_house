<?php
require_once __DIR__ . '/app/services/checkout.php';

customer_auth_require_login('/payment.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    try {
        if ($action === 'submit_payment_proof') {
            $confirmation = checkout_finalize_order($_FILES['payment_proof'] ?? null);
            customer_auth_json([
                'success' => true,
                'message' => 'Payment proof uploaded successfully.',
                'payload' => [
                    'order_public_id' => (string) $confirmation['public_order_id'],
                    'created_at' => (string) $confirmation['created_at'],
                ],
            ]);
        }

        if ($action === 'place_cod_order') {
            $confirmation = checkout_finalize_order(null);
            customer_auth_json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'payload' => [
                    'order_public_id' => (string) $confirmation['public_order_id'],
                    'created_at' => (string) $confirmation['created_at'],
                ],
            ]);
        }

        throw new InvalidArgumentException('Invalid payment request.');
    } catch (Throwable $exception) {
        if (customer_auth_is_json_request()) {
            customer_auth_json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        checkout_flash_set($exception->getMessage(), 'error');
        customer_auth_redirect('/payment.php');
    }
}

try {
    $paymentView = checkout_build_payment_view_model();
} catch (Throwable $exception) {
    checkout_flash_set($exception->getMessage(), 'error');
    customer_auth_redirect('/delivery.php');
}

$paymentFlash = checkout_flash_consume();
$draft = $paymentView['draft'];
$paymentMethod = is_array($paymentView['payment_method'] ?? null) ? $paymentView['payment_method'] : [];
$orderItems = $paymentView['items'];
$subtotal = $paymentView['subtotal'];
$discount = $paymentView['total_discount'];
$grandTotal = $paymentView['grand_total'];
$hasFreeDelivery = $paymentView['has_free_delivery'];
$requiresPaymentProof = $paymentView['requires_payment_proof'];
$paymentQrCardImage = site_content_image_url((string) ($paymentMethod['qr_image'] ?? ''), '');
$paymentInstructions = array_values(array_filter(array_map(
    static fn ($line): string => trim((string) $line),
    (array) ($paymentMethod['instructions'] ?? [])
)));
$paymentAccountNumber = trim((string) ($paymentMethod['account_number'] ?? ''));
$paymentAccountName = trim((string) ($paymentMethod['account_name'] ?? ''));
$paymentPhone = trim((string) ($paymentMethod['phone'] ?? ''));
$paymentMethodLabel = trim((string) ($paymentMethod['label'] ?? ($draft['payment_method_label'] ?? 'Payment')));
$displayOrderNo = '#Pending';
$displayOrderDate = date('d/m/Y');
$displayOrderTime = date('H:i:s');
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
                <p><strong>Order No.</strong> <span data-order-public-id><?php echo htmlspecialchars($displayOrderNo); ?></span></p>
                <p><strong>Order Date -</strong> <span data-order-date><?php echo htmlspecialchars($displayOrderDate); ?></span></p>
                <p><strong>Order Time -</strong> <span class="muted" data-order-time><?php echo htmlspecialchars($displayOrderTime); ?></span></p>
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
                        <span class="cell item"><?php echo htmlspecialchars((string) $item['name']); ?></span>
                        <span class="cell qty"><?php echo (int) $item['quantity']; ?></span>
                        <span class="cell price"><?php echo checkout_format_mmk((float) $item['line_subtotal']); ?> MMK</span>
                    </div>
                <?php endforeach; ?>

                <div class="payment-total-row">
                    <span class="cell total-label">Subtotal</span>
                    <span class="cell total-value"><?php echo checkout_format_mmk($subtotal); ?> MMK</span>
                </div>
                <div class="payment-total-row discount">
                    <span class="cell total-label">Total Discount</span>
                    <span class="cell total-value">- <?php echo checkout_format_mmk($discount); ?> MMK</span>
                </div>
                <div class="payment-total-row">
                    <span class="cell total-label">Grand Total</span>
                    <span class="cell total-value"><?php echo checkout_format_mmk($grandTotal); ?> MMK</span>
                </div>
            </div>

            <?php if ($hasFreeDelivery): ?>
                <p class="delivery-note">Congratulations! You got <span>Free of Charges</span> for delivery!</p>
            <?php endif; ?>

            <div class="payment-details" data-payment-details>
                <div class="detail-group">
                    <h4>Delivery Information</h4>
                    <p><?php echo htmlspecialchars($draft['first_name'] . ' ' . $draft['last_name']); ?></p>
                    <p><?php echo htmlspecialchars((string) $draft['email']); ?></p>
                    <p><?php echo htmlspecialchars((string) $draft['phone']); ?></p>
                    <?php if ($draft['delivery_type'] === 'pickup'): ?>
                        <p>Pick up at store</p>
                    <?php else: ?>
                        <p>
                            <?php
                            echo htmlspecialchars(
                                implode(', ', array_filter([
                                    (string) ($draft['address'] ?? ''),
                                    (string) ($draft['township'] ?? ''),
                                    (string) ($draft['city'] ?? ''),
                                ]))
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="detail-group">
                    <h4>Additional Note</h4>
                    <p><?php echo htmlspecialchars((string) ($draft['additional_note'] !== '' ? $draft['additional_note'] : '-')); ?></p>
                </div>
            </div>

            <div class="payment-actions" data-payment-actions data-payment-mode="<?php echo $requiresPaymentProof ? 'proof' : 'cod'; ?>">
                <a class="back-link" href="<?php echo htmlspecialchars(app_path('/delivery.php')); ?>">Back</a>
                <button
                    type="button"
                    class="pay-btn"
                    data-open-payment-modal
                    data-cod-mode="<?php echo $requiresPaymentProof ? '0' : '1'; ?>"
                >
                    <?php echo $requiresPaymentProof ? 'Pay Now' : 'Confirm Order'; ?>
                </button>
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
                    <a
                        class="pay-btn receipt-btn"
                        href="<?php echo htmlspecialchars(app_path('/receipt.php')); ?>"
                        target="_blank"
                        rel="noopener"
                        data-receipt-link
                    >
                        Download E-receipt
                    </a>
                    <a class="continue-shopping-link" href="<?php echo htmlspecialchars(app_path('/products.php')); ?>">Continue Shopping</a>
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

            <h2 id="paymentModalTitle" class="payment-modal-title">Pay with <?php echo htmlspecialchars($paymentMethodLabel); ?></h2>

            <?php if ($paymentQrCardImage !== ''): ?>
                <div class="payment-qr-card">
                    <img src="<?php echo htmlspecialchars($paymentQrCardImage); ?>" alt="<?php echo htmlspecialchars($paymentMethodLabel); ?>">
                </div>
            <?php endif; ?>

            <?php if ($paymentAccountNumber !== '' || $paymentAccountName !== '' || $paymentPhone !== ''): ?>
                <div class="payment-account-meta">
                    <?php if ($paymentAccountNumber !== ''): ?>
                        <div class="payment-account-row">
                            <span class="label">Account No.</span>
                            <span class="value"><?php echo htmlspecialchars($paymentAccountNumber); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($paymentAccountName !== ''): ?>
                        <div class="payment-account-row">
                            <span class="label">Account Name.</span>
                            <span class="value"><?php echo htmlspecialchars($paymentAccountName); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($paymentPhone !== ''): ?>
                        <div class="payment-account-row">
                            <span class="label">Phone Number.</span>
                            <span class="value"><?php echo htmlspecialchars($paymentPhone); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="payment-upload-section">
                <h3>Note</h3>
                <?php if ($paymentInstructions !== []): ?>
                    <?php foreach ($paymentInstructions as $instruction): ?>
                        <p><?php echo htmlspecialchars($instruction); ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Please transfer the exact amount and upload your payment proof.</p>
                <?php endif; ?>
                <?php if ($paymentPhone !== ''): ?>
                    <p class="payment-phone"><strong>Phone Number:</strong> <?php echo htmlspecialchars($paymentPhone); ?></p>
                <?php endif; ?>

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
                        <source src="<?php echo htmlspecialchars(app_path('/storage/uploads/contents/video/sandy-loading.mp4')); ?>" type="video/mp4">
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
    <?php if ($paymentFlash): ?>
        <script>window.__paymentFlash = <?php echo json_encode($paymentFlash, JSON_UNESCAPED_SLASHES); ?>;</script>
    <?php endif; ?>
    <script src="<?php echo htmlspecialchars(app_path('/assets/js/payment.js')); ?>"></script>
</body>
</html>
