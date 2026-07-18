// ==========================================================================
// Dashboard Manager - Advanced data visualization and components
// ==========================================================================

import ApexCharts from 'apexcharts';
import {
  REALTIME_DASHBOARD_POLL_MS,
  CHART_RESIZE_DEBOUNCE_MS,
  STAT_ANIMATION_DURATION_MS,
  STAT_ANIMATION_STEPS,
} from '../utils/constants.js';

export class DashboardManager {
  constructor() {
    this.charts = new Map();
    this.intervals = new Set();
    this.timeouts = new Set();
    this.cleanupFns = [];
    this.data = {
      revenue: [],
      users: [],
      orders: [],
      performance: [],
      recentOrders: [],
      salesByLocation: []
    };
    this.currentPeriod = '7d';
    this.init();
  }

  async init() {
    await this.loadDashboardData();

    this.initRevenueChart();
    this.initUserGrowthChart();
    this.initOrderStatusChart();
    this.initStorageChart();
    this.initSalesByLocationChart();
    this.populateRecentOrders();

    this.initInteractiveElements();
    this.initResizeHandler();

    // Set initial view to match active UI state (7D)
    this.updateChartPeriod(this.currentPeriod);
  }

  initResizeHandler() {
    const onResize = () => {
      const t = setTimeout(() => {
        this.charts.forEach(chart => {
          if (typeof chart.updateOptions === 'function') {
            chart.updateOptions({ chart: { width: '100%' } }, false, true);
          }
        });
        this.timeouts.delete(t);
      }, CHART_RESIZE_DEBOUNCE_MS);
      this.timeouts.add(t);
    };
    window.addEventListener('resize', onResize);
    this.cleanupFns.push(() => window.removeEventListener('resize', onResize));

    const onThemeChange = (e) => {
      const theme = e.detail?.theme || 'light';
      this.charts.forEach(chart => {
        if (typeof chart.updateOptions === 'function') {
          chart.updateOptions({ theme: { mode: theme } });
        }
      });
    };
    window.addEventListener('themeChanged', onThemeChange);
    this.cleanupFns.push(() => window.removeEventListener('themeChanged', onThemeChange));
  }

  async loadDashboardData() {
    if (window.dashboardData) {
      this.data.revenue = window.dashboardData.revenue_monthly || [];
      this.data.dailyRevenue = window.dashboardData.revenue_daily || [];
      this.data.users = window.dashboardData.users || [];
      this.data.orders = window.dashboardData.orders || {
        completed: 0, pending: 0, cancelled: 0, processing: 0
      };
      this.data.recentOrders = window.dashboardData.recentOrders || [];
      this.data.salesByLocation = window.dashboardData.salesByLocation || [];
    }
  }

