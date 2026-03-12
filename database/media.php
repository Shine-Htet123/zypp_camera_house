<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/catalog.php';

function media_table_exists(string $tableName): bool
{
    static $cache = [];

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = :table_name'
    );
    $statement->execute([':table_name' => $tableName]);

    return $cache[$tableName] = ((int) $statement->fetchColumn() > 0);
}

function media_public_path(?string $path, string $fallback = ''): string
{
    $path = trim((string) $path);
    if ($path !== '') {
        return catalog_public_file_url($path, $fallback !== '' ? $fallback : $path);
    }

    return $fallback;
}

function media_resolve_video_source(array $row): string
{
    $link = trim((string) ($row['video_link'] ?? ''));
    if ($link !== '') {
        return $link;
    }

    return media_public_path((string) ($row['video_file'] ?? ''));
}

function media_fetch_customer_unboxing_videos(): array
{
    if (!media_table_exists('media_unboxing_videos')) {
        return [];
    }

    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT mv.id,
                mv.title,
                mv.video_file,
                mv.video_link,
                mv.thumbnail_file,
                mv.created_at,
                c.category_id,
                c.name AS category_name,
                b.brand_id,
                b.name AS brand_name
         FROM media_unboxing_videos mv
         LEFT JOIN categories c ON c.category_id = mv.category_id
         LEFT JOIN brands b ON b.brand_id = mv.brand_id
         ORDER BY mv.id DESC'
    );

    $rows = [];
    foreach ($statement->fetchAll() as $row) {
        $rows[] = [
            'id' => (int) ($row['id'] ?? 0),
            'title' => trim((string) ($row['title'] ?? '')),
            'category' => trim((string) ($row['category_name'] ?? '')),
            'brand' => trim((string) ($row['brand_name'] ?? '')),
            'video' => media_resolve_video_source($row),
            'thumbnail' => media_public_path((string) ($row['thumbnail_file'] ?? '')),
        ];
    }

    return $rows;
}

function media_fetch_customer_influencer_videos(): array
{
    if (!media_table_exists('media_influencer_videos')) {
        return [];
    }

    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT id, title, influencer_name, video_file, video_link, thumbnail_file
         FROM media_influencer_videos
         ORDER BY id DESC'
    );

    $rows = [];
    foreach ($statement->fetchAll() as $row) {
        $rows[] = [
            'id' => (int) ($row['id'] ?? 0),
            'title' => trim((string) ($row['title'] ?? '')),
            'author' => trim((string) ($row['influencer_name'] ?? '')),
            'video' => media_resolve_video_source($row),
            'thumbnail' => media_public_path((string) ($row['thumbnail_file'] ?? '')),
        ];
    }

    return $rows;
}

function media_fetch_customer_filter_options(): array
{
    $unboxingVideos = media_fetch_customer_unboxing_videos();
    $categories = ['All'];
    $brands = ['All'];

    foreach ($unboxingVideos as $video) {
        $category = trim((string) ($video['category'] ?? ''));
        $brand = trim((string) ($video['brand'] ?? ''));

        if ($category !== '' && !in_array($category, $categories, true)) {
            $categories[] = $category;
        }

        if ($brand !== '' && !in_array($brand, $brands, true)) {
            $brands[] = $brand;
        }
    }

    return [
        'categories' => $categories,
        'brands' => $brands,
        'unboxing' => $unboxingVideos,
        'influencer' => media_fetch_customer_influencer_videos(),
    ];
}

function media_fetch_summary_counts(): array
{
    $summary = [
        'unboxing_count' => 0,
        'influencer_count' => 0,
    ];

    if (media_table_exists('media_unboxing_videos')) {
        $pdo = get_database_connection();
        $summary['unboxing_count'] = (int) $pdo->query('SELECT COUNT(*) FROM media_unboxing_videos')->fetchColumn();
    }

    if (media_table_exists('media_influencer_videos')) {
        $pdo = get_database_connection();
        $summary['influencer_count'] = (int) $pdo->query('SELECT COUNT(*) FROM media_influencer_videos')->fetchColumn();
    }

    return $summary;
}
