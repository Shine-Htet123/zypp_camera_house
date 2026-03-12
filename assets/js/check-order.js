document.addEventListener('DOMContentLoaded', () => {
  const config = window.__orderReupload || null;
  const trigger = document.querySelector('[data-reupload-trigger]');
  const input = document.querySelector('[data-reupload-input]');
  const submitButton = document.querySelector('[data-reupload-submit]');
  const requestCard = document.querySelector('[data-reupload-request-card]');
  const paymentStatusText = document.querySelector('[data-payment-status-text]');
  const fileNameLabel = document.querySelector('[data-reupload-file-name]');

  if (!config || !trigger || !input || !submitButton) {
    return;
  }

  const submitReupload = async (file) => {
    const formData = new FormData();
    formData.set('order_public_id', config.orderPublicId || '');
    formData.set('payment_proof', file);

    const response = await fetch('/auth/payment_reupload.php', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      body: formData,
    });

    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Failed to re-upload payment proof.');
    }

    return payload.payload || {};
  };

  trigger.addEventListener('click', () => {
    input.click();
  });

  input.addEventListener('change', () => {
    const file = input.files?.[0];
    if (!file) {
      if (fileNameLabel) {
        fileNameLabel.textContent = 'No file selected.';
      }
      submitButton.disabled = true;
      return;
    }

    if (!file.type.startsWith('image/')) {
      input.value = '';
      if (fileNameLabel) {
        fileNameLabel.textContent = 'No file selected.';
      }
      submitButton.disabled = true;
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title: 'Invalid file',
          text: 'Payment proof must be an image.',
        });
      }
      return;
    }
    if (fileNameLabel) {
      fileNameLabel.textContent = file.name;
    }
    submitButton.disabled = false;
  });

  submitButton.addEventListener('click', async () => {
    const file = input.files?.[0];
    if (!file) {
      return;
    }
    try {
      const payload = await submitReupload(file);

      if (paymentStatusText && payload.payment_status) {
        const normalized = String(payload.payment_status).trim().toLowerCase();
        paymentStatusText.textContent = normalized.charAt(0).toUpperCase() + normalized.slice(1);
        paymentStatusText.classList.remove('paid', 'unpaid', 'pending');
        paymentStatusText.classList.add(normalized);
      }

      if (requestCard) {
        requestCard.remove();
      }

      input.value = '';
      submitButton.disabled = true;
      if (fileNameLabel) {
        fileNameLabel.textContent = 'No file selected.';
      }

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'success',
          title: 'Re-uploaded',
          text: 'Your new payment proof has been submitted for review.',
          confirmButtonColor: '#52b44b',
        });
      }
    } catch (error) {
      input.value = '';
      submitButton.disabled = true;
      if (fileNameLabel) {
        fileNameLabel.textContent = 'No file selected.';
      }

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Upload failed',
          text: error.message,
        });
      }
    }
  });
});
