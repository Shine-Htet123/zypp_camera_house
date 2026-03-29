const fullscreenLoader = document.querySelector("[data-admin-loader]");
const transitionRoot = document.documentElement;
const transitionBody = document.body;
const progressElement = document.querySelector("[data-admin-progress]");
const progressBar = document.querySelector("[data-admin-progress-bar]");
const ADMIN_PAGE_TRANSITION_KEY = "admin-page-transition";
const ADMIN_PAGE_TRANSITION_MS = 180;
const ADMIN_PROGRESS_KEY = "admin-page-progress";
const prefersReducedMotion = window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches === true;
let progressValue = 0;
let progressDepth = 0;
let progressTrickleTimer = null;
let progressHideTimer = null;

const setProgressValue = (value) => {
    if (!progressBar) {
        return;
    }

    progressValue = Math.max(0, Math.min(1, value));
    progressBar.style.transform = `scaleX(${progressValue})`;
};

const resetProgressBar = () => {
    if (progressTrickleTimer) {
        clearInterval(progressTrickleTimer);
        progressTrickleTimer = null;
    }
    if (progressHideTimer) {
        clearTimeout(progressHideTimer);
        progressHideTimer = null;
    }

    progressDepth = 0;
    progressValue = 0;
    progressElement?.classList.remove("is-active");
    setProgressValue(0);
};

const rememberProgressAcrossRedirect = () => {
    try {
        window.sessionStorage.setItem(ADMIN_PROGRESS_KEY, "pending");
    } catch (_) {
        // Ignore storage errors.
    }
};

const clearRememberedProgress = () => {
    try {
        window.sessionStorage.removeItem(ADMIN_PROGRESS_KEY);
    } catch (_) {
        // Ignore storage errors.
    }
};

const trickleProgress = () => {
    if (progressValue < 0.2) {
        setProgressValue(progressValue + 0.1);
        return;
    }
    if (progressValue < 0.55) {
        setProgressValue(progressValue + 0.06);
        return;
    }
    if (progressValue < 0.8) {
        setProgressValue(progressValue + 0.025);
        return;
    }
    if (progressValue < 0.92) {
        setProgressValue(progressValue + 0.008);
    }
};

const ensureProgressBar = (initialValue = 0.08) => {
    if (!progressElement || prefersReducedMotion) {
        return;
    }

    if (progressHideTimer) {
        clearTimeout(progressHideTimer);
        progressHideTimer = null;
    }

    progressElement.classList.add("is-active");
    setProgressValue(Math.max(progressValue, initialValue));

    if (!progressTrickleTimer) {
        progressTrickleTimer = window.setInterval(trickleProgress, 160);
    }
};

const beginProgress = (options = {}) => {
    const { persist = false, initialValue = 0.08 } = options;
    progressDepth += 1;
    ensureProgressBar(initialValue);
    if (persist) {
        rememberProgressAcrossRedirect();
    }
};

const endProgress = (options = {}) => {
    const { persist = false } = options;
    progressDepth = Math.max(0, progressDepth - 1);

    if (progressDepth > 0 || !progressElement || prefersReducedMotion) {
        if (!persist && progressDepth === 0) {
            clearRememberedProgress();
        }
        return;
    }

    if (progressTrickleTimer) {
        clearInterval(progressTrickleTimer);
        progressTrickleTimer = null;
    }

    setProgressValue(1);
    if (!persist) {
        clearRememberedProgress();
    }

    progressHideTimer = window.setTimeout(() => {
        progressElement.classList.remove("is-active");
        setProgressValue(0);
    }, 220);
};

const resumeRememberedProgress = () => {
    if (!progressElement || prefersReducedMotion) {
        clearRememberedProgress();
        return;
    }

    let shouldResume = false;
    try {
        shouldResume = window.sessionStorage.getItem(ADMIN_PROGRESS_KEY) === "pending";
    } catch (_) {
        shouldResume = false;
    }

    if (!shouldResume) {
        return;
    }

    progressDepth = Math.max(progressDepth, 1);
    ensureProgressBar(0.38);

    const finishResumedProgress = () => {
        progressDepth = 1;
        endProgress();
    };

    if (document.readyState === "complete") {
        window.requestAnimationFrame(finishResumedProgress);
    } else {
        window.addEventListener("load", finishResumedProgress, { once: true });
    }
};

