const heroContainer = document.querySelector(".hero-container");
const heroWrappers = document.querySelectorAll(".hero-wrapper");
const bubbles = document.querySelectorAll(".bubble");

let currentIndex = 0;
let autoSlideInterval = null;
const SLIDE_DURATION = 5000; // 5s

function goToSlide(index) {
    if (!heroContainer || heroWrappers.length === 0) {
        return;
    }

    currentIndex = index;

    // Slide the wrappers
    heroContainer.style.transform = `translateX(-${index * 100}vw)`;

    // Update bubbles
    bubbles.forEach((bubble, i) => {
        bubble.classList.toggle("active", i === index);
    });

    // Reset auto slide
    resetAutoSlide();
}

function nextSlide() {
    let nextIndex = (currentIndex + 1) % heroWrappers.length;
    goToSlide(nextIndex);
}

function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    autoSlideInterval = setInterval(nextSlide, SLIDE_DURATION);
}

// Bubble click navigation
bubbles.forEach((bubble, i) => {
    bubble.addEventListener("click", () => {
        goToSlide(i);
    });
});

// Init
if (heroContainer && heroWrappers.length > 0) {
    goToSlide(0);
}

// Best Sellers slider (non-looping with muted chevrons at ends)
const bestSellerSlider = document.querySelector(".best-seller-slider");
const bestSellerCards = document.querySelectorAll(".best-seller-slider .product-card");
const bestSellerChevrons = document.querySelectorAll(".best-sellers-container .chevrons i");

function getCardStep() {
    if (!bestSellerCards.length || !bestSellerSlider) return 0;
    const cardRect = bestSellerCards[0].getBoundingClientRect();
    const sliderStyles = window.getComputedStyle(bestSellerSlider);
    const gap = parseFloat(sliderStyles.columnGap || sliderStyles.gap || "0");
    return Math.ceil(cardRect.width + gap);
}

function updateBestSellerChevrons() {
    if (!bestSellerSlider || bestSellerChevrons.length < 2) return;
    const maxScroll = bestSellerSlider.scrollWidth - bestSellerSlider.clientWidth;
    const atStart = bestSellerSlider.scrollLeft <= 0;
    const atEnd = bestSellerSlider.scrollLeft >= maxScroll - 1;

    bestSellerChevrons[0].classList.toggle("muted", atStart);
    bestSellerChevrons[1].classList.toggle("muted", atEnd);
}

function scrollBestSellers(direction) {
    if (!bestSellerSlider) return;
    const step = getCardStep();
    if (!step) return;

    const maxScroll = bestSellerSlider.scrollWidth - bestSellerSlider.clientWidth;
    const nextScroll = Math.min(
        Math.max(bestSellerSlider.scrollLeft + direction * step, 0),
        maxScroll
    );

    bestSellerSlider.scrollTo({ left: nextScroll, behavior: "smooth" });
}

if (bestSellerSlider && bestSellerChevrons.length >= 2) {
    bestSellerChevrons[0].addEventListener("click", () => scrollBestSellers(-1));
    bestSellerChevrons[1].addEventListener("click", () => scrollBestSellers(1));
    bestSellerSlider.addEventListener("scroll", updateBestSellerChevrons);
    window.addEventListener("resize", updateBestSellerChevrons);
    updateBestSellerChevrons();
}

// Trusted brands + featured categories carousels
const carouselTracks = document.querySelectorAll("[data-carousel-track]");

const getCarouselStep = (track) => {
    const firstItem = track.children[0];
    if (!firstItem) return 0;
    const itemRect = firstItem.getBoundingClientRect();
    const styles = window.getComputedStyle(track);
    const gap = parseFloat(styles.columnGap || styles.gap || "0");
    return Math.ceil(itemRect.width + gap);
};

const scrollCarousel = (track, direction) => {
    const step = getCarouselStep(track);
    if (!step) return;
    track.scrollTo({
        left: track.scrollLeft + direction * step,
        behavior: "smooth"
    });
};

carouselTracks.forEach((track) => {
    const name = track.dataset.carouselTrack;
    const prevBtn = document.querySelector(`.carousel-btn[data-carousel="${name}"][data-direction="prev"]`);
    const nextBtn = document.querySelector(`.carousel-btn[data-carousel="${name}"][data-direction="next"]`);

    if (prevBtn) {
        prevBtn.addEventListener("click", () => scrollCarousel(track, -1));
    }

    if (nextBtn) {
        nextBtn.addEventListener("click", () => scrollCarousel(track, 1));
    }
});

// Review form toggle
const leaveReviewBtn = document.querySelector("#leave-review-btn");
const reviewForm = document.querySelector("#review-form");

if (leaveReviewBtn && reviewForm) {
    leaveReviewBtn.addEventListener("click", () => {
        const isOpen = reviewForm.classList.toggle("show");
        leaveReviewBtn.textContent = isOpen ? "Back" : "Leave a Review";
    });
}

// Review rating selection
const ratingStars = document.querySelectorAll(".review-form .stars i");
const ratingValueInput = document.querySelector("#rating-value");

