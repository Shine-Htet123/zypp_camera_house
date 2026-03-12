const getAddressCards = () =>
    Array.from(document.querySelectorAll(".profile-card[data-address-card]"));
const deleteModal = document.querySelector("#address-delete-modal");
const deleteConfirmBtn = document.querySelector("#confirm-address-delete");
let cardPendingDelete = null;

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

        if (typeof Swal !== "undefined") {
            await Swal.fire({
                icon: "success",
                title: "Copied",
                text: "Your referral link has been copied.",
                confirmButtonColor: "#56b356",
            });
        }
    } catch (error) {
        if (typeof Swal !== "undefined") {
            await Swal.fire({
                icon: "error",
                title: "Copy Failed",
                text: "Unable to copy the referral link right now.",
                confirmButtonColor: "#56b356",
            });
        }
    }
};

const closeDeleteModal = () => {
    if (!deleteModal) return;
    deleteModal.classList.remove("open");
    deleteModal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
    cardPendingDelete = null;
};

const updateDefaultButtons = () => {
    getAddressCards().forEach((card) => {
        const button = card.querySelector(".card-action-btn.set-default");
        if (!button) return;
        const label = button.querySelector("span");

        if (card.classList.contains("is-default")) {
            if (label) label.textContent = "Default";
            button.setAttribute("disabled", "disabled");
        } else {
            if (label) label.textContent = "Set Default";
            button.removeAttribute("disabled");
        }
    });
};

const setCardView = (card, values) => {
    const mapping = {
        full_name: values.full_name || "",
        phone: values.phone || "",
        email: values.email || "",
        address: values.address || "",
        township: values.township || "",
        city: values.city || "",
    };

    Object.entries(mapping).forEach(([field, value]) => {
        const view = card.querySelector(`[data-view-field="${field}"]`);
        if (view) view.textContent = value;
    });
};

const syncSharedIdentity = (fullName, email) => {
    getAddressCards().forEach((card) => {
        const fullNameInput = card.querySelector('input[name="full_name"]');
        const emailInput = card.querySelector('input[name="email"]');
        const fullNameView = card.querySelector('[data-view-field="full_name"]');
        const emailView = card.querySelector('[data-view-field="email"]');

        if (fullNameInput) fullNameInput.value = fullName;
        if (emailInput) emailInput.value = email;
        if (fullNameView) fullNameView.textContent = fullName;
        if (emailView) emailView.textContent = email;
    });
};