const syncTransitionClasses = () => {
    if (!transitionBody) {
        return;
    }

    transitionBody.classList.toggle("admin-transition-ready", transitionRoot.classList.contains("admin-transition-ready"));
    transitionBody.classList.toggle("admin-page-entering", transitionRoot.classList.contains("admin-page-entering"));
    transitionBody.classList.toggle("admin-page-transitioning", transitionRoot.classList.contains("admin-page-transitioning"));
};

const clearTransitionFlag = () => {
    try {
        window.sessionStorage.removeItem(ADMIN_PAGE_TRANSITION_KEY);
    } catch (_) {
        // Ignore storage errors.
    }
};

const markNextPageForEntryTransition = () => {
    try {
        window.sessionStorage.setItem(ADMIN_PAGE_TRANSITION_KEY, "enter");
    } catch (_) {
        // Ignore storage errors.
    }
};

const triggerEntryTransition = () => {
    if (!transitionBody || !transitionBody.classList.contains("admin-page") || prefersReducedMotion) {
        transitionRoot.classList.remove("admin-transition-ready", "admin-page-entering", "admin-page-transitioning");
        syncTransitionClasses();
        clearTransitionFlag();
        return;
    }

    const shouldEnter = transitionRoot.classList.contains("admin-page-entering");
    transitionRoot.classList.add("admin-transition-ready");
    syncTransitionClasses();

    if (!shouldEnter) {
        clearTransitionFlag();
        return;
    }

    const finishEntry = () => {
        transitionRoot.classList.remove("admin-page-entering", "admin-page-transitioning");
        syncTransitionClasses();
        clearTransitionFlag();
    };

    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(finishEntry);
    });
    window.setTimeout(finishEntry, ADMIN_PAGE_TRANSITION_MS + 80);
};

const isSamePageNavigation = (targetUrl) => {
    const currentUrl = new URL(window.location.href);
    return targetUrl.href === currentUrl.href
        || (
            targetUrl.pathname === currentUrl.pathname
            && targetUrl.search === currentUrl.search
            && targetUrl.hash !== ""
        );
};

const canAnimateNavigationTo = (targetUrl) => {
    return targetUrl.origin === window.location.origin
        && targetUrl.pathname.includes("/admin/")
        && !isSamePageNavigation(targetUrl);
};

const navigateWithTransition = (targetUrl, navigate) => {
    if (!transitionBody || !transitionBody.classList.contains("admin-page") || prefersReducedMotion) {
        navigate();
        return;
    }

    markNextPageForEntryTransition();
    transitionRoot.classList.add("admin-transition-ready");
    transitionRoot.classList.remove("admin-page-entering");
    transitionRoot.classList.add("admin-page-transitioning");
    syncTransitionClasses();

    window.setTimeout(navigate, ADMIN_PAGE_TRANSITION_MS);
};

const detachFloatingFooters = () => {
    const floatingFooterSelectors = [".form-footer", ".apply-footer", ".stack-footer"];
    const footers = document.querySelectorAll(floatingFooterSelectors.join(", "));

    footers.forEach((footer, index) => {
        if (!(footer instanceof HTMLElement)) {
            return;
        }

        if (footer.parentElement === document.body) {
            return;
        }

        const parentForm = footer.closest("form");
        if (parentForm instanceof HTMLFormElement) {
            if (!parentForm.id) {
                parentForm.id = `adminFloatingFooterForm${index + 1}`;
            }

            footer.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
                if (!control.getAttribute("form")) {
                    control.setAttribute("form", parentForm.id);
                }
            });
        }

        document.body.appendChild(footer);
    });
};

const detachModalOverlays = () => {
    const overlays = document.querySelectorAll(".modal-overlay");

    overlays.forEach((overlay) => {
        if (!(overlay instanceof HTMLElement)) {
            return;
        }

        if (overlay.parentElement === document.body) {
            return;
        }

        document.body.appendChild(overlay);
    });
};

detachFloatingFooters();
detachModalOverlays();
triggerEntryTransition();
resumeRememberedProgress();

window.addEventListener("pageshow", () => {
    transitionRoot.classList.remove("admin-page-transitioning");
    syncTransitionClasses();
    triggerEntryTransition();
    if (window.performance?.getEntriesByType?.("navigation")?.[0]?.type === "back_forward") {
        clearRememberedProgress();
        resetProgressBar();
    }
});

const FULLSCREEN_MIN_LOADING_MS = 850;
const fullscreenLoaderEnabled = fullscreenLoader?.dataset.active === "true";
let fullscreenLoaderVisibleSince = fullscreenLoaderEnabled ? (window.performance?.now ? window.performance.now() : Date.now()) : 0;
let fullscreenHideTimer = null;
let fullscreenInitialLoadPending = fullscreenLoaderEnabled;

