<?php
require_once __DIR__ . '/app/services/customer_auth.php';
require_once __DIR__ . '/database/site_content.php';

$aboutContent = site_content_get_about();
$aboutSections = (array) ($aboutContent['story_sections'] ?? []);
$currentCustomer = customer_auth_current_user();
$facebookUrl = trim((string) ($aboutContent['facebook_url'] ?? ''));
$tiktokUrl = trim((string) ($aboutContent['tiktok_url'] ?? ''));
$telegramUrl = trim((string) ($aboutContent['telegram_url'] ?? ''));
$instagramUrl = trim((string) ($aboutContent['instagram_url'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/assets/css/about.css')); ?>">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="about-page">
        <section class="about-hero">
            <h1><?php echo htmlspecialchars((string) ($aboutContent['hero_title'] ?? 'About ZYPP Camera House')); ?></h1>
            <p><?php echo nl2br(htmlspecialchars((string) ($aboutContent['hero_body'] ?? ''))); ?></p>
        </section>

        <section class="about-story">
            <?php foreach ($aboutSections as $section): ?>
                <article class="story-row<?php echo !empty($section['reverse']) ? ' reverse' : ''; ?>">
                    <div class="story-image-card">
                        <img src="<?php echo htmlspecialchars(site_content_image_url((string) ($section['image'] ?? ''), '')); ?>" alt="About story image">
                    </div>
                    <div class="story-copy">
                        <p><?php echo nl2br(htmlspecialchars((string) ($section['body'] ?? ''))); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="vision-section">
            <h2>Our Vision</h2>
            <div class="vision-copy">
                <?php foreach ((array) ($aboutContent['vision_lines'] ?? []) as $line): ?>
                    <p><?php echo htmlspecialchars((string) $line); ?></p>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="contact-section" id="contact">
            <div class="contact-card">
                <h2><?php echo htmlspecialchars((string) ($aboutContent['contact_title'] ?? 'Contact')); ?></h2>
                <div class="contact-layout">
                    <div class="contact-details">
                        <div class="contact-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <p><?php echo htmlspecialchars((string) ($aboutContent['address'] ?? '')); ?></p>
                        </div>
                        <div class="contact-item">
                            <i class="fa-solid fa-phone"></i>
                            <p><?php echo htmlspecialchars((string) ($aboutContent['phone'] ?? '')); ?></p>
                        </div>
                        <div class="contact-item">
                            <i class="fa-solid fa-envelope"></i>
                            <p><?php echo htmlspecialchars((string) ($aboutContent['email'] ?? '')); ?></p>
                        </div>

                        <div class="contact-socials">
                            <h3>Join us on:</h3>
                            <div class="social-row">
                                <?php if ($facebookUrl !== ''): ?>
                                    <a href="<?php echo htmlspecialchars($facebookUrl); ?>" class="social-link fb" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($tiktokUrl !== ''): ?>
                                    <a href="<?php echo htmlspecialchars($tiktokUrl); ?>" class="social-link tt" aria-label="TikTok" target="_blank" rel="noopener noreferrer">
                                        <i class="fab fa-tiktok"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (is_array($currentCustomer) && $telegramUrl !== ''): ?>
                                    <a href="<?php echo htmlspecialchars($telegramUrl); ?>" class="social-link tg" aria-label="Telegram" target="_blank" rel="noopener noreferrer">
                                        <i class="fas fa-paper-plane"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($instagramUrl !== ''): ?>
                                    <a href="<?php echo htmlspecialchars($instagramUrl); ?>" class="social-link ig" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="map-card" aria-label="Google Maps location">
                        <iframe
                            src="<?php echo htmlspecialchars((string) ($aboutContent['map_embed_url'] ?? '')); ?>"
                            width="600"
                            height="450"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="ZYPP Camera House location"
                        ></iframe>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
