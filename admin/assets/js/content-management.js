document.addEventListener("DOMContentLoaded", () => {
    const pageSelect = document.querySelector("[data-page-select]");
    const sections = Array.from(document.querySelectorAll(".content-section"));
    const forms = Array.from(document.querySelectorAll("[data-content-form]"));
    const unsavedBar = document.querySelector("[data-unsaved-bar]");
    const saveButton = document.querySelector("[data-unsaved-save]");
    const discardButton = document.querySelector("[data-unsaved-discard]");

    let currentPage = pageSelect?.value || "";
    let dirtyForm = null;

    const getActiveSection = () => sections.find((section) => section.classList.contains("is-active")) || null;

    const getActiveForm = () => getActiveSection()?.querySelector("[data-content-form]") || null;

    const updateBar = () => {
        if (!unsavedBar) {
            return;
        }

        unsavedBar.classList.toggle("is-visible", Boolean(dirtyForm));
    };

    const markDirty = (form) => {
        dirtyForm = form;
        updateBar();
    };

    const clearDirty = () => {
        dirtyForm = null;
        updateBar();
    };

    const syncSection = () => {
        if (!pageSelect) {
            return;
        }

        const value = pageSelect.value;
        sections.forEach((section) => {
            section.classList.toggle("is-active", section.dataset.page === value);
        });

        currentPage = value;
        const url = new URL(window.location.href);
        url.searchParams.set("page", value);
        window.history.replaceState({}, "", url.toString());
    };

    pageSelect?.addEventListener("change", () => {
        if (dirtyForm) {
            pageSelect.value = currentPage;
            return;
        }

        syncSection();
    });

    forms.forEach((form) => {
        form.addEventListener("input", () => markDirty(form));
        form.addEventListener("change", () => markDirty(form));
        form.addEventListener("submit", () => clearDirty());
    });

    document.querySelectorAll("input[type='file'][data-preview-target]").forEach((input) => {
        input.addEventListener("change", () => {
            const file = input.files?.[0];
            const targetId = input.getAttribute("data-preview-target");
            if (!targetId) {
                return;
            }

            const form = input.closest("[data-content-form]");
            if (form) {
                markDirty(form);
            }

            if (!file) {
                return;
            }

            const target = document.getElementById(targetId);
            if (!target) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                if (target.tagName === "IMG") {
                    target.src = String(event.target?.result || "");
                    return;
                }

                const image = document.createElement("img");
                image.id = targetId;
                image.alt = "Preview";
                image.src = String(event.target?.result || "");
                target.replaceWith(image);
            };
            reader.readAsDataURL(file);
        });
    });

    saveButton?.addEventListener("click", () => {
        const form = dirtyForm || getActiveForm();
        form?.requestSubmit();
    });

    discardButton?.addEventListener("click", () => {
        window.location.reload();
    });

    syncSection();
    updateBar();
});
