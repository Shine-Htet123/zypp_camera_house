const loadingScreen = document.querySelector('.loading-screen');

if (loadingScreen) {
  const MIN_LOADING_MS = 1000;
  let hideTriggered = false;
  const loaderShownAt = window.performance?.now ? window.performance.now() : Date.now();

  const hideLoader = () => {
    if (hideTriggered) {
      return;
    }

    const now = window.performance?.now ? window.performance.now() : Date.now();
    const remaining = Math.max(0, MIN_LOADING_MS - (now - loaderShownAt));

    window.setTimeout(() => {
      if (hideTriggered) {
        return;
      }

      hideTriggered = true;
      loadingScreen.classList.add('hidden');
    }, remaining);
  };

  if (document.readyState === 'complete') {
    window.requestAnimationFrame(hideLoader);
  } else {
    window.addEventListener('load', () => {
      window.setTimeout(hideLoader, 120);
    }, { once: true });
  }
}
