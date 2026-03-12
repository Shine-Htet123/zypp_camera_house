document.addEventListener('DOMContentLoaded', () => {
  const container = document.querySelector('.membership-left');
  const template = document.getElementById('tierEditTemplate');
  const createCard = document.querySelector('.membership-right .tier-form-card');
  const createForm = createCard?.querySelector('form.tier-form');
  const createSubmit = createCard?.querySelector('[data-create-submit]');
  const createReset = createCard?.querySelector('.btn-reset');
  let activeEditCard = null;

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

  document.querySelectorAll('.tier-card').forEach((card) => {
    const rangeEl = card.querySelector('p');
    const computed = buildRange(card.dataset.tierMin, card.dataset.tierMax);
    if (rangeEl && computed) {
      rangeEl.textContent = computed;
    }
  });

  const syncUnit = (select) => {
    if (!select) return;
    const targetId = select.dataset.unitTarget;
    if (!targetId) return;
    const unitEl = document.getElementById(targetId);
    if (!unitEl) return;
    unitEl.textContent = String(select.value).toLowerCase() === 'fixed' ? 'MMK' : '%';
  };

  document.querySelectorAll('[data-unit-target]').forEach((select) => {
    syncUnit(select);
    select.addEventListener('change', () => syncUnit(select));
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

    const editSelect = card.querySelector('[data-unit-target]');
    if (editSelect) {
      syncUnit(editSelect);
      editSelect.addEventListener('change', () => syncUnit(editSelect));
    }
  };

  container?.addEventListener('click', (event) => {
    const editBtn = event.target.closest('.edit-tier');
    const deleteBtn = event.target.closest('.delete-tier');
    const cancelBtn = event.target.closest('.btn-cancel');

    if (editBtn) {
      openEdit(editBtn.closest('.tier-card'));
      return;
    }

    if (deleteBtn) {
      if (typeof Swal === 'undefined') return;
      Swal.fire({
        title: 'Delete member tier?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
      }).then((result) => {
        if (result.isConfirmed) {
          deleteBtn.closest('.tier-card')?.querySelector('.tier-delete-form')?.submit();
        }
      });
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

  createSubmit?.addEventListener('click', () => {
    createForm?.requestSubmit();
  });

  createReset?.addEventListener('click', () => {
    createForm?.reset();
    document.querySelectorAll('[data-unit-target]').forEach((select) => syncUnit(select));
  });

  if (window.adminMembershipFlash && typeof Swal !== 'undefined') {
    Swal.fire({
      icon: window.adminMembershipFlash.type === 'error' ? 'error' : 'success',
      text: window.adminMembershipFlash.message || '',
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
