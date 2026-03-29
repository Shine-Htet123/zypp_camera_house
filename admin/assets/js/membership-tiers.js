document.addEventListener('DOMContentLoaded', () => {
  const pageUrl = window.location.href;
  const container = document.querySelector('.membership-left');
  const cardsWrap = document.querySelector('.tier-cards');
  const template = document.getElementById('tierEditTemplate');
  const createCard = document.querySelector('.membership-right .tier-form-card');
  const createForm = createCard?.querySelector('form.tier-form');
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

  const formatNumber = (value) => {
    if (!value && value !== 0) return '';
    const numeric = String(value).replace(/[^\d.]/g, '');
    if (!numeric) return '';
    return Number(numeric).toLocaleString('en-US');
  };

  const buildRange = (min, max) => {
    const minText = formatNumber(min);
    const maxText = formatNumber(max);
    if (minText && maxText) {
      if (Number(String(min).replace(/[^\d.]/g, '')) <= 0) {
        return `<${maxText} MMK`;
      }
      return `${minText} MMK - ${maxText} MMK`;
    }
    if (!minText && maxText) {
      return `<${maxText} MMK`;
    }
    if (minText && !maxText) {
      return `>${minText} MMK`;
    }
    return '';
  };

  const syncUnit = (select) => {
    if (!select) return;
    const targetId = select.dataset.unitTarget;
    if (!targetId) return;
    const unitEl = document.getElementById(targetId);
    if (!unitEl) return;
    unitEl.textContent = String(select.value).toLowerCase() === 'fixed' ? 'MMK' : '%';
  };

  const syncUnits = (root = document) => {
    root.querySelectorAll('[data-unit-target]').forEach((select) => {
      syncUnit(select);
      select.addEventListener('change', () => syncUnit(select));
    });
  };

  const renderCards = (tiers) => {
    if (!cardsWrap) return;
    activeEditCard = null;
    cardsWrap.innerHTML = (tiers || []).map((tier) => `
      <article
        class="tier-card"
        data-tier-id="${Number(tier.id || 0)}"
        data-tier-title="${escapeHtml(tier.tier_name || '')}"
        data-tier-range="${escapeHtml(tier.range || '')}"
        data-tier-min="${escapeHtml(tier.min_spent ?? '')}"
        data-tier-max="${escapeHtml(tier.max_spent ?? '')}"
        data-tier-ref-type="${escapeHtml(tier.referral_discount_type_label || '')}"
        data-tier-ref-value="${escapeHtml(tier.referral_discount_value ?? '')}"
      >
        <h3>${escapeHtml(tier.tier_name || '')}</h3>
        <p>${escapeHtml(buildRange(tier.min_spent, tier.max_spent))}</p>
        <div class="tier-actions">
          <button type="button" class="icon-btn edit-tier" aria-label="Edit tier">
            <i class="fa-regular fa-pen-to-square"></i>
          </button>
          <button type="button" class="icon-btn delete delete-tier" aria-label="Delete tier">
            <i class="fa-regular fa-trash-can"></i>
          </button>
        </div>
        <form class="tier-delete-form" method="post">
          <input type="hidden" name="membership_action" value="delete">
          <input type="hidden" name="tier_id" value="${Number(tier.id || 0)}">
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
    clone.querySelector('.edit-id').value = card.dataset.tierId || '0';
    clone.querySelector('.edit-name').value = card.dataset.tierTitle || '';
    clone.querySelector('.edit-min').value = formatNumber(card.dataset.tierMin || '');
    clone.querySelector('.edit-max').value = card.dataset.tierMax ? formatNumber(card.dataset.tierMax) : '';
    clone.querySelector('.edit-ref-type').value = String(card.dataset.tierRefType || '').toLowerCase();
    clone.querySelector('.edit-ref-value').value = card.dataset.tierRefValue || '';

    card.dataset.originalMarkup = card.innerHTML;
    card.classList.add('editing');
    card.innerHTML = '';
    card.appendChild(clone);
    activeEditCard = card;
    syncUnits(card);
  };

  container?.addEventListener('click', async (event) => {
    const editBtn = event.target.closest('.edit-tier');
    const deleteBtn = event.target.closest('.delete-tier');
    const cancelBtn = event.target.closest('.btn-cancel');

    if (editBtn) {
      openEdit(editBtn.closest('.tier-card'));
      return;
    }

    if (deleteBtn) {
      const card = deleteBtn.closest('.tier-card');
      const form = card?.querySelector('.tier-delete-form');
      if (!form) return;

      const confirmation = await showAlert({
        title: 'Delete member tier?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
      });

      if (!confirmation?.isConfirmed) return;

      try {
        const payload = await requestJson(new FormData(form));
        renderCards(payload.tiers || []);
        await showAlert({
          icon: 'success',
          text: payload.message || 'Membership tier deleted successfully.',
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
      const card = cancelBtn.closest('.tier-card');
      if (card) {
        card.classList.add('exiting');
        window.setTimeout(() => restoreCard(card), 200);
      }
    }
  });

  container?.addEventListener('submit', async (event) => {
    const form = event.target.closest('.tier-edit');
    if (!form) return;
    event.preventDefault();

    try {
      const payload = await requestJson(new FormData(form));
      renderCards(payload.tiers || []);
      await showAlert({
        icon: 'success',
        text: payload.message || 'Membership tier saved successfully.',
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
      renderCards(payload.tiers || []);
      createForm.reset();
      syncUnits(createCard || document);
      await showAlert({
        icon: 'success',
        text: payload.message || 'Membership tier saved successfully.',
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
    syncUnits(createCard || document);
  });

  syncUnits(document);

  if (window.adminMembershipFlash) {
    showAlert({
      icon: window.adminMembershipFlash.type === 'error' ? 'error' : 'success',
      text: window.adminMembershipFlash.message || '',
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
