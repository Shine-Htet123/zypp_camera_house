document.addEventListener('DOMContentLoaded', () => {
  const container = document.querySelector('.membership-left');
  const template = document.getElementById('tierEditTemplate');
  let activeEditCard = null;

  const formatNumber = (value) => {
    if (!value) return '';
    const numeric = String(value).replace(/[^\d]/g, '');
    if (!numeric) return '';
    return Number(numeric).toLocaleString('en-US');
  };

  const buildRange = (min, max) => {
    const minText = formatNumber(min);
    const maxText = formatNumber(max);
    if (minText && maxText) {
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
    if (!rangeEl) return;
    const computed = buildRange(card.dataset.tierMin, card.dataset.tierMax);
    if (computed) {
      rangeEl.textContent = computed;
    }
  });

  const syncUnit = (select) => {
    if (!select) return;
    const targetId = select.dataset.unitTarget;
    if (!targetId) return;
    const unitEl = document.getElementById(targetId);
    if (!unitEl) return;
    unitEl.textContent = select.value === 'Fixed' ? 'MMK' : '%';
  };

  document.querySelectorAll('[data-unit-target]').forEach((select) => {
    syncUnit(select);
    select.addEventListener('change', () => syncUnit(select));
  });

  const restoreCard = (card, updatedData) => {
    if (!card) return;
    const original = card.dataset.originalMarkup;
    if (!original) return;
    card.innerHTML = original;
    card.classList.remove('editing');
    card.classList.remove('exiting');
    delete card.dataset.originalMarkup;

    if (updatedData) {
      card.dataset.tierTitle = updatedData.title;
      card.dataset.tierMin = updatedData.min;
      card.dataset.tierMax = updatedData.max;
      card.dataset.tierRefType = updatedData.refType;
      card.dataset.tierRefValue = updatedData.refValue;
    }
  };

  const openEdit = (card) => {
    if (!card || !template) return;
    if (activeEditCard && activeEditCard !== card) {
      restoreCard(activeEditCard);
      activeEditCard = null;
    }

    if (card.classList.contains('editing')) return;

    const title = card.dataset.tierTitle || '';
    const min = card.dataset.tierMin || '';
    const max = card.dataset.tierMax || '';
    const refType = card.dataset.tierRefType || 'Percentage';
    const refValue = card.dataset.tierRefValue || '';

    card.dataset.originalMarkup = card.innerHTML;
    card.classList.add('editing');

    const clone = template.content.cloneNode(true);
    const nameInput = clone.querySelector('.edit-name');
    const minInput = clone.querySelector('.edit-min');
    const maxInput = clone.querySelector('.edit-max');
    const refTypeSelect = clone.querySelector('.edit-ref-type');
    const refValueInput = clone.querySelector('.edit-ref-value');

    if (nameInput) nameInput.value = title;
    if (minInput) minInput.value = formatNumber(min);
    if (maxInput) maxInput.value = max ? formatNumber(max) : '';
    if (refTypeSelect) refTypeSelect.value = refType;
    if (refValueInput) refValueInput.value = refValue;

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
    const confirmBtn = event.target.closest('.btn-confirm');

    if (editBtn) {
      const card = editBtn.closest('.tier-card');
      openEdit(card);
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
      });
      return;
    }

    if (cancelBtn) {
      const card = cancelBtn.closest('.tier-card');
      if (card) {
        card.classList.add('exiting');
        window.setTimeout(() => {
          restoreCard(card);
          activeEditCard = null;
        }, 200);
      }
      return;
    }

    if (confirmBtn) {
      const card = confirmBtn.closest('.tier-card');
      const nameInput = card?.querySelector('.edit-name');
      const minInput = card?.querySelector('.edit-min');
      const maxInput = card?.querySelector('.edit-max');
      const refTypeSelect = card?.querySelector('.edit-ref-type');
      const refValueInput = card?.querySelector('.edit-ref-value');

      const updated = {
        title: nameInput?.value.trim() || '',
        min: minInput?.value.trim() || '',
        max: maxInput?.value.trim() || '',
        refType: refTypeSelect?.value || 'Percentage',
        refValue: refValueInput?.value.trim() || '',
      };

      if (card) {
        card.classList.add('exiting');
        window.setTimeout(() => {
          restoreCard(card, updated);
          const titleEl = card.querySelector('h3');
          const rangeEl = card.querySelector('p');
          if (titleEl) titleEl.textContent = updated.title;
          if (rangeEl) rangeEl.textContent = buildRange(updated.min, updated.max);
          activeEditCard = null;
          document.querySelectorAll('[data-unit-target]').forEach((select) => syncUnit(select));
        }, 200);
      }
    }
  });
});
