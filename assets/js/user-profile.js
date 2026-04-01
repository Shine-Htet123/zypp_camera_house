const membershipBenefitsModal = document.querySelector("#membership-benefits-modal");
const membershipBenefitsTriggers = document.querySelectorAll("[data-open-membership-benefits]");
const deleteModal = document.querySelector("#address-delete-modal");
const deleteConfirmBtn = document.querySelector("#confirm-address-delete");
const accountPanel = document.querySelector("[data-account-panel]");
const accountView = document.querySelector("[data-account-view]");
const accountForm = document.querySelector("[data-account-form]");
const accountEditBtn = document.querySelector("[data-account-edit]");
const accountCancelBtn = document.querySelector("[data-account-cancel]");
const accountSaveBtn = document.querySelector("[data-account-save]");
const addressList = document.querySelector("[data-address-list]");
const addressEmptyState = document.querySelector("[data-address-empty]");
const addAddressButtons = document.querySelectorAll("[data-add-address]");

let membershipBenefitsModalTimer = null;
let cardPendingDelete = null;

const getAddressCards = () =>
    Array.from(document.querySelectorAll(".profile-card[data-address-card]"));

const showAlert = async (icon, title, text, confirmButtonColor) => {
    if (typeof Swal === "undefined") return;

    await Swal.fire({
        icon,
        title,
        text,
        confirmButtonColor,
    });
};

const copyReferralLink = async (button) => {
    const value = button?.dataset.copyValue || "";
    if (!value) return;

    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(value);
        } else {
            const tempInput = document.createElement("input");
            tempInput.value = value;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand("copy");
            tempInput.remove();
        }

        await showAlert("success", "Copied", "Your referral link has been copied.", "#56b356");
    } catch (error) {
        await showAlert("error", "Copy Failed", "Unable to copy the referral link right now.", "#56b356");
    }
};

const closeDeleteModal = () => {
    if (!deleteModal) return;
    deleteModal.classList.remove("open");
    deleteModal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
    cardPendingDelete = null;
};

const openMembershipBenefitsModal = () => {
    if (!membershipBenefitsModal) return;
    if (membershipBenefitsModalTimer) {
        window.clearTimeout(membershipBenefitsModalTimer);
        membershipBenefitsModalTimer = null;
    }
    membershipBenefitsModal.classList.remove("closing");
    membershipBenefitsModal.classList.add("open");
    membershipBenefitsModal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
};

const closeMembershipBenefitsModal = () => {
    if (!membershipBenefitsModal) return;
    if (!membershipBenefitsModal.classList.contains("open") && !membershipBenefitsModal.classList.contains("closing")) {
        return;
    }
    membershipBenefitsModal.classList.remove("open");
    membershipBenefitsModal.classList.add("closing");
    membershipBenefitsModalTimer = window.setTimeout(() => {
        membershipBenefitsModal.classList.remove("closing");
        membershipBenefitsModal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
        membershipBenefitsModalTimer = null;
    }, 240);
};

const requestJson = async (url, payload) => {
    const response = await fetch(url, {
        method: "POST",
        body: payload,
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
        credentials: "same-origin",
    });

    const result = await response.json().catch(() => ({
        success: false,
        message: "Something went wrong. Please try again.",
    }));

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Something went wrong. Please try again.");
    }

    return result;
};

const getInputValue = (scope, name) => scope.querySelector(`[name="${name}"]`)?.value.trim() || "";

const storeCardValues = (card, values) => {
    card.dataset.phoneValue = values.phone || "";
    card.dataset.addressValue = values.address || "";
    card.dataset.townshipValue = values.township || "";
    card.dataset.cityValue = values.city || "";
    card.dataset.postalCodeValue = values.postal_code || "";
};

const getStoredCardValues = (card) => ({
    phone: card.dataset.phoneValue || "",
    address: card.dataset.addressValue || "",
    township: card.dataset.townshipValue || "",
    city: card.dataset.cityValue || "",
    postal_code: card.dataset.postalCodeValue || "",
});

const setCardView = (card, values) => {
    const mapping = {
        phone: values.phone || "",
        address: values.address || "",
        township: values.township || "",
        city: values.city || "",
        postal_code: values.postal_code || "",
    };

    Object.entries(mapping).forEach(([field, value]) => {
        const view = card.querySelector(`[data-view-field="${field}"]`);
        if (!view) return;
        const emptyLabel = view.dataset.emptyLabel || "";
        view.textContent = value || emptyLabel;
    });
};

