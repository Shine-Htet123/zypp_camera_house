const tabs = document.querySelectorAll('.apply-tab');
const panes = document.querySelectorAll('.apply-pane');
const footerBar = document.querySelector('.apply-footer');
const footerSave = footerBar?.querySelector('.btn-save');
const footerDiscard = footerBar?.querySelector('.btn-discard');
let baseline = [];

const updateSelectAllState = (pane) => {
    const selectAll = pane.querySelector('[data-select-all]');
    if (!selectAll) return;
    const items = Array.from(pane.querySelectorAll('.apply-card input[type="checkbox"]'));
    if (items.length === 0) return;
    const allChecked = items.every((item) => item.checked);
    selectAll.checked = allChecked;
};

const updateGroupState = (groupRow) => {
    const groupCheckbox = groupRow.querySelector('.group-check input[type="checkbox"]');
    const items = Array.from(groupRow.querySelectorAll('.apply-card input[type="checkbox"]'));
    if (!groupCheckbox || items.length === 0) return;
    groupCheckbox.checked = items.every((item) => item.checked);
};

const setFooterVisible = (visible) => {
    if (!footerBar) return;
    footerBar.classList.toggle('visible', visible);
};

const captureState = () => {
    baseline = Array.from(document.querySelectorAll('.apply-pane input[type="checkbox"]')).map((checkbox) => ({
        checkbox,
        checked: checkbox.checked
    }));
};

const restoreState = () => {
    baseline.forEach(({ checkbox, checked }) => {
        checkbox.checked = checked;
        checkbox.closest('.apply-card')?.classList.toggle('selected', checked);
    });
    panes.forEach((pane) => updateSelectAllState(pane));
    document.querySelectorAll('.group-row').forEach((row) => updateGroupState(row));
};

tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        tabs.forEach((btn) => btn.classList.remove('active'));
        panes.forEach((pane) => pane.classList.remove('active'));
        tab.classList.add('active');
        const target = document.getElementById(tab.dataset.target);
        target?.classList.add('active');
    });
});

document.querySelectorAll('[data-select-all]').forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
        const target = checkbox.dataset.selectAll;
        const pane = document.getElementById(target);
        if (!pane) return;
        const items = pane.querySelectorAll('.apply-card input[type="checkbox"]');
        items.forEach((item) => {
            item.checked = checkbox.checked;
            item.closest('.apply-card')?.classList.toggle('selected', checkbox.checked);
        });
        pane.querySelectorAll('.group-row').forEach((row) => updateGroupState(row));
        setFooterVisible(true);
    });
});

document.querySelectorAll('.apply-card input[type="checkbox"]').forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
        const card = checkbox.closest('.apply-card');
        card?.classList.toggle('selected', checkbox.checked);
        const pane = checkbox.closest('.apply-pane');
        if (pane) updateSelectAllState(pane);
        const groupRow = checkbox.closest('.group-row');
        if (groupRow) updateGroupState(groupRow);
        setFooterVisible(true);
    });
});

// Sub-category dropdowns
document.querySelectorAll('.group-toggle').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const row = toggle.closest('.group-row');
        if (!row) return;
        row.classList.toggle('open');
    });
});

document.querySelectorAll('.group-check input[type="checkbox"]').forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
        const row = checkbox.closest('.group-row');
        if (!row) return;
        const items = row.querySelectorAll('.apply-card input[type="checkbox"]');
        items.forEach((item) => {
            item.checked = checkbox.checked;
            item.closest('.apply-card')?.classList.toggle('selected', checkbox.checked);
        });
        const pane = row.closest('.apply-pane');
        if (pane) updateSelectAllState(pane);
        setFooterVisible(true);
    });
});

// Initialize selection state
panes.forEach((pane) => updateSelectAllState(pane));
document.querySelectorAll('.group-row').forEach((row) => updateGroupState(row));
captureState();

footerSave?.addEventListener('click', () => {
    captureState();
    setFooterVisible(false);
});
footerDiscard?.addEventListener('click', () => {
    restoreState();
    setFooterVisible(false);
});
