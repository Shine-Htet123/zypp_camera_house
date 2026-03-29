<?php

require_once __DIR__ . '/app/services/customer_auth.php';
require_once __DIR__ . '/database/bundles.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$bundleId = (int) ($_GET['id'] ?? 0);
$currentCustomer = customer_auth_current_user();
$currentCustomerId = isset($currentCustomer['id']) ? (int) $currentCustomer['id'] : null;
$bundle = bundle_fetch_customer_bundle($bundleId, $currentCustomerId);

if (!$bundle) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/bundle-details.css')); ?>">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="bundle-details-page">
        <div class="breadcrumb">
            <a href="<?php echo htmlspecialchars(app_path('/bundles.php')); ?>">Bundles</a>
            <span>/</span>
            <span><?php echo htmlspecialchars((string) ($bundle['bundle_name'] ?? 'Bundle Not Found')); ?></span>
        </div>

        <?php if (!$bundle): ?>
            <section class="bundle-not-found">
                <h1>Bundle not found</h1>
                <p>This bundle is unavailable or no longer active.</p>
                <a href="<?php echo htmlspecialchars(app_path('/bundles.php')); ?>" class="bundle-primary-link">Back to Bundles</a>
            </section>
        <?php else: ?>
            <section class="bundle-hero-card">
                <div class="bundle-hero-image">
                    <?php $stackItems = array_slice($bundle['items'], 0, 4); ?>
                    <div class="bundle-image-stack bundle-image-stack-hero bundle-image-stack--count-<?php echo count($stackItems); ?>" aria-hidden="true">
                        <?php foreach ($stackItems as $index => $item): ?>
                            <div class="bundle-stack-item" style="--stack-index: <?php echo (int) $index; ?>;">
                                <img src="<?php echo htmlspecialchars((string) $item['image_url']); ?>" alt="<?php echo htmlspecialchars((string) $item['name']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="bundle-hero-content">
                    <div
                        class="bundle-purchase-panel"
                        data-bundle-info
                        data-bundle-id="<?php echo (int) $bundle['id']; ?>"
                        data-bundle-stock="<?php echo (int) $bundle['availability_count']; ?>"
                    >
                    <span class="bundle-badge"><?php echo htmlspecialchars((string) $bundle['discount_value_label']); ?></span>
                    <h1><?php echo htmlspecialchars((string) $bundle['bundle_name']); ?></h1>
                    <p class="bundle-public-id"><?php echo htmlspecialchars((string) $bundle['public_bundle_id']); ?></p>
                    <p class="bundle-availability <?php echo $bundle['availability_count'] > 0 ? 'available' : 'unavailable'; ?>">
                        <?php echo htmlspecialchars((string) $bundle['availability_note']); ?>
                    </p>
                    <div class="bundle-price-row">
                        <span class="bundle-price-old"><?php echo htmlspecialchars((string) $bundle['subtotal_label']); ?></span>
                        <span class="bundle-price-new"><?php echo htmlspecialchars((string) $bundle['final_total_label']); ?></span>
                    </div>
                    <div class="bundle-save-box">Save <?php echo htmlspecialchars((string) $bundle['discount_amount_label']); ?></div>
                    <div class="bundle-stat-grid">
                        <div class="bundle-stat">
                            <span class="label">Products</span>
                            <span class="value"><?php echo (int) $bundle['product_count']; ?></span>
                        </div>
                        <div class="bundle-stat">
                            <span class="label">Item Qty</span>
                            <span class="value"><?php echo (int) $bundle['item_qty']; ?></span>
                        </div>
                        <div class="bundle-stat">
                            <span class="label">Discount</span>
                            <span class="value"><?php echo htmlspecialchars((string) $bundle['discount_badge']); ?></span>
                        </div>
                    </div>
                    <div class="bundle-action-row">
                        <div class="bundle-qty-controls" aria-label="Bundle quantity">
                            <button type="button" data-bundle-qty="decrease">-</button>
                            <span data-bundle-qty-value>1</span>
                            <button type="button" data-bundle-qty="increase">+</button>
                        </div>
                        <button
                            type="button"
                            class="bundle-buy-btn"
                            data-add-bundle-to-cart
                            <?php echo (int) $bundle['availability_count'] > 0 ? '' : 'disabled'; ?>
                        >
                            <?php echo (int) $bundle['availability_count'] > 0 ? 'Add Bundle to Cart' : 'Out of Stock'; ?>
                        </button>
                    </div>
                    </div>
                </div>
            </section>

            <section class="bundle-items-section">
                <h2>Included Products</h2>
                <div class="bundle-items-list">
                    <?php foreach ($bundle['items'] as $item): ?>
                        <a class="bundle-item-card" href="<?php echo htmlspecialchars((string) $item['detail_url']); ?>">
                            <div class="bundle-item-image">
                                <img src="<?php echo htmlspecialchars((string) $item['image_url']); ?>" alt="<?php echo htmlspecialchars((string) $item['name']); ?>">
                            </div>
                            <div class="bundle-item-copy">
                                <h3><?php echo htmlspecialchars((string) $item['name']); ?></h3>
                                <p><?php echo htmlspecialchars((string) $item['brand_name']); ?> / <?php echo htmlspecialchars((string) $item['category_name']); ?></p>
                                <div class="bundle-item-meta">
                                    <span><?php echo (int) $item['qty']; ?> x <?php echo htmlspecialchars((string) $item['unit_price_label']); ?></span>
                                    <span><?php echo htmlspecialchars((string) $item['line_subtotal_label']); ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
    <script src="<?php echo htmlspecialchars(app_path('/assets/js/bundle-details.js')); ?>"></script>
</body>
</html>
