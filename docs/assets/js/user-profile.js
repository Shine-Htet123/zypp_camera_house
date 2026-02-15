const profileSection = document.querySelector("#profile");
const editBtn = document.querySelector("#profile-edit-btn");
const cancelBtn = document.querySelector("#profile-cancel-btn");

if (profileSection && editBtn) {
    editBtn.addEventListener("click", () => {
        profileSection.classList.toggle("editing");
    });
}

if (profileSection && cancelBtn) {
    cancelBtn.addEventListener("click", () => {
        profileSection.classList.remove("editing");
    });
}

const trackForm = document.querySelector("#track-form");
const trackResult = document.querySelector("#track-result");
const closeTrackResult = document.querySelector("#close-track-result");


if (trackForm && trackResult) {
    trackForm.addEventListener("submit", (event) => {
        event.preventDefault();
        trackResult.classList.remove("hidden");
        trackResult.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    if (closeTrackResult) {
        closeTrackResult.addEventListener("click", () => {
            trackResult.classList.add("hidden");
            trackForm.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    }
}

// Flash section headers when navigating via sidebar links
const navLinks = document.querySelectorAll(".profile-nav a[href^=\"#\"]");

const flashSectionHeader = (hash) => {
    const target = document.querySelector(hash);
    if (!target) return;
    const header = target.querySelector(".section-header");
    if (!header) return;
    header.classList.remove("flash");
    void header.offsetWidth;
    header.classList.add("flash");
    header.addEventListener(
        "animationend",
        () => header.classList.remove("flash"),
        { once: true }
    );
};

navLinks.forEach((link) => {
    link.addEventListener("click", () => {
        const hash = link.getAttribute("href");
        if (!hash || hash === "#") return;
        setTimeout(() => flashSectionHeader(hash), 300);
    });
});

if (window.location.hash) {
    setTimeout(() => flashSectionHeader(window.location.hash), 300);
}
