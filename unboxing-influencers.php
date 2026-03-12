<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('head.php'); ?>
    <link rel="stylesheet" href="./assets/css/unboxing-influencers.css">
</head>
<body>
    <?php include('navbar.php'); ?>

    <?php
        require_once __DIR__ . '/database/media.php';

        $mediaData = media_fetch_customer_filter_options();
        $categories = $mediaData['categories'];
        $brands = $mediaData['brands'];
        $unboxingVideos = $mediaData['unboxing'];
        $influencerVideos = $mediaData['influencer'];
    ?>

    <main class="media-page">
        <div class="media-tabs">
            <button class="tab-btn active" data-tab="unboxing">Unboxing Videos</button>
            <button class="tab-btn" data-tab="influencer">Influencer Reviews</button>
        </div>

        <div class="media-banner" id="media-banner">
            Unbox the hype - watch creators try our products!
        </div>

        <section class="media-panel active" id="unboxing-panel">
            <aside class="filter-panel">
                <div class="filter-group open" data-filter="category">
                    <button type="button" class="filter-header">
                        Category
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="filter-body" id="category-filters">
                        <?php foreach ($categories as $cat): ?>
                            <button
                                type="button"
                                class="filter-option <?php echo $cat === 'All' ? 'active' : ''; ?>"
                                data-type="category"
                                data-value="<?php echo htmlspecialchars($cat); ?>"
                            >
                                <?php echo htmlspecialchars($cat); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="filter-group open" data-filter="brand">
                    <button type="button" class="filter-header">
                        Brand
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="filter-body" id="brand-filters">
                        <?php foreach ($brands as $brand): ?>
                            <button
                                type="button"
                                class="filter-option <?php echo $brand === 'All' ? 'active' : ''; ?>"
                                data-type="brand"
                                data-value="<?php echo htmlspecialchars($brand); ?>"
                            >
                                <?php echo htmlspecialchars($brand); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <div class="media-grid" id="unboxing-grid">
                <?php foreach ($unboxingVideos as $video): ?>
                    <div
                        class="video-card"
                        data-category="<?php echo htmlspecialchars($video['category']); ?>"
                        data-brand="<?php echo htmlspecialchars($video['brand']); ?>"
                        data-video="<?php echo htmlspecialchars($video['video'] ?? ''); ?>"
                    >
                        <div
                            class="video-thumb <?php echo !empty($video['thumbnail']) ? 'has-image' : ''; ?>"
                            <?php if (!empty($video['thumbnail'])): ?>
                                style="background-image:url('<?php echo htmlspecialchars($video['thumbnail']); ?>')"
                            <?php endif; ?>
                        >
                            <div class="play"><i class="fa-solid fa-play"></i></div>
                        </div>
                        <div class="video-meta">
                            <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="media-panel influencer" id="influencer-panel">
            <div class="media-grid" id="influencer-grid">
                <?php foreach ($influencerVideos as $video): ?>
                    <div class="video-card" data-video="<?php echo htmlspecialchars($video['video'] ?? ''); ?>">
                        <div
                            class="video-thumb <?php echo !empty($video['thumbnail']) ? 'has-image' : ''; ?>"
                            <?php if (!empty($video['thumbnail'])): ?>
                                style="background-image:url('<?php echo htmlspecialchars($video['thumbnail']); ?>')"
                            <?php endif; ?>
                        >
                            <div class="play"><i class="fa-solid fa-play"></i></div>
                        </div>
                        <div class="video-meta">
                            <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                            <?php $influencerName = $video['influencer'] ?? $video['author'] ?? ''; ?>
                            <?php if ($influencerName !== ''): ?>
                                <span>By <?php echo htmlspecialchars($influencerName); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <div class="video-modal" id="video-modal" aria-hidden="true">
        <div class="video-modal-content" role="dialog" aria-modal="true" aria-labelledby="video-modal-title">
            <div class="video-modal-header">
                <h3 class="video-modal-title" id="video-modal-title">Video</h3>
                <button class="video-modal-close" type="button" aria-label="Close">&times;</button>
            </div>
            <div class="video-modal-body" id="video-modal-body"></div>
        </div>
    </div>

    <?php include('./footer.php') ?>

    <script src="./assets/js/unboxing-influencers.js"></script>
</body>
</html>
