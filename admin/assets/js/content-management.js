document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-content-root]');
    if (!root) return;

    const pageSelect = root.querySelector('[data-page-select]');
    const sections = Array.from(root.querySelectorAll('.content-section'));
    const unsavedBar = root.querySelector('[data-unsaved-bar]');
    const saveBtn = unsavedBar?.querySelector('.btn-save');
    const discardBtn = unsavedBar?.querySelector('.btn-discard');

    const tabButtons = Array.from(root.querySelectorAll('[data-banner-tab]'));
    const previewImage = root.querySelector('[data-banner-preview]');
    const uploadButton = root.querySelector('[data-banner-upload]');
    const deleteButton = root.querySelector('[data-banner-delete]');
    const fileInput = root.querySelector('[data-banner-file]');
    const buttonToggle = root.querySelector('[data-button-toggle]');
    const buttonConfig = root.querySelector('[data-button-config]');
    const fields = Array.from(root.querySelectorAll('[data-banner-field]'));

    let activeTab = tabButtons[0];

    const setDirty = () => {
        if (!unsavedBar) return;
        unsavedBar.classList.add('is-visible');
    };

    const clearDirty = () => {
        if (!unsavedBar) return;
        unsavedBar.classList.remove('is-visible');
    };

    const applyButtonVisibility = () => {
        if (!buttonToggle || !buttonConfig) return;
        const enabled = buttonToggle.value === 'yes';
        buttonConfig.classList.toggle('is-hidden', !enabled);
    };

    const applyBannerData = (tabButton) => {
        if (!tabButton) return;
        activeTab = tabButton;

        tabButtons.forEach((btn) => btn.classList.toggle('active', btn === tabButton));

        const data = tabButton.dataset;
        if (previewImage) {
            previewImage.src = data.image;
            previewImage.dataset.defaultSrc = data.image;
        }

        const setField = (name, value) => {
            const field = fields.find((item) => item.dataset.bannerField === name);
            if (field) field.value = value;
        };

        setField('title', data.title || '');
        setField('subtitle', data.subtitle || '');
        setField('title-color', data.titleColor || '#000000');
        setField('subtitle-color', data.subtitleColor || '#000000');
        setField('button1-text', data.button1Text || '');
        setField('button1-color', data.button1Color || '#000000');
        setField('button2-text', data.button2Text || '');
        setField('button2-color', data.button2Color || '#000000');

        if (buttonToggle) {
            buttonToggle.value = data.buttonEnabled || 'yes';
        }
        applyButtonVisibility();
    };

    tabButtons.forEach((tab) => {
        tab.addEventListener('click', () => {
            applyBannerData(tab);
            clearDirty();
        });
    });

    if (uploadButton && fileInput) {
        uploadButton.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            if (!file || !previewImage) return;
            const reader = new FileReader();
            reader.onload = () => {
                previewImage.src = reader.result;
                setDirty();
            };
            reader.readAsDataURL(file);
        });
    }

    if (deleteButton && previewImage) {
        deleteButton.addEventListener('click', () => {
            previewImage.src = previewImage.dataset.defaultSrc || previewImage.src;
            if (fileInput) fileInput.value = '';
            setDirty();
        });
    }

    if (buttonToggle) {
        buttonToggle.addEventListener('change', () => {
            applyButtonVisibility();
            setDirty();
        });
    }

    fields.forEach((field) => {
        field.addEventListener('input', setDirty);
        field.addEventListener('change', setDirty);
    });

    saveBtn?.addEventListener('click', () => {
        clearDirty();
    });

    discardBtn?.addEventListener('click', () => {
        applyBannerData(activeTab);
        clearDirty();
    });

    pageSelect?.addEventListener('change', () => {
        const value = pageSelect.value;
        sections.forEach((section) => {
            section.classList.toggle('is-active', section.dataset.page === value);
        });
        clearDirty();
    });

    applyBannerData(activeTab);
    applyButtonVisibility();
});
