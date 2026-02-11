const categoryButtons = document.querySelectorAll(".category-card");
const policyList = document.querySelector("#policy-list");
const faqList = document.querySelector("#faq-list");
const categoryList = document.querySelector(".category-list");
const arrowButtons = document.querySelectorAll(".category-selector .arrow-btn");

const dataEl = document.querySelector("#warranty-data");
let data = {};

if (dataEl) {
    try {
        data = JSON.parse(dataEl.textContent);
    } catch (error) {
        data = {};
    }
}

const renderPolicy = (items) => {
    if (!policyList) return;
    policyList.innerHTML = items.map((item) => `<li>${item}</li>`).join("");
};

const renderFaqs = (items) => {
    if (!faqList) return;
    faqList.innerHTML = items
        .map(
            (item, index) => `
            <div class="faq-item ${index === 0 ? "open" : ""}">
                <button type="button" class="faq-question">
                    <span>${item.q}</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>${item.a}</p>
                </div>
            </div>
        `
        )
        .join("");
};

const setActiveCategory = (key) => {
    categoryButtons.forEach((btn) => {
        btn.classList.toggle("active", btn.dataset.category === key);
    });
    const categoryData = data[key];
    if (categoryData) {
        renderPolicy(categoryData.policy);
        renderFaqs(categoryData.faqs);
    }
};

categoryButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
        setActiveCategory(btn.dataset.category);
    });
});

faqList?.addEventListener("click", (event) => {
    const question = event.target.closest(".faq-question");
    if (!question) return;
    const item = question.closest(".faq-item");
    if (!item) return;
    item.classList.toggle("open");
});

setActiveCategory("cameras");

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
