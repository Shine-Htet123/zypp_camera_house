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
const bannerTexts = {
    unboxing: banner?.dataset.unboxingBanner || "",
    influencer: banner?.dataset.influencerBanner || ""
};

let activeCategory = "All";
let activeBrand = "All";
let activeEmbedCleanup = null;

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
        banner.textContent = bannerTexts[tab] || "";
    }
};

const appendPlayerParams = (rawUrl, params = {}) => {
    try {
        const parsed = new URL(rawUrl, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            parsed.searchParams.set(key, value);
        });
        return parsed.toString();
    } catch (error) {
        const separator = rawUrl.includes("?") ? "&" : "?";
        const query = new URLSearchParams(params).toString();
        return query ? `${rawUrl}${separator}${query}` : rawUrl;
    }
};

const buildYoutubeEmbedUrl = (videoId, muted = false) => appendPlayerParams(`https://www.youtube.com/embed/${videoId}`, {
    autoplay: "1",
    playsinline: "1",
    rel: "0",
    enablejsapi: "1",
    mute: muted ? "1" : "0",
    origin: window.location.origin
});

const buildVimeoEmbedUrl = (videoId) => appendPlayerParams(`https://player.vimeo.com/video/${videoId}`, {
    autoplay: "1",
    muted: "1",
    autopause: "0"
});

const getEmbedConfig = (url) => {
    if (!url) {
        return {
            type: "placeholder"
        };
    }

    try {
        const parsed = new URL(url, window.location.origin);
        const host = parsed.hostname.replace(/^www\./i, "").toLowerCase();

        if (host === "youtu.be") {
            const videoId = parsed.pathname.replace(/^\/+/, "").split("/")[0];
            if (videoId) {
                return {
                    type: "iframe",
                    provider: "youtube",
                    src: buildYoutubeEmbedUrl(videoId, false),
                    fallbackSrc: buildYoutubeEmbedUrl(videoId, true)
                };
            }
        }

        if (host === "youtube.com" || host === "m.youtube.com") {
            if (parsed.pathname === "/watch") {
                const videoId = parsed.searchParams.get("v");
                if (videoId) {
                    return {
                        type: "iframe",
                        provider: "youtube",
                        src: buildYoutubeEmbedUrl(videoId, false),
                        fallbackSrc: buildYoutubeEmbedUrl(videoId, true)
                    };
                }
            }

            if (parsed.pathname.startsWith("/shorts/")) {
                const videoId = parsed.pathname.split("/")[2] || "";
                if (videoId) {
                    return {
                        type: "iframe",
                        provider: "youtube",
                        src: buildYoutubeEmbedUrl(videoId, false),
                        fallbackSrc: buildYoutubeEmbedUrl(videoId, true)
                    };
                }
            }

            if (parsed.pathname.startsWith("/embed/")) {
                return {
                    type: "iframe",
                    provider: "youtube",
                    src: appendPlayerParams(url, {
                        autoplay: "1",
                        playsinline: "1",
                        rel: "0",
                        enablejsapi: "1",
                        mute: "0",
                        origin: window.location.origin
                    }),
                    fallbackSrc: appendPlayerParams(url, {
                        autoplay: "1",
                        playsinline: "1",
                        rel: "0",
                        enablejsapi: "1",
                        mute: "1",
                        origin: window.location.origin
                    })
                };
            }
        }

        if (host === "vimeo.com") {
            const videoId = parsed.pathname.replace(/^\/+/, "").split("/")[0];
            return {
                type: "iframe",
                provider: "vimeo",
                src: videoId ? buildVimeoEmbedUrl(videoId) : appendPlayerParams(url, { autoplay: "1", muted: "1" })
            };
        }

        if (/youtube\.com|youtu\.be|player\.vimeo\.com|vimeo\.com|embed/i.test(url)) {
            return {
                type: "iframe",
                provider: "generic",
                src: appendPlayerParams(url, { autoplay: "1" })
            };
        }

        return {
            type: "video",
            src: appendPlayerParams(url, { autoplay: "1" })
        };
    } catch (error) {
        return {
            type: "video",
            src: appendPlayerParams(url, { autoplay: "1" })
        };
    }
};

