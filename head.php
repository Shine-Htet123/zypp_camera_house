    <?php
    require_once __DIR__ . '/config/customer_bootstrap.php';
    require_once __DIR__ . '/config/app.php';
    require_once __DIR__ . '/app/services/seo.php';

    $loadingCssVersion = @filemtime(app_project_path('assets/css/loading.css')) ?: time();
    $navbarCssVersion = @filemtime(app_project_path('assets/css/navbar.css')) ?: time();
    $footerCssVersion = @filemtime(app_project_path('assets/css/footer.css')) ?: time();
    $loadingJsVersion = @filemtime(app_project_path('assets/js/loading.js')) ?: time();
    $scrollRevealJsVersion = @filemtime(app_project_path('assets/js/scroll-reveal.js')) ?: time();
    $navbarJsVersion = @filemtime(app_project_path('assets/js/navbar.js')) ?: time();
    $seoMeta = seo_meta_payload([
        'title' => $seoTitle ?? '',
        'description' => $seoDescription ?? '',
        'canonical' => $seoCanonical ?? '',
        'image' => $seoImage ?? '',
        'type' => $seoType ?? 'website',
        'noindex' => $seoNoIndex ?? seo_auto_noindex(),
    ]);
    $seoStructuredDataList = seo_structured_data_list($seoStructuredData ?? []);
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($seoMeta['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seoMeta['description']); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($seoMeta['robots']); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($seoMeta['canonical']); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars(seo_site_name()); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seoMeta['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seoMeta['description']); ?>">
    <meta property="og:type" content="<?php echo htmlspecialchars($seoMeta['type']); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($seoMeta['canonical']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($seoMeta['image']); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($seoMeta['title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seoMeta['description']); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($seoMeta['image']); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars(app_path('/assets/images/browser-icon.png')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/style.css')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/loading.css?v=' . (int) $loadingCssVersion)); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/navbar.css?v=' . (int) $navbarCssVersion)); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/footer.css?v=' . (int) $footerCssVersion)); ?>">
    <script>
        window.__APP_BASE_PATH__ = <?php echo json_encode(app_base_path(), JSON_UNESCAPED_SLASHES); ?>;
        window.appPath = window.appPath || function (path) {
            const base = String(window.__APP_BASE_PATH__ || '').replace(/\/+$/, '');
            const target = String(path || '/').replace(/^\/+/, '');
            return base + '/' + target;
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="<?php echo htmlspecialchars(app_path('/assets/js/loading.js?v=' . (int) $loadingJsVersion)); ?>" defer></script>
    <script src="<?php echo htmlspecialchars(app_path('/assets/js/scroll-reveal.js?v=' . (int) $scrollRevealJsVersion)); ?>" defer></script>
    <script src="<?php echo htmlspecialchars(app_path('/assets/js/navbar.js?v=' . (int) $navbarJsVersion)); ?>" defer></script>
    <?php $tidioPublicKey = env('TIDIO_PUBLIC_KEY', ''); ?>
    <?php if ($tidioPublicKey !== ''): ?>
        <script src="https://code.tidio.co/<?php echo htmlspecialchars($tidioPublicKey); ?>.js" async></script>
    <?php endif; ?>
    <?php foreach ($seoStructuredDataList as $schema): ?>
        <script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?></script>
    <?php endforeach; ?>
