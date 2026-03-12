<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/catalog.php';
require_once __DIR__ . '/../database/admin/media.php';

$setFlash = static function (string $message, string $type = 'success'): void {
    $_SESSION['admin_media_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): ?array {
    $flash = $_SESSION['admin_media_flash'] ?? null;
    unset($_SESSION['admin_media_flash']);
    return is_array($flash) ? $flash : null;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = trim((string) ($_POST['media_action'] ?? ''));
        $adminId = (int) ($currentAdmin['id'] ?? 0);

        switch ($action) {
            case 'save_unboxing':
                admin_media_save_unboxing(
                    $_POST,
                    [
                        'videoFile' => $_FILES['videoFile'] ?? [],
                        'thumbnailFile' => $_FILES['thumbnailFile'] ?? [],
                    ],
                    $adminId
                );
                $setFlash('Unboxing video saved successfully.');
                break;
            case 'save_influencer':
                admin_media_save_influencer(
                    $_POST,
                    [
                        'videoFile' => $_FILES['videoFile'] ?? [],
                        'thumbnailFile' => $_FILES['thumbnailFile'] ?? [],
                    ],
                    $adminId
                );
                $setFlash('Influencer video saved successfully.');
                break;
            case 'delete_media':
                admin_media_delete((string) ($_POST['media_type'] ?? ''), (int) ($_POST['media_id'] ?? 0));
                $setFlash('Media item deleted successfully.');
                break;
            default:
                throw new RuntimeException('Unknown media action.');
        }
    } catch (Throwable $exception) {
        $setFlash($exception->getMessage(), 'error');
    }

    header('Location: /admin/unboxing-influencers.php');
    exit;
}

