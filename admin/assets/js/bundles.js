document.addEventListener('DOMContentLoaded', () => {
    const config = window.adminBundlesConfig || {};
    const apiUrl = config.apiUrl || (window.appPath ? window.appPath('/admin/bundles-api.php') : '/admin/bundles-api.php');
    const searchForm = document.querySelector('.bundles-search-form');
    const searchInput = searchForm?.querySelector('input[name="q"]');
    const searchButton = searchForm?.querySelector('.admin-search-submit');
    const showAllButton = searchForm?.querySelector('.admin-show-all');
    const addModal = document.getElementById('addBundleModal');
    const editModal = document.getElementById('editBundleModal');
    const openAddBtn = document.getElementById('openAddBundle');
    const rows = Array.from(document.querySelectorAll('.bundles-row'));
    const emptyRow = document.querySelector('.bundles-empty-row');
    const editBundleId = document.getElementById('editBundleId');
    const deleteBundleButton = document.getElementById('deleteBundleButton');

    const openModal = (overlay) => {
        if (!overlay) return;
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
    };

    const closeModal = (overlay) => {
        if (!overlay) return;
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
    };

    const clearFeedback = (modal) => {
        const feedback = modal?.querySelector('[data-bundle-feedback]');
        if (feedback) {
            feedback.textContent = '';
            feedback.classList.remove('show');
        }
    };

    const setFeedback = (modal, message) => {
        const feedback = modal?.querySelector('[data-bundle-feedback]');
        if (feedback) {
            feedback.textContent = message || '';
            feedback.classList.toggle('show', Boolean(message));
            return;
        }

        if (message && window.Swal) {
            Swal.fire({
                icon: 'error',
                text: message,
            });
        }
    };

    const resetQuantities = (modal) => {
        modal?.querySelectorAll('.bundle-product-card').forEach((card) => {
            const qtyValue = card.querySelector('.qty-value');
            const input = card.querySelector('.bundle-item-input');
            if (qtyValue) qtyValue.textContent = '0';
            if (input) input.value = '0';
            card.classList.remove('selected');
            card.style.display = '';
        });
    };

    const syncCardQuantity = (card, quantity) => {
        const nextQuantity = Math.max(0, Number(quantity || 0));
        const qtyValue = card.querySelector('.qty-value');
        const input = card.querySelector('.bundle-item-input');
        if (qtyValue) qtyValue.textContent = String(nextQuantity);
        if (input) input.value = String(nextQuantity);
        card.classList.toggle('selected', nextQuantity > 0);
    };

    const resetForm = (modal) => {
        const form = modal?.querySelector('[data-bundle-form]');
        if (!form) return;
        form.reset();
        clearFeedback(modal);
        resetQuantities(modal);
        form.querySelectorAll('[data-bundle-search], [data-bundle-category-filter], [data-bundle-brand-filter], [data-bundle-availability-filter]').forEach((field) => {
            if ('value' in field) {
                field.value = '';
            }
        });
    };

    const applyModalFilters = (modal) => {
        const query = String(modal.querySelector('[data-bundle-search]')?.value || '').trim().toLowerCase();
        const category = String(modal.querySelector('[data-bundle-category-filter]')?.value || '').trim().toLowerCase();
        const brand = String(modal.querySelector('[data-bundle-brand-filter]')?.value || '').trim().toLowerCase();
        const availability = String(modal.querySelector('[data-bundle-availability-filter]')?.value || '').trim().toLowerCase();

        modal.querySelectorAll('.bundle-product-card').forEach((card) => {
            const name = String(card.dataset.productName || '').toLowerCase();
            const cardCategory = String(card.dataset.productCategory || '').toLowerCase();
            const cardBrand = String(card.dataset.productBrand || '').toLowerCase();
            const cardAvailability = String(card.dataset.productAvailability || '').toLowerCase();

            const matchesQuery = query === '' || name.includes(query);
            const matchesCategory = category === '' || cardCategory === category;
            const matchesBrand = brand === '' || cardBrand === brand;
            const matchesAvailability = availability === '' || cardAvailability === availability;
            card.style.display = matchesQuery && matchesCategory && matchesBrand && matchesAvailability ? '' : 'none';
        });
    };

    const parseItemsMap = (value) => {
        if (!value) return {};
        try {
            const parsed = JSON.parse(value);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    };

    const applyTableSearch = () => {
        const query = String(searchInput?.value || '').trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach((row) => {
            const haystack = [
                row.dataset.bundlePublicId || '',
                row.dataset.bundleName || '',
                row.cells[2]?.textContent || '',
            ].join(' ').toLowerCase();
            const matches = query === '' || haystack.includes(query);
            row.style.display = matches ? '' : 'none';
            if (matches) {
                visibleCount += 1;
            }
        });

        if (emptyRow) {
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
        }
    };

    const populateEditModal = (row) => {
        const form = editModal?.querySelector('[data-bundle-form]');
        if (!form || !row) return;

        resetForm(editModal);
        form.querySelector('input[name="bundle_id"]').value = String(row.dataset.bundleId || '');
        form.querySelector('input[name="bundle_name"]').value = row.dataset.bundleName || '';
        form.querySelector('select[name="discount_id"]').value = row.dataset.discountId || '';
        if (editBundleId) {
            editBundleId.textContent = row.dataset.bundlePublicId || '-';
        }

        const itemsMap = parseItemsMap(row.dataset.bundleItems || '');
        Object.entries(itemsMap).forEach(([productId, qty]) => {
            const card = editModal.querySelector(`.bundle-product-card[data-product-id="${CSS.escape(String(productId))}"]`);
            if (card) {
                syncCardQuantity(card, qty);
            }
        });
    };

    const reloadPage = () => {
        const nextUrl = new URL(window.location.href);
        const query = String(config.searchQuery || '').trim();
        if (query) {
            nextUrl.searchParams.set('q', query);
        } else {
            nextUrl.searchParams.delete('q');
        }
        window.location.href = nextUrl.toString();
    };

    const submitForm = async (modal) => {
        const form = modal?.querySelector('[data-bundle-form]');
        if (!form) return;

        clearFeedback(modal);
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const result = await response.json().catch(() => ({
                success: false,
                message: 'Unable to save bundle right now.',
            }));

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Unable to save bundle right now.');
            }

            if (window.Swal) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Saved',
                    text: result.message || 'Bundle saved successfully.',
                    timer: 1400,
                    showConfirmButton: false,
                });
            }

            reloadPage();
        } catch (error) {
            setFeedback(modal, error.message || 'Unable to save bundle right now.');
        } finally {
            if (submitButton) submitButton.disabled = false;
        }
    };

    const deleteBundle = async () => {
        const form = editModal?.querySelector('[data-bundle-form]');
        if (!form) return;

        const bundleId = String(form.querySelector('input[name="bundle_id"]')?.value || '').trim();
        if (!bundleId) return;

        const confirmation = window.Swal
            ? await Swal.fire({
                icon: 'warning',
                title: 'Delete bundle?',
                text: 'This will remove the bundle and its bundle items.',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                confirmButtonText: 'Delete',
            })
            : { isConfirmed: window.confirm('Delete this bundle?') };

        if (!confirmation.isConfirmed) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'delete_bundle');
            formData.append('bundle_id', bundleId);

            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const result = await response.json().catch(() => ({
                success: false,
                message: 'Unable to delete bundle right now.',
            }));

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Unable to delete bundle right now.');
            }

            if (window.Swal) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Deleted',
                    text: result.message || 'Bundle deleted successfully.',
                    timer: 1400,
                    showConfirmButton: false,
                });
            }

            reloadPage();
        } catch (error) {
            setFeedback(editModal, error.message || 'Unable to delete bundle right now.');
        }
    };

    document.querySelectorAll('.modal-close').forEach((button) => {
        button.addEventListener('click', () => closeModal(button.closest('.modal-overlay')));
    });

    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closeModal(overlay);
            }
        });
    });

    openAddBtn?.addEventListener('click', () => {
        resetForm(addModal);
        openModal(addModal);
    });

    rows.forEach((row) => {
        row.addEventListener('click', () => {
            populateEditModal(row);
            openModal(editModal);
        });
    });

    searchForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        applyTableSearch();
    });

    searchButton?.addEventListener('click', (event) => {
        event.preventDefault();
        applyTableSearch();
    });

    showAllButton?.addEventListener('click', (event) => {
        event.preventDefault();
        if (searchInput) {
            searchInput.value = '';
        }
        applyTableSearch();
    });

    document.querySelectorAll('.bundle-products-grid').forEach((grid) => {
        grid.addEventListener('click', (event) => {
            const button = event.target.closest('.qty-btn');
            if (!button) return;

            const card = button.closest('.bundle-product-card');
            if (!card) return;

            const currentQty = Number(card.querySelector('.qty-value')?.textContent || '0');
            const nextQty = button.classList.contains('plus') ? currentQty + 1 : Math.max(0, currentQty - 1);
            syncCardQuantity(card, nextQty);
        });
    });

    document.querySelectorAll('[data-bundle-form]').forEach((form) => {
        const modal = form.closest('.modal-overlay');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            await submitForm(modal);
        });

        form.querySelector('.btn-footer.discard')?.addEventListener('click', () => {
            closeModal(modal);
        });
    });

    deleteBundleButton?.addEventListener('click', deleteBundle);

    document.querySelectorAll('.bundle-modal').forEach((modal) => {
        modal.querySelector('[data-bundle-search]')?.addEventListener('input', () => applyModalFilters(modal));

        modal.querySelectorAll('.btn-filter').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                const dropdown = button.closest('.filter-wrapper')?.querySelector('.filter-dropdown');
                if (!dropdown) return;
                const isOpen = dropdown.classList.toggle('open');
                dropdown.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            });
        });

        modal.querySelectorAll('.btn-filter-apply').forEach((button) => {
            button.addEventListener('click', () => {
                applyModalFilters(modal);
                const dropdown = button.closest('.filter-dropdown');
                dropdown?.classList.remove('open');
                dropdown?.setAttribute('aria-hidden', 'true');
            });
        });

        modal.querySelectorAll('.btn-filter-clear').forEach((button) => {
            button.addEventListener('click', () => {
                modal.querySelectorAll('[data-bundle-category-filter], [data-bundle-brand-filter], [data-bundle-availability-filter]').forEach((field) => {
                    field.value = '';
                });
                modal.querySelector('[data-bundle-search]').value = '';
                applyModalFilters(modal);
            });
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.bundle-modal .filter-dropdown.open').forEach((dropdown) => {
            if (dropdown.contains(event.target) || event.target.closest('.btn-filter')) {
                return;
            }
            dropdown.classList.remove('open');
            dropdown.setAttribute('aria-hidden', 'true');
        });
    });

    applyTableSearch();
});
