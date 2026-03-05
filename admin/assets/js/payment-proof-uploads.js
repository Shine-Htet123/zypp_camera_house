document.addEventListener('DOMContentLoaded', () => {
  const orderModal = document.getElementById('proofOrderModal');
  const proofModal = document.getElementById('proofImageModal');
  const modalOrderNo = document.getElementById('modalOrderNo');
  const modalOrderDate = document.getElementById('modalOrderDate');
  const modalOrderStatus = document.getElementById('modalOrderStatus');
  const modalPaymentStatus = document.getElementById('modalPaymentStatus');
  const modalPaymentId = document.getElementById('modalPaymentId');
  const proofImage = document.getElementById('proofImage');
  const downloadProof = document.getElementById('downloadProof');
  const copyProof = document.getElementById('copyProof');
  const selectAll = document.getElementById('selectAllProofs');
  const rowChecks = Array.from(document.querySelectorAll('.proof-check'));
  const bulkApprove = document.querySelector('.btn-approve');
  const bulkReject = document.querySelector('.btn-reject');
  const bulkRequest = document.querySelector('.btn-request');
  const filterButton = document.querySelector('.btn-filter');
  const filterDropdown = document.getElementById('proofFilterDropdown');
  const filterStatus = document.getElementById('filterProofStatus');
  const filterMethod = document.getElementById('filterProofMethod');
  const filterReset = document.getElementById('filterProofReset');

  const openModal = (modal) => {
    if (!modal) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  };

  const closeModal = (modal) => {
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  };

  const clearStatusClasses = (el, base) => {
    if (!el) return;
    el.classList.remove(
      base,
      'pending',
      'cancelled',
      'confirmed',
      'delivered',
      'shipped',
      'paid',
      'unpaid'
    );
  };

  const applyStatus = (el, base, value) => {
    if (!el || !value) return;
    clearStatusClasses(el, base);
    el.textContent = value;
    el.classList.add('status', base, value.toLowerCase());
  };

  document.querySelectorAll('.order-id-link').forEach((link) => {
    link.addEventListener('click', () => {
      const row = link.closest('.proof-row');
      if (!row) return;

      if (modalOrderNo) {
        modalOrderNo.textContent = row.dataset.orderNo || link.textContent.trim();
      }

      if (modalOrderDate && row.dataset.orderDate) {
        modalOrderDate.textContent = row.dataset.orderDate;
      }

      if (modalOrderStatus && row.dataset.orderStatus) {
        applyStatus(modalOrderStatus, 'order', row.dataset.orderStatus);
      }

      if (modalPaymentStatus && row.dataset.paymentStatus) {
        applyStatus(modalPaymentStatus, 'payment', row.dataset.paymentStatus);
      }

      openModal(orderModal);
    });
  });

  document.querySelectorAll('.proof-thumb').forEach((button) => {
    button.addEventListener('click', () => {
      const row = button.closest('.proof-row');
      const src = button.dataset.proofSrc;
      if (proofImage) {
        proofImage.src = src || '';
        proofImage.alt = 'Payment proof';
      }
      if (downloadProof) {
        downloadProof.href = src || '#';
      }
      if (modalPaymentId && row) {
        const paymentCell = row.querySelector('.payment-id-cell');
        modalPaymentId.textContent = paymentCell?.textContent.trim() || '';
      }
      openModal(proofModal);
    });
  });

  const handleCopy = async () => {
    if (!proofImage || !copyProof) return;
    const src = proofImage.getAttribute('src');
    if (!src) return;

    const showCopied = () => {
      copyProof.classList.add('copied');
      if (copyProof.dataset.resetTimer) {
        clearTimeout(Number(copyProof.dataset.resetTimer));
      }
      const timer = window.setTimeout(() => {
        copyProof.classList.remove('copied');
      }, 1600);
      copyProof.dataset.resetTimer = String(timer);
    };

    try {
      if (navigator.clipboard && window.ClipboardItem) {
        const response = await fetch(src);
        const blob = await response.blob();
        const type = blob.type || 'image/png';
        await navigator.clipboard.write([new ClipboardItem({ [type]: blob })]);
        showCopied();
        return;
      }
    } catch (_) {
      // Fallback to copying URL
    }

    try {
      if (navigator.clipboard) {
        await navigator.clipboard.writeText(src);
        showCopied();
      }
    } catch (_) {
      // ignore
    }
  };

  copyProof?.addEventListener('click', handleCopy);

  orderModal?.querySelector('.modal-close')?.addEventListener('click', () => {
    closeModal(orderModal);
  });

  proofModal?.querySelector('.modal-close')?.addEventListener('click', () => {
    closeModal(proofModal);
  });

  orderModal?.addEventListener('click', (event) => {
    if (event.target === orderModal) {
      closeModal(orderModal);
    }
  });

  proofModal?.addEventListener('click', (event) => {
    if (event.target === proofModal) {
      closeModal(proofModal);
    }
  });

  const updateBulkButtons = () => {
    const checked = rowChecks.filter((item) => item.checked).length;
    const enable = checked > 0;
    [bulkApprove, bulkReject, bulkRequest].forEach((btn) => {
      if (!btn) return;
      btn.disabled = !enable;
    });
  };

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      rowChecks.forEach((checkbox) => {
        checkbox.checked = selectAll.checked;
      });
      updateBulkButtons();
    });
  }

  rowChecks.forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
      if (!selectAll) return;
      const total = rowChecks.length;
      const checked = rowChecks.filter((item) => item.checked).length;
      selectAll.checked = checked === total;
      selectAll.indeterminate = checked > 0 && checked < total;
      updateBulkButtons();
    });
  });

  updateBulkButtons();

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

  filterReset?.addEventListener('click', () => {
    if (filterStatus) filterStatus.value = '';
    if (filterMethod) filterMethod.value = '';
  });
});
