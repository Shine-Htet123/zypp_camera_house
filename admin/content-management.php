<?php
$pages = [
    ['value' => 'home', 'label' => 'Home'],
    ['value' => 'product-details', 'label' => 'Product Details'],
    ['value' => 'delivery', 'label' => 'Delivery'],
    ['value' => 'warranty', 'label' => 'Warranty & FAQs'],
    ['value' => 'unboxing', 'label' => 'Unboxing & Influencer'],
    ['value' => 'user-profile', 'label' => 'User Profile'],
    ['value' => 'reservation-policy', 'label' => 'Reservation Policy'],
    ['value' => 'contact', 'label' => 'Contact Us'],
];

$bannerTabs = [
    [
        'id' => 'banner-1',
        'label' => 'Banner 1',
        'image' => '/storage/uploads/contents/hero-img.png',
        'title' => 'Vintage Canon AE-1 Program',
        'subtitle' => '700,000 MMK',
        'title_color' => '#222222',
        'subtitle_color' => '#222222',
        'button_enabled' => 'yes',
        'button1_text' => 'Shop Now',
        'button1_color' => '#3b2a1a',
        'button2_text' => 'Learn More',
        'button2_color' => '#ffffff',
    ],
    [
        'id' => 'banner-2',
        'label' => 'Banner 2',
        'image' => '/storage/uploads/contents/hero-img-2.png',
        'title' => 'Canon EOS R6 Mark II',
        'subtitle' => '2,500,000 MMK',
        'title_color' => '#1f1f1f',
        'subtitle_color' => '#1f1f1f',
        'button_enabled' => 'yes',
        'button1_text' => 'Discover',
        'button1_color' => '#3b2a1a',
        'button2_text' => 'Preorder',
        'button2_color' => '#ffffff',
    ],
    [
        'id' => 'banner-3',
        'label' => 'Banner 3',
        'image' => '/storage/uploads/contents/hero-img-3.png',
        'title' => 'Pro Creator Kits',
        'subtitle' => 'From 1,500,000 MMK',
        'title_color' => '#1f1f1f',
        'subtitle_color' => '#1f1f1f',
        'button_enabled' => 'no',
        'button1_text' => 'View Kits',
        'button1_color' => '#3b2a1a',
        'button2_text' => 'Contact Us',
        'button2_color' => '#ffffff',
    ],
];

