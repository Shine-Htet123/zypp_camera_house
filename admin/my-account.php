<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/admin/my_account.php';

$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = trim((string) ($_POST['account_action'] ?? ''));
        if ($action === 'profile_save') {
            $profile = admin_update_profile($_POST);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile information updated.',
                    'profile' => $profile,
                ], JSON_UNESCAPED_SLASHES);
                exit;
            }
        } elseif ($action === 'security_save') {
            $profile = admin_update_security_contacts($_POST);
            $passwordChanged = trim((string) ($_POST['new_password'] ?? '')) !== '';
            $successMessage = $passwordChanged
                ? 'Security information and password updated.'
                : 'Recovery information updated.';

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $successMessage,
                    'profile' => $profile,
                ], JSON_UNESCAPED_SLASHES);
                exit;
            }
        } elseif ($action === 'avatar_upload') {
            $profile = admin_update_avatar($_FILES['avatar'] ?? []);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile picture updated.',
                    'profile' => $profile,
                ], JSON_UNESCAPED_SLASHES);
                exit;
            }
        } else {
            throw new InvalidArgumentException('Invalid request.');
        }

        header('Location: ' . app_path('/admin/my-account.php'));
        exit;
    } catch (Throwable $exception) {
        if ($isAjax) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $exception->getMessage(),
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }

        $_SESSION['admin_my_account_flash'] = [
            'type' => 'error',
            'message' => $exception->getMessage(),
        ];
        header('Location: ' . app_path('/admin/my-account.php'));
        exit;
    }
}

$adminProfile = admin_fetch_current_profile();
admin_my_account_sync_session($adminProfile);

$flash = $_SESSION['admin_my_account_flash'] ?? null;
unset($_SESSION['admin_my_account_flash']);

$createdAt = strtotime((string) $adminProfile['created_at']);
$invitedDate = $createdAt ? date('d.m.Y', $createdAt) : '-';
$invitedTime = $createdAt ? date('H:i:s', $createdAt) : '-';
$lastPasswordChangedAt = !empty($adminProfile['last_password_changed_at']) ? strtotime((string) $adminProfile['last_password_changed_at']) : false;