  initRevenueChart() {
    const el = document.getElementById('revenueChart');
    if (!el) return;

    const options = {
      chart: {
        type: 'area',
        height: 320,
        toolbar: { show: false },
        zoom: { enabled: false }
      },
      series: [
        { name: 'Revenue', data: this.data.revenue.map(item => item.revenue) },
        { name: 'Profit', data: this.data.revenue.map(item => item.profit) }
      ],
      xaxis: {
        categories: this.data.revenue.map(item => item.month),
        axisBorder: { show: false }
      },
      yaxis: {
        labels: {
          formatter: value => '$' + value.toLocaleString()
        }
      },
      colors: ['#6366f1', '#10b981'],
      stroke: { curve: 'smooth', width: 2 },
      fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05 }
      },
      dataLabels: { enabled: false },
      legend: { position: 'top' },
      tooltip: {
        y: { formatter: value => '$' + value.toLocaleString() }
      },
      grid: { borderColor: 'rgba(0,0,0,0.08)', strokeDashArray: 4 }
    };

    const chart = new ApexCharts(el, options);
    chart.render();
    this.charts.set('revenue', chart);
  }

  initUserGrowthChart() {
    const el = document.getElementById('userGrowthChart');
    if (!el) return;

    const recent = this.data.users.slice(-7);
    const options = {
      chart: { type: 'bar', height: 280, toolbar: { show: false } },
      series: [{ name: 'New Users', data: recent.map(item => item.newUsers) }],
      xaxis: {
        categories: recent.map(item => `Day ${item.day}`),
        axisBorder: { show: false }
      },
      colors: ['#6366f1'],
      plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
      dataLabels: { enabled: false },
      grid: { borderColor: 'rgba(0,0,0,0.08)', strokeDashArray: 4 }
    };

    const chart = new ApexCharts(el, options);
    chart.render();
    this.charts.set('userGrowth', chart);
  }

  initOrderStatusChart() {
    const el = document.getElementById('orderStatusChart');
    if (!el) return;

    const options = {
      chart: { type: 'donut', height: 280 },
      series: [
        this.data.orders.completed,
        this.data.orders.processing,
        this.data.orders.pending,
        this.data.orders.cancelled
      ],
      labels: ['Completed', 'Processing', 'Pending', 'Cancelled'],
      colors: ['#10b981', '#6366f1', '#f59e0b', '#ef4444'],
      legend: { position: 'bottom' },
      dataLabels: { enabled: false },
      plotOptions: { pie: { donut: { size: '60%' } } }
    };

    const chart = new ApexCharts(el, options);
    chart.render();
    this.charts.set('orderStatus', chart);
  }

  initStorageChart() {
    const el = document.querySelector('#storageStatusChart');
    if (!el) return;

    const options = {
      chart: { height: 280, type: 'radialBar' },
      series: [76],
      colors: ['#20E647'],
      plotOptions: {
        radialBar: {
          hollow: { margin: 0, size: '70%', background: '#293450' },
          track: { dropShadow: { enabled: true, top: 2, left: 0, blur: 4, opacity: 0.15 } },
          dataLabels: {
            name: { offsetY: -10, color: '#fff', fontSize: '13px' },
            value: { color: '#fff', fontSize: '30px', show: true }
          }
        }
      },
      fill: {
        type: 'gradient',
        gradient: { shade: 'dark', type: 'vertical', gradientToColors: ['#87D4F9'], stops: [0, 100] }
      },
      stroke: { lineCap: 'round' },
      labels: ['Used Space']
    };

    const chart = new ApexCharts(el, options);
    chart.render();
    this.charts.set('storage', chart);
  }

  initSalesByLocationChart() {
    const chartElement = document.querySelector('#salesByLocationChart');
    if (!chartElement) return;

    const options = {
      series: [{
        name: 'Sales',
        data: this.data.salesByLocation.map(c => ({ x: c.name, y: c.value }))
      }],
      chart: {
        type: 'treemap',
        height: 350,
        width: '100%',
        toolbar: {
          show: true,
          tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false }
        },
        events: {
          mounted: (chart) => { chart.windowResizeHandler(); }
        }
      },
      dataLabels: {
        enabled: true,
        style: { fontSize: '12px' },
        formatter: (text, op) => [text, op.value],
        offsetY: -4
      },
      plotOptions: {
        treemap: {
          enableShades: true,
          shadeIntensity: 0.5,
          reverseNegativeShade: true,
          colorScale: {
            ranges: [
              { from: 0, to: 1000, color: '#CDD7B6' },
              { from: 1001, to: 2000, color: '#A4B494' },
              { from: 2001, to: 3000, color: '#52708E' }
            ]
          }
        }
      },
      responsive: [
        { breakpoint: 1200, options: { chart: { height: 350 }, dataLabels: { style: { fontSize: '11px' } } } },
        { breakpoint: 768, options: { chart: { height: 300 }, dataLabels: { style: { fontSize: '10px' } } } }
      ]
    };

    const chart = new ApexCharts(chartElement, options);
    chart.render();
    this.charts.set('salesByLocation', chart);
  }

  populateRecentOrders() {
    const tableBody = document.getElementById('recent-orders-table');
    if (!tableBody) return;

    tableBody.replaceChildren();
    for (const order of this.data.recentOrders) {
      const tr = document.createElement('tr');

      const idCell = document.createElement('td');
      const strong = document.createElement('strong');
      strong.textContent = order.id;
      idCell.appendChild(strong);

      const customerCell = document.createElement('td');
      customerCell.textContent = order.customer;

      const amountCell = document.createElement('td');
      amountCell.textContent = order.amount;

      const statusCell = document.createElement('td');
      const badge = document.createElement('span');
      badge.className = `badge ${order.status.class}`;
      badge.textContent = order.status.text;
      statusCell.appendChild(badge);

      const dateCell = document.createElement('td');
      dateCell.textContent = order.date;

      tr.append(idCell, customerCell, amountCell, statusCell, dateCell);
      tableBody.appendChild(tr);
    }
  }


  initInteractiveElements() {
    const onPeriodClick = (e) => {
      if (e.target.matches('[data-chart-period]')) {
        this.currentPeriod = e.target.dataset.chartPeriod;
        this.updateChartPeriod(this.currentPeriod);
        document.querySelectorAll('[data-chart-period]').forEach(btn => btn.classList.remove('active'));
        e.target.classList.add('active');
      }
    };
    const onExportClick = (e) => {
      if (e.target.matches('[data-export-chart]')) {
        const chartName = e.target.dataset.exportChart;
        this.exportChart(chartName);
      }
    };

    document.addEventListener('click', onPeriodClick);
    document.addEventListener('click', onExportClick);
    this.cleanupFns.push(() => document.removeEventListener('click', onPeriodClick));
    this.cleanupFns.push(() => document.removeEventListener('click', onExportClick));
  }

  updateChartPeriod(period) {
    switch (period) {
      case '7d': this.loadWeeklyData(); break;
      case '30d': this.loadMonthlyData(); break;
      case '90d': this.loadQuarterlyData(); break;
      case '1y': this.loadYearlyData(); break;
    }
  }

  loadWeeklyData() {
    const chart = this.charts.get('revenue');
    if (!chart) return;
    // Show last 7 data points from daily revenue
    const slice = (this.data.dailyRevenue || []).slice(-7);
    chart.updateOptions({
      series: [
        { name: 'Revenue', data: slice.map(d => d.revenue) },
        { name: 'Profit',  data: slice.map(d => d.profit)  },
      ],
      xaxis: { categories: slice.map(d => d.month) } // month contains the formatted date (e.g., M d)
    });
  }

  loadMonthlyData() {
    const chart = this.charts.get('revenue');
    if (!chart) return;
    const slice = (this.data.dailyRevenue || []);
    chart.updateOptions({
      series: [
        { name: 'Revenue', data: slice.map(d => d.revenue) },
        { name: 'Profit',  data: slice.map(d => d.profit)  },
      ],
      xaxis: { categories: slice.map(d => d.month) }
    });
  }

  loadQuarterlyData() {
    const chart = this.charts.get('revenue');
    if (!chart) return;
    // Group months into quarters
    const quarters = [
      { label: 'Q1', revenue: 0, profit: 0 },
      { label: 'Q2', revenue: 0, profit: 0 },
      { label: 'Q3', revenue: 0, profit: 0 },
      { label: 'Q4', revenue: 0, profit: 0 },
    ];
    this.data.revenue.forEach((d, i) => {
      const q = Math.floor(i / 3);
      quarters[q].revenue += d.revenue;
      quarters[q].profit  += d.profit;
    });
    chart.updateOptions({
      series: [
        { name: 'Revenue', data: quarters.map(q => q.revenue) },
        { name: 'Profit',  data: quarters.map(q => q.profit)  },
      ],
      xaxis: { categories: quarters.map(q => q.label) }
    });
  }

  loadYearlyData() {
    const chart = this.charts.get('revenue');
    if (!chart) return;
    const total = this.data.revenue.reduce((acc, d) => ({
      revenue: acc.revenue + d.revenue,
      profit:  acc.profit  + d.profit
    }), { revenue: 0, profit: 0 });
    chart.updateOptions({
      series: [
        { name: 'Revenue', data: [total.revenue] },
        { name: 'Profit',  data: [total.profit]  },
      ],
      xaxis: { categories: ['This Year'] }
    });
  }

  exportChart(chartName) {
    const chart = this.charts.get(chartName);
    if (chart && typeof chart.dataURI === 'function') {
      chart.dataURI().then(({ imgURI }) => {
        const link = document.createElement('a');
        link.download = `${chartName}-chart.png`;
        link.href = imgURI;
        link.click();
      });
    }
  }

  destroy() {
    this.intervals.forEach(id => clearInterval(id));
    this.intervals.clear();
    this.timeouts.forEach(id => clearTimeout(id));
    this.timeouts.clear();
    this.cleanupFns.forEach(fn => fn());
    this.cleanupFns = [];
    this.charts.forEach(chart => chart.destroy());
    this.charts.clear();
  }
}
