document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("[data-wholesale-form]");
    const modal = document.querySelector("[data-wholesale-modal]");
    const closeTargets = document.querySelectorAll("[data-close-wholesale-modal]");
    const errorBox = document.querySelector("[data-wholesale-error]");
    const submitButton = form?.querySelector(".submit-btn");

    if (!form || !modal) {
        return;
    }

    const setError = (message) => {
        if (!errorBox) return;
        if (!message) {
            errorBox.hidden = true;
            errorBox.textContent = "";
            return;
        }
        errorBox.hidden = false;
        errorBox.textContent = message;
    };

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

    form.addEventListener("submit", async (event) => {
        if (!form.reportValidity()) {
            return;
        }

        event.preventDefault();
        setError("");

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch("/auth/wholesale_submit.php", {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: new FormData(form),
            });

            const payload = await response.json().catch(() => null);

            if (!response.ok || !payload || !payload.success) {
                throw new Error(payload?.message || "Unable to submit the wholesale inquiry.");
            }

            form.reset();
            openModal();
        } catch (error) {
            setError(error.message || "Unable to submit the wholesale inquiry.");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
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
