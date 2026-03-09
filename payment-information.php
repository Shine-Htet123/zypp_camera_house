<?php
$paymentSections = [
    [
        'title' => 'Bank Transfers',
        'logos' => [
            ['label' => 'KBZ BANK', 'image' => '/storage/uploads/contents/payment-information/kbz-bank.png'],
            ['label' => 'AYA Bank', 'image' => '/storage/uploads/contents/payment-information/aya-bank.png'],
            ['label' => 'CB BANK', 'image' => '/storage/uploads/contents/payment-information/cb-bank.png'],
            ['label' => 'uab', 'image' => '/storage/uploads/contents/payment-information/uab-bank.png'],
            ['label' => 'AGDBANK', 'image' => '/storage/uploads/contents/payment-information/agd-bank.png'],
        ],
        'notes' => [
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore',
            'Lorem ipsum dolor sit amet, consectetur',
        ],
    ],
    [
        'title' => 'Mobile Wallets',
        'logos' => [
            ['label' => 'KBZ Pay', 'image' => '/storage/uploads/contents/payment-information/kbzpay.png'],
            ['label' => 'AYA PAY', 'image' => '/storage/uploads/contents/payment-information/ayapay.png'],
            ['label' => 'uab pay', 'image' => '/storage/uploads/contents/payment-information/uabpay.png'],
            ['label' => 'wave', 'image' => '/storage/uploads/contents/payment-information/wavepay.png'],
        ],
        'notes' => [
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore',
            'Lorem ipsum dolor sit amet, consectetur',
        ],
    ],
    [
        'title' => 'Cash on Delivery',
        'logos' => [],
        'notes' => [
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore',
            'Lorem ipsum dolor sit amet, consectetur',
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/assets/css/payment-information.css">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="payment-info-page">
        <header class="payment-info-header reveal-on-scroll">
            <h1>Our Available Payment Options</h1>
        </header>

        <section class="payment-info-card reveal-on-scroll">
            <?php foreach ($paymentSections as $section): ?>
                <article class="payment-option">
                    <h2><?php echo htmlspecialchars($section['title']); ?></h2>

                    <?php if (!empty($section['logos'])): ?>
                        <div class="payment-logo-grid">
                            <?php foreach ($section['logos'] as $logo): ?>
                                <div class="payment-logo-badge">
                                    <img
                                        src="<?php echo htmlspecialchars($logo['image']); ?>"
                                        alt="<?php echo htmlspecialchars($logo['label']); ?>"
                                        loading="lazy"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';"
                                    >
                                    <span class="payment-logo-fallback"><?php echo htmlspecialchars($logo['label']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <ul class="payment-option-notes">
                        <?php foreach ($section['notes'] as $note): ?>
                            <li><?php echo htmlspecialchars($note); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