const getEmbedMarkup = (config) => {
    if (config.type === "placeholder") {
        return `
            <div class="video-modal-placeholder">
                <i class="fa-solid fa-play"></i>
                <span>Video preview</span>
            </div>
        `;
    }

    if (config.type === "iframe") {
        return `<iframe src="${config.src}" title="Video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    }

    return `<video src="${config.src}" controls preload="metadata" autoplay playsinline></video>`;
};

const setupYoutubeAutoplayFallback = (iframe, fallbackSrc = "") => {
    let isPlaying = false;
    let hasFallenBack = false;
    let fallbackTimer = null;

    const clearFallbackTimer = () => {
        if (fallbackTimer) {
            window.clearTimeout(fallbackTimer);
            fallbackTimer = null;
        }
    };

    const postYoutubeCommand = (command) => {
        try {
            iframe.contentWindow?.postMessage(JSON.stringify(command), "*");
        } catch (error) {
            // Ignore cross-origin player messaging errors.
        }
    };

    const triggerPlayback = () => {
        postYoutubeCommand({ event: "listening" });
        postYoutubeCommand({ event: "command", func: "playVideo", args: [] });
    };

    const handleMessage = (event) => {
        if (event.source !== iframe.contentWindow) {
            return;
        }

        let payload = event.data;
        if (typeof payload === "string") {
            try {
                payload = JSON.parse(payload);
            } catch (error) {
                return;
            }
        }

        if (!payload || typeof payload !== "object") {
            return;
        }

        const playerState = payload.info?.playerState ?? payload.info;
        if (payload.event === "onReady") {
            triggerPlayback();
            return;
        }

        if (payload.event === "onStateChange" && payload.info === 1) {
            isPlaying = true;
            clearFallbackTimer();
            return;
        }

        if (payload.event === "infoDelivery" && playerState === 1) {
            isPlaying = true;
            clearFallbackTimer();
        }
    };

    const handleLoad = () => {
        triggerPlayback();

        if (hasFallenBack || fallbackSrc === "") {
            return;
        }

        clearFallbackTimer();
        fallbackTimer = window.setTimeout(() => {
            if (isPlaying || hasFallenBack || !videoModal?.classList.contains("open")) {
                return;
            }

            hasFallenBack = true;
            iframe.src = fallbackSrc;
        }, 1500);
    };

    window.addEventListener("message", handleMessage);
    iframe.addEventListener("load", handleLoad);
    window.setTimeout(triggerPlayback, 250);

    return () => {
        clearFallbackTimer();
        window.removeEventListener("message", handleMessage);
        iframe.removeEventListener("load", handleLoad);
    };
};

const openVideoModal = (card) => {
    if (!videoModal || !videoModalBody) return;
    const title = card.querySelector(".video-meta h4")?.textContent?.trim() || "Video";
    const url = card.dataset.video || "";
    const embedConfig = getEmbedConfig(url);

    if (typeof activeEmbedCleanup === "function") {
        activeEmbedCleanup();
        activeEmbedCleanup = null;
    }

    if (videoModalTitle) {
        videoModalTitle.textContent = title;
    }
    videoModalBody.innerHTML = getEmbedMarkup(embedConfig);
    const embeddedFrame = videoModalBody.querySelector("iframe");
    if (embeddedFrame) {
        const startEmbeddedPlayback = () => {
            try {
                if (embedConfig.provider === "youtube") {
                    embeddedFrame.contentWindow?.postMessage(
                        JSON.stringify({ event: "command", func: "playVideo", args: [] }),
                        "*"
                    );
                    return;
                }

                if (embedConfig.provider === "vimeo") {
                    embeddedFrame.contentWindow?.postMessage({ method: "play" }, "*");
                }
            } catch (error) {
                // Ignore autoplay errors from third-party embeds.
            }
        };

        if (embedConfig.provider === "youtube") {
            activeEmbedCleanup = setupYoutubeAutoplayFallback(embeddedFrame, embedConfig.fallbackSrc || "");
        } else {
            embeddedFrame.addEventListener("load", startEmbeddedPlayback, { once: true });
            window.setTimeout(startEmbeddedPlayback, 250);
        }
    }
    const inlineVideo = videoModalBody.querySelector("video");
    if (inlineVideo) {
        inlineVideo.play().catch(() => {});
    }
    videoModal.classList.add("open");
    videoModal.setAttribute("aria-hidden", "false");
    body.classList.add("modal-open");
};

const closeVideoModal = () => {
    if (!videoModal || !videoModalBody) return;
    if (typeof activeEmbedCleanup === "function") {
        activeEmbedCleanup();
        activeEmbedCleanup = null;
    }
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

const initialTab = (() => {
    try {
        const params = new URLSearchParams(window.location.search);
        const tab = (params.get("tab") || "").toLowerCase();
        if (tab === "unboxing" || tab === "influencer") {
            return tab;
        }
    } catch (error) {
        return "unboxing";
    }

    return "unboxing";
})();

setTab(initialTab);
