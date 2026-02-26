<?php
$adminProfile = [
    'full_name' => 'John Doe',
    'role' => 'Super-admin',
    'phone' => '09123456789',
    'email' => 'example@email.com',
    'address' => 'No. 96, Pyay Road',
    'township' => 'Kamaryut',
    'city' => 'Yangon',
    'invited_date' => '12.02.2025',
    'invited_time' => '10:00:00',
    'avatar' => '',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/my-account.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content admin-account">
        <div class="account-header">
            <h1>My Account</h1>
        </div>

        <section class="account-hero">
            <div class="account-avatar">
                <img class="avatar-image" src="<?php echo htmlspecialchars($adminProfile['avatar']); ?>" alt="Admin Profile" <?php echo empty($adminProfile['avatar']) ? 'hidden' : ''; ?>>
                <?php if (!empty($adminProfile['avatar'])): ?>
                    <?php /* Image rendered above for JS preview */ ?>
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <i class="fa-regular fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="upload-btn">
                <i class="fa-solid fa-upload"></i>
                Upload
            </button>
            <input class="avatar-input" type="file" id="avatarInput" accept="image/*" hidden>
        </section>

        <section class="profile-section">
            <div class="section-header">
                <span>Profile</span>
                <button type="button" class="edit-btn" aria-label="Edit profile">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
            </div>

            <form class="profile-grid" action="#" method="post">
                <div class="profile-row">
                    <span class="label">Full Name:</span>
                    <input class="profile-input" type="text" name="full_name" value="<?php echo htmlspecialchars($adminProfile['full_name']); ?>" disabled>
                </div>
                <div class="profile-row">
                    <span class="label">Role:</span>
                    <input class="profile-input role" type="text" name="role" value="<?php echo htmlspecialchars($adminProfile['role']); ?>" disabled>
                </div>
                <div class="profile-row">
                    <span class="label">Phone:</span>
                    <input class="profile-input" type="tel" name="phone" value="<?php echo htmlspecialchars($adminProfile['phone']); ?>" disabled>
                </div>
                <div class="profile-row">
                    <span class="label">Email:</span>
                    <input class="profile-input link" type="email" name="email" value="<?php echo htmlspecialchars($adminProfile['email']); ?>" disabled>
                </div>
                <div class="profile-row">
                    <span class="label">Address:</span>
                    <input class="profile-input" type="text" name="address" value="<?php echo htmlspecialchars($adminProfile['address']); ?>" disabled>
                </div>
                <div class="profile-row">
                    <span class="label">Township:</span>
                    <input class="profile-input" type="text" name="township" value="<?php echo htmlspecialchars($adminProfile['township']); ?>" disabled>
                </div>
                <div class="profile-row">
                    <span class="label">City:</span>
                    <input class="profile-input" type="text" name="city" value="<?php echo htmlspecialchars($adminProfile['city']); ?>" disabled>
                </div>
                <div class="profile-row">
                    <span class="label">Invited Since:</span>
                    <span class="value">
                        <?php echo htmlspecialchars($adminProfile['invited_date']); ?>
                        <span class="time"><?php echo htmlspecialchars($adminProfile['invited_time']); ?></span>
                    </span>
                </div>
                <div class="profile-actions">
                    <button type="button" class="action-btn cancel-btn">Cancel</button>
                    <button type="submit" class="action-btn save-btn">Save</button>
                </div>
            </form>
        </section>
    </main>

    </div>
</div>

<script src="/admin/assets/js/my-account.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
