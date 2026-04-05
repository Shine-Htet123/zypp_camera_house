document.addEventListener("DOMContentLoaded", () => {
    const pageSelect = document.querySelector("[data-page-select]");
    const sections = Array.from(document.querySelectorAll(".content-section"));
    const forms = Array.from(document.querySelectorAll("[data-content-form]"));
    const unsavedBar = document.querySelector("[data-unsaved-bar]");
    const saveButton = document.querySelector("[data-unsaved-save]");
    const discardButton = document.querySelector("[data-unsaved-discard]");
    const locationsEditor = document.querySelector("[data-delivery-locations-editor]");
    const stateRows = document.querySelector("[data-state-rows]");
    const cityRows = document.querySelector("[data-city-rows]");
    const townshipRows = document.querySelector("[data-township-rows]");
    const stateAddButton = document.querySelector("[data-state-add]");
    const cityAddButton = document.querySelector("[data-city-add]");
    const townshipAddButton = document.querySelector("[data-township-add]");
    const stateSearchInput = document.querySelector("[data-state-search]");
    const cityFilterState = document.querySelector("[data-city-filter-state]");
    const citySearchInput = document.querySelector("[data-city-search]");
    const townshipFilterState = document.querySelector("[data-township-filter-state]");
    const townshipFilterCity = document.querySelector("[data-township-filter-city]");
    const townshipSearchInput = document.querySelector("[data-township-search]");
    const stateCount = document.querySelector("[data-state-count]");
    const cityCount = document.querySelector("[data-city-count]");
    const townshipCount = document.querySelector("[data-township-count]");
    const stateEmpty = document.querySelector("[data-state-empty]");
    const cityEmpty = document.querySelector("[data-city-empty]");
    const townshipEmpty = document.querySelector("[data-township-empty]");
    const stateTemplate = document.getElementById("delivery-location-state-template");
    const cityTemplate = document.getElementById("delivery-location-city-template");
    const townshipTemplate = document.getElementById("delivery-location-township-template");
    const paymentMethodTemplate = document.getElementById("payment-method-template");
    const paymentMethodList = document.querySelector("[data-payment-method-list]");
    const paymentMethodAddButton = document.querySelector("[data-payment-method-add]");
    const heroSlideEditor = document.querySelector("[data-hero-slide-editor]");
    const heroSlideTabs = heroSlideEditor?.querySelector("[data-hero-slide-tabs]");
    const heroSlidePanels = heroSlideEditor?.querySelector("[data-hero-slide-panels]");
    const heroSlideAddButton = heroSlideEditor?.querySelector("[data-hero-slide-add]");
    const heroSlideCount = heroSlideEditor?.querySelector("[data-hero-slide-count]");
    const heroSlideTemplate = document.getElementById("home-hero-slide-template");

    let currentPage = pageSelect?.value || "";
    const formState = new WeakMap();
    let activeHeroSlideIndex = 0;

    const syncHomeButtonControl = (select) => {
        if (!select) {
            return;
        }

        const scope = select.closest(".content-grid");
        const group = select.dataset.homeButtonAction;
        if (!scope || !group) {
            return;
        }

        const isAddToCart = select.value === "add_to_cart";
        const urlField = scope.querySelector(`[data-home-button-url-field="${group}"]`);
        const productField = scope.querySelector(`[data-home-button-product-field="${group}"]`);
        const urlInput = urlField?.querySelector("input, textarea, select");
        const productInput = productField?.querySelector("select");

        if (urlField) {
            urlField.classList.toggle("is-muted", isAddToCart);
        }

        if (productField) {
            productField.classList.toggle("is-muted", !isAddToCart);
        }

        if (urlInput) {
            urlInput.disabled = isAddToCart;
        }

        if (productInput) {
            productInput.disabled = !isAddToCart;
        }
    };

    const getActiveSection = () => sections.find((section) => section.classList.contains("is-active")) || null;

    const getActiveForm = () => getActiveSection()?.querySelector("[data-content-form]") || null;

    const serializeForm = (form) => {
        if (!form) {
            return "";
        }

        const fields = Array.from(form.querySelectorAll("input[name], select[name], textarea[name]"));

        return JSON.stringify(fields.map((field) => {
            const key = field.name;

            if (field instanceof HTMLInputElement) {
                if (field.type === "file") {
                    const files = Array.from(field.files || []).map((file) => ({
                        name: file.name,
                        size: file.size,
                        type: file.type,
                    }));

                    return [
                        key,
                        {
                            type: "file",
                            disabled: field.disabled,
                            files,
                        },
                    ];
                }

                if (field.type === "checkbox" || field.type === "radio") {
                    return [
                        key,
                        {
                            type: field.type,
                            disabled: field.disabled,
                            checked: field.checked,
                            value: field.value,
                        },
                    ];
                }
            }

            return [
                key,
                {
                    disabled: field.disabled,
                    value: field.value,
                },
            ];
        }));
    };

    const captureFormState = (form) => {
        if (!form) {
            return;
        }

        formState.set(form, serializeForm(form));
    };

    const isFormDirty = (form) => serializeForm(form) !== (formState.get(form) || "");

    const getDirtyForms = () => forms.filter((form) => isFormDirty(form));

    const getDirtyForm = () => {
        const activeForm = getActiveForm();
        if (activeForm && isFormDirty(activeForm)) {
            return activeForm;
        }

        return getDirtyForms()[0] || null;
    };

    const buildPageUrl = (value) => {
        const url = new URL(window.location.href);
        url.searchParams.set("page", value);
        return url.toString();
    };

    const updateBar = () => {
        if (!unsavedBar) {
            return;
        }

        unsavedBar.classList.toggle("is-visible", getDirtyForms().length > 0);
    };

    const syncDirtyState = (form) => {
        if (!form) {
            return;
        }

        updateBar();
    };

    const slugifyValue = (value) => value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, "_")
        .replace(/^_+|_+$/g, "");

    const getStateValues = () => {
        if (!stateRows) {
            return [];
        }

        const values = [];
        stateRows.querySelectorAll("[data-state-input]").forEach((input) => {
            const value = input.value.trim();
            if (value !== "" && !values.includes(value)) {
                values.push(value);
            }
        });
        return values;
    };

    const getCityMap = () => {
        const map = new Map();
        if (!cityRows) {
            return map;
        }

        cityRows.querySelectorAll(".delivery-location-table-row--city").forEach((row) => {
            const stateValue = row.querySelector("[data-city-state-select]")?.value.trim() || "";
            const cityValue = row.querySelector("[data-city-input]")?.value.trim() || "";
            if (!stateValue || !cityValue) {
                return;
            }

            if (!map.has(stateValue)) {
                map.set(stateValue, []);
            }

            const cities = map.get(stateValue);
            if (!cities.includes(cityValue)) {
                cities.push(cityValue);
            }
        });

        return map;
    };

    const updateCountLabel = (element, visibleCount, totalCount) => {
        if (!element) {
            return;
        }

        element.textContent = visibleCount === totalCount
            ? `${totalCount} rows`
            : `${visibleCount} of ${totalCount} rows`;
    };

    const setEmptyState = (element, isVisible) => {
        if (!element) {
            return;
        }

        element.hidden = !isVisible;
    };

    const syncSelectOptions = (select, values, placeholder, preferredValue = "") => {
        if (!select) {
            return;
        }

        const previousValue = preferredValue || select.value || select.dataset.selectedCity || "";
        const safeValues = values.filter((value, index) => value !== "" && values.indexOf(value) === index);
        select.innerHTML = "";

        const placeholderOption = document.createElement("option");
        placeholderOption.value = "";
        placeholderOption.textContent = placeholder;
        placeholderOption.selected = previousValue === "";
        select.appendChild(placeholderOption);

        safeValues.forEach((value) => {
            const option = document.createElement("option");
            option.value = value;
            option.textContent = value;
            option.selected = value === previousValue;
            select.appendChild(option);
        });

        if (!safeValues.includes(previousValue)) {
            select.value = "";
        }
    };

    const syncDeliveryLocationControls = () => {
        if (!locationsEditor) {
            return;
        }

        const states = getStateValues();
        const cityMap = getCityMap();

        syncSelectOptions(cityFilterState, states, "All States");
        syncSelectOptions(townshipFilterState, states, "All States");

        const selectedTownshipFilterState = townshipFilterState?.value.trim() || "";
        syncSelectOptions(
            townshipFilterCity,
            selectedTownshipFilterState ? (cityMap.get(selectedTownshipFilterState) || []) : Array.from(new Set(Array.from(cityMap.values()).flat())),
            "All Cities"
        );

        cityRows?.querySelectorAll(".delivery-location-table-row--city").forEach((row) => {
            const stateSelect = row.querySelector("[data-city-state-select]");
            syncSelectOptions(stateSelect, states, "Select State");
        });

        townshipRows?.querySelectorAll(".delivery-location-table-row--township").forEach((row) => {
            const stateSelect = row.querySelector("[data-township-state-select]");
            const citySelect = row.querySelector("[data-township-city-select]");
            syncSelectOptions(stateSelect, states, "Select State");

            const currentState = stateSelect?.value.trim() || "";
            const selectedCity = citySelect?.dataset.selectedCity || citySelect?.value || "";
            syncSelectOptions(citySelect, cityMap.get(currentState) || [], "Select City", selectedCity);
            if (citySelect) {
                delete citySelect.dataset.selectedCity;
            }
        });

        if (cityAddButton) {
            const hasStates = states.length > 0;
            cityAddButton.disabled = !hasStates;
            cityAddButton.title = hasStates ? "" : "Add at least one state first.";
        }

        if (townshipAddButton) {
            const hasCities = Array.from(cityMap.values()).some((cities) => cities.length > 0);
            townshipAddButton.disabled = !hasCities;
            townshipAddButton.title = hasCities ? "" : "Add at least one city first.";
        }

        const stateQuery = stateSearchInput?.value.trim().toLowerCase() || "";
        let visibleStates = 0;
        const stateRowsList = Array.from(stateRows?.querySelectorAll("[data-state-row]") || []);
        stateRowsList.forEach((row) => {
            const stateValue = row.querySelector("[data-state-input]")?.value.trim().toLowerCase() || "";
            const isVisible = stateQuery === "" || stateValue.includes(stateQuery);
            row.hidden = !isVisible;
            if (isVisible) {
                visibleStates += 1;
            }
        });
        updateCountLabel(stateCount, visibleStates, stateRowsList.length);
        setEmptyState(stateEmpty, stateRowsList.length > 0 && visibleStates === 0);

        const cityFilterValue = cityFilterState?.value.trim() || "";
        const cityQuery = citySearchInput?.value.trim().toLowerCase() || "";
        let visibleCities = 0;
        const cityRowsList = Array.from(cityRows?.querySelectorAll("[data-city-row]") || []);
        cityRowsList.forEach((row) => {
            const rowState = row.querySelector("[data-city-state-select]")?.value.trim() || "";
            const rowCity = row.querySelector("[data-city-input]")?.value.trim().toLowerCase() || "";
            const matchesState = cityFilterValue === "" || rowState === cityFilterValue;
            const matchesQuery = cityQuery === "" || rowCity.includes(cityQuery);
            const isVisible = matchesState && matchesQuery;
            row.hidden = !isVisible;
            if (isVisible) {
                visibleCities += 1;
            }
        });
        updateCountLabel(cityCount, visibleCities, cityRowsList.length);
        setEmptyState(cityEmpty, cityRowsList.length > 0 && visibleCities === 0);

        const townshipStateValue = townshipFilterState?.value.trim() || "";
        const townshipCityValue = townshipFilterCity?.value.trim() || "";
        const townshipQuery = townshipSearchInput?.value.trim().toLowerCase() || "";
        let visibleTownships = 0;
        const townshipRowsList = Array.from(townshipRows?.querySelectorAll("[data-township-row]") || []);
        townshipRowsList.forEach((row) => {
            const rowState = row.querySelector("[data-township-state-select]")?.value.trim() || "";
            const rowCity = row.querySelector("[data-township-city-select]")?.value.trim() || "";
            const rowTownship = row.querySelector("[data-township-input]")?.value.trim().toLowerCase() || "";
            const matchesState = townshipStateValue === "" || rowState === townshipStateValue;
            const matchesCity = townshipCityValue === "" || rowCity === townshipCityValue;
            const matchesQuery = townshipQuery === "" || rowTownship.includes(townshipQuery);
            const isVisible = matchesState && matchesCity && matchesQuery;
            row.hidden = !isVisible;
            if (isVisible) {
                visibleTownships += 1;
            }
        });
        updateCountLabel(townshipCount, visibleTownships, townshipRowsList.length);
        setEmptyState(townshipEmpty, townshipRowsList.length > 0 && visibleTownships === 0);
    };

    const appendDeliveryRow = (container, template, focusSelector) => {
        if (!container || !template) {
            return;
        }

        const fragment = template.content.cloneNode(true);
        container.appendChild(fragment);
        syncDeliveryLocationControls();

        const form = container.closest("[data-content-form]");
        if (form) {
            syncDirtyState(form);
        }

        const latestField = container.querySelector(focusSelector);
        latestField?.focus();
    };

    const bindFilePreviewInput = (input) => {
        if (!input || input.dataset.previewBound === "true") {
            return;
        }

        input.dataset.previewBound = "true";
        input.addEventListener("change", () => {
            const file = input.files?.[0];
            const targetId = input.getAttribute("data-preview-target");
            if (!targetId) {
                return;
            }

            const form = input.closest("[data-content-form]");
            if (form) {
                syncDirtyState(form);
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
    };

    const syncPaymentMethodRows = () => {
        if (!paymentMethodList) {
            return;
        }

        const rows = Array.from(paymentMethodList.querySelectorAll("[data-payment-method-row]"));
        rows.forEach((row, rowIndex) => {
            const title = row.querySelector("[data-payment-method-title]");
            const labelInput = row.querySelector("[data-payment-method-label]");
            const keyInput = row.querySelector("[data-payment-method-key]");
            const keyDisplayInput = row.querySelector("[data-payment-method-key-display]");
            const sectionSelect = row.querySelector("[data-payment-method-section]");
            const phoneInput = row.querySelector("[data-payment-method-phone]");
            const accountNameInput = row.querySelector("[data-payment-method-account-name]");
            const accountNumberInput = row.querySelector("[data-payment-method-account-number]");
            const instructionsInput = row.querySelector("[data-payment-method-instructions]");
            const activeInput = row.querySelector("[data-payment-method-active]");
            const proofInput = row.querySelector("[data-payment-method-proof]");
            const logoFileInput = row.querySelector("[data-payment-method-logo-file], input[type='file'][name^='payment_method_logo_image_']");
            const logoPreviewNode = row.querySelector("[data-payment-method-logo-preview], .cms-image-preview img[id^='payment_method_logo_preview_']");
            const logoPreviewId = `payment_method_logo_preview_${rowIndex}`;
            const qrFileInput = row.querySelector("[data-payment-method-file], input[type='file'][name^='payment_method_qr_image_']");
            const qrPreviewNode = row.querySelector("[data-payment-method-preview], .cms-image-preview img[id^='payment_method_preview_']");
            const qrPreviewId = `payment_method_preview_${rowIndex}`;
            const explicitKey = keyInput?.value.trim() || "";
            const fallbackKey = slugifyValue(labelInput?.value || "");
            const resolvedKey = explicitKey || fallbackKey || "auto";

            if (title) {
                title.textContent = labelInput?.value.trim() || `Payment Method ${rowIndex + 1}`;
            }

            if (keyDisplayInput) {
                keyDisplayInput.value = resolvedKey === "auto" ? "Created automatically" : resolvedKey;
            }

            if (labelInput) {
                labelInput.name = "payment_method_label[]";
            }

            if (keyInput) {
                keyInput.name = "payment_method_key[]";
            }

            if (sectionSelect) {
                sectionSelect.name = "payment_method_section_key[]";
            }

            if (phoneInput) {
                phoneInput.name = "payment_method_phone[]";
            }

            if (accountNameInput) {
                accountNameInput.name = "payment_method_account_name[]";
            }

            if (accountNumberInput) {
                accountNumberInput.name = "payment_method_account_number[]";
            }

            if (instructionsInput) {
                instructionsInput.name = "payment_method_instructions[]";
            }

            if (activeInput) {
                activeInput.name = `payment_method_is_active[${rowIndex}]`;
            }

            if (proofInput) {
                proofInput.name = `payment_method_requires_payment_proof[${rowIndex}]`;
            }

            if (logoFileInput) {
                logoFileInput.name = `payment_method_logo_image_${rowIndex}`;
                logoFileInput.setAttribute("data-preview-target", logoPreviewId);
                bindFilePreviewInput(logoFileInput);
            }

            if (logoPreviewNode) {
                logoPreviewNode.id = logoPreviewId;
            }

            if (qrFileInput) {
                qrFileInput.name = `payment_method_qr_image_${rowIndex}`;
                qrFileInput.setAttribute("data-preview-target", qrPreviewId);
                bindFilePreviewInput(qrFileInput);
            }

            if (qrPreviewNode) {
                qrPreviewNode.id = qrPreviewId;
            }

            const removeButton = row.querySelector("[data-payment-method-remove]");
            if (removeButton) {
                removeButton.disabled = rows.length === 1;
                removeButton.title = rows.length === 1 ? "Keep at least one payment method row." : "";
            }
        });
    };

    const appendPaymentMethodRow = () => {
        if (!paymentMethodList || !paymentMethodTemplate) {
            return;
        }

        const fragment = paymentMethodTemplate.content.cloneNode(true);
        paymentMethodList.appendChild(fragment);
        syncPaymentMethodRows();

        const form = paymentMethodList.closest("[data-content-form]");
        if (form) {
            syncDirtyState(form);
        }

        const inputs = paymentMethodList.querySelectorAll("[data-payment-method-label]");
        inputs[inputs.length - 1]?.focus();
    };

    const bindHeroSlidePanel = (panel) => {
        if (!panel || panel.dataset.heroSlideBound === "true") {
            return;
        }

        panel.dataset.heroSlideBound = "true";
        panel.querySelectorAll("input[type='file']").forEach((input) => bindFilePreviewInput(input));
        panel.querySelectorAll("[data-home-button-action]").forEach((select) => {
            syncHomeButtonControl(select);
            if (select.dataset.heroActionBound === "true") {
                return;
            }

            select.dataset.heroActionBound = "true";
            select.addEventListener("change", () => syncHomeButtonControl(select));
        });

        const titleInput = panel.querySelector("[data-hero-slide-title-input]");
        const subtitleInput = panel.querySelector("[data-hero-slide-subtitle-input]");
        [titleInput, subtitleInput].forEach((input) => {
            input?.addEventListener("input", () => syncHeroSlideEditor(activeHeroSlideIndex));
        });
    };

    const syncHeroSlideEditor = (preferredIndex = activeHeroSlideIndex) => {
        if (!heroSlideEditor || !heroSlidePanels || !heroSlideTabs) {
            return;
        }

        const panels = Array.from(heroSlidePanels.querySelectorAll("[data-hero-slide-panel]"));
        if (!panels.length) {
            heroSlideTabs.innerHTML = "";
            if (heroSlideCount) {
                heroSlideCount.textContent = "0 slides";
            }
            return;
        }

        activeHeroSlideIndex = Math.min(Math.max(preferredIndex, 0), panels.length - 1);
        heroSlideTabs.innerHTML = "";

        panels.forEach((panel, index) => {
            bindHeroSlidePanel(panel);

            const isActive = index === activeHeroSlideIndex;
            const heading = panel.querySelector("[data-hero-slide-heading]");
            const titleInput = panel.querySelector("[data-hero-slide-title-input]");
            const subtitleInput = panel.querySelector("[data-hero-slide-subtitle-input]");
            const summaryTitle = panel.querySelector("[data-hero-slide-summary-title]");
            const summarySubtitle = panel.querySelector("[data-hero-slide-summary-subtitle]");
            const removeButton = panel.querySelector("[data-hero-slide-remove]");
            const fileInput = panel.querySelector("input[type='file']");
            const previewNode = panel.querySelector(".cms-image-preview img, .cms-image-preview .cms-image-empty");
            const titleValue = titleInput?.value.trim() || "";
            const subtitleValue = subtitleInput?.value.trim() || "";
            const tabTitle = titleValue || `Slide ${index + 1}`;
            const tabSubtitle = subtitleValue || "No subtitle yet";
            const previewId = `hero_preview_${index}`;

            if (heading) {
                heading.textContent = `Slide ${index + 1}`;
            }

            if (summaryTitle) {
                summaryTitle.textContent = tabTitle;
            }

            if (summarySubtitle) {
                summarySubtitle.textContent = tabSubtitle;
            }

            if (fileInput) {
                fileInput.name = `hero_image_${index}`;
                fileInput.setAttribute("data-preview-target", previewId);
                bindFilePreviewInput(fileInput);
            }

            if (previewNode) {
                previewNode.id = previewId;
            }

            if (removeButton) {
                removeButton.disabled = panels.length === 1;
                removeButton.title = panels.length === 1 ? "Keep at least one hero slide." : "";
            }

            panel.hidden = !isActive;
            panel.classList.toggle("is-active", isActive);

            const tabButton = document.createElement("button");
            tabButton.type = "button";
            tabButton.className = `hero-slide-tab${isActive ? " is-active" : ""}`;
            tabButton.dataset.heroSlideTab = String(index);
            tabButton.innerHTML = `
                <span class="hero-slide-tab-index">Slide ${index + 1}</span>
                <strong>${tabTitle}</strong>
                <span>${tabSubtitle}</span>
            `;
            heroSlideTabs.appendChild(tabButton);
        });

        if (heroSlideCount) {
            heroSlideCount.textContent = `${panels.length} slide${panels.length === 1 ? "" : "s"}`;
        }
    };

    const appendHeroSlidePanel = () => {
        if (!heroSlidePanels || !heroSlideTemplate) {
            return;
        }

        const fragment = heroSlideTemplate.content.cloneNode(true);
        heroSlidePanels.appendChild(fragment);

        const panels = heroSlidePanels.querySelectorAll("[data-hero-slide-panel]");
        const nextIndex = Math.max(0, panels.length - 1);
        syncHeroSlideEditor(nextIndex);

        const form = heroSlidePanels.closest("[data-content-form]");
        if (form) {
            syncDirtyState(form);
        }

        heroSlidePanels.querySelectorAll("[data-hero-slide-title-input]")[nextIndex]?.focus();
    };

    const syncSection = (value = pageSelect?.value || "") => {
        if (!pageSelect || value === "") {
            return;
        }

        sections.forEach((section) => {
            section.classList.toggle("is-active", section.dataset.page === value);
        });

        pageSelect.value = value;
        currentPage = value;
        window.history.replaceState({}, "", buildPageUrl(value));
    };

    pageSelect?.addEventListener("change", async () => {
        if (!pageSelect) {
            return;
        }

        const nextPage = pageSelect.value;
        if (nextPage === "" || nextPage === currentPage) {
            return;
        }

        if (getDirtyForm()) {
            const confirmation = window.Swal
                ? await window.Swal.fire({
                    icon: "warning",
                    title: "Discard unsaved changes?",
                    text: "Switching pages will remove your unsaved changes.",
                    showCancelButton: true,
                    confirmButtonText: "Discard changes",
                    cancelButtonText: "Stay here",
                    confirmButtonColor: "#c0392b",
                })
                : { isConfirmed: window.confirm("Switching pages will remove your unsaved changes. Continue?") };

            if (!confirmation.isConfirmed) {
                pageSelect.value = currentPage;
                return;
            }
        }

        window.location.assign(buildPageUrl(nextPage));
    });

    forms.forEach((form) => {
        form.addEventListener("input", () => syncDirtyState(form));
        form.addEventListener("change", () => syncDirtyState(form));
        form.addEventListener("submit", () => {
            captureFormState(form);
            syncDirtyState(form);
        });
    });

    document.querySelectorAll("input[type='file'][data-preview-target]").forEach((input) => {
        bindFilePreviewInput(input);
    });

    document.querySelectorAll("[data-home-button-action]").forEach((select) => {
        syncHomeButtonControl(select);
        select.addEventListener("change", () => syncHomeButtonControl(select));
    });

    heroSlideAddButton?.addEventListener("click", appendHeroSlidePanel);

    heroSlideEditor?.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const tabButton = target.closest("[data-hero-slide-tab]");
        if (tabButton) {
            syncHeroSlideEditor(Number(tabButton.getAttribute("data-hero-slide-tab") || "0"));
            return;
        }

        const removeButton = target.closest("[data-hero-slide-remove]");
        if (!removeButton || !heroSlidePanels) {
            return;
        }

        const panels = heroSlidePanels.querySelectorAll("[data-hero-slide-panel]");
        if (panels.length <= 1) {
            return;
        }

        const panel = removeButton.closest("[data-hero-slide-panel]");
        if (!panel) {
            return;
        }

        const currentPanels = Array.from(heroSlidePanels.querySelectorAll("[data-hero-slide-panel]"));
        const removedIndex = currentPanels.indexOf(panel);
        panel.remove();
        syncHeroSlideEditor(Math.max(0, removedIndex - 1));

        const form = heroSlidePanels.closest("[data-content-form]");
        if (form) {
            syncDirtyState(form);
        }
    });

    stateAddButton?.addEventListener("click", () => {
        appendDeliveryRow(stateRows, stateTemplate, ".delivery-location-table-row--state:last-child [data-state-input]");
    });

    cityAddButton?.addEventListener("click", () => {
        if (cityAddButton.disabled) {
            return;
        }
        appendDeliveryRow(cityRows, cityTemplate, ".delivery-location-table-row--city:last-child [data-city-state-select]");
    });

    townshipAddButton?.addEventListener("click", () => {
        if (townshipAddButton.disabled) {
            return;
        }
        appendDeliveryRow(townshipRows, townshipTemplate, ".delivery-location-table-row--township:last-child [data-township-state-select]");
    });

    locationsEditor?.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const removeButton = target.closest("[data-state-remove], [data-city-remove], [data-township-remove]");
        if (!removeButton) {
            return;
        }

        const row = removeButton.closest(".delivery-location-table-row");
        if (!row) {
            return;
        }

        const container = row.parentElement;
        row.remove();
        syncDeliveryLocationControls();

        const form = container?.closest("[data-content-form]");
        if (form) {
            syncDirtyState(form);
        }
    });

    paymentMethodAddButton?.addEventListener("click", appendPaymentMethodRow);

    document.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const removeMethodButton = target.closest("[data-payment-method-remove]");
        if (!removeMethodButton || !paymentMethodList) {
            return;
        }

        const methodRow = removeMethodButton.closest("[data-payment-method-row]");
        if (!methodRow) {
            return;
        }

        const rows = paymentMethodList.querySelectorAll("[data-payment-method-row]");
        if (rows.length <= 1) {
            return;
        }

        methodRow.remove();
        syncPaymentMethodRows();

        const form = paymentMethodList.closest("[data-content-form]");
        if (form) {
            syncDirtyState(form);
        }
    });

    paymentMethodList?.addEventListener("input", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (
            target.matches("[data-payment-method-label]")
            || target.matches("[data-payment-method-key]")
        ) {
            syncPaymentMethodRows();
        }
    });

    locationsEditor?.addEventListener("change", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (target.matches("[data-township-state-select]")) {
            const row = target.closest(".delivery-location-table-row--township");
            const citySelect = row?.querySelector("[data-township-city-select]");
            if (citySelect) {
                citySelect.dataset.selectedCity = "";
            }
        }

        if (
            target.matches("[data-state-input]")
            || target.matches("[data-city-state-select]")
            || target.matches("[data-city-input]")
            || target.matches("[data-township-state-select]")
        ) {
            syncDeliveryLocationControls();
        }
    });

    [stateSearchInput, cityFilterState, citySearchInput, townshipFilterState, townshipFilterCity, townshipSearchInput]
        .forEach((control) => {
            control?.addEventListener("input", syncDeliveryLocationControls);
            control?.addEventListener("change", () => {
                if (control === townshipFilterState && townshipFilterCity) {
                    townshipFilterCity.value = "";
                }
                syncDeliveryLocationControls();
            });
        });

    saveButton?.addEventListener("click", () => {
        const form = getDirtyForm() || getActiveForm();
        form?.requestSubmit();
    });

    discardButton?.addEventListener("click", () => {
        window.location.reload();
    });

    syncSection();
    syncDeliveryLocationControls();
    syncPaymentMethodRows();
    syncHeroSlideEditor();
    forms.forEach((form) => captureFormState(form));
    updateBar();
});
