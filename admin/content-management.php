<?php

require_once __DIR__ . '/../config/admin_bootstrap.php';
require_once __DIR__ . '/../database/admin/content_management.php';

$pageOptions = admin_content_management_page_options();
$validPages = array_column($pageOptions, 'value');
$selectedPage = trim((string) ($_GET['page'] ?? 'home'));
if (!in_array($selectedPage, $validPages, true)) {
    $selectedPage = 'home';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $redirectPage = trim((string) ($_POST['page'] ?? $selectedPage));
    if (!in_array($redirectPage, $validPages, true)) {
        $redirectPage = 'home';
    }

    try {
        switch ($action) {
            case 'save_home':
                admin_content_management_save_home($_POST, $_FILES);
                break;
            case 'save_footer':
                admin_content_management_save_footer($_POST, $_FILES);
                break;
            case 'save_about':
                admin_content_management_save_about($_POST, $_FILES);
                break;
            case 'save_delivery':
                admin_content_management_save_delivery_policy($_POST, $_FILES);
                break;
            case 'save_payment':
                admin_content_management_save_payment_information($_POST, $_FILES);
                break;
            case 'save_reservation':
                admin_content_management_save_reservation_policy($_POST);
                break;
            case 'save_warranty':
                admin_content_management_save_warranty_faq($_POST, $_FILES);
                break;
            default:
                throw new InvalidArgumentException('Unsupported content action.');
        }

        admin_content_management_flash_set('success', 'Content saved successfully.');
    } catch (Throwable $exception) {
        admin_content_management_flash_set('error', $exception->getMessage());
    }

    header('Location: ' . app_path('/admin/content-management.php?page=' . rawurlencode($redirectPage)));
    exit;
}

$flash = admin_content_management_flash_consume();
$homeContent = site_content_get_home();
$footerContent = site_content_get_footer();
$aboutContent = site_content_get_about();
$deliveryContent = site_content_get_delivery_policy();
$paymentContent = site_content_get_payment_information();
$reservationContent = site_content_get_reservation_policy();
$warrantyContent = site_content_get_warranty_faq();

