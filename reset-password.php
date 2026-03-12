<?php

require_once __DIR__ . '/app/services/customer_auth.php';
require_once __DIR__ . '/config/app.php';

$selector = trim((string) ($_GET['selector'] ?? $_POST['selector'] ?? ''));
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$errorMessage = '';
$tokenData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        customer_auth_reset_password($_POST);
        customer_auth_set_flash('login', 'Password reset successful. Please log in with your new password.', 'success');
        header('Location: ' . app_path('/index.php'));
        exit;
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}

if ($errorMessage === '') {
    try {
        $tokenData = customer_auth_validate_password_reset_token($selector, $token);
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/password-reset.css')); ?>">
</head>
<body class="password-reset-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="password-reset-shell">
        <section class="password-reset-card">
            <h1>Reset Password</h1>
            <?php if ($errorMessage !== ''): ?>
                <p class="password-reset-feedback error"><?php echo htmlspecialchars($errorMessage); ?></p>
                <a class="password-reset-link" href="<?php echo htmlspecialchars(app_path('/index.php')); ?>">Back to Home</a>
            <?php else: ?>
                <p class="password-reset-subtitle">Set a new password for <?php echo htmlspecialchars((string) ($tokenData['email'] ?? 'your account')); ?>.</p>
                <form method="post" class="password-reset-form">
                    <input type="hidden" name="selector" value="<?php echo htmlspecialchars($selector); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <label class="password-reset-field">
                        <span>New Password</span>
                        <div class="password-reset-password-field">
                            <input type="password" name="password" required>
                            <button type="button" class="password-reset-toggle" aria-label="Show password" data-password-toggle>
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </label>

                    <label class="password-reset-field">
                        <span>Confirm Password</span>
                        <div class="password-reset-password-field">
                            <input type="password" name="confirm_password" required>
                            <button type="button" class="password-reset-toggle" aria-label="Show password" data-password-toggle>
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </label>

                    <button type="submit" class="password-reset-submit">Reset Password</button>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
    <script src="<?php echo htmlspecialchars(app_path('/assets/js/password-reset.js')); ?>"></script>
</body>
</html>
