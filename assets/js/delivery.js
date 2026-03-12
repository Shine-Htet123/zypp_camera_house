document.addEventListener('DOMContentLoaded', () => {
const customSelects = document.querySelectorAll(".custom-select");
const deliveryMethodField = document.querySelector('[data-delivery-method-field]');
const deliveryTypeInputs = document.querySelectorAll('input[name="delivery_type"]');
const flash = window.__checkoutFlash || null;

customSelects.forEach((select) => {
    const trigger = select.querySelector(".select-trigger");
    const label = select.querySelector(".select-label");
    const options = select.querySelector(".select-options");
    const hiddenInput = select.querySelector("input[type='hidden']");

    if (!trigger || !options || !hiddenInput || !label) return;

    const closeAll = () => {
        customSelects.forEach((s) => s.classList.remove("open"));
    };

    trigger.addEventListener("click", (event) => {
        event.stopPropagation();
        const isOpen = select.classList.contains("open");
        closeAll();
        select.classList.toggle("open", !isOpen);
    });

    options.addEventListener("click", (event) => {
        const item = event.target.closest("li");
        if (!item) return;
        const value = item.dataset.value || "";
        label.textContent = item.textContent.trim();
        hiddenInput.value = value;
        select.classList.remove("open");
    });
});

document.addEventListener("click", () => {
    customSelects.forEach((s) => s.classList.remove("open"));
});

const syncDeliveryMode = () => {
    const selected = Array.from(deliveryTypeInputs).find((input) => input.checked)?.value || 'delivery';
    if (deliveryMethodField) {
        deliveryMethodField.classList.toggle('is-hidden', selected === 'pickup');
    }
};

deliveryTypeInputs.forEach((input) => {
    input.addEventListener('change', syncDeliveryMode);
});
syncDeliveryMode();

if (flash && typeof Swal !== 'undefined' && flash.message) {
    Swal.fire({
        icon: flash.type === 'success' ? 'success' : 'error',
        title: flash.type === 'success' ? 'Checkout' : 'Delivery Information',
        text: flash.message,
    });
}
});
