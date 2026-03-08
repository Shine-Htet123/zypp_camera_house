<?php
$deliveryPolicies = [
    'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.',
    'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.',
    'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.',
    'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.',
    'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/assets/css/delivery-policy.css">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="delivery-policy-page">
        <header class="delivery-policy-header">
            <h1>Delivery Information</h1>
        </header>

        <section class="delivery-policy-hero">
            <div class="delivery-plan-card">
                <img src="/storage/uploads/contents/delivery-info-img.jpg" alt="Delivery plan">
            </div>
        </section>

        <section class="delivery-policy-content">
            <ol class="delivery-policy-list">
                <?php foreach ($deliveryPolicies as $policy): ?>
                    <li><?php echo htmlspecialchars($policy); ?></li>
                <?php endforeach; ?>
            </ol>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
