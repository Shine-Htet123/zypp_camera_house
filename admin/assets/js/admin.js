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

const adminNotificationRoot = document.querySelector("[data-admin-notifications]");
const adminNotificationToggle = adminNotificationRoot?.querySelector("[data-admin-notification-toggle]") || null;
const adminNotificationDropdown = adminNotificationRoot?.querySelector("[data-admin-notification-dropdown]") || null;
const adminNotificationList = adminNotificationRoot?.querySelector("[data-admin-notification-list]") || null;
const adminNotificationBadge = adminNotificationToggle?.querySelector(".badge") || null;
const adminNotificationInitialToastsNode = document.querySelector("[data-admin-notification-initial-toasts]");
const ADMIN_NOTIFICATIONS_POLL_MS = 12000;
const ADMIN_NOTIFICATION_SEEN_TOASTS_KEY = "admin-notification-seen-toasts";
let adminNotificationMarker = adminNotificationRoot?.dataset.initialMarker || "";
let adminNotificationTimer = null;
let adminNotificationRequest = null;
let adminNotificationAudioContext = null;
let adminNotificationSoundPending = false;
const shownAdminNotificationToastIds = (() => {
    try {
        const raw = window.sessionStorage.getItem(ADMIN_NOTIFICATION_SEEN_TOASTS_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        if (!Array.isArray(parsed)) {
            return new Set();
        }

        return new Set(
            parsed
                .map((value) => Number(value || 0))
                .filter((value) => value > 0)
        );
    } catch (_) {
        return new Set();
    }
})();

const escapeHtml = (value) => String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");

const persistShownAdminNotificationToastIds = () => {
    try {
        window.sessionStorage.setItem(
            ADMIN_NOTIFICATION_SEEN_TOASTS_KEY,
            JSON.stringify(Array.from(shownAdminNotificationToastIds).slice(-200))
        );
    } catch (_) {
        // Ignore storage errors.
    }
};

const updateAdminNotificationBadge = (count) => {
    if (!adminNotificationBadge) {
        return;
    }

    const normalizedCount = Math.max(0, Number(count) || 0);
    adminNotificationBadge.textContent = String(Math.min(normalizedCount, 99));
    adminNotificationBadge.hidden = normalizedCount <= 0;
};

const getAdminNotificationBadgeCount = () => {
    if (!adminNotificationBadge || adminNotificationBadge.hidden) {
        return 0;
    }

    return Math.max(0, Number(adminNotificationBadge.textContent || "0") || 0);
};

const adjustAdminNotificationBadge = (delta) => {
    updateAdminNotificationBadge(getAdminNotificationBadgeCount() + delta);
};

const pulseAdminNotificationBell = () => {
    if (!adminNotificationToggle) {
        return;
    }

    adminNotificationToggle.classList.remove("is-live");
    window.requestAnimationFrame(() => {
        adminNotificationToggle.classList.add("is-live");
        window.setTimeout(() => {
            adminNotificationToggle.classList.remove("is-live");
        }, 1600);
    });
};

const createAdminNotificationMarkup = (notification) => {
    const notificationId = Number(notification?.notification_id || 0);
    const title = escapeHtml(notification?.title || "Notification");
    const body = escapeHtml(notification?.body || "");
    const targetUrl = escapeHtml(notification?.target_url || "/admin/index.php");
    const time = escapeHtml(notification?.created_at_display || "");
    const unreadClass = notification?.is_unread ? " is-unread" : "";

    return `
        <article class="admin-notification-item${unreadClass}" data-notification-id="${notificationId}">
            <a class="admin-notification-item__link" href="${targetUrl}">
                <span class="admin-notification-item__dot" aria-hidden="true"></span>
                <strong>${title}</strong>
                <span>${body}</span>
                <time>${time}</time>
            </a>
            <button type="button" class="admin-notification-item__delete" aria-label="Delete notification" data-admin-notification-delete>&times;</button>
        </article>
    `;
};

const renderAdminNotificationList = (notifications) => {
    if (!adminNotificationList) {
        return;
    }

    if (!Array.isArray(notifications) || notifications.length === 0) {
        adminNotificationList.innerHTML = `<div class="admin-notification-empty" data-admin-notification-empty>No notifications yet.</div>`;
        return;
    }

    adminNotificationList.innerHTML = notifications.map(createAdminNotificationMarkup).join("");
};

const setAdminNotificationDropdownOpen = (open) => {
    if (!adminNotificationRoot || !adminNotificationToggle || !adminNotificationDropdown) {
        return;
    }

    adminNotificationRoot.classList.toggle("is-open", open);
    adminNotificationToggle.setAttribute("aria-expanded", open ? "true" : "false");
    adminNotificationDropdown.hidden = !open;
};

const markNotificationItemReadInUi = (notificationId) => {
    const item = adminNotificationList?.querySelector(`[data-notification-id="${notificationId}"]`);
    if (!item || !item.classList.contains("is-unread")) {
        return;
    }

    item.classList.remove("is-unread");
    adjustAdminNotificationBadge(-1);
};

const ensureAdminNotificationToastContainer = () => {
    let container = document.querySelector("[data-admin-notification-toast-container]");
    if (container) {
        return container;
    }

    container = document.createElement("div");
    container.className = "admin-notification-toast-stack";
    container.setAttribute("data-admin-notification-toast-container", "true");
    document.body.appendChild(container);
    return container;
};

const dismissAdminNotificationToast = (toast) => {
    if (!(toast instanceof HTMLElement)) {
        return;
    }

    toast.classList.remove("is-visible");
    window.setTimeout(() => {
        toast.remove();
    }, 220);
};

const ensureAdminNotificationAudio = async () => {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) {
        return null;
    }

    if (!adminNotificationAudioContext) {
        adminNotificationAudioContext = new AudioContextClass();
    }

    if (adminNotificationAudioContext.state === "suspended") {
        try {
            await adminNotificationAudioContext.resume();
        } catch (_) {
            return null;
        }
    }

    return adminNotificationAudioContext;
};

