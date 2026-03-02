document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('.tab-btn');
  const sections = document.querySelectorAll('.media-section');
  const modals = document.querySelectorAll('.modal-overlay');
  const videoPlayerModal = document.getElementById('videoPlayerModal');
  const videoPlayer = document.getElementById('adminVideoPlayer');
  const cards = document.querySelectorAll('.video-card');
  const fileInputs = [
    { input: 'unboxingVideoFile', preview: 'unboxingVideoPreview' },
    { input: 'influencerVideoFile', preview: 'influencerVideoPreview' },
    { input: 'unboxingEditFile', preview: 'unboxingEditPreview' },
    { input: 'influencerEditFile', preview: 'influencerEditPreview' },
    { input: 'unboxingThumbFile', preview: 'unboxingThumbPreview' },
    { input: 'influencerThumbFile', preview: 'influencerThumbPreview' },
    { input: 'unboxingEditThumbFile', preview: 'unboxingEditThumbPreview' },
    { input: 'influencerEditThumbFile', preview: 'influencerEditThumbPreview' },
  ];

  const grabFrame = (src) => new Promise((resolve) => {
    const video = document.createElement('video');
    video.preload = 'auto';
    video.muted = true;
    video.playsInline = true;
    video.src = src;

    const cleanup = () => {
      video.pause();
      video.removeAttribute('src');
      video.load();
    };

    const drawFrame = () => {
      const canvas = document.createElement('canvas');
      canvas.width = 320;
      canvas.height = 180;
      const ctx = canvas.getContext('2d');
      if (!ctx) {
        cleanup();
        resolve(null);
        return;
      }
      try {
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.75);
        cleanup();
        resolve(dataUrl);
      } catch (err) {
        cleanup();
        resolve(null);
      }
    };

    const seekToTarget = () => {
      const duration = video.duration;
      if (!isFinite(duration) || duration === 0) {
        // Try first frame
        if (video.readyState >= 2) {
          drawFrame();
        } else {
          cleanup();
          resolve(null);
        }
        return;
      }
      const target = Math.min(Math.max(0.1, duration * 0.3), Math.max(0.1, duration - 0.1));
      video.currentTime = target;
    };

    video.addEventListener('loadeddata', () => {
      // If seek fails, we can still grab current frame
      if (video.readyState >= 2) {
        seekToTarget();
      }
    }, { once: true });

    video.addEventListener('seeked', drawFrame, { once: true });
    video.addEventListener('error', () => {
      cleanup();
      resolve(null);
    }, { once: true });
  });

  const generateThumb = async (card) => {
    const src = card.dataset.src;
    const thumb = card.querySelector('.video-thumb');
    if (!src || !thumb) return;
    const dataUrl = await grabFrame(src);
    if (dataUrl) {
      thumb.style.backgroundImage = `url(${dataUrl})`;
      thumb.classList.add('has-thumb');
    }
  };

  (async () => {
    for (const card of cards) {
      // Process sequentially to avoid decode limits
      // eslint-disable-next-line no-await-in-loop
      await generateThumb(card);
    }
  })();

  const setThumbnail = (fileInput, preview) => {
    const file = fileInput.files && fileInput.files[0];
    if (!file || !preview) return;
    const field = preview.closest('.upload-field');
    const img = preview.querySelector('img');
    const url = URL.createObjectURL(file);
    if (file.type.startsWith('image/')) {
      if (img) {
        img.src = url;
        img.onerror = () => {
          img.removeAttribute('src');
        };
      }
      preview.classList.add('is-visible');
      field?.classList.add('has-file');
      return;
    }

    grabFrame(url).then((dataUrl) => {
      if (dataUrl && img) {
        img.src = dataUrl;
      }
      preview.classList.add('is-visible');
      field?.classList.add('has-file');
    });
  };

  const setThumbnailFromSrc = async (preview, src) => {
    if (!preview || !src) return;
    const field = preview.closest('.upload-field');
    const img = preview.querySelector('img');
    const dataUrl = await grabFrame(src);
    if (dataUrl && img) {
      img.src = dataUrl;
      preview.classList.add('is-visible');
      field?.classList.add('has-file');
    }
  };

  const clearThumbnail = (preview) => {
    const field = preview.closest('.upload-field');
    const img = preview.querySelector('img');
    const inputId = field?.querySelector('.upload-box')?.dataset?.upload;
    if (img) img.removeAttribute('src');
    preview.classList.remove('is-visible');
    field?.classList.remove('has-file');
    if (inputId) {
      const input = document.getElementById(inputId);
      if (input) input.value = '';
    }
  };

  fileInputs.forEach(({ input, preview }) => {
    const fileInput = document.getElementById(input);
    const previewEl = document.getElementById(preview);
    if (!fileInput || !previewEl) return;
    fileInput.addEventListener('change', () => setThumbnail(fileInput, previewEl));
  });

  tabs.forEach((btn) => {
    btn.addEventListener('click', () => {
      tabs.forEach((t) => t.classList.remove('active'));
      btn.classList.add('active');
      const target = btn.dataset.tab;
      sections.forEach((section) => {
        section.classList.toggle('active', section.id === target);
      });
    });
  });

  document.addEventListener('click', (event) => {
    const openBtn = event.target.closest('[data-open]');
    if (openBtn) {
      const id = openBtn.dataset.open;
      const modal = document.getElementById(id);
      modal?.classList.add('open');
      modal?.setAttribute('aria-hidden', 'false');
      return;
    }

    const uploadBox = event.target.closest('.upload-box');
    if (uploadBox) {
      event.preventDefault();
      event.stopPropagation();
      const inputId = uploadBox.dataset.upload;
      if (inputId) {
        document.getElementById(inputId)?.click();
      }
      return;
    }

    const closeBtn = event.target.closest('.modal-close, .btn-cancel');
    if (closeBtn) {
      const modal = closeBtn.closest('.modal-overlay');
      modal?.classList.remove('open');
      modal?.setAttribute('aria-hidden', 'true');
      if (modal === videoPlayerModal && videoPlayer) {
        videoPlayer.pause();
        videoPlayer.removeAttribute('src');
        videoPlayer.load();
      }
      return;
    }

    const deleteBtn = event.target.closest('.video-actions .icon-btn.delete');
    if (deleteBtn) {
      const card = deleteBtn.closest('.video-card');
      if (!card) return;
      if (window.Swal) {
        Swal.fire({
          title: 'Delete this video?',
          text: 'This action cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
        }).then((result) => {
          if (result.isConfirmed) {
            card.remove();
            Swal.fire('Deleted', 'The video has been removed.', 'success');
          }
        });
      } else if (window.confirm('Delete this item?')) {
        card.remove();
      }
      return;
    }

    const editBtn = event.target.closest('.video-actions .icon-btn.edit');
    if (editBtn) {
      const card = editBtn.closest('.video-card');
      const isInfluencer = card?.closest('#influencer');
      if (isInfluencer) {
        const modal = document.getElementById('influencerEditModal');
        const title = modal?.querySelector('#influencerEditTitle');
        const name = modal?.querySelector('#influencerEditName');
        const preview = modal?.querySelector('#influencerEditPreview');
        const src = card?.dataset.src;
        if (title) title.value = card?.dataset.title || '';
        if (name) name.value = card?.dataset.influencer || '';
        modal?.classList.add('open');
        modal?.setAttribute('aria-hidden', 'false');
        if (src && preview) {
          setThumbnailFromSrc(preview, src);
        }
      } else {
        const modal = document.getElementById('unboxingEditModal');
        const title = modal?.querySelector('#unboxingEditTitle');
        const preview = modal?.querySelector('#unboxingEditPreview');
        const src = card?.dataset.src;
        if (title) title.value = card?.dataset.title || '';
        modal?.classList.add('open');
        modal?.setAttribute('aria-hidden', 'false');
        if (src && preview) {
          setThumbnailFromSrc(preview, src);
        }
      }
      return;
    }

    const thumb = event.target.closest('.video-thumb');
    if (thumb) {
      const card = thumb.closest('.video-card');
      const src = card?.dataset.src;
      if (!src || !videoPlayerModal || !videoPlayer) return;
      videoPlayer.src = src;
      videoPlayerModal.classList.add('open');
      videoPlayerModal.setAttribute('aria-hidden', 'false');
      videoPlayer.play().catch(() => {});
      return;
    }

    const previewDelete = event.target.closest('.upload-preview .icon-btn.delete');
    if (previewDelete) {
      const preview = previewDelete.closest('.upload-preview');
      clearThumbnail(preview);
      return;
    }

    const previewReupload = event.target.closest('.upload-preview .icon-btn.reupload');
    if (previewReupload) {
      const inputId = previewReupload.dataset.upload;
      if (inputId) {
        document.getElementById(inputId)?.click();
      }
      return;
    }
  });

  modals.forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        if (modal === videoPlayerModal && videoPlayer) {
          videoPlayer.pause();
          videoPlayer.removeAttribute('src');
          videoPlayer.load();
        }
      }
    });
  });
});
