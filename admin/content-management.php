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
            case 'save_unboxing_influencers':
                admin_content_management_save_unboxing_influencers($_POST);
                break;
            case 'save_about':
                admin_content_management_save_about($_POST, $_FILES);
                break;
            case 'save_delivery':
                admin_content_management_save_delivery_policy($_POST, $_FILES);
                break;
            case 'save_delivery_locations':
                admin_content_management_save_delivery_locations($_POST);
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
$homeButtonProductOptions = admin_content_management_fetch_home_button_product_options();
$footerContent = site_content_get_footer();
$unboxingInfluencerContent = site_content_get_unboxing_influencers();
$aboutContent = site_content_get_about();
$deliveryContent = site_content_get_delivery_policy();
$deliveryLocationsContent = site_content_get_delivery_locations();
$paymentContent = site_content_get_payment_information();
$paymentSections = (array) ($paymentContent['sections'] ?? []);
$reservationContent = site_content_get_reservation_policy();
$warrantyContent = site_content_get_warranty_faq();
$deliveryStateRows = [];
$deliveryCityRows = [];
$deliveryTownshipRows = [];
foreach ((array) ($deliveryLocationsContent['states'] ?? []) as $state) {
    $stateName = trim((string) ($state['name'] ?? ''));
    if ($stateName !== '') {
        $deliveryStateRows[] = ['state' => $stateName];
    }
    foreach ((array) ($state['cities'] ?? []) as $city) {
        $cityName = trim((string) ($city['name'] ?? ''));
        if ($stateName !== '' && $cityName !== '') {
            $deliveryCityRows[] = [
                'state' => $stateName,
                'city' => $cityName,
            ];
        }
        foreach ((array) ($city['townships'] ?? []) as $township) {
            $townshipName = trim((string) $township);
            if ($stateName !== '' && $cityName !== '' && $townshipName !== '') {
                $deliveryTownshipRows[] = [
                    'state' => $stateName,
                    'city' => $cityName,
                    'township' => $townshipName,
                ];
            }
        }
    }
}
if ($deliveryStateRows === []) {
    $deliveryStateRows[] = ['state' => ''];
}

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

