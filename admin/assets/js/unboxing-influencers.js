document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('.tab-btn');
  const sections = document.querySelectorAll('.media-section');
  const modals = document.querySelectorAll('.modal-overlay');
  const videoPlayerModal = document.getElementById('videoPlayerModal');
  const videoPlayer = document.getElementById('adminVideoPlayer');
  const cards = document.querySelectorAll('.video-card');
  const deleteForm = document.getElementById('adminMediaDeleteForm');
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

  const flash = window.adminMediaFlash || null;
  if (flash?.message && window.Swal) {
    Swal.fire({
      icon: flash.type === 'error' ? 'error' : 'success',
      title: flash.type === 'error' ? 'Media Update Failed' : 'Media Updated',
      text: flash.message,
      confirmButtonColor: '#6b5646',
    });
  }

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
        const host = parsed.hostname.replace(/^www\./i, '').toLowerCase();

        if (host === 'youtu.be') {
          const videoId = parsed.pathname.replace(/^\/+/, '').split('/')[0];
          return videoId ? `https://www.youtube.com/embed/${videoId}` : rawUrl;
        }

        if (host === 'youtube.com' || host === 'm.youtube.com') {
          if (parsed.pathname === '/watch') {
            const videoId = parsed.searchParams.get('v');
            return videoId ? `https://www.youtube.com/embed/${videoId}` : rawUrl;
          }

          if (parsed.pathname.startsWith('/shorts/')) {
            const videoId = parsed.pathname.split('/')[2] || '';
            return videoId ? `https://www.youtube.com/embed/${videoId}` : rawUrl;
          }

          if (parsed.pathname.startsWith('/embed/')) {
            return rawUrl;
          }
        }

        if (host === 'vimeo.com') {
          const videoId = parsed.pathname.replace(/^\/+/, '').split('/')[0];
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

  const closeModal = (modal) => {
    modal?.classList.remove('open');
    modal?.setAttribute('aria-hidden', 'true');
    if (modal === videoPlayerModal && videoPlayer) {
      videoPlayer.innerHTML = '';
    }
  };

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
      } catch (error) {
        cleanup();
        resolve(null);
      }
    };

    const seekToTarget = () => {
      const duration = video.duration;
      if (!isFinite(duration) || duration === 0) {
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

  const setPreviewImage = (preview, src) => {
    if (!preview || !src) return;
    const img = preview.querySelector('img');
    const field = preview.closest('.upload-field');
    if (img) {
      img.src = src;
    }
    preview.classList.add('is-visible');
    field?.classList.add('has-file');
  };

  const setThumbnail = (fileInput, preview) => {
    const file = fileInput.files && fileInput.files[0];
    if (!file || !preview) return;
    const field = preview.closest('.upload-field');
    const img = preview.querySelector('img');
    const url = URL.createObjectURL(file);
    if (file.type.startsWith('image/')) {
      if (img) {
        img.src = url;
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

  const clearThumbnail = (preview) => {
    const field = preview?.closest('.upload-field');
    const img = preview?.querySelector('img');
    const inputId = field?.querySelector('.upload-box')?.dataset?.upload;
    if (img) img.removeAttribute('src');
    preview?.classList.remove('is-visible');
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

  const fillUnboxingEdit = (card) => {
    const modal = document.getElementById('unboxingEditModal');
    if (!modal) return;

    modal.querySelector('#unboxingEditId').value = card.dataset.id || '0';
    modal.querySelector('#unboxingEditTitle').value = card.dataset.title || '';
    modal.querySelector('#unboxingEditCategory').value = card.dataset.categoryId || '';
    modal.querySelector('#unboxingEditBrand').value = card.dataset.brandId || '';
    modal.querySelector('#unboxingEditLink').value = card.dataset.videoLink || '';

    clearThumbnail(document.getElementById('unboxingEditPreview'));
    clearThumbnail(document.getElementById('unboxingEditThumbPreview'));

    const thumbnailSrc = card.dataset.thumbnail || '';
    if (thumbnailSrc) {
      setPreviewImage(document.getElementById('unboxingEditThumbPreview'), thumbnailSrc);
    }

    const videoSrc = card.dataset.src || '';
    if (videoSrc && !/(youtube\.com|youtu\.be|vimeo\.com|embed)/i.test(videoSrc)) {
      grabFrame(videoSrc).then((frame) => {
        if (frame) {
          setPreviewImage(document.getElementById('unboxingEditPreview'), frame);
        }
      });
    }

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  };

  const fillInfluencerEdit = (card) => {
    const modal = document.getElementById('influencerEditModal');
    if (!modal) return;

    modal.querySelector('#influencerEditId').value = card.dataset.id || '0';
    modal.querySelector('#influencerEditTitle').value = card.dataset.title || '';
    modal.querySelector('#influencerEditName').value = card.dataset.influencer || '';
    modal.querySelector('#influencerEditLink').value = card.dataset.videoLink || '';

    clearThumbnail(document.getElementById('influencerEditPreview'));
    clearThumbnail(document.getElementById('influencerEditThumbPreview'));

    const thumbnailSrc = card.dataset.thumbnail || '';
    if (thumbnailSrc) {
      setPreviewImage(document.getElementById('influencerEditThumbPreview'), thumbnailSrc);
    }

    const videoSrc = card.dataset.src || '';
    if (videoSrc && !/(youtube\.com|youtu\.be|vimeo\.com|embed)/i.test(videoSrc)) {
      grabFrame(videoSrc).then((frame) => {
        if (frame) {
          setPreviewImage(document.getElementById('influencerEditPreview'), frame);
        }
      });
    }

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  };

  tabs.forEach((btn) => {
    btn.addEventListener('click', () => {
      tabs.forEach((tab) => tab.classList.remove('active'));
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
      const modal = document.getElementById(openBtn.dataset.open || '');
      modal?.classList.add('open');
      modal?.setAttribute('aria-hidden', 'false');
      return;
    }

    const uploadBox = event.target.closest('.upload-box');
    if (uploadBox) {
      event.preventDefault();
      const inputId = uploadBox.dataset.upload;
      if (inputId) {
        document.getElementById(inputId)?.click();
      }
      return;
    }

    const closeBtn = event.target.closest('.modal-close, .btn-cancel');
    if (closeBtn) {
      closeModal(closeBtn.closest('.modal-overlay'));
      return;
    }

    const deleteBtn = event.target.closest('.video-actions .icon-btn.delete');
    if (deleteBtn) {
      const card = deleteBtn.closest('.video-card');
      const mediaType = deleteBtn.dataset.deleteType || 'unboxing';
      if (!card || !deleteForm || !window.Swal) return;

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
        if (!result.isConfirmed) return;
        deleteForm.querySelector('[name="media_type"]').value = mediaType;
        deleteForm.querySelector('[name="media_id"]').value = card.dataset.id || '0';
        deleteForm.submit();
      });
      return;
    }

    const editBtn = event.target.closest('.video-actions .icon-btn.edit');
    if (editBtn) {
      const card = editBtn.closest('.video-card');
      if (!card) return;
      const isInfluencer = !!card.closest('#influencer');
      if (isInfluencer) {
        fillInfluencerEdit(card);
      } else {
        fillUnboxingEdit(card);
      }
      return;
    }

    const thumb = event.target.closest('.video-thumb');
    if (thumb) {
      const card = thumb.closest('.video-card');
      const src = card?.dataset.src || '';
      if (!src || !videoPlayerModal || !videoPlayer) return;
      videoPlayer.innerHTML = getEmbedMarkup(src);
      videoPlayerModal.classList.add('open');
      videoPlayerModal.setAttribute('aria-hidden', 'false');
      return;
    }

    const previewDelete = event.target.closest('.upload-preview .icon-btn.delete');
    if (previewDelete) {
      clearThumbnail(previewDelete.closest('.upload-preview'));
      return;
    }

    const previewReupload = event.target.closest('.upload-preview .icon-btn.reupload');
    if (previewReupload) {
      const inputId = previewReupload.dataset.upload;
      if (inputId) {
        document.getElementById(inputId)?.click();
      }
    }
  });

  modals.forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal(modal);
      }
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      modals.forEach((modal) => closeModal(modal));
    }
  });
});
