/*Sidebar Toggle */

/*User Icon to open Sidebar*/
const userProfile = document.querySelector(".user-profile");
const userProfileLink = userProfile ? userProfile.querySelector("a") : null;
const userIcon = document.getElementById("user-icon");
const menuContainer = document.getElementById("menu-container");
const blackSpace = document.getElementById("black-space");
const sideBarClose = document.getElementById("close-menu");
const userMenu = document.getElementById("user-menu");

const registerLink = document.getElementById("register");
const logInLink = document.getElementById("login-link");
const registerPanel = document.getElementById("register-panel");
const loginPanel = document.getElementById("login-panel");
const navMenu = document.querySelector(".nav-menu");

const menuBar = document.getElementById("menu-bar");
const support = document.getElementById("support");
const supportContent = document.getElementById("support-content");
const supportChevron = document.getElementById("support-chevron");

const openSidebar = (mode) => {
    if (!menuContainer || !userMenu) return;
    menuContainer.style.pointerEvents = "auto";
    userMenu.style.transform = "translateX(0)";
    userMenu.style.animation = "slideIn 1s ease 1 normal forwards";

    if (mode === "nav") {
        if (navMenu) navMenu.style.display = "flex";
        if (loginPanel) loginPanel.style.display = "none";
        if (registerPanel) registerPanel.style.display = "none";
        userMenu.classList.add("compact");
        userMenu.classList.remove("normal");
        return;
    }

    if (navMenu) navMenu.style.display = "none";
    if (loginPanel) loginPanel.style.display = mode === "register" ? "none" : "flex";
    if (registerPanel) registerPanel.style.display = mode === "register" ? "flex" : "none";
    userMenu.classList.remove("compact");
    userMenu.classList.add("normal");
};

const closeSidebar = () => {
    if (!menuContainer || !userMenu) return;
    menuContainer.style.pointerEvents = "none";
    userMenu.style.transform = "translateX(100%)";
    userMenu.style.animation = "slideOut 1s ease 1 normal forwards";
    if (loginPanel) loginPanel.style.display = "none";
    if (registerPanel) registerPanel.style.display = "none";
    if (navMenu) navMenu.style.display = "none";
};

menuBar?.addEventListener("click", () => {
    openSidebar("nav");
});

userIcon?.addEventListener("click", (event) => {
    if (userProfile?.dataset.authenticated === "true") return;
    event.preventDefault();
    openSidebar("login");
});

blackSpace?.addEventListener("click", () => {
    closeSidebar();
});

sideBarClose?.addEventListener("click", () => {
    closeSidebar();
});

/*Login to Register Toggle*/
registerLink?.addEventListener("click", (event) => {
    event.preventDefault();
    if (loginPanel) loginPanel.style.display = "none";
    if (registerPanel) registerPanel.style.display = "flex";
});

/*Register to Login toggle */
logInLink?.addEventListener("click", (event) => {
    event.preventDefault();
    if (registerPanel) registerPanel.style.display = "none";
    if (loginPanel) loginPanel.style.display = "flex";
});

const initialAuthPanel = menuContainer?.dataset.authPanel || "";
if (initialAuthPanel === "login" || initialAuthPanel === "register") {
    openSidebar(initialAuthPanel);
}

/*Navbar Offset on Scroll*/
const navbar = document.querySelector('.navbar');
const navTop = navbar ? navbar.offsetTop : 0;

window.addEventListener('scroll', () => {
    if (!navbar) return;
    if (window.scrollY > navTop) {
        navbar.classList.add('glass');
    } else {
        navbar.classList.remove('glass');
    }
});

