const kpiInputs = document.querySelectorAll('.kpi-top .kpi-input');

const monthLabels = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

const formatKpiValue = (input, value) => {
  const pill = input.previousElementSibling;
  if (!pill || !pill.classList.contains('kpi-pill')) return value;

  const type = pill.dataset.type;
  if (type === 'month') {
    if (!value) return 'DEC';
    const parts = value.split('-');
    const monthIndex = parseInt(parts[1], 10) - 1;
    return monthLabels[monthIndex] || 'DEC';
  }

  return value || '2026';
};

kpiInputs.forEach((input) => {
  const pill = input.previousElementSibling;
  const text = pill ? pill.querySelector('.kpi-text') : null;

  const updateText = () => {
    if (!text) return;
    text.textContent = formatKpiValue(input, input.value);
  };

  input.addEventListener('input', updateText);
  input.addEventListener('change', updateText);
  updateText();
});