$flash = $consumeFlash();
$unboxingVideos = admin_media_fetch_unboxing_videos();
$influencerVideos = admin_media_fetch_influencer_videos();
$categories = catalog_fetch_category_options();
$brands = catalog_fetch_brand_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/unboxing-influencers.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content media-page">
        <header class="media-header">
            <h1>Unboxing &amp; Influencer Videos</h1>
        </header>

        <div class="media-tabs">
            <button type="button" class="tab-btn active" data-tab="unboxing">Unboxing Video</button>
            <button type="button" class="tab-btn" data-tab="influencer">Influencer Review</button>
        </div>

        <section class="media-section active" id="unboxing">
            <button type="button" class="btn-upload-main" data-open="unboxingModal">
                <i class="fa-solid fa-upload"></i>
                Upload
            </button>
            <div class="video-grid">
                <?php foreach ($unboxingVideos as $video): ?>
                    <div
                        class="video-card"
                        data-id="<?php echo (int) $video['id']; ?>"
                        data-title="<?php echo htmlspecialchars($video['title']); ?>"
                        data-src="<?php echo htmlspecialchars($video['src']); ?>"
                        data-video-link="<?php echo htmlspecialchars($video['video_link']); ?>"
                        data-thumbnail="<?php echo htmlspecialchars($video['thumbnail_src']); ?>"
                        data-category-id="<?php echo (int) $video['category_id']; ?>"
                        data-brand-id="<?php echo (int) $video['brand_id']; ?>"
                    >
                        <div
                            class="video-thumb<?php echo $video['thumbnail_src'] !== '' ? ' has-thumb' : ''; ?>"
                            role="button"
                            tabindex="0"
                            aria-label="Play video"
                            <?php if ($video['thumbnail_src'] !== ''): ?>
                                style="background-image:url('<?php echo htmlspecialchars($video['thumbnail_src']); ?>')"
                            <?php endif; ?>
                        >
                            <i class="fa-regular fa-circle-play"></i>
                        </div>
                        <div class="video-meta">
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($video['title']); ?></p>
                            <p>Category: <?php echo htmlspecialchars($video['category_name'] !== '' ? $video['category_name'] : '-'); ?></p>
                            <p>Brand: <?php echo htmlspecialchars($video['brand_name'] !== '' ? $video['brand_name'] : '-'); ?></p>
                            <p>Uploaded by (<?php echo htmlspecialchars($video['uploader']); ?>)</p>
                            <p>Uploaded at <?php echo htmlspecialchars($video['uploaded_at']); ?></p>
                        </div>
                        <div class="video-actions">
                            <button type="button" class="icon-btn edit" aria-label="Edit video">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button type="button" class="icon-btn delete" aria-label="Delete video" data-delete-type="unboxing">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="media-section" id="influencer">
            <button type="button" class="btn-upload-main" data-open="influencerModal">
                <i class="fa-solid fa-upload"></i>
                Upload
            </button>
            <div class="video-grid">
                <?php foreach ($influencerVideos as $video): ?>
                    <div
                        class="video-card"
                        data-id="<?php echo (int) $video['id']; ?>"
                        data-title="<?php echo htmlspecialchars($video['title']); ?>"
                        data-influencer="<?php echo htmlspecialchars($video['influencer']); ?>"
                        data-src="<?php echo htmlspecialchars($video['src']); ?>"
                        data-video-link="<?php echo htmlspecialchars($video['video_link']); ?>"
                        data-thumbnail="<?php echo htmlspecialchars($video['thumbnail_src']); ?>"
                    >
                        <div
                            class="video-thumb<?php echo $video['thumbnail_src'] !== '' ? ' has-thumb' : ''; ?>"
                            role="button"
                            tabindex="0"
                            aria-label="Play video"
                            <?php if ($video['thumbnail_src'] !== ''): ?>
                                style="background-image:url('<?php echo htmlspecialchars($video['thumbnail_src']); ?>')"
                            <?php endif; ?>
                        >
                            <i class="fa-regular fa-circle-play"></i>
                        </div>
                        <div class="video-meta">
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($video['title']); ?></p>
                            <p>Influencer Name: <?php echo htmlspecialchars($video['influencer']); ?></p>
                            <p>Uploaded by (<?php echo htmlspecialchars($video['uploader']); ?>)</p>
                            <p>Uploaded at <?php echo htmlspecialchars($video['uploaded_at']); ?></p>
                        </div>
                        <div class="video-actions">
                            <button type="button" class="icon-btn edit" aria-label="Edit video">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button type="button" class="icon-btn delete" aria-label="Delete video" data-delete-type="influencer">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="modal-overlay" id="unboxingModal" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 class="modal-title">Add Unboxing Video</h2>
                <form class="upload-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="media_action" value="save_unboxing">
                    <input type="hidden" name="media_id" value="0">
                    <label>
                        <span>Video Title</span>
                        <input type="text" name="videoTitle">
                    </label>
                    <label>
                        <span>Category</span>
                        <select name="categoryId">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Brand</span>
                        <select name="brandId">
                            <option value="">Select brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo (int) $brand['brand_id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="upload-field">
                        <span>Upload Video:</span>
                        <input type="file" id="unboxingVideoFile" name="videoFile" accept="video/*" hidden>
                        <button type="button" class="upload-box" data-upload="unboxingVideoFile">
                            <i class="fa-solid fa-upload"></i>
                        </button>
                        <div class="upload-preview" id="unboxingVideoPreview">
                            <img alt="Video thumbnail">
                            <div class="upload-actions">
                                <button type="button" class="icon-btn reupload" data-upload="unboxingVideoFile" aria-label="Reupload">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="or-text">OR</div>
                    <label>
                        <span>Upload as Link:</span>
                        <input type="text" name="videoLink">
                    </label>
                    <div class="upload-field">
                        <span>Upload Thumbnail:</span>
                        <input type="file" id="unboxingThumbFile" name="thumbnailFile" accept="image/*" hidden>
                        <button type="button" class="upload-box" data-upload="unboxingThumbFile">
                            <i class="fa-solid fa-upload"></i>
                        </button>
                        <div class="upload-preview" id="unboxingThumbPreview">
                            <img alt="Thumbnail">
                            <div class="upload-actions">
                                <button type="button" class="icon-btn reupload" data-upload="unboxingThumbFile" aria-label="Reupload">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Upload</button>
                        <button type="button" class="btn-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="influencerModal" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 class="modal-title">Add Influencer Video</h2>
                <form class="upload-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="media_action" value="save_influencer">
                    <input type="hidden" name="media_id" value="0">
                    <label>
                        <span>Video Title</span>
                        <input type="text" name="videoTitle">
                    </label>
                    <label>
                        <span>Influencer Name:</span>
                        <input type="text" name="influencerName">
                    </label>
                    <div class="upload-field">
                        <span>Upload as File:</span>
                        <input type="file" id="influencerVideoFile" name="videoFile" accept="video/*" hidden>
                        <button type="button" class="upload-box" data-upload="influencerVideoFile">
                            <i class="fa-solid fa-upload"></i>
                        </button>
                        <div class="upload-preview" id="influencerVideoPreview">
                            <img alt="Video thumbnail">
                            <div class="upload-actions">
                                <button type="button" class="icon-btn reupload" data-upload="influencerVideoFile" aria-label="Reupload">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="or-text">OR</div>
                    <label>
                        <span>Upload as Link:</span>
                        <input type="text" name="videoLink">
                    </label>
                    <div class="upload-field">
                        <span>Upload Thumbnail:</span>
                        <input type="file" id="influencerThumbFile" name="thumbnailFile" accept="image/*" hidden>
                        <button type="button" class="upload-box" data-upload="influencerThumbFile">
                            <i class="fa-solid fa-upload"></i>
                        </button>
                        <div class="upload-preview" id="influencerThumbPreview">
                            <img alt="Thumbnail">
                            <div class="upload-actions">
                                <button type="button" class="icon-btn reupload" data-upload="influencerThumbFile" aria-label="Reupload">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Upload</button>
                        <button type="button" class="btn-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="unboxingEditModal" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 class="modal-title">Edit Unboxing Video</h2>
                <form class="upload-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="media_action" value="save_unboxing">
                    <input type="hidden" name="media_id" id="unboxingEditId" value="0">
                    <label>
                        <span>Video Title</span>
                        <input type="text" name="videoTitle" id="unboxingEditTitle">
                    </label>
                    <label>
                        <span>Category</span>
                        <select name="categoryId" id="unboxingEditCategory">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Brand</span>
                        <select name="brandId" id="unboxingEditBrand">
                            <option value="">Select brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo (int) $brand['brand_id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="upload-field">
                        <span>Upload Video:</span>
                        <input type="file" id="unboxingEditFile" name="videoFile" accept="video/*" hidden>
                        <button type="button" class="upload-box" data-upload="unboxingEditFile">
                            <i class="fa-solid fa-upload"></i>
                        </button>
                        <div class="upload-preview" id="unboxingEditPreview">
                            <img alt="Video thumbnail">
                            <div class="upload-actions">
                                <button type="button" class="icon-btn reupload" data-upload="unboxingEditFile" aria-label="Reupload">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="or-text">OR</div>
                    <label>
                        <span>Upload as Link:</span>
                        <input type="text" name="videoLink" id="unboxingEditLink">
                    </label>
                    <div class="upload-field">
                        <span>Upload Thumbnail:</span>
                        <input type="file" id="unboxingEditThumbFile" name="thumbnailFile" accept="image/*" hidden>
                        <button type="button" class="upload-box" data-upload="unboxingEditThumbFile">
                            <i class="fa-solid fa-upload"></i>
                        </button>
                        <div class="upload-preview" id="unboxingEditThumbPreview">
                            <img alt="Thumbnail">
                            <div class="upload-actions">
                                <button type="button" class="icon-btn reupload" data-upload="unboxingEditThumbFile" aria-label="Reupload">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Upload</button>
                        <button type="button" class="btn-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="influencerEditModal" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 class="modal-title">Edit Influencer Video</h2>
                <form class="upload-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="media_action" value="save_influencer">
                    <input type="hidden" name="media_id" id="influencerEditId" value="0">
                    <label>
                        <span>Video Title</span>
                        <input type="text" name="videoTitle" id="influencerEditTitle">
                    </label>
                    <label>
                        <span>Influencer Name:</span>
                        <input type="text" name="influencerName" id="influencerEditName">
                    </label>
                    <div class="upload-field">
                        <span>Upload as File:</span>
                        <input type="file" id="influencerEditFile" name="videoFile" accept="video/*" hidden>
                        <button type="button" class="upload-box" data-upload="influencerEditFile">
                            <i class="fa-solid fa-upload"></i>
                        </button>
                        <div class="upload-preview" id="influencerEditPreview">
                            <img alt="Video thumbnail">
                            <div class="upload-actions">
                                <button type="button" class="icon-btn reupload" data-upload="influencerEditFile" aria-label="Reupload">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="or-text">OR</div>
                    <label>
                        <span>Upload as Link:</span>
                        <input type="text" name="videoLink" id="influencerEditLink">
                    </label>
                    <div class="upload-field">
                        <span>Upload Thumbnail:</span>
                        <input type="file" id="influencerEditThumbFile" name="thumbnailFile" accept="image/*" hidden>
                        <button type="button" class="upload-box" data-upload="influencerEditThumbFile">
                            <i class="fa-solid fa-upload"></i>
                        </button>
                        <div class="upload-preview" id="influencerEditThumbPreview">
                            <img alt="Thumbnail">
                            <div class="upload-actions">
                                <button type="button" class="icon-btn reupload" data-upload="influencerEditThumbFile" aria-label="Reupload">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                                <button type="button" class="icon-btn delete" aria-label="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Upload</button>
                        <button type="button" class="btn-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="videoPlayerModal" aria-hidden="true">
            <div class="modal-card video-player-card" role="dialog" aria-modal="true" aria-label="Video player">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div id="adminVideoPlayer"></div>
            </div>
        </div>
    </main>

    <form id="adminMediaDeleteForm" method="post" hidden>
        <input type="hidden" name="media_action" value="delete_media">
        <input type="hidden" name="media_type" value="">
        <input type="hidden" name="media_id" value="">
    </form>

    <script>
        window.adminMediaFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/unboxing-influencers.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>