$buttonTexts = ['Shop Now', 'Learn More', 'Discover', 'Preorder', 'View Kits', 'Contact Us'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/content-management.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content content-management" data-content-root>
        <header class="content-header">
            <h1>Manage Website Contents</h1>
        </header>

        <div class="page-select">
            <label for="contentPage">Page:</label>
            <select id="contentPage" class="page-dropdown" data-page-select>
                <?php foreach ($pages as $page): ?>
                    <option value="<?php echo htmlspecialchars($page['value']); ?>" <?php echo $page['value'] === 'home' ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($page['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <section class="content-section is-active" data-page="home">
            <div class="banner-tabs" role="tablist">
                <?php foreach ($bannerTabs as $index => $tab): ?>
                    <button
                        class="banner-tab<?php echo $index === 0 ? ' active' : ''; ?>"
                        type="button"
                        data-banner-tab="<?php echo htmlspecialchars($tab['id']); ?>"
                        data-image="<?php echo htmlspecialchars($tab['image']); ?>"
                        data-title="<?php echo htmlspecialchars($tab['title']); ?>"
                        data-subtitle="<?php echo htmlspecialchars($tab['subtitle']); ?>"
                        data-title-color="<?php echo htmlspecialchars($tab['title_color']); ?>"
                        data-subtitle-color="<?php echo htmlspecialchars($tab['subtitle_color']); ?>"
                        data-button-enabled="<?php echo htmlspecialchars($tab['button_enabled']); ?>"
                        data-button1-text="<?php echo htmlspecialchars($tab['button1_text']); ?>"
                        data-button1-color="<?php echo htmlspecialchars($tab['button1_color']); ?>"
                        data-button2-text="<?php echo htmlspecialchars($tab['button2_text']); ?>"
                        data-button2-color="<?php echo htmlspecialchars($tab['button2_color']); ?>"
                    >
                        <?php echo htmlspecialchars($tab['label']); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="banner-config">
                <div class="banner-media">
                    <div class="banner-preview">
                        <img
                            src="<?php echo htmlspecialchars($bannerTabs[0]['image']); ?>"
                            alt="Banner preview"
                            data-banner-preview
                            data-default-src="<?php echo htmlspecialchars($bannerTabs[0]['image']); ?>"
                        >
                    </div>
                    <div class="banner-actions">
                        <button type="button" class="btn-upload" data-banner-upload>
                            <i class="fa-solid fa-arrow-up-from-bracket"></i>
                            Upload
                        </button>
                        <button type="button" class="btn-icon btn-delete" data-banner-delete aria-label="Delete banner">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <input class="banner-file" type="file" accept="image/*" data-banner-file hidden>
                    </div>
                </div>

                <div class="banner-text">
                    <h3>Banner Text</h3>
                    <div class="banner-text-grid">
                        <label for="bannerTitle">Title:</label>
                        <input id="bannerTitle" type="text" value="<?php echo htmlspecialchars($bannerTabs[0]['title']); ?>" data-banner-field="title">
                        <input type="color" value="<?php echo htmlspecialchars($bannerTabs[0]['title_color']); ?>" data-banner-field="title-color" aria-label="Title color">

                        <label for="bannerSubtitle">Subtitle:</label>
                        <input id="bannerSubtitle" type="text" value="<?php echo htmlspecialchars($bannerTabs[0]['subtitle']); ?>" data-banner-field="subtitle">
                        <input type="color" value="<?php echo htmlspecialchars($bannerTabs[0]['subtitle_color']); ?>" data-banner-field="subtitle-color" aria-label="Subtitle color">
                    </div>

                    <div class="banner-toggle">
                        <label for="bannerButtonToggle">Button:</label>
                        <select id="bannerButtonToggle" data-button-toggle>
                            <option value="yes" <?php echo $bannerTabs[0]['button_enabled'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="no" <?php echo $bannerTabs[0]['button_enabled'] === 'no' ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                </div>

                <div class="button-config-grid" data-button-config>
                    <div class="button-card">
                        <h4>Button 1</h4>
                        <label for="button1Text">Text:</label>
                        <select id="button1Text" data-banner-field="button1-text">
                            <?php foreach ($buttonTexts as $text): ?>
                                <option value="<?php echo htmlspecialchars($text); ?>" <?php echo $text === $bannerTabs[0]['button1_text'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($text); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Color:</label>
                        <input type="color" value="<?php echo htmlspecialchars($bannerTabs[0]['button1_color']); ?>" data-banner-field="button1-color">
                    </div>

                    <div class="button-card">
                        <h4>Button 2</h4>
                        <label for="button2Text">Text:</label>
                        <select id="button2Text" data-banner-field="button2-text">
                            <?php foreach ($buttonTexts as $text): ?>
                                <option value="<?php echo htmlspecialchars($text); ?>" <?php echo $text === $bannerTabs[0]['button2_text'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($text); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Color:</label>
                        <input type="color" value="<?php echo htmlspecialchars($bannerTabs[0]['button2_color']); ?>" data-banner-field="button2-color">
                    </div>
                </div>
            </div>
        </section>

        <section class="content-section" data-page="product-details">
            <div class="content-card">
                <h3>Product Details Page</h3>
                <div class="content-grid">
                    <div class="content-field">
                        <label>Section Title</label>
                        <input type="text" value="Product Information">
                    </div>
                    <div class="content-field">
                        <label>CTA Text</label>
                        <input type="text" value="Buy Now">
                    </div>
                </div>
            </div>
        </section>

        <section class="content-section" data-page="delivery">
            <div class="content-card">
                <h3>Delivery Page</h3>
                <div class="content-grid">
                    <div class="content-field">
                        <label>Headline</label>
                        <input type="text" value="Delivery Information">
                    </div>
                    <div class="content-field">
                        <label>Button Label</label>
                        <input type="text" value="Continue">
                    </div>
                </div>
            </div>
        </section>

        <section class="content-section" data-page="warranty">
            <div class="content-card">
                <h3>Warranty & FAQs</h3>
                <div class="content-grid">
                    <div class="content-field">
                        <label>Hero Title</label>
                        <input type="text" value="Warranty & FAQs">
                    </div>
                    <div class="content-field">
                        <label>Hero Subtitle</label>
                        <input type="text" value="Please select the category you want to know.">
                    </div>
                </div>
            </div>
        </section>

        <section class="content-section" data-page="unboxing">
            <div class="content-card">
                <h3>Unboxing & Influencer</h3>
                <div class="content-grid">
                    <div class="content-field">
                        <label>Banner Text</label>
                        <input type="text" value="Unbox the hype — watch creators try our products!">
                    </div>
                    <div class="content-field">
                        <label>Tab Title</label>
                        <input type="text" value="Influencer Reviews">
                    </div>
                </div>
            </div>
        </section>

        <section class="content-section" data-page="user-profile">
            <div class="content-card">
                <h3>User Profile</h3>
                <div class="content-grid">
                    <div class="content-field">
                        <label>Section Header</label>
                        <input type="text" value="Profile">
                    </div>
                    <div class="content-field">
                        <label>CTA Text</label>
                        <input type="text" value="Download E-receipt">
                    </div>
                </div>
            </div>
        </section>

        <section class="content-section" data-page="reservation-policy">
            <div class="content-card">
                <h3>Reservation Policy</h3>
                <div class="content-grid">
                    <div class="content-field">
                        <label>Page Title</label>
                        <input type="text" value="Reservation Policy">
                    </div>
                    <div class="content-field">
                        <label>Primary Button</label>
                        <input type="text" value="Confirm">
                    </div>
                </div>
            </div>
        </section>

        <section class="content-section" data-page="contact">
            <div class="content-card">
                <h3>Contact Us</h3>
                <div class="content-grid">
                    <div class="content-field">
                        <label>Title</label>
                        <input type="text" value="Get in touch">
                    </div>
                    <div class="content-field">
                        <label>CTA Label</label>
                        <input type="text" value="Send Message">
                    </div>
                </div>
            </div>
        </section>

        <div class="unsaved-bar" data-unsaved-bar>
            <span>Unsaved data will be deleted</span>
            <div class="unsaved-actions">
                <button type="button" class="btn-save">Save</button>
                <button type="button" class="btn-discard">Discard</button>
            </div>
        </div>
    </main>

    </div>
</div>

<script src="/admin/assets/js/content-management.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
