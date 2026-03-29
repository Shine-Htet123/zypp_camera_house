<?php
require_once __DIR__ . '/config/app.php';
$loadingLogoPath = app_path('/storage/uploads/contents/logo.png');
?>
<div class="loading-screen" aria-hidden="true">
    <div class="loading-box">
        <div class="orbit-loader orbit-loader--page" aria-hidden="true">
            <span class="orbit-loader__core">
                <img src="<?php echo htmlspecialchars($loadingLogoPath); ?>" alt="ZYPP Camera House">
            </span>
        </div>
    </div>
</div>