const syncCardInputs = (card, values) => {
    ["phone", "address", "township", "city", "postal_code"].forEach((field) => {
        const input = card.querySelector(`input[name="${field}"]`);
        if (input) input.value = values[field] || "";
    });
};

const updateAddressEmptyState = () => {
    if (!addressEmptyState) return;
    addressEmptyState.classList.toggle("hidden", getAddressCards().length > 0);
};

const refreshAddressCardLabels = () => {
    let savedIndex = 1;

    getAddressCards().forEach((card) => {
        const title = card.querySelector(".profile-card-title");
        const subtitle = card.querySelector(".profile-card-subtitle");

        if (!title || !subtitle) return;

        if (!card.dataset.addressId) {
            title.textContent = "New Address";
            subtitle.textContent = "Save it to use it during checkout.";
            return;
        }

        if (card.classList.contains("is-default")) {
            title.textContent = "Default Address";
            subtitle.textContent = "This address is currently used first at checkout.";
            return;
        }

        title.textContent = `Saved Address ${savedIndex}`;
        subtitle.textContent = "Saved for faster checkout.";
        savedIndex += 1;
    });
};

const updateDefaultButtons = () => {
    getAddressCards().forEach((card) => {
        const button = card.querySelector(".card-action-btn.set-default");
        if (!button) return;

        const label = button.querySelector("span");
        const isSaved = Boolean(card.dataset.addressId);

        if (!isSaved) {
            if (label) label.textContent = "Save First";
            button.setAttribute("disabled", "disabled");
            return;
        }

        if (card.classList.contains("is-default")) {
            if (label) label.textContent = "Default";
            button.setAttribute("disabled", "disabled");
        } else {
            if (label) label.textContent = "Set Default";
            button.removeAttribute("disabled");
        }
    });

    refreshAddressCardLabels();
    updateAddressEmptyState();
};

const setCardSubmitting = (card, isSubmitting) => {
    const saveBtn = card.querySelector(".card-form-actions .save-btn");
    const deleteBtn = card.querySelector(".card-action-btn.delete");
    const defaultBtn = card.querySelector(".card-action-btn.set-default");
    const editBtn = card.querySelector(".card-action-btn.edit");
    const cancelBtn = card.querySelector(".card-form-actions .cancel-btn");

    [saveBtn, deleteBtn, defaultBtn, editBtn, cancelBtn].forEach((button) => {
        if (button) button.disabled = isSubmitting;
    });

    if (saveBtn) {
        saveBtn.dataset.originalText = saveBtn.dataset.originalText || saveBtn.textContent;
        saveBtn.textContent = isSubmitting ? "Saving..." : saveBtn.dataset.originalText;
    }
};

const requestProfileAddressAction = async (card, action) => {
    const form = card.querySelector(".profile-card-form");
    const payload = new FormData();
    payload.append("action", action);
    payload.append("address_id", card.dataset.addressId || "");

    if (form) {
        new FormData(form).forEach((value, key) => payload.append(key, value));
    }

    return requestJson("/profile/address.php", payload);
};

const focusFirstCardInput = (card) => {
    const input = card.querySelector(".profile-card-form input");
    if (input) input.focus();
};

