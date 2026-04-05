const mainImage = document.querySelector("#main-image");
const zoomContainer = document.querySelector("#zoom-container");
const thumbnailRow = document.querySelector("#thumbnail-row");

if (thumbnailRow && mainImage) {
    thumbnailRow.addEventListener("click", (event) => {
        const button = event.target.closest(".thumb");
        if (!button) return;

        const nextSrc = button.dataset.src;
        if (nextSrc) {
            mainImage.src = nextSrc;
        }

        thumbnailRow.querySelectorAll(".thumb").forEach((thumb) => {
            thumb.classList.toggle("active", thumb === button);
        });
    });
}

if (zoomContainer && mainImage) {
    const isTouchDevice = window.matchMedia("(hover: none), (pointer: coarse)").matches;

    const setOrigin = (clientX, clientY) => {
        const rect = zoomContainer.getBoundingClientRect();
        const x = ((clientX - rect.left) / rect.width) * 100;
        const y = ((clientY - rect.top) / rect.height) * 100;
        mainImage.style.transformOrigin = `${x}% ${y}%`;
    };

    if (isTouchDevice) {
        let lastTap = 0;
        let isZoomed = false;

        zoomContainer.addEventListener("touchstart", (event) => {
            if (!event.touches.length) return;
            const touch = event.touches[0];
            const now = Date.now();
            setOrigin(touch.clientX, touch.clientY);

            if (now - lastTap < 300) {
                isZoomed = !isZoomed;
                zoomContainer.classList.toggle("zoomed", isZoomed);
                if (!isZoomed) {
                    mainImage.style.transformOrigin = "center";
                }
            } else {
                isZoomed = true;
                zoomContainer.classList.add("zoomed");
            }

            lastTap = now;
        });

        zoomContainer.addEventListener(
            "touchmove",
            (event) => {
                if (!isZoomed || !event.touches.length) return;
                event.preventDefault();
                const touch = event.touches[0];
                setOrigin(touch.clientX, touch.clientY);
            },
            { passive: false }
        );

        zoomContainer.addEventListener("touchend", () => {
            if (!isZoomed) {
                mainImage.style.transformOrigin = "center";
            }
        });
    } else {
        zoomContainer.addEventListener("mousemove", (event) => {
            setOrigin(event.clientX, event.clientY);
            zoomContainer.classList.add("zoomed");
        });

        zoomContainer.addEventListener("mouseleave", () => {
            zoomContainer.classList.remove("zoomed");
            mainImage.style.transformOrigin = "center";
        });
    }
}

// Quantity controls
const qtyControls = document.querySelector(".qty-controls");

if (qtyControls) {
    const qtyValue = qtyControls.querySelector("span");
    const [minusBtn, plusBtn] = qtyControls.querySelectorAll("button");
    const productInfo = document.querySelector("[data-product-info]");
    const maxQty = Math.max(1, Number(productInfo?.dataset.productStock || "1"));

    const getQty = () => Number(qtyValue.textContent || "1");
    const setQty = (value) => {
        qtyValue.textContent = String(Math.min(maxQty, Math.max(1, value)));
    };

    if (minusBtn) {
        minusBtn.addEventListener("click", () => {
            setQty(getQty() - 1);
        });
    }

    if (plusBtn) {
        plusBtn.addEventListener("click", () => {
            setQty(getQty() + 1);
        });
    }
}

const addToCartButton = document.querySelector("[data-add-to-cart]");

if (addToCartButton) {
    addToCartButton.addEventListener("click", async () => {
        const productInfo = document.querySelector("[data-product-info]");
        const qtyValue = document.querySelector(".qty-controls span");
        const productId = Number(productInfo?.dataset.productId || "0");
        const quantity = Math.max(1, Number(qtyValue?.textContent || "1"));
        const cartAddUrl =
            typeof window.appPath === "function"
                ? window.appPath("/auth/cart_add.php")
                : "/auth/cart_add.php";
        const showAlert = (options) => {
            if (typeof Swal !== "undefined") {
                return Swal.fire(options);
            }

            window.alert(options?.text || options?.title || "Done.");
            return Promise.resolve();
        };

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
                    document.getElementById("user-icon")?.click();
                }
                throw new Error(payload.message || "Failed to add item to cart.");
            }

            const cartCount = document.querySelector(".cart-count span");
            if (cartCount && payload.payload && typeof payload.payload.cart_count !== "undefined") {
                cartCount.textContent = String(payload.payload.cart_count);
            }

            showAlert({
                icon: "success",
                title: "Added to cart",
                text: payload.message || "The product has been added to your cart.",
            });
        } catch (error) {
            showAlert({
                icon: "error",
                title: "Add to cart failed",
                text: error.message,
            });
        }
    });
}

document.querySelectorAll('.option-list').forEach((list) => {
    list.addEventListener('click', (event) => {
        const chip = event.target.closest('.option-chip');
        if (!chip) return;

        list.querySelectorAll('.option-chip').forEach((item) => {
            item.classList.toggle('active', item === chip);
        });
    });
});

// Related products slider (non-looping with muted chevrons)
const relatedSlider = document.querySelector(".related-products .best-seller-slider");
const relatedCards = document.querySelectorAll(".related-products .product-card");
const relatedChevrons = document.querySelectorAll(".related-products .chevrons i");

function getRelatedStep() {
    if (!relatedCards.length || !relatedSlider) return 0;
    const cardRect = relatedCards[0].getBoundingClientRect();
    const sliderStyles = window.getComputedStyle(relatedSlider);
    const gap = parseFloat(sliderStyles.columnGap || sliderStyles.gap || "0");
    return Math.ceil(cardRect.width + gap);
}

function updateRelatedChevrons() {
    if (!relatedSlider || relatedChevrons.length < 2) return;
    const maxScroll = relatedSlider.scrollWidth - relatedSlider.clientWidth;
    const atStart = relatedSlider.scrollLeft <= 0;
    const atEnd = relatedSlider.scrollLeft >= maxScroll - 1;

    relatedChevrons[0].classList.toggle("muted", atStart);
    relatedChevrons[1].classList.toggle("muted", atEnd);
}

function scrollRelated(direction) {
    if (!relatedSlider) return;
    const step = getRelatedStep();
    if (!step) return;

    const maxScroll = relatedSlider.scrollWidth - relatedSlider.clientWidth;
    const nextScroll = Math.min(
        Math.max(relatedSlider.scrollLeft + direction * step, 0),
        maxScroll
    );

    relatedSlider.scrollTo({ left: nextScroll, behavior: "smooth" });
}

if (relatedSlider && relatedChevrons.length >= 2) {
    relatedChevrons[0].addEventListener("click", () => scrollRelated(-1));
    relatedChevrons[1].addEventListener("click", () => scrollRelated(1));
    relatedSlider.addEventListener("scroll", updateRelatedChevrons);
    window.addEventListener("resize", updateRelatedChevrons);
    updateRelatedChevrons();
}