const now = () => (window.performance?.now ? window.performance.now() : Date.now());

const setFullscreenLoaderVisibility = (visible) => {
    if (!fullscreenLoader) {
        return;
    }

    if (visible && fullscreenHideTimer) {
        clearTimeout(fullscreenHideTimer);
        fullscreenHideTimer = null;
    }

    fullscreenLoader.classList.toggle("is-hidden", !visible);
    fullscreenLoader.setAttribute("aria-hidden", visible ? "false" : "true");
    document.body.classList.toggle("admin-loading-active", visible);
};

const finishInitialFullscreenLoad = () => {
    if (!fullscreenLoaderEnabled || !fullscreenInitialLoadPending) {
        setFullscreenLoaderVisibility(false);
        return;
    }

    fullscreenInitialLoadPending = false;
    const remaining = Math.max(0, FULLSCREEN_MIN_LOADING_MS - (now() - fullscreenLoaderVisibleSince));

    if (fullscreenHideTimer) {
        clearTimeout(fullscreenHideTimer);
    }

    fullscreenHideTimer = window.setTimeout(() => {
        fullscreenHideTimer = null;
        setFullscreenLoaderVisibility(false);
    }, remaining);
};

window.AdminLoading = {
    show() {
        beginProgress({ initialValue: 0.12 });
    },
    hide() {
        endProgress();
    },
    reset() {
        clearRememberedProgress();
        resetProgressBar();
    },
    async while(task) {
        beginProgress({ initialValue: 0.12 });
        try {
            return await task();
        } finally {
            endProgress();
        }
    },
};

const nativeFetch = window.fetch?.bind(window);
if (nativeFetch) {
    window.fetch = async (...args) => {
        let shouldTrack = false;

        try {
            const [input, init] = args;
            const requestUrl = typeof input === "string" || input instanceof URL
                ? String(input)
                : String(input?.url || "");
            const method = String(init?.method || input?.method || "GET").toUpperCase();
            const targetUrl = new URL(requestUrl, window.location.href);
            shouldTrack = transitionBody?.classList.contains("admin-page")
                && targetUrl.origin === window.location.origin
                && targetUrl.pathname.includes("/admin/")
                && ["POST", "PUT", "PATCH", "DELETE"].includes(method);
        } catch (_) {
            shouldTrack = false;
        }

        if (shouldTrack) {
            beginProgress({ initialValue: 0.12 });
        }

        try {
            return await nativeFetch(...args);
        } finally {
            if (shouldTrack) {
                endProgress();
            }
        }
    };
}

const paymentProofNotificationButton = document.querySelector("[data-admin-proof-notifications]");
const paymentProofNotificationBadge = paymentProofNotificationButton?.querySelector(".badge") || null;
const PAYMENT_PROOF_NOTIFICATIONS_POLL_MS = 15000;
let paymentProofNotificationMarker = paymentProofNotificationButton?.dataset.initialMarker || "";
let paymentProofNotificationTimer = null;
let paymentProofNotificationRequest = null;
let paymentProofToastTimer = null;

const updatePaymentProofBadge = (count) => {
    if (!paymentProofNotificationBadge) {
        return;
    }

    const normalizedCount = Math.max(0, Number(count) || 0);
    paymentProofNotificationBadge.textContent = String(normalizedCount);
    paymentProofNotificationBadge.hidden = normalizedCount <= 0;
};

const pulsePaymentProofBell = () => {
    if (!paymentProofNotificationButton) {
        return;
    }

    paymentProofNotificationButton.classList.remove("is-live");
    window.requestAnimationFrame(() => {
        paymentProofNotificationButton.classList.add("is-live");
        window.setTimeout(() => {
            paymentProofNotificationButton.classList.remove("is-live");
        }, 1600);
    });
};

const ensurePaymentProofToastContainer = () => {
    let container = document.querySelector("[data-admin-proof-toast-container]");
    if (container) {
        return container;
    }

    container = document.createElement("div");
    container.className = "admin-proof-toast-stack";
    container.setAttribute("data-admin-proof-toast-container", "true");
    document.body.appendChild(container);
    return container;
};

const dismissPaymentProofToast = () => {
    const existingToast = document.querySelector("[data-admin-proof-toast]");
    if (!existingToast) {
        return;
    }

    existingToast.classList.remove("is-visible");
    window.setTimeout(() => {
        existingToast.remove();
    }, 220);
};

