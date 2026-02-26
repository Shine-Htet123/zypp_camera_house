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
