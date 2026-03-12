<?php

require_once __DIR__ . '/../site_content.php';
require_once __DIR__ . '/catalog_management.php';

function admin_content_management_flash_set(string $type, string $message): void
{
    $_SESSION['admin_content_management_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function admin_content_management_flash_consume(): ?array
{
    $flash = $_SESSION['admin_content_management_flash'] ?? null;
    unset($_SESSION['admin_content_management_flash']);
    return is_array($flash) ? $flash : null;
}

function admin_content_management_lines(string $value): array
{
    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
    $result = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $result[] = $line;
        }
    }
    return $result;
}

function admin_content_management_faq_lines(string $value): array
{
    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
    $items = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line, 2));
        $question = $parts[0] ?? '';
        $answer = $parts[1] ?? '';
        if ($question === '' || $answer === '') {
            continue;
        }

        $items[] = [
            'q' => $question,
            'a' => $answer,
        ];
    }

    return $items;
}

function admin_content_management_faq_pairs(array $questions, array $answers): array
{
    $items = [];
    $count = max(count($questions), count($answers));

    for ($index = 0; $index < $count; $index++) {
        $question = trim((string) ($questions[$index] ?? ''));
        $answer = trim((string) ($answers[$index] ?? ''));

        if ($question === '' && $answer === '') {
            continue;
        }

        if ($question === '' || $answer === '') {
            continue;
        }

        $items[] = [
            'q' => $question,
            'a' => $answer,
        ];
    }

    return $items;
}

function admin_content_management_store_image(?array $file, ?string $existingPath = null): ?string
{
    $newPath = admin_catalog_store_upload($file, 'content');
    if ($newPath !== null && $existingPath) {
        admin_catalog_delete_file($existingPath);
        return $newPath;
    }

    return $newPath ?: $existingPath;
}

function admin_content_management_social_links(array $input): array
{
    return [
        'facebook_url' => trim((string) ($input['facebook_url'] ?? '')),
        'tiktok_url' => trim((string) ($input['tiktok_url'] ?? '')),
        'telegram_url' => trim((string) ($input['telegram_url'] ?? '')),
        'instagram_url' => trim((string) ($input['instagram_url'] ?? '')),
    ];
}

function admin_content_management_page_options(): array
{
    return [
        ['value' => 'home', 'label' => 'Home'],
        ['value' => 'footer', 'label' => 'Footer'],
        ['value' => 'about', 'label' => 'About'],
        ['value' => 'warranty', 'label' => 'Warranty & FAQs'],
        ['value' => 'delivery', 'label' => 'Delivery Policy'],
        ['value' => 'payment', 'label' => 'Payment Information'],
        ['value' => 'reservation', 'label' => 'Reservation Policy'],
    ];
}

function admin_content_management_save_home(array $input, array $files): void
{
    $current = site_content_get_home();
    $slides = [];
    for ($index = 0; $index < 3; $index++) {
        $existing = $current['hero_slides'][$index] ?? [];
        $imageField = 'hero_image_' . $index;
        $slides[] = [
            'image' => admin_content_management_store_image($files[$imageField] ?? null, $existing['image'] ?? null),
            'title' => trim((string) ($input['hero_title'][$index] ?? '')),
            'title_color' => trim((string) ($input['hero_title_color'][$index] ?? '#2f2419')),
            'subtitle' => trim((string) ($input['hero_subtitle'][$index] ?? '')),
            'subtitle_color' => trim((string) ($input['hero_subtitle_color'][$index] ?? '#2f2419')),
            'button1_text' => trim((string) ($input['hero_button1_text'][$index] ?? '')),
            'button1_url' => trim((string) ($input['hero_button1_url'][$index] ?? '')),
            'button1_background_color' => trim((string) ($input['hero_button1_background_color'][$index] ?? '#6b5241')),
            'button1_text_color' => trim((string) ($input['hero_button1_text_color'][$index] ?? '#ffffff')),
            'button2_text' => trim((string) ($input['hero_button2_text'][$index] ?? '')),
            'button2_url' => trim((string) ($input['hero_button2_url'][$index] ?? '')),
            'button2_background_color' => trim((string) ($input['hero_button2_background_color'][$index] ?? '#ffffff')),
            'button2_text_color' => trim((string) ($input['hero_button2_text_color'][$index] ?? '#6b5241')),
        ];

        if (trim((string) $slides[$index]['image']) === '') {
            throw new InvalidArgumentException('Hero image is required for slide ' . ($index + 1) . '.');
        }
    }

    $badges = [];
    for ($index = 0; $index < 4; $index++) {
        $existing = $current['trust_badges'][$index] ?? [];
        $imageField = 'trust_badge_image_' . $index;
        $badges[] = [
            'image' => admin_content_management_store_image($files[$imageField] ?? null, $existing['image'] ?? null),
            'title' => trim((string) ($input['trust_badge_title'][$index] ?? '')),
        ];
    }

    site_content_save_raw('home', [
        'hero_slides' => $slides,
        'trust_badges' => $badges,
    ]);
}

