document.addEventListener('DOMContentLoaded', () => {
  const modals = {
    category: {
      overlay: document.getElementById('categoryModal'),
      titleId: 'categoryModalTitle',
      entityAction: 'save_category',
      nameInput: 'categoryName',
      featuredSelect: 'categoryFeatured',
      imageInput: 'categoryImageInput',
      imagePreview: 'categoryPreview',
    },
    brand: {
      overlay: document.getElementById('brandModal'),
      titleId: 'brandModalTitle',
      entityAction: 'save_brand',
      nameInput: 'brandName',
      featuredSelect: 'brandFeatured',
      imageInput: 'brandImageInput',
      imagePreview: 'brandPreview',
    },
    subcategory: {
      overlay: document.getElementById('subcategoryModal'),
      titleId: 'subcategoryModalTitle',
      entityAction: 'save_subcategory',
      parentInput: 'subCategoryParentId',
      nameInput: 'subCategoryName',
      featuredSelect: 'subCategoryFeatured',
    },
  };

  const deleteForm = document.getElementById('categoryBrandDeleteForm');

  const openModal = (type, values = {}) => {
    const config = modals[type];
    if (!config?.overlay) return;

    const overlay = config.overlay;
    const title = overlay.querySelector(`#${config.titleId}`);
    const form = overlay.querySelector('form');
    const closeBtn = overlay.querySelector('.modal-close');
    const discardBtn = overlay.querySelector('.btn-footer.discard');

    overlay.classList.add('open');
    overlay.setAttribute('aria-hidden', 'false');

    if (title) title.textContent = values.title || title.textContent;
    if (form) {
      const actionInput = form.querySelector('input[name="entity_action"]');
      const idInput = form.querySelector('input[name="entity_id"]');
      if (actionInput) actionInput.value = config.entityAction;
      if (idInput) idInput.value = values.id || '0';
    }

    if (config.nameInput) {
      const input = overlay.querySelector(`[name="${config.nameInput}"]`);
      if (input) input.value = values.name || '';
    }

    if (config.parentInput) {
      const input = overlay.querySelector(`[name="${config.parentInput}"]`);
      if (input) input.value = values.parentId || '';
    }

    if (config.featuredSelect) {
      const select = overlay.querySelector(`[name="${config.featuredSelect}"]`);
      if (select) select.value = values.featured || 'no';
    }

    if (closeBtn) closeBtn.style.display = 'inline-flex';
    if (discardBtn) discardBtn.style.display = 'inline-flex';

    if (config.imagePreview) {
      const preview = overlay.querySelector(`#${config.imagePreview}`);
      if (preview) {
        if (values.image) {
          preview.innerHTML = `<img src="${values.image}" alt="Preview" style="width:100%;height:100%;object-fit:contain;">`;
          preview.classList.add('has-image');
        } else {
          preview.classList.remove('has-image');
          preview.innerHTML = '<i class="fa-regular fa-image"></i>';
        }
      }
    }

    if (config.imageInput) {
      const fileInput = overlay.querySelector(`#${config.imageInput}`);
      if (fileInput) fileInput.value = '';
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
        openModal('brand', { title: 'Add Brand', featured: 'no' });
      } else if (type === 'subcategory') {
        openModal('subcategory', { title: 'Add Sub-Category', featured: 'no' });
      }
      return;
    }

    const editBtn = event.target.closest('.icon-btn.edit');
    if (editBtn) {
      const row = editBtn.closest('.data-row');
      if (!row) return;

      const values = {
        id: row.dataset.id || '0',
        name: row.dataset.name || '',
        featured: row.dataset.featured || 'no',
        parentId: row.dataset.parentId || '',
        image: row.querySelector('.table-image')?.getAttribute('src') || '',
      };

      if (editBtn.dataset.edit === 'category') {
        openModal('category', { ...values, title: 'Edit Category' });
      } else if (editBtn.dataset.edit === 'brand') {
        openModal('brand', { ...values, title: 'Edit Brand' });
      } else if (editBtn.dataset.edit === 'subcategory') {
        openModal('subcategory', { ...values, title: 'Edit Sub-Category' });
      }
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
    if (deleteBtn && deleteForm) {
      const row = deleteBtn.closest('.data-row');
      if (!row) return;

      const type = deleteBtn.dataset.deleteType || '';
      const id = row.dataset.id || '';
      const name = row.dataset.name || 'this item';
      const actionInput = deleteForm.querySelector('input[name="entity_action"]');
      const idInput = deleteForm.querySelector('input[name="entity_id"]');

      if (!actionInput || !idInput || !type || !id) return;

      const submitDelete = () => {
        actionInput.value = `delete_${type}`;
        idInput.value = id;
        deleteForm.submit();
      };

      if (window.Swal) {
        Swal.fire({
          title: `Delete this ${type.replace('subcategory', 'sub-category')}?`,
          text: `"${name}" will be removed permanently.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
        }).then((result) => {
          if (result.isConfirmed) {
            submitDelete();
          }
        });
      } else {
        submitDelete();
      }
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
        preview.innerHTML = `<img src="${url}" alt="Preview" style="width:100%;height:100%;object-fit:contain;">`;
        preview.classList.add('has-image');
      });
    }
  });

  if (window.categoryBrandFlash && window.Swal) {
    Swal.fire({
      icon: window.categoryBrandFlash.type === 'error' ? 'error' : 'success',
      text: window.categoryBrandFlash.message || '',
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
