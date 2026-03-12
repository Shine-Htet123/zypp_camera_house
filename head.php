    <?php
    require_once __DIR__ . '/config/customer_bootstrap.php';
    require_once __DIR__ . '/config/app.php';

    $loadingCssVersion = @filemtime(app_project_path('assets/css/loading.css')) ?: time();
    $navbarCssVersion = @filemtime(app_project_path('assets/css/navbar.css')) ?: time();
    $footerCssVersion = @filemtime(app_project_path('assets/css/footer.css')) ?: time();
    $loadingJsVersion = @filemtime(app_project_path('assets/js/loading.js')) ?: time();
    $scrollRevealJsVersion = @filemtime(app_project_path('assets/js/scroll-reveal.js')) ?: time();
    $navbarJsVersion = @filemtime(app_project_path('assets/js/navbar.js')) ?: time();
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
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
    <title>ZYPP Camera House</title>
