<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../catalog.php';
require_once __DIR__ . '/../media.php';
require_once __DIR__ . '/auth.php';

function admin_media_storage_directory(string $type): string
{
    $map = [
        'video' => 'storage/uploads/media/videos',
        'thumbnail' => 'storage/uploads/media/thumbnails',
    ];

    if (!isset($map[$type])) {
        throw new InvalidArgumentException('Unsupported media storage type.');
    }

    return $map[$type];
}

function admin_media_store_upload(array $file, string $type): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed.');
    }

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $allowed = $type === 'video'
        ? ['mp4', 'webm', 'ogg', 'mov', 'm4v']
        : ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException($type === 'video'
            ? 'Please upload a valid video file.'
            : 'Please upload a valid image file.');
    }

    $relativeDirectory = admin_media_storage_directory($type);
    $absoluteDirectory = app_project_path($relativeDirectory);
    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0777, true) && !is_dir($absoluteDirectory)) {
        throw new RuntimeException('Unable to create upload directory.');
    }

    $prefix = $type === 'video' ? 'media-video' : 'media-thumb';
    $filename = sprintf('%s-%s-%s.%s', $prefix, date('YmdHis'), bin2hex(random_bytes(5)), $extension);
    $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $absolutePath)) {
        throw new RuntimeException('Unable to store the uploaded file.');
    }

    return '/' . trim(str_replace(DIRECTORY_SEPARATOR, '/', $relativeDirectory), '/') . '/' . $filename;
}

function admin_media_delete_file(?string $path): void
{
    $path = trim((string) $path);
    if ($path === '' || str_contains($path, '://')) {
        return;
    }

    $absolutePath = app_project_path(ltrim($path, '/'));
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function admin_media_fetch_unboxing_videos(): array
{
    if (!media_table_exists('media_unboxing_videos')) {
        return [];
    }

    $pdo = get_database_connection();
    $hasProfiles = admin_auth_has_profiles_table($pdo);
    $uploaderExpr = $hasProfiles
        ? "COALESCE(ap.full_name, a.email, 'Admin')"
        : "COALESCE(a.email, 'Admin')";

    $statement = $pdo->query(
        "SELECT mv.*,
                c.name AS category_name,
                b.name AS brand_name,
                {$uploaderExpr} AS uploader_name
         FROM media_unboxing_videos mv
         LEFT JOIN categories c ON c.category_id = mv.category_id
         LEFT JOIN brands b ON b.brand_id = mv.brand_id
         LEFT JOIN admins a ON a.id = mv.created_by
         " . ($hasProfiles ? 'LEFT JOIN admin_profiles ap ON ap.admin_id = a.id' : '') . "
         ORDER BY mv.id DESC"
    );

    return array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'title' => trim((string) ($row['title'] ?? '')),
            'category_id' => isset($row['category_id']) ? (int) $row['category_id'] : 0,
            'category_name' => trim((string) ($row['category_name'] ?? '')),
            'brand_id' => isset($row['brand_id']) ? (int) $row['brand_id'] : 0,
            'brand_name' => trim((string) ($row['brand_name'] ?? '')),
            'video_file' => trim((string) ($row['video_file'] ?? '')),
            'video_link' => trim((string) ($row['video_link'] ?? '')),
            'thumbnail_file' => trim((string) ($row['thumbnail_file'] ?? '')),
            'src' => media_resolve_video_source($row),
            'thumbnail_src' => media_public_path((string) ($row['thumbnail_file'] ?? '')),
            'uploader' => trim((string) ($row['uploader_name'] ?? 'Admin')),
            'uploaded_at' => trim((string) ($row['created_at'] ?? '')),
        ];
    }, $statement->fetchAll());
}

