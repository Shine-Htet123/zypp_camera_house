document.addEventListener('DOMContentLoaded', () => {
  const rows = document.querySelectorAll('.customers-row');
  const customersBody = document.querySelector('.customers-body');
  const filterButton = document.querySelector('.btn-filter');
  const filterDropdown = document.getElementById('customersFilterDropdown');
  const filterMemberSelect = document.getElementById('filterMemberLevel');
  const filterStatusSelect = document.getElementById('filterCustomerStatus');
  const filterResetButton = document.querySelector('.btn-filter-clear');

  const clearStatusClasses = (el) => {
    if (!el) return;
    el.classList.remove('active', 'suspended', 'cancelled');
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
      const editIcon = document.createElement('i');
      editIcon.className = 'fa-regular fa-pen-to-square edit-status';
      actionsCell.appendChild(editIcon);
    }

    row.dataset.status = status;
  };

  const createStatusSelect = (current) => {
    const select = document.createElement('select');
    select.className = 'status-select';
    ['Active', 'Suspended', 'Cancelled'].forEach((value) => {
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

    const confirmIcon = document.createElement('i');
    confirmIcon.className = 'fa-solid fa-check status-confirm';

    const cancelIcon = document.createElement('i');
    cancelIcon.className = 'fa-solid fa-xmark status-cancel';

    actionsCell.appendChild(confirmIcon);
    actionsCell.appendChild(cancelIcon);

    confirmIcon.addEventListener('click', (event) => {
      event.stopPropagation();

      const newStatus = statusSelect.value;

      const proceed = (confirmed) => {
        if (confirmed) {
          applyStatusToRow(row, newStatus);
          row.dataset.status = newStatus;
        } else {
          applyStatusToRow(row, originalStatus);
        }
        row.classList.remove('editing');
        currentlyEditingRow = null;
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

    cancelIcon.addEventListener('click', (event) => {
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
    if (filterMemberSelect) filterMemberSelect.value = '';
    if (filterStatusSelect) filterStatusSelect.value = '';
  });
});

