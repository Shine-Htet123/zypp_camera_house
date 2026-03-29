document.addEventListener('DOMContentLoaded', () => {
  const ordersApiUrl = window.appPath ? window.appPath('/admin/orders-api.php') : '/admin/orders-api.php';
  const orderModal = document.getElementById('orderModal');
  const searchForm = document.querySelector('.orders-search-form');
  const closeBtn = orderModal?.querySelector('.modal-close');
  const rows = Array.from(document.querySelectorAll('.orders-row'));
  const rowsBody = document.querySelector('.orders-body');
  const modalOrderNo = document.getElementById('modalOrderNo');
  const modalOrderDate = document.getElementById('modalOrderDate');
  const modalOrderStatus = document.getElementById('modalOrderStatus');
  const modalPaymentStatus = document.getElementById('modalPaymentStatus');
  const modalShipName = document.getElementById('modalShipName');
  const modalShipEmail = document.getElementById('modalShipEmail');
  const modalShipPhone = document.getElementById('modalShipPhone');
  const modalShipAddress = document.getElementById('modalShipAddress');
  const modalAdditionalNote = document.getElementById('modalAdditionalNote');
  const modalSummaryTable = document.getElementById('orderSummaryTable');
  const searchInput = document.getElementById('ordersSearchInput');
  const searchButton = document.getElementById('ordersSearchButton');
  const filterButton = document.querySelector('.btn-filter');
  const filterDropdown = document.getElementById('ordersFilterDropdown');
  const filterPaymentSelect = document.getElementById('filterPaymentStatus');
  const filterOrderSelect = document.getElementById('filterOrderStatus');
  const filterApplyButton = document.querySelector('.btn-filter-apply');
  const filterResetButton = document.querySelector('.btn-filter-clear');
  const showAllButton = document.querySelector('.admin-show-all');
  const emptyRow = document.querySelector('.orders-empty-row');

  const clearStatusClasses = (el, base) => {
    if (!el) return;
    el.classList.remove(
      `${base}-status`,
      'pending',
      'cancelled',
      'confirmed',
      'delivered',
      'shipped',
      'paid',
      'unpaid'
    );
  };

  const applyStatusClass = (el, base, value) => {
    if (!el) return;
    clearStatusClasses(el, base);
    el.textContent = value;
    el.classList.add('status', base, value.toLowerCase());
  };

  const createStatusSelect = (options, current) => {
    const select = document.createElement('select');
    select.className = 'status-select';
    options.forEach((value) => {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = value;
      option.selected = value.toLowerCase() === current.toLowerCase();
      select.appendChild(option);
    });
    return select;
  };

  const updateEmptyState = () => {
    if (!rowsBody || !emptyRow) return;
    const visibleRows = rows.filter((row) => row.style.display !== 'none');
    emptyRow.style.display = visibleRows.length === 0 ? '' : 'none';
  };

  const applyFilters = () => {
    const query = (searchInput?.value || '').trim().toLowerCase();
    const paymentFilter = (filterPaymentSelect?.value || '').trim().toLowerCase();
    const orderFilter = (filterOrderSelect?.value || '').trim().toLowerCase();

    rows.forEach((row) => {
      const haystack = [
        row.dataset.orderNo,
        row.dataset.customer,
        row.dataset.name,
      ].join(' ').toLowerCase();

      const matchesQuery = query === '' || haystack.includes(query);
      const matchesPayment =
        paymentFilter === '' || (row.dataset.paymentStatus || '').toLowerCase() === paymentFilter;
      const matchesOrder =
        orderFilter === '' || (row.dataset.orderStatus || '').toLowerCase() === orderFilter;

      row.style.display = matchesQuery && matchesPayment && matchesOrder ? '' : 'none';
    });

    updateEmptyState();
  };

  const toggleFilterDropdown = () => {
    if (!filterDropdown) return;
    const isOpen = filterDropdown.classList.contains('open');
    filterDropdown.classList.toggle('open', !isOpen);
    filterDropdown.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
  };

  const closeModal = () => {
    orderModal?.classList.remove('open');
    orderModal?.setAttribute('aria-hidden', 'true');
  };

  const openModal = () => {
    orderModal?.classList.add('open');
    orderModal?.setAttribute('aria-hidden', 'false');
  };

  const renderSummary = (detail) => {
    if (!modalSummaryTable) return;

    modalSummaryTable.querySelectorAll('.summary-row').forEach((row) => row.remove());

    (detail.items || []).forEach((item) => {
      const row = document.createElement('div');
      row.className = 'summary-row';
      row.innerHTML = `
        <span>${item.qty}</span>
        <span>${item.name}</span>
        <span>${item.price_display}</span>
      `;
      modalSummaryTable.appendChild(row);
    });

    [
      ['Subtotal', detail.subtotal_display],
      ['Total Discount', `-${detail.total_discount_display}`],
      ['Grand Total', detail.grand_total_display || detail.total_display],
    ].forEach(([label, value]) => {
      const row = document.createElement('div');
      row.className = 'summary-row summary-total';
      row.innerHTML = `<span>${label}</span><span>${value}</span>`;
      modalSummaryTable.appendChild(row);
    });
  };

  const loadOrderDetail = async (orderId) => {
    const response = await fetch(`${ordersApiUrl}?action=detail&order_id=${encodeURIComponent(orderId)}`, {
      headers: { Accept: 'application/json' },
    });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Failed to load order details.');
    }
    return payload.data;
  };

  const populateModal = (detail) => {
    if (modalOrderNo) modalOrderNo.textContent = detail.order_no_display || '#-';
    if (modalOrderDate) modalOrderDate.textContent = detail.order_date_display || '-';
    if (modalShipName) modalShipName.textContent = detail.delivery_name || '-';
    if (modalShipEmail) modalShipEmail.textContent = detail.delivery_email || '-';
    if (modalShipPhone) modalShipPhone.textContent = detail.delivery_phone || '-';
    if (modalShipAddress) modalShipAddress.textContent = detail.delivery_address || '-';
    if (modalAdditionalNote) modalAdditionalNote.textContent = detail.additional_note || '-';
    applyStatusClass(modalOrderStatus, 'order', detail.order_status_label || 'Pending');
    applyStatusClass(modalPaymentStatus, 'payment', detail.payment_status_label || 'Unpaid');
    renderSummary(detail);
  };

  let currentlyEditingRow = null;

  const resetRow = (row) => {
    if (!row) return;
    row.classList.remove('editing');
    const paymentCell = row.querySelector('.status.payment');
    const orderCell = row.querySelector('.status.order');
    const actionsCell = row.querySelector('.order-actions');
    applyStatusClass(paymentCell, 'payment', row.dataset.paymentStatus || 'Unpaid');
    applyStatusClass(orderCell, 'order', row.dataset.orderStatus || 'Pending');
    if (actionsCell) {
      actionsCell.innerHTML = '<i class="fa-regular fa-pen-to-square edit-status"></i>';
    }
  };

  const saveRowStatuses = async (row, orderStatus, paymentStatus = '') => {
    const formData = new FormData();
    formData.append('action', 'update-status');
    formData.append('order_id', row.dataset.orderId || '');
    formData.append('order_status', orderStatus);
    if (paymentStatus !== '') {
      formData.append('payment_status', paymentStatus);
    }

    const response = await fetch(ordersApiUrl, {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json' },
    });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Failed to update order statuses.');
    }

    row.dataset.orderStatus = payload.data.order_status;
    if (payload.data.payment_status) {
      row.dataset.paymentStatus = payload.data.payment_status;
    }
    resetRow(row);
  };

  const enterEditMode = (row) => {
    if (!row || row.classList.contains('editing')) return;
    if (currentlyEditingRow && currentlyEditingRow !== row) {
      resetRow(currentlyEditingRow);
    }
    currentlyEditingRow = row;
    row.classList.add('editing');

    const paymentCell = row.querySelector('.status.payment');
    const orderCell = row.querySelector('.status.order');
    const actionsCell = row.querySelector('.order-actions');
    if (!paymentCell || !orderCell || !actionsCell) return;

    const originalOrder = row.dataset.orderStatus || orderCell.textContent.trim();
    const originalPayment = row.dataset.paymentStatus || paymentCell.textContent.trim();
    const canEditPaymentStatus = row.dataset.canEditPaymentStatus === '1';

    paymentCell.innerHTML = '';
    orderCell.innerHTML = '';
    actionsCell.innerHTML = '';

    const orderSelect = createStatusSelect(
      ['Pending', 'Cancelled', 'Confirmed', 'Delivered', 'Shipped'],
      originalOrder
    );
    let paymentSelect = null;
    if (canEditPaymentStatus) {
      paymentSelect = createStatusSelect(['Unpaid', 'Paid', 'Pending'], originalPayment);
      paymentCell.appendChild(paymentSelect);
    } else {
      applyStatusClass(paymentCell, 'payment', originalPayment);
    }

    orderCell.appendChild(orderSelect);

    const syncOrderStatusFromPayment = () => {
      if (!paymentSelect || paymentSelect.value !== 'Paid') return;
      if (orderSelect.value === 'Pending' || orderSelect.value === 'Confirmed') {
        orderSelect.value = 'Confirmed';
      }
    };

    paymentSelect?.addEventListener('change', syncOrderStatusFromPayment);
    syncOrderStatusFromPayment();

    actionsCell.innerHTML = `
      <i class="fa-solid fa-check status-confirm"></i>
      <i class="fa-solid fa-xmark status-cancel"></i>
    `;

    actionsCell.querySelector('.status-confirm')?.addEventListener('click', async (event) => {
      event.stopPropagation();
      try {
        await saveRowStatuses(row, orderSelect.value, paymentSelect?.value || '');
        currentlyEditingRow = null;
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Updated',
            text: canEditPaymentStatus ? 'Order and payment statuses have been updated.' : 'Order status has been updated.',
            timer: 1400,
            showConfirmButton: false,
          });
        }
      } catch (error) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Update failed', text: error.message });
        }
      }
    });

    actionsCell.querySelector('.status-cancel')?.addEventListener('click', (event) => {
      event.stopPropagation();
      resetRow(row);
      currentlyEditingRow = null;
    });
  };

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
    if (filterPaymentSelect) filterPaymentSelect.value = '';
    if (filterOrderSelect) filterOrderSelect.value = '';
    applyFilters();
  });

  searchInput?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      applyFilters();
    }
  });

  filterApplyButton?.addEventListener('click', () => {
    applyFilters();
    filterDropdown?.classList.remove('open');
    filterDropdown?.setAttribute('aria-hidden', 'true');
  });

  filterResetButton?.addEventListener('click', () => {
    if (filterPaymentSelect) filterPaymentSelect.value = '';
    if (filterOrderSelect) filterOrderSelect.value = '';
    if (searchInput) searchInput.value = '';
    applyFilters();
  });

  filterButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    toggleFilterDropdown();
  });

  document.addEventListener('click', (event) => {
    if (!filterDropdown || !filterDropdown.classList.contains('open')) return;
    if (event.target.closest('.filter-dropdown') || event.target.closest('.btn-filter')) return;
    filterDropdown.classList.remove('open');
    filterDropdown.setAttribute('aria-hidden', 'true');
  });

  rows.forEach((row) => {
    row.querySelector('.order-id-link')?.addEventListener('click', async () => {
      try {
        const detail = await loadOrderDetail(row.dataset.orderId || '');
        populateModal(detail);
        openModal();
      } catch (error) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Load failed', text: error.message });
        }
      }
    });
  });

  rowsBody?.addEventListener('click', (event) => {
    const editIcon = event.target.closest('.edit-status');
    if (!editIcon) return;
    enterEditMode(editIcon.closest('.orders-row'));
  });

  closeBtn?.addEventListener('click', closeModal);
  orderModal?.addEventListener('click', (event) => {
    if (event.target === orderModal) {
      closeModal();
    }
  });

  applyFilters();
});