const playAdminNotificationSound = async () => {
    const context = await ensureAdminNotificationAudio();
    if (!context) {
        adminNotificationSoundPending = true;
        return false;
    }

    const nowAt = context.currentTime;
    [0, 0.16].forEach((offset, index) => {
        const oscillator = context.createOscillator();
        const gainNode = context.createGain();
        oscillator.type = "triangle";
        oscillator.frequency.value = index === 0 ? 920 : 1280;
        gainNode.gain.setValueAtTime(0.0001, nowAt + offset);
        gainNode.gain.exponentialRampToValueAtTime(0.13, nowAt + offset + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.0001, nowAt + offset + 0.26);
        oscillator.connect(gainNode);
        gainNode.connect(context.destination);
        oscillator.start(nowAt + offset);
        oscillator.stop(nowAt + offset + 0.28);
    });

    adminNotificationSoundPending = false;
    return true;
};

const showAdminNotificationToast = (notification) => {
    const notificationId = Number(notification?.notification_id || 0);
    if (notificationId > 0 && shownAdminNotificationToastIds.has(notificationId)) {
        return;
    }
    if (notificationId > 0) {
        shownAdminNotificationToastIds.add(notificationId);
        persistShownAdminNotificationToastIds();
    }

    const container = ensureAdminNotificationToastContainer();
    const toast = document.createElement("aside");
    toast.className = "admin-notification-toast";
    toast.setAttribute("data-notification-id", String(notificationId));
    toast.innerHTML = `
        <a class="admin-notification-toast__link" href="${escapeHtml(notification?.target_url || "/admin/index.php")}">
            <strong>${escapeHtml(notification?.title || "Notification")}</strong>
            <span>${escapeHtml(notification?.body || "")}</span>
        </a>
        <button type="button" class="admin-notification-toast__close" aria-label="Dismiss notification">&times;</button>
    `;

    container.appendChild(toast);
    const visibleToasts = Array.from(container.querySelectorAll(".admin-notification-toast"));
    visibleToasts.slice(0, Math.max(0, visibleToasts.length - 5)).forEach((oldToast) => {
        dismissAdminNotificationToast(oldToast);
    });

    window.requestAnimationFrame(() => {
        toast.classList.add("is-visible");
    });

    toast.querySelector(".admin-notification-toast__close")?.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        dismissAdminNotificationToast(toast);
    });
    toast.querySelector(".admin-notification-toast__link")?.addEventListener("click", () => {
        if (notificationId > 0) {
            markAdminNotificationRead(notificationId, { useBeacon: true }).catch(() => {});
        }
    });

    window.setTimeout(() => {
        dismissAdminNotificationToast(toast);
    }, 6500);

    playAdminNotificationSound().catch(() => {
        adminNotificationSoundPending = true;
    });
};