const createAddressCardElement = () => {
    const card = document.createElement("div");
    card.className = "profile-card is-new editing";
    card.setAttribute("data-address-card", "");
    card.setAttribute("data-address-id", "");
    card.innerHTML = `
        <span class="card-badge">Default</span>
        <div class="profile-card-header">
            <div>
                <h4 class="profile-card-title">New Address</h4>
                <p class="profile-card-subtitle">Save it to use it during checkout.</p>
            </div>
        </div>
        <div class="profile-list profile-card-view">
            <div class="profile-row">
                <span class="label">Phone:</span>
                <span class="value" data-view-field="phone"></span>
            </div>
            <div class="profile-row">
                <span class="label">Address:</span>
                <span class="value" data-view-field="address"></span>
            </div>
            <div class="profile-row">
                <span class="label">Township:</span>
                <span class="value" data-view-field="township"></span>
            </div>
            <div class="profile-row">
                <span class="label">City:</span>
                <span class="value" data-view-field="city"></span>
            </div>
            <div class="profile-row">
                <span class="label">Postal Code:</span>
                <span class="value" data-view-field="postal_code" data-empty-label="Optional">Optional</span>
            </div>
        </div>
        <form class="profile-card-form">
            <div class="form-row">
                <div class="field">
                    <label>Phone</label>
                    <input type="text" name="phone" value="">
                </div>
                <div class="field">
                    <label>Address</label>
                    <input type="text" name="address" value="">
                </div>
                <div class="field">
                    <label>Township</label>
                    <input type="text" name="township" value="">
                </div>
                <div class="field">
                    <label>City</label>
                    <input type="text" name="city" value="">
                </div>
                <div class="field">
                    <label>Postal Code</label>
                    <input type="text" name="postal_code" value="" placeholder="Optional">
                </div>
            </div>
            <div class="card-form-actions">
                <button type="button" class="cancel-btn">Cancel</button>
                <button type="button" class="save-btn">Save Address</button>
            </div>
        </form>
        <div class="card-actions">
            <button type="button" class="card-action-btn edit" aria-label="Edit address">
                <i class="fa-regular fa-pen-to-square"></i>
                <span>Edit</span>
            </button>
            <button type="button" class="card-action-btn delete" aria-label="Delete address">
                <i class="fa-regular fa-trash-can"></i>
                <span>Delete</span>
            </button>
            <button type="button" class="card-action-btn set-default">
                <i class="fa-regular fa-star"></i>
                <span>Save First</span>
            </button>
        </div>
    `;

    storeCardValues(card, {
        phone: "",
        address: "",
        township: "",
        city: "",
        postal_code: "",
    });

    return card;
};

const syncAccountFields = (user) => {
    const fullName = user?.name || "";
    const email = user?.email || "";

    const fullNameView = document.querySelector('[data-account-view-field="full_name"]');
    const emailView = document.querySelector('[data-account-view-field="email"]');
    const fullNameInput = accountForm?.querySelector('input[name="full_name"]');
    const emailInput = accountForm?.querySelector('input[name="email"]');

    if (fullNameView) fullNameView.textContent = fullName;
    if (emailView) emailView.textContent = email;
    if (fullNameInput) fullNameInput.value = fullName;
    if (emailInput) emailInput.value = email;
};

const openAccountEditing = () => {
    if (!accountPanel) return;
    accountPanel.classList.add("editing-account");
};

const closeAccountEditing = () => {
    if (!accountPanel) return;
    accountPanel.classList.remove("editing-account");
};

const bindAddressCard = (card) => {
    if (!card || card.dataset.bound === "1") return;
    card.dataset.bound = "1";

    const editBtn = card.querySelector(".card-action-btn.edit");
    const deleteBtn = card.querySelector(".card-action-btn.delete");
    const defaultBtn = card.querySelector(".card-action-btn.set-default");
    const cancelBtn = card.querySelector(".card-form-actions .cancel-btn");
    const saveBtn = card.querySelector(".card-form-actions .save-btn");

    storeCardValues(card, {
        phone: getInputValue(card, "phone"),
        address: getInputValue(card, "address"),
        township: getInputValue(card, "township"),
        city: getInputValue(card, "city"),
        postal_code: getInputValue(card, "postal_code"),
    });

    editBtn?.addEventListener("click", () => {
        card.classList.add("editing");
        focusFirstCardInput(card);
    });

    cancelBtn?.addEventListener("click", () => {
        if (!card.dataset.addressId) {
            if (cardPendingDelete === card) {
                closeDeleteModal();
            }
            card.remove();
            updateDefaultButtons();
            return;
        }

        syncCardInputs(card, getStoredCardValues(card));
        card.classList.remove("editing");
    });

    saveBtn?.addEventListener("click", async () => {
        try {
            setCardSubmitting(card, true);
            const result = await requestProfileAddressAction(card, "save");
            const address = result.address || null;

            if (!address) {
                throw new Error("Unable to save the address right now.");
            }

            const savedValues = {
                phone: address.phone || "",
                address: address.street || "",
                township: address.township || "",
                city: address.city || "",
                postal_code: address.postal_code || "",
            };

            if (address.is_default) {
                getAddressCards().forEach((item) => item.classList.remove("is-default"));
                card.classList.add("is-default");
            }

            card.dataset.addressId = String(address.address_id || "");
            card.classList.remove("is-new", "editing");
            storeCardValues(card, savedValues);
            syncCardInputs(card, savedValues);
            setCardView(card, savedValues);
            updateDefaultButtons();

            await showAlert("success", "Saved", result.message || "Address saved.", "#56b356");
        } catch (error) {
            await showAlert("error", "Unable to Save", error.message || "Something went wrong.", "#b31212");
        } finally {
            setCardSubmitting(card, false);
        }
    });

    defaultBtn?.addEventListener("click", async () => {
        if (!card.dataset.addressId) return;

        try {
            const result = await requestProfileAddressAction(card, "set_default");
            getAddressCards().forEach((item) => item.classList.remove("is-default"));
            card.classList.add("is-default");
            updateDefaultButtons();

            await showAlert("success", "Default Updated", result.message || "Default address updated.", "#56b356");
        } catch (error) {
            await showAlert("error", "Unable to Update", error.message || "Something went wrong.", "#b31212");
        }
    });

    deleteBtn?.addEventListener("click", () => {
        if (!card.dataset.addressId) {
            card.remove();
            updateDefaultButtons();
            return;
        }

        cardPendingDelete = card;
        if (deleteModal) {
            deleteModal.classList.add("open");
            deleteModal.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-open");
        }
    });
};

