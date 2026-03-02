document.addEventListener('DOMContentLoaded', () => {
  const orderModal = document.getElementById('orderModal');
  const closeBtn = orderModal?.querySelector('.modal-close');
  const rows = document.querySelectorAll('.orders-row');
  const modalOrderNo = document.getElementById('modalOrderNo');
  const modalOrderDate = document.getElementById('modalOrderDate');
  const modalOrderStatus = document.getElementById('modalOrderStatus');
  const modalPaymentStatus = document.getElementById('modalPaymentStatus');
  const ordersBody = document.querySelector('.orders-body');
  const filterButton = document.querySelector('.btn-filter');
  const filterDropdown = document.getElementById('ordersFilterDropdown');
  const filterPaymentSelect = document.getElementById('filterPaymentStatus');
  const filterOrderSelect = document.getElementById('filterOrderStatus');
  const filterResetButton = document.querySelector('.btn-filter-clear');

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

  const applyStatusToRow = (row, paymentStatus, orderStatus) => {
    const paymentSpan = row.querySelector('.status.payment');
    const orderSpan = row.querySelector('.status.order');
    const actionsCell = row.querySelector('.order-actions');

    if (paymentSpan) {
      clearStatusClasses(paymentSpan, 'payment');
      paymentSpan.textContent = paymentStatus;
      paymentSpan.classList.add('status', 'payment', paymentStatus.toLowerCase());
    }

    if (orderSpan) {
      clearStatusClasses(orderSpan, 'order');
      orderSpan.textContent = orderStatus;
      orderSpan.classList.add('status', 'order', orderStatus.toLowerCase());
    }

    if (actionsCell) {
      actionsCell.innerHTML = '';
      const editIcon = document.createElement('i');
      editIcon.className = 'fa-regular fa-pen-to-square edit-status';
      actionsCell.appendChild(editIcon);
    }

    row.dataset.paymentStatus = paymentStatus;
    row.dataset.orderStatus = orderStatus;
  };

  const createStatusSelect = (options, current) => {
    const select = document.createElement('select');
    select.className = 'status-select';

    options.forEach((value) => {
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
    const payment = row.dataset.paymentStatus || row.dataset.originalPaymentStatus;
    const order = row.dataset.orderStatus || row.dataset.originalOrderStatus;
    if (payment && order) {
      applyStatusToRow(row, payment, order);
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

    const paymentSpan = row.querySelector('.status.payment');
    const orderSpan = row.querySelector('.status.order');
    const actionsCell = row.querySelector('.order-actions');

    if (!paymentSpan || !orderSpan || !actionsCell) return;

    const originalPayment = row.dataset.paymentStatus || paymentSpan.textContent.trim();
    const originalOrder = row.dataset.orderStatus || orderSpan.textContent.trim();

    row.dataset.originalPaymentStatus = originalPayment;
    row.dataset.originalOrderStatus = originalOrder;

    paymentSpan.innerHTML = '';
    orderSpan.innerHTML = '';
    actionsCell.innerHTML = '';

    const paymentSelect = createStatusSelect(['Unpaid', 'Paid', 'Pending'], originalPayment);
    const orderSelect = createStatusSelect(
      ['Pending', 'Cancelled', 'Confirmed', 'Delivered', 'Shipped'],
      originalOrder
    );

    paymentSpan.appendChild(paymentSelect);
    orderSpan.appendChild(orderSelect);

    const confirmIcon = document.createElement('i');
    confirmIcon.className = 'fa-solid fa-check status-confirm';

    const cancelIcon = document.createElement('i');
    cancelIcon.className = 'fa-solid fa-xmark status-cancel';

    actionsCell.appendChild(confirmIcon);
    actionsCell.appendChild(cancelIcon);

    confirmIcon.addEventListener('click', (event) => {
      event.stopPropagation();

      const newPayment = paymentSelect.value;
      const newOrder = orderSelect.value;

      const proceed = (confirmed) => {
        if (confirmed) {
          applyStatusToRow(row, newPayment, newOrder);
          row.dataset.paymentStatus = newPayment;
          row.dataset.orderStatus = newOrder;
        } else {
          applyStatusToRow(row, originalPayment, originalOrder);
        }
        row.classList.remove('editing');
        currentlyEditingRow = null;
      };

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Confirm status update?',
          text: 'Are you sure you want to save these changes?',
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
      applyStatusToRow(row, originalPayment, originalOrder);
      row.classList.remove('editing');
      currentlyEditingRow = null;
    });
  };

  rows.forEach((row) => {
    row.addEventListener('click', (event) => {
      const target = event.target;
      if (target.closest('.edit-status')) return;
      if (!target.closest('.order-id-link')) return;

      const dataset = row.dataset;

      if (modalOrderNo && dataset.orderNo) {
        modalOrderNo.textContent = dataset.orderNo;
      }

      if (modalOrderDate && dataset.orderTime) {
        modalOrderDate.textContent = dataset.orderTime;
      }

      if (modalOrderStatus && dataset.orderStatus) {
        clearStatusClasses(modalOrderStatus, 'order');
        modalOrderStatus.textContent = dataset.orderStatus;
        modalOrderStatus.classList.add(
          'status',
          'order',
          dataset.orderStatus.toLowerCase()
        );
      }

      if (modalPaymentStatus && dataset.paymentStatus) {
        clearStatusClasses(modalPaymentStatus, 'payment');
        modalPaymentStatus.textContent = dataset.paymentStatus;
        modalPaymentStatus.classList.add(
          'status',
          'payment',
          dataset.paymentStatus.toLowerCase()
        );
      }

      orderModal?.classList.add('open');
      orderModal?.setAttribute('aria-hidden', 'false');
    });
  });

  ordersBody?.addEventListener('click', (event) => {
    const icon = event.target.closest('.edit-status');
    if (!icon) return;
    event.stopPropagation();
    const row = icon.closest('.orders-row');
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
    if (filterPaymentSelect) filterPaymentSelect.value = '';
    if (filterOrderSelect) filterOrderSelect.value = '';
  });

  closeBtn?.addEventListener('click', () => {
    orderModal?.classList.remove('open');
    orderModal?.setAttribute('aria-hidden', 'true');
  });

  orderModal?.addEventListener('click', (event) => {
    if (event.target === orderModal) {
      orderModal.classList.remove('open');
      orderModal.setAttribute('aria-hidden', 'true');
    }
  });
});
