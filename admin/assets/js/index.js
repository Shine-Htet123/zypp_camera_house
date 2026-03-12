document.addEventListener('DOMContentLoaded', () => {
  const dashboardData = window.adminDashboardData || {};
  const filterInput = document.querySelector('.kpi-filter-input');
  const filterText = document.querySelector('.kpi-filter-text');
  const updateDashboardUrl = (updates) => {
    const nextUrl = new URL(window.location.href);
    Object.entries(updates).forEach(([key, value]) => {
      if (!value) {
        nextUrl.searchParams.delete(key);
      } else {
        nextUrl.searchParams.set(key, value);
      }
    });
    window.location.href = nextUrl.toString();
  };

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
    filterInput.addEventListener('change', () => {
      updateText();
      updateDashboardUrl({ sales_month: filterInput.value || dashboardData.selectedMonth || '' });
    });
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
      const rangeValue = tab.dataset.rangeValue || dashboardData.selectedRange || '1y';
      updateDashboardUrl({
        sales_month: filterInput?.value || dashboardData.selectedMonth || '',
        sales_range: rangeValue,
      });
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
          data: dashboardData.salesChart?.series || [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
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
        categories: dashboardData.salesChart?.categories || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
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
          name: 'Units Sold',
          type: 'column',
          data: dashboardData.trendingChart?.sold_qty || [0],
        },
        {
          name: 'Orders',
          type: 'line',
          data: dashboardData.trendingChart?.order_count || [0],
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
        categories: dashboardData.trendingChart?.categories || ['No Data'],
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
          data: dashboardData.bestSellersChart?.sold_qty || [0],
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
        categories: dashboardData.bestSellersChart?.categories || ['No Data'],
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
