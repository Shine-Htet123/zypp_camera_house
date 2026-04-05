document.addEventListener('DOMContentLoaded', () => {
  const bundleInfo = document.querySelector('[data-bundle-info]');
  const addButton = document.querySelector('[data-add-bundle-to-cart]');
  const qtyValue = document.querySelector('[data-bundle-qty-value]');
  const showAlert = (options) => {
    if (typeof Swal !== 'undefined') {
      return Swal.fire(options);
    }

    window.alert(options?.text || options?.title || 'Done.');
    return Promise.resolve();
  };

  if (!bundleInfo || !addButton || !qtyValue || bundleInfo.dataset.bundleInitialized === 'true') {
    return;
  }

  bundleInfo.dataset.bundleInitialized = 'true';

  const openCustomerLoginPanel = () => {
    document.getElementById('user-icon')?.click();
  };

  const updateCartCount = (count) => {
    const cartCount = document.querySelector('.cart-count span');
    if (!cartCount || typeof count === 'undefined') {
      return;
    }

    cartCount.textContent = String(count);
  };

  const maxQty = Math.max(1, Number(bundleInfo.dataset.bundleStock || '1'));
  const cartUrl = bundleInfo.dataset.bundleCartUrl || (typeof window.appPath === 'function' ? window.appPath('/auth/cart_add.php') : '/auth/cart_add.php');
  const getQty = () => Math.max(1, Number(qtyValue.textContent || '1'));
  const setQty = (value) => {
    qtyValue.textContent = String(Math.min(maxQty, Math.max(1, value)));
  };

  document.querySelectorAll('[data-bundle-qty]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      const direction = button.getAttribute('data-bundle-qty');
      const nextValue = direction === 'increase' ? getQty() + 1 : getQty() - 1;
      setQty(nextValue);
    });
  });

  addButton.addEventListener('click', async (event) => {
    event.preventDefault();

    const bundleId = Number(bundleInfo.dataset.bundleId || '0');
    const quantity = getQty();

    if (bundleId <= 0) {
      return;
    }

    addButton.disabled = true;

    try {
      const body = new URLSearchParams({
        action: 'add_bundle',
        bundle_id: String(bundleId),
        quantity: String(quantity),
      });

      const response = await fetch(cartUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: body.toString(),
      });

      const payload = await response.json().catch(() => ({
        success: false,
        message: 'Failed to add bundle to cart.',
      }));
      if (!response.ok || !payload.success) {
        if (payload.login_required) {
          openCustomerLoginPanel();
        }
        throw new Error(payload.message || 'Failed to add bundle to cart.');
      }

      updateCartCount(payload.payload?.cart_count);

      showAlert({
        icon: 'success',
        title: 'Bundle added',
        text: payload.message || 'The bundle has been added to your cart.',
      });
    } catch (error) {
      showAlert({
        icon: 'error',
        title: 'Add to cart failed',
        text: error.message || 'Something went wrong.',
      });
    } finally {
      if (maxQty > 0) {
        addButton.disabled = false;
      }
    }
  });
});