/*Navbar Support Dropdown Toggle*/
support?.addEventListener("click", () => {
    if (!supportContent || !supportChevron) return;
    if (supportContent.style.display === "flex") {
        supportContent.style.animation = "slideDown 0.5s ease forwards";

        setTimeout(() => {
            supportContent.style.display = "none";
        }, 500);
    } else {
        supportContent.style.display = "flex";
        supportContent.style.animation = "slideUp 0.5s ease forwards";
    }

    if (supportChevron.style.transform === "rotate(180deg)") {
        supportChevron.style.transform = "rotate(0deg)";
        supportChevron.style.transition = "transform 0.3s ease";
    } else {
        supportChevron.style.transform = "rotate(180deg)";
    }
});
/* Category Dropdown Menu */
const dropdown = document.querySelector(".dropdown");
const categoryDropdown = document.querySelector(".category-dropdown");
const categoryContainer = document.querySelector(".category-container");

let isOpen = false;

function openMenu() {
    isOpen = true;
    categoryDropdown.classList.add("show");
}

function closeMenu() {
    isOpen = false;
    categoryDropdown.classList.remove("show");
}

dropdown.addEventListener("mouseenter", openMenu);

categoryDropdown.addEventListener("mouseenter", () => {
    if (isOpen) openMenu();
});

categoryContainer.addEventListener("mouseenter", () => {
    if (isOpen) openMenu();
});

dropdown.addEventListener("mouseleave", () => {
    setTimeout(() => {
        if (
            !categoryDropdown.matches(":hover") &&
            !categoryContainer.matches(":hover")
        ) {
            closeMenu();
        }
    }, 100); // delay prevents flicker when moving from dropdown to menu
});

categoryDropdown.addEventListener("mouseleave", () => {
    setTimeout(() => {
        if (!dropdown.matches(":hover") && !categoryContainer.matches(":hover")) {
            closeMenu();
        }
    }, 100);
});

categoryContainer.addEventListener("mouseleave", () => {
    setTimeout(() => {
        if (!dropdown.matches(":hover") && !categoryDropdown.matches(":hover")) {
            closeMenu();
        }
    }, 100);
});

/* End of Category Dropdown Menu */

/*Sub-Category Dropdown Menu*/
/*Dropdown Margin alignment */
const catItems = document.querySelectorAll(
    ".category-dropdown > .dropdown-container",
);
const total = catItems.length;
const mid = Math.floor(total / 2);

catItems.forEach((item, i) => {
    if (i < mid) item.dataset.pos = "left";
    else if (i > mid) item.dataset.pos = "right";
    else item.dataset.pos = "center";
});

/*Dropdown toggle*/
const subMenus = document.querySelectorAll(".dropdown-container");

subMenus.forEach((menu) => {
    const trigger = menu.querySelector(".dropdown");
    const content = menu.querySelector(".dropdown-content");

    if (!trigger || !content) return;

    let open = false;

    function openSub() {
        open = true;
        content.classList.add("show");
    }

    function closeSub() {
        open = false;
        content.classList.remove("show");
    }

    trigger.addEventListener("mouseenter", openSub);

    menu.addEventListener("mouseleave", () => {
        setTimeout(() => {
            if (!menu.matches(":hover")) closeSub();
        }, 120);
    });

    content.addEventListener("mouseenter", openSub);
});
/*Sub-Category Dropdown Menu End*/

/* ===============================
        Search Bar Functionality
   =============================== */

