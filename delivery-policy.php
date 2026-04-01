<?php
require_once __DIR__ . '/database/site_content.php';

$deliveryContent = site_content_get_delivery_policy();
$seoTitle = (string) ($deliveryContent['title'] ?? 'Delivery Information');
$seoDescription = 'Read delivery coverage, process, and shipping expectations for orders placed with ZYPP Camera House.';
$seoCanonical = app_url('/delivery-policy.php');
$seoImage = site_content_image_url((string) ($deliveryContent['image'] ?? ''), '/storage/uploads/contents/logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/delivery-policy.css')); ?>">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="delivery-policy-page">
        <header class="delivery-policy-header">
            <h1><?php echo htmlspecialchars((string) ($deliveryContent['title'] ?? 'Delivery Information')); ?></h1>
        </header>

        <section class="delivery-policy-hero">
            <div class="delivery-plan-card">
                <img src="<?php echo htmlspecialchars(site_content_image_url((string) ($deliveryContent['image'] ?? ''), '')); ?>" alt="Delivery plan">
            </div>
        </section>

        <section class="delivery-policy-content">
            <ol class="delivery-policy-list">
                <?php foreach ((array) ($deliveryContent['policies'] ?? []) as $policy): ?>
                    <li><?php echo htmlspecialchars((string) $policy); ?></li>
                <?php endforeach; ?>
            </ol>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