$renderHomeHeroSlideEditor = static function (array $slide, $index, array $productOptions) use ($renderImageField): void {
    $safeIndex = htmlspecialchars((string) $index);
    $slideTitle = trim((string) ($slide['title'] ?? ''));
    $slideSubtitle = trim((string) ($slide['subtitle'] ?? ''));
    $previewId = 'hero_preview_' . (string) $index;
    ?>
    <article class="cms-card hero-slide-panel" data-hero-slide-panel>
        <input type="hidden" name="hero_existing_image[]" value="<?php echo htmlspecialchars((string) ($slide['image'] ?? '')); ?>">
        <div class="hero-slide-panel-head">
            <div>
                <h4 data-hero-slide-heading>Slide</h4>
                <p class="content-field-note">Edit one slide at a time, then switch from the slide list on the left.</p>
            </div>
            <button type="button" class="hero-slide-remove" data-hero-slide-remove>Remove Slide</button>
        </div>

        <div class="hero-slide-overview">
            <div class="hero-slide-overview-copy">
                <strong data-hero-slide-summary-title><?php echo htmlspecialchars($slideTitle !== '' ? $slideTitle : 'Untitled slide'); ?></strong>
                <span data-hero-slide-summary-subtitle><?php echo htmlspecialchars($slideSubtitle !== '' ? $slideSubtitle : 'No subtitle yet'); ?></span>
            </div>
            <span class="hero-slide-badge">Home Hero</span>
        </div>

        <?php $renderImageField('Image', 'hero_image_' . (string) $index, $slide['image'] ?? '', $previewId); ?>

        <div class="content-grid">
            <div class="content-field">
                <label>Title</label>
                <input type="text" name="hero_title[]" value="<?php echo htmlspecialchars((string) ($slide['title'] ?? '')); ?>" data-hero-slide-title-input>
            </div>
            <div class="content-field">
                <label>Title Color</label>
                <input type="color" name="hero_title_color[]" value="<?php echo htmlspecialchars((string) ($slide['title_color'] ?? '#2f2419')); ?>">
            </div>
            <div class="content-field">
                <label>Subtitle</label>
                <input type="text" name="hero_subtitle[]" value="<?php echo htmlspecialchars((string) ($slide['subtitle'] ?? '')); ?>" data-hero-slide-subtitle-input>
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
                <label>Button 1 Action</label>
                <select name="hero_button1_action[]" data-home-button-action="button1">
                    <option value="link" <?php echo (($slide['button1_action'] ?? 'link') === 'link') ? 'selected' : ''; ?>>Link URL</option>
                    <option value="add_to_cart" <?php echo (($slide['button1_action'] ?? 'link') === 'add_to_cart') ? 'selected' : ''; ?>>Add to Cart</option>
                </select>
            </div>
            <div class="content-field" data-home-button-url-field="button1">
                <label>Button 1 URL</label>
                <input type="text" name="hero_button1_url[]" value="<?php echo htmlspecialchars((string) ($slide['button1_url'] ?? '')); ?>">
                <p class="content-field-note">Used when action is set to Link URL.</p>
            </div>
            <div class="content-field" data-home-button-product-field="button1">
                <label>Button 1 Product</label>
                <select name="hero_button1_product_id[]">
                    <option value="0">Select Product</option>
                    <?php foreach ($productOptions as $productOption): ?>
                        <?php
                        $productId = (int) ($productOption['product_id'] ?? 0);
                        $brandName = trim((string) ($productOption['brand_name'] ?? ''));
                        $productLabel = trim((string) ($productOption['name'] ?? ''));
                        if ($brandName !== '') {
                            $productLabel .= ' (' . $brandName . ')';
                        }
                        ?>
                        <option value="<?php echo $productId; ?>" <?php echo $productId === (int) ($slide['button1_product_id'] ?? 0) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($productLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="content-field-note">Used when action is set to Add to Cart.</p>
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
                <label>Button 2 Action</label>
                <select name="hero_button2_action[]" data-home-button-action="button2">
                    <option value="link" <?php echo (($slide['button2_action'] ?? 'link') === 'link') ? 'selected' : ''; ?>>Link URL</option>
                    <option value="add_to_cart" <?php echo (($slide['button2_action'] ?? 'link') === 'add_to_cart') ? 'selected' : ''; ?>>Add to Cart</option>
                </select>
            </div>
            <div class="content-field" data-home-button-url-field="button2">
                <label>Button 2 URL</label>
                <input type="text" name="hero_button2_url[]" value="<?php echo htmlspecialchars((string) ($slide['button2_url'] ?? '')); ?>">
                <p class="content-field-note">Used when action is set to Link URL.</p>
            </div>
            <div class="content-field" data-home-button-product-field="button2">
                <label>Button 2 Product</label>
                <select name="hero_button2_product_id[]">
                    <option value="0">Select Product</option>
                    <?php foreach ($productOptions as $productOption): ?>
                        <?php
                        $productId = (int) ($productOption['product_id'] ?? 0);
                        $brandName = trim((string) ($productOption['brand_name'] ?? ''));
                        $productLabel = trim((string) ($productOption['name'] ?? ''));
                        if ($brandName !== '') {
                            $productLabel .= ' (' . $brandName . ')';
                        }
                        ?>
                        <option value="<?php echo $productId; ?>" <?php echo $productId === (int) ($slide['button2_product_id'] ?? 0) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($productLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="content-field-note">Used when action is set to Add to Cart.</p>
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
    </article>
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
                    <p class="content-note">Add as many slides as you need. Images are required; text and buttons stay optional.</p>
                    <div class="hero-slide-workspace" data-hero-slide-editor>
                        <aside class="hero-slide-sidebar">
                            <div class="hero-slide-sidebar-head">
                                <div>
                                    <h4>Slide Navigator</h4>
                                    <p>Switch between slides without scrolling through every form.</p>
                                </div>
                                <span class="hero-slide-count" data-hero-slide-count>0 slides</span>
                            </div>
                            <div class="hero-slide-tab-list" data-hero-slide-tabs></div>
                            <button type="button" class="hero-slide-add" data-hero-slide-add>Add New Slide</button>
                        </aside>

                        <div class="hero-slide-panel-shell">
                            <div class="hero-slide-panel-list" data-hero-slide-panels>
                                <?php foreach (($homeContent['hero_slides'] ?? []) as $index => $slide): ?>
                                    <?php $renderHomeHeroSlideEditor($slide, $index, $homeButtonProductOptions); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <h3>Video Redirect Sections</h3>
                    <div class="cms-stack cms-stack--compact">
                        <div class="cms-card">
                            <h4>Home Page</h4>
                            <div class="content-grid">
                                <div class="content-field">
                                    <label>Title</label>
                                    <input type="text" name="home_video_title" value="<?php echo htmlspecialchars((string) (($homeContent['video_sections']['home']['title'] ?? ''))); ?>">
                                </div>
                                <div class="content-field content-field--full">
                                    <label>Description</label>
                                    <textarea name="home_video_description"><?php echo htmlspecialchars((string) (($homeContent['video_sections']['home']['description'] ?? ''))); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="cms-card">
                            <h4>Product Details Page</h4>
                            <div class="content-grid">
                                <div class="content-field">
                                    <label>Title</label>
                                    <input type="text" name="product_video_title" value="<?php echo htmlspecialchars((string) (($homeContent['video_sections']['product_details']['title'] ?? ''))); ?>">
                                </div>
                                <div class="content-field content-field--full">
                                    <label>Description</label>
                                    <textarea name="product_video_description"><?php echo htmlspecialchars((string) (($homeContent['video_sections']['product_details']['description'] ?? ''))); ?></textarea>
                                </div>
                            </div>
                        </div>
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

            </form>
        </section>

        <section class="content-section <?php echo $selectedPage === 'unboxing_influencers' ? 'is-active' : ''; ?>" data-page="unboxing_influencers">
            <form class="content-form" method="post" data-content-form>
                <input type="hidden" name="action" value="save_unboxing_influencers">
                <input type="hidden" name="page" value="unboxing_influencers">

                <div class="content-card">
                    <h3>Tab Labels and Banner Text</h3>
                    <div class="cms-stack cms-stack--compact">
                        <div class="cms-card">
                            <h4>Unboxing Tab</h4>
                            <div class="content-grid">
                                <div class="content-field">
                                    <label>Tab Title</label>
                                    <input type="text" name="unboxing_tab_title" value="<?php echo htmlspecialchars((string) ($unboxingInfluencerContent['unboxing_tab_title'] ?? '')); ?>">
                                </div>
                                <div class="content-field content-field--full">
                                    <label>Banner Text</label>
                                    <textarea name="unboxing_banner_text"><?php echo htmlspecialchars((string) ($unboxingInfluencerContent['unboxing_banner_text'] ?? '')); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="cms-card">
                            <h4>Influencer Tab</h4>
                            <div class="content-grid">
                                <div class="content-field">
                                    <label>Tab Title</label>
                                    <input type="text" name="influencer_tab_title" value="<?php echo htmlspecialchars((string) ($unboxingInfluencerContent['influencer_tab_title'] ?? '')); ?>">
                                </div>
                                <div class="content-field content-field--full">
                                    <label>Banner Text</label>
                                    <textarea name="influencer_banner_text"><?php echo htmlspecialchars((string) ($unboxingInfluencerContent['influencer_banner_text'] ?? '')); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
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
                        <div class="cms-card">
                            <h4>General FAQs</h4>
                            <div class="content-field content-field--full">
                                <label>FAQs</label>
                                <?php $generalFaqRows = (array) ($warrantyContent['general_faqs'] ?? []); ?>
                                <?php if ($generalFaqRows === []): ?>
                                    <?php $generalFaqRows[] = ['q' => '', 'a' => '']; ?>
                                <?php endif; ?>
                                <div class="cms-faq-grid">
                                    <?php foreach ($generalFaqRows as $faqIndex => $faq): ?>
                                        <div class="cms-card cms-card--mini">
                                            <h5>General FAQ <?php echo $faqIndex + 1; ?></h5>
                                            <div class="content-field">
                                                <label>Question</label>
                                                <input type="text" name="general_faq_question[]" value="<?php echo htmlspecialchars((string) ($faq['q'] ?? '')); ?>">
                                            </div>
                                            <div class="content-field">
                                                <label>Answer</label>
                                                <textarea name="general_faq_answer[]"><?php echo htmlspecialchars((string) ($faq['a'] ?? '')); ?></textarea>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

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

            </form>
        </section>

        <section class="content-section <?php echo $selectedPage === 'delivery_locations' ? 'is-active' : ''; ?>" data-page="delivery_locations">
            <form class="content-form" method="post" data-content-form>
                <input type="hidden" name="action" value="save_delivery_locations">
                <input type="hidden" name="page" value="delivery_locations">

                <div class="content-card">
                    <h3>Delivery Locations</h3>
                    <div class="delivery-location-intro">
                        <p class="content-note">Step 1: add states. Step 2: assign cities to a state. Step 3: assign townships to a city.</p>
                        <p class="content-field-note">Admins can only add cities after there is at least one state, and only add townships after there is at least one city.</p>
                    </div>
                    <div class="delivery-location-panels" data-delivery-locations-editor>
                        <div class="delivery-location-panel">
                            <div class="delivery-location-panel-head">
                                <div>
                                    <h4>States / Provinces</h4>
                                    <p class="content-field-note">Add each state or province once.</p>
                                </div>
                                <div class="delivery-location-panel-meta">
                                    <span class="delivery-location-count" data-state-count><?php echo count($deliveryStateRows); ?> rows</span>
                                    <button type="button" class="delivery-location-add" data-state-add>Add State</button>
                                </div>
                            </div>
                            <div class="delivery-location-filters">
                                <div class="content-field">
                                    <label>Search States</label>
                                    <input type="text" placeholder="Search state or province" data-state-search>
                                </div>
                            </div>
                            <div class="delivery-location-table-scroller">
                                <div class="delivery-location-table" data-state-rows>
                                    <?php foreach ($deliveryStateRows as $stateRow): ?>
                                        <div class="delivery-location-table-row delivery-location-table-row--state" data-state-row>
                                            <div class="content-field">
                                                <label>State / Province</label>
                                                <input type="text" name="state_name[]" value="<?php echo htmlspecialchars((string) ($stateRow['state'] ?? '')); ?>" placeholder="e.g. Yangon" data-state-input>
                                            </div>
                                            <div class="delivery-location-actions">
                                                <button type="button" class="delivery-location-remove" data-state-remove aria-label="Remove state row">Remove</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <p class="delivery-location-empty" data-state-empty hidden>No matching states found.</p>
                        </div>

                        <div class="delivery-location-panel">
                            <div class="delivery-location-panel-head">
                                <div>
                                    <h4>Cities</h4>
                                    <p class="content-field-note">Choose the state first, then enter the city.</p>
                                </div>
                                <div class="delivery-location-panel-meta">
                                    <span class="delivery-location-count" data-city-count><?php echo count($deliveryCityRows); ?> rows</span>
                                    <button type="button" class="delivery-location-add" data-city-add>Add City</button>
                                </div>
                            </div>
                            <div class="delivery-location-filters delivery-location-filters--two">
                                <div class="content-field">
                                    <label>Filter by State</label>
                                    <select data-city-filter-state>
                                        <option value="">All States</option>
                                    </select>
                                </div>
                                <div class="content-field">
                                    <label>Search Cities</label>
                                    <input type="text" placeholder="Search city name" data-city-search>
                                </div>
                            </div>
                            <div class="delivery-location-table-scroller">
                                <div class="delivery-location-table" data-city-rows>
                                    <?php foreach ($deliveryCityRows as $cityRow): ?>
                                        <div class="delivery-location-table-row delivery-location-table-row--city" data-city-row>
                                            <div class="content-field">
                                                <label>State / Province</label>
                                                <select name="city_state[]" data-city-state-select>
                                                    <option value="">Select State</option>
                                                    <?php foreach ($deliveryStateRows as $stateOption): ?>
                                                        <?php $stateOptionName = trim((string) ($stateOption['state'] ?? '')); ?>
                                                        <?php if ($stateOptionName === '') { continue; } ?>
                                                        <option value="<?php echo htmlspecialchars($stateOptionName); ?>" <?php echo $stateOptionName === (string) ($cityRow['state'] ?? '') ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($stateOptionName); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="content-field">
                                                <label>City</label>
                                                <input type="text" name="city_name[]" value="<?php echo htmlspecialchars((string) ($cityRow['city'] ?? '')); ?>" placeholder="e.g. Yangon" data-city-input>
                                            </div>
                                            <div class="delivery-location-actions">
                                                <button type="button" class="delivery-location-remove" data-city-remove aria-label="Remove city row">Remove</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <p class="delivery-location-empty" data-city-empty hidden>No matching cities found.</p>
                        </div>

                        <div class="delivery-location-panel">
                            <div class="delivery-location-panel-head">
                                <div>
                                    <h4>Townships</h4>
                                    <p class="content-field-note">Choose the state and city first, then enter the township.</p>
                                </div>
                                <div class="delivery-location-panel-meta">
                                    <span class="delivery-location-count" data-township-count><?php echo count($deliveryTownshipRows); ?> rows</span>
                                    <button type="button" class="delivery-location-add" data-township-add>Add Township</button>
                                </div>
                            </div>
                            <div class="delivery-location-filters delivery-location-filters--three">
                                <div class="content-field">
                                    <label>Filter by State</label>
                                    <select data-township-filter-state>
                                        <option value="">All States</option>
                                    </select>
                                </div>
                                <div class="content-field">
                                    <label>Filter by City</label>
                                    <select data-township-filter-city>
                                        <option value="">All Cities</option>
                                    </select>
                                </div>
                                <div class="content-field">
                                    <label>Search Townships</label>
                                    <input type="text" placeholder="Search township name" data-township-search>
                                </div>
                            </div>
                            <div class="delivery-location-table-scroller">
                                <div class="delivery-location-table" data-township-rows>
                                    <?php foreach ($deliveryTownshipRows as $townshipRow): ?>
                                        <div class="delivery-location-table-row delivery-location-table-row--township" data-township-row>
                                            <div class="content-field">
                                                <label>State / Province</label>
                                                <select name="township_state[]" data-township-state-select>
                                                    <option value="">Select State</option>
                                                    <?php foreach ($deliveryStateRows as $stateOption): ?>
                                                        <?php $stateOptionName = trim((string) ($stateOption['state'] ?? '')); ?>
                                                        <?php if ($stateOptionName === '') { continue; } ?>
                                                        <option value="<?php echo htmlspecialchars($stateOptionName); ?>" <?php echo $stateOptionName === (string) ($townshipRow['state'] ?? '') ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($stateOptionName); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="content-field">
                                                <label>City</label>
                                                <select name="township_city[]" data-township-city-select data-selected-city="<?php echo htmlspecialchars((string) ($townshipRow['city'] ?? '')); ?>">
                                                    <option value="">Select City</option>
                                                </select>
                                            </div>
                                            <div class="content-field">
                                                <label>Township</label>
                                                <input type="text" name="township_name[]" value="<?php echo htmlspecialchars((string) ($townshipRow['township'] ?? '')); ?>" placeholder="e.g. Hlaing" data-township-input>
                                            </div>
                                            <div class="delivery-location-actions">
                                                <button type="button" class="delivery-location-remove" data-township-remove aria-label="Remove township row">Remove</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <p class="delivery-location-empty" data-township-empty hidden>No matching townships found.</p>
                        </div>
                    </div>
                </div>

            </form>
        </section>
        <template id="delivery-location-state-template">
            <div class="delivery-location-table-row delivery-location-table-row--state" data-state-row>
                <div class="content-field">
                    <label>State / Province</label>
                    <input type="text" name="state_name[]" placeholder="e.g. Yangon" data-state-input>
                </div>
                <div class="delivery-location-actions">
                    <button type="button" class="delivery-location-remove" data-state-remove aria-label="Remove state row">Remove</button>
                </div>
            </div>
        </template>
        <template id="delivery-location-city-template">
            <div class="delivery-location-table-row delivery-location-table-row--city" data-city-row>
                <div class="content-field">
                    <label>State / Province</label>
                    <select name="city_state[]" data-city-state-select>
                        <option value="">Select State</option>
                    </select>
                </div>
                <div class="content-field">
                    <label>City</label>
                    <input type="text" name="city_name[]" placeholder="e.g. Yangon" data-city-input>
                </div>
                <div class="delivery-location-actions">
                    <button type="button" class="delivery-location-remove" data-city-remove aria-label="Remove city row">Remove</button>
                </div>
            </div>
        </template>
        <template id="delivery-location-township-template">
            <div class="delivery-location-table-row delivery-location-table-row--township" data-township-row>
                <div class="content-field">
                    <label>State / Province</label>
                    <select name="township_state[]" data-township-state-select>
                        <option value="">Select State</option>
                    </select>
                </div>
                <div class="content-field">
                    <label>City</label>
                    <select name="township_city[]" data-township-city-select>
                        <option value="">Select City</option>
                    </select>
                </div>
                <div class="content-field">
                    <label>Township</label>
                    <input type="text" name="township_name[]" placeholder="e.g. Hlaing" data-township-input>
                </div>
                <div class="delivery-location-actions">
                    <button type="button" class="delivery-location-remove" data-township-remove aria-label="Remove township row">Remove</button>
                </div>
            </div>
        </template>

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
                                <p class="content-field-note">Payment method logos are managed below inside each payment method card.</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="content-card">
                    <div class="payment-method-head">
                        <div>
                            <h3>Checkout Payment Methods</h3>
                            <p class="content-note">These are the payment choices customers see during checkout and on the payment information page.</p>
                        </div>
                        <button type="button" class="delivery-location-add" data-payment-method-add>Add New Payment Method</button>
                    </div>
                    <div class="payment-method-guide">
                        <p>Simple setup guide:</p>
                        <p>1. Enter the payment method name customers should see.</p>
                        <p>2. Choose which payment category it belongs to.</p>
                        <p>3. Add the account details and customer instructions.</p>
                        <p>4. Turn on payment proof only when customers need to upload a screenshot or receipt.</p>
                    </div>
                    <?php $paymentMethodRows = (array) ($paymentContent['methods'] ?? []); ?>
                    <?php if ($paymentMethodRows === []): ?>
                        <?php $paymentMethodRows[] = []; ?>
                    <?php endif; ?>
                    <div class="cms-stack" data-payment-method-list>
                        <?php foreach ($paymentMethodRows as $methodIndex => $method): ?>
                            <?php
                            $methodLabel = trim((string) ($method['label'] ?? ''));
                            $methodKey = trim((string) ($method['key'] ?? ''));
                            ?>
                            <div class="cms-card payment-method-card" data-payment-method-row>
                                <div class="payment-method-card-head">
                                    <div>
                                        <h4 data-payment-method-title><?php echo htmlspecialchars($methodLabel !== '' ? $methodLabel : 'Payment Method ' . ($methodIndex + 1)); ?></h4>
                                        <p class="content-field-note">Customers will see this payment method during checkout.</p>
                                    </div>
                                    <button type="button" class="delivery-location-remove" data-payment-method-remove aria-label="Remove payment method">Remove</button>
                                </div>
                                <div class="content-grid">
                                    <div class="content-field">
                                        <label>Payment Method Name</label>
                                        <input type="text" name="payment_method_label[]" value="<?php echo htmlspecialchars($methodLabel); ?>" data-payment-method-label>
                                    </div>
                                    <div class="content-field">
                                        <label>System ID</label>
                                        <input type="text" value="<?php echo htmlspecialchars($methodKey !== '' ? $methodKey : 'Created automatically'); ?>" data-payment-method-key-display readonly>
                                        <input type="hidden" name="payment_method_key[]" value="<?php echo htmlspecialchars($methodKey); ?>" data-payment-method-key>
                                        <p class="content-field-note">You can ignore this. The system creates and uses it automatically.</p>
                                    </div>
                                    <div class="content-field">
                                        <label>Show Under</label>
                                        <select name="payment_method_section_key[]" data-payment-method-section>
                                            <?php foreach ($paymentSections as $section): ?>
                                                <?php $sectionKey = trim((string) ($section['key'] ?? '')); ?>
                                                <?php if ($sectionKey === '') { continue; } ?>
                                                <option value="<?php echo htmlspecialchars($sectionKey); ?>" <?php echo $sectionKey === (string) ($method['section_key'] ?? '') ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars((string) ($section['title'] ?? $sectionKey)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="content-field">
                                        <label>Contact Phone</label>
                                        <input type="text" name="payment_method_phone[]" value="<?php echo htmlspecialchars((string) ($method['phone'] ?? '')); ?>">
                                    </div>
                                    <div class="content-field content-field--full">
                                        <?php $renderImageField('Payment Method Logo', 'payment_method_logo_image_' . $methodIndex, $method['logo_image'] ?? '', 'payment_method_logo_preview_' . $methodIndex); ?>
                                    </div>
                                    <div class="content-field">
                                        <label>Account Name</label>
                                        <input type="text" name="payment_method_account_name[]" value="<?php echo htmlspecialchars((string) ($method['account_name'] ?? '')); ?>">
                                    </div>
                                    <div class="content-field">
                                        <label>Account No.</label>
                                        <input type="text" name="payment_method_account_number[]" value="<?php echo htmlspecialchars((string) ($method['account_number'] ?? '')); ?>">
                                    </div>
                                    <div class="content-field content-field--full">
                                        <label>Customer Instructions</label>
                                        <textarea name="payment_method_instructions[]" data-payment-method-instructions><?php echo htmlspecialchars(implode(PHP_EOL, (array) ($method['instructions'] ?? []))); ?></textarea>
                                        <p class="content-field-note">Write one point per line. Customers will read these before paying.</p>
                                    </div>
                                </div>
                                <div class="payment-method-flags">
                                    <label class="payment-method-toggle">
                                        <input type="checkbox" name="payment_method_is_active[<?php echo $methodIndex; ?>]" value="1" <?php echo !empty($method['is_active']) ? 'checked' : ''; ?> data-payment-method-active>
                                        <span>Show this payment method to customers</span>
                                    </label>
                                    <label class="payment-method-toggle">
                                        <input type="checkbox" name="payment_method_requires_payment_proof[<?php echo $methodIndex; ?>]" value="1" <?php echo !empty($method['requires_payment_proof']) ? 'checked' : ''; ?> data-payment-method-proof>
                                        <span>Customers must upload payment proof</span>
                                    </label>
                                </div>
                                <?php $renderImageField('QR Code / Payment Image', 'payment_method_qr_image_' . $methodIndex, $method['qr_image'] ?? '', 'payment_method_preview_' . $methodIndex); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <template id="home-hero-slide-template">
                    <?php $renderHomeHeroSlideEditor([], '__INDEX__', $homeButtonProductOptions); ?>
                </template>

            </form>
        </section>
        <template id="payment-method-template">
            <div class="cms-card payment-method-card" data-payment-method-row>
                <div class="payment-method-card-head">
                    <div>
                        <h4 data-payment-method-title>Payment Method</h4>
                        <p class="content-field-note">Customers will see this payment method during checkout.</p>
                    </div>
                    <button type="button" class="delivery-location-remove" data-payment-method-remove aria-label="Remove payment method">Remove</button>
                </div>
                <div class="content-grid">
                    <div class="content-field">
                        <label>Payment Method Name</label>
                        <input type="text" data-payment-method-label>
                    </div>
                    <div class="content-field">
                        <label>System ID</label>
                        <input type="text" value="Created automatically" data-payment-method-key-display readonly>
                        <input type="hidden" data-payment-method-key>
                        <p class="content-field-note">You can ignore this. The system creates and uses it automatically.</p>
                    </div>
                    <div class="content-field">
                        <label>Show Under</label>
                        <select data-payment-method-section>
                            <?php foreach ($paymentSections as $section): ?>
                                <?php $sectionKey = trim((string) ($section['key'] ?? '')); ?>
                                <?php if ($sectionKey === '') { continue; } ?>
                                <option value="<?php echo htmlspecialchars($sectionKey); ?>">
                                    <?php echo htmlspecialchars((string) ($section['title'] ?? $sectionKey)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="content-field">
                        <label>Contact Phone</label>
                        <input type="text" data-payment-method-phone>
                    </div>
                    <div class="content-field content-field--full">
                        <div class="cms-image-field">
                            <label class="cms-label">Payment Method Logo</label>
                            <div class="cms-image-row">
                                <div class="cms-image-preview">
                                    <div class="cms-image-empty" data-payment-method-logo-preview>No image</div>
                                </div>
                                <input type="file" class="cms-file-input" accept="image/*" data-payment-method-logo-file>
                            </div>
                        </div>
                    </div>
                    <div class="content-field">
                        <label>Account Name</label>
                        <input type="text" data-payment-method-account-name>
                    </div>
                    <div class="content-field">
                        <label>Account No.</label>
                        <input type="text" data-payment-method-account-number>
                    </div>
                    <div class="content-field content-field--full">
                        <label>Customer Instructions</label>
                        <textarea data-payment-method-instructions></textarea>
                        <p class="content-field-note">Write one point per line. Customers will read these before paying.</p>
                    </div>
                </div>
                <div class="payment-method-flags">
                    <label class="payment-method-toggle">
                        <input type="checkbox" value="1" checked data-payment-method-active>
                        <span>Show this payment method to customers</span>
                    </label>
                    <label class="payment-method-toggle">
                        <input type="checkbox" value="1" checked data-payment-method-proof>
                        <span>Customers must upload payment proof</span>
                    </label>
                </div>
                <div class="cms-image-field">
                    <label class="cms-label">QR Code / Payment Image</label>
                    <div class="cms-image-row">
                        <div class="cms-image-preview">
                            <div class="cms-image-empty" data-payment-method-preview>No image</div>
                        </div>
                        <input type="file" class="cms-file-input" accept="image/*" data-payment-method-file>
                    </div>
                </div>
            </div>
        </template>

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
