document.addEventListener('DOMContentLoaded', () => {
  const pageUrl = window.location.href;
  const container = document.querySelector('.delivery-methods-left');
  const cardsWrap = document.querySelector('.delivery-method-cards');
  const template = document.getElementById('deliveryMethodEditTemplate');
  const createCard = document.querySelector('.delivery-methods-right .delivery-method-form-card');
  const createForm = createCard?.querySelector('form.delivery-method-form');
  const createSubmit = createCard?.querySelector('[data-create-submit]');
  const createReset = createCard?.querySelector('.btn-reset');
  let activeEditCard = null;

  const showAlert = async (options) => {
    if (window.Swal) {
      return Swal.fire(options);
    }
    const message = options?.text || options?.title || '';
    if (message) alert(message);
    return null;
  };

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

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

  const syncThresholdToggles = (root = document) => {
    root.querySelectorAll('[data-threshold-toggle]').forEach((toggle) => {
      toggleThresholdRow(toggle);
      toggle.addEventListener('change', () => toggleThresholdRow(toggle));
    });
  };

  const requestJson = async (formData) => {
    window.AdminLoading?.show('Saving changes...');
    try {
      const response = await fetch(pageUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
      });
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload?.success) {
        throw new Error(payload?.message || 'Request failed.');
      }
      return payload;
    } finally {
      window.AdminLoading?.hide();
    }
  };

  const buildMethodDescription = (method) => {
    if (method.supports_free_shipping) {
      if (method.free_shipping_threshold_label) {
        return `Free shipping at ${method.free_shipping_threshold_label}`;
      }
      return 'Free shipping enabled';
    }
    return 'Paid delivery only';
  };

  const renderCards = (methods) => {
    if (!cardsWrap) return;
    activeEditCard = null;
    cardsWrap.innerHTML = (methods || []).map((method) => `
      <article
        class="delivery-method-card"
        data-delivery-method-id="${Number(method.delivery_method_id || 0)}"
        data-delivery-method-name="${escapeHtml(method.name || '')}"
        data-supports-free-shipping="${method.supports_free_shipping ? '1' : '0'}"
        data-free-shipping-threshold="${escapeHtml(method.free_shipping_threshold ?? '')}"
        data-is-active="${method.is_active ? '1' : '0'}"
      >
        <div class="delivery-method-icon">
          <i class="fa-solid fa-truck-fast"></i>
        </div>
        <h3>${escapeHtml(method.name || '')}</h3>
        <p>${escapeHtml(buildMethodDescription(method))}</p>
        <span class="delivery-method-status ${method.is_active ? 'is-active' : 'is-inactive'}">
          ${method.is_active ? 'Active' : 'Inactive'}
        </span>
        <div class="delivery-method-actions">
          <button type="button" class="icon-btn edit-method" aria-label="Edit delivery method">
            <i class="fa-regular fa-pen-to-square"></i>
          </button>
          <button type="button" class="icon-btn delete delete-method" aria-label="Delete delivery method">
            <i class="fa-regular fa-trash-can"></i>
          </button>
        </div>
        <form class="delivery-method-delete-form" method="post">
          <input type="hidden" name="delivery_method_action" value="delete">
          <input type="hidden" name="delivery_method_id" value="${Number(method.delivery_method_id || 0)}">
        </form>
      </article>
    `).join('');
  };

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
    syncThresholdToggles(card);
  };

  container?.addEventListener('click', async (event) => {
    const editBtn = event.target.closest('.edit-method');
    const deleteBtn = event.target.closest('.delete-method');
    const cancelBtn = event.target.closest('.btn-cancel');

    if (editBtn) {
      openEdit(editBtn.closest('.delivery-method-card'));
      return;
    }

    if (deleteBtn) {
      const card = deleteBtn.closest('.delivery-method-card');
      const form = card?.querySelector('.delivery-method-delete-form');
      if (!form) return;

      const confirmation = await showAlert({
        title: 'Delete delivery method?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
      });

      if (!confirmation?.isConfirmed) return;

      try {
        const payload = await requestJson(new FormData(form));
        renderCards(payload.delivery_methods || []);
        await showAlert({
          icon: 'success',
          text: payload.message || 'Delivery method deleted successfully.',
          confirmButtonColor: '#3b2615',
        });
      } catch (error) {
        await showAlert({
          icon: 'error',
          title: 'Delete failed',
          text: error.message || 'Delete failed.',
          confirmButtonColor: '#3b2615',
        });
      }
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

  container?.addEventListener('submit', async (event) => {
    const form = event.target.closest('.delivery-method-edit');
    if (!form) return;
    event.preventDefault();

    try {
      const payload = await requestJson(new FormData(form));
      renderCards(payload.delivery_methods || []);
      await showAlert({
        icon: 'success',
        text: payload.message || 'Delivery method saved successfully.',
        confirmButtonColor: '#3b2615',
      });
    } catch (error) {
      await showAlert({
        icon: 'error',
        title: 'Save failed',
        text: error.message || 'Save failed.',
        confirmButtonColor: '#3b2615',
      });
    }
  });

  createForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
      const payload = await requestJson(new FormData(createForm));
      renderCards(payload.delivery_methods || []);
      createForm.reset();
      syncThresholdToggles(createCard || document);
      await showAlert({
        icon: 'success',
        text: payload.message || 'Delivery method saved successfully.',
        confirmButtonColor: '#3b2615',
      });
    } catch (error) {
      await showAlert({
        icon: 'error',
        title: 'Save failed',
        text: error.message || 'Save failed.',
        confirmButtonColor: '#3b2615',
      });
    }
  });

  createSubmit?.addEventListener('click', () => {
    createForm?.requestSubmit();
  });

  createReset?.addEventListener('click', () => {
    createForm?.reset();
    syncThresholdToggles(createCard || document);
  });

  syncThresholdToggles(document);

  if (window.adminDeliveryMethodsFlash) {
    showAlert({
      icon: window.adminDeliveryMethodsFlash.type === 'error' ? 'error' : 'success',
      text: window.adminDeliveryMethodsFlash.message || '',
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