// Loop through ALL search bars (navbar + menu)
document
    .querySelectorAll(".search-bar-container")
    .forEach((searchContainer) => {
        // Core elements
        const searchForm = searchContainer.querySelector(".search-box");
        const searchInput = searchContainer.querySelector("input");
        const resetBtn = searchContainer.querySelector(".resetbtn");
        const resultsBox = searchContainer.querySelector(".search-bar-content");
        const resultsList = searchContainer.querySelector(".search-result");
        const emptyState = searchContainer.querySelector(".search-empty");
        const viewAll = searchContainer.querySelector(".view-all");
        const viewAllLink = viewAll ? viewAll.querySelector("a") : null;
        const divider = searchContainer.querySelector("hr");
        const endpointUrl = searchForm?.dataset.searchEndpoint || "search-products.php";
        const productsUrl = searchForm?.dataset.productsUrl || "products.php";

        // Safety check (in case markup changes)
        if (!searchInput) return;

        // Loading elements
        const loadingScreen = searchContainer.querySelector(".search-loading");
        const loadingVideo = loadingScreen
            ? loadingScreen.querySelector("video")
            : null;
        let debounceTimer = null;
        let activeController = null;

        const escapeHtml = (value) =>
            String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");

        const setViewAllHref = (query) => {
            if (!viewAllLink) return;
            const nextUrl = new URL(productsUrl, window.location.origin);
            if (query) {
                nextUrl.searchParams.set("q", query);
            }
            viewAllLink.href = nextUrl.toString();
        };

        const hidePanels = () => {
            if (loadingScreen) loadingScreen.style.display = "none";
            if (resultsList) {
                resultsList.style.display = "none";
                resultsList.innerHTML = "";
            }
            if (emptyState) emptyState.style.display = "none";
            if (viewAll) viewAll.style.display = "none";
            if (divider) divider.style.display = "none";
        };

        const renderResults = (items, query) => {
            if (!resultsList) return;

            if (!items.length) {
                if (emptyState) emptyState.style.display = "flex";
                if (viewAll) viewAll.style.display = "flex";
                if (divider) divider.style.display = "block";
                return;
            }

            const markup = items
                .map((item) => {
                    const specs = Array.isArray(item.specs) && item.specs.length
                        ? `
                            <div class="specs">
                                <span>Specifications</span>
                                <ul>${item.specs
                                    .map((spec) => `<li>${escapeHtml(spec)}</li>`)
                                    .join("")}</ul>
                            </div>
                        `
                        : `
                            <div class="specs">
                                <span>Brand</span>
                                <ul><li>${escapeHtml(item.brand_name || "")}</li></ul>
                            </div>
                        `;

                    return `
                        <a href="${escapeHtml(item.detail_url)}" class="result-item">
                            <div class="item-img">
                                <img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.name)}">
                            </div>
                            ${specs}
                            <div class="item-name">
                                <span>${escapeHtml(item.name)}</span>
                            </div>
                            <div class="price-container">
                                <span class="discounted-price">${escapeHtml(item.price)}</span>
                            </div>
                        </a>
                    `;
                })
                .join("");

            resultsList.innerHTML = markup;
            resultsList.style.display = "grid";
            if (viewAll) viewAll.style.display = "flex";
            if (divider) divider.style.display = "block";
        };

        const fetchResults = async (query) => {
            if (activeController) {
                activeController.abort();
            }

            activeController = new AbortController();
            searchContainer.classList.add("show-results");
            hidePanels();

            if (loadingScreen) {
                loadingScreen.style.display = "flex";
                if (loadingVideo) {
                    loadingVideo.currentTime = 0;
                    loadingVideo.play().catch(() => {});
                }
            }

            try {
                const requestUrl = new URL(endpointUrl, window.location.origin);
                requestUrl.searchParams.set("q", query);
                requestUrl.searchParams.set("limit", "6");

                const response = await fetch(requestUrl.toString(), {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    signal: activeController.signal,
                    credentials: "same-origin",
                });

                if (!response.ok) {
                    throw new Error("Search request failed.");
                }

                const payload = await response.json();
                hidePanels();
                renderResults(Array.isArray(payload.items) ? payload.items : [], query);
            } catch (error) {
                if (error.name === "AbortError") return;
                hidePanels();
                if (emptyState) {
                    emptyState.textContent = "Unable to load search results.";
                    emptyState.style.display = "flex";
                }
            } finally {
                if (loadingVideo) loadingVideo.pause();
            }
        };

        const updateState = () => {
            const query = searchInput.value.trim();
            searchContainer.classList.toggle("active", query !== "");
            setViewAllHref(query);

            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }

            if (query === "") {
                if (activeController) {
                    activeController.abort();
                    activeController = null;
                }
                searchContainer.classList.remove("show-results");
                hidePanels();
                if (emptyState) {
                    emptyState.textContent = "No products found.";
                }
                return;
            }

            debounceTimer = setTimeout(() => {
                fetchResults(query);
            }, 250);
        };

        searchInput.addEventListener("focus", updateState);
        searchInput.addEventListener("input", updateState);

        if (resetBtn) {
            resetBtn.addEventListener("click", (event) => {
                event.preventDefault();
                searchInput.value = "";
                searchInput.focus();
                updateState();
            });
        }

        searchForm?.addEventListener("submit", () => {
            const query = searchInput.value.trim();
            setViewAllHref(query);
            if (query) {
                const nextUrl = new URL(productsUrl, window.location.origin);
                nextUrl.searchParams.set("q", query);
                searchForm.action = nextUrl.pathname;
            }
        });

        document.addEventListener("click", (e) => {
            if (!searchContainer.contains(e.target)) {
                searchContainer.classList.remove("active", "show-results");
                if (activeController) {
                    activeController.abort();
                    activeController = null;
                }
                hidePanels();
                if (loadingVideo) loadingVideo.pause();
            }
        });

        setViewAllHref(searchInput.value.trim());
    });

