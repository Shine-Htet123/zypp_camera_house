document.addEventListener('DOMContentLoaded', () => {
  const container = document.querySelector('.delivery-methods-left');
  const template = document.getElementById('deliveryMethodEditTemplate');
  const createCard = document.querySelector('.delivery-methods-right .delivery-method-form-card');
  const createForm = createCard?.querySelector('form.delivery-method-form');
  const createSubmit = createCard?.querySelector('[data-create-submit]');
  const createReset = createCard?.querySelector('.btn-reset');
  let activeEditCard = null;

  const formatNumber = (value) => {
    if (!value && value !== 0) return '';
    const numeric = String(value).replace(/[^\d.]/g, '');
    if (!numeric) return '';
    return Number(numeric).toLocaleString('en-US');
  };

  const toggleThresholdRow = (toggle) => {
    if (!toggle) return;
    const targetId = toggle.dataset.thresholdToggle;
    if (!targetId) return;
    const row = document.getElementById(targetId);
    if (!row) return;
    row.classList.toggle('is-hidden', !toggle.checked);
  };

  document.querySelectorAll('[data-threshold-toggle]').forEach((toggle) => {
    toggleThresholdRow(toggle);
    toggle.addEventListener('change', () => toggleThresholdRow(toggle));
  });

  const restoreCard = (card) => {
    if (!card) return;
    const original = card.dataset.originalMarkup;
    if (!original) return;
    card.innerHTML = original;
    card.classList.remove('editing', 'exiting');
    delete card.dataset.originalMarkup;
    activeEditCard = null;
  };

  const openEdit = (card) => {
    if (!card || !template) return;
    if (activeEditCard && activeEditCard !== card) {
      restoreCard(activeEditCard);
    }

    if (card.classList.contains('editing')) return;

    const clone = template.content.cloneNode(true);
    clone.querySelector('.edit-id').value = card.dataset.deliveryMethodId || '0';
    clone.querySelector('.edit-name').value = card.dataset.deliveryMethodName || '';
    clone.querySelector('.edit-threshold').value = formatNumber(card.dataset.freeShippingThreshold || '');

    const supportsFreeShipping = clone.querySelector('.edit-supports-free-shipping');
    if (supportsFreeShipping) {
      supportsFreeShipping.checked = card.dataset.supportsFreeShipping === '1';
    }

    const isActive = clone.querySelector('.edit-is-active');
    if (isActive) {
      isActive.value = card.dataset.isActive === '0' ? '0' : '1';
    }

    card.dataset.originalMarkup = card.innerHTML;
    card.classList.add('editing');
    card.innerHTML = '';
    card.appendChild(clone);
    activeEditCard = card;

    const editToggle = card.querySelector('[data-threshold-toggle]');
    if (editToggle) {
      toggleThresholdRow(editToggle);
      editToggle.addEventListener('change', () => toggleThresholdRow(editToggle));
    }
  };

  container?.addEventListener('click', (event) => {
    const editBtn = event.target.closest('.edit-method');
    const deleteBtn = event.target.closest('.delete-method');
    const cancelBtn = event.target.closest('.btn-cancel');

    if (editBtn) {
      openEdit(editBtn.closest('.delivery-method-card'));
      return;
    }

    if (deleteBtn) {
      if (typeof Swal === 'undefined') return;
      Swal.fire({
        title: 'Delete delivery method?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
      }).then((result) => {
        if (result.isConfirmed) {
          deleteBtn.closest('.delivery-method-card')?.querySelector('.delivery-method-delete-form')?.submit();
        }
      });
      return;
    }

    if (cancelBtn) {
      const card = cancelBtn.closest('.delivery-method-card');
      if (card) {
        card.classList.add('exiting');
        window.setTimeout(() => restoreCard(card), 200);
      }
    }
  });

  createSubmit?.addEventListener('click', () => {
    createForm?.requestSubmit();
  });

  createReset?.addEventListener('click', () => {
    createForm?.reset();
    document.querySelectorAll('[data-threshold-toggle]').forEach((toggle) => toggleThresholdRow(toggle));
  });

  if (window.adminDeliveryMethodsFlash && typeof Swal !== 'undefined') {
    Swal.fire({
      icon: window.adminDeliveryMethodsFlash.type === 'error' ? 'error' : 'success',
      text: window.adminDeliveryMethodsFlash.message || '',
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