function admin_media_fetch_influencer_videos(): array
{
    if (!media_table_exists('media_influencer_videos')) {
        return [];
    }

    $pdo = get_database_connection();
    $hasProfiles = admin_auth_has_profiles_table($pdo);
    $uploaderExpr = $hasProfiles
        ? "COALESCE(ap.full_name, a.email, 'Admin')"
        : "COALESCE(a.email, 'Admin')";

    $statement = $pdo->query(
        "SELECT mv.*,
                {$uploaderExpr} AS uploader_name
         FROM media_influencer_videos mv
         LEFT JOIN admins a ON a.id = mv.created_by
         " . ($hasProfiles ? 'LEFT JOIN admin_profiles ap ON ap.admin_id = a.id' : '') . "
         ORDER BY mv.id DESC"
    );

    return array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'title' => trim((string) ($row['title'] ?? '')),
            'influencer' => trim((string) ($row['influencer_name'] ?? '')),
            'video_file' => trim((string) ($row['video_file'] ?? '')),
            'video_link' => trim((string) ($row['video_link'] ?? '')),
            'thumbnail_file' => trim((string) ($row['thumbnail_file'] ?? '')),
            'src' => media_resolve_video_source($row),
            'thumbnail_src' => media_public_path((string) ($row['thumbnail_file'] ?? '')),
            'uploader' => trim((string) ($row['uploader_name'] ?? 'Admin')),
            'uploaded_at' => trim((string) ($row['created_at'] ?? '')),
        ];
    }, $statement->fetchAll());
}

