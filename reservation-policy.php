<?php
require_once __DIR__ . '/database/site_content.php';

$reservationContent = site_content_get_reservation_policy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './head.php'; ?>
    <link rel="stylesheet" href="./assets/css/reservation-policy.css">
</head>
<body>
    <?php include './navbar.php'; ?>

    <main class="reservation-policy-container">
        <h2><?php echo htmlspecialchars((string) ($reservationContent['title'] ?? 'Reservation Policy')); ?></h2>
        <div class="policy-box">
            <ol class="policy-list">
                <?php foreach ((array) ($reservationContent['policies'] ?? []) as $policy): ?>
                    <li><?php echo htmlspecialchars((string) $policy); ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </main>

    <?php include './footer.php'; ?>

    <script src="./assets/js/reservation-policy.js"></script>
</body>
</html>
