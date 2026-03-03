document.addEventListener('DOMContentLoaded', () => {
  const adminsBody = document.querySelector('.admins-grid');
  const filterButton = document.querySelector('.btn-filter');
  const filterDropdown = document.getElementById('adminsFilterDropdown');
  const filterRoleSelect = document.getElementById('filterAdminRole');
  const filterStatusSelect = document.getElementById('filterAdminStatus');
  const filterResetButton = document.querySelector('.btn-filter-clear');
  const pageButtons = document.querySelectorAll('.admins-pagination .page-number');
  const prevButton = document.querySelector('.admins-pagination .page-arrow:first-child');
  const nextButton = document.querySelector('.admins-pagination .page-arrow:last-child');
  const cards = document.querySelectorAll('.admin-card');

  const PER_PAGE = 6;
  let currentPage = 1;

  const totalPages = Math.max(1, Math.ceil(cards.length / PER_PAGE));

  const updatePaginationVisibility = () => {
    pageButtons.forEach((btn, index) => {
      const pageNum = index + 1;
      if (pageNum > totalPages) {
        btn.style.display = 'none';
      } else {
        btn.style.display = 'inline-flex';
      }
    });
  };

  const goToPage = (page) => {
    if (!cards.length) return;
    const clamped = Math.min(Math.max(page, 1), totalPages);
    currentPage = clamped;

    const start = (clamped - 1) * PER_PAGE;
    const end = start + PER_PAGE;

    cards.forEach((card, index) => {
      const inPage = index >= start && index < end;
      if (inPage) {
        card.style.display = 'flex';
        card.classList.remove('visible');
        // next frame to trigger transition
        requestAnimationFrame(() => {
          card.classList.add('visible');
        });
      } else {
        card.classList.remove('visible');
        card.style.display = 'none';
      }
    });

    pageButtons.forEach((btn, index) => {
      const pageNum = index + 1;
      if (pageNum === clamped) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  };

  updatePaginationVisibility();
  goToPage(1);

  adminsBody?.addEventListener('click', (event) => {
    const deleteBtn = event.target.closest('.delete-admin');

    if (deleteBtn) {
      event.stopPropagation();
      const card = deleteBtn.closest('.admin-card');
      if (!card) return;

      const performDelete = (confirmed) => {
        if (!confirmed) return;
        card.remove();
      };

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Delete admin?',
          text: 'This action cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, delete',
          cancelButtonText: 'Cancel',
          confirmButtonColor: '#e53935',
        }).then((result) => {
          performDelete(result.isConfirmed);
        });
      } else {
        performDelete(window.confirm('Are you sure you want to delete this admin?'));
      }
    }
  });

  pageButtons.forEach((btn, index) => {
    btn.addEventListener('click', () => {
      const pageNum = index + 1;
      if (pageNum <= totalPages) {
        goToPage(pageNum);
      }
    });
  });

  prevButton?.addEventListener('click', () => {
    goToPage(currentPage - 1);
  });

  nextButton?.addEventListener('click', () => {
    goToPage(currentPage + 1);
  });

  const toggleFilterDropdown = () => {
    if (!filterDropdown) return;
    const isOpen = filterDropdown.classList.contains('open');
    filterDropdown.classList.toggle('open', !isOpen);
    filterDropdown.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
  };

  filterButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    toggleFilterDropdown();
  });

  document.addEventListener('click', (event) => {
    if (!filterDropdown || !filterDropdown.classList.contains('open')) return;
    if (
      event.target.closest('.filter-dropdown') ||
      event.target.closest('.btn-filter')
    ) {
      return;
    }
    filterDropdown.classList.remove('open');
    filterDropdown.setAttribute('aria-hidden', 'true');
  });

  filterResetButton?.addEventListener('click', () => {
    if (filterRoleSelect) filterRoleSelect.value = '';
    if (filterStatusSelect) filterStatusSelect.value = '';
  });
});

