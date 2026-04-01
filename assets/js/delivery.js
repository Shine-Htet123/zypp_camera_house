document.addEventListener('DOMContentLoaded', () => {
const customSelects = document.querySelectorAll('.custom-select');
const deliveryMethodField = document.querySelector('[data-delivery-method-field]');
const deliveryTypeInputs = document.querySelectorAll('input[name="delivery_type"]');
const form = document.querySelector('.delivery-form');
const flash = window.__checkoutFlash || null;
const deliveryLocations = window.__deliveryLocations || { states: [] };
const savedAddresses = Array.isArray(window.__savedAddresses) ? window.__savedAddresses : [];

const getSelectParts = (select) => ({
    trigger: select.querySelector('.select-trigger'),
    label: select.querySelector('.select-label'),
    options: select.querySelector('.select-options'),
    hiddenInput: select.querySelector('input[type="hidden"]'),
});

const closeAll = () => {
    customSelects.forEach((s) => s.classList.remove('open'));
};

const setSelectValue = (select, value, placeholder) => {
    const { label, hiddenInput } = getSelectParts(select);
    if (!label || !hiddenInput) return;

    const normalizedValue = String(value || '').trim();
    hiddenInput.value = normalizedValue;
    label.textContent = normalizedValue !== '' ? normalizedValue : placeholder;
};

const renderSelectOptions = (select, values) => {
    const { options } = getSelectParts(select);
    if (!options) return;

    options.innerHTML = values
        .map((value) => `<li data-value="${String(value).replace(/"/g, '&quot;')}">${String(value)}</li>`)
        .join('');
};

const stateSelect = document.querySelector('.custom-select[data-name="state"]');
const citySelect = document.querySelector('.custom-select[data-name="city"]');
const townshipSelect = document.querySelector('.custom-select[data-name="township"]');
const savedAddressSelect = document.querySelector('.custom-select[data-address-select]');

const findState = (stateName) => {
    return (deliveryLocations.states || []).find(
        (state) => String(state.name || '').trim() === String(stateName || '').trim()
    ) || null;
};

const findCity = (stateName, cityName) => {
    const state = findState(stateName);
    if (!state) return null;

    return (state.cities || []).find(
        (city) => String(city.name || '').trim() === String(cityName || '').trim()
    ) || null;
};

const findStateForAddress = (cityName, townshipName) => {
    const normalizedCity = String(cityName || '').trim();
    const normalizedTownship = String(townshipName || '').trim();

    if (normalizedCity === '') {
        return '';
    }

    const matchedState = (deliveryLocations.states || []).find((state) =>
        (state.cities || []).some((city) => {
            const sameCity = String(city.name || '').trim() === normalizedCity;
            if (!sameCity) {
                return false;
            }

            const townships = (city.townships || []).map((township) => String(township).trim());
            return normalizedTownship === '' || townships.length === 0 || townships.includes(normalizedTownship);
        })
    );

    return String(matchedState?.name || '').trim();
};

const setInputValue = (name, value) => {
    const input = form?.querySelector(`[name="${name}"]`);
    if (input) {
        input.value = String(value || '').trim();
    }
};

const applySavedAddress = (addressId) => {
    const id = Number.parseInt(String(addressId || '0'), 10);
    const address = savedAddresses.find((item) => Number.parseInt(String(item.address_id || '0'), 10) === id);
    if (!address) {
        return;
    }

    setInputValue('phone', address.phone || '');
    setInputValue('address', address.street || '');
    setInputValue('postal_code', address.postal_code || '');

    const stateValue = String(address.state || '').trim() || findStateForAddress(address.city, address.township);
    setSelectValue(stateSelect, stateValue, 'State/Province');
    syncLocationSelects('state');
    setSelectValue(citySelect, address.city || '', 'City');
    syncLocationSelects('city');
    setSelectValue(townshipSelect, address.township || '', 'Township');
};

const syncLocationSelects = (changed = null) => {
    if (!stateSelect || !citySelect || !townshipSelect) return;

    const stateParts = getSelectParts(stateSelect);
    const cityParts = getSelectParts(citySelect);
    const townshipParts = getSelectParts(townshipSelect);

    const selectedState = String(stateParts.hiddenInput?.value || '').trim();
    let selectedCity = String(cityParts.hiddenInput?.value || '').trim();
    let selectedTownship = String(townshipParts.hiddenInput?.value || '').trim();

    const state = findState(selectedState);
    const cityValues = state ? (state.cities || []).map((city) => String(city.name || '').trim()).filter(Boolean) : [];

    if (changed === 'state' || !cityValues.includes(selectedCity)) {
        selectedCity = '';
    }

    renderSelectOptions(citySelect, cityValues);
    setSelectValue(citySelect, selectedCity, 'City');

    const city = findCity(selectedState, selectedCity);
    const townshipValues = city ? (city.townships || []).map((township) => String(township).trim()).filter(Boolean) : [];

    if (changed === 'state' || changed === 'city' || !townshipValues.includes(selectedTownship)) {
        selectedTownship = '';
    }

    renderSelectOptions(townshipSelect, townshipValues);
    setSelectValue(townshipSelect, selectedTownship, 'Township');
};

customSelects.forEach((select) => {
    const { trigger, label, options, hiddenInput } = getSelectParts(select);

    if (!trigger || !options || !hiddenInput || !label) return;

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = select.classList.contains('open');
        closeAll();
        select.classList.toggle('open', !isOpen);
    });

    options.addEventListener('click', (event) => {
        const item = event.target.closest('li');
        if (!item) return;

        const value = item.dataset.value || '';
        hiddenInput.value = value;
        label.textContent = item.textContent.trim();
        select.classList.remove('open');

        if (select.dataset.name === 'state') {
            syncLocationSelects('state');
        } else if (select.dataset.name === 'city') {
            syncLocationSelects('city');
        } else if (select.dataset.name === 'selected_address_id') {
            applySavedAddress(value);
        }
    });
});

document.addEventListener('click', () => {
    closeAll();
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

syncLocationSelects();
syncDeliveryMode();

if (flash && typeof Swal !== 'undefined' && flash.message) {
    Swal.fire({
        icon: flash.type === 'success' ? 'success' : 'error',
        title: flash.type === 'success' ? 'Checkout' : 'Delivery Information',
        text: flash.message,
    });
}
});
