<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/services/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_auth_request_password_reset($_POST);
        admin_auth_set_flash('If an admin account matches that email, a reset link has been sent.', 'success');
        header('Location: ' . app_path('/admin/forgot-password.php'));
        exit;
    } catch (Throwable $exception) {
        admin_auth_set_flash($exception->getMessage());
        header('Location: ' . app_path('/admin/forgot-password.php'));
        exit;
    }
}

$flash = admin_auth_consume_flash();
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
                <h1>Forgot Password</h1>
                <p>Enter your admin email and we will send a reset link.</p>
            </div>

            <form class="auth-form" method="post" action="<?php echo htmlspecialchars(app_path('/admin/forgot-password.php')); ?>">
                <label class="auth-field">
                    <span>Email</span>
                    <input type="email" name="email" autocomplete="email" required>
                </label>

                <?php if (is_array($flash) && !empty($flash['message'])): ?>
                    <p class="auth-feedback <?php echo ($flash['type'] ?? 'error') === 'success' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars((string) $flash['message']); ?>
                    </p>
                <?php endif; ?>

                <button type="submit" class="auth-submit">Send Reset Link</button>
                <a class="auth-secondary-link" href="<?php echo htmlspecialchars(app_path('/admin/login.php')); ?>">Back to Login</a>
            </form>
        </section>
    </main>
</body>
</html>
