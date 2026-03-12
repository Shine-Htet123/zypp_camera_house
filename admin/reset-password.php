<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/services/admin_auth.php';

$selector = trim((string) ($_GET['selector'] ?? $_POST['selector'] ?? ''));
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenData = null;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_auth_reset_password($_POST);
        admin_auth_set_flash('Password reset successful. Please log in with your new password.', 'success');
        header('Location: ' . app_path('/admin/login.php'));
        exit;
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}

if ($errorMessage === '') {
    try {
        $tokenData = admin_auth_validate_password_reset_token($selector, $token);
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}
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
                <h1>Reset Admin Password</h1>
                <p>Set a new password for <?php echo htmlspecialchars((string) ($tokenData['email'] ?? 'your admin account')); ?>.</p>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <p class="auth-feedback error"><?php echo htmlspecialchars($errorMessage); ?></p>
                <a class="auth-secondary-link" href="<?php echo htmlspecialchars(app_path('/admin/login.php')); ?>">Back to Login</a>
            <?php else: ?>
                <form class="auth-form" method="post" action="<?php echo htmlspecialchars(app_path('/admin/reset-password.php')); ?>">
                    <input type="hidden" name="selector" value="<?php echo htmlspecialchars($selector); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <label class="auth-field">
                        <span>New Password</span>
                        <div class="password-field">
                            <input type="password" name="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" aria-label="Show password" data-password-toggle>
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </label>

                    <label class="auth-field">
                        <span>Confirm Password</span>
                        <div class="password-field">
                            <input type="password" name="confirm_password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" aria-label="Show password" data-password-toggle>
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </label>

                    <button type="submit" class="auth-submit">Reset Password</button>
                    <a class="auth-secondary-link" href="<?php echo htmlspecialchars(app_path('/admin/login.php')); ?>">Back to Login</a>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/auth.js')); ?>"></script>
</body>
</html>
