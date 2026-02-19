const revealSelectors = [
    "section",
    ".profile-section",
    ".policy-box",
    ".order-card",
    ".delivery-card"
];

const excludedClasses = new Set(["top", "menu-container", "hero-section"]);

const candidates = Array.from(
    document.querySelectorAll(revealSelectors.join(","))
).filter((el) => !Array.from(el.classList).some((cls) => excludedClasses.has(cls)));

if ("IntersectionObserver" in window && candidates.length) {
    candidates.forEach((el) => el.classList.add("reveal-on-scroll"));

    const observer = new IntersectionObserver(
        (entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    obs.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15, rootMargin: "0px 0px -10% 0px" }
    );

    candidates.forEach((el) => observer.observe(el));
}
