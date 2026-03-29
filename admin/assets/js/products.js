document.addEventListener('DOMContentLoaded', () => {
  const searchForm = document.querySelector('.products-search-form');
  const searchInput = document.getElementById('productSearch');
  const searchBtn = document.getElementById('productSearchBtn');
  const searchTrigger = document.getElementById('productSearchTrigger');
  const showAllButton = document.querySelector('.admin-show-all');
  let cards = Array.from(document.querySelectorAll('.product-card'));
  const filterToggle = document.getElementById('filterToggle');
  const filterDropdown = document.getElementById('filterDropdown');
  const filterForm = document.getElementById('filterForm');
  const filterClear = document.getElementById('filterClear');
  const pageNumbers = Array.from(document.querySelectorAll('.page-number'));
  const prevBtn = document.querySelector('.pager-btn.prev');
  const nextBtn = document.querySelector('.pager-btn.next');
  const deleteForm = document.getElementById('productDeleteForm');
  const pageSize = 9;
  let currentPage = 1;
  let totalPages = Math.max(1, Math.ceil(cards.length / pageSize));

  const showAlert = async (options) => {
    if (window.Swal) {
      return Swal.fire(options);
    }
    const message = options?.text || options?.title || '';
    if (message) alert(message);
    return null;
  };

  const getVisibleCards = () => cards.filter((card) => card.dataset.filtered !== 'false');

  const filterCards = () => {
    const term = (searchInput?.value || '').trim().toLowerCase();
    const selectedCategories = Array.from(document.querySelectorAll('input[name="cat[]"]:checked')).map((input) => input.value.toLowerCase());
    const selectedBrands = Array.from(document.querySelectorAll('input[name="brand[]"]:checked')).map((input) => input.value.toLowerCase());
    const selectedStock = Array.from(document.querySelectorAll('input[name="stock[]"]:checked')).map((input) => input.value.toLowerCase());

    cards.forEach((card) => {
      const name = card.querySelector('.product-name')?.textContent.toLowerCase() || '';
      const brand = card.querySelector('.product-brand')?.textContent.toLowerCase() || '';
      const category = (card.dataset.category || '').toLowerCase();
      const stock = Number(card.dataset.stock || '0');
      const matchTerm = !term || name.includes(term) || brand.includes(term);
      const matchCategory = selectedCategories.length === 0 || selectedCategories.includes(category);
      const matchBrand = selectedBrands.length === 0 || selectedBrands.includes(brand);
      const stockLabel = stock <= 0 ? 'out' : stock <= 10 ? 'low' : 'in';
      const matchStock = selectedStock.length === 0 || selectedStock.includes(stockLabel);

      card.dataset.filtered = matchTerm && matchCategory && matchBrand && matchStock ? 'true' : 'false';
    });
    renderPage(1);
  };

  searchForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    filterCards();
  });

  if (searchBtn && searchInput) {
    searchBtn.addEventListener('click', (e) => {
      e.preventDefault();
      filterCards();
    });
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        filterCards();
      }
    });
  }
  searchTrigger?.addEventListener('click', (e) => {
    e.preventDefault();
    filterCards();
  });

  showAllButton?.addEventListener('click', (e) => {
    e.preventDefault();
    if (searchInput) searchInput.value = '';
    filterForm?.reset();
    filterCards();
  });

  const renderPage = (page) => {
    const visibleCards = getVisibleCards();
    totalPages = Math.max(1, Math.ceil(visibleCards.length / pageSize));
    currentPage = Math.min(Math.max(1, page), totalPages);
    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;
    cards.forEach((card) => {
      card.style.display = 'none';
    });
    visibleCards.forEach((card, idx) => {
      card.style.display = idx >= start && idx < end ? 'flex' : 'none';
    });
    pageNumbers.forEach((btn, idx) => {
      const pageNum = idx + 1;
      btn.style.display = pageNum <= totalPages ? 'inline-flex' : 'none';
      btn.classList.toggle('active', pageNum === currentPage);
    });
  };

  pageNumbers.forEach((btn, idx) => {
    btn.addEventListener('click', () => renderPage(idx + 1));
  });

  if (prevBtn) {
    prevBtn.addEventListener('click', () => renderPage(currentPage - 1));
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => renderPage(currentPage + 1));
  }

  filterCards();

  const closeFilter = () => {
    if (filterDropdown) filterDropdown.classList.remove('open');
  };

  if (filterToggle && filterDropdown) {
    filterToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      filterDropdown.classList.toggle('open');
    });
  }

  if (filterForm) {
    filterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      filterCards();
      closeFilter();
    });
  }

  if (filterClear && filterForm) {
    filterClear.addEventListener('click', () => {
      filterForm.reset();
      filterCards();
    });
  }

  document.addEventListener('click', (e) => {
    if (!filterDropdown) return;
    if (!filterDropdown.contains(e.target) && !filterToggle.contains(e.target)) {
      closeFilter();
    }
  });

  // Delete handling with SweetAlert
  const deleteButtons = document.querySelectorAll('.icon-btn.delete');
  deleteButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const productId = btn.dataset.productId || '0';
      if (!deleteForm || !productId) return;

      showAlert({
        title: 'Delete this product?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
      }).then(async (result) => {
        if (!result.isConfirmed) return;

        const formData = new FormData(deleteForm);
        formData.set('product_id', productId);

        window.AdminLoading?.show('Deleting product...');
        try {
          const response = await fetch(deleteForm.action || window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              Accept: 'application/json',
            },
          });

          const payload = await response.json().catch(() => null);
          if (!response.ok || !payload?.success) {
            throw new Error(payload?.message || 'Delete failed.');
          }

          const card = btn.closest('.product-card');
          card?.remove();
          cards = Array.from(document.querySelectorAll('.product-card'));
          filterCards();

          await showAlert({
            icon: 'success',
            text: payload.message || 'Product deleted successfully.',
            confirmButtonColor: '#3b2615',
          });
        } catch (error) {
          await showAlert({
            icon: 'error',
            title: 'Delete failed',
            text: error.message || 'Delete failed.',
            confirmButtonColor: '#3b2615',
          });
        } finally {
          window.AdminLoading?.hide();
        }
      });
    });
  });

  if (window.adminProductsFlash && window.Swal) {
    Swal.fire({
      icon: window.adminProductsFlash.type === 'error' ? 'error' : 'success',
      text: window.adminProductsFlash.message || '',
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
