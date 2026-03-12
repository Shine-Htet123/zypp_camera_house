<?php
require_once __DIR__ . '/app/services/checkout.php';

customer_auth_require_login('/check-order.php');
$order = checkout_fetch_latest_confirmation();
if (!$order) {
    checkout_flash_set('No order confirmation found yet.', 'error');
    customer_auth_redirect('/user-profile.php#orders');
}
$orderStatusClass = strtolower((string) ($order['status'] ?? 'pending'));
$paymentStatusText = strtolower((string) (($order['payment_row_status'] ?? $order['payment_status']) ?? 'pending'));
$reuploadRequested = !empty($order['reupload_requested']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './head.php'; ?>
    <link rel="stylesheet" href="./assets/css/check-order.css">
</head>
<body>
    <?php include './navbar.php'; ?>

    <main class="check-order-container">
        <section class="order-card">
            <div class="order-meta">
                <h3>Order No. #<?php echo htmlspecialchars((string) $order['public_order_id']); ?></h3>
                <p>Order Date - <?php echo htmlspecialchars(date('d/m/Y', strtotime((string) $order['created_at']))); ?></p>
                <p>Order Time - <?php echo htmlspecialchars(date('H:i:s', strtotime((string) $order['created_at']))); ?></p>
            </div>

            <h4 class="order-summary-title">Order Summary</h4>
            <div class="summary-table">
                <div class="summary-row head">
                    <span class="cell">No.</span>
                    <span class="cell">Product Name</span>
                    <span class="cell">Qty.</span>
                    <span class="cell">Price</span>
                </div>
                <?php foreach (($order['items'] ?? []) as $index => $item): ?>
                    <div class="summary-row">
                        <span class="cell"><?php echo $index + 1; ?></span>
                        <span class="cell"><?php echo htmlspecialchars((string) $item['name']); ?></span>
                        <span class="cell"><?php echo (int) $item['qty']; ?></span>
                        <span class="cell"><?php echo checkout_format_mmk((float) $item['final_price']); ?> MMK</span>
                    </div>
                <?php endforeach; ?>
                <div class="summary-total">
                    <span class="cell label">Subtotal</span>
                    <span class="cell value"><?php echo checkout_format_mmk((float) $order['subtotal']); ?> MMK</span>
                </div>
                <div class="summary-total">
                    <span class="cell label">Discount</span>
                    <span class="cell value discount">-<?php echo checkout_format_mmk((float) $order['total_discount']); ?> MMK</span>
                </div>
                <div class="summary-total">
                    <span class="cell label">Grand Total</span>
                    <span class="cell value"><?php echo checkout_format_mmk((float) $order['grand_total']); ?> MMK</span>
                </div>
                <?php if (($order['delivery_type'] ?? 'delivery') === 'delivery' && !empty($order['delivery_method_name'])): ?>
                    <div class="free-note"><?php echo htmlspecialchars((string) $order['delivery_method_name']); ?></div>
                <?php endif; ?>
            </div>

            <div class="order-details">
                <div class="detail-block">
                    <h5>Delivery Information</h5>
                    <p><?php echo htmlspecialchars(trim(((string) ($order['first_name'] ?? '')) . ' ' . ((string) ($order['last_name'] ?? '')))); ?></p>
                    <p><?php echo htmlspecialchars((string) ($order['email'] ?? '')); ?></p>
                    <p><?php echo htmlspecialchars((string) ($order['phone'] ?? '')); ?></p>
                    <?php if (($order['delivery_type'] ?? 'delivery') === 'pickup'): ?>
                        <p>Pick up at store</p>
                    <?php else: ?>
                        <p>
                            <?php
                            echo htmlspecialchars(
                                implode(', ', array_filter([
                                    (string) ($order['address'] ?? ''),
                                    (string) ($order['township'] ?? ''),
                                    (string) ($order['city'] ?? ''),
                                ]))
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="detail-block">
                    <p><strong>Order Status</strong> <span class="status <?php echo htmlspecialchars($orderStatusClass); ?>"><?php echo htmlspecialchars(ucfirst((string) $order['status'])); ?></span></p>
                    <p><strong>Payment Status</strong> <span class="status <?php echo htmlspecialchars($paymentStatusText); ?>" data-payment-status-text><?php echo htmlspecialchars(ucfirst((string) ($order['payment_row_status'] ?? $order['payment_status']))); ?></span></p>
                    <p><strong>Additional Note</strong></p>
                    <p><?php echo htmlspecialchars((string) (($order['additional_note'] ?? '') !== '' ? $order['additional_note'] : '-')); ?></p>
                </div>
            </div>

            <?php if ($reuploadRequested): ?>
                <div class="reupload-request-card" data-reupload-request-card>
                    <h5>Payment Proof Re-Upload Requested</h5>
                    <p>Admin requested a new payment screenshot for this order. Please upload a clearer or updated payment proof.</p>
                    <input type="file" id="reuploadPaymentProofInput" class="reupload-file-input" accept="image/*" data-reupload-input>
                    <div class="reupload-actions">
                        <button type="button" class="download-btn reupload-btn reupload-select-btn" data-reupload-trigger>Select Image</button>
                        <button type="button" class="download-btn reupload-btn reupload-submit-btn" data-reupload-submit disabled>Submit Re-Upload</button>
                    </div>
                    <p class="reupload-file-name" data-reupload-file-name>No file selected.</p>
                </div>
            <?php endif; ?>

            <div class="order-actions">
                <a href="<?php echo htmlspecialchars(app_path('/user-profile.php#orders')); ?>">Go Back to Profile</a>
                <button type="button" class="download-btn">Download E-receipt</button>
            </div>
        </section>
    </main>
    <?php include './footer.php'; ?>
    <?php if ($reuploadRequested): ?>
        <script>
            window.__orderReupload = <?php echo json_encode([
                'orderPublicId' => (string) $order['public_order_id'],
            ], JSON_UNESCAPED_SLASHES); ?>;
        </script>
        <script src="./assets/js/check-order.js"></script>
    <?php endif; ?>
</body>
</html>