$securityProfile = [
    'password_mask' => '************',
    'recovery_email' => $adminProfile['recovery_email'],
    'recovery_phone' => $adminProfile['recovery_phone'],
    'last_changed_date' => $lastPasswordChangedAt ? date('d.m.Y', $lastPasswordChangedAt) : '-',
    'last_changed_time' => $lastPasswordChangedAt ? date('H:i:s', $lastPasswordChangedAt) : '-',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/my-account.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-account">
        <div class="account-header">
            <h1>My Account</h1>
        </div>

        <section class="account-hero">
            <label class="account-avatar avatar-upload-trigger" for="avatarInput" aria-label="Upload profile picture">
                <img class="avatar-image" src="<?php echo htmlspecialchars($adminProfile['avatar']); ?>" alt="Admin Profile" <?php echo empty($adminProfile['avatar']) ? 'hidden' : ''; ?>>
                <?php if (empty($adminProfile['avatar'])): ?>
                    <div class="avatar-placeholder">
                        <i class="fa-regular fa-user"></i>
                    </div>
                <?php else: ?>
                    <div class="avatar-placeholder" style="display:none;">
                        <i class="fa-regular fa-user"></i>
                    </div>
                <?php endif; ?>
                <span class="avatar-upload-overlay">
                    <i class="fa-solid fa-camera"></i>
                </span>
            </label>
            <input class="avatar-input" type="file" id="avatarInput" accept="image/*" hidden>
        </section>

        <section class="profile-section">
            <div class="section-header">
                <span>Profile</span>
                <button type="button" class="edit-btn profile-edit-btn" aria-label="Edit profile">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
            </div>

            <div class="profile-grid">
                <div class="profile-row">
                    <span class="label">Full Name:</span>
                    <span class="value" data-profile-display="full_name"><?php echo htmlspecialchars($adminProfile['full_name']); ?></span>
                </div>
                <div class="profile-row">
                    <span class="label">Role:</span>
                    <span class="value role" data-profile-display="role"><?php echo htmlspecialchars($adminProfile['role']); ?></span>
                </div>
                <div class="profile-row">
                    <span class="label">Phone:</span>
                    <span class="value" data-profile-display="phone"><?php echo htmlspecialchars($adminProfile['phone']); ?></span>
                </div>
                <div class="profile-row">
                    <span class="label">Email:</span>
                    <span class="value link" data-profile-display="email"><?php echo htmlspecialchars($adminProfile['email']); ?></span>
                </div>
                <div class="profile-row">
                    <span class="label">Address:</span>
                    <span class="value" data-profile-display="address"><?php echo htmlspecialchars($adminProfile['address']); ?></span>
                </div>
                <div class="profile-row">
                    <span class="label">Township:</span>
                    <span class="value" data-profile-display="township"><?php echo htmlspecialchars($adminProfile['township']); ?></span>
                </div>
                <div class="profile-row">
                    <span class="label">City:</span>
                    <span class="value" data-profile-display="city"><?php echo htmlspecialchars($adminProfile['city']); ?></span>
                </div>
                <div class="profile-row">
                    <span class="label">Invited Since:</span>
                    <span class="value">
                        <?php echo htmlspecialchars($invitedDate); ?>
                        <span class="time"><?php echo htmlspecialchars($invitedTime); ?></span>
                    </span>
                </div>
            </div>
        </section>

        <section class="profile-section security-section">
            <div class="section-header">
                <span>Security</span>
                <button type="button" class="edit-btn security-edit-btn" aria-label="Edit security settings">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
            </div>

            <div class="settings-list">
                <div class="settings-row">
                    <span class="settings-label">Password</span>
                    <span class="settings-value" data-security-display="password_mask"><?php echo htmlspecialchars($securityProfile['password_mask']); ?></span>
                </div>
                <div class="settings-row">
                    <span class="settings-label">Recovery Email</span>
                    <span class="settings-value email" data-security-display="recovery_email"><?php echo htmlspecialchars($securityProfile['recovery_email']); ?></span>
                </div>
                <div class="settings-row">
                    <span class="settings-label">Recovery Phone</span>
                    <span class="settings-value" data-security-display="recovery_phone"><?php echo htmlspecialchars($securityProfile['recovery_phone']); ?></span>
                </div>
                <div class="settings-row meta">
                    <span class="settings-label">Last Password Change</span>
                    <span class="settings-value">
                        <span data-security-display="last_changed_date"><?php echo htmlspecialchars($securityProfile['last_changed_date']); ?></span>
                        <span class="time" data-security-display="last_changed_time"><?php echo htmlspecialchars($securityProfile['last_changed_time']); ?></span>
                    </span>
                </div>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="profileModal" aria-hidden="true">
        <div class="modal-card profile-modal" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
            <button type="button" class="modal-close" aria-label="Close profile editor">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2 id="profileModalTitle">Edit Profile Information</h2>
            <p class="modal-subtitle">Update your account details.</p>

            <form class="profile-form" id="profileForm" action="<?php echo htmlspecialchars(app_path('/admin/my-account.php')); ?>" method="post">
                <input type="hidden" name="account_action" value="profile_save">
                <div class="profile-field">
                    <label for="profileFullName">Full Name</label>
                    <input type="text" id="profileFullName" name="full_name" value="<?php echo htmlspecialchars($adminProfile['full_name']); ?>">
                </div>
                <div class="profile-field">
                    <label for="profileRole">Role</label>
                    <input type="text" id="profileRole" name="role" value="<?php echo htmlspecialchars($adminProfile['role']); ?>" readonly>
                </div>
                <div class="profile-field">
                    <label for="profilePhone">Phone</label>
                    <input type="tel" id="profilePhone" name="phone" value="<?php echo htmlspecialchars($adminProfile['phone']); ?>">
                </div>
                <div class="profile-field">
                    <label for="profileEmail">Email</label>
                    <input type="email" id="profileEmail" name="email" value="<?php echo htmlspecialchars($adminProfile['email']); ?>">
                </div>
                <div class="profile-field">
                    <label for="profileAddress">Address</label>
                    <input type="text" id="profileAddress" name="address" value="<?php echo htmlspecialchars($adminProfile['address']); ?>">
                </div>
                <div class="profile-field">
                    <label for="profileTownship">Township</label>
                    <input type="text" id="profileTownship" name="township" value="<?php echo htmlspecialchars($adminProfile['township']); ?>">
                </div>
                <div class="profile-field">
                    <label for="profileCity">City</label>
                    <input type="text" id="profileCity" name="city" value="<?php echo htmlspecialchars($adminProfile['city']); ?>">
                </div>

                <div class="profile-feedback" id="profileFeedback" aria-live="polite"></div>

                <div class="modal-actions">
                    <button type="button" class="action-btn cancel-btn profile-cancel-btn">Cancel</button>
                    <button type="submit" class="action-btn save-btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="securityModal" aria-hidden="true">
        <div class="modal-card security-modal" role="dialog" aria-modal="true" aria-labelledby="securityModalTitle">
            <button type="button" class="modal-close" aria-label="Close security editor">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2 id="securityModalTitle">Edit Security Information</h2>
            <p class="modal-subtitle">Update your password recovery details and change your password securely.</p>

            <form class="security-form" id="securityForm" action="<?php echo htmlspecialchars(app_path('/admin/my-account.php')); ?>" method="post">
                <input type="hidden" name="account_action" value="security_save">
                <div class="security-field">
                    <label for="securityRecoveryEmail">Recovery Email</label>
                    <input type="email" id="securityRecoveryEmail" name="recovery_email" value="<?php echo htmlspecialchars($securityProfile['recovery_email']); ?>">
                </div>
                <div class="security-field">
                    <label for="securityRecoveryPhone">Recovery Phone</label>
                    <input type="tel" id="securityRecoveryPhone" name="recovery_phone" value="<?php echo htmlspecialchars($securityProfile['recovery_phone']); ?>">
                </div>
                <div class="security-field">
                    <label for="securityCurrentPassword">Current Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="securityCurrentPassword" name="current_password" placeholder="Enter current password">
                        <button type="button" class="password-toggle" data-password-toggle="securityCurrentPassword" aria-label="Show password" aria-pressed="false">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="security-field">
                    <label for="securityNewPassword">New Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="securityNewPassword" name="new_password" placeholder="Enter new password">
                        <button type="button" class="password-toggle" data-password-toggle="securityNewPassword" aria-label="Show password" aria-pressed="false">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="security-field">
                    <label for="securityConfirmPassword">Confirm New Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="securityConfirmPassword" name="confirm_password" placeholder="Confirm new password">
                        <button type="button" class="password-toggle" data-password-toggle="securityConfirmPassword" aria-label="Show password" aria-pressed="false">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="security-feedback" id="securityFeedback" aria-live="polite"></div>

                <div class="modal-actions">
                    <button type="button" class="action-btn cancel-btn security-cancel-btn">Cancel</button>
                    <button type="submit" class="action-btn save-btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.adminMyAccountFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/my-account.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

