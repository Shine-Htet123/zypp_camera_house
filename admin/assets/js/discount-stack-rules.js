const list = document.getElementById('priorityList');
let dragged = null;
let pointerDrag = null;
const footerBar = document.querySelector('.stack-footer');
const footerSave = footerBar?.querySelector('.btn-save');
const footerDiscard = footerBar?.querySelector('.btn-discard');
const stackForm = document.querySelector('[data-stack-form]');
const priorityOrderInput = document.querySelector('[data-priority-order]');
const stackRulesInput = document.querySelector('[data-stack-rules]');
let baselineOrder = [];
let baselineAllowed = new Map();

const hasChanges = () => {
    const currentOrder = getCurrentPriorityOrder();
    if (currentOrder.join('|') !== baselineOrder.join('|')) {
        return true;
    }

    const rows = Array.from(document.querySelectorAll('.stack-table tbody tr'));
    if (rows.length !== baselineAllowed.size) {
        return true;
    }

    return rows.some((row, index) => (row.dataset.allowed || 'No') !== (baselineAllowed.get(index) || 'No'));
};

const getCurrentPriorityOrder = () => Array.from(list?.querySelectorAll('.priority-item') || []).map((node) => node.dataset.type);

const syncPriorityDirtyState = () => {
    setFooterVisible(hasChanges());
};

const moveDraggedItem = (clientY) => {
    if (!list || !dragged) return;
    const items = Array.from(list.querySelectorAll('.priority-item')).filter((item) => item !== dragged);
    const target = items.find((item) => {
        const rect = item.getBoundingClientRect();
        return clientY < rect.top + rect.height / 2;
    });

    if (!target) {
        list.appendChild(dragged);
        return;
    }

    list.insertBefore(dragged, target);
};

const finishDrag = (item = dragged) => {
    if (!item) return;
    item.classList.remove('dragging');
    dragged = null;
    pointerDrag = null;
    updatePriorityNumbers();
    syncPriorityDirtyState();
};

