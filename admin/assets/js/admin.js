const adminLoader = document.querySelector("[data-admin-loader]");
const adminLoaderVideo = adminLoader?.querySelector(".admin-loading-video");
const adminLoaderText = adminLoader?.querySelector("[data-admin-loader-text]");
let adminLoaderRequests = 0;
let adminInitialLoadPending = Boolean(adminLoader);
let adminInitialPageLoaded = document.readyState === "complete";
let adminInitialAnimationFinished = !adminLoaderVideo;
let adminInitialHideTriggered = false;

const setAdminLoaderVisibility = (visible, message = "") => {
    if (!adminLoader) {
        return;
    }

    adminLoader.classList.toggle("is-hidden", !visible);
    adminLoader.setAttribute("aria-hidden", visible ? "false" : "true");
    document.body.classList.toggle("admin-loading-active", visible);
    if (visible && adminLoaderText && message) {
        adminLoaderText.textContent = message;
    }
};

const syncAdminLoader = () => {
    const shouldShow = adminInitialLoadPending || adminLoaderRequests > 0;
    setAdminLoaderVisibility(shouldShow);
};

const showAdminLoader = (message = "Loading admin dashboard...") => {
    if (!adminLoader) {
        return;
    }

    adminLoaderRequests += 1;
    setAdminLoaderVisibility(true, message);
};

const hideAdminLoader = () => {
    if (!adminLoader) {
        return;
    }

    adminLoaderRequests = Math.max(0, adminLoaderRequests - 1);
    syncAdminLoader();
};

window.AdminLoading = {
    show: showAdminLoader,
    hide: hideAdminLoader,
    async while(task, message = "Loading admin dashboard...") {
        showAdminLoader(message);
        try {
            return await task();
        } finally {
            hideAdminLoader();
        }
    },
};

const finishInitialAdminLoad = () => {
    if (
        adminInitialHideTriggered ||
        !adminInitialPageLoaded ||
        !adminInitialAnimationFinished
    ) {
        return;
    }

    adminInitialHideTriggered = true;
    adminInitialLoadPending = false;
    syncAdminLoader();
};

if (adminLoader) {
    setAdminLoaderVisibility(true, "Loading admin dashboard...");

    if (adminLoaderVideo) {
        adminLoaderVideo.currentTime = 0;
        const playPromise = adminLoaderVideo.play();
        if (playPromise && typeof playPromise.catch === "function") {
            playPromise.catch(() => {
                adminLoader.classList.add("is-fallback");
            });
        }

        adminLoaderVideo.addEventListener("ended", () => {
            adminInitialAnimationFinished = true;
            finishInitialAdminLoad();
        }, { once: true });
    }

    if (document.readyState === "complete") {
        finishInitialAdminLoad();
    } else {
        window.addEventListener("load", finishInitialAdminLoad, { once: true });
    }

    adminLoaderVideo?.addEventListener("error", () => {
        adminLoader.classList.add("is-fallback");
        adminInitialAnimationFinished = true;
        finishInitialAdminLoad();
    }, { once: true });

    window.addEventListener("load", () => {
        adminInitialPageLoaded = true;
        finishInitialAdminLoad();
    }, { once: true });
}

document.addEventListener("submit", (event) => {
    if (event.defaultPrevented) {
        return;
    }

    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.matches(".admin-search-form") || form.hasAttribute("data-skip-loader")) {
        return;
    }

    showAdminLoader("Saving changes...");
});

document.addEventListener("click", (event) => {
    const link = event.target.closest("a[href]");
    if (!link || event.defaultPrevented) {
        return;
    }

    const href = link.getAttribute("href") || "";
    if (
        href === "" ||
        href.startsWith("#") ||
        href.startsWith("javascript:") ||
        link.target === "_blank" ||
        link.hasAttribute("download")
    ) {
        return;
    }

    try {
        const targetUrl = new URL(link.href, window.location.href);
        if (targetUrl.origin !== window.location.origin || targetUrl.href === window.location.href) {
            return;
        }

        showAdminLoader("Opening page...");
    } catch (_) {
        // ignore malformed urls
    }
});

const toggles = document.querySelectorAll(".nav-group-toggle");

toggles.forEach((toggle) => {
    toggle.addEventListener("click", () => {
        const targetId = toggle.dataset.target;
        if (!targetId) return;
        const submenu = document.getElementById(targetId);
        if (!submenu) return;

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

    window.addEventListener("resize", () => {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
}

if (navLinks.length > 0) {
    const currentPath = window.location.pathname;
    navLinks.forEach((link) => {
        link.classList.remove("active");
        const href = link.getAttribute("href");
        if (!href || href === "#") return;
        if (currentPath.endsWith(href)) {
            link.classList.add("active");
        }
    });
}

const logoutBtn = document.querySelector(".logout-btn");
if (logoutBtn) {
    logoutBtn.addEventListener("click", (event) => {
        event.preventDefault();
        const targetUrl = logoutBtn.getAttribute("href") || "/";

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
                    showAdminLoader("Signing out...");
                    window.location.href = targetUrl;
                }
            });
        } else {
            showAdminLoader("Signing out...");
            window.location.href = targetUrl;
        }
    });
}
