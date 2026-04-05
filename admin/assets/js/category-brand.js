document.addEventListener('DOMContentLoaded', () => {
  const pageUrl = window.location.href;
  const categoryTableBody = document.querySelector('.cols-category tbody');
  const subCategoryTableBody = document.querySelector('.cols-subcategory tbody');
  const brandTableBody = document.querySelector('.cols-brand tbody');
  const deleteForm = document.getElementById('categoryBrandDeleteForm');
  const formState = new WeakMap();

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

  const showAlert = async (options) => {
    if (window.Swal) {
      return Swal.fire(options);
    }

    const message = options?.text || options?.title || '';
    if (message) alert(message);
    return null;
  };

  const escapeHtml = (value) => {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
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

  const closeModal = (overlay) => {
    if (!overlay) return;
    overlay.classList.remove('open');
    overlay.setAttribute('aria-hidden', 'true');
  };

  const captureFormState = (form) => {
    if (!form) return;
    const fields = Array.from(form.querySelectorAll('input[name]:not([type="file"]), select[name], textarea[name]'));
    form.dataset.fileDirty = '0';
    formState.set(form, fields.map((field) => ({
      field,
      value: field.type === 'checkbox' ? field.checked : field.value,
    })));
  };

  const isFormDirty = (form) => {
    const state = formState.get(form);
    const hasFieldChanges = state ? state.some((item) => {
      if (!item.field) return false;
      const currentValue = item.field.type === 'checkbox' ? item.field.checked : item.field.value;
      return currentValue !== item.value;
    }) : false;

    return hasFieldChanges || form?.dataset.fileDirty === '1';
  };

  const syncDirtyState = (form) => {
    if (!form) return;
    form.classList.toggle('is-dirty', isFormDirty(form));
  };

  const resetModal = (type) => {
    const config = modals[type];
    const overlay = config?.overlay;
    if (!overlay) return;

    const form = overlay.querySelector('form');
    form?.reset();
    form?.classList.remove('is-dirty');
    const idInput = form?.querySelector('input[name="entity_id"]');
    if (idInput) idInput.value = '0';

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

    captureFormState(form);
  };

  const openModal = (type, values = {}) => {
    const config = modals[type];
    if (!config?.overlay) return;

    const overlay = config.overlay;
    const title = overlay.querySelector(`#${config.titleId}`);
    const form = overlay.querySelector('form');

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

    if (config.imagePreview) {
      const preview = overlay.querySelector(`#${config.imagePreview}`);
      if (preview) {
        if (values.image) {
          preview.innerHTML = `<img src="${escapeHtml(values.image)}" alt="Preview" style="width:100%;height:100%;object-fit:contain;">`;
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

    requestAnimationFrame(() => {
      captureFormState(form);
      syncDirtyState(form);
    });
  };

  const renderStatus = (featured) => {
    const enabled = Number(featured) === 1 || String(featured) === 'yes';
    return `
      <td class="status ${enabled ? 'yes' : 'no'}">
        <i class="fa-solid ${enabled ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>
      </td>
    `;
  };

  const renderImageCell = (url, name) => {
    if (url) {
      return `
        <td class="image-cell">
          <img class="table-image" src="${escapeHtml(url)}" alt="${escapeHtml(name)}">
        </td>
      `;
    }

    return `
      <td class="image-cell">
        <div class="image-placeholder"><i class="fa-regular fa-image"></i></div>
      </td>
    `;
  };

  const renderCategories = (categories) => {
    if (!categoryTableBody) return;
    categoryTableBody.innerHTML = (categories || []).map((row) => `
      <tr
        class="data-row cols-category"
        data-type="Category"
        data-id="${Number(row.category_id || 0)}"
        data-name="${escapeHtml(row.name || '')}"
        data-featured="${Number(row.featured || 0) ? 'yes' : 'no'}"
      >
        <td>${Number(row.category_id || 0)}</td>
        <td>${escapeHtml(row.name || '')}</td>
        ${renderImageCell(row.image_url || '', row.name || '')}
        ${renderStatus(row.featured)}
        <td class="action-buttons">
          <button type="button" class="icon-btn edit" aria-label="Edit category" data-edit="category">
            <i class="fa-regular fa-pen-to-square"></i>
          </button>
          <button type="button" class="icon-btn delete" aria-label="Delete category" data-delete-type="category">
            <i class="fa-regular fa-trash-can"></i>
          </button>
        </td>
      </tr>
    `).join('');
  };

  const renderSubCategories = (subCategories) => {
    if (!subCategoryTableBody) return;
    subCategoryTableBody.innerHTML = (subCategories || []).map((row) => `
      <tr
        class="data-row cols-subcategory"
        data-type="Sub-Category"
        data-id="${Number(row.sub_category_id || 0)}"
        data-name="${escapeHtml(row.name || '')}"
        data-parent-id="${Number(row.category_id || 0)}"
        data-parent-name="${escapeHtml(row.category_name || '')}"
        data-featured="${Number(row.featured || 0) ? 'yes' : 'no'}"
      >
        <td>${Number(row.sub_category_id || 0)}</td>
        <td>${escapeHtml(row.name || '')}</td>
        <td>${escapeHtml(row.category_name || '')}</td>
        ${renderStatus(row.featured)}
        <td class="action-buttons">
          <button type="button" class="icon-btn edit" aria-label="Edit sub-category" data-edit="subcategory">
            <i class="fa-regular fa-pen-to-square"></i>
          </button>
          <button type="button" class="icon-btn delete" aria-label="Delete sub-category" data-delete-type="subcategory">
            <i class="fa-regular fa-trash-can"></i>
          </button>
        </td>
      </tr>
    `).join('');
  };

  const renderBrands = (brands) => {
    if (!brandTableBody) return;
    brandTableBody.innerHTML = (brands || []).map((row) => `
      <tr
        class="data-row cols-brand"
        data-type="Brand"
        data-id="${Number(row.brand_id || 0)}"
        data-name="${escapeHtml(row.name || '')}"
        data-featured="${Number(row.featured || 0) ? 'yes' : 'no'}"
      >
        <td>${Number(row.brand_id || 0)}</td>
        <td>${escapeHtml(row.name || '')}</td>
        ${renderImageCell(row.image_url || '', row.name || '')}
        ${renderStatus(row.featured)}
        <td class="action-buttons">
          <button type="button" class="icon-btn edit" aria-label="Edit brand" data-edit="brand">
            <i class="fa-regular fa-pen-to-square"></i>
          </button>
          <button type="button" class="icon-btn delete" aria-label="Delete brand" data-delete-type="brand">
            <i class="fa-regular fa-trash-can"></i>
          </button>
        </td>
      </tr>
    `).join('');
  };

  const syncSubCategoryOptions = (categories) => {
    const select = modals.subcategory.overlay?.querySelector('[name="subCategoryParentId"]');
    if (!select) return;

    const options = ['<option value="">Select category</option>'].concat(
      (categories || []).map((category) => (
        `<option value="${Number(category.category_id || 0)}">${escapeHtml(category.name || '')}</option>`
      ))
    );
    select.innerHTML = options.join('');
  };

  const applyPayload = (payload) => {
    renderCategories(payload.categories || []);
    renderSubCategories(payload.sub_categories || []);
    renderBrands(payload.brands || []);
    syncSubCategoryOptions(payload.categories || []);
  };

  Object.entries(modals).forEach(([type, config]) => {
    const overlay = config.overlay;
    if (!overlay) return;

    const form = overlay.querySelector('form');
    const closeBtn = overlay.querySelector('.modal-close');
    const discardBtn = overlay.querySelector('.btn-footer.discard');

    closeBtn?.addEventListener('click', () => {
      resetModal(type);
      closeModal(overlay);
    });
    discardBtn?.addEventListener('click', () => {
      resetModal(type);
      closeModal(overlay);
    });

    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) {
        resetModal(type);
        closeModal(overlay);
      }
    });

    if (config.imageInput && config.imagePreview) {
      const input = overlay.querySelector(`#${config.imageInput}`);
      const preview = overlay.querySelector(`#${config.imagePreview}`);
      input?.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!preview) return;
        if (!file) {
          form.dataset.fileDirty = '0';
          syncDirtyState(form);
          return;
        }
        preview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Preview" style="width:100%;height:100%;object-fit:contain;">`;
        preview.classList.add('has-image');
        form.dataset.fileDirty = '1';
        syncDirtyState(form);
      });
    }

    form?.querySelectorAll('input[name]:not([type="file"]), select[name], textarea[name]').forEach((field) => {
      const sync = () => syncDirtyState(form);
      field.addEventListener('input', sync);
      field.addEventListener('change', sync);
    });

    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      try {
        const payload = await requestJson(new FormData(form));
        applyPayload(payload);
        resetModal(type);
        closeModal(overlay);
        await showAlert({
          icon: 'success',
          text: payload.message || 'Saved successfully.',
          confirmButtonColor: '#3b2615',
        });
      } catch (error) {
        await showAlert({
          icon: 'error',
          title: 'Save failed',
          text: error.message || 'Request failed.',
          confirmButtonColor: '#3b2615',
        });
      }
    });
  });

  document.addEventListener('click', async (event) => {
    const newBtn = event.target.closest('.btn-new');
    if (newBtn?.dataset.modal) {
      const type = newBtn.dataset.modal;
      resetModal(type);
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
      if (!type || !id) return;

      const confirmation = await showAlert({
        title: `Delete this ${type.replace('subcategory', 'sub-category')}?`,
        text: `"${name}" will be removed permanently.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
      });

      if (!confirmation?.isConfirmed) {
        return;
      }

      const formData = new FormData(deleteForm);
      formData.set('entity_action', `delete_${type}`);
      formData.set('entity_id', id);

      try {
        const payload = await requestJson(formData);
        applyPayload(payload);
        await showAlert({
          icon: 'success',
          text: payload.message || 'Deleted successfully.',
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

  if (window.categoryBrandFlash) {
    showAlert({
      icon: window.categoryBrandFlash.type === 'error' ? 'error' : 'success',
      text: window.categoryBrandFlash.message || '',
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
