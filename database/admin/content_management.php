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

function admin_content_management_delivery_location_lines(string $value): array
{
    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
    $states = [];

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));
        if (count($parts) < 3) {
            continue;
        }

        [$stateName, $cityName, $townshipName] = [$parts[0], $parts[1], $parts[2]];
        if ($stateName === '' || $cityName === '' || $townshipName === '') {
            continue;
        }

        if (!isset($states[$stateName])) {
            $states[$stateName] = [
                'name' => $stateName,
                'cities' => [],
            ];
        }

        if (!isset($states[$stateName]['cities'][$cityName])) {
            $states[$stateName]['cities'][$cityName] = [
                'name' => $cityName,
                'townships' => [],
            ];
        }

        if (!in_array($townshipName, $states[$stateName]['cities'][$cityName]['townships'], true)) {
            $states[$stateName]['cities'][$cityName]['townships'][] = $townshipName;
        }
    }

    $result = [];
    foreach ($states as $state) {
        $cities = [];
        foreach ($state['cities'] as $city) {
            $cities[] = $city;
        }

        $state['cities'] = $cities;
        $result[] = $state;
    }

    return ['states' => $result];
}

function admin_content_management_delivery_locations_from_columns(array $statesInput, array $citiesInput, array $townshipsInput): array
{
    $states = [];
    $rowCount = max(count($statesInput), count($citiesInput), count($townshipsInput));

    for ($index = 0; $index < $rowCount; $index++) {
        $stateName = trim((string) ($statesInput[$index] ?? ''));
        $cityName = trim((string) ($citiesInput[$index] ?? ''));
        $townshipName = trim((string) ($townshipsInput[$index] ?? ''));

        if ($stateName === '' && $cityName === '' && $townshipName === '') {
            continue;
        }

        if ($stateName === '' || $cityName === '' || $townshipName === '') {
            continue;
        }

        if (!isset($states[$stateName])) {
            $states[$stateName] = [
                'name' => $stateName,
                'cities' => [],
            ];
        }

        if (!isset($states[$stateName]['cities'][$cityName])) {
            $states[$stateName]['cities'][$cityName] = [
                'name' => $cityName,
                'townships' => [],
            ];
        }

        if (!in_array($townshipName, $states[$stateName]['cities'][$cityName]['townships'], true)) {
            $states[$stateName]['cities'][$cityName]['townships'][] = $townshipName;
        }
    }

    $result = [];
    foreach ($states as $state) {
        $cities = [];
        foreach ($state['cities'] as $city) {
            $cities[] = $city;
        }
        $state['cities'] = $cities;
        $result[] = $state;
    }

    return ['states' => $result];
}

function admin_content_management_delivery_locations_from_tables(
    array $statesInput,
    array $cityStatesInput,
    array $citiesInput,
    array $townshipStatesInput,
    array $townshipCitiesInput,
    array $townshipsInput
): array {
    $states = [];

    foreach ($statesInput as $stateName) {
        $stateName = trim((string) $stateName);
        if ($stateName === '' || isset($states[$stateName])) {
            continue;
        }

        $states[$stateName] = [
            'name' => $stateName,
            'cities' => [],
        ];
    }

    $cityRowCount = max(count($cityStatesInput), count($citiesInput));
    for ($index = 0; $index < $cityRowCount; $index++) {
        $stateName = trim((string) ($cityStatesInput[$index] ?? ''));
        $cityName = trim((string) ($citiesInput[$index] ?? ''));

        if ($stateName === '' && $cityName === '') {
            continue;
        }

        if ($stateName === '' || $cityName === '' || !isset($states[$stateName])) {
            continue;
        }

        if (!isset($states[$stateName]['cities'][$cityName])) {
            $states[$stateName]['cities'][$cityName] = [
                'name' => $cityName,
                'townships' => [],
            ];
        }
    }

    $townshipRowCount = max(count($townshipStatesInput), count($townshipCitiesInput), count($townshipsInput));
    for ($index = 0; $index < $townshipRowCount; $index++) {
        $stateName = trim((string) ($townshipStatesInput[$index] ?? ''));
        $cityName = trim((string) ($townshipCitiesInput[$index] ?? ''));
        $townshipName = trim((string) ($townshipsInput[$index] ?? ''));

        if ($stateName === '' && $cityName === '' && $townshipName === '') {
            continue;
        }

        if (
            $stateName === ''
            || $cityName === ''
            || $townshipName === ''
            || !isset($states[$stateName])
            || !isset($states[$stateName]['cities'][$cityName])
        ) {
            continue;
        }

        if (!in_array($townshipName, $states[$stateName]['cities'][$cityName]['townships'], true)) {
            $states[$stateName]['cities'][$cityName]['townships'][] = $townshipName;
        }
    }

    $result = [];
    foreach ($states as $state) {
        $cities = [];
        foreach ($state['cities'] as $city) {
            $cities[] = $city;
        }

        $state['cities'] = $cities;
        $result[] = $state;
    }

    return ['states' => $result];
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
        ['value' => 'delivery_locations', 'label' => 'Delivery Locations'],
        ['value' => 'payment', 'label' => 'Payment Information'],
        ['value' => 'reservation', 'label' => 'Reservation Policy'],
    ];
}