function admin_media_validate_common(array $input, array $files, bool $isInfluencer = false): array
{
    $id = (int) ($input['media_id'] ?? 0);
    $title = trim((string) ($input['videoTitle'] ?? ''));
    $videoLink = trim((string) ($input['videoLink'] ?? ''));
    $hasVideoUpload = isset($files['videoFile']) && ($files['videoFile']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $hasThumbUpload = isset($files['thumbnailFile']) && ($files['thumbnailFile']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($title === '') {
        throw new RuntimeException('Video title is required.');
    }

    if (!$hasVideoUpload && $videoLink === '' && $id <= 0) {
        throw new RuntimeException('Please upload a video file or provide a video link.');
    }

    if ($videoLink !== '' && !filter_var($videoLink, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Please enter a valid video link.');
    }

    $validated = [
        'id' => $id,
        'title' => $title,
        'video_link' => $videoLink,
        'has_video_upload' => $hasVideoUpload,
        'has_thumbnail_upload' => $hasThumbUpload,
    ];

    if ($isInfluencer) {
        $influencerName = trim((string) ($input['influencerName'] ?? ''));
        if ($influencerName === '') {
            throw new RuntimeException('Influencer name is required.');
        }
        $validated['influencer_name'] = $influencerName;
    } else {
        $categoryId = (int) ($input['categoryId'] ?? 0);
        $brandId = (int) ($input['brandId'] ?? 0);
        if ($categoryId <= 0) {
            throw new RuntimeException('Please choose a category.');
        }
        if ($brandId <= 0) {
            throw new RuntimeException('Please choose a brand.');
        }
        $validated['category_id'] = $categoryId;
        $validated['brand_id'] = $brandId;
    }

    return $validated;
}

function admin_media_fetch_existing_row(string $table, int $id): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
    $statement->execute([':id' => $id]);
    $row = $statement->fetch();

    return $row ?: null;
}

function admin_media_save_unboxing(array $input, array $files, int $adminId): void
{
    if (!media_table_exists('media_unboxing_videos')) {
        throw new RuntimeException('Media tables are required. Run the media migration first.');
    }

    $validated = admin_media_validate_common($input, $files, false);
    $existing = $validated['id'] > 0 ? admin_media_fetch_existing_row('media_unboxing_videos', $validated['id']) : null;
    if ($validated['id'] > 0 && !$existing) {
        throw new RuntimeException('Unboxing video not found.');
    }

    $videoFile = $existing['video_file'] ?? null;
    $thumbnailFile = $existing['thumbnail_file'] ?? null;

    if ($validated['has_video_upload']) {
        $newVideoFile = admin_media_store_upload($files['videoFile'], 'video');
        admin_media_delete_file($videoFile);
        $videoFile = $newVideoFile;
        if ($validated['video_link'] !== '' && $existing) {
            $validated['video_link'] = '';
        }
    }

    if ($validated['has_thumbnail_upload']) {
        $newThumbnailFile = admin_media_store_upload($files['thumbnailFile'], 'thumbnail');
        admin_media_delete_file($thumbnailFile);
        $thumbnailFile = $newThumbnailFile;
    }

    $pdo = get_database_connection();
    if ($validated['id'] > 0) {
        $statement = $pdo->prepare(
            'UPDATE media_unboxing_videos
             SET title = :title,
                 category_id = :category_id,
                 brand_id = :brand_id,
                 video_file = :video_file,
                 video_link = :video_link,
                 thumbnail_file = :thumbnail_file,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            ':title' => $validated['title'],
            ':category_id' => $validated['category_id'],
            ':brand_id' => $validated['brand_id'],
            ':video_file' => $videoFile ?: null,
            ':video_link' => $validated['video_link'] !== '' ? $validated['video_link'] : null,
            ':thumbnail_file' => $thumbnailFile ?: null,
            ':id' => $validated['id'],
        ]);
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO media_unboxing_videos
            (title, category_id, brand_id, video_file, video_link, thumbnail_file, created_by)
         VALUES
            (:title, :category_id, :brand_id, :video_file, :video_link, :thumbnail_file, :created_by)'
    );
    $statement->execute([
        ':title' => $validated['title'],
        ':category_id' => $validated['category_id'],
        ':brand_id' => $validated['brand_id'],
        ':video_file' => $videoFile ?: null,
        ':video_link' => $validated['video_link'] !== '' ? $validated['video_link'] : null,
        ':thumbnail_file' => $thumbnailFile ?: null,
        ':created_by' => $adminId > 0 ? $adminId : null,
    ]);
}

function admin_media_save_influencer(array $input, array $files, int $adminId): void
{
    if (!media_table_exists('media_influencer_videos')) {
        throw new RuntimeException('Media tables are required. Run the media migration first.');
    }

    $validated = admin_media_validate_common($input, $files, true);
    $existing = $validated['id'] > 0 ? admin_media_fetch_existing_row('media_influencer_videos', $validated['id']) : null;
    if ($validated['id'] > 0 && !$existing) {
        throw new RuntimeException('Influencer video not found.');
    }

    $videoFile = $existing['video_file'] ?? null;
    $thumbnailFile = $existing['thumbnail_file'] ?? null;

    if ($validated['has_video_upload']) {
        $newVideoFile = admin_media_store_upload($files['videoFile'], 'video');
        admin_media_delete_file($videoFile);
        $videoFile = $newVideoFile;
        if ($validated['video_link'] !== '' && $existing) {
            $validated['video_link'] = '';
        }
    }

    if ($validated['has_thumbnail_upload']) {
        $newThumbnailFile = admin_media_store_upload($files['thumbnailFile'], 'thumbnail');
        admin_media_delete_file($thumbnailFile);
        $thumbnailFile = $newThumbnailFile;
    }

    $pdo = get_database_connection();
    if ($validated['id'] > 0) {
        $statement = $pdo->prepare(
            'UPDATE media_influencer_videos
             SET title = :title,
                 influencer_name = :influencer_name,
                 video_file = :video_file,
                 video_link = :video_link,
                 thumbnail_file = :thumbnail_file,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            ':title' => $validated['title'],
            ':influencer_name' => $validated['influencer_name'],
            ':video_file' => $videoFile ?: null,
            ':video_link' => $validated['video_link'] !== '' ? $validated['video_link'] : null,
            ':thumbnail_file' => $thumbnailFile ?: null,
            ':id' => $validated['id'],
        ]);
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO media_influencer_videos
            (title, influencer_name, video_file, video_link, thumbnail_file, created_by)
         VALUES
            (:title, :influencer_name, :video_file, :video_link, :thumbnail_file, :created_by)'
    );
    $statement->execute([
        ':title' => $validated['title'],
        ':influencer_name' => $validated['influencer_name'],
        ':video_file' => $videoFile ?: null,
        ':video_link' => $validated['video_link'] !== '' ? $validated['video_link'] : null,
        ':thumbnail_file' => $thumbnailFile ?: null,
        ':created_by' => $adminId > 0 ? $adminId : null,
    ]);
}

function admin_media_delete(string $type, int $id): void
{
    $table = $type === 'influencer' ? 'media_influencer_videos' : 'media_unboxing_videos';
    if (!media_table_exists($table)) {
        throw new RuntimeException('Media tables are required. Run the media migration first.');
    }

    $existing = admin_media_fetch_existing_row($table, $id);
    if (!$existing) {
        throw new RuntimeException('Media item not found.');
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
    $statement->execute([':id' => $id]);

    admin_media_delete_file((string) ($existing['video_file'] ?? ''));
    admin_media_delete_file((string) ($existing['thumbnail_file'] ?? ''));
}
