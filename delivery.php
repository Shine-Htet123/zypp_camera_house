<?php
require_once __DIR__ . '/app/services/checkout.php';

customer_auth_require_login('/delivery.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        checkout_save_draft($_POST);
        customer_auth_redirect('/payment.php');
    } catch (Throwable $exception) {
        checkout_flash_set($exception->getMessage(), 'error');
        $_SESSION['checkout_draft'] = [
            'user_id' => (int) (customer_auth_current_user()['id'] ?? 0),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'company' => trim((string) ($_POST['company'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'state' => trim((string) ($_POST['state'] ?? '')),
            'city' => trim((string) ($_POST['city'] ?? '')),
            'township' => trim((string) ($_POST['township'] ?? '')),
            'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
            'delivery_type' => trim((string) ($_POST['delivery_type'] ?? 'delivery')),
            'delivery_method_id' => (int) ($_POST['delivery_method'] ?? 0),
            'payment_method' => trim((string) ($_POST['payment_method'] ?? '')),
            'set_default' => !empty($_POST['set_default']),
        ];
        customer_auth_redirect('/delivery.php');
    }
}

$checkoutFlash = checkout_flash_consume();
$prefill = checkout_get_prefill();
$deliveryMethods = checkout_fetch_delivery_methods();
$paymentMethods = checkout_get_payment_methods();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <link rel="stylesheet" href="./assets/css/delivery.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="delivery-page">
        <section class="checkout-steps">
            <span class="step done">Cart</span>
            <span class="step-line"></span>
            <span class="step active">Delivery</span>
            <span class="step-line"></span>
            <span class="step">Payment</span>
            <span class="step-line"></span>
            <span class="step">Confirmation</span>
        </section>

        <section class="delivery-card">
            <h2>Contact Information</h2>
            <form class="delivery-form" method="post" action="/delivery.php">
                <div class="field-row two-col">
                    <div class="field">
                        <input type="text" name="first_name" placeholder="First Name" required value="<?php echo htmlspecialchars((string) ($prefill['first_name'] ?? '')); ?>">
                    </div>
                    <div class="field">
                        <input type="text" name="last_name" placeholder="Last Name" required value="<?php echo htmlspecialchars((string) ($prefill['last_name'] ?? '')); ?>">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <input type="text" name="company" placeholder="Company (Optional)" value="<?php echo htmlspecialchars((string) ($prefill['company'] ?? '')); ?>">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <input type="tel" name="phone" placeholder="Phone" required value="<?php echo htmlspecialchars((string) ($prefill['phone'] ?? '')); ?>">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars((string) ($prefill['email'] ?? '')); ?>">
                    </div>
                </div>

                <h3>Delivery Information</h3>
                <div class="field-row">
                    <div class="field">
                        <input type="text" name="address" placeholder="Address" value="<?php echo htmlspecialchars((string) ($prefill['address'] ?? '')); ?>">
                    </div>
                </div>
                <div class="field-row two-col">
                    <div class="field">
                        <div class="custom-select" data-name="state">
                            <button type="button" class="select-trigger">
                                <span class="select-label"><?php echo htmlspecialchars((string) (($prefill['state'] ?? '') !== '' ? $prefill['state'] : 'State/Province')); ?></span>
                                <span class="select-arrow"></span>
                            </button>
                            <ul class="select-options">
                                <li data-value="Yangon">Yangon</li>
                                <li data-value="Mandalay">Mandalay</li>
                                <li data-value="Nay Pyi Taw">Nay Pyi Taw</li>
                            </ul>
                            <input type="hidden" name="state" value="<?php echo htmlspecialchars((string) ($prefill['state'] ?? '')); ?>">
                        </div>
                    </div>
                    <div class="field">
                        <div class="custom-select" data-name="city">
                            <button type="button" class="select-trigger">
                                <span class="select-label"><?php echo htmlspecialchars((string) (($prefill['city'] ?? '') !== '' ? $prefill['city'] : 'City')); ?></span>
                                <span class="select-arrow"></span>
                            </button>
                            <ul class="select-options">
                                <li data-value="Yangon">Yangon</li>
                                <li data-value="Mandalay">Mandalay</li>
                                <li data-value="Nay Pyi Taw">Nay Pyi Taw</li>
                            </ul>
                            <input type="hidden" name="city" value="<?php echo htmlspecialchars((string) ($prefill['city'] ?? '')); ?>">
                        </div>
                    </div>
                </div>
                <div class="field-row two-col">
                    <div class="field">
                        <div class="custom-select" data-name="township">
                            <button type="button" class="select-trigger">
                                <span class="select-label"><?php echo htmlspecialchars((string) (($prefill['township'] ?? '') !== '' ? $prefill['township'] : 'Township')); ?></span>
                                <span class="select-arrow"></span>
                            </button>
                            <ul class="select-options">
                                <li data-value="Hlaing">Hlaing</li>
                                <li data-value="Mayangone">Mayangone</li>
                                <li data-value="Bahan">Bahan</li>
                                <li data-value="Lanmadaw">Lanmadaw</li>
                            </ul>
                            <input type="hidden" name="township" value="<?php echo htmlspecialchars((string) ($prefill['township'] ?? '')); ?>">
                        </div>
                    </div>
                    <div class="field">
                        <input type="text" name="postal_code" placeholder="Postal Code" value="<?php echo htmlspecialchars((string) ($prefill['postal_code'] ?? '')); ?>">
                    </div>
                </div>
                <div class="delivery-type-box">
                    <div class="delivery-type">
                        <label class="radio-row">
                            <input type="radio" name="delivery_type" value="delivery" <?php echo (($prefill['delivery_type'] ?? 'delivery') === 'delivery') ? 'checked' : ''; ?>>
                            <span>Delivery</span>
                        </label>
                        <hr>
                        <label class="radio-row">
                            <input type="radio" name="delivery_type" value="pickup" <?php echo (($prefill['delivery_type'] ?? '') === 'pickup') ? 'checked' : ''; ?>>
                            <span>Pick up</span>
                        </label>
                    </div>
                    <label class="checkbox-row">
                        <input type="checkbox" name="set_default" <?php echo !empty($prefill['set_default']) ? 'checked' : ''; ?>>
                        <span>Set as Default</span>
                    </label>
                </div>

                <div class="field-row two-col">
                    <div class="field<?php echo (($prefill['delivery_type'] ?? 'delivery') === 'pickup') ? ' is-hidden' : ''; ?>" data-delivery-method-field>
                        <div class="custom-select" data-name="delivery_method">
                            <button type="button" class="select-trigger">
                                <span class="select-label">
                                    <?php
                                    $selectedMethodName = 'Delivery Method';
                                    foreach ($deliveryMethods as $method) {
                                        if ((int) $method['delivery_method_id'] === (int) ($prefill['delivery_method_id'] ?? 0)) {
                                            $selectedMethodName = (string) $method['name'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($selectedMethodName);
                                    ?>
                                </span>
                                <span class="select-arrow"></span>
                            </button>
                            <ul class="select-options">
                                <?php foreach ($deliveryMethods as $method): ?>
                                    <li data-value="<?php echo (int) $method['delivery_method_id']; ?>">
                                        <?php echo htmlspecialchars((string) $method['name']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <input type="hidden" name="delivery_method" value="<?php echo (int) ($prefill['delivery_method_id'] ?? 0); ?>">
                        </div>
                    </div>
                    <div class="field">
                        <div class="custom-select" data-name="payment_method">
                            <button type="button" class="select-trigger">
                                <span class="select-label">
                                    <?php
                                    $selectedPaymentLabel = 'Payment Method';
                                    foreach ($paymentMethods as $code => $label) {
                                        if ($code === ($prefill['payment_method'] ?? '')) {
                                            $selectedPaymentLabel = $label;
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($selectedPaymentLabel);
                                    ?>
                                </span>
                                <span class="select-arrow"></span>
                            </button>
                            <ul class="select-options">
                                <?php foreach ($paymentMethods as $code => $label): ?>
                                    <li data-value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($label); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars((string) ($prefill['payment_method'] ?? '')); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a class="back-btn" href="<?php echo htmlspecialchars(app_path('/cart.php')); ?>">Back</a>
                    <button type="submit" class="continue-btn">Continue</button>
                </div>
            </form>
        </section>
    </main>

    <?php include './footer.php'; ?>
    <?php if ($checkoutFlash): ?>
        <script>window.__checkoutFlash = <?php echo json_encode($checkoutFlash, JSON_UNESCAPED_SLASHES); ?>;</script>
    <?php endif; ?>
    <script src="./assets/js/delivery.js"></script>
</body>
</html>
