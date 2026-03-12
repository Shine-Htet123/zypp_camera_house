<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/database/site_content.php';

$footerContent = site_content_get_footer();
$footerImageUrl = site_content_image_url((string) ($footerContent['footer_image'] ?? ''), app_path('/storage/uploads/contents/logo.png'));
?>
<footer class="footer">
    <div class="footer-logo" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($footerImageUrl); ?>" alt="">
    </div>
    <div class="footer-container">

        <div class="footer-col">
            <h3>Quick Links</h3>
            <a href="<?php echo htmlspecialchars(app_path('/products.php')); ?>">Shop</a>
            <a href="<?php echo htmlspecialchars(app_path('/wholesale.php')); ?>">Wholesale</a>
            <a href="<?php echo htmlspecialchars(app_path('/about.php')); ?>">About Us</a>
            <a href="<?php echo htmlspecialchars(app_path('/about.php#contact')); ?>">Contact Us</a>
        </div>

        <div class="footer-col">
            <h3>Support</h3>
            <a href="<?php echo htmlspecialchars(app_path('/warranty-FAQ.php')); ?>">Warranty & FAQs</a>
            <a href="<?php echo htmlspecialchars(app_path('/reservation-policy.php')); ?>">Reservation Policy</a>
            <a href="<?php echo htmlspecialchars(app_path('/delivery-policy.php')); ?>">Delivery Policy</a>
            <a href="<?php echo htmlspecialchars(app_path('/payment-information.php')); ?>">Payment Information</a>
            <a href="<?php echo htmlspecialchars(app_path('/admin/login.php')); ?>">Admin Login</a>
        </div>

        <div class="footer-col">
            <h3>Media</h3>
            <a href="#">Unboxing Videos</a>
            <a href="#">Influencers’ Reviews</a>
        </div>

        <div class="footer-col contact">
            <h3>Contact</h3>

            <div class="contact-info">
                <div class="row">
                    <span class="label">Address:</span>
                    <span>
                        <?php echo htmlspecialchars((string) ($footerContent['address'] ?? '')); ?>
                    </span>
                </div>

                <div class="row">
                    <span class="label">Phone:</span>
                    <span><?php echo htmlspecialchars((string) ($footerContent['phone'] ?? '')); ?></span>
                </div>

                <div class="row">
                    <span class="label">Email:</span>
                    <span><?php echo htmlspecialchars((string) ($footerContent['email'] ?? '')); ?></span>
                </div>
            </div>


            <div class="social-icons">
                <a href="<?php echo htmlspecialchars((string) (($footerContent['facebook_url'] ?? '') !== '' ? $footerContent['facebook_url'] : '#')); ?>" class="social-link fb" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-facebook-f"></i>
                </a>

                <a href="<?php echo htmlspecialchars((string) (($footerContent['tiktok_url'] ?? '') !== '' ? $footerContent['tiktok_url'] : '#')); ?>" class="social-link tt" aria-label="TikTok" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-tiktok"></i>
                </a>

                <a href="<?php echo htmlspecialchars((string) (($footerContent['telegram_url'] ?? '') !== '' ? $footerContent['telegram_url'] : '#')); ?>" class="social-link tg" aria-label="Telegram" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-paper-plane"></i>
                </a>

                <a href="<?php echo htmlspecialchars((string) (($footerContent['instagram_url'] ?? '') !== '' ? $footerContent['instagram_url'] : '#')); ?>" class="social-link ig" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-instagram"></i>
                </a>
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        © ZYPP Camera House
    </div>
</footer>