if (ratingStars.length && ratingValueInput) {
    ratingStars.forEach((star) => {
        star.addEventListener("click", () => {
            const value = Number(star.dataset.value || "0");
            ratingValueInput.value = String(value);

            ratingStars.forEach((s) => {
                const sValue = Number(s.dataset.value || "0");
                s.classList.toggle("fa-solid", sValue <= value);
                s.classList.toggle("fa-regular", sValue > value);
            });
        });
    });
}

const openCustomerLoginPanel = () => {
    document.getElementById("user-icon")?.click();
};

const showCartAlert = (options) => {
    if (typeof Swal !== "undefined") {
        return Swal.fire(options);
    }

    window.alert(options?.text || options?.title || "Done.");
    return Promise.resolve();
};

const updateCartCount = (count) => {
    const cartCount = document.querySelector(".cart-count span");
    if (!cartCount || typeof count === "undefined") {
        return;
    }

    cartCount.textContent = String(count);
};

document.querySelectorAll("[data-home-add-to-cart]").forEach((button) => {
    button.addEventListener("click", async (event) => {
        event.preventDefault();
        const productId = Number(button.dataset.productId || "0");
        const quantity = Math.max(1, Number(button.dataset.quantity || "1"));
        const cartAddUrl =
            typeof window.appPath === "function"
                ? window.appPath("/auth/cart_add.php")
                : "/auth/cart_add.php";

        try {
            const body = new URLSearchParams({
                action: "add_item",
                product_id: String(productId),
                quantity: String(quantity),
            });

            const response = await fetch(cartAddUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
                credentials: "same-origin",
                body: body.toString(),
            });

            const payload = await response.json().catch(() => ({
                success: false,
                message: "Failed to add item to cart.",
            }));
            if (!response.ok || !payload.success) {
                if (payload.login_required) {
                    openCustomerLoginPanel();
                }
                throw new Error(payload.message || "Failed to add item to cart.");
            }

            updateCartCount(payload.payload?.cart_count);

            showCartAlert({
                icon: "success",
                title: "Added to cart",
                text: payload.message || "The product has been added to your cart.",
            });
        } catch (error) {
            showCartAlert({
                icon: "error",
                title: "Add to cart failed",
                text: error.message,
            });
        }
    });
});

const renderReviewStars = (rating) => {
    let html = "";
    for (let value = 1; value <= 5; value += 1) {
        html += `<i class="${value <= rating ? "fa-solid" : "fa-regular"} fa-star"></i>`;
    }
    return html;
};

const createReviewCard = (review) => {
    const card = document.createElement("div");
    card.className = "review-card";
    card.innerHTML = `
        <div class="user-info">
            <div class="user-profile">
                <i class="fa-regular fa-user"></i>
            </div>
            <div class="user-name-rating">
                <span class="user-name"></span>
                <div class="rating">${renderReviewStars(Number(review.rating || 0))}</div>
            </div>
        </div>
        <h3 class="review-topic"></h3>
        <p class="review-text"></p>
    `;

    card.querySelector(".user-name").textContent = review.user_name || "Customer";
    card.querySelector(".review-topic").textContent = review.topic && review.topic.trim() !== "" ? review.topic : "Customer Review";
    card.querySelector(".review-text").textContent = review.comment || "";

    return card;
};

if (reviewForm) {
    reviewForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const submitButton = reviewForm.querySelector(".submit-review");
        const formData = new FormData(reviewForm);

        try {
            submitButton?.setAttribute("disabled", "disabled");

            const response = await fetch(reviewForm.action || "/auth/review_submit.php", {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
                body: formData,
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                if (payload.login_required) {
                    openCustomerLoginPanel();
                }
                throw new Error(payload.message || "Failed to submit review.");
            }

            const reviewList = document.querySelector(".review-list");
            if (reviewList) {
                reviewList.querySelector(".home-empty-state-wide")?.remove();
                reviewList.prepend(createReviewCard(payload.payload?.review || {}));

                const cards = reviewList.querySelectorAll(".review-card");
                if (cards.length > 4) {
                    cards[cards.length - 1].remove();
                }
            }

            reviewForm.reset();
            ratingValueInput.value = "0";
            ratingStars.forEach((star) => {
                star.classList.remove("fa-solid");
                star.classList.add("fa-regular");
            });
            reviewForm.classList.remove("show");
            if (leaveReviewBtn) {
                leaveReviewBtn.textContent = "Leave a Review";
            }

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "success",
                    title: "Review submitted",
                    text: payload.message || "Thank you for your review.",
                });
            }
        } catch (error) {
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "error",
                    title: "Review failed",
                    text: error.message,
                });
            }
        } finally {
            submitButton?.removeAttribute("disabled");
        }
    });
}

// Scroll reveal (exclude hero section)
const revealTargets = document.querySelectorAll("section:not(.hero-section)");

if ("IntersectionObserver" in window && revealTargets.length) {
    revealTargets.forEach((el) => el.classList.add("reveal-on-scroll"));

    const revealObserver = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15, rootMargin: "0px 0px -10% 0px" }
    );

    revealTargets.forEach((el) => revealObserver.observe(el));
}
