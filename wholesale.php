<?php require_once __DIR__ . '/config/app.php'; ?>
<?php
$seoTitle = 'Wholesale';
$seoDescription = 'Submit a wholesale inquiry to buy ZYPP Camera House products for your business, studio, or retail store.';
$seoCanonical = app_url('/wholesale.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/wholesale.css')); ?>">
    <script src="<?php echo htmlspecialchars(app_path('/assets/js/wholesale.js')); ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="wholesale-page">
        <section class="wholesale-notice reveal-on-scroll">
            <p>Please complete the form below.</p>
            <p>Our team will contact you within 1-2 business days.</p>
        </section>

        <section class="wholesale-card reveal-on-scroll">
            <h1>Wholesale</h1>

            <form class="wholesale-form" data-wholesale-form novalidate>
                <div class="field-group">
                    <label for="business-name">Business Name</label>
                    <input id="business-name" name="business_name" type="text" required>
                </div>

                <div class="field-group">
                    <label for="contact-person">Contact Person Name</label>
                    <input id="contact-person" name="contact_person" type="text" required>
                </div>

                <div class="field-group">
                    <label for="phone-number">Phone No.</label>
                    <input id="phone-number" name="phone_number" type="tel" required>
                </div>

                <div class="field-group">
                    <label for="email-address">Email Address</label>
                    <input id="email-address" name="email_address" type="email" required>
                </div>

                <div class="field-group">
                    <label for="business-type">Business Type:</label>
                    <div class="select-wrap">
                        <select id="business-type" name="business_type" required>
                            <option value="" selected disabled></option>
                            <option value="retailer">Retailer</option>
                            <option value="studio">Studio</option>
                            <option value="reseller">Reseller</option>
                            <option value="production-house">Production House</option>
                            <option value="education">Education</option>
                            <option value="other">Other</option>
                        </select>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="field-group">
                    <label for="additional-note">Additional Note</label>
                    <textarea id="additional-note" name="additional_note" rows="7"></textarea>
                </div>

                <label class="agreement-row">
                    <input type="checkbox" name="agreement" required>
                    <span>I agree that the information provided above is correct.</span>
                </label>

                <div class="wholesale-actions">
                    <button type="submit" class="submit-btn">Submit Inquiry</button>
                </div>
                <p class="wholesale-form-error" data-wholesale-error hidden></p>
            </form>
        </section>
    </main>

    <div class="wholesale-modal" data-wholesale-modal aria-hidden="true">
        <div class="wholesale-modal__backdrop" data-close-wholesale-modal></div>
        <div class="wholesale-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="wholesale-modal-title">
            <h2 id="wholesale-modal-title">Thank you for completing the inquiry</h2>
            <p>Your inquiry has been received.</p>
            <p>Our team will contact you soon.</p>
            <a href="<?php echo htmlspecialchars(app_path('/')); ?>" class="wholesale-modal__home">Back to Home</a>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
