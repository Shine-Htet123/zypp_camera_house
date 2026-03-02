<?php
$unboxingVideos = [
    [
        'id' => 1,
        'title' => 'Video Title',
        'uploader' => 'Admin Name',
        'uploaded_at' => '12:00:00',
        'src' => '../storage/uploads/contents/video/sample_video.mp4',
    ],
    [
        'id' => 2,
        'title' => 'Video Title',
        'uploader' => 'Admin Name',
        'uploaded_at' => '12:00:00',
        'src' => '../storage/uploads/contents/video/sample_video.mp4',
    ],
];

$influencerVideos = [
    [
        'id' => 1,
        'title' => 'Video Title',
        'influencer' => '(Influencer Name)',
        'uploader' => 'Admin Name',
        'uploaded_at' => '12:00:00',
        'src' => '../storage/uploads/contents/video/sample_video.mp4',
    ],
    [
        'id' => 2,
        'title' => 'Video Title',
        'influencer' => '(Influencer Name)',
        'uploader' => 'Admin Name',
        'uploaded_at' => '12:00:00',
        'src' => '../storage/uploads/contents/video/sample_video.mp4',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/unboxing-influencers.css">
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
                    <div class="video-card" data-title="<?php echo htmlspecialchars($video['title']); ?>" data-src="<?php echo htmlspecialchars($video['src']); ?>">
                        <div class="video-thumb" role="button" tabindex="0" aria-label="Play video">
                            <i class="fa-regular fa-circle-play"></i>
                        </div>
                        <div class="video-meta">
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($video['title']); ?></p>
                            <p>Uploaded by (<?php echo htmlspecialchars($video['uploader']); ?>)</p>
                            <p>Uploaded at <?php echo htmlspecialchars($video['uploaded_at']); ?></p>
                        </div>
                        <div class="video-actions">
                            <button type="button" class="icon-btn edit" aria-label="Edit video">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button type="button" class="icon-btn delete" aria-label="Delete video">
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
                    <div class="video-card" data-title="<?php echo htmlspecialchars($video['title']); ?>" data-influencer="<?php echo htmlspecialchars($video['influencer']); ?>" data-src="<?php echo htmlspecialchars($video['src']); ?>">
                        <div class="video-thumb" role="button" tabindex="0" aria-label="Play video">
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
                            <button type="button" class="icon-btn delete" aria-label="Delete video">
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
                <form class="upload-form">
                    <label>
                        <span>Video Title</span>
                        <input type="text" name="videoTitle">
                    </label>
                    <div class="upload-field">
                        <span>Upload Video:</span>
                        <input type="file" id="unboxingVideoFile" accept="video/*" hidden>
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
                        <input type="file" id="unboxingThumbFile" accept="image/*" hidden>
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
                        <button type="button" class="btn-submit">Upload</button>
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
                <form class="upload-form">
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
                        <input type="file" id="influencerVideoFile" accept="video/*" hidden>
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
                        <input type="file" id="influencerThumbFile" accept="image/*" hidden>
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
                        <button type="button" class="btn-submit">Upload</button>
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
                <form class="upload-form">
                    <label>
                        <span>Video Title</span>
                        <input type="text" name="videoTitle" id="unboxingEditTitle">
                    </label>
                    <div class="upload-field">
                        <span>Upload Video:</span>
                        <input type="file" id="unboxingEditFile" accept="video/*" hidden>
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
                        <input type="text" name="videoLink">
                    </label>
                    <div class="upload-field">
                        <span>Upload Thumbnail:</span>
                        <input type="file" id="unboxingEditThumbFile" accept="image/*" hidden>
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
                        <button type="button" class="btn-submit">Upload</button>
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
                <form class="upload-form">
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
                        <input type="file" id="influencerEditFile" accept="video/*" hidden>
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
                        <input type="text" name="videoLink">
                    </label>
                    <div class="upload-field">
                        <span>Upload Thumbnail:</span>
                        <input type="file" id="influencerEditThumbFile" accept="image/*" hidden>
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
                        <button type="button" class="btn-submit">Upload</button>
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
                <video id="adminVideoPlayer" controls playsinline></video>
            </div>
        </div>
    </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/admin/assets/js/unboxing-influencers.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
