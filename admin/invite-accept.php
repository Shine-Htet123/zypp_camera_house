<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/services/admin_auth.php';

$inviteError = null;
$invite = null;
$formValues = [
    'full_name' => '',
];

try {
    $invite = admin_auth_validate_invite_token((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
} catch (Throwable $exception) {
    $inviteError = $exception->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $invite) {
    $formValues['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    try {
        admin_auth_accept_invite($invite, $_POST);
        header('Location: ' . app_path('/admin/index.php'));
        exit;
    } catch (Throwable $exception) {
        $inviteError = $exception->getMessage();
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
                <h1>Accept Admin Invite</h1>
                <p>Set your password to finish creating the admin account.</p>
            </div>

            <?php if ($inviteError !== null): ?>
                <div class="auth-state error-state">
                    <p><?php echo htmlspecialchars($inviteError); ?></p>
                    <a class="auth-secondary-link" href="<?php echo htmlspecialchars(app_path('/admin/login.php')); ?>">Back to Login</a>
                </div>
            <?php elseif ($invite): ?>
                <div class="invite-summary">
                    <div><strong>Email:</strong> <?php echo htmlspecialchars((string) $invite['email']); ?></div>
                    <div><strong>Role:</strong> <?php echo htmlspecialchars((string) $invite['role']); ?></div>
                </div>

                <form class="auth-form" method="post" action="<?php echo htmlspecialchars(app_path('/admin/invite-accept.php?token=' . rawurlencode((string) $invite['token']))); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars((string) $invite['token']); ?>">

                    <label class="auth-field">
                        <span>Full Name</span>
                        <input type="text" name="full_name" autocomplete="name" value="<?php echo htmlspecialchars($formValues['full_name']); ?>" required>
                    </label>

                    <label class="auth-field">
                        <span>Password</span>
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

                    <?php if ($inviteError !== null): ?>
                        <p class="auth-feedback error"><?php echo htmlspecialchars($inviteError); ?></p>
                    <?php endif; ?>

                    <button type="submit" class="auth-submit">Create Admin Account</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/auth.js')); ?>"></script>
</body>
</html>
