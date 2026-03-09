document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("[data-wholesale-form]");
    const modal = document.querySelector("[data-wholesale-modal]");
    const closeTargets = document.querySelectorAll("[data-close-wholesale-modal]");

    if (!form || !modal) {
        return;
    }

    const openModal = () => {
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
    };

    const closeModal = () => {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
    };

    form.addEventListener("submit", (event) => {
        if (!form.reportValidity()) {
            return;
        }

        event.preventDefault();
        openModal();
    });

    closeTargets.forEach((target) => {
        target.addEventListener("click", closeModal);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal.classList.contains("is-open")) {
            closeModal();
        }
    });
});
