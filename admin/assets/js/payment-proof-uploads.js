document.addEventListener('DOMContentLoaded', () => {
  const paymentProofApiUrl = window.appPath ? window.appPath('/admin/payment-proof-api.php') : '/admin/payment-proof-api.php';
  const orderModal = document.getElementById('proofOrderModal');
  const proofModal = document.getElementById('proofImageModal');
  const searchForm = document.querySelector('.proof-search-form');
  const modalOrderNo = document.getElementById('modalOrderNo');
  const modalOrderDate = document.getElementById('modalOrderDate');
  const modalOrderStatus = document.getElementById('modalOrderStatus');
  const modalPaymentStatus = document.getElementById('modalPaymentStatus');
  const modalShipName = document.getElementById('modalShipName');
  const modalShipEmail = document.getElementById('modalShipEmail');
  const modalShipPhone = document.getElementById('modalShipPhone');
  const modalShipAddress = document.getElementById('modalShipAddress');
  const modalAdditionalNote = document.getElementById('modalAdditionalNote');
  const modalSummaryTable = document.getElementById('proofOrderSummaryTable');
  const modalPaymentId = document.getElementById('modalPaymentId');
  const proofImage = document.getElementById('proofImage');
  const downloadProof = document.getElementById('downloadProof');
  const copyProof = document.getElementById('copyProof');
  const selectAll = document.getElementById('selectAllProofs');
  const tableBody = document.querySelector('.proof-table-grid tbody');
  const bulkApprove = document.querySelector('.btn-approve');
  const bulkReject = document.querySelector('.btn-reject');
  const bulkRequest = document.querySelector('.btn-request');
  const filterButton = document.querySelector('.btn-filter');
  const filterDropdown = document.getElementById('proofFilterDropdown');
  const filterStatus = document.getElementById('filterProofStatus');
  const filterMethod = document.getElementById('filterProofMethod');
  const filterReset = document.getElementById('filterProofReset');
  const filterApply = document.querySelector('.btn-filter-apply');
  const searchInput = document.getElementById('proofSearchInput');
  const searchButton = document.getElementById('proofSearchButton');
  const showAllButton = document.querySelector('.admin-show-all');
  const emptyRow = document.querySelector('.proof-empty-row');
  const getRowChecks = () => Array.from(document.querySelectorAll('.proof-check'));
  const getRows = () => Array.from(document.querySelectorAll('.proof-row'));

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  const renderProofRow = (proof) => `
    <tr
      class="proof-row"
      data-order-id="${Number(proof.order_id || 0)}"
      data-order-no="${escapeHtml(proof.order_no_display || '')}"
      data-order-date="${escapeHtml(proof.order_date_display || '')}"
      data-payment-status="${escapeHtml(proof.status_label || 'Pending')}"
      data-order-status="${escapeHtml(proof.order_status_label || 'Pending')}"
      data-payment-method="${escapeHtml(proof.payment_method || '')}"
      data-payment-id="${Number(proof.payment_id || 0)}"
    >
      <td><input type="checkbox" class="proof-check" value="${Number(proof.payment_id || 0)}"></td>
      <td>${Number(proof.no || 0)}</td>
      <td class="payment-id-cell">${escapeHtml(proof.payment_id_display || '')}</td>
      <td class="order-link order-id-link">${escapeHtml(proof.order_no_display || '')}</td>
      <td>${escapeHtml(proof.public_user_id || '')}</td>
      <td>${escapeHtml(proof.amount_display || '')}</td>
      <td>
        <span class="status ${(proof.status_label || 'Pending').toLowerCase()}">
          ${escapeHtml(proof.status_label || 'Pending')}
        </span>
      </td>
      <td>${escapeHtml(proof.payment_method || '')}</td>
      <td>
        <button type="button" class="proof-thumb" data-proof-src="${escapeHtml(proof.payment_proof_file || '')}">
          <img src="${escapeHtml(proof.payment_proof_file || '')}" alt="Payment proof thumbnail">
        </button>
      </td>
    </tr>
  `;

  const clearStatusClasses = (el, base) => {
    if (!el) return;
    const classes = ['pending', 'cancelled', 'confirmed', 'delivered', 'shipped', 'paid', 'unpaid'];
    if (base) {
      classes.unshift(base);
    }
    el.classList.remove(...classes);
  };

  const applyStatus = (el, base, value) => {
    if (!el || !value) return;
    clearStatusClasses(el, base);
    el.textContent = value;
    const classes = ['status', value.toLowerCase()];
    if (base) {
      classes.splice(1, 0, base);
    }
    el.classList.add(...classes);
  };

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

  const updateEmptyState = () => {
    if (!emptyRow) return;
    const visibleRows = getRows().filter((row) => row.style.display !== 'none');
    emptyRow.style.display = visibleRows.length === 0 ? '' : 'none';
  };

  const renumberRows = () => {
    getRows().forEach((row, index) => {
      if (row.children[1]) {
        row.children[1].textContent = String(index + 1);
      }
    });
  };

  const syncSelectAllState = () => {
    if (!selectAll) return;
    const visibleChecks = getRowChecks().filter((item) => item.closest('.proof-row')?.style.display !== 'none');
    const checked = visibleChecks.filter((item) => item.checked).length;
    selectAll.checked = checked === visibleChecks.length && visibleChecks.length > 0;
    selectAll.indeterminate = checked > 0 && checked < visibleChecks.length;
  };

  const refreshMethodOptions = () => {
    if (!filterMethod) return;

    const currentValue = filterMethod.value;
    const methods = Array.from(new Set(
      getRows()
        .map((row) => (row.dataset.paymentMethod || '').trim())
        .filter((value) => value !== '')
    )).sort((left, right) => left.localeCompare(right));

    filterMethod.innerHTML = '<option value="">All</option>'
      + methods.map((method) => `<option value="${escapeHtml(method)}">${escapeHtml(method)}</option>`).join('');

    filterMethod.value = methods.includes(currentValue) ? currentValue : '';
  };

  const mergeIncomingProofs = (proofs) => {
    if (!tableBody || !Array.isArray(proofs) || proofs.length === 0) return;

    proofs.slice().reverse().forEach((proof) => {
      const paymentId = String(proof.payment_id || '');
      if (paymentId === '') return;

      const existingRow = tableBody.querySelector(`.proof-row[data-payment-id="${paymentId}"]`);
      const selected = existingRow?.querySelector('.proof-check')?.checked === true;
      const wrapper = document.createElement('tbody');
      wrapper.innerHTML = renderProofRow(proof).trim();
      const nextRow = wrapper.querySelector('.proof-row');
      if (!nextRow) return;

      const nextCheckbox = nextRow.querySelector('.proof-check');
      if (nextCheckbox) {
        nextCheckbox.checked = selected;
      }

      if (existingRow) {
        existingRow.replaceWith(nextRow);
      } else {
        tableBody.insertBefore(nextRow, emptyRow?.nextSibling || tableBody.firstChild);
      }
    });

    renumberRows();
    refreshMethodOptions();
    syncSelectAllState();
    updateBulkButtons();
    applyFilters();
    window.AdminPaymentProofNotifications?.refresh?.();
  };

  const applyFilters = () => {
    const query = (searchInput?.value || '').trim().toLowerCase();
    const status = (filterStatus?.value || '').trim().toLowerCase();
    const method = (filterMethod?.value || '').trim().toLowerCase();

    getRows().forEach((row) => {
      const haystack = [
        row.dataset.orderNo,
        row.dataset.paymentMethod,
        row.querySelector('.payment-id-cell')?.textContent || '',
        row.children[4]?.textContent || '',
      ].join(' ').toLowerCase();

      const matchesQuery = query === '' || haystack.includes(query);
      const matchesStatus = status === '' || (row.dataset.paymentStatus || '').toLowerCase() === status;
      const matchesMethod = method === '' || (row.dataset.paymentMethod || '').toLowerCase() === method;

      row.style.display = matchesQuery && matchesStatus && matchesMethod ? '' : 'none';
    });

    updateEmptyState();
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
    const response = await fetch(`${paymentProofApiUrl}?action=order-detail&order_id=${encodeURIComponent(orderId)}`, {
      headers: { Accept: 'application/json' },
    });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Failed to load order details.');
    }
    return payload.data;
  };

  const populateOrderModal = (detail) => {
    if (modalOrderNo) modalOrderNo.textContent = detail.order_no_display || '#-';
    if (modalOrderDate) modalOrderDate.textContent = detail.order_date_display || '-';
    if (modalShipName) modalShipName.textContent = detail.delivery_name || '-';
    if (modalShipEmail) modalShipEmail.textContent = detail.delivery_email || '-';
    if (modalShipPhone) modalShipPhone.textContent = detail.delivery_phone || '-';
    if (modalShipAddress) modalShipAddress.textContent = detail.delivery_address || '-';
    if (modalAdditionalNote) modalAdditionalNote.textContent = detail.additional_note || '-';
    applyStatus(modalOrderStatus, 'order', detail.order_status_label || 'Pending');
    applyStatus(modalPaymentStatus, 'payment', detail.payment_status_label || 'Pending');
    renderSummary(detail);
  };

  const updateBulkButtons = () => {
    const checked = getRowChecks().filter((item) => item.checked).length;
    const enabled = checked > 0;
    [bulkApprove, bulkReject, bulkRequest].forEach((btn) => {
      if (!btn) return;
      btn.disabled = !enabled;
    });
  };

  const selectedPaymentIds = () =>
    getRowChecks().filter((item) => item.checked).map((item) => item.value);

  const updateSelectedRowsStatus = (paymentIds, paymentStatus) => {
    getRows().forEach((row) => {
      if (!paymentIds.includes(row.dataset.paymentId)) return;
      row.dataset.paymentStatus = paymentStatus;
      const badge = row.querySelector('.status');
      applyStatus(badge, '', paymentStatus);
    });
  };

  const submitBulkAction = async (statusAction) => {
    const paymentIds = selectedPaymentIds();
    if (paymentIds.length === 0) {
      return;
    }

    const formData = new FormData();
    formData.append('action', 'bulk-status');
    formData.append('status_action', statusAction);
    paymentIds.forEach((id) => formData.append('payment_ids[]', id));

    const response = await fetch(paymentProofApiUrl, {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json' },
    });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Failed to update payment proofs.');
    }

    updateSelectedRowsStatus(paymentIds, payload.data.payment_status);
    getRowChecks().forEach((item) => { item.checked = false; });
    if (selectAll) {
      selectAll.checked = false;
      selectAll.indeterminate = false;
    }
    updateBulkButtons();
    applyFilters();
  };

  document.addEventListener('click', async (event) => {
    const orderLink = event.target.closest('.order-id-link');
    if (orderLink) {
      const row = orderLink.closest('.proof-row');
      if (!row) return;

      try {
        const detail = await loadOrderDetail(row.dataset.orderId || '');
        populateOrderModal(detail);
        openModal(orderModal);
      } catch (error) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Load failed', text: error.message });
        }
      }
      return;
    }

    const proofButton = event.target.closest('.proof-thumb');
    if (!proofButton) return;

    const row = proofButton.closest('.proof-row');
    const src = proofButton.dataset.proofSrc;
    if (proofImage) {
      proofImage.src = src || '';
      proofImage.alt = 'Payment proof';
    }
    if (downloadProof) {
      downloadProof.href = src || '#';
    }
    if (modalPaymentId && row) {
      modalPaymentId.textContent = row.querySelector('.payment-id-cell')?.textContent.trim() || '-';
    }
    openModal(proofModal);
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
      // Fallback to URL copy
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

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      getRowChecks().forEach((checkbox) => {
        if (checkbox.closest('.proof-row')?.style.display === 'none') return;
        checkbox.checked = selectAll.checked;
      });
      updateBulkButtons();
    });
  }

  document.addEventListener('change', (event) => {
    if (!event.target.matches('.proof-check')) return;
    syncSelectAllState();
    updateBulkButtons();
  });

  [bulkApprove, bulkReject, bulkRequest].forEach((button) => {
    button?.addEventListener('click', async () => {
      const action = button.classList.contains('btn-approve')
        ? 'approve'
        : button.classList.contains('btn-reject')
          ? 'reject'
          : 'request';

      try {
        await submitBulkAction(action);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Updated',
            text: 'Payment proof statuses have been updated.',
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
  });

  const toggleFilterDropdown = () => {
    if (!filterDropdown) return;
    const isOpen = filterDropdown.classList.contains('open');
    filterDropdown.classList.toggle('open', !isOpen);
    filterDropdown.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
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
    if (filterStatus) filterStatus.value = '';
    if (filterMethod) filterMethod.value = '';
    if (searchInput) searchInput.value = '';
    applyFilters();
  });

  searchInput?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      applyFilters();
    }
  });

  filterButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    toggleFilterDropdown();
  });

  filterApply?.addEventListener('click', () => {
    applyFilters();
    filterDropdown?.classList.remove('open');
    filterDropdown?.setAttribute('aria-hidden', 'true');
  });

  document.addEventListener('click', (event) => {
    if (!filterDropdown || !filterDropdown.classList.contains('open')) return;
    if (event.target.closest('.filter-dropdown') || event.target.closest('.btn-filter')) return;
    filterDropdown.classList.remove('open');
    filterDropdown.setAttribute('aria-hidden', 'true');
  });

  filterReset?.addEventListener('click', () => {
    if (filterStatus) filterStatus.value = '';
    if (filterMethod) filterMethod.value = '';
    if (searchInput) searchInput.value = '';
    applyFilters();
  });

  orderModal?.querySelector('.modal-close')?.addEventListener('click', () => closeModal(orderModal));
  proofModal?.querySelector('.modal-close')?.addEventListener('click', () => closeModal(proofModal));

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

  window.addEventListener('admin:payment-proof-notifications', (event) => {
    const proofs = event.detail?.proofs;
    if (!Array.isArray(proofs) || proofs.length === 0) {
      return;
    }

    mergeIncomingProofs(proofs);
  });

  updateBulkButtons();
  renumberRows();
  refreshMethodOptions();
  applyFilters();
});
