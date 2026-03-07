document.addEventListener('DOMContentLoaded', () => {
  const filterInput = document.querySelector('.kpi-filter-input');
  const filterText = document.querySelector('.kpi-filter-text');

  if (filterInput && filterText) {
    const formatMonthYear = (value) => {
      if (!value) return filterText.textContent || 'DEC 2026';
      const [year, month] = value.split('-');
      const monthIndex = parseInt(month, 10) - 1;
      const months = [
        'JANUARY',
        'FEBRUARY',
        'MARCH',
        'APRIL',
        'MAY',
        'JUNE',
        'JULY',
        'AUGUST',
        'SEPTEMBER',
        'OCTOBER',
        'NOVEMBER',
        'DECEMBER',
      ];
      return `${months[monthIndex] || 'DECEMBER'} ${year}`;
    };

    const updateText = () => {
      filterText.textContent = formatMonthYear(filterInput.value);
    };

    updateText();
    filterInput.addEventListener('input', updateText);
    filterInput.addEventListener('change', updateText);
  }

  const filterPill = document.querySelector('.kpi-filter-pill');
  if (filterPill && filterInput) {
    filterPill.addEventListener('click', () => {
      if (typeof filterInput.showPicker === 'function') {
        filterInput.showPicker();
      } else {
        filterInput.focus();
        filterInput.click();
      }
    });
  }

  const tabs = document.querySelectorAll('.range-tab');
  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((item) => item.classList.remove('active'));
      tab.classList.add('active');
    });
  });

  const chartsReady = typeof ApexCharts !== 'undefined';
  if (!chartsReady) return;

  const rootStyles = getComputedStyle(document.documentElement);
  const primaryColor = rootStyles.getPropertyValue('--bg-primary').trim() || '#4f9dff';
  const primaryDarkColor =
    rootStyles.getPropertyValue('--bg-primary-dark').trim() || '#2f5fb8';

  const salesEl = document.querySelector('#salesChart');
  if (salesEl) {
    const salesOptions = {
      chart: {
        type: 'area',
        height: 220,
        toolbar: { show: false },
        zoom: { enabled: false },
      },
      series: [
        {
          name: 'Sales',
          data: [22, 27, 24, 30, 28, 33, 31, 36, 34, 39, 37, 42],
        },
      ],
      colors: [primaryColor],
      stroke: {
        curve: 'smooth',
        width: 3,
        colors: [primaryDarkColor],
      },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 0.6,
          opacityFrom: 0.45,
          opacityTo: 0.05,
          stops: [0, 90, 100],
        },
      },
      dataLabels: { enabled: false },
      xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
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
      tooltip: { theme: 'light' },
    };

    new ApexCharts(salesEl, salesOptions).render();
  }

  const trendingEl = document.querySelector('#trendingChart');
  if (trendingEl) {
    const trendingOptions = {
      chart: {
        height: 220,
        type: 'line',
        toolbar: { show: false },
      },
      series: [
        {
          name: 'Sales',
          type: 'column',
          data: [18, 32, 45, 52, 48, 60, 55],
        },
        {
          name: 'Target',
          type: 'line',
          data: [22, 35, 40, 58, 52, 63, 59],
        },
      ],
      stroke: {
        width: [0, 3],
        curve: 'smooth',
      },
      plotOptions: {
        bar: {
          columnWidth: '50%',
          borderRadius: 6,
        },
      },
      colors: [primaryColor, primaryDarkColor],
      dataLabels: { enabled: false },
      xaxis: {
        categories: ['2012', '2013', '2014', '2015', '2016', '2017', '2018'],
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
    };

    new ApexCharts(trendingEl, trendingOptions).render();
  }

  const bestEl = document.querySelector('#bestSellersChart');
  if (bestEl) {
    const bestOptions = {
      chart: {
        height: 220,
        type: 'bar',
        toolbar: { show: false },
      },
      series: [
        {
          name: 'Orders',
          data: [1200, 1020, 940, 780, 620, 520],
        },
      ],
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 6,
          barHeight: '50%',
        },
      },
      colors: [primaryColor],
      dataLabels: { enabled: false },
      xaxis: {
        categories: ['Category 10', 'Category 9', 'Category 8', 'Category 7', 'Category 6', 'Category 5'],
        labels: {
          style: { colors: '#9a9a9a', fontSize: '10px' },
        },
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
    };

    new ApexCharts(bestEl, bestOptions).render();
  }

});
