<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/database/catalog.php';
require_once __DIR__ . '/database/bundles.php';

function sitemap_xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function sitemap_lastmod_from_file(string $relativePath): ?string
{
    $absolutePath = app_project_path($relativePath);
    if (!is_file($absolutePath)) {
        return null;
    }

    $timestamp = @filemtime($absolutePath);
    if ($timestamp === false) {
        return null;
    }

    return gmdate('c', $timestamp);
}

function sitemap_lastmod_from_date(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return gmdate('c', $timestamp);
}

$entries = [
    [
        'loc' => app_url('/'),
        'lastmod' => sitemap_lastmod_from_file('index.php'),
        'changefreq' => 'daily',
        'priority' => '1.0',
    ],
    [
        'loc' => app_url('/products.php'),
        'lastmod' => sitemap_lastmod_from_file('products.php'),
        'changefreq' => 'daily',
        'priority' => '0.9',
    ],
    [
        'loc' => app_url('/bundles.php'),
        'lastmod' => sitemap_lastmod_from_file('bundles.php'),
        'changefreq' => 'weekly',
        'priority' => '0.8',
    ],
    [
        'loc' => app_url('/about.php'),
        'lastmod' => sitemap_lastmod_from_file('about.php'),
        'changefreq' => 'monthly',
        'priority' => '0.7',
    ],
    [
        'loc' => app_url('/unboxing-influencers.php'),
        'lastmod' => sitemap_lastmod_from_file('unboxing-influencers.php'),
        'changefreq' => 'weekly',
        'priority' => '0.7',
    ],
    [
        'loc' => app_url('/warranty-FAQ.php'),
        'lastmod' => sitemap_lastmod_from_file('warranty-FAQ.php'),
        'changefreq' => 'monthly',
        'priority' => '0.6',
    ],
    [
        'loc' => app_url('/delivery-policy.php'),
        'lastmod' => sitemap_lastmod_from_file('delivery-policy.php'),
        'changefreq' => 'monthly',
        'priority' => '0.6',
    ],
    [
        'loc' => app_url('/payment-information.php'),
        'lastmod' => sitemap_lastmod_from_file('payment-information.php'),
        'changefreq' => 'monthly',
        'priority' => '0.6',
    ],
    [
        'loc' => app_url('/reservation-policy.php'),
        'lastmod' => sitemap_lastmod_from_file('reservation-policy.php'),
        'changefreq' => 'monthly',
        'priority' => '0.6',
    ],
    [
        'loc' => app_url('/wholesale.php'),
        'lastmod' => sitemap_lastmod_from_file('wholesale.php'),
        'changefreq' => 'monthly',
        'priority' => '0.6',
    ],
];

try {
    foreach (catalog_fetch_customer_products() as $product) {
        $productId = (int) ($product['product_id'] ?? 0);
        if ($productId <= 0) {
            continue;
        }

        $entries[] = [
            'loc' => app_url('/product-details.php?id=' . $productId),
            'lastmod' => sitemap_lastmod_from_date((string) ($product['created_at'] ?? '')),
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ];
    }
} catch (Throwable $exception) {
    // Serve the static sitemap entries even if the database is temporarily unavailable.
}

try {
    foreach (bundle_fetch_customer_bundles() as $bundle) {
        $bundleId = (int) ($bundle['id'] ?? 0);
        if ($bundleId <= 0) {
            continue;
        }

        $entries[] = [
            'loc' => app_url('/bundle-details.php?id=' . $bundleId),
            'lastmod' => sitemap_lastmod_from_date((string) ($bundle['created_at'] ?? '')),
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ];
    }
} catch (Throwable $exception) {
    // Serve the static sitemap entries even if the database is temporarily unavailable.
}

header('Content-Type: application/xml; charset=UTF-8');

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach ($entries as $entry) {
    echo "  <url>\n";
    echo '    <loc>' . sitemap_xml_escape((string) $entry['loc']) . "</loc>\n";

    if (!empty($entry['lastmod'])) {
        echo '    <lastmod>' . sitemap_xml_escape((string) $entry['lastmod']) . "</lastmod>\n";
    }

    if (!empty($entry['changefreq'])) {
        echo '    <changefreq>' . sitemap_xml_escape((string) $entry['changefreq']) . "</changefreq>\n";
    }

    if (!empty($entry['priority'])) {
        echo '    <priority>' . sitemap_xml_escape((string) $entry['priority']) . "</priority>\n";
    }

    echo "  </url>\n";
}

echo "</urlset>\n";