if (list) {
    list.addEventListener('dragstart', (event) => {
        const item = event.target.closest('.priority-item');
        if (!item) return;
        dragged = item;
        item.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    list.addEventListener('dragend', (event) => {
        const item = event.target.closest('.priority-item');
        if (!item) return;
        finishDrag(item);
    });

    list.addEventListener('dragover', (event) => {
        event.preventDefault();
        const target = event.target.closest('.priority-item');
        if (!target || target === dragged) return;
        const rect = target.getBoundingClientRect();
        const next = (event.clientY - rect.top) > rect.height / 2;
        list.insertBefore(dragged, next ? target.nextSibling : target);
    });

    list.addEventListener('pointerdown', (event) => {
        const item = event.target.closest('.priority-item');
        if (!item || event.pointerType === 'mouse') return;

        pointerDrag = {
            pointerId: event.pointerId,
            item,
        };
        dragged = item;
        item.classList.add('dragging');
        item.setPointerCapture?.(event.pointerId);
    });

    list.addEventListener('pointermove', (event) => {
        if (!pointerDrag || pointerDrag.pointerId !== event.pointerId || pointerDrag.item !== dragged) {
            return;
        }

        event.preventDefault();
        moveDraggedItem(event.clientY);
    });

    list.addEventListener('pointerup', (event) => {
        if (!pointerDrag || pointerDrag.pointerId !== event.pointerId) {
            return;
        }

        pointerDrag.item.releasePointerCapture?.(event.pointerId);
        finishDrag(pointerDrag.item);
    });

    list.addEventListener('pointercancel', (event) => {
        if (!pointerDrag || pointerDrag.pointerId !== event.pointerId) {
            return;
        }

        pointerDrag.item.releasePointerCapture?.(event.pointerId);
        finishDrag(pointerDrag.item);
    });
}

const updatePriorityNumbers = () => {
    const items = list?.querySelectorAll('.priority-item');
    if (!items) return;
    items.forEach((item, index) => {
        const number = item.querySelector('.priority-number');
        if (number) number.textContent = String(index + 1);
    });
};

const stackTable = document.querySelector('.stack-table');
const setFooterVisible = (visible) => {
    if (!footerBar) return;
    footerBar.classList.toggle('visible', visible);
};

const captureState = () => {
    baselineOrder = Array.from(list?.querySelectorAll('.priority-item') || []).map((item) => item.dataset.type);
    baselineAllowed = new Map();
    document.querySelectorAll('.stack-table tbody tr').forEach((row, index) => {
        baselineAllowed.set(index, row.dataset.allowed || 'No');
    });
};

const restoreState = () => {
    if (list && baselineOrder.length) {
        const itemsByType = new Map();
        list.querySelectorAll('.priority-item').forEach((item) => {
            itemsByType.set(item.dataset.type, item);
        });
        baselineOrder.forEach((type) => {
            const item = itemsByType.get(type);
            if (item) list.appendChild(item);
        });
        updatePriorityNumbers();
    }

    document.querySelectorAll('.stack-table tbody tr').forEach((row, index) => {
        const allowed = baselineAllowed.get(index) || row.dataset.allowed || 'No';
        row.dataset.allowed = allowed;
        const allowedCell = row.querySelector('.allowed-cell');
        if (allowedCell) {
            allowedCell.innerHTML = '';
            allowedCell.appendChild(renderAllowedPill(allowed));
        }
        row.classList.remove('editing');
        const actions = row.querySelector('.stack-actions');
        if (actions) {
            actions.innerHTML = `
                <button class="icon-btn edit" type="button" aria-label="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
            `;
        }
    });
};

const renderAllowedPill = (value) => {
    const span = document.createElement('span');
    span.className = `allowed-pill ${value.toLowerCase()}`;
    span.textContent = value;
    return span;
};

const serializePayload = () => {
    if (priorityOrderInput) {
        priorityOrderInput.value = Array.from(list?.querySelectorAll('.priority-item') || [])
            .map((item) => item.dataset.type || '')
            .join(',');
    }

    if (stackRulesInput) {
        const rows = Array.from(document.querySelectorAll('.stack-table tbody tr')).map((row) => ({
            id: Number(row.dataset.ruleId || 0),
            type_a: row.dataset.typeA || '',
            type_b: row.dataset.typeB || '',
            allowed: (row.dataset.allowed || 'No') === 'Yes',
        }));
        stackRulesInput.value = JSON.stringify(rows);
    }
};

if (stackTable) {
    stackTable.addEventListener('click', (event) => {
        const editBtn = event.target.closest('.icon-btn.edit');
        const confirmBtn = event.target.closest('.icon-btn.confirm');
        const cancelBtn = event.target.closest('.icon-btn.cancel');
        const row = event.target.closest('tr');
        if (!row) return;

        if (editBtn) {
            if (row.classList.contains('editing')) return;
            row.classList.add('editing');

            const allowedCell = row.querySelector('.allowed-cell');
            const currentValue = row.dataset.allowed || 'No';
            row.dataset.originalAllowed = currentValue;

            const select = document.createElement('select');
            select.className = 'allowed-select';
            ['Yes', 'No'].forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                if (value === currentValue) option.selected = true;
                select.appendChild(option);
            });

            allowedCell.innerHTML = '';
            allowedCell.appendChild(select);

            const actions = row.querySelector('.stack-actions');
            actions.innerHTML = `
                <button class="icon-btn confirm" type="button" aria-label="Confirm">
                    <i class="fa-solid fa-check"></i>
                </button>
                <button class="icon-btn cancel" type="button" aria-label="Cancel">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;
        }

        if (confirmBtn) {
            const allowedCell = row.querySelector('.allowed-cell');
            const select = allowedCell.querySelector('select');
            const value = select?.value || 'No';
            row.dataset.allowed = value;
            allowedCell.innerHTML = '';
            allowedCell.appendChild(renderAllowedPill(value));

            const actions = row.querySelector('.stack-actions');
            actions.innerHTML = `
                <button class="icon-btn edit" type="button" aria-label="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
            `;
            row.classList.remove('editing');
            setFooterVisible(hasChanges());
        }

        if (cancelBtn) {
            const allowedCell = row.querySelector('.allowed-cell');
            const value = row.dataset.originalAllowed || row.dataset.allowed || 'No';
            allowedCell.innerHTML = '';
            allowedCell.appendChild(renderAllowedPill(value));

            const actions = row.querySelector('.stack-actions');
            actions.innerHTML = `
                <button class="icon-btn edit" type="button" aria-label="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
            `;
            row.classList.remove('editing');
        }
    });
}

captureState();

stackForm?.addEventListener('submit', () => {
    serializePayload();
    captureState();
    setFooterVisible(false);
});

footerDiscard?.addEventListener('click', () => {
    restoreState();
    setFooterVisible(hasChanges());
});

if (window.adminDiscountStackFlash && window.Swal) {
    Swal.fire({
        icon: window.adminDiscountStackFlash.type === 'error' ? 'error' : 'success',
        text: window.adminDiscountStackFlash.message || '',
        timer: 2200,
        showConfirmButton: false,
    });
}