const showPaymentProofToast = (count) => {
    const destination = paymentProofNotificationButton?.getAttribute("href") || "/admin/payment-proof-uploads.php";
    const container = ensurePaymentProofToastContainer();
    dismissPaymentProofToast();

    const toast = document.createElement("aside");
    toast.className = "admin-proof-toast";
    toast.setAttribute("data-admin-proof-toast", "true");
    toast.innerHTML = `
        <div class="admin-proof-toast__copy">
            <strong>${count === 1 ? "New payment proof uploaded" : `${count} new payment proofs uploaded`}</strong>
            <span>Open the review queue to approve, reject, or request a re-upload.</span>
        </div>
        <div class="admin-proof-toast__actions">
            <a class="admin-proof-toast__link" href="${destination}">Review</a>
            <button type="button" class="admin-proof-toast__close" aria-label="Dismiss notification">&times;</button>
        </div>
    `;

    container.appendChild(toast);
    window.requestAnimationFrame(() => {
        toast.classList.add("is-visible");
    });

    toast.querySelector(".admin-proof-toast__close")?.addEventListener("click", dismissPaymentProofToast);

    if (paymentProofToastTimer) {
        clearTimeout(paymentProofToastTimer);
    }
    paymentProofToastTimer = window.setTimeout(dismissPaymentProofToast, 6000);
};

const dispatchPaymentProofNotificationEvent = (payload) => {
    window.dispatchEvent(new CustomEvent("admin:payment-proof-notifications", { detail: payload }));
};

const pollPaymentProofNotifications = async ({ immediateToast = true } = {}) => {
    if (!paymentProofNotificationButton || !nativeFetch || paymentProofNotificationRequest) {
        return;
    }

    const apiUrl = paymentProofNotificationButton.dataset.apiUrl || "";
    if (apiUrl === "") {
        return;
    }

    const requestUrl = new URL(apiUrl, window.location.href);
    requestUrl.searchParams.set("action", "notifications");
    if (paymentProofNotificationMarker) {
        requestUrl.searchParams.set("since", paymentProofNotificationMarker);
    }

    paymentProofNotificationRequest = nativeFetch(requestUrl.href, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
    });

    try {
        const response = await paymentProofNotificationRequest;
        const payload = await response.json().catch(() => null);
        if (!response.ok || !payload?.success) {
            throw new Error(payload?.message || "Failed to load payment proof notifications.");
        }

        const data = payload.data || {};
        updatePaymentProofBadge(data.pending_count ?? 0);

        if (typeof data.latest_marker === "string" && data.latest_marker !== "") {
            paymentProofNotificationMarker = data.latest_marker;
        }

        const newCount = Math.max(0, Number(data.new_count) || 0);
        if (newCount > 0) {
            pulsePaymentProofBell();
            if (immediateToast) {
                showPaymentProofToast(newCount);
            }
            dispatchPaymentProofNotificationEvent(data);
        }
    } catch (_) {
        // Fail silently and try again on the next poll.
    } finally {
        paymentProofNotificationRequest = null;
    }
};

const schedulePaymentProofPolling = () => {
    if (!paymentProofNotificationButton) {
        return;
    }

    if (paymentProofNotificationTimer) {
        clearInterval(paymentProofNotificationTimer);
    }

    updatePaymentProofBadge(paymentProofNotificationButton.dataset.initialCount || 0);
    paymentProofNotificationTimer = window.setInterval(() => {
        if (document.visibilityState === "hidden") {
            return;
        }
        pollPaymentProofNotifications();
    }, PAYMENT_PROOF_NOTIFICATIONS_POLL_MS);
};

if (paymentProofNotificationButton) {
    schedulePaymentProofPolling();

    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "visible") {
            pollPaymentProofNotifications({ immediateToast: true });
        }
    });

    window.AdminPaymentProofNotifications = {
        refresh() {
            return pollPaymentProofNotifications({ immediateToast: false });
        },
    };
}

if (fullscreenLoader) {
    if (fullscreenLoaderEnabled) {
        setFullscreenLoaderVisibility(true);

        if (document.readyState === "complete") {
            window.requestAnimationFrame(finishInitialFullscreenLoad);
        } else {
            window.addEventListener("load", () => {
                window.setTimeout(finishInitialFullscreenLoad, 100);
            }, { once: true });
        }
    } else {
        setFullscreenLoaderVisibility(false);
    }
}

const toggles = document.querySelectorAll(".nav-group-toggle");

