document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('uspModal');
  const modalTitle = modal?.querySelector('#uspModalTitle');
  const closeBtn = modal?.querySelector('.modal-close');
  const discardBtn = modal?.querySelector('.btn-footer.discard');
  const uploadBtn = modal?.querySelector('.btn-upload');
  const uploadInput = modal?.querySelector('#uspIconInput');
  const iconPreview = modal?.querySelector('#uspIconPreview');
  const titleInput = modal?.querySelector('input[name="uspTitle"]');
  const descInput = modal?.querySelector('textarea[name="uspDescription"]');

  const openModal = (mode, row) => {
    if (!modal) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    if (modalTitle) modalTitle.textContent = mode === 'edit' ? 'Edit' : 'Add';
    if (titleInput) titleInput.value = row?.querySelector('.usp-title')?.textContent?.trim() || '';
    if (descInput) descInput.value = row?.querySelector('.usp-desc')?.textContent?.trim() || '';
    if (iconPreview) {
      iconPreview.innerHTML = '<span class="icon-placeholder"></span>';
    }
    if (uploadInput) uploadInput.value = '';
  };

  const closeModal = () => {
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  };

  document.addEventListener('click', (event) => {
    const newBtn = event.target.closest('.btn-new');
    if (newBtn) {
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
      if (!row) return;
      if (window.Swal) {
        Swal.fire({
          title: 'Delete this point?',
          text: 'This action cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
        }).then((result) => {
          if (result.isConfirmed) {
            row.remove();
            Swal.fire('Deleted', 'The point has been removed.', 'success');
          }
        });
      } else if (window.confirm('Delete this item?')) {
        row.remove();
      }
      return;
    }
  });

  uploadBtn?.addEventListener('click', () => uploadInput?.click());
  uploadInput?.addEventListener('change', () => {
    const file = uploadInput.files && uploadInput.files[0];
    if (!file || !iconPreview) return;
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.alt = 'Icon preview';
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'contain';
    iconPreview.innerHTML = '';
    iconPreview.appendChild(img);
  });

  closeBtn?.addEventListener('click', closeModal);
  discardBtn?.addEventListener('click', closeModal);
  modal?.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
  });
});