const addNewAddressCard = () => {
    if (!addressList) return;

    const existingDraftCard = getAddressCards().find((card) => !card.dataset.addressId);
    if (existingDraftCard) {
        existingDraftCard.classList.add("editing");
        focusFirstCardInput(existingDraftCard);
        existingDraftCard.scrollIntoView({ behavior: "smooth", block: "nearest" });
        return;
    }

    const card = createAddressCardElement();
    addressList.prepend(card);
    bindAddressCard(card);
    updateDefaultButtons();
    focusFirstCardInput(card);
    card.scrollIntoView({ behavior: "smooth", block: "nearest" });
};

getAddressCards().forEach(bindAddressCard);
updateDefaultButtons();

accountEditBtn?.addEventListener("click", openAccountEditing);

accountCancelBtn?.addEventListener("click", () => {
    syncAccountFields({
        name: accountView?.querySelector('[data-account-view-field="full_name"]')?.textContent.trim() || "",
        email: accountView?.querySelector('[data-account-view-field="email"]')?.textContent.trim() || "",
    });
    closeAccountEditing();
});

accountSaveBtn?.addEventListener("click", async () => {
    if (!accountForm) return;

    const payload = new FormData(accountForm);

    try {
        accountSaveBtn.disabled = true;
        accountSaveBtn.dataset.originalText = accountSaveBtn.dataset.originalText || accountSaveBtn.textContent;
        accountSaveBtn.textContent = "Saving...";

        const result = await requestJson("/profile/account.php", payload);
        syncAccountFields(result.user || {});
        closeAccountEditing();

        await showAlert("success", "Saved", result.message || "Account details updated.", "#56b356");
    } catch (error) {
        await showAlert("error", "Unable to Save", error.message || "Something went wrong.", "#b31212");
    } finally {
        accountSaveBtn.disabled = false;
        accountSaveBtn.textContent = accountSaveBtn.dataset.originalText || "Save Changes";
    }
});

addAddressButtons.forEach((button) => {
    button.addEventListener("click", addNewAddressCard);
});

membershipBenefitsTriggers.forEach((button) => {
    button.addEventListener("click", openMembershipBenefitsModal);
});

if (membershipBenefitsModal) {
    const closeBtn = membershipBenefitsModal.querySelector(".modal-close");

    closeBtn?.addEventListener("click", closeMembershipBenefitsModal);

    membershipBenefitsModal.addEventListener("click", (event) => {
        if (event.target === membershipBenefitsModal) {
            closeMembershipBenefitsModal();
        }
    });
}

if (deleteModal) {
    const closeBtn = deleteModal.querySelector(".modal-close");
    const cancelBtn = deleteModal.querySelector(".modal-cancel");

    closeBtn?.addEventListener("click", closeDeleteModal);
    cancelBtn?.addEventListener("click", closeDeleteModal);

    deleteModal.addEventListener("click", (event) => {
        if (event.target === deleteModal) {
            closeDeleteModal();
        }
    });

    deleteConfirmBtn?.addEventListener("click", async () => {
        if (!cardPendingDelete) {
            closeDeleteModal();
            return;
        }

        try {
            const result = await requestProfileAddressAction(cardPendingDelete, "delete");

            cardPendingDelete.remove();

            if (result.next_default_address_id) {
                const nextDefaultCard = document.querySelector(`.profile-card[data-address-id="${result.next_default_address_id}"]`);
                if (nextDefaultCard) nextDefaultCard.classList.add("is-default");
            }

            updateDefaultButtons();
            closeDeleteModal();

            await showAlert("success", "Deleted", result.message || "Address removed.", "#56b356");
        } catch (error) {
            closeDeleteModal();
            await showAlert("error", "Unable to Delete", error.message || "Something went wrong.", "#b31212");
        }
    });
}

