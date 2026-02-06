const customSelects = document.querySelectorAll(".custom-select");

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
