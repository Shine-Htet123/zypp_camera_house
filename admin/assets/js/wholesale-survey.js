document.addEventListener('DOMContentLoaded', () => {
  if (typeof ApexCharts === 'undefined') return;

  const chartEl = document.querySelector('#companyTypesChart');
  if (!chartEl) return;

  const rootStyles = getComputedStyle(document.documentElement);
  const primaryColor = rootStyles.getPropertyValue('--bg-primary').trim() || '#4f9dff';
  const primaryDarkColor =
    rootStyles.getPropertyValue('--bg-primary-dark').trim() || '#2f5fb8';

  const chartData = window.wholesaleSurveyChartData || {};
  const categories = Array.isArray(chartData.categories) ? chartData.categories : [];
  const seriesData = Array.isArray(chartData.series) ? chartData.series : [];

  const options = {
    chart: {
      height: 220,
      type: 'bar',
      toolbar: { show: false },
    },
    series: [
      {
        name: 'Responses',
        type: 'column',
        data: seriesData,
      },
    ],
    stroke: {
      width: 0,
      curve: 'smooth',
    },
    plotOptions: {
      bar: {
        columnWidth: '50%',
        borderRadius: 6,
      },
    },
    colors: [primaryColor],
    dataLabels: { enabled: false },
    xaxis: {
      categories,
      labels: {
        style: { colors: '#9a9a9a', fontSize: '10px' },
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        style: { colors: '#9a9a9a', fontSize: '10px' },
      },
    },
    grid: {
      borderColor: '#ececec',
      strokeDashArray: 4,
    },
    legend: { show: false },
    noData: {
      text: 'No survey data yet.',
      style: {
        color: '#9a9a9a',
      },
    },
  };

  new ApexCharts(chartEl, options).render();

  const modal = document.getElementById('surveyModal');
  const closeBtn = modal?.querySelector('.modal-close');
  const detailBusiness = document.getElementById('detailBusiness');
  const detailContact = document.getElementById('detailContact');
  const detailPhone = document.getElementById('detailPhone');
  const detailEmail = document.getElementById('detailEmail');
  const detailType = document.getElementById('detailType');
  const detailNote = document.getElementById('detailNote');

  const openModal = () => {
    if (!modal) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  };

  const closeModal = () => {
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  };

  document.querySelectorAll('.answers-row').forEach((row) => {
    row.addEventListener('click', () => {
      if (detailBusiness) detailBusiness.textContent = row.dataset.business || '';
      if (detailContact) detailContact.textContent = row.dataset.contact || '';
      if (detailPhone) detailPhone.textContent = row.dataset.phone || '';
      if (detailEmail) detailEmail.textContent = row.dataset.email || '';
      if (detailType) detailType.textContent = row.dataset.type || '';
      if (detailNote) detailNote.textContent = row.dataset.note || '';
      openModal();
    });
  });

  closeBtn?.addEventListener('click', closeModal);

  modal?.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });
});
