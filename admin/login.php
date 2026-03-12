<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/services/admin_auth.php';

if (admin_auth_current_admin()) {
        header('Location: ' . admin_auth_normalize_redirect($_GET['redirect_to'] ?? app_path('/admin/index.php'), app_path('/admin/index.php')));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_auth_login($_POST);
        header('Location: ' . admin_auth_normalize_redirect($_POST['redirect_to'] ?? app_path('/admin/index.php'), app_path('/admin/index.php')));
        exit;
    } catch (Throwable $exception) {
        admin_auth_set_flash($exception->getMessage());
        header('Location: ' . app_path('/admin/login.php?redirect_to=' . rawurlencode((string) ($_POST['redirect_to'] ?? app_path('/admin/index.php')))));
        exit;
    }
}

$flash = admin_auth_consume_flash();
$redirectTo = admin_auth_normalize_redirect($_GET['redirect_to'] ?? app_path('/admin/index.php'), app_path('/admin/index.php'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/auth.css')); ?>">
</head>
<body class="admin-page auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <div class="auth-brand">
                <img src="<?php echo htmlspecialchars(app_path('/storage/uploads/contents/logo.png')); ?>" alt="ZYPP Camera House">
                <h1>Admin Login</h1>
                <p>Use your admin account to access the dashboard.</p>
            </div>

            <form class="auth-form" method="post" action="<?php echo htmlspecialchars(app_path('/admin/login.php')); ?>">
                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectTo); ?>">

                <label class="auth-field">
                    <span>Email</span>
                    <input type="email" name="email" autocomplete="email" required>
                </label>

                <label class="auth-field">
                    <span>Password</span>
                    <div class="password-field">
                        <input type="password" name="password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" aria-label="Show password" data-password-toggle>
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </label>

                <a class="auth-secondary-link" href="<?php echo htmlspecialchars(app_path('/admin/forgot-password.php')); ?>">Forgot password?</a>

                <?php if (is_array($flash) && !empty($flash['message'])): ?>
                    <p class="auth-feedback <?php echo ($flash['type'] ?? 'error') === 'success' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars((string) $flash['message']); ?>
                    </p>
                <?php endif; ?>

                <button type="submit" class="auth-submit">Login</button>
            </form>
        </section>
    </main>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/auth.js')); ?>"></script>
</body>
</html>
