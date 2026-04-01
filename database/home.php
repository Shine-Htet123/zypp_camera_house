<?php

require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/media.php';
require_once __DIR__ . '/site_content.php';
require_once __DIR__ . '/user/cart.php';
require_once __DIR__ . '/user/auth.php';

function home_default_trust_badges(): array
{
    return [
        [
            'icon_url' => app_path('/assets/images/secure-payment.png'),
            'title' => 'Secure Payment',
        ],
        [
            'icon_url' => app_path('/assets/images/fast-delivery.png'),
            'title' => 'Fast Delivery',
        ],
        [
            'icon_url' => app_path('/assets/images/warranty-guaranteed.png'),
            'title' => 'Warranty Guaranteed',
        ],
        [
            'icon_url' => app_path('/assets/images/excellent-support.png'),
            'title' => 'Excellent Support',
        ],
    ];
}

function home_fetch_trusted_brands(int $limit = 12): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT brand_id, name, logo_file
         FROM brands
         WHERE logo_file IS NOT NULL AND TRIM(logo_file) <> ""
         ORDER BY brand_id ASC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $statement->execute();

    $brands = $statement->fetchAll();
    foreach ($brands as &$brand) {
        $brand['logo_url'] = catalog_public_file_url($brand['logo_file'], '/storage/uploads/contents/logo.png');
        $brand['href'] = app_path('/products.php?brand=' . rawurlencode((string) $brand['name']));
    }

    return $brands;
}

function home_fetch_featured_categories(int $limit = 10): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT category_id, name, category_img, featured
         FROM categories
         WHERE featured = 1
         ORDER BY category_id ASC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $statement->execute();

    $categories = $statement->fetchAll();
    foreach ($categories as &$category) {
        $category['image_url'] = catalog_public_file_url($category['category_img'], '/storage/uploads/products/placeholder-camera.png');
        $category['href'] = app_path('/products.php?category=' . rawurlencode((string) $category['name']));
    }

    return $categories;
}

function home_fetch_unboxing_summary(): array
{
    $homeContent = site_content_get_home();
    $section = (array) ($homeContent['video_sections']['home'] ?? []);

    return [
        'title' => trim((string) ($section['title'] ?? 'Unboxing & Influencers Videos')),
        'description' => trim((string) ($section['description'] ?? 'Experience our products through influencer reviews & unboxings.')),
        'href' => app_path('/unboxing-influencers.php'),
    ];
}

function home_fetch_latest_reviews(int $limit = 4): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT
            r.review_id,
            r.rating,
            r.topic,
            r.comment,
            r.created_at,
            u.name AS user_name
         FROM reviews r
         INNER JOIN users u ON u.id = r.user_id
         ORDER BY r.created_at DESC, r.review_id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function home_submit_review(int $userId, array $input): array
{
    if ($userId <= 0) {
        throw new InvalidArgumentException('Please log in to leave a review.');
    }

    $rating = (int) ($input['rating'] ?? 0);
    $topic = trim((string) ($input['topic'] ?? ''));
    $comment = trim((string) ($input['comment'] ?? ''));

    if ($rating < 1 || $rating > 5) {
        throw new InvalidArgumentException('Please choose a rating between 1 and 5 stars.');
    }

    if ($comment === '') {
        throw new InvalidArgumentException('Please write your review before submitting.');
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'INSERT INTO reviews (user_id, rating, topic, comment)
         VALUES (:user_id, :rating, :topic, :comment)'
    );
    $statement->execute([
        ':user_id' => $userId,
        ':rating' => $rating,
        ':topic' => $topic !== '' ? $topic : null,
        ':comment' => $comment,
    ]);

    $user = auth_user_by_id($userId);

    return [
        'review_id' => (int) $pdo->lastInsertId(),
        'rating' => $rating,
        'topic' => $topic,
        'comment' => $comment,
        'user_name' => (string) ($user['name'] ?? 'Customer'),
        'created_at' => date('Y-m-d H:i:s'),
    ];
}

function home_fetch_trust_badges(int $limit = 8): array
{
    $badges = home_default_trust_badges();
    return array_slice($badges, 0, max(1, $limit));
}

function home_fetch_best_sellers(int $currentUserId = 0, int $limit = 8): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT
            p.product_id,
            p.name,
            p.price,
            p.stock_quantity,
            p.is_featured,
            p.category_id,
            p.brand_id,
            p.sub_category_id,
            b.name AS brand_name,
            (
                SELECT pi.image_file
                FROM product_images pi
                WHERE pi.product_id = p.product_id
                ORDER BY pi.is_primary DESC, pi.image_id ASC
                LIMIT 1
            ) AS image_file,
            COALESCE(SUM(oi.qty), 0) AS sold_qty
         FROM order_items oi
         INNER JOIN orders o
            ON o.id = oi.order_id
           AND o.status IN ("confirmed", "shipped", "delivered")
         INNER JOIN products p ON p.product_id = oi.product_id
         INNER JOIN brands b ON b.brand_id = p.brand_id
         WHERE p.visibility = 1
         GROUP BY
            p.product_id,
            p.name,
            p.price,
            p.stock_quantity,
            p.is_featured,
            p.category_id,
            p.brand_id,
            p.sub_category_id,
            b.name,
            image_file
         ORDER BY sold_qty DESC, p.is_featured DESC, p.product_id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $statement->execute();

    $products = $statement->fetchAll();
    if ($products === []) {
        return [];
    }

    foreach ($products as &$product) {
        $product['image_url'] = catalog_public_file_url($product['image_file']);
        $product['detail_url'] = app_path('/product-details.php?id=' . (int) $product['product_id']);

        $unitPrice = (float) $product['price'];
        $discount = cart_fetch_applicable_discount_for_product($currentUserId, $product);
        $discountAmount = $discount ? (float) ($discount['unit_discount_amount'] ?? 0) : 0.0;
        $finalPrice = max($unitPrice - $discountAmount, 0);

        $product['original_price_raw'] = (int) round($unitPrice);
        $product['discounted_price_raw'] = (int) round($finalPrice);
        $product['save_amount_raw'] = (int) round($discountAmount);
        $product['show_discount'] = $discountAmount > 0;
        $product['add_to_cart_quantity'] = 1;
    }

    return $products;
}
