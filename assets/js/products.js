document.addEventListener('DOMContentLoaded', () => {
  const minRangeInput = document.getElementById('priceMinRange');
  const maxRangeInput = document.getElementById('priceMaxRange');
  const minRangeLabel = document.getElementById('priceMinLabel');
  const maxRangeLabel = document.getElementById('priceMaxLabel');
  const rangeFill = document.querySelector('[data-range-fill]');
  const productCards = Array.from(document.querySelectorAll('.product-card'));
  const pagination = document.querySelector('[data-pagination]');
  const loadingOverlay = document.querySelector('.product-loading');
  const emptyState = document.querySelector('.product-empty-state');
  const itemsPerPage = 9;
  const MIN_FILTER_LOADING_MS = 200;
  let currentPage = 1;
  let loadingTimer = null;

  const formatMMK = (value) => {
    const number = Number(value) || 0;
    return `${number.toLocaleString()} MMK`;
  };

  const getRangeStep = () => {
    if (!minRangeInput) return 1;
    return Math.max(Number(minRangeInput.step) || 1, 1);
  };

  const syncRangeState = (activeInput = null) => {
    if (!minRangeInput || !maxRangeInput) return;

    const step = getRangeStep();
    const maxLimit = Number(maxRangeInput.max) || 0;
    let minValue = Number(minRangeInput.value) || 0;
    let maxValue = Number(maxRangeInput.value) || 0;

    if (activeInput === minRangeInput && minValue > maxValue - step) {
      minValue = Math.max(0, maxValue - step);
      minRangeInput.value = String(minValue);
    } else if (activeInput === maxRangeInput && maxValue < minValue + step) {
      maxValue = Math.min(maxLimit, minValue + step);
      maxRangeInput.value = String(maxValue);
    } else if (minValue > maxValue) {
      minValue = maxValue;
      minRangeInput.value = String(minValue);
    }

    if (minRangeLabel) {
      minRangeLabel.textContent = formatMMK(minValue);
    }

    if (maxRangeLabel) {
      maxRangeLabel.textContent = formatMMK(maxValue);
    }

    if (rangeFill && maxLimit > 0) {
      const startPercent = (minValue / maxLimit) * 100;
      const endPercent = (maxValue / maxLimit) * 100;
      rangeFill.style.left = `${startPercent}%`;
      rangeFill.style.width = `${Math.max(endPercent - startPercent, 0)}%`;
    }

    if (minRangeInput && maxRangeInput) {
      minRangeInput.style.zIndex = activeInput === minRangeInput ? '3' : '2';
      maxRangeInput.style.zIndex = activeInput === maxRangeInput ? '3' : '2';
    }
  };

  const getSelectedValues = (group) => {
    return Array.from(
      document.querySelectorAll(`input[data-filter-group="${group}"]:checked`)
    ).map((input) => input.value);
  };

  const getFilteredCards = () => {
    const selectedCategories = getSelectedValues('category');
    const selectedBrands = getSelectedValues('brand');
    const selectedOptions = getSelectedValues('option');
    const selectedAvailability = getSelectedValues('availability');
    const minPrice = minRangeInput ? Number(minRangeInput.value) : 0;
    const maxPrice = maxRangeInput ? Number(maxRangeInput.value) : Number.POSITIVE_INFINITY;

    return productCards.filter((card) => {
      const category = card.dataset.category || '';
      const brand = card.dataset.brand || '';
      const availability = card.dataset.availability || '';
      const tags = (card.dataset.tags || '').split(',').map((tag) => tag.trim());
      const price = Number(card.dataset.price || '0');

      const matchCategory =
        selectedCategories.length === 0 || selectedCategories.includes(category);
      const matchBrand =
        selectedBrands.length === 0 || selectedBrands.includes(brand);
      const matchAvailability =
        selectedAvailability.length === 0 || selectedAvailability.includes(availability);
      const matchOptions =
        selectedOptions.length === 0 || selectedOptions.some((opt) => tags.includes(opt));
      const matchPrice = price >= minPrice && price <= maxPrice;

      return matchCategory && matchBrand && matchAvailability && matchOptions && matchPrice;
    });
  };

  const renderPagination = (totalItems) => {
    if (!pagination) return;

    if (totalItems <= 0) {
      pagination.innerHTML = '';
      pagination.hidden = true;
      return;
    }

    pagination.hidden = false;
    const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));
    if (currentPage > totalPages) currentPage = 1;

    const buttons = [];
    buttons.push(
      `<button class="page-btn" type="button" data-page="prev" ${
        currentPage === 1 ? 'disabled' : ''
      }><i class="fa-solid fa-chevron-left"></i></button>`
    );

    for (let i = 1; i <= totalPages; i += 1) {
      buttons.push(
        `<button class="page-btn${i === currentPage ? ' active' : ''}" type="button" data-page="${i}">${i}</button>`
      );
    }

    buttons.push(
      `<button class="page-btn" type="button" data-page="next" ${
        currentPage === totalPages ? 'disabled' : ''
      }><i class="fa-solid fa-chevron-right"></i></button>`
    );

    pagination.innerHTML = buttons.join('');
  };

  const applyFiltersCore = () => {
    const filtered = getFilteredCards();
    const totalItems = filtered.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));

    if (currentPage > totalPages) currentPage = 1;

    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;

    productCards.forEach((card) => {
      card.style.display = 'none';
    });

    const visibleCards = filtered.slice(start, end);
    visibleCards.forEach((card) => {
      card.style.display = 'flex';
    });

    if (emptyState) {
      emptyState.hidden = visibleCards.length > 0;
    }

    renderPagination(totalItems);
  };

  const applyWithLoading = () => {
    if (loadingOverlay) {
      loadingOverlay.classList.add('active');
    }
    if (loadingTimer) {
      clearTimeout(loadingTimer);
    }
    loadingTimer = setTimeout(() => {
      applyFiltersCore();
      if (loadingOverlay) {
        loadingOverlay.classList.remove('active');
      }
    }, MIN_FILTER_LOADING_MS);
  };

  syncRangeState();

  [minRangeInput, maxRangeInput].forEach((rangeInput) => {
    rangeInput?.addEventListener('input', () => {
      syncRangeState(rangeInput);
      applyWithLoading();
    });

    rangeInput?.addEventListener('mousedown', () => {
      syncRangeState(rangeInput);
    });

    rangeInput?.addEventListener('touchstart', () => {
      syncRangeState(rangeInput);
    }, { passive: true });
  });

  document.querySelectorAll('input[data-filter-group]').forEach((input) => {
    input.addEventListener('change', () => {
      currentPage = 1;
      applyWithLoading();
    });
  });

  pagination?.addEventListener('click', (event) => {
    const btn = event.target.closest('.page-btn');
    if (!btn || btn.disabled) return;
    const page = btn.dataset.page;
    if (page === 'prev') {
      currentPage = Math.max(1, currentPage - 1);
    } else if (page === 'next') {
      const totalItems = getFilteredCards().length;
      const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));
      currentPage = Math.min(totalPages, currentPage + 1);
    } else {
      currentPage = Number(page) || 1;
    }
    applyWithLoading();
  });

  applyFiltersCore();
});
