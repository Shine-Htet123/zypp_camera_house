document.addEventListener('DOMContentLoaded', () => {
    const addModal = document.getElementById('addBundleModal');
    const editModal = document.getElementById('editBundleModal');
    const openAddBtn = document.getElementById('openAddBundle');
    const editBundleId = document.getElementById('editBundleId');
    const editBundleName = document.getElementById('editBundleName');
    const rows = document.querySelectorAll('.bundles-row');
    const modalState = new WeakMap();

    const captureState = (modal) => {
        if (!modal) return;
        const nameInput = modal.querySelector('.bundle-input');
        const qtyValues = Array.from(modal.querySelectorAll('.bundle-product-card')).map((card) => {
            const qtyEl = card.querySelector('.qty-value');
            return qtyEl ? qtyEl.textContent.trim() : '0';
        });
        modalState.set(modal, {
            name: nameInput ? nameInput.value : '',
            qtys: qtyValues,
        });
    };

    const restoreState = (modal) => {
        const state = modalState.get(modal);
        if (!state) return;
        const nameInput = modal.querySelector('.bundle-input');
        if (nameInput) {
            nameInput.value = state.name;
        }
        const cards = modal.querySelectorAll('.bundle-product-card');
        cards.forEach((card, index) => {
            const qtyEl = card.querySelector('.qty-value');
            if (qtyEl) {
                qtyEl.textContent = state.qtys[index] || '0';
            }
            syncCardSelection(card);
        });
    };

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        captureState(modal);
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    };

    openAddBtn?.addEventListener('click', () => {
        openModal(addModal);
    });

    rows.forEach((row) => {
        row.addEventListener('click', () => {
            if (editBundleId) {
                editBundleId.textContent = row.dataset.bundleId || 'BUN202601230001';
            }
            if (editBundleName) {
                editBundleName.value = row.dataset.bundleName || '';
            }
            openModal(editModal);
        });
    });

    document.querySelectorAll('.modal-close').forEach((button) => {
        button.addEventListener('click', () => {
            const overlay = button.closest('.modal-overlay');
            closeModal(overlay);
        });
    });

    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closeModal(overlay);
            }
        });
    });

    const closeAllFilters = (exceptWrapper) => {
        document.querySelectorAll('.filter-dropdown.open').forEach((dropdown) => {
            if (exceptWrapper && exceptWrapper.contains(dropdown)) return;
            dropdown.classList.remove('open');
            dropdown.setAttribute('aria-hidden', 'true');
        });
    };

    document.querySelectorAll('.filter-wrapper .btn-filter').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const wrapper = button.closest('.filter-wrapper');
            const dropdown = wrapper?.querySelector('.filter-dropdown');
            if (!dropdown) return;

            const isOpen = dropdown.classList.contains('open');
            closeAllFilters(wrapper);
            dropdown.classList.toggle('open', !isOpen);
            dropdown.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
        });
    });

    document.querySelectorAll('.btn-filter-clear').forEach((button) => {
        button.addEventListener('click', () => {
            const dropdown = button.closest('.filter-dropdown');
            if (!dropdown) return;
            dropdown.querySelectorAll('select').forEach((select) => {
                select.value = '';
            });
        });
    });

    document.addEventListener('click', (event) => {
        if (
            event.target.closest('.filter-dropdown') ||
            event.target.closest('.btn-filter')
        ) {
            return;
        }
        closeAllFilters();
    });

    const syncCardSelection = (card) => {
        const qtyEl = card.querySelector('.qty-value');
        if (!qtyEl) return;
        const qty = parseInt(qtyEl.textContent, 10) || 0;
        card.classList.toggle('selected', qty > 0);
    };

    document.querySelectorAll('.bundle-products-grid').forEach((grid) => {
        grid.addEventListener('click', (event) => {
            const button = event.target.closest('.qty-btn');
            if (!button) return;
            const card = button.closest('.bundle-product-card');
            if (!card) return;
            const qtyEl = card.querySelector('.qty-value');
            if (!qtyEl) return;
            let qty = parseInt(qtyEl.textContent, 10) || 0;

            if (button.classList.contains('plus')) {
                qty += 1;
            }

            if (button.classList.contains('minus')) {
                qty = Math.max(0, qty - 1);
            }

            qtyEl.textContent = String(qty);
            syncCardSelection(card);
        });
    });

    document.querySelectorAll('.bundle-product-card').forEach((card) => {
        syncCardSelection(card);
    });

    const bindFooterActions = (modal) => {
        if (!modal) return;
        const saveBtn = modal.querySelector('.btn-footer.save');
        const discardBtn = modal.querySelector('.btn-footer.discard');

        saveBtn?.addEventListener('click', () => {
            captureState(modal);
            closeModal(modal.closest('.modal-overlay'));
        });

        discardBtn?.addEventListener('click', () => {
            restoreState(modal);
            closeModal(modal.closest('.modal-overlay'));
        });
    };

    document.querySelectorAll('.bundle-modal').forEach((modal) => {
        bindFooterActions(modal);
    });
});
