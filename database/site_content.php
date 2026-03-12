<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/catalog.php';

function site_content_ensure_table(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $pdo = get_database_connection();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS cms_content_blocks (
            block_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            page VARCHAR(100) NOT NULL,
            block_key VARCHAR(100) NOT NULL DEFAULT "main",
            content_json LONGTEXT NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (block_id),
            UNIQUE KEY uq_cms_content_page_block (page, block_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );

    $ensured = true;
}

function site_content_fetch_raw(string $page, string $blockKey = 'main'): ?array
{
    site_content_ensure_table();

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT content_json
         FROM cms_content_blocks
         WHERE page = :page
           AND block_key = :block_key
           AND active = 1
         LIMIT 1'
    );
    $statement->execute([
        ':page' => $page,
        ':block_key' => $blockKey,
    ]);

    $json = $statement->fetchColumn();
    if (!is_string($json) || trim($json) === '') {
        return null;
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : null;
}

function site_content_save_raw(string $page, array $content, string $blockKey = 'main'): void
{
    site_content_ensure_table();

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'INSERT INTO cms_content_blocks (page, block_key, content_json, active)
         VALUES (:page, :block_key, :content_json, 1)
         ON DUPLICATE KEY UPDATE
            content_json = VALUES(content_json),
            active = 1'
    );
    $statement->execute([
        ':page' => $page,
        ':block_key' => $blockKey,
        ':content_json' => json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);
}

function site_content_image_url(?string $path, string $fallback = ''): string
{
    $path = trim((string) $path);
    $fallback = trim((string) $fallback);

    if ($path === '') {
        if ($fallback === '') {
            return '';
        }

        return catalog_public_file_url($fallback, $fallback);
    }

    return catalog_public_file_url($path, $fallback !== '' ? $fallback : $path);
}

function site_content_default_home(): array
{
    return [
        'hero_slides' => [
            [
                'image' => '/storage/uploads/contents/hero-img.png',
                'title' => 'Vintage Canon AE-1 Program',
                'title_color' => '#2f2419',
                'subtitle' => '700,000 MMK',
                'subtitle_color' => '#2f2419',
                'button1_text' => 'ADD TO CART',
                'button1_url' => '',
                'button1_background_color' => '#6b5241',
                'button1_text_color' => '#ffffff',
                'button2_text' => 'VIEW MORE',
                'button2_url' => '/products.php',
                'button2_background_color' => '#ffffff',
                'button2_text_color' => '#6b5241',
            ],
            [
                'image' => '/storage/uploads/contents/hero-img-2.png',
                'title' => 'Canon EOS R6 Mark II',
                'title_color' => '#1f1f1f',
                'subtitle' => '2,500,000 MMK',
                'subtitle_color' => '#1f1f1f',
                'button1_text' => 'SHOP NOW',
                'button1_url' => '/products.php?brand=Canon',
                'button1_background_color' => '#6b5241',
                'button1_text_color' => '#ffffff',
                'button2_text' => 'VIEW MORE',
                'button2_url' => '/products.php?brand=Canon',
                'button2_background_color' => '#ffffff',
                'button2_text_color' => '#6b5241',
            ],
            [
                'image' => '/storage/uploads/contents/hero-img-3.png',
                'title' => 'Pro Creator Kits',
                'title_color' => '#1f1f1f',
                'subtitle' => 'From 1,500,000 MMK',
                'subtitle_color' => '#1f1f1f',
                'button1_text' => '',
                'button1_url' => '',
                'button1_background_color' => '#6b5241',
                'button1_text_color' => '#ffffff',
                'button2_text' => 'VIEW MORE',
                'button2_url' => '/products.php',
                'button2_background_color' => '#ffffff',
                'button2_text_color' => '#6b5241',
            ],
        ],
        'trust_badges' => [
            [
                'image' => '/assets/images/secure-payment.png',
                'title' => 'Secure Payment',
            ],
            [
                'image' => '/assets/images/fast-delivery.png',
                'title' => 'Fast Delivery',
            ],
            [
                'image' => '/assets/images/warranty-guaranteed.png',
                'title' => 'Warranty Guaranteed',
            ],
            [
                'image' => '/assets/images/excellent-support.png',
                'title' => 'Excellent Support',
            ],
        ],
    ];
}

function site_content_default_footer(): array
{
    return [
        'footer_image' => '/storage/uploads/contents/logo.png',
        'address' => 'No. 112, 52nd Street, Middle Block, Pazundaung Township, Yangon 11171',
        'phone' => '09-251562642, 09-424574187',
        'email' => 'zyppcamerahouse2023@gmail.com',
        'facebook_url' => '',
        'tiktok_url' => '',
        'telegram_url' => '',
        'instagram_url' => '',
    ];
}

function site_content_default_about(): array
{
    return [
        'hero_title' => 'About ZYPP Camera House',
        'hero_body' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod.',
        'story_sections' => [
            [
                'image' => '/storage/uploads/contents/hero-img.png',
                'body' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet consectetur adipisicing elit.',
                'reverse' => false,
            ],
            [
                'image' => '/storage/uploads/contents/hero-img-2.png',
                'body' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet.',
                'reverse' => true,
            ],
            [
                'image' => '/storage/uploads/contents/hero-img-3.png',
                'body' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet.',
                'reverse' => false,
            ],
        ],
        'vision_lines' => [
            'Lorem ipsum dolor sit amet,',
            'consectetur adipisicing elit,',
            'sed do eiusmod tempor incididunt ut',
            'labore et dolore magna aliqua.',
            'Lorem ipsum dolor sit',
        ],
        'contact_title' => 'Contact',
        'address' => 'No. 112, 52nd Street, Middle Block, Pazundaung Township, Yangon 11171',
        'phone' => '09-251562642, 09-424574187',
        'email' => 'zyppcamerahouse2023@gmail.com',
        'facebook_url' => '',
        'tiktok_url' => '',
        'telegram_url' => '',
        'instagram_url' => '',
        'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3820.026510724878!2d96.1722099!3d16.775356599999995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30c1ed33a6c6f30d%3A0x256ab0e5a54a67ac!2sZYPP%20Camera%20House!5e0!3m2!1sen!2snl!4v1772969831783!5m2!1sen!2snl',
    ];
}

function site_content_default_delivery_policy(): array
{
    return [
        'title' => 'Delivery Information',
        'image' => '/storage/uploads/contents/delivery-info-img.jpg',
        'policies' => [
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.',
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.',
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.',
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.',
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.',
        ],
    ];
}

function site_content_default_payment_information(): array
{
    return [
        'page_title' => 'Our Available Payment Options',
        'sections' => [
            [
                'key' => 'bank_transfers',
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
                'key' => 'mobile_wallets',
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
                'key' => 'cash_on_delivery',
                'title' => 'Cash on Delivery',
                'logos' => [],
                'notes' => [
                    'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore',
                    'Lorem ipsum dolor sit amet, consectetur',
                ],
            ],
        ],
    ];
}

function site_content_default_reservation_policy(): array
{
    return [
        'title' => 'Reservation Policy',
        'policies' => [
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore',
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore',
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore',
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore',
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore',
        ],
    ];
}

function site_content_default_warranty_faq(): array
{
    return [
        'hero_title' => 'Warranty & FAQs',
        'hero_subtitle' => 'Please select the category you want to know.',
        'categories' => [
            [
                'key' => 'camera',
                'label' => 'Cameras',
                'image' => '/storage/uploads/categories/camera.png',
                'policy' => [
                    'Camera warranty covers manufacturing defects for 12 months.',
                    'Warranty is valid with original receipt and warranty card.',
                    'Physical damage or water damage is not covered.',
                    'Free inspection is available within the first 7 days.',
                    'Repairs are subject to parts availability.',
                ],
                'faqs' => [
                    ['q' => 'Question 1', 'a' => 'Camera warranty covers manufacturing defects only.'],
                    ['q' => 'Question 2', 'a' => 'Bring the receipt and warranty card for service.'],
                    ['q' => 'Question 3', 'a' => 'Accidental damage is excluded from coverage.'],
                    ['q' => 'Question 4', 'a' => 'Inspection is free within 7 days of purchase.'],
                    ['q' => 'Question 5', 'a' => 'Repair time depends on parts availability.'],
                ],
            ],
            [
                'key' => 'lens',
                'label' => 'Lenses',
                'image' => '/storage/uploads/categories/lens.png',
                'policy' => [
                    'Lens warranty covers optical and mechanical defects for 12 months.',
                    'Warranty excludes scratches on glass or physical damage.',
                    'Calibration is covered within the first 30 days.',
                    'Service requires proof of purchase.',
                    'Third-party modifications void warranty.',
                ],
                'faqs' => [
                    ['q' => 'Question 1', 'a' => 'Optical defects are covered under warranty.'],
                    ['q' => 'Question 2', 'a' => 'Scratches are not covered.'],
                    ['q' => 'Question 3', 'a' => 'Calibration is free in the first 30 days.'],
                    ['q' => 'Question 4', 'a' => 'Proof of purchase is required.'],
                    ['q' => 'Question 5', 'a' => 'Modifications void warranty.'],
                ],
            ],
            [
                'key' => 'action-camera',
                'label' => 'Action Cameras',
                'image' => '/storage/uploads/categories/action_camera.png',
                'policy' => [
                    'Action camera warranty covers manufacturing defects for 12 months.',
                    'Warranty does not cover wear on rubber feet.',
                    'Locks and clamps are covered for defects.',
                    'Service requires original receipt.',
                    'Warranty excludes misuse or overload.',
                ],
                'faqs' => [
                    ['q' => 'Question 1', 'a' => 'Action camera warranty is 12 months.'],
                    ['q' => 'Question 2', 'a' => 'Rubber feet wear is not covered.'],
                    ['q' => 'Question 3', 'a' => 'Clamp defects are covered.'],
                    ['q' => 'Question 4', 'a' => 'Receipt required for service.'],
                    ['q' => 'Question 5', 'a' => 'Overload damage is excluded.'],
                ],
            ],
            [
                'key' => 'gimbal',
                'label' => 'Gimbals',
                'image' => '/storage/uploads/categories/gimbal.png',
                'policy' => [
                    'Gimbal warranty covers defects for 12 months.',
                    'Batteries are considered consumables and not covered.',
                    'Power adapter defects are covered.',
                    'Warranty requires proof of purchase.',
                    'Unauthorized repairs void warranty.',
                ],
                'faqs' => [
                    ['q' => 'Question 1', 'a' => 'Gimbal warranty is 12 months.'],
                    ['q' => 'Question 2', 'a' => 'Batteries are not covered.'],
                    ['q' => 'Question 3', 'a' => 'Power adapters are covered.'],
                    ['q' => 'Question 4', 'a' => 'Proof of purchase required.'],
                    ['q' => 'Question 5', 'a' => 'Unauthorized repairs void warranty.'],
                ],
            ],
            [
                'key' => 'microphone',
                'label' => 'Microphones',
                'image' => '/storage/uploads/categories/microphone.png',
                'policy' => [
                    'Microphone warranty covers defects for 12 months.',
                    'Batteries are considered consumables and not covered.',
                    'Power adapter defects are covered.',
                    'Warranty requires proof of purchase.',
                    'Unauthorized repairs void warranty.',
                ],
                'faqs' => [
                    ['q' => 'Question 1', 'a' => 'Microphone warranty is 12 months.'],
                    ['q' => 'Question 2', 'a' => 'Batteries are not covered.'],
                    ['q' => 'Question 3', 'a' => 'Power adapters are covered.'],
                    ['q' => 'Question 4', 'a' => 'Proof of purchase required.'],
                    ['q' => 'Question 5', 'a' => 'Unauthorized repairs void warranty.'],
                ],
            ],
        ],
    ];
}

function site_content_fetch_warranty_category_records(): array
{
    $categories = catalog_fetch_category_options();
    $records = [];

    foreach ($categories as $category) {
        $categoryId = (int) ($category['category_id'] ?? 0);
        if ($categoryId <= 0) {
            continue;
        }

        $records[] = [
            'category_id' => $categoryId,
            'key' => 'category-' . $categoryId,
            'label' => (string) ($category['name'] ?? ''),
            'image' => (string) ($category['category_img'] ?? ''),
        ];
    }

    return $records;
}

function site_content_warranty_default_category_map(): array
{
    $defaults = site_content_default_warranty_faq();
    $map = [];

    foreach ((array) ($defaults['categories'] ?? []) as $category) {
        $label = strtolower(trim((string) ($category['label'] ?? '')));
        if ($label !== '') {
            $map[$label] = [
                'policy' => (array) ($category['policy'] ?? []),
                'faqs' => (array) ($category['faqs'] ?? []),
            ];
        }
    }

    return $map;
}

function site_content_merge_warranty_categories(?array $storedContent): array
{
    $storedCategories = (array) (($storedContent['categories'] ?? []) ?: []);
    $storedByCategoryId = [];
    $storedByKey = [];
    $storedByLabel = [];

    foreach ($storedCategories as $category) {
        $categoryId = (int) ($category['category_id'] ?? 0);
        $key = trim((string) ($category['key'] ?? ''));
        $label = strtolower(trim((string) ($category['label'] ?? '')));

        if ($categoryId > 0) {
            $storedByCategoryId[$categoryId] = $category;
        }
        if ($key !== '') {
            $storedByKey[$key] = $category;
        }
        if ($label !== '') {
            $storedByLabel[$label] = $category;
        }
    }

    $defaultMap = site_content_warranty_default_category_map();
    $merged = [];

    foreach (site_content_fetch_warranty_category_records() as $categoryRecord) {
        $categoryId = (int) $categoryRecord['category_id'];
        $key = (string) $categoryRecord['key'];
        $label = strtolower(trim((string) $categoryRecord['label']));
        $storedCategory = $storedByCategoryId[$categoryId]
            ?? $storedByKey[$key]
            ?? $storedByLabel[$label]
            ?? null;
        $defaultCategory = $defaultMap[$label] ?? ['policy' => [], 'faqs' => []];

        $merged[] = [
            'category_id' => $categoryId,
            'key' => $key,
            'label' => (string) $categoryRecord['label'],
            'image' => (string) $categoryRecord['image'],
            'policy' => (array) ($storedCategory['policy'] ?? $defaultCategory['policy']),
            'faqs' => (array) ($storedCategory['faqs'] ?? $defaultCategory['faqs']),
        ];
    }

    return $merged;
}

function site_content_get_home(): array
{
    $content = site_content_fetch_raw('home');
    $defaultContent = site_content_default_home();
    if (!is_array($content)) {
        return $defaultContent;
    }

    $slides = [];
    foreach (($defaultContent['hero_slides'] ?? []) as $index => $defaultSlide) {
        $storedSlide = (array) ($content['hero_slides'][$index] ?? []);
        $slides[] = array_merge($defaultSlide, $storedSlide);
    }

    $badges = [];
    foreach (($defaultContent['trust_badges'] ?? []) as $index => $defaultBadge) {
        $storedBadge = (array) ($content['trust_badges'][$index] ?? []);
        $badges[] = array_merge($defaultBadge, $storedBadge);
    }

    return [
        'hero_slides' => $slides,
        'trust_badges' => $badges,
    ];
}

function site_content_get_footer(): array
{
    $content = site_content_fetch_raw('footer');
    return is_array($content) ? $content : site_content_default_footer();
}

function site_content_get_about(): array
{
    $content = site_content_fetch_raw('about');
    return is_array($content) ? $content : site_content_default_about();
}

function site_content_get_delivery_policy(): array
{
    $content = site_content_fetch_raw('support_delivery_policy');
    return is_array($content) ? $content : site_content_default_delivery_policy();
}

function site_content_get_payment_information(): array
{
    $content = site_content_fetch_raw('support_payment_information');
    return is_array($content) ? $content : site_content_default_payment_information();
}

function site_content_get_reservation_policy(): array
{
    $content = site_content_fetch_raw('support_reservation_policy');
    return is_array($content) ? $content : site_content_default_reservation_policy();
}

function site_content_get_warranty_faq(): array
{
    $content = site_content_fetch_raw('support_warranty_faq');
    $defaultContent = site_content_default_warranty_faq();

    return [
        'hero_title' => (string) (($content['hero_title'] ?? $defaultContent['hero_title'] ?? 'Warranty & FAQs')),
        'hero_subtitle' => (string) (($content['hero_subtitle'] ?? $defaultContent['hero_subtitle'] ?? '')),
        'categories' => site_content_merge_warranty_categories(is_array($content) ? $content : null),
    ];
}
