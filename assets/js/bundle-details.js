document.addEventListener('DOMContentLoaded', () => {
  const bundleInfo = document.querySelector('[data-bundle-info]');
  const addButton = document.querySelector('[data-add-bundle-to-cart]');
  const qtyValue = document.querySelector('[data-bundle-qty-value]');

  if (!bundleInfo || !addButton || !qtyValue) {
    return;
  }

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
  const getQty = () => Math.max(1, Number(qtyValue.textContent || '1'));
  const setQty = (value) => {
    qtyValue.textContent = String(Math.min(maxQty, Math.max(1, value)));
  };

  document.querySelectorAll('[data-bundle-qty]').forEach((button) => {
    button.addEventListener('click', () => {
      const direction = button.getAttribute('data-bundle-qty');
      const nextValue = direction === 'increase' ? getQty() + 1 : getQty() - 1;
      setQty(nextValue);
    });
  });

  addButton.addEventListener('click', async () => {
    const bundleId = Number(bundleInfo.dataset.bundleId || '0');
    const quantity = getQty();

    try {
      const body = new URLSearchParams({
        action: 'add_bundle',
        bundle_id: String(bundleId),
        quantity: String(quantity),
      });

      const response = await fetch('/auth/cart_add.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        body: body.toString(),
      });

      const payload = await response.json();
      if (!response.ok || !payload.success) {
        if (payload.login_required) {
          openCustomerLoginPanel();
        }
        throw new Error(payload.message || 'Failed to add bundle to cart.');
      }

      updateCartCount(payload.payload?.cart_count);

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'success',
          title: 'Bundle added',
          text: payload.message || 'The bundle has been added to your cart.',
        });
      }
    } catch (error) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Add to cart failed',
          text: error.message,
        });
      }
    }
  });
});
