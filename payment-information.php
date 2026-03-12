<?php
require_once __DIR__ . '/database/site_content.php';

$paymentContent = site_content_get_payment_information();
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
        </header>

        <section class="payment-info-card reveal-on-scroll">
            <?php foreach ((array) ($paymentContent['sections'] ?? []) as $section): ?>
                <article class="payment-option">
                    <h2><?php echo htmlspecialchars((string) ($section['title'] ?? '')); ?></h2>

                    <?php if (!empty($section['logos'])): ?>
                        <div class="payment-logo-grid">
                            <?php foreach ((array) $section['logos'] as $logo): ?>
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
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
