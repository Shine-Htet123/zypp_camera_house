document.addEventListener('DOMContentLoaded', () => {
  const rows = Array.from(document.querySelectorAll('.customers-row'));
  const customersTable = document.querySelector('.customers-table');
  const customersBody = customersTable || document;
  const searchForm = document.querySelector('.customers-search-form');
  const filterButton = document.querySelector('.btn-filter');
  const filterDropdown = document.getElementById('customersFilterDropdown');
  const filterMemberSelect = document.getElementById('filterMemberLevel');
  const filterStatusSelect = document.getElementById('filterCustomerStatus');
  const filterResetButton = document.querySelector('.btn-filter-clear');
  const filterApplyButton = document.querySelector('.btn-filter-apply');
  const searchInput = document.querySelector('.admin-search-box input');
  const searchButton = document.querySelector('.admin-search-submit');
  const showAllButton = document.querySelector('.admin-show-all');
  const updateUrl = customersTable?.dataset.updateUrl || window.location.pathname;
  const filters = {
    query: '',
    memberLevel: '',
    status: '',
  };

  const clearStatusClasses = (el) => {
    if (!el) return;
    el.classList.remove('active', 'suspended', 'banned');
  };

  const applyStatusToRow = (row, status) => {
    const statusSpan = row.querySelector('.customer-status');
    const actionsCell = row.querySelector('.customer-actions');

    if (statusSpan) {
      clearStatusClasses(statusSpan);
      statusSpan.textContent = status;
      statusSpan.classList.add('customer-status', status.toLowerCase());
    }

    if (actionsCell) {
      actionsCell.innerHTML = '';
      const editButton = document.createElement('button');
      editButton.type = 'button';
      editButton.className = 'icon-btn-small edit-status';
      editButton.setAttribute('aria-label', 'Edit status');
      editButton.innerHTML = '<i class="fa-regular fa-pen-to-square"></i>';
      actionsCell.appendChild(editButton);
    }

    row.dataset.status = status;
  };

  const createStatusSelect = (current) => {
    const select = document.createElement('select');
    select.className = 'status-select';
    ['Active', 'Suspended', 'Banned'].forEach((value) => {
      const opt = document.createElement('option');
      opt.value = value;
      opt.textContent = value;
      if (value.toLowerCase() === current.toLowerCase()) {
        opt.selected = true;
      }
      select.appendChild(opt);
    });
    return select;
  };

  const filterRows = () => {
    rows.forEach((row) => {
      const haystack = [
        row.dataset.id,
        row.dataset.name,
        row.dataset.email,
      ]
        .join(' ')
        .toLowerCase();

      const matchesQuery =
        filters.query === '' || haystack.includes(filters.query);
      const matchesMemberLevel =
        filters.memberLevel === '' ||
        (row.dataset.memberLevel || '').toLowerCase() === filters.memberLevel;
      const matchesStatus =
        filters.status === '' ||
        (row.dataset.status || '').toLowerCase() === filters.status;

      row.hidden = !(matchesQuery && matchesMemberLevel && matchesStatus);
    });
  };

  const updateCustomerStatus = async (customerId, status) => {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('customer_id', customerId);
    formData.append('status', status.toLowerCase());

    const response = await fetch(updateUrl, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    const payload = await response.json();

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Unable to update status.');
    }

    return payload;
  };

  let currentlyEditingRow = null;

  const resetRowFromDataset = (row) => {
    if (!row) return;
    const status = row.dataset.status || row.dataset.originalStatus;
    if (status) {
      applyStatusToRow(row, status);
    }
    row.classList.remove('editing');
  };

  const enterEditMode = (row) => {
    if (row.classList.contains('editing')) return;

    if (currentlyEditingRow && currentlyEditingRow !== row) {
      resetRowFromDataset(currentlyEditingRow);
    }

    row.classList.add('editing');
    currentlyEditingRow = row;

    const statusSpan = row.querySelector('.customer-status');
    const actionsCell = row.querySelector('.customer-actions');

    if (!statusSpan || !actionsCell) return;

    const originalStatus = row.dataset.status || statusSpan.textContent.trim();
    row.dataset.originalStatus = originalStatus;

    statusSpan.innerHTML = '';
    actionsCell.innerHTML = '';

    const statusSelect = createStatusSelect(originalStatus);
    statusSpan.appendChild(statusSelect);

    const confirmButton = document.createElement('button');
    confirmButton.type = 'button';
    confirmButton.className = 'icon-btn-small status-confirm';
    confirmButton.setAttribute('aria-label', 'Confirm status');
    confirmButton.innerHTML = '<i class="fa-solid fa-check"></i>';

    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.className = 'icon-btn-small status-cancel';
    cancelButton.setAttribute('aria-label', 'Cancel');
    cancelButton.innerHTML = '<i class="fa-solid fa-xmark"></i>';

    actionsCell.appendChild(confirmButton);
    actionsCell.appendChild(cancelButton);

    confirmButton.addEventListener('click', (event) => {
      event.stopPropagation();

      const newStatus = statusSelect.value;

      const proceed = async (confirmed) => {
        if (!confirmed) {
          applyStatusToRow(row, originalStatus);
          row.classList.remove('editing');
          currentlyEditingRow = null;
          return;
        }

        try {
          const payload = await updateCustomerStatus(row.dataset.id, newStatus);
          applyStatusToRow(row, payload.status_label);
          row.dataset.status = payload.status_label;
          row.classList.remove('editing');
          currentlyEditingRow = null;
          filterRows();
        } catch (error) {
          applyStatusToRow(row, originalStatus);
          row.classList.remove('editing');
          currentlyEditingRow = null;

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: 'Update failed',
              text: error.message,
              icon: 'error',
              confirmButtonText: 'OK',
            });
          }
        }
      };

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Confirm status update?',
          text: 'Are you sure you want to save this change?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, confirm',
          cancelButtonText: 'No, cancel',
        }).then((result) => {
          proceed(result.isConfirmed);
        });
      } else {
        proceed(true);
      }
    });

    cancelButton.addEventListener('click', (event) => {
      event.stopPropagation();
      applyStatusToRow(row, originalStatus);
      row.classList.remove('editing');
      currentlyEditingRow = null;
    });
  };

  customersBody?.addEventListener('click', (event) => {
    const icon = event.target.closest('.edit-status');
    if (!icon) return;
    event.stopPropagation();
    const row = icon.closest('.customers-row');
    if (!row) return;
    enterEditMode(row);
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
    if (searchInput) searchInput.value = '';
    if (filterMemberSelect) filterMemberSelect.value = '';
    if (filterStatusSelect) filterStatusSelect.value = '';
    filters.query = '';
    filters.memberLevel = '';
    filters.status = '';
    filterRows();
  });

  filterApplyButton?.addEventListener('click', () => {
    filters.memberLevel = (filterMemberSelect?.value || '').trim().toLowerCase();
    filters.status = (filterStatusSelect?.value || '').trim().toLowerCase();
    filterRows();
    filterDropdown?.classList.remove('open');
    filterDropdown?.setAttribute('aria-hidden', 'true');
  });

  const applySearch = () => {
    filters.query = (searchInput?.value || '').trim().toLowerCase();
    filterRows();
  };

  searchForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    applySearch();
  });

  searchButton?.addEventListener('click', (event) => {
    event.preventDefault();
    applySearch();
  });

  showAllButton?.addEventListener('click', (event) => {
    event.preventDefault();
    if (searchInput) searchInput.value = '';
    if (filterMemberSelect) filterMemberSelect.value = '';
    if (filterStatusSelect) filterStatusSelect.value = '';
    filters.query = '';
    filters.memberLevel = '';
    filters.status = '';
    filterRows();
  });

  searchInput?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      applySearch();
    }
  });

  applySearch();
});

