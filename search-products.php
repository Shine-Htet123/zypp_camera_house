<?php

require_once __DIR__ . '/database/catalog.php';
require_once __DIR__ . '/config/app.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim((string) ($_GET['q'] ?? ''));
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 6;
$limit = max(1, min(10, $limit));

if ($query === '') {
    echo json_encode([
        'query' => '',
        'items' => [],
    ]);
    exit;
}

$results = catalog_search_products($query, $limit);
$items = [];
$productDetailsPath = app_path('/product-details.php');
$specsByProduct = catalog_fetch_product_specs_map(array_column($results, 'product_id'));

foreach ($results as $product) {
    $specs = $specsByProduct[(int) ($product['product_id'] ?? 0)] ?? [];
    $specLabels = [];

    foreach ($specs as $spec) {
        $name = trim((string) ($spec['spec_name'] ?? ''));
        $value = trim((string) ($spec['spec_value'] ?? ''));
        $label = $name !== '' && $value !== '' && $name !== $value
            ? $name . ': ' . $value
            : ($name !== '' ? $name : $value);

        if ($label === '') {
            continue;
        }

        $specLabels[] = $label;
        if (count($specLabels) >= 5) {
            break;
        }
    }

    $items[] = [
        'product_id' => (int) $product['product_id'],
        'name' => (string) $product['name'],
        'image_url' => (string) $product['image'],
        'price' => (string) $product['price'],
        'original_price' => (string) ($product['original'] ?? ''),
        'has_discount' => !empty($product['original']),
        'brand_name' => (string) ($product['brand_name'] ?? $product['brand'] ?? ''),
        'detail_url' => $productDetailsPath . '?id=' . (int) $product['product_id'],
        'specs' => $specLabels,
    ];
}

echo json_encode([
    'query' => $query,
    'items' => $items,
], JSON_UNESCAPED_SLASHES);