function admin_content_management_save_footer(array $input, array $files): void
{
    $current = site_content_get_footer();
    site_content_save_raw('footer', array_merge(
        [
            'footer_image' => admin_content_management_store_image($files['footer_image'] ?? null, $current['footer_image'] ?? null),
            'address' => trim((string) ($input['address'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
        ],
        admin_content_management_social_links($input)
    ));
}

function admin_content_management_save_about(array $input, array $files): void
{
    $current = site_content_get_about();
    $storySections = [];
    for ($index = 0; $index < 3; $index++) {
        $existing = $current['story_sections'][$index] ?? [];
        $storySections[] = [
            'image' => admin_content_management_store_image($files['about_story_image_' . $index] ?? null, $existing['image'] ?? null),
            'body' => trim((string) ($input['story_body'][$index] ?? '')),
            'reverse' => !empty($input['story_reverse'][$index]),
        ];
    }

    site_content_save_raw('about', array_merge(
        [
            'hero_title' => trim((string) ($input['hero_title'] ?? '')),
            'hero_body' => trim((string) ($input['hero_body'] ?? '')),
            'story_sections' => $storySections,
            'vision_lines' => admin_content_management_lines((string) ($input['vision_lines'] ?? '')),
            'contact_title' => trim((string) ($input['contact_title'] ?? '')),
            'address' => trim((string) ($input['address'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'map_embed_url' => trim((string) ($input['map_embed_url'] ?? '')),
        ],
        admin_content_management_social_links($input)
    ));
}

function admin_content_management_save_delivery_policy(array $input, array $files): void
{
    $current = site_content_get_delivery_policy();
    site_content_save_raw('support_delivery_policy', [
        'title' => trim((string) ($input['title'] ?? '')),
        'image' => admin_content_management_store_image($files['delivery_image'] ?? null, $current['image'] ?? null),
        'policies' => admin_content_management_lines((string) ($input['policies'] ?? '')),
    ]);
}

function admin_content_management_save_payment_information(array $input, array $files): void
{
    $current = site_content_get_payment_information();
    $sections = [];
    $defaultSections = $current['sections'] ?? [];

    foreach ($defaultSections as $sectionIndex => $existingSection) {
        $logos = [];
        for ($logoIndex = 0; $logoIndex < 5; $logoIndex++) {
            $existingLogo = $existingSection['logos'][$logoIndex] ?? ['label' => '', 'image' => ''];
            $label = trim((string) ($input['payment_logo_label'][$sectionIndex][$logoIndex] ?? ''));
            $image = admin_content_management_store_image(
                $files['payment_logo_image_' . $sectionIndex . '_' . $logoIndex] ?? null,
                $existingLogo['image'] ?? null
            );

            if ($label === '' && trim((string) $image) === '') {
                continue;
            }

            $logos[] = [
                'label' => $label,
                'image' => $image,
            ];
        }

        $sections[] = [
            'key' => (string) ($existingSection['key'] ?? ('section_' . $sectionIndex)),
            'title' => trim((string) ($input['payment_section_title'][$sectionIndex] ?? '')),
            'logos' => $logos,
            'notes' => admin_content_management_lines((string) ($input['payment_section_notes'][$sectionIndex] ?? '')),
        ];
    }

    site_content_save_raw('support_payment_information', [
        'page_title' => trim((string) ($input['page_title'] ?? '')),
        'sections' => $sections,
    ]);
}

function admin_content_management_save_reservation_policy(array $input): void
{
    site_content_save_raw('support_reservation_policy', [
        'title' => trim((string) ($input['title'] ?? '')),
        'policies' => admin_content_management_lines((string) ($input['policies'] ?? '')),
    ]);
}

function admin_content_management_save_warranty_faq(array $input, array $files): void
{
    $current = site_content_get_warranty_faq();
    $categories = [];

    foreach (($current['categories'] ?? []) as $index => $existingCategory) {
        $categories[] = [
            'category_id' => (int) ($existingCategory['category_id'] ?? 0),
            'key' => (string) ($existingCategory['key'] ?? ('category_' . $index)),
            'label' => (string) ($existingCategory['label'] ?? ''),
            'policy' => admin_content_management_lines((string) ($input['category_policy'][$index] ?? '')),
            'faqs' => admin_content_management_faq_pairs(
                (array) ($input['category_faq_question'][$index] ?? []),
                (array) ($input['category_faq_answer'][$index] ?? [])
            ),
        ];
    }

    site_content_save_raw('support_warranty_faq', [
        'hero_title' => trim((string) ($input['hero_title'] ?? '')),
        'hero_subtitle' => trim((string) ($input['hero_subtitle'] ?? '')),
        'categories' => $categories,
    ]);
}
