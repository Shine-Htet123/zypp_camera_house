<?php

require_once __DIR__ . '/app/services/products.php';
require_once __DIR__ . '/app/services/seo.php';
require_once __DIR__ . '/database/site_content.php';

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$currentCustomerId = products_current_customer_id();
$product = $productId > 0 ? fetch_product_by_id($productId, false, $currentCustomerId) : null;
$homeContent = site_content_get_home();
$productVideoSection = (array) ($homeContent['video_sections']['product_details'] ?? []);

if (!$product) {
    http_response_code(404);
}

$relatedProducts = $product ? fetch_related_products($product['product_id'], $product['category_id'], 6, $currentCustomerId) : [];
$uniqueSellingPoints = catalog_fetch_unique_selling_points_for_product_detail();
$seoCanonical = $product
    ? app_url('/product-details.php?id=' . (int) $product['product_id'])
    : app_url('/product-details.php');
$seoImage = $product ? (string) ($product['image_url'] ?? '/storage/uploads/contents/logo.png') : '/storage/uploads/contents/logo.png';
$seoType = $product ? 'product' : 'website';
$seoNoIndex = !$product;

if (!$product) {
    $seoTitle = 'Product Not Found';
    $seoDescription = 'The product you requested is unavailable or hidden from the shop.';
} else {
    $seoTitle = (string) $product['name'];
    $seoDescription = trim((string) ($product['description'] ?? '')) !== ''
        ? (string) $product['description']
        : $product['name'] . ' by ' . $product['brand_name'] . ' at ZYPP Camera House.';

    $productImageUrls = [];
    foreach ((array) ($product['images'] ?? []) as $image) {
        $imageUrl = trim((string) ($image['url'] ?? ''));
        if ($imageUrl !== '') {
            $productImageUrls[] = seo_abs_url($imageUrl);
        }
    }

    $seoStructuredData = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => (string) $product['name'],
            'image' => $productImageUrls,
            'description' => seo_clean_text((string) ($product['description'] ?? ''), 500),
            'sku' => (string) $product['product_id'],
            'brand' => [
                '@type' => 'Brand',
                'name' => (string) $product['brand_name'],
            ],
            'category' => (string) $product['category_name'],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'MMK',
                'price' => number_format((float) ($product['discounted_price_value'] ?? $product['price'] ?? 0), 2, '.', ''),
                'availability' => (int) ($product['stock_quantity'] ?? 0) > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'url' => $seoCanonical,
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_filter([
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => app_url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Products',
                    'item' => app_url('/products.php'),
                ],
                !empty($product['category_name']) ? [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => (string) $product['category_name'],
                    'item' => app_url('/products.php?category=' . rawurlencode((string) $product['category_name'])),
                ] : null,
                [
                    '@type' => 'ListItem',
                    'position' => !empty($product['category_name']) ? 4 : 3,
                    'name' => (string) $product['name'],
                    'item' => $seoCanonical,
                ],
            ])),
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="./assets/css/product-details.css">
</head>

