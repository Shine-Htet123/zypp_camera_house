document.addEventListener('DOMContentLoaded', () => {
  const adminsEndpoint = window.appPath ? window.appPath('/admin/admins.php') : '/admin/admins.php';
  const pageRoot = document.querySelector('.admins-page');
  const adminsBody = document.querySelector('.admins-grid');
  const searchForm = document.querySelector('.admins-search-form');
  const filterButton = document.querySelector('.btn-filter');
  const filterDropdown = document.getElementById('adminsFilterDropdown');
  const filterRoleSelect = document.getElementById('filterAdminRole');
  const filterStatusSelect = document.getElementById('filterAdminStatus');
  const filterApplyButton = document.querySelector('.btn-filter-apply');
  const filterResetButton = document.querySelector('.btn-filter-clear');
  const searchInput = document.getElementById('adminsSearchInput');
  const searchButton = document.querySelector('.admin-search-submit');
  const showAllButton = document.querySelector('.admin-show-all');
  const inviteButton = document.querySelector('.btn-invite');
  const inviteEmailInput = document.getElementById('inviteEmail');
  const inviteRoleSelect = document.getElementById('inviteRole');
  const pagination = document.querySelector('.admins-pagination');
  const pageButtons = document.querySelectorAll('.admins-pagination .page-number');
  const prevButton = document.querySelector('.admins-pagination .page-arrow:first-child');
  const nextButton = document.querySelector('.admins-pagination .page-arrow:last-child');
  const isSuperAdmin = pageRoot?.dataset.isSuperAdmin === 'true';
  let emptyState = document.querySelector('.admins-empty');

  const allCards = Array.from(document.querySelectorAll('.admin-card'));
  let filteredCards = [...allCards];
  const PER_PAGE = 6;
  let currentPage = 1;

  const showSwal = (options) => {
    if (typeof Swal !== 'undefined') {
      return Swal.fire(options);
    }

    console.error('SweetAlert2 is not available on the Admins page.');
    return Promise.resolve({ isConfirmed: false });
  };

  const renderPagination = () => {
    const totalPages = Math.max(1, Math.ceil(filteredCards.length / PER_PAGE));
    if (!pagination) return;

    pagination.style.display = filteredCards.length > 0 ? 'flex' : 'none';

    pageButtons.forEach((btn, index) => {
      const pageNum = index + 1;
      btn.style.display = pageNum <= totalPages ? 'inline-flex' : 'none';
      btn.classList.toggle('active', pageNum === currentPage);
    });
  };

  const ensureEmptyState = () => {
    if (!adminsBody) return;

    if (allCards.length === 0) {
      if (!emptyState) {
        emptyState = document.createElement('div');
        emptyState.className = 'admins-empty';
        emptyState.textContent = 'No admins found.';
        adminsBody.appendChild(emptyState);
      }
      return;
    }

    if (emptyState) {
      emptyState.remove();
      emptyState = null;
    }
  };

  const renderPage = (page) => {
    if (!adminsBody) return;
    const totalPages = Math.max(1, Math.ceil(filteredCards.length / PER_PAGE));
    currentPage = Math.min(Math.max(page, 1), totalPages);

    allCards.forEach((card) => {
      card.classList.remove('visible');
      card.style.display = 'none';
    });

    const start = (currentPage - 1) * PER_PAGE;
    const visibleCards = filteredCards.slice(start, start + PER_PAGE);
    visibleCards.forEach((card) => {
      card.style.display = 'flex';
      requestAnimationFrame(() => card.classList.add('visible'));
    });

    ensureEmptyState();
    renderPagination();
  };

  const applyFilters = () => {
    const query = (searchInput?.value || '').trim().toLowerCase();
    const role = (filterRoleSelect?.value || '').trim().toLowerCase();
    const status = (filterStatusSelect?.value || '').trim().toLowerCase();

    filteredCards = allCards.filter((card) => {
      const haystack = [
        card.dataset.publicId,
        card.dataset.name,
        card.dataset.email,
      ].join(' ').toLowerCase();

      const matchesQuery = query === '' || haystack.includes(query);
      const matchesRole = role === '' || (card.dataset.role || '').toLowerCase() === role;
      const matchesStatus = status === '' || (card.dataset.status || '').toLowerCase() === status;

      return matchesQuery && matchesRole && matchesStatus;
    });

    currentPage = 1;
    renderPage(1);
  };

  if (filterApplyButton) {
    filterApplyButton.addEventListener('click', () => {
      applyFilters();
      filterDropdown?.classList.remove('open');
      filterDropdown?.setAttribute('aria-hidden', 'true');
    });
  }

  filterResetButton?.addEventListener('click', () => {
    if (filterRoleSelect) filterRoleSelect.value = '';
    if (filterStatusSelect) filterStatusSelect.value = '';
    if (searchInput) searchInput.value = '';
    applyFilters();
  });

  searchForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    applyFilters();
  });

  searchButton?.addEventListener('click', (event) => {
    event.preventDefault();
    applyFilters();
  });

  showAllButton?.addEventListener('click', (event) => {
    event.preventDefault();
    if (searchInput) searchInput.value = '';
    if (filterRoleSelect) filterRoleSelect.value = '';
    if (filterStatusSelect) filterStatusSelect.value = '';
    applyFilters();
  });

  searchInput?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      applyFilters();
    }
  });

  pageButtons.forEach((btn, index) => {
    btn.addEventListener('click', () => renderPage(index + 1));
  });

  prevButton?.addEventListener('click', () => renderPage(currentPage - 1));
  nextButton?.addEventListener('click', () => renderPage(currentPage + 1));

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
    if (event.target.closest('.filter-dropdown') || event.target.closest('.btn-filter')) {
      return;
    }
    filterDropdown.classList.remove('open');
    filterDropdown.setAttribute('aria-hidden', 'true');
  });

  inviteButton?.addEventListener('click', async () => {
    if (!isSuperAdmin) {
      showSwal({ icon: 'error', title: 'Not allowed', text: 'Only super-admins can invite new admins.' });
      return;
    }

    const email = (inviteEmailInput?.value || '').trim();
    const role = (inviteRoleSelect?.value || '').trim();

    if (email === '' || role === '') {
      showSwal({ icon: 'warning', title: 'Missing information', text: 'Please enter an admin email and choose a role.' });
      return;
    }

    const body = new URLSearchParams({
      action: 'invite_admin',
      email,
      role,
    });

    try {
      const response = await fetch(adminsEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body.toString(),
      });

      const payload = await response.json();
      if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Failed to create invite.');
      }

      inviteEmailInput.value = '';
      inviteRoleSelect.value = '';

      await showSwal({
        icon: 'success',
        title: 'Invite Sent',
        text: payload.message || 'The invite email has been sent successfully.',
        confirmButtonText: 'OK',
      });
    } catch (error) {
      showSwal({ icon: 'error', title: 'Invite Failed', text: error.message });
    }
  });

  adminsBody?.addEventListener('click', async (event) => {
    const deleteBtn = event.target.closest('.delete-admin');
    if (!deleteBtn || deleteBtn.disabled) return;

    event.stopPropagation();
    const card = deleteBtn.closest('.admin-card');
    if (!card) return;

    const result = await showSwal({
      title: 'Delete admin?',
      text: 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#e53935',
    });

    if (!result.isConfirmed) return;

    const body = new URLSearchParams({
      action: 'delete_admin',
      admin_id: card.dataset.adminId || '',
    });

    try {
      const response = await fetch(adminsEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body.toString(),
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Failed to delete admin.');
      }

      card.remove();
      const index = allCards.indexOf(card);
      if (index >= 0) {
        allCards.splice(index, 1);
      }
      applyFilters();
    } catch (error) {
      showSwal({ icon: 'error', title: 'Delete Failed', text: error.message });
    }
  });

  if (window.__adminFlash && window.__adminFlash.message) {
    showSwal({
      icon: window.__adminFlash.type === 'success' ? 'success' : 'error',
      title: window.__adminFlash.type === 'success' ? 'Success' : 'Notice',
      text: window.__adminFlash.message,
    });
  }

  applyFilters();
});