$renderImageField = static function (string $label, string $name, ?string $imagePath, string $previewId): void {
    $imageUrl = site_content_image_url($imagePath, '');
    ?>
    <div class="cms-image-field">
        <label class="cms-label"><?php echo htmlspecialchars($label); ?></label>
        <div class="cms-image-row">
            <div class="cms-image-preview">
                <?php if ($imageUrl !== ''): ?>
                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($label); ?>" id="<?php echo htmlspecialchars($previewId); ?>">
                <?php else: ?>
                    <div class="cms-image-empty" id="<?php echo htmlspecialchars($previewId); ?>">No image</div>
                <?php endif; ?>
            </div>
            <input type="file" class="cms-file-input" name="<?php echo htmlspecialchars($name); ?>" accept="image/*" data-preview-target="<?php echo htmlspecialchars($previewId); ?>">
        </div>
    </div>
    <?php
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/content-management.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content content-management" data-content-root>
        <header class="content-header">
            <h1>Manage Website Contents</h1>
        </header>

        <?php if ($flash): ?>
            <div class="content-flash <?php echo htmlspecialchars((string) ($flash['type'] ?? 'info')); ?>">
                <?php echo htmlspecialchars((string) ($flash['message'] ?? '')); ?>
            </div>
        <?php endif; ?>

        <div class="page-select">
            <label for="contentPage">Page:</label>
            <select id="contentPage" class="page-dropdown" data-page-select>
                <?php foreach ($pageOptions as $page): ?>
                    <option value="<?php echo htmlspecialchars($page['value']); ?>" <?php echo $page['value'] === $selectedPage ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($page['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <section class="content-section <?php echo $selectedPage === 'home' ? 'is-active' : ''; ?>" data-page="home">
            <form class="content-form" method="post" enctype="multipart/form-data" data-content-form>
                <input type="hidden" name="action" value="save_home">
                <input type="hidden" name="page" value="home">

                <div class="content-card">
                    <h3>Hero Section</h3>
                    <p class="content-note">Images are required. Texts and buttons are optional.</p>
                    <div class="cms-stack">
                        <?php foreach (($homeContent['hero_slides'] ?? []) as $index => $slide): ?>
                            <div class="cms-card">
                                <h4>Slide <?php echo $index + 1; ?></h4>
                                <?php $renderImageField('Image', 'hero_image_' . $index, $slide['image'] ?? '', 'hero_preview_' . $index); ?>
                                <div class="content-grid">
                                    <div class="content-field">
                                        <label>Title</label>
                                        <input type="text" name="hero_title[]" value="<?php echo htmlspecialchars((string) ($slide['title'] ?? '')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Title Color</label>
                                        <input type="color" name="hero_title_color[]" value="<?php echo htmlspecialchars((string) ($slide['title_color'] ?? '#2f2419')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Subtitle</label>
                                        <input type="text" name="hero_subtitle[]" value="<?php echo htmlspecialchars((string) ($slide['subtitle'] ?? '')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Subtitle Color</label>
                                        <input type="color" name="hero_subtitle_color[]" value="<?php echo htmlspecialchars((string) ($slide['subtitle_color'] ?? '#2f2419')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Button 1 Text</label>
                                        <input type="text" name="hero_button1_text[]" value="<?php echo htmlspecialchars((string) ($slide['button1_text'] ?? '')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Button 1 URL</label>
                                        <input type="text" name="hero_button1_url[]" value="<?php echo htmlspecialchars((string) ($slide['button1_url'] ?? '')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Button 1 Background</label>
                                        <input type="color" name="hero_button1_background_color[]" value="<?php echo htmlspecialchars((string) ($slide['button1_background_color'] ?? '#6b5241')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Button 1 Text Color</label>
                                        <input type="color" name="hero_button1_text_color[]" value="<?php echo htmlspecialchars((string) ($slide['button1_text_color'] ?? '#ffffff')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Button 2 Text</label>
                                        <input type="text" name="hero_button2_text[]" value="<?php echo htmlspecialchars((string) ($slide['button2_text'] ?? '')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Button 2 URL</label>
                                        <input type="text" name="hero_button2_url[]" value="<?php echo htmlspecialchars((string) ($slide['button2_url'] ?? '')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Button 2 Background</label>
                                        <input type="color" name="hero_button2_background_color[]" value="<?php echo htmlspecialchars((string) ($slide['button2_background_color'] ?? '#ffffff')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Button 2 Text Color</label>
                                        <input type="color" name="hero_button2_text_color[]" value="<?php echo htmlspecialchars((string) ($slide['button2_text_color'] ?? '#6b5241')); ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="content-card">
                    <h3>Trust Badges</h3>
                    <div class="cms-stack cms-stack--compact">
                        <?php foreach (($homeContent['trust_badges'] ?? []) as $index => $badge): ?>
                            <div class="cms-card">
                                <h4>Badge <?php echo $index + 1; ?></h4>
                                <?php $renderImageField('Badge Image', 'trust_badge_image_' . $index, $badge['image'] ?? '', 'badge_preview_' . $index); ?>
                                <div class="content-field">
                                    <label>Badge Text</label>
                                    <input type="text" name="trust_badge_title[]" value="<?php echo htmlspecialchars((string) ($badge['title'] ?? '')); ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section-actions">
                    <button type="submit" class="btn-save">Save Home Content</button>
                </div>
            </form>
        </section>

        <section class="content-section <?php echo $selectedPage === 'footer' ? 'is-active' : ''; ?>" data-page="footer">
            <form class="content-form" method="post" enctype="multipart/form-data" data-content-form>
                <input type="hidden" name="action" value="save_footer">
                <input type="hidden" name="page" value="footer">

                <div class="content-card">
                    <h3>Footer</h3>
                    <?php $renderImageField('Footer Image', 'footer_image', $footerContent['footer_image'] ?? '', 'footer_preview'); ?>
                    <div class="content-grid">
                        <div class="content-field content-field--full">
                            <label>Address</label>
                            <textarea name="address"><?php echo htmlspecialchars((string) ($footerContent['address'] ?? '')); ?></textarea>
                        </div>
                        <div class="content-field">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars((string) ($footerContent['phone'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>Email</label>
                            <input type="text" name="email" value="<?php echo htmlspecialchars((string) ($footerContent['email'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>Facebook URL</label>
                            <input type="text" name="facebook_url" value="<?php echo htmlspecialchars((string) ($footerContent['facebook_url'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>TikTok URL</label>
                            <input type="text" name="tiktok_url" value="<?php echo htmlspecialchars((string) ($footerContent['tiktok_url'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>Telegram URL</label>
                            <input type="text" name="telegram_url" value="<?php echo htmlspecialchars((string) ($footerContent['telegram_url'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>Instagram URL</label>
                            <input type="text" name="instagram_url" value="<?php echo htmlspecialchars((string) ($footerContent['instagram_url'] ?? '')); ?>">
                        </div>
                    </div>
                </div>

                <div class="section-actions">
                    <button type="submit" class="btn-save">Save Footer Content</button>
                </div>
            </form>
        </section>

        <section class="content-section <?php echo $selectedPage === 'about' ? 'is-active' : ''; ?>" data-page="about">
            <form class="content-form" method="post" enctype="multipart/form-data" data-content-form>
                <input type="hidden" name="action" value="save_about">
                <input type="hidden" name="page" value="about">

                <div class="content-card">
                    <h3>About Hero</h3>
                    <div class="content-grid">
                        <div class="content-field">
                            <label>Hero Title</label>
                            <input type="text" name="hero_title" value="<?php echo htmlspecialchars((string) ($aboutContent['hero_title'] ?? '')); ?>">
                        </div>
                        <div class="content-field content-field--full">
                            <label>Hero Body</label>
                            <textarea name="hero_body"><?php echo htmlspecialchars((string) ($aboutContent['hero_body'] ?? '')); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <h3>Story Sections</h3>
                    <div class="cms-stack">
                        <?php foreach (($aboutContent['story_sections'] ?? []) as $index => $story): ?>
                            <div class="cms-card">
                                <h4>Story <?php echo $index + 1; ?></h4>
                                <?php $renderImageField('Story Image', 'about_story_image_' . $index, $story['image'] ?? '', 'about_story_preview_' . $index); ?>
                                <div class="content-field content-field--full">
                                    <label>Body</label>
                                    <textarea name="story_body[]"><?php echo htmlspecialchars((string) ($story['body'] ?? '')); ?></textarea>
                                </div>
                                <label class="inline-check">
                                    <input type="checkbox" name="story_reverse[<?php echo $index; ?>]" value="1" <?php echo !empty($story['reverse']) ? 'checked' : ''; ?>>
                                    <span>Reverse layout</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="content-card">
                    <h3>Vision and Contact</h3>
                    <div class="content-grid">
                        <div class="content-field content-field--full">
                            <label>Vision Lines</label>
                            <textarea name="vision_lines"><?php echo htmlspecialchars(implode(PHP_EOL, (array) ($aboutContent['vision_lines'] ?? []))); ?></textarea>
                        </div>
                        <div class="content-field">
                            <label>Contact Section Title</label>
                            <input type="text" name="contact_title" value="<?php echo htmlspecialchars((string) ($aboutContent['contact_title'] ?? '')); ?>">
                        </div>
                        <div class="content-field content-field--full">
                            <label>Address</label>
                            <textarea name="address"><?php echo htmlspecialchars((string) ($aboutContent['address'] ?? '')); ?></textarea>
                        </div>
                        <div class="content-field">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars((string) ($aboutContent['phone'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>Email</label>
                            <input type="text" name="email" value="<?php echo htmlspecialchars((string) ($aboutContent['email'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>Facebook URL</label>
                            <input type="text" name="facebook_url" value="<?php echo htmlspecialchars((string) ($aboutContent['facebook_url'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>TikTok URL</label>
                            <input type="text" name="tiktok_url" value="<?php echo htmlspecialchars((string) ($aboutContent['tiktok_url'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>Telegram URL</label>
                            <input type="text" name="telegram_url" value="<?php echo htmlspecialchars((string) ($aboutContent['telegram_url'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>Instagram URL</label>
                            <input type="text" name="instagram_url" value="<?php echo htmlspecialchars((string) ($aboutContent['instagram_url'] ?? '')); ?>">
                        </div>
                        <div class="content-field content-field--full">
                            <label>Google Map Embed URL</label>
                            <textarea name="map_embed_url"><?php echo htmlspecialchars((string) ($aboutContent['map_embed_url'] ?? '')); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="section-actions">
                    <button type="submit" class="btn-save">Save About Content</button>
                </div>
            </form>
        </section>

        <section class="content-section <?php echo $selectedPage === 'warranty' ? 'is-active' : ''; ?>" data-page="warranty">
            <form class="content-form" method="post" data-content-form>
                <input type="hidden" name="action" value="save_warranty">
                <input type="hidden" name="page" value="warranty">

                <div class="content-card">
                    <h3>Warranty &amp; FAQs</h3>
                    <div class="content-grid">
                        <div class="content-field">
                            <label>Hero Title</label>
                            <input type="text" name="hero_title" value="<?php echo htmlspecialchars((string) ($warrantyContent['hero_title'] ?? '')); ?>">
                        </div>
                        <div class="content-field">
                            <label>Hero Subtitle</label>
                            <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars((string) ($warrantyContent['hero_subtitle'] ?? '')); ?>">
                        </div>
                    </div>
                    <div class="cms-stack">
                        <?php foreach (($warrantyContent['categories'] ?? []) as $index => $category): ?>
                            <div class="cms-card">
                                <h4><?php echo htmlspecialchars((string) ($category['label'] ?? ('Category ' . ($index + 1)))); ?></h4>
                                <div class="cms-image-field">
                                    <label class="cms-label">Category Image</label>
                                    <div class="cms-image-row">
                                        <div class="cms-image-preview">
                                            <?php if (trim((string) ($category['image'] ?? '')) !== ''): ?>
                                                <img src="<?php echo htmlspecialchars(site_content_image_url((string) ($category['image'] ?? ''), '')); ?>" alt="<?php echo htmlspecialchars((string) ($category['label'] ?? '')); ?>">
                                            <?php else: ?>
                                                <div class="cms-image-empty">No image</div>
                                            <?php endif; ?>
                                        </div>
                                        <p class="content-note">Managed from Category &amp; Brand.</p>
                                    </div>
                                </div>
                                <div class="content-grid">
                                    <div class="content-field content-field--full">
                                        <label>Policy Lines</label>
                                        <textarea name="category_policy[]"><?php echo htmlspecialchars(implode(PHP_EOL, (array) ($category['policy'] ?? []))); ?></textarea>
                                    </div>
                                    <div class="content-field content-field--full">
                                        <label>FAQs</label>
                                        <?php $faqRows = (array) ($category['faqs'] ?? []); ?>
                                        <?php if ($faqRows === []): ?>
                                            <?php $faqRows[] = ['q' => '', 'a' => '']; ?>
                                        <?php endif; ?>
                                        <div class="cms-faq-grid">
                                            <?php foreach ($faqRows as $faqIndex => $faq): ?>
                                                <div class="cms-card cms-card--mini">
                                                    <h5>FAQ <?php echo $faqIndex + 1; ?></h5>
                                                    <div class="content-field">
                                                        <label>Question</label>
                                                        <input type="text" name="category_faq_question[<?php echo $index; ?>][]" value="<?php echo htmlspecialchars((string) ($faq['q'] ?? '')); ?>">
                                                    </div>
                                                    <div class="content-field">
                                                        <label>Answer</label>
                                                        <textarea name="category_faq_answer[<?php echo $index; ?>][]"><?php echo htmlspecialchars((string) ($faq['a'] ?? '')); ?></textarea>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section-actions">
                    <button type="submit" class="btn-save">Save Warranty Content</button>
                </div>
            </form>
        </section>

        <section class="content-section <?php echo $selectedPage === 'delivery' ? 'is-active' : ''; ?>" data-page="delivery">
            <form class="content-form" method="post" enctype="multipart/form-data" data-content-form>
                <input type="hidden" name="action" value="save_delivery">
                <input type="hidden" name="page" value="delivery">

                <div class="content-card">
                    <h3>Delivery Policy</h3>
                    <?php $renderImageField('Delivery Image', 'delivery_image', $deliveryContent['image'] ?? '', 'delivery_preview'); ?>
                    <div class="content-grid">
                        <div class="content-field">
                            <label>Page Title</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars((string) ($deliveryContent['title'] ?? '')); ?>">
                        </div>
                        <div class="content-field content-field--full">
                            <label>Policy Lines</label>
                            <textarea name="policies"><?php echo htmlspecialchars(implode(PHP_EOL, (array) ($deliveryContent['policies'] ?? []))); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="section-actions">
                    <button type="submit" class="btn-save">Save Delivery Policy</button>
                </div>
            </form>
        </section>

        <section class="content-section <?php echo $selectedPage === 'payment' ? 'is-active' : ''; ?>" data-page="payment">
            <form class="content-form" method="post" enctype="multipart/form-data" data-content-form>
                <input type="hidden" name="action" value="save_payment">
                <input type="hidden" name="page" value="payment">

                <div class="content-card">
                    <h3>Payment Information</h3>
                    <div class="content-grid">
                        <div class="content-field">
                            <label>Page Title</label>
                            <input type="text" name="page_title" value="<?php echo htmlspecialchars((string) ($paymentContent['page_title'] ?? '')); ?>">
                        </div>
                    </div>
                    <div class="cms-stack">
                        <?php foreach (($paymentContent['sections'] ?? []) as $sectionIndex => $section): ?>
                            <div class="cms-card">
                                <h4><?php echo htmlspecialchars((string) ($section['key'] ?? ('Section ' . ($sectionIndex + 1)))); ?></h4>
                                <div class="content-grid">
                                    <div class="content-field">
                                        <label>Section Title</label>
                                        <input type="text" name="payment_section_title[]" value="<?php echo htmlspecialchars((string) ($section['title'] ?? '')); ?>">
                                    </div>
                                    <div class="content-field content-field--full">
                                        <label>Notes</label>
                                        <textarea name="payment_section_notes[]"><?php echo htmlspecialchars(implode(PHP_EOL, (array) ($section['notes'] ?? []))); ?></textarea>
                                    </div>
                                </div>
                                <div class="cms-logo-grid">
                                    <?php for ($logoIndex = 0; $logoIndex < 5; $logoIndex++): ?>
                                        <?php $logo = $section['logos'][$logoIndex] ?? ['label' => '', 'image' => '']; ?>
                                        <div class="cms-card cms-card--mini">
                                            <h5>Logo <?php echo $logoIndex + 1; ?></h5>
                                            <?php $renderImageField('Logo Image', 'payment_logo_image_' . $sectionIndex . '_' . $logoIndex, $logo['image'] ?? '', 'payment_logo_preview_' . $sectionIndex . '_' . $logoIndex); ?>
                                            <div class="content-field">
                                                <label>Logo Label</label>
                                                <input type="text" name="payment_logo_label[<?php echo $sectionIndex; ?>][]" value="<?php echo htmlspecialchars((string) ($logo['label'] ?? '')); ?>">
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section-actions">
                    <button type="submit" class="btn-save">Save Payment Information</button>
                </div>
            </form>
        </section>

        <section class="content-section <?php echo $selectedPage === 'reservation' ? 'is-active' : ''; ?>" data-page="reservation">
            <form class="content-form" method="post" data-content-form>
                <input type="hidden" name="action" value="save_reservation">
                <input type="hidden" name="page" value="reservation">

                <div class="content-card">
                    <h3>Reservation Policy</h3>
                    <div class="content-grid">
                        <div class="content-field">
                            <label>Page Title</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars((string) ($reservationContent['title'] ?? '')); ?>">
                        </div>
                        <div class="content-field content-field--full">
                            <label>Policy Lines</label>
                            <textarea name="policies"><?php echo htmlspecialchars(implode(PHP_EOL, (array) ($reservationContent['policies'] ?? []))); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="section-actions">
                    <button type="submit" class="btn-save">Save Reservation Policy</button>
                </div>
            </form>
        </section>
    </main>

    <div class="unsaved-bar" data-unsaved-bar>
        <span>Unsaved changes</span>
        <div class="unsaved-actions">
            <button type="button" class="btn-save" data-unsaved-save>Save</button>
            <button type="button" class="btn-discard" data-unsaved-discard>Discard</button>
        </div>
    </div>

    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/content-management.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>