/* End of Search Bar Functionality */

/*Password Eye toggle */
const passwords = document.querySelectorAll(".password");
const toggles = document.querySelectorAll(".togglePassword");

toggles.forEach((toggle, index) => {
    const eye = toggle.querySelector(".fa-eye");
    const eyeSlash = toggle.querySelector(".fa-eye-slash");
    const input = passwords[index];

    toggle.addEventListener("click", () => {
        if (input.type === "password") {
            input.type = "text";
            eye.style.display = "none";
            eyeSlash.style.display = "inline-block";
        } else {
            input.type = "password";
            eye.style.display = "inline-block";
            eyeSlash.style.display = "none";
        }
    });
});

/* End of Password Eye toggle */

/* Sidebar Auth Validation */
const loginFormElement = document.getElementById("login-form");
const registerFormElement = document.getElementById("register-form");
const loginWarning = document.getElementById("login-warning");
const registerWarning = document.getElementById("register-warning");
const forgotPasswordForm = document.getElementById("forgot-password-form");
const forgotPasswordFeedback = document.getElementById("forgot-password-feedback");
const changePasswordModal = document.getElementById("change-password-modal");
const changePasswordForm = changePasswordModal ? changePasswordModal.querySelector("form") : null;
const changePasswordFeedback = document.getElementById("change-password-feedback");

const passwordRule =
    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/;

const setWarning = (element, message) => {
    if (!element) {
        if (message && typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                text: message,
                confirmButtonColor: "#b31212",
            });
        }
        return;
    }
    element.textContent = message;
    element.classList.toggle("show", Boolean(message));
};

const setInvalid = (input, isInvalid) => {
    if (!input) return;
    const wrapper = input.closest(".input");
    if (wrapper) {
        wrapper.classList.toggle("is-invalid", isInvalid);
    }
};

const setSubmitting = (form, isSubmitting) => {
    if (!form) return;
    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) return;

    submitButton.dataset.originalText = submitButton.dataset.originalText || submitButton.textContent;
    submitButton.disabled = isSubmitting;
    submitButton.textContent = isSubmitting ? "Please wait..." : submitButton.dataset.originalText;
};

const submitJsonForm = async (url, formData) => {
    const response = await fetch(url, {
        method: "POST",
        body: formData,
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json",
        },
        credentials: "same-origin",
    });

    const result = await response.json().catch(() => ({
        success: false,
        message: "Something went wrong. Please try again.",
    }));

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Something went wrong. Please try again.");
    }

    return result;
};

const markCustomerAuthenticated = () => {
    if (userProfile) {
        userProfile.dataset.authenticated = "true";
        userProfile.removeAttribute("id");
    }

    if (userProfileLink) {
        const profileUrl = typeof window.appPath === "function" ? window.appPath("/user-profile.php") : "/user-profile.php";
        userProfileLink.setAttribute("href", profileUrl);
        userProfileLink.setAttribute("aria-label", "My profile");
    }
};

