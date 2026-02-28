document.addEventListener('DOMContentLoaded', () => {
  const productForm = document.querySelector('.product-form');
  const formFooter = document.querySelector('.form-footer');
  let formDirty = false;
  const specInput = document.getElementById('specInput');
  const specAdd = document.getElementById('specAdd');
  const specList = document.querySelector('.specs-edit .spec-list');
  const imageGrid = document.getElementById('editImageGrid');
  const uploadInput = document.getElementById('imageUploadInput');
  const uploadTile = document.getElementById('imageUploadTile');
  const discardBtn = document.querySelector('.btn-footer.discard');
  const maxImages = 5;
  let activeTile = imageGrid?.querySelector('.image-tile.active') || null;

  const setDirty = () => {
    if (formDirty) return;
    formDirty = true;
    formFooter?.classList.add('is-visible');
  };

  if (productForm) {
    productForm.addEventListener('input', (event) => {
      if (event.target?.classList?.contains('spec-edit-input')) return;
      setDirty();
    });
    productForm.addEventListener('change', setDirty);
  }

  const addSpec = () => {
    const value = (specInput?.value || '').trim();
    if (!value || !specList) return;
    const li = document.createElement('li');
    const specId = `spec-${Date.now()}`;
    li.className = 'spec-item';
    li.dataset.specId = specId;
    li.innerHTML = `
      <span class="spec-dot"></span>
      <span class="spec-text">${value}</span>
      <span class="spec-actions">
        <button type="button" class="icon-btn edit" aria-label="Edit spec"><i class="fa-regular fa-pen-to-square"></i></button>
        <button type="button" class="icon-btn delete" aria-label="Delete spec"><i class="fa-regular fa-trash-can"></i></button>
      </span>
    `;
    specList.appendChild(li);
    specInput.value = '';
    setDirty();
  };

  if (specAdd && specInput) {
    specAdd.addEventListener('click', addSpec);
    specInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        addSpec();
      }
    });
  }

  const startInlineEdit = (item) => {
    if (!item || item.classList.contains('editing')) return;
    const text = item.querySelector('.spec-text');
    if (!text) return;
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

  if (specList) {
    specList.querySelectorAll('.spec-item').forEach((item, index) => {
      if (!item.dataset.specId) {
        item.dataset.specId = `spec-${index}`;
      }
    });

    specList.addEventListener('click', (e) => {
      const delBtn = e.target.closest('.icon-btn.delete');
      if (delBtn) {
        const item = delBtn.closest('.spec-item');
        item?.remove();
        setDirty();
        return;
      }

      const editBtn = e.target.closest('.icon-btn.edit');
      if (editBtn) {
        const item = editBtn.closest('.spec-item');
        startInlineEdit(item);
      }
    });
  }

  const setActiveTile = (tile) => {
    imageGrid?.querySelectorAll('.image-tile').forEach((t) => t.classList.remove('active'));
    if (tile) {
      tile.classList.add('active');
    }
    activeTile = tile;
  };

  const ensurePrimarySingle = (tile) => {
    if (!tile) return;
    const radio = tile.querySelector('input[type="radio"]');
    if (radio) {
      imageGrid.querySelectorAll('input[type="radio"]').forEach((r) => (r.checked = false));
      radio.checked = true;
    }
  };

  if (imageGrid) {
    imageGrid.addEventListener('click', (e) => {
      const tile = e.target.closest('.image-tile');
      if (!tile) return;
      if (tile.classList.contains('upload-tile')) {
        setActiveTile(null);
        if (uploadInput) uploadInput.click();
        return;
      }

      if (e.target.closest('.icon-btn.delete')) {
        tile.remove();
        const first = imageGrid.querySelector('.image-tile:not(.upload-tile)');
        setActiveTile(first);
        ensurePrimarySingle(first);
        updateUploadTileVisibility();
        setDirty();
        return;
      }

      if (e.target.closest('.icon-btn.reupload')) {
        setActiveTile(tile);
        if (uploadInput) uploadInput.click();
        setDirty();
        return;
      }

      if (e.target.closest('.primary-radio')) {
        ensurePrimarySingle(tile);
        setDirty();
        return;
      }

      setActiveTile(tile);
      setDirty();
    });
  }

  const updateUploadTileVisibility = () => {
    if (!uploadTile || !imageGrid) return;
    const tiles = imageGrid.querySelectorAll('.image-tile:not(.upload-tile)');
    if (tiles.length >= maxImages) {
      uploadTile.classList.add('hidden');
    } else {
      uploadTile.classList.remove('hidden');
    }
  };

  if (uploadInput) {
    uploadInput.addEventListener('change', () => {
      const file = uploadInput.files && uploadInput.files[0];
      if (!file) return;
      if (activeTile) {
        const img = activeTile.querySelector('img');
        if (!img) return;
        img.src = URL.createObjectURL(file);
      } else {
        const tile = document.createElement('div');
        tile.className = 'image-tile active';
        tile.innerHTML = `
          <img alt="Product image">
          <div class="image-tools">
            <button type="button" class="icon-btn reupload" title="Reupload">
              <i class="fa-solid fa-rotate-right"></i>
            </button>
            <label class="primary-radio" title="Set primary">
              <input type="radio" name="primary_image">
              <span></span>
            </label>
            <button type="button" class="icon-btn delete" title="Delete">
              <i class="fa-regular fa-trash-can"></i>
            </button>
          </div>
        `;
        const img = tile.querySelector('img');
        img.src = URL.createObjectURL(file);
        imageGrid.insertBefore(tile, uploadTile);
        setActiveTile(tile);
        ensurePrimarySingle(tile);
      }
      updateUploadTileVisibility();
      uploadInput.value = '';
      setDirty();
    });
  }

  updateUploadTileVisibility();

  if (discardBtn) {
    discardBtn.addEventListener('click', () => {
      window.location.reload();
    });
  }

});
