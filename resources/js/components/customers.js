import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

// ─── CSRF helper ─────────────────────────────────────────────────────────────
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
  const { headers, ...otherOptions } = options;
  const res = await fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      ...(headers || {}),
    },
    ...otherOptions,
  });

  const text = await res.text();
  const data = text ? JSON.parse(text) : {};

  if (!res.ok) {
    const validation = data?.errors ? Object.values(data.errors).flat().join(' ') : '';
    const message = validation || data?.message || data?.error || 'Request failed';
    throw new Error(message);
  }

  return data;
}

function getModal(elementOrSelector) {
  const element = typeof elementOrSelector === 'string'
    ? document.querySelector(elementOrSelector)
    : elementOrSelector;

  return element ? Modal.getOrCreateInstance(element) : null;
}

// ─── Toast helper ─────────────────────────────────────────────────────────────
function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const id = 'toast-' + Date.now();
  const iconMap = {
    success: 'bi-check-circle-fill',
    danger:  'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info:    'bi-info-circle-fill',
  };

  const el = document.createElement('div');
  el.id = id;
  el.className = `toast align-items-center text-bg-${type} border-0 show mb-2`;
  el.setAttribute('role', 'alert');
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi ${iconMap[type] ?? 'bi-info-circle-fill'} me-2"></i><span></span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  el.querySelector('.toast-body span').textContent = message;

  container.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

async function confirmDelete({ title, text, confirmButtonText = 'Yes, delete it' }) {
  const result = await Swal.fire({
    title,
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#dc3545',
    reverseButtons: true,
    focusCancel: true,
  });

  return result.isConfirmed;
}

function downloadBlob(filename, content, contentType) {
  const blob = new Blob([content], { type: contentType });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  URL.revokeObjectURL(link.href);
  document.body.removeChild(link);
}

function csvEscape(value) {
  return `"${String(value ?? '').replace(/"/g, '""')}"`;
}

function parseCsvLine(line) {
  const values = [];
  let current = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const next = line[i + 1];

    if (char === '"' && inQuotes && next === '"') {
      current += '"';
      i++;
    } else if (char === '"') {
      inQuotes = !inQuotes;
    } else if (char === ',' && !inQuotes) {
      values.push(current.trim());
      current = '';
    } else {
      current += char;
    }
  }

  values.push(current.trim());
  return values;
}