const sendAdminNotificationBeacon = (action, notificationId) => {
    if (!adminNotificationRoot || notificationId <= 0 || !navigator.sendBeacon) {
        return false;
    }

    const apiUrl = adminNotificationRoot.dataset.apiUrl || "";
    if (apiUrl === "") {
        return false;
    }

    try {
        const payload = new URLSearchParams({
            action,
            notification_id: String(notificationId),
        });
        return navigator.sendBeacon(apiUrl, payload);
    } catch (_) {
        return false;
    }
};

const markAdminNotificationRead = async (notificationId, options = {}) => {
    const { useBeacon = false } = options;
    if (!adminNotificationRoot || !nativeFetch || notificationId <= 0) {
        return false;
    }

    if (useBeacon && sendAdminNotificationBeacon("mark-read", notificationId)) {
        markNotificationItemReadInUi(notificationId);
        return true;
    }

    const apiUrl = adminNotificationRoot.dataset.apiUrl || "";
    if (apiUrl === "") {
        return false;
    }

    const response = await nativeFetch(apiUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            Accept: "application/json",
        },
        credentials: "same-origin",
        keepalive: useBeacon,
        body: new URLSearchParams({
            action: "mark-read",
            notification_id: String(notificationId),
        }).toString(),
    });
    const payload = await response.json().catch(() => null);
    if (!response.ok || !payload?.success) {
        throw new Error(payload?.message || "Failed to mark notification as read.");
    }

    markNotificationItemReadInUi(notificationId);
    return true;
};

const pollAdminNotifications = async ({ immediateToast = true } = {}) => {
    if (!adminNotificationRoot || !nativeFetch || adminNotificationRequest) {
        return;
    }

    const apiUrl = adminNotificationRoot.dataset.apiUrl || "";
    if (apiUrl === "") {
        return;
    }

    const requestUrl = new URL(apiUrl, window.location.href);
    requestUrl.searchParams.set("action", "snapshot");
    if (adminNotificationMarker) {
        requestUrl.searchParams.set("since", adminNotificationMarker);
    }

    adminNotificationRequest = nativeFetch(requestUrl.href, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
    });

    try {
        const response = await adminNotificationRequest;
        const payload = await response.json().catch(() => null);
        if (!response.ok || !payload?.success) {
            throw new Error(payload?.message || "Failed to load notifications.");
        }

        const data = payload.data || {};
        updateAdminNotificationBadge(data.unread_count ?? 0);
        renderAdminNotificationList(data.notifications || []);

        if (typeof data.latest_marker === "string" && data.latest_marker !== "") {
            adminNotificationMarker = data.latest_marker;
        }

        const newNotifications = Array.isArray(data.new_notifications)
            ? data.new_notifications.filter((notification) => {
                const notificationId = Number(notification?.notification_id || 0);
                return notification?.is_unread && (notificationId <= 0 || !shownAdminNotificationToastIds.has(notificationId));
            })
            : [];
        if (newNotifications.length > 0) {
            pulseAdminNotificationBell();
            if (immediateToast) {
                newNotifications.slice(0, 5).reverse().forEach((notification) => {
                    showAdminNotificationToast(notification);
                });
            }
        }
    } catch (_) {
        // Fail silently and try again on the next poll.
    } finally {
        adminNotificationRequest = null;
    }
};

