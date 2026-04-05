document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.summary-card');
  const flash = window.__cartFlash || null;
  const dynamicContainer = form?.querySelector('[data-cart-dynamic]') || null;
  const checkoutActions = document.querySelector('[data-cart-checkout-actions]');
  const cartActionUrl =
    form?.getAttribute('action') ||
    (typeof window.appPath === 'function' ? window.appPath('/cart.php') : '/cart.php');

  const showAlert = (options) => {
    if (typeof Swal !== 'undefined') {
      return Swal.fire(options);
    }

    const fallbackMessage = options?.text || options?.title || 'Done.';
    window.alert(fallbackMessage);
    return Promise.resolve({ isConfirmed: true });
  };

  if (!form) {
    return;
  }

  if (flash && flash.message) {
    showAlert({
      icon: flash.type === 'success' ? 'success' : 'error',
      title: flash.type === 'success' ? 'Updated' : 'Cart',
      text: flash.message,
    });
  }

  const clampQuantity = (value, max) => {
    const parsed = Number.parseInt(value, 10);

    if (Number.isNaN(parsed) || parsed < 1) {
      return 1;
    }

    return Math.min(parsed, max);
  };

  const syncNavbarCount = (count) => {
    const cartCount = document.querySelector('.cart-count span');
    if (cartCount && typeof count !== 'undefined') {
      cartCount.textContent = String(count);
    }
  };

  const syncCartFromPayload = (payload) => {
    if (!payload) {
      return;
    }

    if (dynamicContainer && typeof payload.cart_html === 'string') {
      dynamicContainer.innerHTML = payload.cart_html;
    }

    if (checkoutActions && typeof payload.checkout_html === 'string') {
      checkoutActions.innerHTML = payload.checkout_html;
    }

    syncNavbarCount(payload.cart_count);
  };

  const postCartAction = async (payload) => {
    const response = await fetch(cartActionUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: new URLSearchParams(payload).toString(),
    });

    const data = await response.json().catch(() => ({
      success: false,
      message: 'Cart update failed.',
    }));

    if (!response.ok || !data.success) {
      if (data.login_required) {
        document.getElementById('user-icon')?.click();
      }
      throw new Error(data.message || 'Cart update failed.');
    }

    return data.payload || {};
  };

  const removeRow = async (row) => {
    if (!row) {
      return;
    }

    const cartItemId = row.dataset.cartItemId || '';
    const bundleId = row.dataset.bundleId || '';
    const rowType = row.dataset.rowType || 'product';
    const payload = await postCartAction({
      action: 'remove_item',
      ...(rowType === 'bundle' ? { bundle_id: bundleId } : { cart_item_id: cartItemId }),
    });

    syncCartFromPayload(payload);

    showAlert({
      icon: 'success',
      title: 'Removed',
      text: 'The product was removed from your cart.',
    });
  };

  const confirmRemove = (row) => {
    if (!row) {
      return;
    }

    if (typeof Swal === 'undefined') {
      const confirmed = window.confirm('This product will be removed from your cart.');
      if (confirmed) {
        removeRow(row).catch((error) => {
          showAlert({ icon: 'error', title: 'Remove failed', text: error.message });
        });
      }
      return;
    }

    showAlert({
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
        removeRow(row).catch((error) => {
          showAlert({ icon: 'error', title: 'Remove failed', text: error.message });
        });
      }
    });
  };

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
    const rowType = row.dataset.rowType || 'product';

    qtyInput.value = clampQuantity(next, max);
    postCartAction({
      action: 'update_item',
      ...(rowType === 'bundle'
        ? { bundle_id: row.dataset.bundleId || '' }
        : { cart_item_id: row.dataset.cartItemId || '' }),
      quantity: qtyInput.value,
    }).then((payload) => {
      syncCartFromPayload(payload);
    }).catch((error) => {
      showAlert({ icon: 'error', title: 'Update failed', text: error.message });
    });
  });

  form.addEventListener('input', (event) => {
    if (!event.target.matches('[data-qty-input]')) {
      return;
    }

    const row = event.target.closest('[data-cart-row]');

    if (!row) {
      return;
    }
    const rowType = row.dataset.rowType || 'product';
    postCartAction({
      action: 'update_item',
      ...(rowType === 'bundle'
        ? { bundle_id: row.dataset.bundleId || '' }
        : { cart_item_id: row.dataset.cartItemId || '' }),
      quantity: event.target.value,
    }).then((payload) => {
      syncCartFromPayload(payload);
    }).catch((error) => {
      showAlert({ icon: 'error', title: 'Update failed', text: error.message });
    });
  });

  let noteTimer = null;
  const noteField = form.querySelector('[data-cart-note]');
  noteField?.addEventListener('input', () => {
    if (noteTimer) {
      clearTimeout(noteTimer);
    }

    noteTimer = window.setTimeout(() => {
      postCartAction({
        action: 'save_note',
        additional_note: noteField.value,
      }).catch(() => {});
    }, 400);
  });
});
