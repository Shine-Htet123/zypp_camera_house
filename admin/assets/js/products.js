document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('productSearch');
  const searchBtn = document.getElementById('productSearchBtn');
  const cards = Array.from(document.querySelectorAll('.product-card'));
  const filterToggle = document.getElementById('filterToggle');
  const filterDropdown = document.getElementById('filterDropdown');
  const filterForm = document.getElementById('filterForm');
  const filterClear = document.getElementById('filterClear');
  const pageNumbers = Array.from(document.querySelectorAll('.page-number'));
  const prevBtn = document.querySelector('.pager-btn.prev');
  const nextBtn = document.querySelector('.pager-btn.next');
  const pageSize = 9;
  let currentPage = 1;

  const filterCards = () => {
    const term = (searchInput?.value || '').trim().toLowerCase();
    cards.forEach((card) => {
      const name = card.querySelector('.product-name')?.textContent.toLowerCase() || '';
      const brand = card.querySelector('.product-brand')?.textContent.toLowerCase() || '';
      const match = !term || name.includes(term) || brand.includes(term);
      card.style.display = match ? 'flex' : 'none';
    });
  };

  if (searchBtn && searchInput) {
    searchBtn.addEventListener('click', filterCards);
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        filterCards();
      }
    });
  }

  const totalPages = Math.max(1, Math.ceil(cards.length / pageSize));

  const renderPage = (page) => {
    currentPage = Math.min(Math.max(1, page), totalPages);
    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;
    cards.forEach((card, idx) => {
      card.style.display = idx >= start && idx < end ? 'flex' : 'none';
    });
    pageNumbers.forEach((btn, idx) => {
      const pageNum = idx + 1;
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

  renderPage(1);

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
      // Placeholder: integrate with backend/filter logic later
      closeFilter();
    });
  }

  if (filterClear && filterForm) {
    filterClear.addEventListener('click', () => {
      filterForm.reset();
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
      const card = btn.closest('.product-card');
      const productId = btn.dataset.productId || 'unknown';
      if (!card) return;

      Swal.fire({
        title: 'Delete this product?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
      }).then((result) => {
        if (result.isConfirmed) {
          // Placeholder for backend delete. Replace with real fetch:
          // fetch(`/admin/api/products/${productId}`, { method: 'DELETE' })
          //   .then((res) => { if (!res.ok) throw new Error(); })
          //   .then(() => { card.remove(); renderPage(currentPage); });
          card.remove();
          renderPage(currentPage);
          Swal.fire('Deleted', 'The product has been removed.', 'success');
        }
      });
    });
  });
});
