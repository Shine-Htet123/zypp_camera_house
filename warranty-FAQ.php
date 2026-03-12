<?php
require_once __DIR__ . '/database/site_content.php';

$warrantyContent = site_content_get_warranty_faq();
$warrantyCategories = (array) ($warrantyContent['categories'] ?? []);
$defaultCategory = (string) (($warrantyCategories[0]['key'] ?? 'camera'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <link rel="stylesheet" href="./assets/css/warranty-FAQ.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="warranty-page">
        <header class="warranty-hero">
            <h2><?php echo htmlspecialchars((string) ($warrantyContent['hero_title'] ?? 'Warranty & FAQs')); ?></h2>
            <p><?php echo htmlspecialchars((string) ($warrantyContent['hero_subtitle'] ?? '')); ?></p>
        </header>

        <section class="category-selector">
            <button class="arrow-btn" type="button" aria-label="Previous category">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="category-list">
                <?php foreach ($warrantyCategories as $category): ?>
                    <?php $categoryKey = (string) ($category['key'] ?? ''); ?>
                    <button class="category-card <?php echo $categoryKey === $defaultCategory ? 'active' : ''; ?>" type="button" data-category="<?php echo htmlspecialchars($categoryKey); ?>">
                        <div class="category-image">
                            <img src="<?php echo htmlspecialchars(site_content_image_url((string) ($category['image'] ?? ''), '')); ?>" alt="<?php echo htmlspecialchars((string) ($category['label'] ?? '')); ?>">
                        </div>
                        <span><?php echo htmlspecialchars((string) ($category['label'] ?? '')); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <button class="arrow-btn" type="button" aria-label="Next category">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </section>

        <section class="warranty-policy">
            <h3>Warranty Policy</h3>
            <?php foreach ($warrantyCategories as $category): ?>
                <?php $categoryKey = (string) ($category['key'] ?? ''); ?>
                <ol class="policy-list policy-group <?php echo $categoryKey === $defaultCategory ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($categoryKey); ?>">
                    <?php foreach ((array) ($category['policy'] ?? []) as $policy): ?>
                        <li><?php echo htmlspecialchars((string) $policy); ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endforeach; ?>
        </section>

        <section class="faq-section">
            <h3>FAQs</h3>
            <?php foreach ($warrantyCategories as $category): ?>
                <?php $categoryKey = (string) ($category['key'] ?? ''); ?>
                <div class="faq-list faq-group <?php echo $categoryKey === $defaultCategory ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($categoryKey); ?>">
                    <?php foreach ((array) ($category['faqs'] ?? []) as $index => $faq): ?>
                        <div class="faq-item <?php echo $index === 0 ? 'open' : ''; ?>">
                            <button type="button" class="faq-question">
                                <span><?php echo htmlspecialchars((string) ($faq['q'] ?? '')); ?></span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo htmlspecialchars((string) ($faq['a'] ?? '')); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

    <?php include './footer.php'; ?>

    <script src="./assets/js/warranty-FAQ.js"></script>
</body>
</html>
