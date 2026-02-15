const categoryButtons = document.querySelectorAll(".category-card");
const policyGroups = document.querySelectorAll(".policy-group");
const faqGroups = document.querySelectorAll(".faq-group");
const categoryList = document.querySelector(".category-list");
const arrowButtons = document.querySelectorAll(".category-selector .arrow-btn");
const setActiveCategory = (key) => {
    categoryButtons.forEach((btn) => {
        btn.classList.toggle("active", btn.dataset.category === key);
    });
    policyGroups.forEach((group) => {
        group.classList.toggle("active", group.dataset.category === key);
    });
    faqGroups.forEach((group) => {
        group.classList.toggle("active", group.dataset.category === key);
    });
};

categoryButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
        setActiveCategory(btn.dataset.category);
    });
});

document.addEventListener("click", (event) => {
    const question = event.target.closest(".faq-question");
    if (!question) return;
    const item = question.closest(".faq-item");
    if (!item) return;
    item.classList.toggle("open");
});

const defaultCategory = document.querySelector(".category-card.active")?.dataset.category;
if (defaultCategory) {
    setActiveCategory(defaultCategory);
}

const scrollCategoryList = (direction) => {
    if (!categoryList) return;
    const card = categoryList.querySelector(".category-card");
    if (!card) return;
    const style = window.getComputedStyle(categoryList);
    const gap = parseFloat(style.columnGap || style.gap || "0");
    const step = card.getBoundingClientRect().width + gap;
    const perView = Math.max(1, Math.round(categoryList.clientWidth / step));
    categoryList.scrollBy({ left: direction * step * perView, behavior: "smooth" });
};

arrowButtons.forEach((btn, index) => {
    btn.addEventListener("click", () => {
        scrollCategoryList(index === 0 ? -1 : 1);
    });
});
