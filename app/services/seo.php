<?php

require_once __DIR__ . '/../../config/app.php';

function seo_site_name(): string
{
    return 'ZYPP Camera House';
}

function seo_default_description(): string
{
    return 'Shop cameras, lenses, creator gear, bundles, and accessories from ZYPP Camera House.';
}

function seo_default_image_path(): string
{
    return '/storage/uploads/contents/logo.png';
}

function seo_is_absolute_url(string $value): bool
{
    return preg_match('~^https?://~i', $value) === 1;
}

function seo_abs_url(string $value = ''): string
{
    $value = trim($value);
    if ($value === '') {
        return app_url('/');
    }

    if (seo_is_absolute_url($value)) {
        return $value;
    }

    return app_url($value);
}

function seo_clean_text(?string $value, int $maxLength = 160): string
{
    $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim((string) $text);

    if ($text === '') {
        return '';
    }

    if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
        $truncated = mb_substr($text, 0, $maxLength - 1);
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > (int) floor($maxLength * 0.55)) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        $text = rtrim($truncated, " \t\n\r\0\x0B.,;:-") . '...';
    }

    return $text;
}

function seo_title(string $pageTitle = ''): string
{
    $pageTitle = trim($pageTitle);
    if ($pageTitle === '') {
        return seo_site_name();
    }

    return $pageTitle . ' | ' . seo_site_name();
}

function seo_current_url(bool $includeQuery = false): string
{
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '/');
    $url = app_base_url() . $path;

    if ($includeQuery) {
        $query = trim((string) (parse_url($requestUri, PHP_URL_QUERY) ?? ''));
        if ($query !== '') {
            $url .= '?' . $query;
        }
    }

    return $url;
}

function seo_auto_noindex(?string $scriptPath = null): bool
{
    $basename = strtolower(basename((string) ($scriptPath ?? ($_SERVER['SCRIPT_NAME'] ?? ''))));
    $noIndexPages = [
        'cart.php',
        'check-order.php',
        'delivery.php',
        'payment.php',
        'receipt.php',
        'register.php',
        'reset-password.php',
        'user-profile.php',
    ];

    return in_array($basename, $noIndexPages, true);
}

function seo_meta_payload(array $overrides = []): array
{
    $title = trim((string) ($overrides['title'] ?? ''));
    $description = trim((string) ($overrides['description'] ?? ''));
    $canonical = trim((string) ($overrides['canonical'] ?? ''));
    $image = trim((string) ($overrides['image'] ?? ''));
    $type = trim((string) ($overrides['type'] ?? 'website'));
    $noIndex = array_key_exists('noindex', $overrides)
        ? (bool) $overrides['noindex']
        : seo_auto_noindex();

    return [
        'title' => seo_title($title),
        'description' => seo_clean_text($description !== '' ? $description : seo_default_description(), 170),
        'canonical' => seo_abs_url($canonical !== '' ? $canonical : seo_current_url(false)),
        'image' => seo_abs_url($image !== '' ? $image : seo_default_image_path()),
        'type' => $type !== '' ? $type : 'website',
        'robots' => $noIndex ? 'noindex, nofollow, noarchive' : 'index, follow, max-image-preview:large',
        'noindex' => $noIndex,
    ];
}

function seo_structured_data_list($data): array
{
    if (!is_array($data) || $data === []) {
        return [];
    }

    if (array_is_list($data)) {
        return array_values(array_filter($data, static fn ($item): bool => is_array($item) && $item !== []));
    }

    return [$data];
}
