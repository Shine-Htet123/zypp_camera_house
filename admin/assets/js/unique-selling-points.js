document.addEventListener('DOMContentLoaded', () => {
  const pageUrl = window.location.href;
  const tableBody = document.querySelector('.usp-table-grid tbody');
  const modal = document.getElementById('uspModal');
  const modalForm = modal?.querySelector('.modal-form');
  const modalTitle = modal?.querySelector('#uspModalTitle');
  const closeBtn = modal?.querySelector('.modal-close');
  const discardBtn = modal?.querySelector('.btn-footer.discard');
  const uploadBtn = modal?.querySelector('.btn-upload');
  const uploadInput = modal?.querySelector('#uspIconInput');
  const iconPreview = modal?.querySelector('#uspIconPreview');
  const titleInput = modal?.querySelector('input[name="uspTitle"]');
  const descInput = modal?.querySelector('textarea[name="uspDescription"]');
  const entityIdInput = modal?.querySelector('input[name="entity_id"]');

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

  const renderRows = (points) => {
    if (!tableBody) return;
    tableBody.innerHTML = (points || []).map((point) => `
      <tr
        class="usp-row"
        data-id="${Number(point.item_id || 0)}"
        data-title="${escapeHtml(point.title || '')}"
        data-description="${escapeHtml(point.subtitle || '')}"
        data-icon="${escapeHtml(point.icon_url || '')}"
      >
        <td>${Number(point.item_id || 0)}</td>
        <td class="icon-cell">
          ${point.icon_url
            ? `<img class="usp-icon-image" src="${escapeHtml(point.icon_url)}" alt="${escapeHtml(point.title || '')}">`
            : '<span class="icon-placeholder"></span>'}
        </td>
        <td class="usp-title">${escapeHtml(point.title || '')}</td>
        <td class="usp-desc">${escapeHtml(point.subtitle || '')}</td>
        <td class="usp-actions">
          <button type="button" class="icon-btn edit" aria-label="Edit point">
            <i class="fa-regular fa-pen-to-square"></i>
          </button>
          <button type="button" class="icon-btn delete" aria-label="Delete point">
            <i class="fa-regular fa-trash-can"></i>
          </button>
        </td>
        <td hidden>
          <form method="post" class="usp-delete-form">
            <input type="hidden" name="usp_action" value="delete">
            <input type="hidden" name="entity_id" value="${Number(point.item_id || 0)}">
          </form>
        </td>
      </tr>
    `).join('');
  };

  const resetModal = () => {
    modalForm?.reset();
    if (entityIdInput) entityIdInput.value = '0';
    if (iconPreview) {
      iconPreview.innerHTML = '<span class="icon-placeholder"></span>';
    }
    if (uploadInput) uploadInput.value = '';
  };

  const openModal = (mode, row) => {
    if (!modal) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    if (modalTitle) modalTitle.textContent = mode === 'edit' ? 'Edit' : 'Add';
    if (entityIdInput) entityIdInput.value = row?.dataset.id || '0';
    if (titleInput) titleInput.value = row?.dataset.title || '';
    if (descInput) descInput.value = row?.dataset.description || '';
    if (iconPreview) {
      const iconUrl = row?.dataset.icon || '';
      iconPreview.innerHTML = iconUrl
        ? `<img src="${escapeHtml(iconUrl)}" alt="Icon preview" style="width:100%;height:100%;object-fit:contain;">`
        : '<span class="icon-placeholder"></span>';
    }
    if (uploadInput) uploadInput.value = '';
  };

  const closeModal = () => {
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  };

  uploadBtn?.addEventListener('click', () => uploadInput?.click());
  uploadInput?.addEventListener('change', () => {
    const file = uploadInput.files?.[0];
    if (!file || !iconPreview) return;
    iconPreview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Icon preview" style="width:100%;height:100%;object-fit:contain;">`;
  });

  closeBtn?.addEventListener('click', () => {
    resetModal();
    closeModal();
  });
  discardBtn?.addEventListener('click', () => {
    resetModal();
    closeModal();
  });
  modal?.addEventListener('click', (event) => {
    if (event.target === modal) {
      resetModal();
      closeModal();
    }
  });

  modalForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
      const payload = await requestJson(new FormData(modalForm));
      renderRows(payload.points || []);
      resetModal();
      closeModal();
      await showAlert({
        icon: 'success',
        text: payload.message || 'Unique selling point saved successfully.',
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

  document.addEventListener('click', async (event) => {
    const newBtn = event.target.closest('.btn-new');
    if (newBtn) {
      resetModal();
      openModal('add');
      return;
    }

    const editBtn = event.target.closest('.usp-actions .icon-btn.edit');
    if (editBtn) {
      const row = editBtn.closest('.usp-row');
      openModal('edit', row);
      return;
    }

    const deleteBtn = event.target.closest('.usp-actions .icon-btn.delete');
    if (deleteBtn) {
      const row = deleteBtn.closest('.usp-row');
      const form = row?.querySelector('.usp-delete-form');
      if (!form) return;

      const confirmation = await showAlert({
        title: 'Delete this point?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
      });

      if (!confirmation?.isConfirmed) return;

      try {
        const payload = await requestJson(new FormData(form));
        renderRows(payload.points || []);
        await showAlert({
          icon: 'success',
          text: payload.message || 'Unique selling point deleted successfully.',
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
    }
  });

  if (window.adminUspFlash) {
    showAlert({
      icon: window.adminUspFlash.type === 'error' ? 'error' : 'success',
      text: window.adminUspFlash.message || '',
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
