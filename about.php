<?php
$aboutSections = [
    [
        'image' => '/storage/uploads/contents/hero-img.png',
        'title' => 'Picture 1',
        'body' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet consectetur adipisicing elit.',
        'reverse' => false,
    ],
    [
        'image' => '/storage/uploads/contents/hero-img-2.png',
        'title' => 'Picture 2',
        'body' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet.',
        'reverse' => true,
    ],
    [
        'image' => '/storage/uploads/contents/hero-img-3.png',
        'title' => 'Picture 3',
        'body' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet.',
        'reverse' => false,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/assets/css/about.css">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="about-page">
        <section class="about-hero">
            <h1>About ZYPP Camera House</h1>
            <p>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore
                magna aliqua. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut
                labore et dolore magna aliqua. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod.
            </p>
        </section>

        <section class="about-story">
            <?php foreach ($aboutSections as $section): ?>
                <article class="story-row<?php echo $section['reverse'] ? ' reverse' : ''; ?>">
                    <div class="story-image-card">
                        <img src="<?php echo htmlspecialchars($section['image']); ?>" alt="<?php echo htmlspecialchars($section['title']); ?>">
                    </div>
                    <div class="story-copy">
                        <p><?php echo htmlspecialchars($section['body']); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="vision-section">
            <h2>Our Vision</h2>
            <div class="vision-copy">
                <p>Lorem ipsum dolor sit amet,</p>
                <p>consectetur adipisicing elit,</p>
                <p>sed do eiusmod tempor incididunt ut</p>
                <p>labore et dolore magna aliqua.</p>
                <p>Lorem ipsum dolor sit</p>
            </div>
        </section>

        <section class="contact-section" id="contact">
            <div class="contact-card">
                <h2>Contact</h2>
                <div class="contact-layout">
                    <div class="contact-details">
                        <div class="contact-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <p>No. 112, 52nd Street, Middle Block, Pazundaung Township, Yangon 11171</p>
                        </div>
                        <div class="contact-item">
                            <i class="fa-solid fa-phone"></i>
                            <p>09-251562642, 09-424574187</p>
                        </div>
                        <div class="contact-item">
                            <i class="fa-solid fa-envelope"></i>
                            <p>zyppcamerahouse2023@gmail.com</p>
                        </div>

                        <div class="contact-socials">
                            <h3>Join us on:</h3>
                            <div class="social-row">
                                <a href="#" class="social-link fb" aria-label="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-link tt" aria-label="TikTok">
                                    <i class="fab fa-tiktok"></i>
                                </a>
                                <a href="#" class="social-link tg" aria-label="Telegram">
                                    <i class="fas fa-paper-plane"></i>
                                </a>
                                <a href="#" class="social-link ig" aria-label="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="map-card" aria-label="Google Maps location">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3820.026510724878!2d96.1722099!3d16.775356599999995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30c1ed33a6c6f30d%3A0x256ab0e5a54a67ac!2sZYPP%20Camera%20House!5e0!3m2!1sen!2snl!4v1772969831783!5m2!1sen!2snl"
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