const submitAuthForm = async (form, warningElement, successTitle) => {
    const result = await submitJsonForm(form.action, new FormData(form));

    setWarning(warningElement, "");
    markCustomerAuthenticated();
    form.reset();
    closeSidebar();

    if (typeof Swal !== "undefined") {
        await Swal.fire({
            icon: "success",
            title: successTitle,
            text: result.message || "Welcome.",
            confirmButtonColor: "#56b356",
        });
    }
};

if (loginFormElement) {
    loginFormElement.addEventListener("submit", async (event) => {
        event.preventDefault();
        const emailInput = loginFormElement.querySelector('input[type="email"]');
        const passwordInput = loginFormElement.querySelector('input[type="password"]');
        const validEmail = emailInput ? emailInput.checkValidity() : false;
        const validPassword = passwordInput ? passwordInput.value.trim().length > 0 : false;

        if (!validEmail || !validPassword) {
            setWarning(loginWarning, "Incorrect email or password.");
            setInvalid(emailInput, !validEmail);
            setInvalid(passwordInput, !validPassword);
            return;
        }

        setWarning(loginWarning, "");
        setInvalid(emailInput, false);
        setInvalid(passwordInput, false);

        try {
            setSubmitting(loginFormElement, true);
            await submitAuthForm(loginFormElement, loginWarning, "Welcome");
        } catch (error) {
            setWarning(loginWarning, error.message || "Incorrect email or password.");
        } finally {
            setSubmitting(loginFormElement, false);
        }
    });

    loginFormElement.addEventListener("input", () => {
        setWarning(loginWarning, "");
        const emailInput = loginFormElement.querySelector('input[type="email"]');
        const passwordInput = loginFormElement.querySelector('input[type="password"]');
        setInvalid(emailInput, false);
        setInvalid(passwordInput, false);
    });
}

if (registerFormElement) {
    registerFormElement.addEventListener("submit", async (event) => {
        event.preventDefault();
        const emailInput = registerFormElement.querySelector('input[type="email"]');
        const passwordInput = registerFormElement.querySelector('input[type="password"]');
        const validEmail = emailInput ? emailInput.checkValidity() : false;
        const passwordValue = passwordInput ? passwordInput.value.trim() : "";
        const validPassword = passwordRule.test(passwordValue);

        if (!validEmail || !validPassword) {
            if (!validEmail && !validPassword) {
                setWarning(
                    registerWarning,
                    "Enter a valid email and a password with 8+ chars, upper, lower, number, and special character."
                );
            } else if (!validEmail) {
                setWarning(registerWarning, "Please enter a valid email address.");
            } else {
                setWarning(
                    registerWarning,
                    "Password must be 8+ chars with upper, lower, number, and special character."
                );
            }
            setInvalid(emailInput, !validEmail);
            setInvalid(passwordInput, !validPassword);
            return;
        }

        setWarning(registerWarning, "");
        setInvalid(emailInput, false);
        setInvalid(passwordInput, false);

        try {
            setSubmitting(registerFormElement, true);
            await submitAuthForm(registerFormElement, registerWarning, "Account Created");
        } catch (error) {
            setWarning(registerWarning, error.message || "Unable to create your account.");
        } finally {
            setSubmitting(registerFormElement, false);
        }
    });

    registerFormElement.addEventListener("input", () => {
        setWarning(registerWarning, "");
        const emailInput = registerFormElement.querySelector('input[type="email"]');
        const passwordInput = registerFormElement.querySelector('input[type="password"]');
        setInvalid(emailInput, false);
        setInvalid(passwordInput, false);
    });
}