const deleteAdminNotification = async (notificationId) => {
    if (!adminNotificationRoot || !nativeFetch || notificationId <= 0) {
        return;
    }

    const apiUrl = adminNotificationRoot.dataset.apiUrl || "";
    if (apiUrl === "") {
        return;
    }

    const response = await nativeFetch(apiUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            Accept: "application/json",
        },
        credentials: "same-origin",
        body: new URLSearchParams({
            action: "delete",
            notification_id: String(notificationId),
        }).toString(),
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok || !payload?.success) {
        throw new Error(payload?.message || "Failed to delete notification.");
    }

    await pollAdminNotifications({ immediateToast: false });
};

const scheduleAdminNotificationPolling = () => {
    if (!adminNotificationRoot) {
        return;
    }

    if (adminNotificationTimer) {
        clearInterval(adminNotificationTimer);
    }

    updateAdminNotificationBadge(adminNotificationRoot.dataset.initialCount || 0);
    adminNotificationTimer = window.setInterval(() => {
        if (document.visibilityState === "hidden") {
            return;
        }
        pollAdminNotifications();
    }, ADMIN_NOTIFICATIONS_POLL_MS);
};

if (adminNotificationRoot && adminNotificationToggle && adminNotificationDropdown) {
    scheduleAdminNotificationPolling();

    adminNotificationToggle.addEventListener("click", (event) => {
        event.preventDefault();
        const shouldOpen = adminNotificationDropdown.hidden;
        setAdminNotificationDropdownOpen(shouldOpen);
    });

    adminNotificationList?.addEventListener("click", (event) => {
        const deleteButton = event.target.closest("[data-admin-notification-delete]");
        if (!deleteButton) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        const item = deleteButton.closest("[data-notification-id]");
        const notificationId = Number(item?.getAttribute("data-notification-id") || 0);
        if (notificationId <= 0) {
            return;
        }

        deleteAdminNotification(notificationId).catch(() => {});
    });

    adminNotificationList?.addEventListener("click", (event) => {
        const notificationLink = event.target.closest(".admin-notification-item__link");
        if (!notificationLink) {
            return;
        }

        const item = notificationLink.closest("[data-notification-id]");
        const notificationId = Number(item?.getAttribute("data-notification-id") || 0);
        if (notificationId <= 0) {
            return;
        }

        markAdminNotificationRead(notificationId, { useBeacon: true }).catch(() => {});
    });

    document.addEventListener("click", (event) => {
        if (!adminNotificationRoot.contains(event.target)) {
            setAdminNotificationDropdownOpen(false);
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            setAdminNotificationDropdownOpen(false);
        }
    });

    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "visible") {
            pollAdminNotifications({ immediateToast: true });
        }
    });

    const unlockNotificationAudio = () => {
        ensureAdminNotificationAudio()
            .then(() => {
                if (adminNotificationSoundPending && document.querySelector(".admin-notification-toast")) {
                    return playAdminNotificationSound();
                }
                return null;
            })
            .catch(() => {});
        document.removeEventListener("pointerdown", unlockNotificationAudio);
        document.removeEventListener("keydown", unlockNotificationAudio);
    };

    document.addEventListener("pointerdown", unlockNotificationAudio, { once: true });
    document.addEventListener("keydown", unlockNotificationAudio, { once: true });

    try {
        const initialToastPayload = JSON.parse(adminNotificationInitialToastsNode?.textContent || "[]");
        if (Array.isArray(initialToastPayload) && initialToastPayload.length > 0) {
            initialToastPayload
                .filter((notification) => {
                    const notificationId = Number(notification?.notification_id || 0);
                    return notificationId <= 0 || !shownAdminNotificationToastIds.has(notificationId);
                })
                .slice(0, 5)
                .reverse()
                .forEach((notification) => {
                showAdminNotificationToast(notification);
            });
        }
    } catch (_) {
        // Ignore malformed initial notification payloads.
    }

    window.AdminNotifications = {
        refresh() {
            return pollAdminNotifications({ immediateToast: false });
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