<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="product-details-page">
        <?php if (!$product): ?>
            <section class="product-details-text">
                <h3>Product Not Found</h3>
                <p>The product you requested is unavailable or hidden from the shop.</p>
                <p><a class="primary-btn" href="<?php echo htmlspecialchars(app_path('/products.php')); ?>">Back to Shop</a></p>
            </section>
        <?php else: ?>
            <section class="breadcrumb">
                <span>Shop</span>
                <span>/</span>
                <span><?php echo htmlspecialchars($product['category_name']); ?></span>
                <?php if (!empty($product['sub_category_name'])): ?>
                    <span>/</span>
                    <span><?php echo htmlspecialchars($product['sub_category_name']); ?></span>
                <?php endif; ?>
            </section>

            <section class="product-card">
                <div class="product-gallery">
                    <div class="zoom-container" id="zoom-container">
                        <img
                            src="<?php echo htmlspecialchars($product['images'][0]['url']); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="main-image"
                            id="main-image">
                    </div>
                    <div class="thumbnail-row" id="thumbnail-row">
                        <?php foreach ($product['images'] as $index => $image): ?>
                            <button class="thumb<?php echo $index === 0 ? ' active' : ''; ?>" data-src="<?php echo htmlspecialchars($image['url']); ?>">
                                <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?> thumbnail <?php echo $index + 1; ?>" loading="lazy" decoding="async">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="product-info" data-product-info data-product-id="<?php echo (int) $product['product_id']; ?>" data-product-stock="<?php echo (int) $product['stock_quantity']; ?>">
                    <div class="title-row">
                        <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                        <div class="stock-badge<?php echo $product['stock_quantity'] > 3 ? ' neutral' : ''; ?>">
                            <i class="fa-solid <?php echo $product['stock_quantity'] > 0 ? 'fa-fire' : 'fa-box-open'; ?>"></i>
                            <span><?php echo htmlspecialchars($product['is_featured'] ? 'Featured Product' : $product['stock_badge']); ?></span>
                        </div>
                    </div>
                    <div class="info-line">
                        <span class="label">Brand:</span> <?php echo htmlspecialchars($product['brand_name']); ?>
                    </div>
                    <div class="info-line">
                        <span class="label">Stock:</span>
                        <span class="in-stock<?php echo $product['stock_quantity'] <= 0 ? ' out-of-stock' : ''; ?>">
                            <div class="in-stock-circle"></div>
                            <?php echo htmlspecialchars($product['availability_label']); ?>
                        </span>
                    </div>
                    <div class="info-line">
                        <span class="label">Category:</span> <?php echo htmlspecialchars($product['category_name']); ?>
                    </div>
                    <?php if (!empty($product['sub_category_name'])): ?>
                        <div class="info-line">
                            <span class="label">Sub-Category:</span> <?php echo htmlspecialchars($product['sub_category_name']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($product['colors'])): ?>
                        <div class="product-option-group">
                            <span class="label">Color:</span>
                            <div class="option-list" id="colorOptions">
                                <?php foreach ($product['colors'] as $index => $color): ?>
                                    <button type="button" class="option-chip<?php echo $index === 0 ? ' active' : ''; ?>" data-option-type="color">
                                        <?php echo htmlspecialchars($color['color_name']); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($product['sizes'])): ?>
                        <div class="product-option-group">
                            <span class="label">Size:</span>
                            <div class="option-list" id="sizeOptions">
                                <?php foreach ($product['sizes'] as $index => $size): ?>
                                    <button type="button" class="option-chip<?php echo $index === 0 ? ' active' : ''; ?>" data-option-type="size">
                                        <?php echo htmlspecialchars($size['size_name']); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="info-line qty-line">
                        <span class="label">Quantity:</span>
                        <div class="qty-controls">
                            <button type="button">-</button>
                            <span>1</span>
                            <button type="button">+</button>
                        </div>
                    </div>
                    <div class="price-row">
                        <?php if (!empty($product['original_price_label'])): ?>
                            <span class="old-price"><?php echo htmlspecialchars($product['original_price_label']); ?></span>
                        <?php endif; ?>
                        <span class="new-price"><?php echo htmlspecialchars($product['price_label']); ?></span>
                    </div>
                    <?php if (!empty($product['discount_badge_label'])): ?>
                        <div class="discount-name-badge"><?php echo htmlspecialchars($product['discount_badge_label']); ?></div>
                    <?php endif; ?>
                    <button class="primary-btn" type="button" data-add-to-cart>Add to Cart</button>
                </div>
            </section>

            <section class="product-details-text">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars((string) ($product['description'] ?? 'No description available.'))); ?></p>
                <?php if (!empty($product['specs'])): ?>
                    <h4>Specifications</h4>
                    <ul class="specifications">
                        <?php foreach ($product['specs'] as $spec): ?>
                            <?php
                            $specText = trim((string) ($spec['spec_name'] ?? ''));
                            if ($specText === '') {
                                $specText = trim((string) ($spec['spec_value'] ?? ''));
                            }
                            if ($specText === '') {
                                continue;
                            }
                            ?>
                            <li><?php echo htmlspecialchars($specText); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="video-link-container">
                <div class="video-link-box">
                    <div class="video-link-logo">
                        <i class="fa-solid fa-photo-film"></i>
                    </div>
                    <div class="video-link-text">
                        <h4><?php echo htmlspecialchars((string) ($productVideoSection['title'] ?? 'Unboxing & Influencers Videos')); ?></h4>
                        <p><?php echo htmlspecialchars((string) ($productVideoSection['description'] ?? 'Experience our products through influencer reviews and unboxings.')); ?></p>
                    </div>
                    <div class="video-link-btn">
                                <a href="<?php echo htmlspecialchars(app_path('/unboxing-influencers.php')); ?>">Discover More</a>
                    </div>
                </div>
            </section>

            <section class="related-products best-sellers-container">
                <div class="chevrons">
                    <i class="fa-solid fa-chevron-left"></i>
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
                <div class="best-sellers-box">
                    <h2>Related Products</h2>
                    <div class="best-seller-slider">
                        <?php foreach ($relatedProducts as $relatedProduct): ?>
                            <a href="<?php echo htmlspecialchars(app_path('/product-details.php?id=' . (int) $relatedProduct['product_id'])); ?>" class="product-card">
                                <div class="product-card-content">
                                    <div class="product-img-box">
                                        <img src="<?php echo htmlspecialchars($relatedProduct['image_url']); ?>" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>" loading="lazy" decoding="async">
                                    </div>
                                    <div class="product-description">
                                        <h4 class="product-name"><?php echo htmlspecialchars($relatedProduct['name']); ?></h4>
                                        <span class="brand-name"><?php echo htmlspecialchars($relatedProduct['brand_name']); ?></span>
                                        <div class="product-price">
                                            <?php if (!empty($relatedProduct['original_price_label'])): ?>
                                                <span class="old-price"><?php echo htmlspecialchars($relatedProduct['original_price_label']); ?></span>
                                            <?php endif; ?>
                                            <span class="new-price"><?php echo htmlspecialchars($relatedProduct['price_label']); ?></span>
                                        </div>
                                        <?php if (!empty($relatedProduct['discount_badge_label'])): ?>
                                            <div class="discount-box"><?php echo htmlspecialchars($relatedProduct['discount_badge_label']); ?></div>
                                        <?php elseif ($relatedProduct['is_featured']): ?>
                                            <div class="discount-box">Featured</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        <?php if (empty($relatedProducts)): ?>
                            <div class="product-card related-empty-card">
                                <div class="product-description">
                                    <h4 class="product-name">No related products yet</h4>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($uniqueSellingPoints)): ?>
            <section class="why-choose">
                <h3>Why Smart Vloggers Choose ZYPP Camera House?</h3>
                <div class="why-list">
                    <?php foreach ($uniqueSellingPoints as $point): ?>
                        <div class="why-item">
                            <div class="why-icon">
                                <?php if (!empty($point['icon_url'])): ?>
                                    <img src="<?php echo htmlspecialchars((string) $point['icon_url']); ?>" alt="<?php echo htmlspecialchars((string) $point['title']); ?>">
                                <?php else: ?>
                                    <i class="fa-regular fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="why-text">
                                <h4><?php echo htmlspecialchars((string) $point['title']); ?></h4>
                                <p><?php echo htmlspecialchars((string) ($point['subtitle'] ?? '')); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include './footer.php'; ?>

    <script src="./assets/js/product-details.js"></script>
</body>

</html>
