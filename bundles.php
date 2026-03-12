<?php

require_once __DIR__ . '/app/services/customer_auth.php';
require_once __DIR__ . '/database/bundles.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$currentCustomer = customer_auth_current_user();
$currentCustomerId = isset($currentCustomer['id']) ? (int) $currentCustomer['id'] : null;
$bundles = bundle_fetch_customer_bundles($currentCustomerId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/bundles.css')); ?>">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="bundle-list-page">
        <div class="breadcrumb">Shop / Bundles</div>

        <section class="bundle-list-header">
            <h1>Bundle Offers</h1>
            <p>Browse curated product sets with one linked bundle discount.</p>
        </section>

        <section class="bundle-grid">
            <?php if ($bundles === []): ?>
                <div class="bundle-empty-state">No bundle offers are available right now.</div>
            <?php else: ?>
                <?php foreach ($bundles as $bundle): ?>
                    <a class="bundle-card" href="<?php echo htmlspecialchars((string) $bundle['detail_url']); ?>">
                        <div class="bundle-card-image">
                            <?php $stackItems = array_slice($bundle['items'], 0, 4); ?>
                            <div class="bundle-image-stack bundle-image-stack-card bundle-image-stack--count-<?php echo count($stackItems); ?>" aria-hidden="true">
                                <?php foreach ($stackItems as $index => $item): ?>
                                    <div class="bundle-stack-item" style="--stack-index: <?php echo (int) $index; ?>;">
                                        <img src="<?php echo htmlspecialchars((string) $item['image_url']); ?>" alt="<?php echo htmlspecialchars((string) $item['name']); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="bundle-card-body">
                            <div class="bundle-card-top">
                                <span class="bundle-badge"><?php echo htmlspecialchars((string) $bundle['discount_value_label']); ?></span>
                                <span class="bundle-stock <?php echo $bundle['availability_count'] > 0 ? 'available' : 'unavailable'; ?>">
                                    <?php echo htmlspecialchars((string) $bundle['availability_label']); ?>
                                </span>
                            </div>
                            <h2><?php echo htmlspecialchars((string) $bundle['bundle_name']); ?></h2>
                            <p class="bundle-card-id"><?php echo htmlspecialchars((string) $bundle['public_bundle_id']); ?></p>
                            <p class="bundle-card-meta">
                                <?php echo (int) $bundle['product_count']; ?> product(s) / <?php echo (int) $bundle['item_qty']; ?> total item(s)
                            </p>
                            <div class="bundle-price">
                                <span class="bundle-price-old"><?php echo htmlspecialchars((string) $bundle['subtotal_label']); ?></span>
                                <span class="bundle-price-new"><?php echo htmlspecialchars((string) $bundle['final_total_label']); ?></span>
                            </div>
                            <p class="bundle-save">Save <?php echo htmlspecialchars((string) $bundle['discount_amount_label']); ?></p>
                            <ul class="bundle-card-items">
                                <?php foreach (array_slice($bundle['items'], 0, 3) as $item): ?>
                                    <li><?php echo (int) $item['qty']; ?> x <?php echo htmlspecialchars((string) $item['name']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
