<?php
$tiers = [
    [
        'title' => 'Standard Customer',
        'range' => '<1,500,000 MMK',
        'min' => '',
        'max' => '1500000',
        'ref_type' => 'Percentage',
        'ref_value' => '5',
    ],
    [
        'title' => 'ZYPP Active Vlogger',
        'range' => '1,500,000 MMK - 50,000,000 MMK',
        'min' => '1500000',
        'max' => '50000000',
        'ref_type' => 'Percentage',
        'ref_value' => '10',
    ],
    [
        'title' => 'ZYPP Pro Vlogger',
        'range' => '5,000,000 MMK - 10,000,000 MMK',
        'min' => '5000000',
        'max' => '10000000',
        'ref_type' => 'Percentage',
        'ref_value' => '20',
    ],
    [
        'title' => 'ZYPP Master Vlogger',
        'range' => '>10,000,000 MMK',
        'min' => '10000000',
        'max' => '',
        'ref_type' => 'Percentage',
        'ref_value' => '25',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/membership-tiers.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content membership-page">
        <header class="membership-header">
            <h1>Membership Tiers</h1>
        </header>

        <section class="membership-layout">
            <div class="membership-left">
                <div class="tier-cards">
                    <?php foreach ($tiers as $tier): ?>
                        <article
                            class="tier-card"
                            data-tier-title="<?php echo htmlspecialchars($tier['title']); ?>"
                            data-tier-range="<?php echo htmlspecialchars($tier['range']); ?>"
                            data-tier-min="<?php echo htmlspecialchars($tier['min']); ?>"
                            data-tier-max="<?php echo htmlspecialchars($tier['max']); ?>"
                            data-tier-ref-type="<?php echo htmlspecialchars($tier['ref_type']); ?>"
                            data-tier-ref-value="<?php echo htmlspecialchars($tier['ref_value']); ?>"
                        >
                            <div class="tier-icon">
                                <i class="fa-regular fa-image"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($tier['title']); ?></h3>
                            <p><?php echo htmlspecialchars($tier['range']); ?></p>
                            <div class="tier-actions">
                                <button type="button" class="icon-btn edit-tier" aria-label="Edit tier">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="icon-btn delete delete-tier" aria-label="Delete tier">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="membership-divider"></div>

            <div class="membership-right">
                <article class="tier-form-card">
                    <h2>Create Member Tier</h2>
                    <div class="tier-form">
                        <label>
                            <span>Member Tier:</span>
                            <input type="text" class="tier-input" placeholder="">
                        </label>
                        <label>
                            <span>Min Spent:</span>
                            <input type="text" class="tier-input" placeholder="">
                        </label>
                        <label>
                            <span>Max Spent:</span>
                            <input type="text" class="tier-input" placeholder="">
                        </label>
                        <label>
                            <span>Referral Discount Type:</span>
                            <select class="tier-select" id="createTierRefType" data-unit-target="createRefUnit">
                                <option></option>
                                <option>Percentage</option>
                                <option>Fixed</option>
                            </select>
                        </label>
                        <label class="input-suffix">
                            <span>Referral Discount Value:</span>
                            <div class="suffix-field">
                                <input type="text" class="tier-input" placeholder="">
                                <span class="suffix" id="createRefUnit">%</span>
                            </div>
                        </label>
                    </div>
                    <div class="tier-form-actions">
                        <button type="button" class="btn-create">Create</button>
                        <button type="button" class="btn-reset">Reset</button>
                    </div>
                </article>
            </div>
        </section>

        <template id="tierEditTemplate">
            <div class="tier-edit">
                <h3 class="tier-edit-title">Edit Member Tier</h3>
                <div class="tier-form">
                    <label>
                        <span>Member Tier:</span>
                        <input type="text" class="tier-input edit-name" value="">
                    </label>
                    <label>
                        <span>Min Spent:</span>
                        <input type="text" class="tier-input edit-min" value="">
                    </label>
                    <label>
                        <span>Max Spent:</span>
                        <input type="text" class="tier-input edit-max" value="">
                    </label>
                    <label>
                        <span>Referral Discount Type:</span>
                        <select class="tier-select edit-ref-type" data-unit-target="editRefUnit">
                            <option>Percentage</option>
                            <option>Fixed</option>
                        </select>
                    </label>
                    <label class="input-suffix">
                        <span>Referral Discount Value:</span>
                        <div class="suffix-field">
                            <input type="text" class="tier-input edit-ref-value" value="">
                            <span class="suffix" id="editRefUnit">%</span>
                        </div>
                    </label>
                </div>
                <div class="tier-form-actions">
                    <button type="button" class="btn-confirm">Confirm</button>
                    <button type="button" class="btn-cancel">Cancel</button>
                </div>
            </div>
        </template>
    </main>

    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/admin/assets/js/membership-tiers.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
