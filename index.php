<?php
require_once __DIR__ . '/app/services/customer_auth.php';
require_once __DIR__ . '/database/home.php';
require_once __DIR__ . '/database/site_content.php';

$currentCustomer = customer_auth_current_user();
$currentCustomerId = (int) ($currentCustomer['id'] ?? 0);
$homeContent = site_content_get_home();
$heroSlides = array_values(array_filter((array) ($homeContent['hero_slides'] ?? []), static fn (array $slide): bool => trim((string) ($slide['image'] ?? '')) !== ''));
$trustedBrands = home_fetch_trusted_brands();
$videoSummary = home_fetch_unboxing_summary();
$featuredCategories = home_fetch_featured_categories();
$bestSellers = home_fetch_best_sellers($currentCustomerId);
$latestReviews = home_fetch_latest_reviews();
$trustBadges = (array) ($homeContent['trust_badges'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <!-- css links -->
    <link rel="stylesheet" href="./assets/css/home.css">
</head>
<body class="home-page">
    <!--navbar & sidebar -->
    <?php include 'navbar.php'; ?>

    <!-- home page -->

    <!--hero section -->
    <section class="hero-section">
        <div class="hero-container" style="width: <?php echo max(1, count($heroSlides)) * 100; ?>vw;">
            <?php foreach ($heroSlides as $slide): ?>
                <div class="hero-wrapper">
                    <div class="hero-elements">
                        <?php if (trim((string) ($slide['subtitle'] ?? '')) !== ''): ?>
                            <h4 class="hero-subtitle"><?php echo htmlspecialchars((string) $slide['subtitle']); ?></h4>
                        <?php endif; ?>
                        <?php if (trim((string) ($slide['title'] ?? '')) !== ''): ?>
                            <h1 class="hero-title"><?php echo htmlspecialchars((string) $slide['title']); ?></h1>
                        <?php endif; ?>
                        <?php if (trim((string) ($slide['button1_text'] ?? '')) !== '' || trim((string) ($slide['button2_text'] ?? '')) !== ''): ?>
                            <div class="hero-btn">
                                <?php if (trim((string) ($slide['button1_text'] ?? '')) !== ''): ?>
                                    <a class="hero-action btn1" href="<?php echo htmlspecialchars(app_path((string) (($slide['button1_url'] ?? '') !== '' ? $slide['button1_url'] : '/products.php'))); ?>">
                                        <?php echo htmlspecialchars((string) $slide['button1_text']); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (trim((string) ($slide['button2_text'] ?? '')) !== ''): ?>
                                    <a class="hero-action btn2" href="<?php echo htmlspecialchars(app_path((string) (($slide['button2_url'] ?? '') !== '' ? $slide['button2_url'] : '/products.php'))); ?>">
                                        <?php echo htmlspecialchars((string) $slide['button2_text']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <img class="hero-img" src="<?php echo htmlspecialchars(site_content_image_url((string) ($slide['image'] ?? ''), '')); ?>" alt="Hero Image">
                </div>
            <?php endforeach; ?>
        </div>
        <div class="bubble-container">
            <?php foreach ($heroSlides as $index => $slide): ?>
                <div class="bubble<?php echo $index === 0 ? ' active' : ''; ?>"></div>
            <?php endforeach; ?>
        </div>
    </section>

    <!--trusted brands section -->
    <section class="trusted-brands">
        <div class="trusted-brands-title carousel-header">
            <h3 class="title">Trusted Brands</h3>
            <div class="carousel-controls">
                <button type="button" class="carousel-btn" data-carousel="brands" data-direction="prev" aria-label="Previous brand">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button type="button" class="carousel-btn" data-carousel="brands" data-direction="next" aria-label="Next brand">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="brand-logos carousel-track" data-carousel-track="brands">
            <?php if ($trustedBrands === []): ?>
                <div class="home-empty-state">No trusted brands available yet.</div>
            <?php else: ?>
                <?php foreach ($trustedBrands as $brand): ?>
                    <a class="logo" href="<?= htmlspecialchars((string) $brand['href']) ?>">
                        <img
                            src="<?= htmlspecialchars((string) $brand['logo_url']) ?>"
                            alt="<?= htmlspecialchars((string) $brand['name']) ?>"
                        >
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!--Unboxing & Influencers Video Link -->
    <section class="video-link-container">
        <div class="video-link-box">
            <div class="video-link-logo">
                <i class="fa-solid fa-photo-film"></i>
            </div>
            <div class="video-link-text">
                <h4><?= htmlspecialchars((string) $videoSummary['title']) ?></h4>
                <p><?= htmlspecialchars((string) $videoSummary['description']) ?></p>
            </div>
            <div class="video-link-btn">
                <a href="<?= htmlspecialchars((string) $videoSummary['href']) ?>">Discover More</a>
            </div>
        </div>
    </section>

    <!--Featured Categories -->
    <section class="featured-categories-container">
        <div class="carousel-header">
            <h2>Featured Categories</h2>
            <div class="carousel-controls">
                <button type="button" class="carousel-btn" data-carousel="categories" data-direction="prev" aria-label="Previous category">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button type="button" class="carousel-btn" data-carousel="categories" data-direction="next" aria-label="Next category">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="featured-categories-box carousel-track" data-carousel-track="categories">
            <?php if ($featuredCategories === []): ?>
                <div class="home-empty-state">No categories available yet.</div>
            <?php else: ?>
                <?php foreach ($featuredCategories as $category): ?>
                    <a href="<?= htmlspecialchars((string) $category['href']) ?>" class="featured-category">
                        <div class="category-img">
                            <img src="<?= htmlspecialchars((string) $category['image_url']) ?>" alt="<?= htmlspecialchars((string) $category['name']) ?>">
                        </div>
                        <div class="category-text">
                            <h3><?= htmlspecialchars((string) $category['name']) ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!--Best Sellers -->
    <section class="best-sellers-container">
        <div class="chevrons">
            <i class="fa-solid fa-chevron-left"></i>
            <i class="fa-solid fa-chevron-right"></i>
        </div>
        <div class="best-sellers-box">
            <h2>Best Sellers</h2>
            <div class="best-seller-slider">
                <?php if ($bestSellers === []): ?>
                    <div class="home-empty-state">No best sellers available yet.</div>
                <?php else: ?>
                    <?php foreach ($bestSellers as $product): ?>
                        <a class="product-card" href="<?= htmlspecialchars((string) $product['detail_url']) ?>">
                            <div class="product-img-box">
                                <img src="<?= htmlspecialchars((string) $product['image_url']) ?>" alt="<?= htmlspecialchars((string) $product['name']) ?>">
                            </div>
                            <div class="product-description">
                                <h3 class="product-name"><?= htmlspecialchars((string) $product['name']) ?></h3>
                                <h4 class="brand-name"><?= htmlspecialchars((string) $product['brand_name']) ?></h4>
                                <div class="product-price">
                                    <?php if (!empty($product['show_discount'])): ?>
                                        <h4 class="original-price"><span class="price-format"><?= (int) $product['original_price_raw'] ?></span> MMK</h4>
                                    <?php endif; ?>
                                    <h4 class="discounted-price"><span class="price-format"><?= (int) $product['discounted_price_raw'] ?></span> MMK</h4>
                                </div>
                                <?php if (!empty($product['show_discount'])): ?>
                                    <div class="discount-box">
                                        <h5>Save <span class="price-format"><?= (int) $product['save_amount_raw'] ?></span> MMK</h5>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="<?= htmlspecialchars(app_path('/products.php')) ?>" class="see-more">See More</a>
        </div>
    </section>

    <!--Review Section-->
    <section class="review-container">
        <h2>What our customers say</h2>
        <div class="review-list">
            <?php if ($latestReviews === []): ?>
                <div class="home-empty-state home-empty-state-wide">No reviews yet. Be the first to leave one.</div>
            <?php else: ?>
                <?php foreach ($latestReviews as $review): ?>
                    <div class="review-card">
                        <div class="user-info">
                            <div class="user-profile">
                                <i class="fa-regular fa-user"></i>
                            </div>
                            <div class="user-name-rating">
                                <span class="user-name"><?= htmlspecialchars((string) $review['user_name']) ?></span>
                                <div class="rating">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <i class="<?= $star <= (int) $review['rating'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <h3 class="review-topic"><?= htmlspecialchars(trim((string) ($review['topic'] ?? '')) !== '' ? (string) $review['topic'] : 'Customer Review') ?></h3>
                        <p class="review-text"><?= nl2br(htmlspecialchars((string) $review['comment'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button class="leave-review" id="leave-review-btn">Leave a Review</button>

        <form class="review-form" id="review-form" action="/auth/review_submit.php" method="post">
            <div class="rating-input">
                <span>Your Rating:</span>
                <div class="stars" data-rating="0">
                    <i class="fa-regular fa-star" data-value="1" aria-label="1 star"></i>
                    <i class="fa-regular fa-star" data-value="2" aria-label="2 stars"></i>
                    <i class="fa-regular fa-star" data-value="3" aria-label="3 stars"></i>
                    <i class="fa-regular fa-star" data-value="4" aria-label="4 stars"></i>
                    <i class="fa-regular fa-star" data-value="5" aria-label="5 stars"></i>
                </div>
                <input type="hidden" name="rating" id="rating-value" value="0">
            </div>
            <div class="topic">
                <label for="review-topic">Review Topic:</label>
                <input type="text" name="topic" id="review-topic" class="topic-input">
            </div>
            <div class="review">
                <label for="review-text">Tell us what you feel</label>
                <textarea name="comment" id="review-text" class="topic-input" rows="4"></textarea>
            </div>
            <button type="submit" class="submit-review">Submit</button>
        </form>
    </section>

    <!--Trust Badges-->
    <section class="trust-badges-container">
        <h2>Trust Badges</h2>
        <div class="trust-badges-box">
            <?php foreach ($trustBadges as $badge): ?>
                <div class="badge">
                    <div class="badge-icon">
                        <img src="<?= htmlspecialchars(site_content_image_url((string) ($badge['image'] ?? ''), '')) ?>" alt="<?= htmlspecialchars((string) ($badge['title'] ?? '')) ?>">
                    </div>
                    <h4><?= htmlspecialchars((string) ($badge['title'] ?? '')) ?></h4>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!--footer -->
    <?php include './footer.php'; ?>

    <!--js links -->
    <script src="./assets/js/home.js"></script>
</body>
</html>