if (forgotPasswordForm) {
    forgotPasswordForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        setWarning(forgotPasswordFeedback, "");

        const emailInput = forgotPasswordForm.querySelector('input[name="reset_email"]');
        const isValid = emailInput ? emailInput.checkValidity() : false;
        if (!isValid) {
            setWarning(forgotPasswordFeedback, "Please enter a valid email address.");
            setInvalid(emailInput, true);
            return;
        }

        try {
            setSubmitting(forgotPasswordForm, true);
            const result = await submitJsonForm(forgotPasswordForm.action, new FormData(forgotPasswordForm));
            setWarning(forgotPasswordFeedback, result.message || "If an account matches that email, a reset link has been sent.");
            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    icon: "success",
                    title: "Check your email",
                    text: result.message || "If an account matches that email, a reset link has been sent.",
                    confirmButtonColor: "#56b356",
                });
            }
            forgotPasswordForm.reset();
            const forgotModal = forgotPasswordForm.closest(".modal-overlay");
            if (forgotModal) closeModal(forgotModal);
        } catch (error) {
            setWarning(forgotPasswordFeedback, error.message || "Unable to send reset link right now.");
        } finally {
            setSubmitting(forgotPasswordForm, false);
        }
    });
}

if (changePasswordForm) {
    const changePasswordEndpoint = typeof window.appPath === "function"
        ? window.appPath("/profile/security.php")
        : "/profile/security.php";

    changePasswordForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        setWarning(changePasswordFeedback, "");

        const currentPasswordInput = changePasswordForm.querySelector('input[name="current_password"]');
        const newPasswordInput = changePasswordForm.querySelector('input[name="new_password"]');
        const confirmPasswordInput = changePasswordForm.querySelector('input[name="confirm_password"]');
        const newPasswordValue = newPasswordInput ? newPasswordInput.value.trim() : "";
        const confirmPasswordValue = confirmPasswordInput ? confirmPasswordInput.value.trim() : "";

        if (!currentPasswordInput?.value.trim()) {
            setWarning(changePasswordFeedback, "Current password is required.");
            return;
        }

        if (!passwordRule.test(newPasswordValue)) {
            setWarning(changePasswordFeedback, "Password must be 8+ chars with upper, lower, number, and special character.");
            return;
        }

        if (newPasswordValue !== confirmPasswordValue) {
            setWarning(changePasswordFeedback, "Password confirmation does not match.");
            return;
        }

        const formData = new FormData(changePasswordForm);
        formData.set("action", "change_password");

        try {
            setSubmitting(changePasswordForm, true);
            const result = await submitJsonForm(changePasswordEndpoint, formData);
            changePasswordForm.reset();
            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    icon: "success",
                    title: "Password Updated",
                    text: result.message || "Password updated successfully.",
                    confirmButtonColor: "#56b356",
                });
            }
            if (changePasswordModal) closeModal(changePasswordModal);
        } catch (error) {
            setWarning(changePasswordFeedback, error.message || "Unable to update password right now.");
        } finally {
            setSubmitting(changePasswordForm, false);
        }
    });
}

/* End Sidebar Auth Validation */

/* Number Format */

document.querySelectorAll('.price-format').forEach(price => {
    const number = parseInt(price.textContent.replace(/,/g, ''), 10);
    price.textContent = number.toLocaleString('en-US');
});

/* Modal Controls */
const modalTriggers = document.querySelectorAll("[data-modal-target]");
const modalOverlays = document.querySelectorAll(".modal-overlay");

const openModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
};

const closeModal = (modal) => {
    if (!modal) return;
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
};

modalTriggers.forEach((trigger) => {
    trigger.addEventListener("click", (event) => {
        event.preventDefault();
        const target = trigger.dataset.modalTarget;
        if (target) openModal(target);
    });
});

modalOverlays.forEach((overlay) => {
    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            closeModal(overlay);
        }
    });

    const closeBtn = overlay.querySelector(".modal-close");
    const cancelBtn = overlay.querySelector(".modal-cancel");

    if (closeBtn) {
        closeBtn.addEventListener("click", () => closeModal(overlay));
    }
    if (cancelBtn) {
        cancelBtn.addEventListener("click", () => closeModal(overlay));
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        const openModalEl = document.querySelector(".modal-overlay.open");
        if (openModalEl) closeModal(openModalEl);
    }
});

/* End Modal Controls */

