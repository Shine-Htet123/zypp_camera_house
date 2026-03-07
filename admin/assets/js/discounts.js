const overlay = document.querySelector('[data-modal-overlay]');
const openButtons = document.querySelectorAll('[data-modal-open]');
const closeButtons = document.querySelectorAll('[data-modal-close]');
const editModal = document.querySelector('[data-modal="edit-discount"]');
const addModal = document.querySelector('[data-modal="add-discount"]');

const valueTypeSelectors = document.querySelectorAll('.discount-modal .value-type');

const updateValueSuffix = (modal) => {
    const valueType = modal.querySelector('.value-type');
    const suffix = modal.querySelector('.value-suffix');
    if (!valueType || !suffix) return;
    suffix.textContent = valueType.value === 'Fixed' ? 'MMK' : '%';
};

const normalizeDateInput = (value) => {
    if (!value) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
    const parts = value.split('/');
    if (parts.length === 3) {
        const [day, month, year] = parts;
        if (day && month && year) {
            return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
        }
    }
    return '';
};

const modalState = new WeakMap();

const captureState = (modal) => {
    const fields = Array.from(modal.querySelectorAll('input, select, textarea'));
    const state = fields.map((field) => ({
        field,
        value: field.type === 'checkbox' ? field.checked : field.value
    }));
    modalState.set(modal, state);
};

const isDirty = (modal) => {
    const state = modalState.get(modal);
    if (!state) return false;
    return state.some((item) => {
        if (!item.field) return false;
        const current = item.field.type === 'checkbox' ? item.field.checked : item.field.value;
        return current !== item.value;
    });
};

const restoreState = (modal) => {
    const state = modalState.get(modal);
    if (!state) return;
    state.forEach((item) => {
        if (!item.field) return;
        if (item.field.type === 'checkbox') {
            item.field.checked = item.value;
        } else {
            item.field.value = item.value;
        }
    });
};

const bindDirtyTracking = (modal) => {
    const fields = modal.querySelectorAll('input, select, textarea');
    fields.forEach((field) => {
        field.addEventListener('input', () => {
            modal.classList.toggle('dirty', isDirty(modal));
        });
        field.addEventListener('change', () => {
            modal.classList.toggle('dirty', isDirty(modal));
        });
    });
};

const bindFooterActions = (modal) => {
    const saveBtn = modal.querySelector('.btn-save');
    const discardBtn = modal.querySelector('.btn-discard');

    saveBtn?.addEventListener('click', () => {
        captureState(modal);
        modal.classList.remove('dirty');
    });

    discardBtn?.addEventListener('click', () => {
        restoreState(modal);
        modal.classList.remove('dirty');
        closeModals();
    });
};

valueTypeSelectors.forEach((select) => {
    select.addEventListener('change', () => {
        const modal = select.closest('.discount-modal');
        updateValueSuffix(modal);
    });
});

const closeModals = () => {
    const openModals = document.querySelectorAll('.discount-modal.open');
    if (!overlay || openModals.length === 0) return;

    overlay.classList.remove('open');
    openModals.forEach((modal) => {
        modal.classList.add('closing');
    });

    window.setTimeout(() => {
        openModals.forEach((modal) => {
            modal.classList.remove('open', 'closing');
            modal.setAttribute('aria-hidden', 'true');
        });
        if (!document.querySelector('.discount-modal.open')) {
            overlay.style.display = '';
        }
    }, 200);
};

const openModal = (modalId) => {
    if (!overlay) return;
    const modal = document.querySelector(`[data-modal="${modalId}"]`);
    if (!modal) return;
    overlay.classList.add('open');
    modal.classList.add('open');
    modal.classList.remove('closing');
    modal.setAttribute('aria-hidden', 'false');
    updateValueSuffix(modal);
    captureState(modal);
    modal.classList.remove('dirty');
};

openButtons.forEach((button) => {
    button.addEventListener('click', () => openModal(button.dataset.modalOpen));
});

closeButtons.forEach((button) => {
    button.addEventListener('click', closeModals);
});

overlay?.addEventListener('click', (event) => {
    if (event.target === overlay) {
        closeModals();
    }
});

document.querySelectorAll('.discount-row').forEach((row) => {
    row.addEventListener('click', () => {
        if (!editModal) return;
        editModal.querySelector('[data-discount-id]').textContent = row.dataset.id || '--';

        editModal.querySelector('[data-field="title"]').value = row.dataset.title || '';
        editModal.querySelector('[data-field="discount-type"]').value = row.dataset.discountType || '';
        editModal.querySelector('[data-field="value-type"]').value = row.dataset.valueType || 'Percentage';
        editModal.querySelector('[data-field="value"]').value = row.dataset.value?.replace(' MMK', '') || '';
        editModal.querySelector('[data-field="start"]').value = normalizeDateInput(row.dataset.start);
        editModal.querySelector('[data-field="end"]').value = normalizeDateInput(row.dataset.end);
        editModal.querySelector('[data-field="status"]').value = row.dataset.status || 'Active';

        const users = (row.dataset.users || '').split('|').map((user) => user.trim());
        editModal.querySelectorAll('[data-field="users"] input[type="checkbox"]').forEach((checkbox) => {
            checkbox.checked = users.includes(checkbox.value);
        });

        openModal('edit-discount');
    });
});

const applyBtn = document.querySelector('.discount-modal[data-modal="edit-discount"] .btn-apply');
applyBtn?.addEventListener('click', () => {
    const id = editModal?.querySelector('[data-discount-id]')?.textContent?.trim() || '';
    const target = id ? `/admin/apply-discounts.php?id=${encodeURIComponent(id)}` : '/admin/apply-discounts.php';
    window.location.href = target;
});

const filterToggle = document.querySelector('.btn-filter');
const filterDropdown = document.querySelector('.filter-dropdown');
const filterClear = document.querySelector('.filter-clear');

const closeFilter = () => {
    if (!filterDropdown || !filterToggle) return;
    filterDropdown.classList.remove('open');
    filterDropdown.setAttribute('aria-hidden', 'true');
    filterToggle.setAttribute('aria-expanded', 'false');
};

const toggleFilter = () => {
    if (!filterDropdown || !filterToggle) return;
    const isOpen = filterDropdown.classList.toggle('open');
    filterDropdown.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    filterToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
};

filterToggle?.addEventListener('click', (event) => {
    event.stopPropagation();
    toggleFilter();
});

filterClear?.addEventListener('click', () => {
    if (!filterDropdown) return;
    filterDropdown.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.checked = false;
    });
});

document.addEventListener('click', (event) => {
    if (!filterDropdown || !filterToggle) return;
    if (filterDropdown.contains(event.target) || filterToggle.contains(event.target)) return;
    closeFilter();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeFilter();
    }
});

// default suffixes + binding
document.querySelectorAll('.discount-modal').forEach((modal) => {
    updateValueSuffix(modal);
    bindDirtyTracking(modal);
    bindFooterActions(modal);
});
