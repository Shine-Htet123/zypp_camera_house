document.addEventListener('DOMContentLoaded', () => {
  const productForm = document.querySelector('.product-form');
  const formFooter = document.querySelector('.form-footer');
  const specInput = document.getElementById('specInput');
  const specAdd = document.getElementById('specAdd');
  const specList = document.getElementById('editSpecList');
  const colorInput = document.getElementById('colorInput');
  const colorAdd = document.getElementById('colorAdd');
  const colorList = document.getElementById('colorList');
  const sizeInput = document.getElementById('sizeInput');
  const sizeAdd = document.getElementById('sizeAdd');
  const sizeList = document.getElementById('sizeList');
  const imageGrid = document.getElementById('editImageGrid');
  const uploadInput = document.getElementById('imageUploadInput');
  const uploadTile = document.getElementById('imageUploadTile');
  const discardBtn = document.querySelector('.btn-footer.discard');
  const primaryImageKey = document.getElementById('primaryImageKey');
  const deletedImageInputs = document.getElementById('deletedImageInputs');
  const categorySelect = productForm?.querySelector('select[name="category"]');
  const subCategorySelect = productForm?.querySelector('select[name="subcategory"]');
  const maxImages = 5;
  let formDirty = false;
  let newImageFiles = new DataTransfer();
  let activeImageKey = '';

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

  const buildListItem = (value, inputName, label) => {
    const li = document.createElement('li');
    li.className = 'spec-item';
    li.innerHTML = `
      <span class="spec-dot"></span>
      <span class="spec-text"></span>
      <input type="hidden" name="${inputName}">
      <span class="spec-actions">
        <button type="button" class="icon-btn edit" aria-label="Edit ${label}"><i class="fa-regular fa-pen-to-square"></i></button>
        <button type="button" class="icon-btn delete" aria-label="Delete ${label}"><i class="fa-regular fa-trash-can"></i></button>
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
    if (uploadInput) uploadInput.files = newImageFiles.files;
  };

  const existingTiles = () => Array.from(imageGrid?.querySelectorAll('.image-tile[data-image-key^="existing:"]') || []);
  const newTiles = () => Array.from(imageGrid?.querySelectorAll('.image-tile[data-image-key^="new:"]') || []);

  const updateUploadTileVisibility = () => {
    if (!uploadTile || !imageGrid) return;
    const total = imageGrid.querySelectorAll('.image-tile:not(.upload-tile)').length;
    uploadTile.style.display = total >= maxImages ? 'none' : 'grid';
  };

  const setActiveImage = (key) => {
    activeImageKey = key;
    imageGrid?.querySelectorAll('.image-tile').forEach((tile) => {
      if (tile.classList.contains('upload-tile')) return;
      tile.classList.toggle('active', tile.dataset.imageKey === key);
    });
  };

  const setPrimary = (key) => {
    if (primaryImageKey) primaryImageKey.value = key;
    imageGrid?.querySelectorAll('.image-tile').forEach((tile) => {
      const radio = tile.querySelector('.primary-radio input');
      if (radio) radio.checked = !tile.classList.contains('upload-tile') && tile.dataset.imageKey === key;
    });
  };

  const markDeletedExistingImage = (imageId) => {
    if (!deletedImageInputs || !imageId) return;
    if (deletedImageInputs.querySelector(`input[value="${imageId}"]`)) return;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'deleted_image_ids[]';
    input.value = String(imageId);
    deletedImageInputs.appendChild(input);
  };

  const rebuildNewTileIndexes = () => {
    newTiles().forEach((tile, index) => {
      tile.dataset.newIndex = String(index);
      tile.dataset.imageKey = `new:${index}`;
    });
  };

  const appendNewTile = (file, index) => {
    const tile = document.createElement('div');
    tile.className = 'image-tile';
    tile.dataset.newIndex = String(index);
    tile.dataset.imageKey = `new:${index}`;
    tile.innerHTML = `
      <img alt="Product image">
      <div class="image-tools">
        <label class="primary-radio" title="Set primary">
          <input type="radio" name="primary_image_marker">
          <span></span>
        </label>
        <button type="button" class="icon-btn delete" title="Delete">
          <i class="fa-regular fa-trash-can"></i>
        </button>
      </div>
    `;
    tile.querySelector('img').src = URL.createObjectURL(file);
    imageGrid.insertBefore(tile, uploadTile);
  };

  const refreshNewTiles = () => {
    newTiles().forEach((tile) => tile.remove());
    Array.from(newImageFiles.files).forEach((file, index) => appendNewTile(file, index));
    rebuildNewTileIndexes();
    updateUploadTileVisibility();
    if (activeImageKey === '' && imageGrid) {
      const firstTile = imageGrid.querySelector('.image-tile:not(.upload-tile)');
      if (firstTile) setActiveImage(firstTile.dataset.imageKey || '');
    }
  };

  [
    { input: specInput, button: specAdd, list: specList, name: 'spec_names[]', label: 'specification' },
    { input: colorInput, button: colorAdd, list: colorList, name: 'color_names[]', label: 'color' },
    { input: sizeInput, button: sizeAdd, list: sizeList, name: 'size_names[]', label: 'size' },
  ].forEach(({ input, button, list, name, label }) => {
    button?.addEventListener('click', () => addListValue(input, list, name, label));
    input?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        addListValue(input, list, name, label);
      }
    });

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
    Array.from(uploadInput.files || []).forEach((file) => {
      const totalCount = existingTiles().length + newImageFiles.files.length;
      if (totalCount < maxImages) {
        newImageFiles.items.add(file);
      }
    });
    syncFilesToInput();
    refreshNewTiles();
    if ((primaryImageKey?.value || '') === '' && newImageFiles.files.length > 0 && existingTiles().length === 0) {
      setPrimary('new:0');
    }
    setDirty();
  });

  imageGrid?.addEventListener('click', (e) => {
    const tile = e.target.closest('.image-tile');
    if (!tile || tile.classList.contains('upload-tile')) return;
    const key = tile.dataset.imageKey || '';

    if (e.target.closest('.icon-btn.delete')) {
      if (key.startsWith('existing:')) {
        const imageId = Number(tile.dataset.imageId || '0');
        markDeletedExistingImage(imageId);
        tile.remove();
      } else if (key.startsWith('new:')) {
        const removedIndex = Number(tile.dataset.newIndex || '0');
        const next = new DataTransfer();
        Array.from(newImageFiles.files).forEach((file, index) => {
          if (index !== removedIndex) next.items.add(file);
        });
        newImageFiles = next;
        syncFilesToInput();
        refreshNewTiles();
      }

      const firstTile = imageGrid.querySelector('.image-tile:not(.upload-tile)');
      if (firstTile) {
        const nextKey = firstTile.dataset.imageKey || '';
        setActiveImage(nextKey);
        setPrimary(nextKey);
      } else {
        if (primaryImageKey) primaryImageKey.value = '';
        activeImageKey = '';
      }
      updateUploadTileVisibility();
      setDirty();
      return;
    }

    if (e.target.closest('.icon-btn.reupload')) {
      uploadInput?.click();
      return;
    }

    if (e.target.closest('.primary-radio')) {
      setPrimary(key);
      setActiveImage(key);
      setDirty();
      return;
    }

    setActiveImage(key);
    setDirty();
  });

  categorySelect?.addEventListener('change', syncSubCategories);
  syncSubCategories();
  updateUploadTileVisibility();

  if (!primaryImageKey?.value) {
    const firstTile = imageGrid?.querySelector('.image-tile:not(.upload-tile)');
    if (firstTile) setPrimary(firstTile.dataset.imageKey || '');
  }
  setActiveImage(primaryImageKey?.value || imageGrid?.querySelector('.image-tile:not(.upload-tile)')?.dataset.imageKey || '');

  discardBtn?.addEventListener('click', () => {
    window.location.reload();
  });

  if (window.adminProductEditFlash && window.Swal) {
    Swal.fire({
      icon: window.adminProductEditFlash.type === 'error' ? 'error' : 'success',
      text: window.adminProductEditFlash.message || '',
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