const setCardSubmitting = (card, isSubmitting) => {
    const saveBtn = card.querySelector(".card-form-actions .save-btn");
    const deleteBtn = card.querySelector(".card-action-btn.delete");
    const defaultBtn = card.querySelector(".card-action-btn.set-default");

    [saveBtn, deleteBtn, defaultBtn].forEach((button) => {
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

    const response = await fetch("/profile/address.php", {
        method: "POST",
        body: payload,
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json",
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

const bindAddressCard = (card) => {
    const editBtn = card.querySelector(".card-action-btn.edit");
    const deleteBtn = card.querySelector(".card-action-btn.delete");
    const defaultBtn = card.querySelector(".card-action-btn.set-default");
    const cancelBtn = card.querySelector(".card-form-actions .cancel-btn");
    const saveBtn = card.querySelector(".card-form-actions .save-btn");

    editBtn?.addEventListener("click", () => {
        card.classList.add("editing");
    });

    cancelBtn?.addEventListener("click", () => {
        card.classList.remove("editing");
    });

    saveBtn?.addEventListener("click", async () => {
        try {
            setCardSubmitting(card, true);
            const result = await requestProfileAddressAction(card, "save");
            const user = result.user || {};
            const address = result.address || null;

            syncSharedIdentity(user.name || "", user.email || "");

            if (address) {
                card.dataset.addressId = String(address.address_id || "");
                setCardView(card, {
                    full_name: user.name || "",
                    phone: address.phone || "",
                    email: user.email || "",
                    address: address.street || "",
                    township: address.township || "",
                    city: address.city || "",
                });

                const phoneInput = card.querySelector('input[name="phone"]');
                const addressInput = card.querySelector('input[name="address"]');
                const townshipInput = card.querySelector('input[name="township"]');
                const cityInput = card.querySelector('input[name="city"]');
                if (phoneInput) phoneInput.value = address.phone || "";
                if (addressInput) addressInput.value = address.street || "";
                if (townshipInput) townshipInput.value = address.township || "";
                if (cityInput) cityInput.value = address.city || "";
            } else {
                card.dataset.addressId = "";
                setCardView(card, {
                    full_name: user.name || "",
                    phone: "",
                    email: user.email || "",
                    address: "",
                    township: "",
                    city: "",
                });
            }

            card.classList.remove("editing");

            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    icon: "success",
                    title: "Saved",
                    text: result.message || "Profile card updated.",
                    confirmButtonColor: "#56b356",
                });
            }
        } catch (error) {
            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    icon: "error",
                    title: "Unable to Save",
                    text: error.message || "Something went wrong.",
                    confirmButtonColor: "#b31212",
                });
            }
        } finally {
            setCardSubmitting(card, false);
        }
    });

    defaultBtn?.addEventListener("click", async () => {
        try {
            const result = await requestProfileAddressAction(card, "set_default");
            getAddressCards().forEach((item) => item.classList.remove("is-default"));
            card.classList.add("is-default");
            updateDefaultButtons();

            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    icon: "success",
                    title: "Default Updated",
                    text: result.message || "Default address updated.",
                    confirmButtonColor: "#56b356",
                });
            }
        } catch (error) {
            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    icon: "error",
                    title: "Unable to Update",
                    text: error.message || "Something went wrong.",
                    confirmButtonColor: "#b31212",
                });
            }
        }
    });

    deleteBtn?.addEventListener("click", () => {
        cardPendingDelete = card;
        if (deleteModal) {
            deleteModal.classList.add("open");
            deleteModal.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-open");
        }
    });
};

getAddressCards().forEach(bindAddressCard);

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

            if (result.mode === "reset_blank") {
                cardPendingDelete.dataset.addressId = "";
                cardPendingDelete.classList.add("is-default");
                setCardView(cardPendingDelete, {
                    full_name: cardPendingDelete.querySelector('input[name="full_name"]')?.value || "",
                    phone: "",
                    email: cardPendingDelete.querySelector('input[name="email"]')?.value || "",
                    address: "",
                    township: "",
                    city: "",
                });
                ["phone", "address", "township", "city"].forEach((field) => {
                    const input = cardPendingDelete.querySelector(`input[name="${field}"]`);
                    if (input) input.value = "";
                });
            } else {
                cardPendingDelete.remove();
                if (result.next_default_address_id) {
                    const nextDefaultCard = document.querySelector(`.profile-card[data-address-id="${result.next_default_address_id}"]`);
                    if (nextDefaultCard) nextDefaultCard.classList.add("is-default");
                }
            }

            updateDefaultButtons();
            closeDeleteModal();

            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    icon: "success",
                    title: "Deleted",
                    text: result.message || "Address removed.",
                    confirmButtonColor: "#56b356",
                });
            }
        } catch (error) {
            closeDeleteModal();
            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    icon: "error",
                    title: "Unable to Delete",
                    text: error.message || "Something went wrong.",
                    confirmButtonColor: "#b31212",
                });
            }
        }
    });
}

updateDefaultButtons();

document.querySelectorAll("[data-copy-referral]").forEach((button) => {
    button.addEventListener("click", () => {
        copyReferralLink(button);
    });
});

const trackForm = document.querySelector("#track-form");
const trackResult = document.querySelector("#track-result");
const closeTrackResult = document.querySelector("#close-track-result");


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

// Flash section headers when navigating via sidebar links
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

navLinks.forEach((link) => {
    link.addEventListener("click", () => {
        const hash = link.getAttribute("href");
        if (!hash || hash === "#") return;
        setTimeout(() => flashSectionHeader(hash), 300);
    });
});

if (window.location.hash) {
    setTimeout(() => flashSectionHeader(window.location.hash), 300);
}
