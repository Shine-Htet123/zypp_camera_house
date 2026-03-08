const loadingScreen = document.querySelector('.loading-screen');
const loadingVideo = loadingScreen
  ? loadingScreen.querySelector('.page-loading-video')
  : null;

if (loadingScreen) {
  let pageLoaded = document.readyState === 'complete';
  let videoFinished = !loadingVideo;
  let hideTriggered = false;

  const hideLoader = () => {
    if (hideTriggered || !pageLoaded || !videoFinished) {
      return;
    }

    hideTriggered = true;
    loadingScreen.classList.add('hidden');
  };

  if (loadingVideo) {
    loadingVideo.currentTime = 0;

    const playPromise = loadingVideo.play();

    if (playPromise && typeof playPromise.catch === 'function') {
      playPromise.catch(() => {});
    }

    loadingVideo.addEventListener('ended', () => {
      videoFinished = true;
      hideLoader();
    });

    loadingVideo.addEventListener('error', () => {
      videoFinished = true;
      hideLoader();
    });
  }

  window.addEventListener('load', () => {
    pageLoaded = true;
    hideLoader();
  });

  hideLoader();
}
