document.addEventListener('DOMContentLoaded', () => {
  const openButton = document.querySelector('[data-open-payment-modal]');
  const overlay = document.querySelector('[data-payment-overlay]');
  const mainModal = document.querySelector('[data-payment-modal]');
  const paymentCard = document.querySelector('[data-payment-card]');
  const formView = document.querySelector('[data-payment-view="form"]');
  const successView = document.querySelector('[data-payment-view="success"]');
  const paymentStep = document.querySelector('[data-step-payment]');
  const confirmationStep = document.querySelector('[data-step-confirmation]');
  const paymentDetails = document.querySelector('[data-payment-details]');
  const paymentActions = document.querySelector('[data-payment-actions]');
  const confirmedPanel = document.querySelector('[data-payment-confirmed]');
  const closeButtons = document.querySelectorAll('[data-close-payment-modal]');
  const uploadInput = document.querySelector('[data-payment-proof-input]');
  const uploadBox = document.querySelector('[data-upload-box]');
  const uploadTrigger = document.querySelector('[data-trigger-payment-upload]');
  const preview = document.querySelector('[data-payment-preview]');
  const previewImage = document.querySelector('[data-payment-preview-image]');
  const submitButton = document.querySelector('[data-submit-payment-proof]');
  const confirmPaymentButton = document.querySelector('[data-confirm-payment]');
  const orderPublicIdEl = document.querySelector('[data-order-public-id]');
  const orderDateEl = document.querySelector('[data-order-date]');
  const orderTimeEl = document.querySelector('[data-order-time]');
  const receiptLink = document.querySelector('[data-receipt-link]');
  const flash = window.__paymentFlash || null;
  const modalAnimationMs = 260;

  if (!openButton || !overlay || !mainModal || !formView || !successView) {
    return;
  }

  if (flash && typeof Swal !== 'undefined' && flash.message) {
    Swal.fire({
      icon: flash.type === 'success' ? 'success' : 'error',
      title: flash.type === 'success' ? 'Payment' : 'Checkout',
      text: flash.message,
    });
  }

  const clearPreview = () => {
    if (!preview || !previewImage) {
      return;
    }

    preview.hidden = true;
    previewImage.src = '';

    if (uploadBox) {
      uploadBox.classList.remove('has-preview');
    }
  };

  const setPreview = (file) => {
    if (!preview || !previewImage || !uploadBox) {
      return;
    }

    if (!file || !file.type.startsWith('image/')) {
      clearPreview();
      return;
    }

    const reader = new FileReader();

    reader.onload = () => {
      previewImage.src = typeof reader.result === 'string' ? reader.result : '';
      preview.hidden = false;
      uploadBox.classList.add('has-preview');
    };

    reader.readAsDataURL(file);
  };

  const isImageFile = (file) => Boolean(file && file.type && file.type.startsWith('image/'));

  const showView = (view) => {
    formView.hidden = view !== 'form';
    successView.hidden = view !== 'success';
  };

  const showConfirmedPage = () => {
    if (paymentStep) {
      paymentStep.classList.remove('active');
      paymentStep.classList.add('done');
    }

    if (confirmationStep) {
      confirmationStep.classList.add('active');
    }

    if (paymentDetails) {
      paymentDetails.hidden = true;
    }

    if (paymentActions) {
      paymentActions.hidden = true;
    }

    if (confirmedPanel) {
      confirmedPanel.hidden = false;
    }

    if (paymentCard) {
      paymentCard.classList.add('is-confirmed');
    }
  };

  const applyConfirmationPayload = (payload) => {
    if (!payload) {
      return;
    }

    if (orderPublicIdEl && payload.order_public_id) {
      orderPublicIdEl.textContent = `#${payload.order_public_id}`;
    }

    if (payload.created_at) {
      const created = new Date(payload.created_at.replace(' ', 'T'));
      if (!Number.isNaN(created.getTime())) {
        if (orderDateEl) {
          orderDateEl.textContent = created.toLocaleDateString('en-GB');
        }
        if (orderTimeEl) {
          orderTimeEl.textContent = created.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
      }
    }

    if (receiptLink && payload.order_public_id) {
      receiptLink.href = `/receipt.php?order=${encodeURIComponent(payload.order_public_id)}`;
    }
  };

  const submitCheckoutAction = async (action, formData = null) => {
    const options = {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
    };

    if (formData) {
      formData.set('action', action);
      options.body = formData;
    } else {
      options.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
      options.body = new URLSearchParams({ action }).toString();
    }

    const response = await fetch('/payment.php', options);
    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Payment request failed.');
    }

    return data.payload || {};
  };

  const switchView = (view) => {
    mainModal.classList.remove('is-active');

    window.setTimeout(() => {
      showView(view);
      requestAnimationFrame(() => {
        mainModal.classList.add('is-active');
      });
    }, Math.round(modalAnimationMs / 2));
  };

  const openModal = () => {
    overlay.hidden = false;
    showView('form');
    requestAnimationFrame(() => {
      overlay.classList.add('is-active');
      mainModal.classList.add('is-active');
    });
    document.body.classList.add('modal-open');
  };

  const closeModal = () => {
    overlay.classList.remove('is-active');
    mainModal.classList.remove('is-active');
    window.setTimeout(() => {
      overlay.hidden = true;
      showView('form');
      document.body.classList.remove('modal-open');
    }, modalAnimationMs);
  };

  openButton.addEventListener('click', () => {
    if (openButton.dataset.codMode === '1') {
      submitCheckoutAction('place_cod_order')
        .then((payload) => {
          applyConfirmationPayload(payload);
          showConfirmedPage();
        })
        .catch((error) => {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Checkout failed',
              text: error.message,
            });
          }
        });
      return;
    }

    openModal();
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeModal);
  });

  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !overlay.hidden) {
      closeModal();
    }
  });

  if (uploadTrigger && uploadInput) {
    uploadTrigger.addEventListener('click', () => uploadInput.click());
  }

  if (uploadInput) {
    uploadInput.addEventListener('change', () => {
      const [file] = uploadInput.files || [];

      if (!isImageFile(file)) {
        uploadInput.value = '';
        clearPreview();
        return;
      }

      setPreview(file);
    });
  }

  if (uploadBox && uploadInput) {
    ['dragenter', 'dragover'].forEach((eventName) => {
      uploadBox.addEventListener(eventName, (event) => {
        event.preventDefault();
        uploadBox.classList.add('is-dragover');
      });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
      uploadBox.addEventListener(eventName, (event) => {
        event.preventDefault();
        uploadBox.classList.remove('is-dragover');
      });
    });

    uploadBox.addEventListener('drop', (event) => {
      const files = event.dataTransfer?.files;

      if (!files || !files.length) {
        return;
      }

      if (!isImageFile(files[0])) {
        return;
      }

      uploadInput.files = files;
      setPreview(files[0]);
    });
  }

  if (submitButton) {
    submitButton.addEventListener('click', async () => {
      const selectedFile = uploadInput?.files?.[0];

      if (!selectedFile) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: 'Upload required',
            text: 'Please upload your payment proof before continuing.',
            confirmButtonColor: '#52b44b',
          });
        }

        return;
      }

      try {
        const formData = new FormData();
        formData.set('payment_proof', selectedFile);
        const payload = await submitCheckoutAction('submit_payment_proof', formData);
        applyConfirmationPayload(payload);
        switchView('success');
      } catch (error) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Upload failed',
            text: error.message || 'Failed to submit payment proof.',
          });
        }
      }
    });
  }

  if (confirmPaymentButton) {
    confirmPaymentButton.addEventListener('click', () => {
      closeModal();
      window.setTimeout(() => {
        showConfirmedPage();
      }, modalAnimationMs);
    });
  }
});
