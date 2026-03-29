    <?php require_once __DIR__ . '/../config/app.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <?php
    $baseCssVersion = @filemtime(app_project_path('assets/css/style.css')) ?: time();
    $adminCssVersion = @filemtime(app_project_path('admin/assets/css/style.css')) ?: time();
    ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars(app_path('/assets/images/browser-icon.png')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/style.css?v=' . (int) $baseCssVersion)); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/style.css?v=' . (int) $adminCssVersion)); ?>">
    <script>
        window.__APP_BASE_PATH__ = <?php echo json_encode(app_base_path(), JSON_UNESCAPED_SLASHES); ?>;
        window.appPath = window.appPath || function (path) {
            const base = String(window.__APP_BASE_PATH__ || '').replace(/\/+$/, '');
            const target = String(path || '/').replace(/^\/+/, '');
            return base + '/' + target;
        };
    </script>
    <script>
        (function () {
            try {
                if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                if (window.sessionStorage.getItem('admin-page-transition') !== 'enter') {
                    return;
                }

                document.documentElement.classList.add('admin-transition-ready', 'admin-page-entering');
            } catch (_) {
                // Ignore transition bootstrap errors.
            }
        }());
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <title>ZYPP Admin Dashboard</title>
