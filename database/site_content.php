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

function site_content_default_home_hero_slide(array $overrides = []): array
{
    return array_merge([
        'image' => '',
        'title' => '',
        'title_color' => '#2f2419',
        'subtitle' => '',
        'subtitle_color' => '#2f2419',
        'button1_text' => '',
        'button1_action' => 'link',
        'button1_url' => '',
        'button1_product_id' => 0,
        'button1_background_color' => '#6b5241',
        'button1_text_color' => '#ffffff',
        'button2_text' => '',
        'button2_action' => 'link',
        'button2_url' => '',
        'button2_product_id' => 0,
        'button2_background_color' => '#ffffff',
        'button2_text_color' => '#6b5241',
    ], $overrides);
}

function site_content_default_home(): array
{
    return [
        'hero_slides' => [
            site_content_default_home_hero_slide([
                'image' => '/storage/uploads/contents/hero-img.png',
                'title' => 'Vintage Canon AE-1 Program',
                'subtitle' => '700,000 MMK',
                'button1_text' => 'ADD TO CART',
                'button1_action' => 'link',
                'button2_text' => 'VIEW MORE',
                'button2_action' => 'link',
                'button2_url' => '/products.php',
            ]),
            site_content_default_home_hero_slide([
                'image' => '/storage/uploads/contents/hero-img-2.png',
                'title' => 'Canon EOS R6 Mark II',
                'title_color' => '#1f1f1f',
                'subtitle' => '2,500,000 MMK',
                'subtitle_color' => '#1f1f1f',
                'button1_text' => 'SHOP NOW',
                'button1_action' => 'link',
                'button1_url' => '/products.php?brand=Canon',
                'button2_text' => 'VIEW MORE',
                'button2_action' => 'link',
                'button2_url' => '/products.php?brand=Canon',
            ]),
            site_content_default_home_hero_slide([
                'image' => '/storage/uploads/contents/hero-img-3.png',
                'title' => 'Pro Creator Kits',
                'title_color' => '#1f1f1f',
                'subtitle' => 'From 1,500,000 MMK',
                'subtitle_color' => '#1f1f1f',
                'button1_text' => '',
                'button1_action' => 'link',
                'button2_text' => 'VIEW MORE',
                'button2_action' => 'link',
                'button2_url' => '/products.php',
            ]),
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
        'video_sections' => [
            'home' => [
                'title' => 'Unboxing & Influencers Videos',
                'description' => 'Experience our products through influencer reviews & unboxings.',
            ],
            'product_details' => [
                'title' => 'Unboxing & Influencers Videos',
                'description' => 'Experience our products through influencer reviews and unboxings.',
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

function site_content_default_unboxing_influencers(): array
{
    return [
        'unboxing_tab_title' => 'Unboxing Videos',
        'unboxing_banner_text' => 'Unbox the hype - watch creators try our products!',
        'influencer_tab_title' => 'Influencer Reviews',
        'influencer_banner_text' => 'Real reviews. Real unboxings. Real results.',
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

function site_content_default_delivery_locations(): array
{
    return [
        'states' => [
            [
                'name' => 'Yangon',
                'cities' => [
                    [
                        'name' => 'Yangon',
                        'townships' => ['Hlaing', 'Mayangone', 'Bahan', 'Lanmadaw'],
                    ],
                ],
            ],
            [
                'name' => 'Mandalay',
                'cities' => [
                    [
                        'name' => 'Mandalay',
                        'townships' => ['Chanayethazan', 'Aungmyaythazan'],
                    ],
                ],
            ],
            [
                'name' => 'Nay Pyi Taw',
                'cities' => [
                    [
                        'name' => 'Nay Pyi Taw',
                        'townships' => ['Zabuthiri', 'Ottarathiri'],
                    ],
                ],
            ],
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
        'methods' => [
            [
                'key' => 'cod',
                'label' => 'Cash on Delivery',
                'section_key' => 'cash_on_delivery',
                'is_active' => true,
                'requires_payment_proof' => false,
                'logo_image' => '',
                'account_name' => '',
                'account_number' => '',
                'phone' => '',
                'qr_image' => '',
                'instructions' => [
                    'Pay cash when your order arrives.',
                    'Our team will confirm your order before delivery.',
                ],
            ],
            [
                'key' => 'kbzpay',
                'label' => 'KBZPay',
                'section_key' => 'mobile_wallets',
                'is_active' => true,
                'requires_payment_proof' => true,
                'logo_image' => '/storage/uploads/contents/payment-information/kbzpay.png',
                'account_name' => 'Shine Htet',
                'account_number' => '09123456789',
                'phone' => '09123456789',
                'qr_image' => '/storage/uploads/contents/logo.png',
                'instructions' => [
                    'Please transfer the exact amount and upload your payment proof.',
                    'Our team will verify the payment before confirming your order.',
                ],
            ],
            [
                'key' => 'wave',
                'label' => 'Wave Pay',
                'section_key' => 'mobile_wallets',
                'is_active' => true,
                'requires_payment_proof' => true,
                'logo_image' => '/storage/uploads/contents/payment-information/wavepay.png',
                'account_name' => 'Shine Htet',
                'account_number' => '09123456789',
                'phone' => '09123456789',
                'qr_image' => '/storage/uploads/contents/logo.png',
                'instructions' => [
                    'Please transfer the exact amount and upload your payment proof.',
                    'Our team will verify the payment before confirming your order.',
                ],
            ],
            [
                'key' => 'aya',
                'label' => 'AYA Pay',
                'section_key' => 'mobile_wallets',
                'is_active' => true,
                'requires_payment_proof' => true,
                'logo_image' => '/storage/uploads/contents/payment-information/ayapay.png',
                'account_name' => 'Shine Htet',
                'account_number' => '09123456789',
                'phone' => '09123456789',
                'qr_image' => '/storage/uploads/contents/logo.png',
                'instructions' => [
                    'Please transfer the exact amount and upload your payment proof.',
                    'Our team will verify the payment before confirming your order.',
                ],
            ],
        ],
    ];
}

function site_content_payment_method_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
    return trim($value, '_');
}

function site_content_normalize_payment_information(array $content): array
{
    $defaultContent = site_content_default_payment_information();
    $pageTitle = trim((string) ($content['page_title'] ?? $defaultContent['page_title'] ?? ''));
    if ($pageTitle === '') {
        $pageTitle = (string) ($defaultContent['page_title'] ?? 'Our Available Payment Options');
    }

    $defaultSections = [];
    foreach ((array) ($defaultContent['sections'] ?? []) as $section) {
        if (!is_array($section)) {
            continue;
        }

        $sectionKey = site_content_payment_method_slug((string) ($section['key'] ?? ''));
        if ($sectionKey === '') {
            continue;
        }

        $defaultSections[$sectionKey] = $section;
    }

    $rawSections = is_array($content['sections'] ?? null) && (array) ($content['sections'] ?? []) !== []
        ? (array) $content['sections']
        : (array) ($defaultContent['sections'] ?? []);
    $sections = [];

    foreach ($rawSections as $sectionIndex => $section) {
        if (!is_array($section)) {
            continue;
        }

        $sectionKey = site_content_payment_method_slug((string) ($section['key'] ?? ''));
        if ($sectionKey === '') {
            $sectionKey = site_content_payment_method_slug((string) ($section['title'] ?? ''));
        }
        if ($sectionKey === '') {
            $sectionKey = 'section_' . $sectionIndex;
        }

        $fallbackSection = $defaultSections[$sectionKey] ?? [];
        $logos = [];
        foreach ((array) ($section['logos'] ?? $fallbackSection['logos'] ?? []) as $logo) {
            if (!is_array($logo)) {
                continue;
            }

            $label = trim((string) ($logo['label'] ?? ''));
            $image = trim((string) ($logo['image'] ?? ''));
            if ($label === '' && $image === '') {
                continue;
            }

            $logos[] = [
                'label' => $label,
                'image' => $image,
            ];
        }

        $notes = [];
        foreach ((array) ($section['notes'] ?? $fallbackSection['notes'] ?? []) as $note) {
            $note = trim((string) $note);
            if ($note !== '') {
                $notes[] = $note;
            }
        }

        $sections[] = [
            'key' => $sectionKey,
            'title' => trim((string) ($section['title'] ?? $fallbackSection['title'] ?? '')),
            'logos' => $logos,
            'notes' => $notes,
        ];
    }

    if ($sections === []) {
        $sections = (array) ($defaultContent['sections'] ?? []);
    }

    $validSectionKeys = array_values(array_filter(array_map(
        static fn (array $section): string => trim((string) ($section['key'] ?? '')),
        $sections
    )));
    $defaultMethods = [];
    foreach ((array) ($defaultContent['methods'] ?? []) as $method) {
        if (!is_array($method)) {
            continue;
        }

        $methodKey = site_content_payment_method_slug((string) ($method['key'] ?? ''));
        if ($methodKey === '') {
            continue;
        }

        $defaultMethods[$methodKey] = $method;
    }

    $rawMethods = is_array($content['methods'] ?? null) && (array) ($content['methods'] ?? []) !== []
        ? (array) $content['methods']
        : (array) ($defaultContent['methods'] ?? []);
    $methods = [];

    foreach ($rawMethods as $methodIndex => $method) {
        if (!is_array($method)) {
            continue;
        }

        $methodKey = site_content_payment_method_slug((string) ($method['key'] ?? ''));
        if ($methodKey === '') {
            $methodKey = site_content_payment_method_slug((string) ($method['label'] ?? ''));
        }
        if ($methodKey === '') {
            $methodKey = 'payment_method_' . $methodIndex;
        }

        $fallbackMethod = $defaultMethods[$methodKey] ?? [];
        $label = trim((string) ($method['label'] ?? $fallbackMethod['label'] ?? ''));
        if ($label === '') {
            continue;
        }

        $sectionKey = site_content_payment_method_slug((string) ($method['section_key'] ?? $fallbackMethod['section_key'] ?? ''));
        if (!in_array($sectionKey, $validSectionKeys, true)) {
            $sectionKey = $validSectionKeys[0] ?? '';
        }

        $instructions = [];
        foreach ((array) ($method['instructions'] ?? $fallbackMethod['instructions'] ?? []) as $instruction) {
            $instruction = trim((string) $instruction);
            if ($instruction !== '') {
                $instructions[] = $instruction;
            }
        }

        $methods[] = [
            'key' => $methodKey,
            'label' => $label,
            'section_key' => $sectionKey,
            'is_active' => array_key_exists('is_active', $method)
                ? !empty($method['is_active'])
                : !empty($fallbackMethod['is_active']),
            'requires_payment_proof' => array_key_exists('requires_payment_proof', $method)
                ? !empty($method['requires_payment_proof'])
                : !empty($fallbackMethod['requires_payment_proof']),
            'logo_image' => trim((string) ($method['logo_image'] ?? $fallbackMethod['logo_image'] ?? '')),
            'account_name' => trim((string) ($method['account_name'] ?? $fallbackMethod['account_name'] ?? '')),
            'account_number' => trim((string) ($method['account_number'] ?? $fallbackMethod['account_number'] ?? '')),
            'phone' => trim((string) ($method['phone'] ?? $fallbackMethod['phone'] ?? '')),
            'qr_image' => trim((string) ($method['qr_image'] ?? $fallbackMethod['qr_image'] ?? '')),
            'instructions' => $instructions,
        ];
    }

    if ($methods === []) {
        $methods = (array) ($defaultContent['methods'] ?? []);
    }

    return [
        'page_title' => $pageTitle,
        'sections' => $sections,
        'methods' => $methods,
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
        'general_faqs' => [
            ['q' => 'Do I need the original receipt for warranty service?', 'a' => 'Yes. Please bring the original receipt or valid proof of purchase when requesting warranty service.'],
            ['q' => 'Does warranty cover accidental or water damage?', 'a' => 'No. Warranty only covers eligible manufacturing defects and does not cover accidental, impact, or water damage.'],
            ['q' => 'How long does warranty inspection or repair take?', 'a' => 'Service time depends on the product issue and parts availability. Our team will confirm an estimated timeline after inspection.'],
        ],
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
    $storedSlides = array_values(array_filter(
        array_map(static fn ($slide): array => is_array($slide) ? $slide : [], (array) ($content['hero_slides'] ?? [])),
        static fn (array $slide): bool => $slide !== []
    ));
    if ($storedSlides === []) {
        $slides = (array) ($defaultContent['hero_slides'] ?? []);
    } else {
        foreach ($storedSlides as $index => $storedSlide) {
            $defaultSlide = (array) (($defaultContent['hero_slides'][$index] ?? null) ?: site_content_default_home_hero_slide());
            $slides[] = array_merge($defaultSlide, $storedSlide);
        }
    }

    $badges = [];
    foreach (($defaultContent['trust_badges'] ?? []) as $index => $defaultBadge) {
        $storedBadge = (array) ($content['trust_badges'][$index] ?? []);
        $badges[] = array_merge($defaultBadge, $storedBadge);
    }

    $videoSections = [];
    foreach (($defaultContent['video_sections'] ?? []) as $sectionKey => $defaultSection) {
        $storedSection = (array) ($content['video_sections'][$sectionKey] ?? []);
        $videoSections[$sectionKey] = array_merge($defaultSection, $storedSection);
    }

    return [
        'hero_slides' => $slides,
        'trust_badges' => $badges,
        'video_sections' => $videoSections,
    ];
}

function site_content_get_footer(): array
{
    $content = site_content_fetch_raw('footer');
    $defaultContent = site_content_default_footer();
    return is_array($content) ? array_merge($defaultContent, $content) : $defaultContent;
}

function site_content_get_about(): array
{
    $content = site_content_fetch_raw('about');
    return is_array($content) ? $content : site_content_default_about();
}

function site_content_get_unboxing_influencers(): array
{
    $content = site_content_fetch_raw('unboxing_influencers');
    $defaultContent = site_content_default_unboxing_influencers();
    return is_array($content) ? array_merge($defaultContent, $content) : $defaultContent;
}

function site_content_get_delivery_policy(): array
{
    $content = site_content_fetch_raw('support_delivery_policy');
    $defaultContent = site_content_default_delivery_policy();
    return is_array($content) ? array_merge($defaultContent, $content) : $defaultContent;
}

function site_content_normalize_delivery_locations(array $content): array
{
    $states = [];

    foreach ((array) ($content['states'] ?? []) as $state) {
        $stateName = trim((string) ($state['name'] ?? ''));
        if ($stateName === '') {
            continue;
        }

        $cities = [];
        foreach ((array) ($state['cities'] ?? []) as $city) {
            $cityName = trim((string) ($city['name'] ?? ''));
            if ($cityName === '') {
                continue;
            }

            $townships = [];
            foreach ((array) ($city['townships'] ?? []) as $township) {
                $townshipName = trim((string) $township);
                if ($townshipName !== '' && !in_array($townshipName, $townships, true)) {
                    $townships[] = $townshipName;
                }
            }

            $cities[] = [
                'name' => $cityName,
                'townships' => $townships,
            ];
        }

        $states[] = [
            'name' => $stateName,
            'cities' => $cities,
        ];
    }

    return ['states' => $states];
}

function site_content_get_delivery_locations(): array
{
    $content = site_content_fetch_raw('delivery_locations');
    $defaultContent = site_content_default_delivery_locations();
    if (!is_array($content)) {
        return $defaultContent;
    }

    $normalized = site_content_normalize_delivery_locations($content);
    return $normalized['states'] !== [] ? $normalized : $defaultContent;
}

function site_content_get_payment_information(): array
{
    $content = site_content_fetch_raw('support_payment_information');
    if (!is_array($content)) {
        return site_content_default_payment_information();
    }

    return site_content_normalize_payment_information($content);
}

function site_content_get_payment_method_definitions(bool $activeOnly = false): array
{
    $paymentContent = site_content_get_payment_information();
    $methods = [];

    foreach ((array) ($paymentContent['methods'] ?? []) as $method) {
        if (!is_array($method)) {
            continue;
        }

        if ($activeOnly && empty($method['is_active'])) {
            continue;
        }

        $methods[] = $method;
    }

    return $methods;
}

function site_content_find_payment_method(string $identifier, bool $activeOnly = false): ?array
{
    $identifierSlug = site_content_payment_method_slug($identifier);
    if ($identifierSlug === '') {
        return null;
    }

    foreach (site_content_get_payment_method_definitions($activeOnly) as $method) {
        $keySlug = site_content_payment_method_slug((string) ($method['key'] ?? ''));
        $labelSlug = site_content_payment_method_slug((string) ($method['label'] ?? ''));
        if ($identifierSlug === $keySlug || $identifierSlug === $labelSlug) {
            return $method;
        }
    }

    return null;
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
        'general_faqs' => (array) (($content['general_faqs'] ?? $defaultContent['general_faqs'] ?? [])),
        'categories' => site_content_merge_warranty_categories(is_array($content) ? $content : null),
    ];
}
