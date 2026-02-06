<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <!-- css links -->
    <link rel="stylesheet" href="./assets/css/delivery.css">
</head>
<body>
    <!--navbar & sidebar -->
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
            <form class="delivery-form" method="post" action="">
                <div class="field-row two-col">
                    <div class="field">
                        <input type="text" name="first_name" placeholder="First Name" required>
                    </div>
                    <div class="field">
                        <input type="text" name="last_name" placeholder="Last Name" required>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <input type="text" name="company" placeholder="Company (Optional)">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <input type="tel" name="phone" placeholder="Phone" required>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                </div>

                <h3>Delivery Information</h3>
                <div class="field-row">
                    <div class="field">
                        <input type="text" name="address" placeholder="Address" required>
                    </div>
                </div>
                <div class="field-row two-col">
                    <div class="field">
                        <div class="custom-select" data-name="state">
                            <button type="button" class="select-trigger">
                                <span class="select-label">State/Province</span>
                                <span class="select-arrow"></span>
                            </button>
                            <ul class="select-options">
                                <li data-value="yangon">Yangon</li>
                                <li data-value="mandalay">Mandalay</li>
                                <li data-value="npt">Nay Pyi Taw</li>
                            </ul>
                            <input type="hidden" name="state" value="">
                        </div>
                    </div>
                    <div class="field">
                        <div class="custom-select" data-name="city">
                            <button type="button" class="select-trigger">
                                <span class="select-label">City</span>
                                <span class="select-arrow"></span>
                            </button>
                            <ul class="select-options">
                                <li data-value="yangon">Yangon</li>
                                <li data-value="mandalay">Mandalay</li>
                                <li data-value="npt">Nay Pyi Taw</li>
                            </ul>
                            <input type="hidden" name="city" value="">
                        </div>
                    </div>
                </div>
                <div class="field-row two-col">
                    <div class="field">
                        <div class="custom-select" data-name="township">
                            <button type="button" class="select-trigger">
                                <span class="select-label">Township</span>
                                <span class="select-arrow"></span>
                            </button>
                            <ul class="select-options">
                                <li data-value="mayangone">Mayangone</li>
                                <li data-value="lanmadaw">Lanmadaw</li>
                                <li data-value="bahan">Bahan</li>
                            </ul>
                            <input type="hidden" name="township" value="">
                        </div>
                    </div>
                    <div class="field">
                        <input type="text" name="postal_code" placeholder="Postal Code">
                    </div>
                </div>
                <div class="delivery-type-box">
                    <div class="delivery-type">
                        <label class="radio-row">
                            <input type="radio" name="delivery_type" value="delivery" checked>
                            <span>Delivery</span>
                        </label>
                        <hr>
                        <label class="radio-row">
                            <input type="radio" name="delivery_type" value="pickup">
                            <span>Pick up</span>
                        </label>
                    </div>
                    <label class="checkbox-row">
                        <input type="checkbox" name="set_default">
                        <span>Set as Default</span>
                    </label>
                </div>

                <div class="field-row two-col">
                    <div class="field">
                        <div class="custom-select" data-name="delivery_method">
                            <button type="button" class="select-trigger">
                                <span class="select-label">Delivery Method</span>
                                <span class="select-arrow"></span>
                            </button>
                            <ul class="select-options">
                                <li data-value="royal">Royal Express</li>
                                <li data-value="dhl">DHL</li>
                            </ul>
                            <input type="hidden" name="delivery_method" value="">
                        </div>
                    </div>
                    <div class="field">
                        <div class="custom-select" data-name="payment_method">
                            <button type="button" class="select-trigger">
                                <span class="select-label">Payment Method</span>
                                <span class="select-arrow"></span>
                            </button>
                            <ul class="select-options">
                                <li data-value="cod">Cash on Delivery</li>
                                <li data-value="kbzpay">KBZPay</li>
                                <li data-value="wave">Wave Pay</li>
                                <li data-value="aya">AYA Pay</li>
                            </ul>
                            <input type="hidden" name="payment_method" value="">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="back-btn">Back</button>
                    <button type="submit" class="continue-btn">Continue</button>
                </div>
            </form>
        </section>
    </main>

    <!--js links -->
    <script src="./assets/js/navbar.js"></script>
    <script src="./assets/js/delivery.js"></script>
</body>
</html>
