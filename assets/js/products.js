document.addEventListener('DOMContentLoaded', () => {
  const rangeInput = document.getElementById('priceRange');
  const rangeLabel = document.getElementById('priceMaxLabel');
  const productGrid = document.querySelector('.product-grid');
  const productCards = Array.from(document.querySelectorAll('.product-card'));
  const pagination = document.querySelector('[data-pagination]');
  const loadingOverlay = document.querySelector('.product-loading');
  const itemsPerPage = 9;
  let currentPage = 1;
  let loadingTimer = null;

  const formatMMK = (value) => {
    const number = Number(value) || 0;
    return `${number.toLocaleString()} MMK`;
  };

  const updateRangeLabel = () => {
    if (!rangeInput || !rangeLabel) return;
    rangeLabel.textContent = formatMMK(rangeInput.value);
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
    const maxPrice = rangeInput ? Number(rangeInput.value) : Number.POSITIVE_INFINITY;

    return productCards.filter((card) => {
      const category = card.dataset.category || '';
      const brand = card.dataset.brand || '';
      const availability = card.dataset.availability || '';
      const tags = (card.dataset.tags || '').split(',').map((tag) => tag.trim());
      const price = Number(card.dataset.price || '0');

      const matchCategory =
        selectedCategories.length === 0 || selectedCategories.includes(category);
      const matchBrand = selectedBrands.length === 0 || selectedBrands.includes(brand);
      const matchAvailability =
        selectedAvailability.length === 0 || selectedAvailability.includes(availability);
      const matchOptions =
        selectedOptions.length === 0 || selectedOptions.some((opt) => tags.includes(opt));
      const matchPrice = price <= maxPrice;

      return matchCategory && matchBrand && matchAvailability && matchOptions && matchPrice;
    });
  };

  const renderPagination = (totalItems) => {
    if (!pagination) return;
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
    }, 250);
  };

  updateRangeLabel();
  if (rangeInput) {
    rangeInput.addEventListener('input', () => {
      updateRangeLabel();
      applyWithLoading();
    });
  }

  document.querySelectorAll('input[data-filter-group]').forEach((input) => {
    input.addEventListener('change', applyWithLoading);
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
