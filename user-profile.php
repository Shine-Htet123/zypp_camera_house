<?php
require_once __DIR__ . '/app/services/customer_auth.php';
require_once __DIR__ . '/database/user/membership.php';
require_once __DIR__ . '/database/user/profile.php';
require_once __DIR__ . '/database/user/orders.php';
customer_auth_require_login('/user-profile.php');

function user_profile_membership_range_label(array $tier): string
{
    $minSpent = (float) ($tier['min_spent'] ?? 0);
    $maxSpent = $tier['max_spent'] !== null ? (float) $tier['max_spent'] : null;

    if ($maxSpent === null) {
        return number_format($minSpent) . ' MMK and above';
    }

    if ($minSpent <= 0) {
        return 'Less than ' . number_format($maxSpent) . ' MMK';
    }

    return number_format($minSpent) . ' MMK - ' . number_format($maxSpent) . ' MMK';
}

function user_profile_membership_benefits_for_index(int $index): array
{
    $benefitSets = [
        [
            ['icon' => 'fas fa-paper-plane', 'label' => 'Telegram Group Access'],
            ['icon' => 'fas fa-rectangle-list', 'label' => 'Daily Price List'],
            ['icon' => 'fas fa-lightbulb', 'label' => 'Vlog Knowledge Sharing'],
            ['icon' => 'fas fa-bullhorn', 'label' => 'Early Campaign Access'],
            ['icon' => 'fas fa-ticket', 'label' => 'Monthly Coupon/Giveaways'],
        ],
        [
            ['icon' => 'fas fa-paper-plane', 'label' => 'Telegram Group Access'],
            ['icon' => 'fas fa-rectangle-list', 'label' => 'Daily Price List'],
            ['icon' => 'fas fa-lightbulb', 'label' => 'Vlog Knowledge Sharing'],
            ['icon' => 'fas fa-bullhorn', 'label' => 'Early Campaign Access'],
            ['icon' => 'fas fa-ticket', 'label' => 'Monthly Coupon/Giveaways'],
            ['icon' => 'fas fa-percent', 'label' => 'Membership Discounts (Cameras & Accessories)'],
        ],
        [
            ['icon' => 'fas fa-paper-plane', 'label' => 'Telegram Group Access'],
            ['icon' => 'fas fa-rectangle-list', 'label' => 'Daily Price List'],
            ['icon' => 'fas fa-lightbulb', 'label' => 'Vlog Knowledge Sharing'],
            ['icon' => 'fas fa-bullhorn', 'label' => 'Early Campaign Access'],
            ['icon' => 'fas fa-ticket', 'label' => 'Monthly Coupon/Giveaways'],
            ['icon' => 'fas fa-percent', 'label' => 'Membership Discounts (Cameras & Accessories)'],
            ['icon' => 'fas fa-link', 'label' => 'Referral Program'],
            ['icon' => 'fas fa-gift', 'label' => 'Yearly Gifts'],
        ],
    ];

    $safeIndex = min(max($index, 0), count($benefitSets) - 1);

    return $benefitSets[$safeIndex];
}

$currentCustomer = customer_auth_current_user();
$profileFlash = customer_profile_consume_flash();
$membershipSummary = is_array($currentCustomer) ? membership_fetch_user_summary((int) $currentCustomer['id']) : null;
$membershipTierCards = membership_fetch_tiers();
$addressCards = is_array($currentCustomer) ? profile_fetch_user_addresses((int) $currentCustomer['id']) : [];
$orderRows = is_array($currentCustomer) ? customer_fetch_user_orders((int) $currentCustomer['id']) : [];
$displayName = trim((string) ($currentCustomer['name'] ?? ''));
$displayEmail = trim((string) ($currentCustomer['email'] ?? ''));
$displayPublicUserId = trim((string) ($currentCustomer['public_user_id'] ?? ''));
$referralToken = trim((string) ($currentCustomer['referral_token'] ?? ''));
$referralLink = $referralToken !== '' ? app_url('/register.php?ref=' . rawurlencode($referralToken)) : '';
$socialProviders = [
    'google' => [
        'label' => 'Google',
        'column' => 'google_provider_id',
    ],
];