document.addEventListener('alpine:init', () => {


  // ─── Customer Table Controller ─────────────────────────────────────────────
  Alpine.data('customerTable', () => ({
    customers: [],
    isLoading: false,
    currentPage: 1,
    totalPages: 1,
    totalCustomers: 0,
    itemsPerPage: 15,
    
    // Filters
    searchQuery: '',
    statusFilter: '',
    categoryFilter: '',
    sortField: 'name',
    sortDirection: 'asc',
    
    // Selection
    selectedCustomers: [],

    get selectedRows() {
      return this.customers.filter(c => this.selectedCustomers.includes(c.id));
    },

    get hasSelectedDeletedCustomers() {
      return this.selectedRows.some(c => c.isDeleted);
    },

    get hasSelectedActiveCustomers() {
      return this.selectedRows.some(c => !c.isDeleted);
    },
    
    // Charts & Trends
    growthPeriod: 7,
    charts: {},

    init() {
      this.loadCustomers();
      // Listen for customer changes to refresh stats/charts
      window.addEventListener('customer-updated', () => this.loadCustomers());
    },

    async loadCustomers() {
      this.isLoading = true;
      try {
        const params = new URLSearchParams({
          page: this.currentPage,
          per_page: this.itemsPerPage,
          sort_by: this.sortField,
          sort_dir: this.sortDirection,
        });

        if (this.searchQuery) params.set('search', this.searchQuery);
        if (this.statusFilter) {
          if (this.statusFilter === 'deleted') {
            params.set('deleted', 'only');
          } else {
            params.set('status', this.statusFilter);
          }
        }
        if (this.categoryFilter) params.set('category', this.categoryFilter);

        const data = await apiFetch(`/api/customers?${params.toString()}`);
        
        this.customers = (data.data ?? []).map(c => this._mapCustomer(c));
        this.currentPage = data.current_page ?? 1;
        this.totalPages = data.last_page ?? 1;
        this.totalCustomers = data.total ?? 0;

        this.$nextTick(() => {
          this.initCharts();
        });
      } catch (err) {
        showToast('Failed to load customers: ' + err.message, 'danger');
      } finally {
        this.isLoading = false;
      }
    },

    _mapCustomer(c) {
      const first = c.firstname ?? '';
      const last = c.lastname ?? '';
      const initials = ((first[0] ?? '') + (last[0] ?? '')).toUpperCase();
      const cropsList = Array.isArray(c.crops) ? c.crops : (typeof c.crops === 'string' ? JSON.parse(c.crops || '[]') : []);
      const irrigationList = Array.isArray(c.irrigation_type) ? c.irrigation_type : (typeof c.irrigation_type === 'string' ? JSON.parse(c.irrigation_type || '[]') : []);
      
      return {
        ...c,
        name: [c.firstname, c.middlename, c.lastname].filter(Boolean).join(' '),
        initials: initials || 'C',
        isDeleted: !!c.deleted_at,
        cropsList,
        irrigationList,
        formattedOutstanding: 'Rs ' + Number(c.outstanding_balance || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
        joinDate: c.created_at ? new Date(c.created_at).toLocaleDateString() : '—',
      };
    },

    // Pagination
    goToPage(page) {
      if (page < 1 || page > this.totalPages) return;
      this.currentPage = page;
      this.loadCustomers();
    },

    get visiblePages() {
      const pages = [];
      const delta = 2;
      for (let i = Math.max(1, this.currentPage - delta); i <= Math.min(this.totalPages, this.currentPage + delta); i++) {
        pages.push(i);
      }
      return pages;
    },

    get pageFrom() {
      if (this.totalCustomers === 0) return 0;
      return (this.currentPage - 1) * this.itemsPerPage + 1;
    },

    get pageTo() {
      return Math.min(this.currentPage * this.itemsPerPage, this.totalCustomers);
    },

    // Sorting
    sort(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
      this.currentPage = 1;
      this.loadCustomers();
    },

    searchCustomers() {
      this.currentPage = 1;
      this.loadCustomers();
    },

    filterCustomers() {
      this.currentPage = 1;
      this.loadCustomers();
    },

    getSortIcon(field) {
      if (this.sortField !== field) return 'bi-arrow-down-up text-muted small';
      return this.sortDirection === 'asc' ? 'bi-arrow-up text-primary' : 'bi-arrow-down text-primary';
    },

    resetFilters() {
      this.searchQuery = '';
      this.statusFilter = '';
      this.categoryFilter = '';
      this.currentPage = 1;
      this.loadCustomers();
    },

    // Selection
    toggleAll(checked) {
      this.selectedCustomers = checked ? this.customers.map(c => c.id) : [];
    },

    toggleCustomer(id) {
      if (this.selectedCustomers.includes(id)) {
        this.selectedCustomers = this.selectedCustomers.filter(i => i !== id);
      } else {
        this.selectedCustomers = [...this.selectedCustomers, id];
      }
    },

    // Actions
    editCustomer(c) {
      window.dispatchEvent(new CustomEvent('open-add-customer-modal', { detail: { customer: c } }));
    },

    openCreateCustomer() {
      window.dispatchEvent(new CustomEvent('open-add-customer-modal'));
    },

    viewCustomer(c) {
      const profile = Alpine.$data(document.querySelector('[x-data="customerProfile"]'));
      if (profile) profile.loadProfile(c.id);
      getModal('#viewCustomerModal')?.show();
    },

    async deleteCustomer(c) {
      const confirmed = await confirmDelete({
        title: 'Temporarily delete customer?',
        text: `Move ${c.name} to deleted. Outstanding balances will be preserved.`,
        confirmButtonText: 'Yes, delete',
      });
      if (!confirmed) return;

      try {
        const res = await apiFetch(`/api/customers/${c.id}`, { method: 'DELETE' });
        showToast(res.message, 'success');
        this.loadCustomers();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async restoreCustomer(c) {
      try {
        const res = await apiFetch(`/api/customers/${c.id}/restore`, { method: 'PATCH' });
        showToast(res.message, 'success');
        this.loadCustomers();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async forceDeleteCustomer(c) {
      const confirmed = await confirmDelete({
        title: 'Permanently delete customer?',
        text: `This will permanently delete ${c.name} and all associated addresses. This cannot be undone.`,
        confirmButtonText: 'Permanently Delete',
      });
      if (!confirmed) return;

      try {
        const res = await apiFetch(`/api/customers/${c.id}/force`, { method: 'DELETE' });
        showToast(res.message, 'success');
        this.loadCustomers();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async bulkAction(action) {
      if (this.selectedCustomers.length === 0) {
        showToast('Please select customers first.', 'warning');
        return;
      }

      if (action === 'delete') {
        const confirmed = await confirmDelete({
          title: 'Temporarily delete selected customers?',
          text: `Do you want to move ${this.selectedCustomers.length} selected customer(s) to deleted customers? You can restore them later.`,
          confirmButtonText: 'Yes, delete customers',
        });
        if (!confirmed) return;
      }
      if (action === 'force-delete') {
        const confirmed = await confirmDelete({
          title: 'Permanently delete selected customers?',
          text: `This will permanently delete ${this.selectedCustomers.length} selected customer(s). This action cannot be undone.`,
          confirmButtonText: 'Yes, permanently delete',
        });
        if (!confirmed) return;
      }
      if (action === 'restore') {
        const confirmed = await confirmDelete({
          title: 'Restore selected customers?',
          text: `Are you sure you want to restore ${this.selectedCustomers.length} selected customer(s)?`,
          confirmButtonText: 'Yes, restore customers',
        });
        if (!confirmed) return;
      }

      try {
        const res = await apiFetch('/api/customers/bulk-action', {
          method: 'POST',
          body: JSON.stringify({ action, ids: this.selectedCustomers }),
        });
        showToast(res.message, 'success');
        this.selectedCustomers = [];
        this.loadCustomers();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    // Stats calculations
    get stats() {
      const active = this.customers.filter(c => c.status === 'active').length;
      const blacklisted = this.customers.filter(c => c.is_blacklisted).length;
      const kycCompleted = this.customers.filter(c => c.kyc_completed).length;

      return {
        total: this.totalCustomers,
        active,
        blacklisted,
        kycPercentage: this.customers.length > 0 ? (kycCompleted / this.customers.length) * 100 : 0,
      };
    },

    get cropStats() {
      const counts = {};
      this.customers.forEach(c => {
        c.cropsList.forEach(crop => {
          counts[crop] = (counts[crop] || 0) + 1;
        });
      });
      const colors = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
      return Object.entries(counts).map(([name, count], i) => ({
        name, count,
        percentage: this.customers.length > 0 ? Math.round((count / this.customers.length) * 100) : 0,
        color: colors[i % colors.length],
      })).sort((a,b) => b.count - a.count).slice(0, 5);
    },

    // Charts
    initCharts() {
      this._initSparkline();
      this._initGrowthChart();
      this._initCategoryChart();
    },

    setGrowthPeriod(days) {
      this.growthPeriod = Number(days);
      this.$nextTick(() => {
        if (this.charts.customerGrowth) {
          this.charts.customerGrowth.destroy();
          delete this.charts.customerGrowth;
        }
        document.querySelector('#customerGrowthChart')?.removeAttribute('data-chart-initialized');
        this._initGrowthChart();
      });
    },

    _initSparkline() {
      const el = document.querySelector('#activeCustomerChart');
      if (!el || el.hasAttribute('data-chart-initialized')) return;
      el.setAttribute('data-chart-initialized', 'true');
      this.charts.activeCustomers = new ApexCharts(el, {
        series: [{ name: 'KYC Verified', data: [40, 45, 52, 58, 65, 72, 78] }],
        chart: { type: 'line', height: 50, sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 2 },
        colors: ['#10b981'],
      });
      this.charts.activeCustomers.render();
    },

    _initGrowthChart() {
      const el = document.querySelector('#customerGrowthChart');
      if (!el || el.hasAttribute('data-chart-initialized')) return;
      el.setAttribute('data-chart-initialized', 'true');

      const period = Number(this.growthPeriod) || 7;
      const dayCounts = new Array(period).fill(0);
      const dayLabels = [];
      const now = new Date();
      for (let i = period - 1; i >= 0; i--) {
        const d = new Date(now);
        d.setDate(d.getDate() - i);
        dayLabels.push(d.toLocaleDateString('en', period <= 7 ? { weekday: 'short' } : { month: 'short', day: 'numeric' }));
        dayCounts[period - 1 - i] = this.customers.filter(c => {
          const j = new Date(c.created_at);
          return j.toDateString() === d.toDateString();
        }).length;
      }

      this.charts.customerGrowth = new ApexCharts(el, {
        series: [{ name: 'New Registrations', data: dayCounts }],
        chart: { type: 'bar', height: 250, width: '100%', toolbar: { show: false } },
        colors: ['#6366f1'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
        xaxis: {
          categories: dayLabels,
          labels: { style: { colors: '#64748b', fontSize: '12px' } }
        },
        yaxis: { show: false },
        grid: { show: false },
        dataLabels: { enabled: false },
      });
      this.charts.customerGrowth.render();
    },

    _initCategoryChart() {
      const el = document.querySelector('#categoryDistributionChart');
      if (!el || el.hasAttribute('data-chart-initialized')) return;
      el.setAttribute('data-chart-initialized', 'true');

      const catCounts = this.customers.reduce((acc, c) => {
        const cat = c.category || 'individual';
        acc[cat] = (acc[cat] || 0) + 1;
        return acc;
      }, { individual: 0, business: 0 });

      this.charts.categoryChart = new ApexCharts(el, {
        series: [catCounts.individual, catCounts.business],
        chart: { type: 'donut', height: 160 },
        labels: ['Individual/Farmer', 'Business/Dealer'],
        colors: ['#6366f1', '#06b6d4'],
        legend: { position: 'bottom', labels: { colors: '#64748b' } },
        dataLabels: { enabled: false }
      });
      this.charts.categoryChart.render();
    },

    async fetchAllFilteredCustomers() {
      const first = await apiFetch(`/api/customers?page=1&per_page=100&sort_by=${this.sortField}&sort_dir=${this.sortDirection}`);
      const mapped = (first.data ?? []).map(c => this._mapCustomer(c));
      const lastPage = first.last_page ?? 1;

      for (let page = 2; page <= lastPage; page++) {
        const data = await apiFetch(`/api/customers?page=${page}&per_page=100&sort_by=${this.sortField}&sort_dir=${this.sortDirection}`);
        mapped.push(...(data.data ?? []).map(c => this._mapCustomer(c)));
      }
      return mapped;
    },

    async exportCustomers() {
      try {
        const list = await this.fetchAllFilteredCustomers();
        const headers = ['ID','Code','Name','Email','Phone','Category','Outstanding Balance','Credit Limit','Credit Days','Crops','Irrigation Source','KYC Status','Account Status','Joined'];
        const rows = list.map(c => [
          c.id, c.party_code, c.name, c.email || '', c.phone || '', c.category || 'individual',
          c.outstanding_balance, c.credit_limit, c.credit_days,
          c.cropsList.join('; '), c.irrigationList.join('; '),
          c.kyc_completed ? 'Verified' : 'Pending', c.status, c.joinDate
        ]);
        const csv = [headers, ...rows].map(r => r.map(csvEscape).join(',')).join('\n');
        downloadBlob('customers-export.csv', csv, 'text/csv;charset=utf-8;');
        showToast(`Exported ${list.length} customer(s).`);
      } catch (err) {
        showToast('Export failed: ' + err.message, 'danger');
      }
    }
  }));


  // ─── Customer Profile & Address Manager Controller ──────────────────────────
  Alpine.data('customerProfile', () => ({
    customer: null,
    loading: false,
    savingAddress: false,
    
    // Address Form
    showAddressForm: false,
    editingAddressId: null,
    addressForm: {
      label: 'Primary',
      address_line_1: '',
      address_line_2: '',
      village_id: '',
      village_name: '',
      city: '',
      state: '',
      pincode: '',
      is_default: false,
    },
    
    // Village Autocomplete Search
    villageSearchQuery: '',
    villageResults: [],

    async loadProfile(id) {
      this.loading = true;
      this.showAddressForm = false;
      try {
        const data = await apiFetch(`/api/customers/${id}`);
        this.customer = this._mapProfileCustomer(data.data ?? data);
      } catch (err) {
        showToast('Failed to load profile: ' + err.message, 'danger');
      } finally {
        this.loading = false;
      }
    },

    _mapProfileCustomer(c) {
      const first = c.firstname ?? '';
      const last = c.lastname ?? '';
      const initials = ((first[0] ?? '') + (last[0] ?? '')).toUpperCase();
      const cropsList = Array.isArray(c.crops) ? c.crops : (typeof c.crops === 'string' ? JSON.parse(c.crops || '[]') : []);
      const irrigationList = Array.isArray(c.irrigation_type) ? c.irrigation_type : (typeof c.irrigation_type === 'string' ? JSON.parse(c.irrigation_type || '[]') : []);

      return {
        ...c,
        name: [c.firstname, c.middlename, c.lastname].filter(Boolean).join(' '),
        initials: initials || 'C',
        isDeleted: !!c.deleted_at,
        cropsList,
        irrigationList,
        formattedOutstanding: 'Rs ' + Number(c.outstanding_balance || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
      };
    },

    editFromProfile() {
      getModal('#viewCustomerModal')?.hide();
      const table = Alpine.$data(document.querySelector('[x-data="customerTable"]'));
      if (table) table.editCustomer(this.customer);
    },

    async toggleActiveStatus() {
      try {
        const res = await apiFetch(`/api/customers/${this.customer.id}/toggle-active`, { method: 'PATCH' });
        showToast(res.message);
        this.customer.is_active = res.is_active;
        this.customer.status = res.is_active ? 'active' : 'inactive';
        window.dispatchEvent(new CustomEvent('customer-updated'));
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    // Addresses CRUD
    startAddAddress() {
      // Hide View Profile Modal
      getModal('#viewCustomerModal')?.hide();

      this.editingAddressId = null;
      this.villageSearchQuery = '';
      this.villageResults = [];
      this.addressForm = {
        label: 'Home',
        address_line_1: '',
        address_line_2: '',
        village_id: '',
        village_name: '',
        city: '',
        state: '',
        pincode: '',
        post_so_name: '',
        taluka_name: '',
        district_name: '',
        state_name: '',
        is_default: this.customer?.addresses?.length === 0,
      };

      // Show Address Modal
      getModal('#addressModal')?.show();
    },

    startEditAddress(addr) {
      // Hide View Profile Modal
      getModal('#viewCustomerModal')?.hide();

      this.editingAddressId = addr.id;
      this.villageSearchQuery = addr.village?.village_name ?? '';
      this.villageResults = [];
      this.addressForm = {
        label: addr.label ?? 'Primary',
        address_line_1: addr.address_line_1 ?? '',
        address_line_2: addr.address_line_2 ?? '',
        village_id: addr.village_id ?? '',
        village_name: addr.village?.village_name ?? '',
        city: addr.city ?? '',
        state: addr.state ?? '',
        pincode: addr.pincode ?? '',
        post_so_name: addr.village?.post_so_name ?? '',
        taluka_name: addr.village?.taluka_name ?? '',
        district_name: addr.village?.district_name ?? '',
        state_name: addr.village?.state_name ?? addr.state ?? '',
        is_default: !!addr.is_default,
      };

      // Show Address Modal
      getModal('#addressModal')?.show();
    },

    cancelAddressForm() {
      getModal('#addressModal')?.hide();
      getModal('#viewCustomerModal')?.show();
    },

    async searchVillages() {
      if (this.villageSearchQuery.trim().length === 0) {
        this.addressForm.village_id = '';
        this.addressForm.village_name = '';
        this.addressForm.city = '';
        this.addressForm.state = '';
        this.addressForm.pincode = '';
        this.addressForm.post_so_name = '';
        this.addressForm.taluka_name = '';
        this.addressForm.district_name = '';
        this.addressForm.state_name = '';
      }
      if (this.villageSearchQuery.length < 3) {
        this.villageResults = [];
        return;
      }
      try {
        const data = await apiFetch(`/api/villages/search?q=${encodeURIComponent(this.villageSearchQuery)}`);
        this.villageResults = data.data ?? [];
      } catch (err) {
        console.error(err);
      }
    },

    selectVillage(v) {
      this.addressForm.village_id = v.id;
      this.addressForm.village_name = v.village_name;
      this.addressForm.city = v.taluka_name || v.district_name || '';
      this.addressForm.state = v.state_name || '';
      this.addressForm.pincode = v.pincode || '';
      this.addressForm.post_so_name = v.post_so_name || '';
      this.addressForm.taluka_name = v.taluka_name || '';
      this.addressForm.district_name = v.district_name || '';
      this.addressForm.state_name = v.state_name || '';
      this.villageSearchQuery = v.village_name;
      this.villageResults = [];
    },

    async saveAddress() {
      this.savingAddress = true;
      try {
        const f = this.addressForm;

        // Always include status; derive city/state/pincode from village when available
        const payload = {
          label:          f.label,
          status:         'active',
          address_line_1: f.address_line_1,
          address_line_2: f.address_line_2 || '',
          village_id:     f.village_id || null,
          city:           f.city || f.taluka_name || f.district_name || '',
          state:          f.state || f.state_name || '',
          pincode:        f.pincode || '',
          is_default:     f.is_default,
        };

        const url = this.editingAddressId 
          ? `/api/customers/${this.customer.id}/addresses/${this.editingAddressId}`
          : `/api/customers/${this.customer.id}/addresses`;
        const method = this.editingAddressId ? 'PATCH' : 'POST';

        const res = await apiFetch(url, {
          method,
          body: JSON.stringify(payload)
        });

        showToast(res.message || 'Address saved successfully.', 'success');
        
        // Hide Address Modal
        getModal('#addressModal')?.hide();

        // Reload customer profile to refresh addresses list
        await this.loadProfile(this.customer.id);

        // Reopen View Profile Modal
        getModal('#viewCustomerModal')?.show();
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.savingAddress = false;
      }
    },

    async deleteAddress(addr) {
      const confirmed = await confirmDelete({
        title: 'Delete Address?',
        text: `Are you sure you want to delete the address labeled "${addr.label}"?`,
      });
      if (!confirmed) return;

      try {
        const res = await apiFetch(`/api/customers/${this.customer.id}/addresses/${addr.id}`, {
          method: 'DELETE'
        });
        showToast(res.message, 'success');
        this.loadProfile(this.customer.id);
      } catch (err) {
        showToast(err.message, 'danger');
      }
    }
  }));

  // ─── importForm ─────────────────────────────────────────────────────────────
  Alpine.data('importForm', () => ({
    file:     null,
    importing: false,
    result:   null,

    async importCustomers() {
      if (!this.file) {
        showToast('Please select a CSV file.', 'warning');
        return;
      }
      this.importing = true;
      this.result    = null;

      const text = await this.file.text();
      const lines = text.trim().split('\n').filter(l => l.trim());
      if (lines.length < 2) {
        showToast('CSV file is empty or has no data rows.', 'warning');
        this.importing = false;
        return;
      }

      // Format: firstname, middlename, lastname, email, phone, category, status, company_name, kyc_completed
      let created = 0;
      let errors  = [];

      for (let i = 1; i < lines.length; i++) {
        const values = parseCsvLine(lines[i]);
        const [firstname, middlename, lastname, email, phone, category, statusRaw, companyName, kycRaw] = values;
        if (!firstname || !lastname) { errors.push(`Row ${i + 1}: missing first or last name`); continue; }

        const status = ['active', 'inactive', 'suspended'].includes(statusRaw?.toLowerCase()) ? statusRaw.toLowerCase() : 'active';
        const kyc_completed = kycRaw?.toLowerCase() === 'true' || kycRaw === '1';

        try {
          await apiFetch('/api/customers', {
            method: 'POST',
            body: JSON.stringify({
              firstname,
              middlename: middlename || null,
              lastname,
              email: email || null,
              phone: phone || null,
              category: ['individual', 'business'].includes(category?.toLowerCase()) ? category.toLowerCase() : 'individual',
              status,
              company_name: companyName || null,
              kyc_completed,
              is_active: status === 'active',
            }),
          });
          created++;
        } catch (err) {
          errors.push(`Row ${i + 1} (${firstname} ${lastname}): ${err.message}`);
        }
      }

      this.result = { created, errors };
      this.importing = false;

      if (created > 0) {
        showToast(`Imported ${created} customer(s) successfully.`);
        const table = Alpine.$data(document.querySelector('[x-data="customerTable"]'));
        if (table) await table.loadCustomers();
      }
      if (errors.length > 0) {
        showToast(`${errors.length} row(s) failed. See import result.`, 'warning');
      }
    },

    handleFile(event) {
      this.file   = event.target.files[0] ?? null;
      this.result = null;
    },
  }));

});

