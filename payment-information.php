<?php
require_once __DIR__ . '/database/site_content.php';

$paymentContent = site_content_get_payment_information();
$paymentMethodsBySection = [];
foreach (site_content_get_payment_method_definitions(true) as $method) {
    $sectionKey = trim((string) ($method['section_key'] ?? ''));
    if ($sectionKey === '') {
        continue;
    }

    if (!isset($paymentMethodsBySection[$sectionKey])) {
        $paymentMethodsBySection[$sectionKey] = [];
    }

    $paymentMethodsBySection[$sectionKey][] = $method;
}
$seoTitle = (string) ($paymentContent['page_title'] ?? 'Payment Information');
$seoDescription = 'See available payment methods, account details, and payment instructions for ZYPP Camera House orders.';
$seoCanonical = app_url('/payment-information.php');
$seoImage = '/storage/uploads/contents/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/payment-information.css')); ?>">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="payment-info-page">
        <header class="payment-info-header">
            <h2><?php echo htmlspecialchars((string) ($paymentContent['page_title'] ?? 'Our Available Payment Options')); ?></h2>
            <p>Choose the payment option that feels easiest for you. The details below show where to pay and what to prepare.</p>
        </header>

        <section class="payment-info-card reveal-on-scroll">
            <?php foreach ((array) ($paymentContent['sections'] ?? []) as $section): ?>
                <?php $sectionKey = trim((string) ($section['key'] ?? '')); ?>
                <?php
                $sectionMethods = (array) ($paymentMethodsBySection[$sectionKey] ?? []);
                $sectionLogos = [];
                foreach ($sectionMethods as $method) {
                    $logoImage = trim((string) ($method['logo_image'] ?? ''));
                    $logoLabel = trim((string) ($method['label'] ?? ''));
                    if ($logoImage === '' && $logoLabel === '') {
                        continue;
                    }

                    $sectionLogos[] = [
                        'image' => $logoImage,
                        'label' => $logoLabel,
                    ];
                }

                if ($sectionLogos === []) {
                    $sectionLogos = (array) ($section['logos'] ?? []);
                }
                ?>
                <article class="payment-option">
                    <h2><?php echo htmlspecialchars((string) ($section['title'] ?? '')); ?></h2>

                    <?php if ($sectionLogos !== []): ?>
                        <div class="payment-logo-grid">
                            <?php foreach ($sectionLogos as $logo): ?>
                                <div class="payment-logo-badge">
                                    <img
                                        src="<?php echo htmlspecialchars(site_content_image_url((string) ($logo['image'] ?? ''), '')); ?>"
                                        alt="<?php echo htmlspecialchars((string) ($logo['label'] ?? '')); ?>"
                                        loading="lazy"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';"
                                    >
                                    <span class="payment-logo-fallback"><?php echo htmlspecialchars((string) ($logo['label'] ?? '')); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <ul class="payment-option-notes">
                        <?php foreach ((array) ($section['notes'] ?? []) as $note): ?>
                            <li><?php echo htmlspecialchars((string) $note); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($sectionMethods !== []): ?>
                        <div class="payment-method-info-grid">
                            <?php foreach ($sectionMethods as $method): ?>
                                <?php
                                $instructions = array_values(array_filter(array_map(
                                    static fn ($line): string => trim((string) $line),
                                    (array) ($method['instructions'] ?? [])
                                )));
                                ?>
                                <div class="payment-method-info-card">
                                    <h3><?php echo htmlspecialchars((string) ($method['label'] ?? '')); ?></h3>
                                    <?php if (trim((string) ($method['account_name'] ?? '')) !== ''): ?>
                                        <p><strong>Account name:</strong> <?php echo htmlspecialchars((string) $method['account_name']); ?></p>
                                    <?php endif; ?>
                                    <?php if (trim((string) ($method['account_number'] ?? '')) !== ''): ?>
                                        <p><strong>Account number:</strong> <?php echo htmlspecialchars((string) $method['account_number']); ?></p>
                                    <?php endif; ?>
                                    <?php if (trim((string) ($method['phone'] ?? '')) !== ''): ?>
                                        <p><strong>Contact phone:</strong> <?php echo htmlspecialchars((string) $method['phone']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($instructions !== []): ?>
                                        <ul>
                                            <?php foreach ($instructions as $instruction): ?>
                                                <li><?php echo htmlspecialchars($instruction); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