function admin_content_management_fetch_home_button_product_options(): array
{
    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT p.product_id, p.name, b.name AS brand_name
         FROM products p
         LEFT JOIN brands b ON b.brand_id = p.brand_id
         WHERE p.visibility = 1
         ORDER BY p.name ASC, p.product_id ASC'
    );

    return $statement->fetchAll() ?: [];
}

function admin_content_management_save_home(array $input, array $files): void
{
    $current = site_content_get_home();
    $slides = [];
    $pdo = get_database_connection();
    for ($index = 0; $index < 3; $index++) {
        $existing = $current['hero_slides'][$index] ?? [];
        $imageField = 'hero_image_' . $index;
        $button1Action = trim((string) ($input['hero_button1_action'][$index] ?? 'link'));
        $button2Action = trim((string) ($input['hero_button2_action'][$index] ?? 'link'));
        $button1ProductId = (int) ($input['hero_button1_product_id'][$index] ?? 0);
        $button2ProductId = (int) ($input['hero_button2_product_id'][$index] ?? 0);

        if (!in_array($button1Action, ['link', 'add_to_cart'], true)) {
            $button1Action = 'link';
        }

        if (!in_array($button2Action, ['link', 'add_to_cart'], true)) {
            $button2Action = 'link';
        }

        if ($button1Action === 'add_to_cart' && $button1ProductId <= 0) {
            throw new InvalidArgumentException('Please select a product for Home slide ' . ($index + 1) . ' button 1.');
        }

        if ($button2Action === 'add_to_cart' && $button2ProductId <= 0) {
            throw new InvalidArgumentException('Please select a product for Home slide ' . ($index + 1) . ' button 2.');
        }

        if ($button1ProductId > 0 && !admin_catalog_record_exists($pdo, 'products', 'product_id', $button1ProductId)) {
            throw new InvalidArgumentException('Selected product for Home slide ' . ($index + 1) . ' button 1 is invalid.');
        }

        if ($button2ProductId > 0 && !admin_catalog_record_exists($pdo, 'products', 'product_id', $button2ProductId)) {
            throw new InvalidArgumentException('Selected product for Home slide ' . ($index + 1) . ' button 2 is invalid.');
        }

        $slides[] = [
            'image' => admin_content_management_store_image($files[$imageField] ?? null, $existing['image'] ?? null),
            'title' => trim((string) ($input['hero_title'][$index] ?? '')),
            'title_color' => trim((string) ($input['hero_title_color'][$index] ?? '#2f2419')),
            'subtitle' => trim((string) ($input['hero_subtitle'][$index] ?? '')),
            'subtitle_color' => trim((string) ($input['hero_subtitle_color'][$index] ?? '#2f2419')),
            'button1_text' => trim((string) ($input['hero_button1_text'][$index] ?? '')),
            'button1_action' => $button1Action,
            'button1_url' => trim((string) ($input['hero_button1_url'][$index] ?? '')),
            'button1_product_id' => $button1ProductId,
            'button1_background_color' => trim((string) ($input['hero_button1_background_color'][$index] ?? '#6b5241')),
            'button1_text_color' => trim((string) ($input['hero_button1_text_color'][$index] ?? '#ffffff')),
            'button2_text' => trim((string) ($input['hero_button2_text'][$index] ?? '')),
            'button2_action' => $button2Action,
            'button2_url' => trim((string) ($input['hero_button2_url'][$index] ?? '')),
            'button2_product_id' => $button2ProductId,
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

function admin_content_management_save_delivery_locations(array $input): void
{
    if (
        isset($input['state_name'])
        || isset($input['city_state'])
        || isset($input['city_name'])
        || isset($input['township_state'])
        || isset($input['township_city'])
        || isset($input['township_name'])
    ) {
        $locations = admin_content_management_delivery_locations_from_tables(
            (array) ($input['state_name'] ?? []),
            (array) ($input['city_state'] ?? []),
            (array) ($input['city_name'] ?? []),
            (array) ($input['township_state'] ?? []),
            (array) ($input['township_city'] ?? []),
            (array) ($input['township_name'] ?? [])
        );
    } elseif (isset($input['location_state']) || isset($input['location_city']) || isset($input['location_township'])) {
        $locations = admin_content_management_delivery_locations_from_columns(
            (array) ($input['location_state'] ?? []),
            (array) ($input['location_city'] ?? []),
            (array) ($input['location_township'] ?? [])
        );
    } else {
        $locations = admin_content_management_delivery_location_lines((string) ($input['locations'] ?? ''));
    }

    site_content_save_raw('delivery_locations', $locations);
}

function admin_content_management_save_payment_information(array $input, array $files): void
{
    $current = site_content_get_payment_information();
    $sections = [];
    $defaultSections = $current['sections'] ?? [];

    foreach ($defaultSections as $sectionIndex => $existingSection) {
        $sections[] = [
            'key' => (string) ($existingSection['key'] ?? ('section_' . $sectionIndex)),
            'title' => trim((string) ($input['payment_section_title'][$sectionIndex] ?? '')),
            'logos' => (array) ($existingSection['logos'] ?? []),
            'notes' => admin_content_management_lines((string) ($input['payment_section_notes'][$sectionIndex] ?? '')),
        ];
    }

    $sectionKeys = array_values(array_filter(array_map(
        static fn (array $section): string => trim((string) ($section['key'] ?? '')),
        $sections
    )));
    $methods = [];
    $existingMethods = (array) ($current['methods'] ?? []);
    $methodKeys = (array) ($input['payment_method_key'] ?? []);
    $methodLabels = (array) ($input['payment_method_label'] ?? []);
    $methodSectionKeys = (array) ($input['payment_method_section_key'] ?? []);
    $methodAccountNames = (array) ($input['payment_method_account_name'] ?? []);
    $methodAccountNumbers = (array) ($input['payment_method_account_number'] ?? []);
    $methodPhones = (array) ($input['payment_method_phone'] ?? []);
    $methodInstructions = (array) ($input['payment_method_instructions'] ?? []);
    $methodActiveFlags = (array) ($input['payment_method_is_active'] ?? []);
    $methodProofFlags = (array) ($input['payment_method_requires_payment_proof'] ?? []);
    $methodFileKeys = array_filter(
        array_keys($files),
        static fn (string $key): bool => str_starts_with($key, 'payment_method_qr_image_')
    );
    $methodRowCount = max(
        count($existingMethods),
        count($methodKeys),
        count($methodLabels),
        count($methodSectionKeys),
        count($methodAccountNames),
        count($methodAccountNumbers),
        count($methodPhones),
        count($methodInstructions),
        count($methodFileKeys)
    );
    $usedMethodKeys = [];

    for ($methodIndex = 0; $methodIndex < $methodRowCount; $methodIndex++) {
        $existingMethod = $existingMethods[$methodIndex] ?? [];
        $label = trim((string) ($methodLabels[$methodIndex] ?? ''));
        $methodKey = site_content_payment_method_slug((string) ($methodKeys[$methodIndex] ?? ''));
        if ($methodKey === '') {
            $methodKey = site_content_payment_method_slug($label);
        }

        $sectionKey = site_content_payment_method_slug((string) ($methodSectionKeys[$methodIndex] ?? ($existingMethod['section_key'] ?? '')));
        if (!in_array($sectionKey, $sectionKeys, true)) {
            $sectionKey = $sectionKeys[0] ?? '';
        }

        $accountName = trim((string) ($methodAccountNames[$methodIndex] ?? ''));
        $accountNumber = trim((string) ($methodAccountNumbers[$methodIndex] ?? ''));
        $phone = trim((string) ($methodPhones[$methodIndex] ?? ''));
        $instructions = admin_content_management_lines((string) ($methodInstructions[$methodIndex] ?? ''));
        $logoImage = admin_content_management_store_image(
            $files['payment_method_logo_image_' . $methodIndex] ?? null,
            $existingMethod['logo_image'] ?? null
        );
        $qrImage = admin_content_management_store_image(
            $files['payment_method_qr_image_' . $methodIndex] ?? null,
            $existingMethod['qr_image'] ?? null
        );

        if (
            $label === ''
            && $methodKey === ''
            && $sectionKey === ''
            && $accountName === ''
            && $accountNumber === ''
            && $phone === ''
            && $instructions === []
            && trim((string) $logoImage) === ''
            && trim((string) $qrImage) === ''
        ) {
            continue;
        }

        if ($label === '') {
            continue;
        }

        if ($methodKey === '') {
            $methodKey = 'payment_method_' . ($methodIndex + 1);
        }

        $baseMethodKey = $methodKey;
        $suffix = 2;
        while (in_array($methodKey, $usedMethodKeys, true)) {
            $methodKey = $baseMethodKey . '_' . $suffix;
            $suffix++;
        }
        $usedMethodKeys[] = $methodKey;

        $methods[] = [
            'key' => $methodKey,
            'label' => $label,
            'section_key' => $sectionKey,
            'is_active' => !empty($methodActiveFlags[$methodIndex]),
            'requires_payment_proof' => !empty($methodProofFlags[$methodIndex]),
            'logo_image' => $logoImage,
            'account_name' => $accountName,
            'account_number' => $accountNumber,
            'phone' => $phone,
            'qr_image' => $qrImage,
            'instructions' => $instructions,
        ];
    }

    site_content_save_raw('support_payment_information', [
        'page_title' => trim((string) ($input['page_title'] ?? '')),
        'sections' => $sections,
        'methods' => $methods,
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
        'general_faqs' => admin_content_management_faq_pairs(
            (array) ($input['general_faq_question'] ?? []),
            (array) ($input['general_faq_answer'] ?? [])
        ),
        'categories' => $categories,
    ]);
}