toggles.forEach((toggle) => {
    toggle.addEventListener("click", () => {
        const targetId = toggle.dataset.target;
        if (!targetId) {
            return;
        }

        const submenu = document.getElementById(targetId);
        if (!submenu) {
            return;
        }

        const isOpen = submenu.classList.toggle("open");
        toggle.classList.toggle("open", isOpen);
        toggle.setAttribute("aria-expanded", String(isOpen));
    });
});

const adminBody = document.querySelector(".admin-body");
const adminToggle = document.querySelector(".admin-toggle");
const adminOverlay = document.querySelector(".admin-overlay");
const navLinks = document.querySelectorAll(".sidebar-nav .nav-link");

if (adminBody && adminToggle) {
    const closeSidebar = () => {
        adminBody.classList.remove("sidebar-open");
        adminToggle.setAttribute("aria-expanded", "false");
    };

    adminToggle.addEventListener("click", () => {
        const isOpen = adminBody.classList.toggle("sidebar-open");
        adminToggle.setAttribute("aria-expanded", String(isOpen));
    });

    if (adminOverlay) {
        adminOverlay.addEventListener("click", closeSidebar);
    }

    navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            if (window.innerWidth <= 1024) {
                closeSidebar();
            }
        });
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 1024) {
            closeSidebar();
        }
    });
}

const normalizePath = (value) => String(value || "").replace(/\/+$/, "");

document.addEventListener("click", (event) => {
    const link = event.target.closest("a[href]");
    if (!link || event.defaultPrevented) {
        return;
    }

    if (link.target === "_blank" || link.hasAttribute("download")) {
        return;
    }

    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
        return;
    }

    const href = link.getAttribute("href") || "";
    if (href === "" || href.startsWith("#") || href.startsWith("javascript:")) {
        return;
    }

    try {
        const targetUrl = new URL(link.href, window.location.href);
        if (!canAnimateNavigationTo(targetUrl)) {
            return;
        }

        event.preventDefault();
        navigateWithTransition(targetUrl, () => {
            window.location.assign(targetUrl.href);
        });
    } catch (_) {
        // Ignore malformed URLs.
    }
});

document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
        return;
    }

    if (form.dataset.skipLoader !== undefined || form.dataset.skipTransition === "true") {
        return;
    }

    if (form.target && form.target !== "_self") {
        return;
    }

    if (form.dataset.transitionSubmitting === "true") {
        return;
    }

    try {
        const action = form.getAttribute("action") || window.location.href;
        const targetUrl = new URL(action, window.location.href);
        if (!canAnimateNavigationTo(targetUrl)) {
            return;
        }

        event.preventDefault();
        form.dataset.transitionSubmitting = "true";
        beginProgress({ persist: true, initialValue: 0.14 });
        navigateWithTransition(targetUrl, () => {
            form.submit();
        });
    } catch (_) {
        // Ignore malformed URLs.
    }
});

if (navLinks.length > 0) {
    const currentPath = normalizePath(window.location.pathname);

    navLinks.forEach((link) => {
        link.classList.remove("active");

        try {
            const targetUrl = new URL(link.href, window.location.href);
            if (normalizePath(targetUrl.pathname) !== currentPath) {
                return;
            }

            link.classList.add("active");

            const submenu = link.closest(".nav-submenu");
            if (!submenu) {
                return;
            }

            submenu.classList.add("open");
            const toggle = document.querySelector(`.nav-group-toggle[data-target="${submenu.id}"]`);
            toggle?.classList.add("open");
            toggle?.setAttribute("aria-expanded", "true");
        } catch (_) {
            // Ignore malformed URLs.
        }
    });
}

const logoutBtn = document.querySelector(".logout-btn");
if (logoutBtn) {
    logoutBtn.addEventListener("click", (event) => {
        event.preventDefault();
        const targetUrl = logoutBtn.getAttribute("href") || "/";

        const continueLogout = () => {
            try {
                const destination = new URL(targetUrl, window.location.href);
                if (canAnimateNavigationTo(destination)) {
                    navigateWithTransition(destination, () => {
                        window.location.assign(destination.href);
                    });
                    return;
                }
            } catch (_) {
                // Fall through to normal navigation.
            }

            window.location.assign(targetUrl);
        };

        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Leave admin panel?",
                text: "You will be redirected to the customer home page.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, continue",
                cancelButtonText: "Cancel",
            }).then((result) => {
                if (result.isConfirmed) {
                    continueLogout();
                }
            });
        } else {
            continueLogout();
        }
    });
}