if ($membershipTierCards === []) {
    $membershipTierCards = [
        ['id' => 1, 'tier_name' => 'Standard', 'min_spent' => 0, 'max_spent' => 1000000],
        ['id' => 2, 'tier_name' => 'Active Creator', 'min_spent' => 1000000, 'max_spent' => 5000000],
        ['id' => 3, 'tier_name' => 'ZYPP Pro Creator', 'min_spent' => 5000000, 'max_spent' => 10000000],
        ['id' => 4, 'tier_name' => 'ZYPP Master Creator', 'min_spent' => 10000000, 'max_spent' => null],
    ];
}

$currentTierId = isset($membershipSummary['current_tier']['id']) ? (int) $membershipSummary['current_tier']['id'] : null;
$currentTierTitle = strtolower(trim((string) ($membershipSummary['current_title'] ?? '')));
$currentTierIndex = 0;

foreach ($membershipTierCards as $index => $tierCard) {
    $tierId = isset($tierCard['id']) ? (int) $tierCard['id'] : 0;
    $tierTitle = strtolower(trim((string) ($tierCard['tier_name'] ?? '')));

    if (($currentTierId !== null && $tierId === $currentTierId) || ($currentTierTitle !== '' && $tierTitle === $currentTierTitle)) {
        $currentTierIndex = $index;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <link rel="stylesheet" href="./assets/css/user-profile.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="profile-page">
        <div class="profile-layout">
            <aside class="profile-sidebar">
                <nav class="profile-nav">
                    <a href="#membership" class="active">Membership</a>
                    <a href="#profile">Profile</a>
                    <a href="#orders">My Orders</a>
                    <a href="#track-order">Track Order</a>
                    <a href="#settings">Settings</a>
                </nav>
                <a class="logout-btn" href="<?php echo htmlspecialchars(app_path('/auth/logout.php?redirect_to=/')); ?>">Log Out</a>
            </aside>

            <section class="profile-content">
                <div class="profile-section membership-overview" id="membership">
                    <div class="membership-card">
                        <div class="membership-card-main">
                            <div class="membership-copy">
                                <span class="membership-eyebrow">Membership</span>
                                <h3><?php echo htmlspecialchars((string) ($membershipSummary['current_title'] ?? 'Standard Customer')); ?></h3>
                                <?php if (!empty($membershipSummary['next_title']) && (float) ($membershipSummary['required_amount'] ?? 0) > 0): ?>
                                    <p class="required-amount">
                                        Upgrade to <strong class="next-level"><?php echo htmlspecialchars((string) $membershipSummary['next_title']); ?></strong>
                                        by purchasing more items worth <?php echo htmlspecialchars(number_format((float) ($membershipSummary['required_amount'] ?? 0))); ?> MMK.
                                    </p>
                                <?php else: ?>
                                    <p class="required-amount">You are currently at the highest membership tier.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="membership-card-side">
                            <div class="membership-progress">
                                <div class="membership-progress-meta">
                                    <span>Progress</span>
                                    <strong><?php echo (int) ($membershipSummary['progress_percent'] ?? 0); ?>%</strong>
                                </div>
                                <div class="progress-bar">
                                    <span class="progress-fill" style="width: <?php echo (int) ($membershipSummary['progress_percent'] ?? 0); ?>%;"></span>
                                </div>
                            </div>
                            <div class="membership-stats">
                                <p class="membership-meta">You joined since <strong><?php echo htmlspecialchars((string) ($membershipSummary['joined_since'] ?? '')); ?></strong></p>
                                <p class="membership-meta">You've spent <strong><?php echo htmlspecialchars(number_format((float) ($membershipSummary['total_spent'] ?? 0))); ?> MMK</strong>.</p>
                            </div>
                            <button type="button" class="membership-btn" data-open-membership-benefits>View ZYPP Benefits</button>
                        </div>
                    </div>
                </div>

                <div class="profile-section" id="profile">
                    <div class="section-header">
                        <span>Profile</span>
                    </div>

                    <div class="profile-stack">
                        <div class="profile-panel account-panel" data-account-panel>
                            <div class="panel-heading panel-heading-split">
                                <div>
                                    <span class="panel-eyebrow">Account Information</span>
                                    <h3>Manage your core account details</h3>
                                    <p class="panel-helper">Your name and email are shared across your orders and saved addresses.</p>
                                </div>
                                <button type="button" class="secondary-inline-btn" data-account-edit>Edit Account</button>
                            </div>

                            <div class="account-view" data-account-view>
                                <div class="profile-list">
                                    <div class="profile-row">
                                        <span class="label">User ID:</span>
                                        <span class="value"><?php echo htmlspecialchars($displayPublicUserId !== '' ? $displayPublicUserId : 'Not available'); ?></span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="label">Full Name:</span>
                                        <span class="value" data-account-view-field="full_name"><?php echo htmlspecialchars($displayName); ?></span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="label">Email:</span>
                                        <span class="value email" data-account-view-field="email"><?php echo htmlspecialchars($displayEmail); ?></span>
                                    </div>
                                </div>
                            </div>

                            <form class="account-form" data-account-form>
                                <div class="form-row two-col">
                                    <div class="field">
                                        <label>Full Name</label>
                                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($displayName); ?>">
                                    </div>
                                    <div class="field">
                                        <label>Email</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($displayEmail); ?>">
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="cancel-btn" data-account-cancel>Cancel</button>
                                    <button type="button" class="save-btn" data-account-save>Save Changes</button>
                                </div>
                            </form>
                        </div>

                        <div class="profile-panel addresses-panel">
                            <div class="panel-heading panel-heading-split">
                                <div>
                                    <span class="panel-eyebrow">Saved Addresses</span>
                                    <h3>Choose what fills your checkout form</h3>
                                    <p class="panel-helper">Your default address is pre-filled during checkout. Save separate cards for home, office, or studio.</p>
                                </div>
                                <button type="button" class="primary-inline-btn" data-add-address>Add New Address</button>
                            </div>

                            <div class="address-tip">
                                <i class="fa-regular fa-star"></i>
                                <span>The address marked as default is the one used first during checkout.</span>
                            </div>

                            <div class="profile-cards" data-address-list>
                                <?php foreach ($addressCards as $index => $addressCard): ?>
                                    <?php
                                    $addressId = isset($addressCard['address_id']) ? (int) $addressCard['address_id'] : 0;
                                    $street = trim((string) ($addressCard['street'] ?? ''));
                                    $township = trim((string) ($addressCard['township'] ?? ''));
                                    $city = trim((string) ($addressCard['city'] ?? ''));
                                    $phone = trim((string) ($addressCard['phone'] ?? ''));
                                    $postalCode = trim((string) ($addressCard['postal_code'] ?? ''));
                                    $isDefaultAddress = (int) ($addressCard['is_default'] ?? 0) === 1;
                                    $cardTitle = $isDefaultAddress ? 'Default Address' : 'Saved Address ' . ($index + 1);
                                    ?>
                                    <div class="profile-card<?php echo $isDefaultAddress ? ' is-default' : ''; ?>" data-address-card data-address-id="<?php echo $addressId > 0 ? $addressId : ''; ?>">
                                        <span class="card-badge">Default</span>
                                        <div class="profile-card-header">
                                            <div>
                                                <h4 class="profile-card-title"><?php echo htmlspecialchars($cardTitle); ?></h4>
                                                <p class="profile-card-subtitle"><?php echo $isDefaultAddress ? 'This address is currently used first at checkout.' : 'Saved for faster checkout.'; ?></p>
                                            </div>
                                        </div>
                                        <div class="profile-list profile-card-view">
                                            <div class="profile-row">
                                                <span class="label">Phone:</span>
                                                <span class="value" data-view-field="phone"><?php echo htmlspecialchars($phone); ?></span>
                                            </div>
                                            <div class="profile-row">
                                                <span class="label">Address:</span>
                                                <span class="value" data-view-field="address"><?php echo htmlspecialchars($street); ?></span>
                                            </div>
                                            <div class="profile-row">
                                                <span class="label">Township:</span>
                                                <span class="value" data-view-field="township"><?php echo htmlspecialchars($township); ?></span>
                                            </div>
                                            <div class="profile-row">
                                                <span class="label">City:</span>
                                                <span class="value" data-view-field="city"><?php echo htmlspecialchars($city); ?></span>
                                            </div>
                                            <div class="profile-row">
                                                <span class="label">Postal Code:</span>
                                                <span class="value" data-view-field="postal_code" data-empty-label="Optional"><?php echo htmlspecialchars($postalCode !== '' ? $postalCode : 'Optional'); ?></span>
                                            </div>
                                        </div>
                                        <form class="profile-card-form">
                                            <div class="form-row">
                                                <div class="field">
                                                    <label>Phone</label>
                                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                                                </div>
                                                <div class="field">
                                                    <label>Address</label>
                                                    <input type="text" name="address" value="<?php echo htmlspecialchars($street); ?>">
                                                </div>
                                                <div class="field">
                                                    <label>Township</label>
                                                    <input type="text" name="township" value="<?php echo htmlspecialchars($township); ?>">
                                                </div>
                                                <div class="field">
                                                    <label>City</label>
                                                    <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>">
                                                </div>
                                                <div class="field">
                                                    <label>Postal Code</label>
                                                    <input type="text" name="postal_code" value="<?php echo htmlspecialchars($postalCode); ?>" placeholder="Optional">
                                                </div>
                                            </div>
                                            <div class="card-form-actions">
                                                <button type="button" class="cancel-btn">Cancel</button>
                                                <button type="button" class="save-btn">Save Address</button>
                                            </div>
                                        </form>
                                        <div class="card-actions">
                                            <button type="button" class="card-action-btn edit" aria-label="Edit address">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                                <span>Edit</span>
                                            </button>
                                            <button type="button" class="card-action-btn delete" aria-label="Delete address">
                                                <i class="fa-regular fa-trash-can"></i>
                                                <span>Delete</span>
                                            </button>
                                            <button type="button" class="card-action-btn set-default">
                                                <i class="fa-regular fa-star"></i>
                                                <span><?php echo $isDefaultAddress ? 'Default' : 'Set Default'; ?></span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="addresses-empty<?php echo $addressCards === [] ? '' : ' hidden'; ?>" data-address-empty>
                                <i class="fa-regular fa-address-card"></i>
                                <h4>No saved addresses yet</h4>
                                <p>Add your first address so checkout can fill your details faster.</p>
                                <button type="button" class="primary-inline-btn" data-add-address>Add Your First Address</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-section" id="orders">
                    <div class="section-header">My Orders</div>
                    <div class="orders-table">
                        <div class="orders-scroll">
                            <div class="orders-inner">
                                <div class="table-row table-head">
                                    <span>Order No.</span>
                                    <span>Date</span>
                                    <span>Status</span>
                                    <span>Payment</span>
                                    <span>Total</span>
                                </div>
                                <div class="orders-body">
                                    <?php if ($orderRows === []): ?>
                                        <div class="orders-empty">No orders found yet.</div>
                                    <?php else: ?>
                                        <?php foreach ($orderRows as $orderRow): ?>
                                            <a class="table-row" href="<?php echo htmlspecialchars((string) $orderRow['detail_url']); ?>">
                                                <span><?php echo htmlspecialchars((string) $orderRow['order_no_display']); ?></span>
                                                <span><?php echo htmlspecialchars((string) $orderRow['date_display']); ?></span>
                                                <span class="status <?php echo htmlspecialchars((string) $orderRow['order_status_class']); ?>">
                                                    <?php echo htmlspecialchars((string) $orderRow['order_status_label']); ?>
                                                </span>
                                                <span class="status <?php echo htmlspecialchars((string) $orderRow['payment_status_class']); ?>">
                                                    <?php echo htmlspecialchars((string) $orderRow['payment_status_label']); ?>
                                                </span>
                                                <span><?php echo htmlspecialchars((string) $orderRow['total_display']); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-section" id="track-order">
                    <div class="section-header">Track Order</div>
                    <div class="track-card">
                        <form class="track-form" id="track-form">
                            <div class="field">
                                <label>Email</label>
                                <input type="email" name="track_email" placeholder="email@example.com">
                            </div>
                            <div class="field">
                                <label>Phone</label>
                                <input type="text" name="track_phone" placeholder="09XXXXXXXXX">
                            </div>
                            <div class="field">
                                <label>Order No.</label>
                                <input type="text" name="track_order" placeholder="202603170001" inputmode="numeric" pattern="[0-9]*" autocomplete="off" data-track-order-input>
                            </div>
                            <button type="submit" class="track-btn">Find My Order</button>
                        </form>
                    </div>

                    <div class="track-result hidden" id="track-result">
                        <div class="track-meta">
                            <h4>Order No. #202601260001</h4>
                            <p>Order Date - 20/12/2025</p>
                            <p>Order Time - 12:00:00</p>
                        </div>
                        <h5>Order Summary</h5>
                        <div class="summary-table">
                            <div class="summary-row head">
                                <span class="cell">No.</span>
                                <span class="cell">Product Name</span>
                                <span class="cell">Qty.</span>
                                <span class="cell">Price</span>
                            </div>
                            <div class="summary-row">
                                <span class="cell">1</span>
                                <span class="cell">Canon EOS R6 Mark II</span>
                                <span class="cell">1</span>
                                <span class="cell">3,000,000 MMK</span>
                            </div>
                            <div class="summary-row">
                                <span class="cell">2</span>
                                <span class="cell">Canon EOS R6 Mark II</span>
                                <span class="cell">1</span>
                                <span class="cell">3,000,000 MMK</span>
                            </div>
                            <div class="summary-total">
                                <span class="cell label">Subtotal</span>
                                <span class="cell value">6,000,000 MMK</span>
                            </div>
                            <div class="summary-total">
                                <span class="cell label">Discount</span>
                                <span class="cell value discount">-60,000 MMK</span>
                            </div>
                            <div class="summary-total">
                                <span class="cell label">Grand Total</span>
                                <span class="cell value">5,940,000 MMK</span>
                            </div>
                            <div class="free-note">Congratulations! You got <span>Free of Charges</span> for delivery!</div>
                        </div>

                        <div class="track-details">
                            <div class="detail-block">
                                <h3>Delivery Information</h3>
                                <p>Kyaw Ko Ko</p>
                                <p>kyawko@gmail.com</p>
                                <p>09/8/1230</p>
                                <p>No. 86, Pyay Road, Hlaing Township, Yangon</p>
                            </div>
                            <div class="detail-block">
                                <p><strong>Order Status</strong> <span class="status pending">Pending</span></p>
                                <p><strong>Payment Status</strong> <span class="status paid">Paid</span></p>
                                <p><strong>Additional Note</strong></p>
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</p>
                            </div>
                        </div>
                        <div class="track-actions">
                            <a id="close-track-result">Close</a>
                            <button type="button" class="download-btn">Download E-receipt</button>
                        </div>
                    </div>
                </div>

                <?php if (false): ?>
                <div class="profile-section membership-legacy" id="membership-legacy" hidden aria-hidden="true">
                    <div class="section-header">Membership</div>
                    <div class="membership-card">
                        <div class="membership-icon">
                            <img src="./assets/images/logo.png" alt="Logo or Mascort">
                        </div>
                        <div class="membership-text">
                            <h4><?php echo htmlspecialchars((string) ($membershipSummary['current_title'] ?? 'Standard Customer')); ?></h4>
                            <div class="membership-progress">
                                <div class="progress-bar">
                                    <span class="progress-fill" style="width: <?php echo (int) ($membershipSummary['progress_percent'] ?? 0); ?>%;"></span>
                                </div>
                            </div>
                            <?php if (!empty($membershipSummary['next_title']) && (float) ($membershipSummary['required_amount'] ?? 0) > 0): ?>
                                <p class="required-amount">
                                    Upgrade to <strong class="next-level"><?php echo htmlspecialchars((string) $membershipSummary['next_title']); ?></strong>
                                    by purchasing more items worth <?php echo htmlspecialchars(number_format((float) $membershipSummary['required_amount'])); ?> MMK.
                                </p>
                            <?php else: ?>
                                <p class="required-amount">You are currently at the highest membership tier.</p>
                            <?php endif; ?>
                            <p class="membership-meta">You joined since <strong><?php echo htmlspecialchars((string) ($membershipSummary['joined_since'] ?? '')); ?></strong></p>
                            <p class="membership-meta">You’ve spent <strong><?php echo htmlspecialchars(number_format((float) ($membershipSummary['total_spent'] ?? 0))); ?> MMK</strong>.</p>
                            <button type="button" class="membership-btn">View ZYPP Benefits</button>
                        </div>
                    </div>
                </div>

                <?php endif; ?>

                <div class="profile-section" id="settings">
                    <div class="section-header">Settings</div>
                    <div class="settings-list">
                        <div class="settings-row">
                            <span>Password:</span>
                            <span>**********</span>
                            <button type="button" class="icon-btn modal-trigger" data-modal-target="change-password-modal">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                        </div>
                        <a href="#" class="settings-link modal-trigger" data-modal-target="forgot-password-modal">Forgot Password</a>
                        <a href="#" class="settings-link danger modal-trigger" data-modal-target="delete-account-modal">Delete this Account</a>
                    </div>
                    <div class="social-connect-section referral-section">
                        <div class="social-connect-header">
                            <h3>Your Referral Link</h3>
                            <p>Share this link so new users can register with your referral code.</p>
                        </div>
                        <div class="social-connect-card referral-card">
                            <div class="social-connect-copy">
                                <h4>Referral Link</h4>
                                <p class="referral-link-text" data-referral-link-text>
                                    <?php echo htmlspecialchars($referralLink !== '' ? $referralLink : 'Referral link is not available yet.'); ?>
                                </p>
                            </div>
                            <?php if ($referralLink !== ''): ?>
                                <button
                                    type="button"
                                    class="social-connect-btn referral-copy-btn"
                                    data-copy-referral
                                    data-copy-value="<?php echo htmlspecialchars($referralLink); ?>"
                                >
                                    Copy Link
                                </button>
                            <?php else: ?>
                                <span class="social-connect-state unavailable">Unavailable</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="social-connect-section">
                        <div class="social-connect-header">
                            <h3>Connected Sign-In Methods</h3>
                            <p>Connect your social accounts to use quick sign-in safely.</p>
                        </div>
                        <?php if ($profileFlash): ?>
                            <div class="social-connect-flash <?php echo htmlspecialchars($profileFlash['type'] ?? 'info'); ?>">
                                <?php echo htmlspecialchars($profileFlash['message'] ?? ''); ?>
                            </div>
                        <?php endif; ?>
                        <div class="social-connect-list">
                            <?php foreach ($socialProviders as $providerKey => $providerData): ?>
                                <?php
                                $providerColumn = $providerData['column'];
                                $isAvailable = is_array($currentCustomer) && array_key_exists($providerColumn, $currentCustomer);
                                $isConnected = $isAvailable && !empty($currentCustomer[$providerColumn]);
                                $connectUrl = '/auth/oauth_start.php?provider=' . rawurlencode($providerKey) . '&redirect_to=' . rawurlencode('/user-profile.php#settings') . '&mode=connect';
                                ?>
                                <div class="social-connect-card<?php echo $isConnected ? ' is-connected' : ''; ?>">
                                    <div class="social-connect-copy">
                                        <h4><?php echo htmlspecialchars($providerData['label']); ?></h4>
                                        <p>
                                            <?php if (!$isAvailable): ?>
                                                Social linking is unavailable until the provider columns are added to the `users` table.
                                            <?php elseif ($isConnected): ?>
                                                Connected to your account.
                                            <?php else: ?>
                                                Not connected yet.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <?php if (!$isAvailable): ?>
                                        <span class="social-connect-state unavailable">Unavailable</span>
                                    <?php elseif ($isConnected): ?>
                                        <span class="social-connect-state connected">Connected</span>
                                    <?php else: ?>
                                        <a class="social-connect-btn" href="<?php echo htmlspecialchars($connectUrl); ?>">Connect</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="mobile-logout">
                    <a class="logout-btn" href="<?php echo htmlspecialchars(app_path('/auth/logout.php?redirect_to=/')); ?>">Log Out</a>
                </div>
            </section>
        </div>
    </main>

    <div class="modal-overlay membership-benefits-modal" id="membership-benefits-modal" aria-hidden="true">
        <div class="modal-card benefits-modal-card" role="dialog" aria-modal="true" aria-labelledby="membership-benefits-title">
            <button type="button" class="modal-close benefits-modal-close" aria-label="Close">&times;</button>
            <div class="benefits-modal-header">
                <h2 id="membership-benefits-title">ZYPP Member Benefits</h2>
                <p>Level up your membership and score bigger benefits every step of the way.</p>
            </div>
            <div class="benefits-tier-grid">
                <?php foreach ($membershipTierCards as $index => $tierCard): ?>
                    <?php
                    $isCurrentTier = $index === $currentTierIndex;
                    $isCompletedTier = $index < $currentTierIndex;
                    $statusClass = $isCurrentTier ? 'current' : ($isCompletedTier ? 'completed' : 'upcoming');
                    $statusLabel = $isCurrentTier ? 'You are Here' : ($isCompletedTier ? 'Completed' : "Let's Go");
                    $statusHoverLabel = $isCurrentTier ? 'Browse Products' : ($isCompletedTier ? 'Shop Again' : 'Unlock Here');
                    $benefits = user_profile_membership_benefits_for_index($index);
                    ?>
                    <article class="benefits-tier-card <?php echo htmlspecialchars($statusClass); ?>">
                        <div class="benefits-tier-head">
                            <h3><?php echo htmlspecialchars((string) ($tierCard['tier_name'] ?? 'Membership Tier')); ?></h3>
                            <p class="benefits-tier-range"><?php echo htmlspecialchars(user_profile_membership_range_label($tierCard)); ?></p>
                            <a
                                class="benefits-tier-status <?php echo htmlspecialchars($statusClass); ?>"
                                href="<?php echo htmlspecialchars(app_path('/products.php')); ?>"
                            >
                                <span class="status-default"><?php echo htmlspecialchars($statusLabel); ?></span>
                                <span class="status-hover"><?php echo htmlspecialchars($statusHoverLabel); ?></span>
                            </a>
                        </div>
                        <ul class="benefits-tier-list">
                            <?php foreach ($benefits as $benefit): ?>
                                <li>
                                    <i class="<?php echo htmlspecialchars((string) ($benefit['icon'] ?? 'fas fa-check')); ?>" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars((string) ($benefit['label'] ?? 'Benefit')); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="address-delete-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="address-delete-title">
            <div class="modal-header">
                <h3 id="address-delete-title">Delete Address</h3>
                <button type="button" class="modal-close" aria-label="Close">×</button>
            </div>
            <p class="modal-subtitle danger-text">
                This address will be removed permanently. Are you sure?
            </p>
            <div class="modal-actions">
                <button type="button" class="btn ghost modal-cancel">Cancel</button>
                <button type="button" class="btn danger" id="confirm-address-delete">Delete</button>
            </div>
        </div>
    </div>

    <?php include('./footer.php') ?>

    <script src="./assets/js/user-profile.js"></script>
</body>
</html>
