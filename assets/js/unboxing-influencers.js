const tabButtons = document.querySelectorAll(".tab-btn");
const panels = {
    unboxing: document.querySelector("#unboxing-panel"),
    influencer: document.querySelector("#influencer-panel")
};
const banner = document.querySelector("#media-banner");
const unboxingGrid = document.querySelector("#unboxing-grid");
const videoModal = document.querySelector("#video-modal");
const videoModalBody = document.querySelector("#video-modal-body");
const videoModalTitle = document.querySelector("#video-modal-title");
const body = document.body;

let activeCategory = "All";
let activeBrand = "All";

const renderUnboxing = () => {
    if (!unboxingGrid) return;
    const cards = Array.from(unboxingGrid.querySelectorAll(".video-card"));
    cards.forEach((card) => {
        const category = card.dataset.category || "";
        const brand = card.dataset.brand || "";
        const matchCategory = activeCategory === "All" || category === activeCategory;
        const matchBrand = activeBrand === "All" || brand === activeBrand;
        card.style.display = matchCategory && matchBrand ? "" : "none";
    });
};

const setTab = (tab) => {
    tabButtons.forEach((btn) => {
        btn.classList.toggle("active", btn.dataset.tab === tab);
    });
    Object.keys(panels).forEach((key) => {
        if (panels[key]) {
            panels[key].classList.toggle("active", key === tab);
        }
    });
    if (banner) {
        banner.textContent =
            tab === "unboxing"
                ? "Unbox the hype — watch creators try our products!"
                : "Real reviews. Real unboxings. Real results.";
    }
};

const getEmbedMarkup = (url) => {
    if (!url) {
        return `
            <div class="video-modal-placeholder">
                <i class="fa-solid fa-play"></i>
                <span>Video preview</span>
            </div>
        `;
    }

    const normalizeEmbedUrl = (rawUrl) => {
        try {
            const parsed = new URL(rawUrl, window.location.origin);
            const host = parsed.hostname.replace(/^www\./i, "").toLowerCase();

            if (host === "youtu.be") {
                const videoId = parsed.pathname.replace(/^\/+/, "").split("/")[0];
                return videoId ? `https://www.youtube.com/embed/${videoId}` : rawUrl;
            }

            if (host === "youtube.com" || host === "m.youtube.com") {
                if (parsed.pathname === "/watch") {
                    const videoId = parsed.searchParams.get("v");
                    return videoId ? `https://www.youtube.com/embed/${videoId}` : rawUrl;
                }

                if (parsed.pathname.startsWith("/shorts/")) {
                    const videoId = parsed.pathname.split("/")[2] || "";
                    return videoId ? `https://www.youtube.com/embed/${videoId}` : rawUrl;
                }

                if (parsed.pathname.startsWith("/embed/")) {
                    return rawUrl;
                }
            }

            if (host === "vimeo.com") {
                const videoId = parsed.pathname.replace(/^\/+/, "").split("/")[0];
                return videoId ? `https://player.vimeo.com/video/${videoId}` : rawUrl;
            }

            return rawUrl;
        } catch (error) {
            return rawUrl;
        }
    };

    const embedUrl = normalizeEmbedUrl(url);
    const isIframe = /(youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com|embed)/i.test(embedUrl);
    if (isIframe) {
        return `<iframe src="${embedUrl}" title="Video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    }

    return `<video src="${embedUrl}" controls preload="metadata"></video>`;
};

const openVideoModal = (card) => {
    if (!videoModal || !videoModalBody) return;
    const title = card.querySelector(".video-meta h4")?.textContent?.trim() || "Video";
    const url = card.dataset.video || "";
    if (videoModalTitle) {
        videoModalTitle.textContent = title;
    }
    videoModalBody.innerHTML = getEmbedMarkup(url);
    videoModal.classList.add("open");
    videoModal.setAttribute("aria-hidden", "false");
    body.classList.add("modal-open");
};

const closeVideoModal = () => {
    if (!videoModal || !videoModalBody) return;
    videoModal.classList.remove("open");
    videoModal.setAttribute("aria-hidden", "true");
    videoModalBody.innerHTML = "";
    body.classList.remove("modal-open");
};

document.addEventListener("click", (event) => {
    const tabBtn = event.target.closest(".tab-btn");
    if (tabBtn) {
        setTab(tabBtn.dataset.tab);
        return;
    }

    const filterHeader = event.target.closest(".filter-header");
    if (filterHeader) {
        const group = filterHeader.closest(".filter-group");
        if (group) {
            group.classList.toggle("open");
        }
        return;
    }

    const filterBtn = event.target.closest(".filter-option");
    if (filterBtn) {
        const type = filterBtn.dataset.type;
        const value = filterBtn.dataset.value;
        if (type === "category") {
            activeCategory = value;
        }
        if (type === "brand") {
            activeBrand = value;
        }
        document.querySelectorAll(`.filter-option[data-type="${type}"]`).forEach((btn) => {
            btn.classList.toggle("active", btn === filterBtn);
        });
        renderUnboxing();
        return;
    }

    const videoCard = event.target.closest(".video-card");
    if (videoCard) {
        openVideoModal(videoCard);
        return;
    }

    if (event.target.closest(".video-modal-close")) {
        closeVideoModal();
        return;
    }

    if (videoModal && event.target === videoModal) {
        closeVideoModal();
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && videoModal?.classList.contains("open")) {
        closeVideoModal();
    }
});

renderUnboxing();
setTab("unboxing");
