document.addEventListener('DOMContentLoaded', () => {
  const productForm = document.querySelector('.product-form');
  const formFooter = document.querySelector('.form-footer');
  const specInput = document.getElementById('specInput');
  const specAdd = document.getElementById('specAdd');
  const specList = document.getElementById('specList');
  const colorInput = document.getElementById('colorInput');
  const colorAdd = document.getElementById('colorAdd');
  const colorList = document.getElementById('colorList');
  const sizeInput = document.getElementById('sizeInput');
  const sizeAdd = document.getElementById('sizeAdd');
  const sizeList = document.getElementById('sizeList');
  const previews = document.getElementById('imagePreviews');
  const uploadTile = document.getElementById('addUploadTile');
  const uploadInput = document.getElementById('addImagesInput');
  const discardBtn = document.querySelector('.btn-footer.discard');
  const primaryImageIndex = document.getElementById('primaryImageIndex');
  const categorySelect = productForm?.querySelector('select[name="category"]');
  const subCategorySelect = productForm?.querySelector('select[name="subcategory"]');
  const maxImages = 5;
  let imageFiles = new DataTransfer();
  let selectedImageIndex = 0;
  let formDirty = false;

  const showWarning = (message) => {
    if (window.Swal) {
      Swal.fire({
        icon: 'warning',
        text: message,
        confirmButtonColor: '#3b2615',
      });
      return;
    }
    alert(message);
  };

  const validateForm = () => {
    const name = productForm?.querySelector('input[name="name"]')?.value.trim() || '';
    const description = productForm?.querySelector('textarea[name="description"]')?.value.trim() || '';
    const brand = productForm?.querySelector('select[name="brand"]')?.value || '';
    const category = productForm?.querySelector('select[name="category"]')?.value || '';
    const price = productForm?.querySelector('input[name="price"]')?.value.trim() || '';
    const stock = productForm?.querySelector('input[name="stock"]')?.value.trim() || '';

    if (!name) return 'Product name is required.';
    if (!brand) return 'Please select a brand.';
    if (!price) return 'Product price is required.';
    if (Number.isNaN(Number(price)) || Number(price) < 0) return 'Product price must be a valid non-negative number.';
    if (!description) return 'Product description is required.';
    if (!stock) return 'Product stock is required.';
    if (!Number.isInteger(Number(stock)) || Number(stock) < 0) return 'Product stock must be a valid non-negative integer.';
    if (!category) return 'Please select a category.';
    if (imageFiles.files.length === 0) return 'Please upload at least one product image.';

    return '';
  };

  const setDirty = () => {
    if (formDirty) return;
    formDirty = true;
    formFooter?.classList.add('is-visible');
  };

  const syncSubCategories = () => {
    if (!categorySelect || !subCategorySelect) return;
    const categoryId = categorySelect.value;
    const options = Array.from(subCategorySelect.options);
    options.forEach((option, index) => {
      if (index === 0) return;
      const visible = !categoryId || option.dataset.categoryId === categoryId;
      option.hidden = !visible;
    });

    if (subCategorySelect.selectedOptions[0] && subCategorySelect.selectedOptions[0].hidden) {
      subCategorySelect.value = '';
    }
  };

  if (productForm) {
    productForm.addEventListener('input', (event) => {
      if (event.target?.classList?.contains('spec-edit-input')) return;
      setDirty();
    });
    productForm.addEventListener('change', setDirty);
    productForm.addEventListener('submit', (event) => {
      const message = validateForm();
      if (!message) return;
      event.preventDefault();
      showWarning(message);
    });
  }

  const buildListItem = (value, inputName, editLabel) => {
    const li = document.createElement('li');
    li.className = 'spec-item';
    li.innerHTML = `
      <span class="spec-dot"></span>
      <span class="spec-text"></span>
      <input type="hidden" name="${inputName}">
      <span class="spec-actions">
        <button type="button" class="icon-btn edit" aria-label="Edit ${editLabel}"><i class="fa-regular fa-pen-to-square"></i></button>
        <button type="button" class="icon-btn delete" aria-label="Delete ${editLabel}"><i class="fa-regular fa-trash-can"></i></button>
      </span>
    `;
    li.querySelector('.spec-text').textContent = value;
    li.querySelector(`input[name="${inputName}"]`).value = value;
    return li;
  };

  const addListValue = (input, list, inputName, label) => {
    const value = (input?.value || '').trim();
    if (!value || !list) return;
    list.appendChild(buildListItem(value, inputName, label));
    input.value = '';
    setDirty();
  };

  const startInlineEdit = (item) => {
    if (!item || item.classList.contains('editing')) return;
    const text = item.querySelector('.spec-text');
    const hidden = item.querySelector('input[type="hidden"]');
    if (!text || !hidden) return;

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'spec-edit-input';
    input.value = text.textContent.trim();
    item.classList.add('editing');
    item.insertBefore(input, item.querySelector('.spec-actions'));
    input.focus();

    const finish = (save) => {
      if (save) {
        const next = input.value.trim();
        if (!next) {
          item.remove();
        } else {
          text.textContent = next;
          hidden.value = next;
        }
      }
      item.classList.remove('editing');
      input.remove();
    };

    input.addEventListener('keydown', (evt) => {
      if (evt.key === 'Enter') {
        evt.preventDefault();
        finish(true);
      }
      if (evt.key === 'Escape') {
        evt.preventDefault();
        finish(false);
      }
    });

    input.addEventListener('blur', () => finish(true));
    input.addEventListener('input', setDirty);
  };

  const syncFilesToInput = () => {
    if (uploadInput) {
      uploadInput.files = imageFiles.files;
    }
  };

  const updateActiveSelection = (index) => {
    selectedImageIndex = Math.max(0, index);
    previews?.querySelectorAll('.image-tile').forEach((tile, tileIndex) => {
      tile.classList.toggle('active', !tile.classList.contains('upload-tile') && tileIndex === selectedImageIndex);
    });
  };

  const updatePrimarySelection = (index) => {
    if (primaryImageIndex) {
      primaryImageIndex.value = String(index);
    }

    previews?.querySelectorAll('.image-tile').forEach((tile, tileIndex) => {
      const radio = tile.querySelector('.primary-radio input');
      if (radio) radio.checked = !tile.classList.contains('upload-tile') && tileIndex === index;
    });
  };

  const refreshTiles = () => {
    if (!previews || !uploadTile) return;

    previews.querySelectorAll('.image-tile:not(.upload-tile)').forEach((tile) => tile.remove());
    Array.from(imageFiles.files).forEach((file, index) => {
      const tile = document.createElement('div');
      tile.className = 'image-tile';
      tile.dataset.fileIndex = String(index);
      tile.innerHTML = `
        <img alt="Product image">
        <div class="image-tools">
          <button type="button" class="icon-btn reupload" title="Reupload">
            <i class="fa-solid fa-upload"></i>
          </button>
          <label class="primary-radio" title="Set primary">
            <input type="radio" name="primary_preview">
            <span></span>
          </label>
          <button type="button" class="icon-btn delete" title="Delete">
            <i class="fa-regular fa-trash-can"></i>
          </button>
        </div>
      `;
      tile.querySelector('img').src = URL.createObjectURL(file);
      previews.insertBefore(tile, uploadTile);
    });

    uploadTile.style.display = imageFiles.files.length >= maxImages ? 'none' : 'grid';
    if (imageFiles.files.length === 0) {
      if (primaryImageIndex) primaryImageIndex.value = '0';
      selectedImageIndex = 0;
      return;
    }

    const primaryIndex = Math.min(Number(primaryImageIndex?.value || '0'), Math.max(imageFiles.files.length - 1, 0));
    updateActiveSelection(Math.min(selectedImageIndex, imageFiles.files.length - 1));
    updatePrimarySelection(primaryIndex);
  };

  [
    { input: specInput, button: specAdd, list: specList, name: 'spec_names[]', label: 'specification' },
    { input: colorInput, button: colorAdd, list: colorList, name: 'color_names[]', label: 'color' },
    { input: sizeInput, button: sizeAdd, list: sizeList, name: 'size_names[]', label: 'size' },
  ].forEach(({ input, button, list, name, label }) => {
    if (button && input) {
      button.addEventListener('click', () => addListValue(input, list, name, label));
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          addListValue(input, list, name, label);
        }
      });
    }

    list?.addEventListener('click', (e) => {
      const deleteBtn = e.target.closest('.icon-btn.delete');
      if (deleteBtn) {
        deleteBtn.closest('.spec-item')?.remove();
        setDirty();
        return;
      }

      const editBtn = e.target.closest('.icon-btn.edit');
      if (editBtn) {
        startInlineEdit(editBtn.closest('.spec-item'));
      }
    });
  });

  uploadTile?.addEventListener('click', () => uploadInput?.click());

  uploadInput?.addEventListener('change', () => {
    const replaceIndex = uploadInput.dataset.replaceIndex !== undefined ? Number(uploadInput.dataset.replaceIndex) : -1;
    const incomingFiles = Array.from(uploadInput.files || []);

    if (replaceIndex >= 0 && incomingFiles[0]) {
      const next = new DataTransfer();
      Array.from(imageFiles.files).forEach((file, index) => {
        next.items.add(index === replaceIndex ? incomingFiles[0] : file);
      });
      imageFiles = next;
      updateActiveSelection(replaceIndex);
    } else {
      incomingFiles.forEach((file) => {
        if (imageFiles.files.length < maxImages) {
          imageFiles.items.add(file);
        }
      });
      if (imageFiles.files.length > 0 && !Number.isFinite(selectedImageIndex)) {
        selectedImageIndex = 0;
      }
    }

    delete uploadInput.dataset.replaceIndex;
    syncFilesToInput();
    refreshTiles();
    setDirty();
  });

  previews?.addEventListener('click', (e) => {
    const tile = e.target.closest('.image-tile');
    if (!tile || tile.classList.contains('upload-tile')) return;
    const index = Number(tile.dataset.fileIndex || '0');

    if (e.target.closest('.icon-btn.delete')) {
      const next = new DataTransfer();
      Array.from(imageFiles.files).forEach((file, fileIndex) => {
        if (fileIndex !== index) next.items.add(file);
      });
      imageFiles = next;
      syncFilesToInput();
      const currentPrimary = Number(primaryImageIndex?.value || '0');
      if (currentPrimary === index) {
        updatePrimarySelection(Math.max(Math.min(index, imageFiles.files.length - 1), 0));
      } else if (currentPrimary > index) {
        updatePrimarySelection(currentPrimary - 1);
      }
      selectedImageIndex = Math.max(Math.min(selectedImageIndex, imageFiles.files.length - 1), 0);
      refreshTiles();
      setDirty();
      return;
    }

    if (e.target.closest('.icon-btn.reupload')) {
      uploadInput.dataset.replaceIndex = String(index);
      uploadInput?.click();
      return;
    }

    if (e.target.closest('.primary-radio')) {
      updatePrimarySelection(index);
      updateActiveSelection(index);
      setDirty();
      return;
    }

    updateActiveSelection(index);
    setDirty();
  });

  categorySelect?.addEventListener('change', syncSubCategories);
  syncSubCategories();

  discardBtn?.addEventListener('click', () => {
    window.location.reload();
  });

  if (window.adminProductAddFlash && window.Swal) {
    Swal.fire({
      icon: window.adminProductAddFlash.type === 'error' ? 'error' : 'success',
      text: window.adminProductAddFlash.message || '',
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
