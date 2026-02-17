const addressCards = document.querySelectorAll(".profile-card[data-address-card]");
const deleteModal = document.querySelector("#address-delete-modal");
const deleteConfirmBtn = document.querySelector("#confirm-address-delete");
let cardPendingDelete = null;

const updateDefaultButtons = () => {
    addressCards.forEach((card) => {
        const button = card.querySelector(".card-action-btn.set-default");
        if (!button) return;
        if (card.classList.contains("is-default")) {
            button.querySelector("span").textContent = "Default";
            button.setAttribute("disabled", "disabled");
        } else {
            button.querySelector("span").textContent = "Set Default";
            button.removeAttribute("disabled");
        }
    });
};

addressCards.forEach((card) => {
    const editBtn = card.querySelector(".card-action-btn.edit");
    const deleteBtn = card.querySelector(".card-action-btn.delete");
    const defaultBtn = card.querySelector(".card-action-btn.set-default");
    const cancelBtn = card.querySelector(".card-form-actions .cancel-btn");
    const saveBtn = card.querySelector(".card-form-actions .save-btn");

    if (editBtn) {
        editBtn.addEventListener("click", () => {
            card.classList.add("editing");
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener("click", () => {
            card.classList.remove("editing");
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener("click", () => {
            card.classList.remove("editing");
        });
    }

    if (defaultBtn) {
        defaultBtn.addEventListener("click", () => {
            addressCards.forEach((item) => item.classList.remove("is-default"));
            card.classList.add("is-default");
            updateDefaultButtons();
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener("click", () => {
            cardPendingDelete = card;
            if (deleteModal) {
                deleteModal.classList.add("open");
                deleteModal.setAttribute("aria-hidden", "false");
                document.body.classList.add("modal-open");
            }
        });
    }
});

if (deleteModal) {
    const closeBtn = deleteModal.querySelector(".modal-close");
    const cancelBtn = deleteModal.querySelector(".modal-cancel");

    const closeModal = () => {
        deleteModal.classList.remove("open");
        deleteModal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
        cardPendingDelete = null;
    };

    if (closeBtn) {
        closeBtn.addEventListener("click", closeModal);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener("click", closeModal);
    }

    deleteModal.addEventListener("click", (event) => {
        if (event.target === deleteModal) {
            closeModal();
        }
    });

    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener("click", () => {
            if (cardPendingDelete) {
                cardPendingDelete.remove();
                updateDefaultButtons();
            }
            closeModal();
        });
    }
}

updateDefaultButtons();

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
