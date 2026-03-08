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
                    window.location.href = targetUrl;
                }
            });
        } else {
            window.location.href = targetUrl;
        }
    });
}
