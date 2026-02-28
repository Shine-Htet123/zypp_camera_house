document.addEventListener('DOMContentLoaded', () => {
  const modals = {
    category: {
      overlay: document.getElementById('categoryModal'),
      titleId: 'categoryModalTitle',
      nameInput: 'categoryName',
      featuredSelect: 'categoryFeatured',
      imageInput: 'categoryImageInput',
      imagePreview: 'categoryPreview',
    },
    brand: {
      overlay: document.getElementById('brandModal'),
      titleId: 'brandModalTitle',
      nameInput: 'brandName',
      featuredSelect: 'brandFeatured',
      imageInput: 'brandImageInput',
      imagePreview: 'brandPreview',
    },
    subcategory: {
      overlay: document.getElementById('subcategoryModal'),
      titleId: 'subcategoryModalTitle',
      parentInput: 'subCategoryParent',
      nameInput: 'subCategoryName',
      featuredSelect: 'subCategoryFeatured',
    },
  };

  const openModal = (type, values = {}) => {
    const config = modals[type];
    if (!config?.overlay) return;
    const overlay = config.overlay;
    const title = overlay.querySelector(`#${config.titleId}`);
    const closeBtn = overlay.querySelector('.modal-close');
    const discardBtn = overlay.querySelector('.btn-footer.discard');
    const actions = overlay.querySelector('.modal-actions');
    overlay.classList.add('open');
    overlay.setAttribute('aria-hidden', 'false');
    if (title && values.title) title.textContent = values.title;
    if (config.nameInput) {
      const input = overlay.querySelector(`input[name="${config.nameInput}"]`);
      if (input) input.value = values.name || '';
    }
    if (config.parentInput) {
      const input = overlay.querySelector(`input[name="${config.parentInput}"]`);
      if (input) input.value = values.parent || '';
    }
    if (config.featuredSelect) {
      const select = overlay.querySelector(`select[name="${config.featuredSelect}"]`);
      if (select) select.value = values.featured || 'no';
    }
    if (closeBtn) closeBtn.style.display = 'inline-flex';
    if (discardBtn) {
      discardBtn.style.display = values.showDiscard === false ? 'none' : 'inline-flex';
    }
    actions?.classList.toggle('single', values.showDiscard === false);
    if (config.imagePreview) {
      const preview = overlay.querySelector(`#${config.imagePreview}`);
      if (preview) {
        preview.classList.remove('has-image');
        preview.innerHTML = '<i class="fa-regular fa-image"></i>';
      }
    }
    if (config.imageInput) {
      const input = overlay.querySelector(`#${config.imageInput}`);
      if (input) input.value = '';
    }
  };

  const closeModal = (overlay) => {
    if (!overlay) return;
    overlay.classList.remove('open');
    overlay.setAttribute('aria-hidden', 'true');
  };

  document.addEventListener('click', (event) => {
    const newBtn = event.target.closest('.btn-new');
    if (newBtn && newBtn.dataset.modal) {
      const type = newBtn.dataset.modal;
      if (type === 'category') {
        openModal('category', { title: 'Add Category', featured: 'no' });
      } else if (type === 'brand') {
        openModal('brand', { title: 'Add Brand', featured: 'yes', showDiscard: true });
      } else if (type === 'subcategory') {
        openModal('subcategory', { title: 'Add Sub-Category', featured: 'no' });
      }
      return;
    }

    const editBtn = event.target.closest('.icon-btn.edit');
    if (editBtn && editBtn.dataset.edit === 'category') {
      const row = editBtn.closest('.data-row');
      const name = row?.dataset.name || '';
      const featured = row?.querySelector('.status.yes') ? 'yes' : 'no';
      openModal('category', { title: 'Edit Category', name, featured });
      return;
    }
    if (editBtn && editBtn.dataset.edit === 'brand') {
      const row = editBtn.closest('.data-row');
      const name = row?.dataset.name || '';
      const featured = row?.querySelector('.status.yes') ? 'yes' : 'no';
      openModal('brand', { title: 'Edit Brand', name, featured, showDiscard: true });
      return;
    }
    if (editBtn && editBtn.dataset.edit === 'subcategory') {
      const row = editBtn.closest('.data-row');
      const name = row?.dataset.name || '';
      const parent = row?.querySelector('span:nth-child(3)')?.textContent?.trim() || '';
      const featured = row?.querySelector('.status.yes') ? 'yes' : 'no';
      openModal('subcategory', { title: 'Edit Sub-Category', name, parent, featured });
      return;
    }

    const uploadBtn = event.target.closest('.btn-upload');
    if (uploadBtn) {
      const inputId = uploadBtn.dataset.upload;
      const input = inputId ? document.getElementById(inputId) : null;
      input?.click();
      return;
    }

    const deleteBtn = event.target.closest('.action-buttons .icon-btn.delete');
    if (deleteBtn) {
      const row = deleteBtn.closest('.data-row');
      if (!row) return;
      const type = row.dataset.type || 'Item';
      const name = row.dataset.name || '';

      if (window.Swal) {
        Swal.fire({
          title: `Delete this ${type.toLowerCase()}?`,
          text: name ? `“${name}” will be removed permanently.` : 'This action cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
        }).then((result) => {
          if (result.isConfirmed) {
            row.remove();
            Swal.fire('Deleted', `${type} has been removed.`, 'success');
          }
        });
      } else if (window.confirm('Delete this item?')) {
        row.remove();
      }
      return;
    }
  });

  Object.values(modals).forEach((config) => {
    const overlay = config.overlay;
    if (!overlay) return;
    const closeBtn = overlay.querySelector('.modal-close');
    const discardBtn = overlay.querySelector('.btn-footer.discard');
    closeBtn?.addEventListener('click', () => closeModal(overlay));
    discardBtn?.addEventListener('click', () => closeModal(overlay));
    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) closeModal(overlay);
    });

    if (config.imageInput && config.imagePreview) {
      const input = overlay.querySelector(`#${config.imageInput}`);
      const preview = overlay.querySelector(`#${config.imagePreview}`);
      input?.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file || !preview) return;
        const url = URL.createObjectURL(file);
        preview.innerHTML = '';
        preview.classList.add('has-image');
        const img = document.createElement('img');
        img.src = url;
        img.alt = 'Preview';
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'contain';
        preview.appendChild(img);
      });
    }
  });
});
