document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.summary-card');

  if (!form) {
    return;
  }

  const subtotalEl = form.querySelector('[data-cart-subtotal]');
  const discountEl = form.querySelector('[data-cart-discount]');

  const formatNumber = (value) => Number(value || 0).toLocaleString();

  const calculateRowDiscount = (subtotal, discountValue, discountType, quantity) => {
    if (discountType === 'fixed') {
      return Math.min(discountValue * quantity, subtotal);
    }

    return Math.round(subtotal * (discountValue / 100));
  };

  const clampQuantity = (value, max) => {
    const parsed = Number.parseInt(value, 10);

    if (Number.isNaN(parsed) || parsed < 1) {
      return 1;
    }

    return Math.min(parsed, max);
  };

  const syncStockNote = (row) => {
    const stock = Number.parseInt(row.dataset.stock || '0', 10);
    const note = row.querySelector('[data-stock-note]');
    const count = row.querySelector('[data-stock-count]');

    if (!note || !count) {
      return;
    }

    count.textContent = stock;
    note.classList.toggle('is-hidden', !(stock > 0 && stock <= 10));
  };

  const updateRow = (row) => {
    const qtyInput = row.querySelector('[data-qty-input]');

    if (!qtyInput) {
      return;
    }

    const stock = Number.parseInt(row.dataset.stock || '1', 10);
    const unitPrice = Number.parseInt(row.dataset.unitPrice || '0', 10);
    const discountValue = Number.parseInt(row.dataset.discountValue || '0', 10);
    const discountType = row.dataset.discountType || 'percentage';
    const quantity = clampQuantity(qtyInput.value, Math.max(stock, 1));
    const subtotal = unitPrice * quantity;
    const lineDiscount = calculateRowDiscount(subtotal, discountValue, discountType, quantity);
    const subtotalEl = row.querySelector('[data-line-subtotal]');
    const discountValueEl = row.querySelector('[data-discount-value]');
    const discountUnitEl = row.querySelector('[data-discount-unit]');

    qtyInput.value = quantity;

    if (subtotalEl) {
      subtotalEl.textContent = formatNumber(subtotal);
    }

    if (discountValueEl) {
      discountValueEl.textContent = formatNumber(discountValue);
    }

    if (discountUnitEl) {
      discountUnitEl.textContent = discountType === 'fixed' ? 'MMK' : '%';
    }

    row.dataset.lineDiscount = String(lineDiscount);
    row.dataset.lineSubtotal = String(subtotal);
    syncStockNote(row);
  };

  const updateTotals = () => {
    let subtotal = 0;
    let discount = 0;

    form.querySelectorAll('[data-cart-row]').forEach((row) => {
      subtotal += Number.parseInt(row.dataset.lineSubtotal || '0', 10);
      discount += Number.parseInt(row.dataset.lineDiscount || '0', 10);
    });

    if (subtotalEl) {
      subtotalEl.textContent = formatNumber(subtotal);
    }

    if (discountEl) {
      discountEl.textContent = formatNumber(discount);
    }
  };

  const removeRow = (row) => {
    if (!row) {
      return;
    }

    row.remove();
    updateTotals();
  };

  const confirmRemove = (row) => {
    if (!row) {
      return;
    }

    if (typeof Swal === 'undefined') {
      return;
    }

    Swal.fire({
      title: 'Remove this item?',
      text: 'This product will be removed from your cart.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Remove',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#d91c11',
      cancelButtonColor: '#8f8f8f',
    }).then((result) => {
      if (result.isConfirmed) {
        removeRow(row);
      }
    });
  };

  form.querySelectorAll('[data-cart-row]').forEach((row) => {
    updateRow(row);
  });

  updateTotals();

  form.addEventListener('click', (event) => {
    const actionButton = event.target.closest('[data-qty-action]');
    const removeButton = event.target.closest('[data-remove-row]');

    if (removeButton) {
      confirmRemove(removeButton.closest('[data-cart-row]'));
      return;
    }

    if (!actionButton) {
      return;
    }

    const row = actionButton.closest('[data-cart-row]');
    const qtyInput = row?.querySelector('[data-qty-input]');

    if (!row || !qtyInput) {
      return;
    }

    const max = Math.max(Number.parseInt(row.dataset.stock || '1', 10), 1);
    const current = clampQuantity(qtyInput.value, max);
    const next = actionButton.dataset.qtyAction === 'increase' ? current + 1 : current - 1;

    qtyInput.value = clampQuantity(next, max);
    updateRow(row);
    updateTotals();
  });

  form.addEventListener('input', (event) => {
    if (!event.target.matches('[data-qty-input]')) {
      return;
    }

    const row = event.target.closest('[data-cart-row]');

    if (!row) {
      return;
    }

    updateRow(row);
    updateTotals();
  });
});
