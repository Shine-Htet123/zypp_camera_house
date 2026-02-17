const loadingScreen = document.querySelector(".loading-screen");
const loadingVideo = loadingScreen
    ? loadingScreen.querySelector(".page-loading-video")
    : null;

if (loadingScreen) {
    const startTime = performance.now();
    let videoDurationMs = 0;
    let speedTimerId = null;

    const updatePlaybackRate = () => {
        if (!loadingVideo || !videoDurationMs) return;
        const elapsed = performance.now() - startTime;
        const target = Math.max(videoDurationMs, elapsed);
        const rate = Math.min(1, videoDurationMs / target);
        loadingVideo.playbackRate = rate;
    };

    if (loadingVideo) {
        loadingVideo.addEventListener("loadedmetadata", () => {
            videoDurationMs = (loadingVideo.duration || 0) * 1000;
            updatePlaybackRate();
            speedTimerId = window.setInterval(updatePlaybackRate, 250);
        });
    }

    window.addEventListener("load", () => {
        const elapsed = performance.now() - startTime;
        const minTime = videoDurationMs || 0;
        const remaining = Math.max(0, minTime - elapsed);

        window.setTimeout(() => {
            if (speedTimerId) {
                window.clearInterval(speedTimerId);
            }
            loadingScreen.classList.add("hidden");
        }, remaining);
    });
}
