    <?php
    require_once __DIR__ . '/app/services/customer_auth.php';
    require_once __DIR__ . '/app/services/cart.php';
    require_once __DIR__ . '/app/services/products.php';
    require_once __DIR__ . '/config/app.php';

    $currentRequestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $customerUser = customer_auth_current_user();
    $authFlash = customer_auth_consume_flash();
    $authPanel = is_array($authFlash) ? ($authFlash['panel'] ?? '') : '';
    $authMessage = is_array($authFlash) ? ($authFlash['message'] ?? '') : '';
    $authType = is_array($authFlash) ? ($authFlash['type'] ?? 'error') : 'error';
    $navCategories = catalog_fetch_nav_categories();
    $navBrands = catalog_fetch_nav_brands();
    $cartCount = customer_cart_count();
    $searchQuery = htmlspecialchars((string) ($_GET['q'] ?? ''));
    $homePath = app_path('/index.php');
    $productsSearchPath = app_path('/products.php');
    $bundlesPath = app_path('/bundles.php');
    $searchEndpointPath = app_path('/search-products.php');
    $wholesalePath = app_path('/wholesale.php');
    $aboutPath = app_path('/about.php');
    $warrantyFaqPath = app_path('/warranty-FAQ.php');
    $reservationPolicyPath = app_path('/reservation-policy.php');
    $deliveryPolicyPath = app_path('/delivery-policy.php');
    $paymentInfoPath = app_path('/payment-information.php');
    $profilePath = app_path('/user-profile.php');
    $cartPath = app_path('/cart.php');
    $loginPath = app_path('/auth/login.php');
    $registerPath = app_path('/auth/register.php');
    $forgotPasswordPath = app_path('/auth/forgot_password.php');
    $profileSecurityPath = app_path('/profile/security.php');
    $oauthStartPath = app_path('/auth/oauth_start.php');
    $logoPath = app_path('/storage/uploads/contents/logo.png');

    $renderQuickLoginButtons = static function (string $redirectTo, string $oauthStartPath): string {
        $providers = [
            'google' => ['label' => 'Continue with Google', 'icon_html' => '<img src="./assets/images/google-icon.png" alt="">'],
        ];

        $html = '';
        foreach ($providers as $provider => $providerData) {
            $html .= '<a href="' . htmlspecialchars($oauthStartPath) . '?provider=' . rawurlencode($provider) . '&redirect_to=' . rawurlencode($redirectTo) . '" class="quick-login-btn quick-login-link" data-provider="' . htmlspecialchars($provider) . '">';
            $html .= $providerData['icon_html'];
            $html .= '<span>' . htmlspecialchars($providerData['label']) . '</span>';
            $html .= '</a>';
        }

        return $html;
    };

    $renderSearchBox = static function (string $queryValue, string $productsPath, string $endpointPath): string {
        ob_start();
        ?>
        <div class="search-bar-container">
            <form
                action="<?php echo htmlspecialchars($productsPath); ?>"
                method="GET"
                class="search-box"
                autocomplete="off"
                data-search-form
                data-products-url="<?php echo htmlspecialchars($productsPath); ?>"
                data-search-endpoint="<?php echo htmlspecialchars($endpointPath); ?>"
            >
                <input type="text" name="q" value="<?php echo $queryValue; ?>" placeholder="Search..." data-search-input>
                <button type="reset" class="resetbtn" aria-label="Clear search">
                    <i class="fa-solid fa-xmark reset"></i>
                </button>
                <button type="submit" class="search" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
            <div class="search-bar-content">
                <div class="search-loading">
                    <video autoplay muted loop playsinline class="search-loading-video">
                        <source src="./assets/images/Search-box-loading.webm" type="video/webm">
                    </video>
                </div>
                <div class="search-result"></div>
                <div class="search-empty">No products found.</div>
                <hr>
                <div class="view-all">
                    <a href="<?php echo htmlspecialchars($productsPath); ?>">VIEW ALL RESULTS</a>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    };
    ?>
    <?php include 'loading.php'; ?>
    <!--- Navigation Bar Start ---->
    <section class="top">
        <div class="navbar">
            <div class="nav-left">
                <div class="logo">
                    <a href="<?php echo htmlspecialchars($homePath); ?>">
                        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="ZYPP Camera House Logo">
                    </a>
                </div>
                <div class="nav-links">
                    <a href="<?php echo htmlspecialchars($homePath); ?>">Home</a>
                    <div class="category-container">
                        <a href="<?php echo htmlspecialchars($productsSearchPath); ?>" class="dropdown">Shop</a>
                        <i class="fa-solid fa-chevron-down"></i>
                        <div class="category-dropdown">
                            <div class="dropdown-container">
                                <a href="<?php echo htmlspecialchars($bundlesPath); ?>" class="dropdown">Bundles</a>
                            </div>
                            <?php if (!empty($navBrands)): ?>
                                <div class="dropdown-container">
                                    <a href="<?php echo htmlspecialchars($productsSearchPath); ?>" class="dropdown">Brands</a>
                                    <div class="dropdown-content">
                                        <?php foreach ($navBrands as $brand): ?>
                                            <div class="dropdown-link">
                                                <a href="<?php echo htmlspecialchars($productsSearchPath . '?brand=' . rawurlencode($brand['name'])); ?>"><?php echo htmlspecialchars($brand['name']); ?></a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php foreach ($navCategories as $category): ?>
                                <div class="dropdown-container">
                                    <a href="<?php echo htmlspecialchars($productsSearchPath . '?category=' . rawurlencode($category['name'])); ?>" class="dropdown"><?php echo htmlspecialchars($category['name']); ?></a>
                                    <?php if (!empty($category['sub_categories'])): ?>
                                        <div class="dropdown-content">
                                            <?php foreach ($category['sub_categories'] as $subCategory): ?>
                                                <div class="dropdown-link">
                                                    <a href="<?php echo htmlspecialchars($productsSearchPath . '?category=' . rawurlencode($category['name']) . '&sub_category=' . rawurlencode($subCategory['name'])); ?>"><?php echo htmlspecialchars($subCategory['name']); ?></a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="<?php echo htmlspecialchars($wholesalePath); ?>">Wholesale</a>
                    <a href="<?php echo htmlspecialchars($aboutPath); ?>">About</a>
                    <div class="dropdown-container">
                        <a class="dropdown">Support</a>
                        <i class="fa-solid fa-chevron-down"></i>
                        <div class="dropdown-content" style="padding-left: 0;">
                            <div class="dropdown-link">
                                <a href="<?php echo htmlspecialchars($warrantyFaqPath); ?>">Warranty & FAQs</a>
                            </div>
                            <div class="dropdown-link">
                                <a href="<?php echo htmlspecialchars($reservationPolicyPath); ?>">Reservation Policy</a>
                            </div>
                            <div class="dropdown-link">
                                <a href="<?php echo htmlspecialchars($deliveryPolicyPath); ?>">Delivery Policy</a>
                            </div>
                            <div class="dropdown-link">
                                <a href="<?php echo htmlspecialchars($paymentInfoPath); ?>">Payment Info</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nav-right">
                <?php echo $renderSearchBox($searchQuery, $productsSearchPath, $searchEndpointPath); ?>
                <div class="user-shortcuts">
                    <div class="user-profile" data-authenticated="<?php echo $customerUser ? 'true' : 'false'; ?>" <?php echo $customerUser ? '' : 'id="user-icon"'; ?>>
                        <a href="<?php echo $customerUser ? htmlspecialchars($profilePath) : '#'; ?>" aria-label="<?php echo $customerUser ? 'My profile' : 'Sign in'; ?>">
                            <i class="fa-regular fa-user"></i>
                        </a>
                    </div>
                    <div class="cart">
                        <a href="<?php echo htmlspecialchars($cartPath); ?>" class="cart-icon">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <div class="cart-count">
                                <span><?php echo (int) $cartCount; ?></span>
                            </div>
                        </a>
                    </div>
                    <div class="menu-bar" id="menu-bar">
                        <i class="fa-solid fa-bars"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--- Navigation Bar End ---->

    <!--Sidebar Start --->
    <section class="menu-container" id="menu-container" data-auth-panel="<?php echo htmlspecialchars($authPanel); ?>">
        <div class="blank-space" id="black-space"></div>
        <div class="user-menu" id="user-menu">
            <div id="close-menu">
                <i class="fa-solid fa-xmark"></i>
            </div>
            <div class="login" id="login-panel">
                <h2>Log in to your account</h2>
                <form action="<?php echo htmlspecialchars($loginPath); ?>" method="POST" id="login-form" novalidate>
                    <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($currentRequestUri); ?>">
                    <div class="input">
                        <span>Email:</span>
                        <input type="email" name="email">
                    </div>
                    <div class="input">
                        <span>Password:</span>
                        <input type="password" name="password" class="password">
                        <div class="eyes togglePassword">
                            <i class="fa-solid fa-eye"></i>
                            <i class="fa-solid fa-eye-slash"></i>
                        </div>
                    </div>
                    <p class="form-warning<?php echo $authPanel === 'login' && $authMessage !== '' ? ' show' : ''; ?><?php echo $authPanel === 'login' && $authType === 'success' ? ' success' : ''; ?>" id="login-warning" aria-live="polite"><?php echo $authPanel === 'login' ? htmlspecialchars($authMessage) : ''; ?></p>
                    <button type="button" id="forgetpwd" class="modal-trigger" data-modal-target="forgot-password-modal">
                        Forgot Password
                    </button>
                    <button type="submit" class="login-btn">Login</button>
                    <span class="register-link">Don't have an account? <a href="#" id="register">Register</a></span>
                </form>
                <div class="quick-login">
                    <?php echo $renderQuickLoginButtons($currentRequestUri, $oauthStartPath); ?>
                </div>
            </div>
            <div class="register" id="register-panel">
                <h2>Create an Account</h2>
                <form action="<?php echo htmlspecialchars($registerPath); ?>" method="POST" id="register-form" novalidate>
                    <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($currentRequestUri); ?>">
                    <div class="input">
                        <span>First Name:</span>
                        <input type="text" name="firstname">
                    </div>
                    <div class="input">
                        <span>Last Name:</span>
                        <input type="text" name="lastname">
                    </div>
                    <div class="input">
                        <span>Email:</span>
                        <input type="email" name="email">
                    </div>
                    <div class="input">
                        <span>Password:</span>
                        <input type="password" name="password" class="password">
                        <div class="eyes togglePassword">
                            <i class="fa-solid fa-eye"></i>
                            <i class="fa-solid fa-eye-slash"></i>
                        </div>
                    </div>
                    <p class="form-warning<?php echo $authPanel === 'register' && $authMessage !== '' ? ' show' : ''; ?><?php echo $authPanel === 'register' && $authType === 'success' ? ' success' : ''; ?>" id="register-warning" aria-live="polite"><?php echo $authPanel === 'register' ? htmlspecialchars($authMessage) : ''; ?></p>
                    <button type="submit" class="register-btn">Register</button>
                    <span class="login-link">Already have an account? <a href="#" id="login-link">Login</a></span>
                    <div class="quick-login">
                        <?php echo $renderQuickLoginButtons($currentRequestUri, $oauthStartPath); ?>
                    </div>
                </form>
            </div>
            <div class="nav-menu">
                <div class="logo">
                    <a href="<?php echo htmlspecialchars($homePath); ?>">
                        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="ZYPP Camera House Logo">
                    </a>
                </div>
                <?php echo $renderSearchBox($searchQuery, $productsSearchPath, $searchEndpointPath); ?>
                <div class="nav-links">
                    <a href="<?php echo htmlspecialchars($homePath); ?>">Home</a>
                    <a href="<?php echo htmlspecialchars($productsSearchPath); ?>" class="dropdown">Shop</a>
                    <a href="<?php echo htmlspecialchars($bundlesPath); ?>">Bundles</a>
                    <a href="<?php echo htmlspecialchars($wholesalePath); ?>">Wholesale</a>
                    <a href="<?php echo htmlspecialchars($aboutPath); ?>">About</a>
                    <div class="category-container" id="support">
                        <a class="support">Support</a>
                        <i class="fa-solid fa-chevron-down" id="support-chevron"></i>
                    </div>
                    <div class="support-dropdown" id="support-content">
                        <a href="<?php echo htmlspecialchars($warrantyFaqPath); ?>">Warranty & FAQs</a>
                        <a href="<?php echo htmlspecialchars($reservationPolicyPath); ?>">Reservation Policy</a>
                        <a href="<?php echo htmlspecialchars($deliveryPolicyPath); ?>">Delivery Policy</a>
                        <a href="<?php echo htmlspecialchars($paymentInfoPath); ?>">Payment Info</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--Sidebar End--->

    <div class="modal-overlay" id="forgot-password-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="forgot-password-title">
            <div class="modal-header">
                <h3 id="forgot-password-title">Forgot Password</h3>
                <button type="button" class="modal-close" aria-label="Close">Ã—</button>
            </div>
            <p class="modal-subtitle">Enter your email and we will send a reset link.</p>
            <form class="modal-form" id="forgot-password-form" action="<?php echo htmlspecialchars($forgotPasswordPath); ?>" method="post">
                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($currentRequestUri); ?>">
                <div class="field">
                    <label>Email</label>
                    <input type="email" name="reset_email" placeholder="email@example.com">
                </div>
                <p class="form-warning" id="forgot-password-feedback" aria-live="polite"></p>
                <div class="modal-actions">
                    <button type="button" class="btn ghost modal-cancel">Cancel</button>
                    <button type="submit" class="btn primary">Send Reset Link</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="change-password-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="change-password-title">
            <div class="modal-header">
                <h3 id="change-password-title">Change Password</h3>
                <button type="button" class="modal-close" aria-label="Close">Ã—</button>
            </div>
            <form class="modal-form">
                <div class="field">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢">
                </div>
                <div class="field">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢">
                </div>
                <div class="field">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn ghost modal-cancel">Cancel</button>
                    <button type="submit" class="btn primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="delete-account-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="delete-account-title">
            <div class="modal-header">
                <h3 id="delete-account-title">Delete Account</h3>
                <button type="button" class="modal-close" aria-label="Close">Ã—</button>
            </div>
            <p class="modal-subtitle danger-text">
                This action is permanent and cannot be undone.
            </p>
            <form class="modal-form">
                <div class="field">
                    <label>Type DELETE to confirm</label>
                    <input type="text" name="delete_confirm" placeholder="DELETE">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn ghost modal-cancel">Cancel</button>
                    <button type="button" class="btn danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>