document.querySelectorAll("[data-copy-referral]").forEach((button) => {
    button.addEventListener("click", () => {
        copyReferralLink(button);
    });
});

const trackForm = document.querySelector("#track-form");
const trackResult = document.querySelector("#track-result");
const closeTrackResult = document.querySelector("#close-track-result");
const trackOrderInput = document.querySelector("[data-track-order-input]");

if (trackOrderInput) {
    trackOrderInput.addEventListener("input", () => {
        trackOrderInput.value = trackOrderInput.value.replace(/\D/g, "");
    });
}

if (trackForm && trackResult) {
    trackForm.addEventListener("submit", (event) => {
        event.preventDefault();
        trackResult.classList.remove("hidden");
        trackResult.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    if (closeTrackResult) {
        closeTrackResult.addEventListener("click", () => {
            trackResult.classList.add("hidden");
            trackForm.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    }
}

const navLinks = document.querySelectorAll(".profile-nav a[href^=\"#\"]");

const flashSectionHeader = (hash) => {
    const target = document.querySelector(hash);
    if (!target) return;
    const header = target.querySelector(".section-header");
    if (!header) return;
    header.classList.remove("flash");
    void header.offsetWidth;
    header.classList.add("flash");
    header.addEventListener(
        "animationend",
        () => header.classList.remove("flash"),
        { once: true }
    );
};

const showMembershipUpgradePopup = async () => {
    const upgrade = window.__membershipUpgrade || null;
    if (!upgrade || typeof Swal === "undefined") {
        return;
    }

    const escapeHtml = (value) =>
        String(value || "").replace(/[&<>"']/g, (char) => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;",
        }[char]));

    const currentTitle = String(upgrade.current_title || "New Tier").trim();
    const previousTitle = String(upgrade.previous_title || "").trim();
    const totalSpent = String(upgrade.total_spent || "").trim();
    const previousTitleLine = previousTitle
        ? `<p style="margin:0 0 8px;color:#7b6558;font-size:14px;">From <strong>${escapeHtml(previousTitle)}</strong></p>`
        : "";
    const totalSpentLine = totalSpent
        ? `<p style="margin:10px 0 0;color:#3f3128;font-size:14px;">Eligible spend: <strong>${escapeHtml(totalSpent)}</strong></p>`
        : "";

    const result = await Swal.fire({
        icon: "success",
        title: "Membership Upgraded",
        html: `
            <div style="padding-top:4px;">
                <div style="display:inline-block;padding:6px 12px;border-radius:999px;background:#f4e0c9;color:#7a5134;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;">
                    Congratulations
                </div>
                <h3 style="margin:14px 0 8px;font-size:28px;line-height:1.15;color:#2c211b;">${escapeHtml(currentTitle)}</h3>
                ${previousTitleLine}
                <p style="margin:0;color:#4b3d34;font-size:15px;line-height:1.65;">
                    You have unlocked a new ZYPP membership tier. Your profile and benefits are now updated.
                </p>
                ${totalSpentLine}
            </div>
        `,
        confirmButtonText: "View Benefits",
        showCancelButton: true,
        cancelButtonText: "Close",
        confirmButtonColor: "#5d4e47",
        cancelButtonColor: "#c3b7ad",
        width: 420,
    });

    if (result.isConfirmed) {
        openMembershipBenefitsModal();
    }
};

navLinks.forEach((link) => {
    link.addEventListener("click", () => {
        const hash = link.getAttribute("href");
        if (!hash || hash === "#") return;
        window.setTimeout(() => flashSectionHeader(hash), 300);
    });
});

if (window.location.hash) {
    window.setTimeout(() => flashSectionHeader(window.location.hash), 300);
}

window.addEventListener("load", () => {
    showMembershipUpgradePopup();
}, { once: true });

document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;

    if (membershipBenefitsModal?.classList.contains("open")) {
        closeMembershipBenefitsModal();
    }

    if (deleteModal?.classList.contains("open")) {
        closeDeleteModal();
    }
});
