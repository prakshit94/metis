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

document.addEventListener('alpine:init', () => {


  // ─── Village Table Controller ─────────────────────────────────────────────
  Alpine.data('villageTable', () => ({
    villages: [],
    isLoading: false,
    currentPage: 1,
    totalPages: 1,
    totalVillages: 0,
    itemsPerPage: 15,

    // Filters
    searchQuery: '',
    stateFilter: '',
    districtFilter: '',
    talukaFilter: '',
    serviceFilter: '',
    deletedFilter: '',
    sortField: 'id',
    sortDirection: 'desc',

    // Selection
    selectedVillages: [],

    // Options Lists
    statesList: [],
    districtsList: [],
    talukasList: [],
    servicesOptions: [],

    charts: {},

    init() {
      this.loadServicesOptions();
      this.loadVillages();
      window.addEventListener('village-updated', () => this.loadVillages());
    },

    async loadServicesOptions() {
      try {
        const data = await apiFetch('/api/villages/services-options');
        this.servicesOptions = data ?? [];
      } catch (err) {
        console.error(err);
      }
    },

    async loadVillages() {
      this.isLoading = true;
      try {
        const params = new URLSearchParams({
          page: this.currentPage,
          per_page: this.itemsPerPage,
          sort_by: this.sortField,
          sort_dir: this.sortDirection,
        });

        if (this.searchQuery)    params.set('search', this.searchQuery);
        if (this.stateFilter)    params.set('state', this.stateFilter);
        if (this.districtFilter) params.set('district', this.districtFilter);
        if (this.talukaFilter)   params.set('taluka', this.talukaFilter);
        if (this.serviceFilter)  params.set('service_id', this.serviceFilter);
        if (this.deletedFilter)  params.set('deleted', this.deletedFilter);

        const data = await apiFetch(`/api/villages?${params.toString()}`);
        
        const list = data.pagination?.data ?? data.data ?? [];
        this.villages = list.map(v => this._mapVillage(v));
        
        this.currentPage = data.pagination?.current_page ?? data.current_page ?? 1;
        this.totalPages = data.pagination?.last_page ?? data.last_page ?? 1;
        this.totalVillages = data.pagination?.total ?? data.total ?? 0;

        this.statesList    = data.states    ?? [];
        this.districtsList = data.districts ?? [];
        this.talukasList   = data.talukas   ?? [];

        this.$nextTick(() => {
          this.initCharts(data.stats ?? {});
        });
      } catch (err) {
        showToast('Failed to load villages: ' + err.message, 'danger');
      } finally {
        this.isLoading = false;
      }
    },

    _mapVillage(v) {
      const activeMappings = (v.mappings ?? []).filter(m => m.is_available);
      return {
        ...v,
        active_mappings: activeMappings,
      };
    },

    // Pagination
    goToPage(page) {
      if (page < 1 || page > this.totalPages) return;
      this.currentPage = page;
      this.loadVillages();
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
      if (this.totalVillages === 0) return 0;
      return (this.currentPage - 1) * this.itemsPerPage + 1;
    },

    get pageTo() {
      return Math.min(this.currentPage * this.itemsPerPage, this.totalVillages);
    },

    // Sorting
    sort(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'desc'; // Default desc
      }
      this.currentPage = 1;
      this.loadVillages();
    },

    searchVillages() {
      this.currentPage = 1;
      this.loadVillages();
    },

    filterVillages() {
      this.currentPage = 1;
      this.loadVillages();
    },

    getSortIcon(field) {
      if (this.sortField !== field) return 'bi-arrow-down-up text-muted small';
      return this.sortDirection === 'asc' ? 'bi-arrow-up text-primary' : 'bi-arrow-down text-primary';
    },

    resetFilters() {
      this.searchQuery    = '';
      this.stateFilter    = '';
      this.districtFilter = '';
      this.talukaFilter   = '';
      this.serviceFilter  = '';
      this.deletedFilter  = '';
      this.currentPage    = 1;
      this.loadVillages();
    },

    // Selection
    toggleAll(checked) {
      this.selectedVillages = checked ? this.villages.map(v => v.id) : [];
    },

    toggleVillage(id) {
      if (this.selectedVillages.includes(id)) {
        this.selectedVillages = this.selectedVillages.filter(i => i !== id);
      } else {
        this.selectedVillages = [...this.selectedVillages, id];
      }
    },

    // CRUD/Actions
    editVillage(v) {
      const form = Alpine.$data(document.querySelector('[x-data="villageForm"]'));
      if (form) form.loadVillage(v);
      getModal('#villageModal')?.show();
    },

    openCreateVillage() {
      const form = Alpine.$data(document.querySelector('[x-data="villageForm"]'));
      if (form) form.resetForm();
      getModal('#villageModal')?.show();
    },

    openServicesModal(v) {
      const svcModal = Alpine.$data(document.querySelector('[x-data="villageServices"]'));
      if (svcModal) svcModal.loadVillageServices(v);
      getModal('#servicesModal')?.show();
    },

    openBulkServiceModal() {
      const bulkModal = Alpine.$data(document.querySelector('[x-data="bulkServicesForm"]'));
      if (bulkModal) {
        bulkModal.count = this.selectedVillages.length;
        bulkModal.ids = [...this.selectedVillages];
        bulkModal.resetForm();
      }
      getModal('#bulkServicesModal')?.show();
    },

    async deleteVillage(v) {
      const confirmed = await confirmDelete({
        title: 'Delete Village?',
        text: `Are you sure you want to delete ${v.village_name}? This will remove geolocation references for customer addresses in this village.`,
        confirmButtonText: 'Yes, delete',
      });
      if (!confirmed) return;

      try {
        const res = await apiFetch(`/api/villages/${v.id}`, { method: 'DELETE' });
        showToast(res.message, 'success');
        this.loadVillages();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async restoreVillage(v) {
      try {
        const res = await apiFetch(`/api/villages/${v.id}/restore`, { method: 'PATCH' });
        showToast(res.message || 'Village restored.', 'success');
        this.loadVillages();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async forceDeleteVillage(v) {
      const confirmed = await confirmDelete({
        title: 'Permanently Delete?',
        text: `This cannot be undone. ${v.village_name} will be removed forever.`,
        confirmButtonText: 'Yes, permanently delete',
      });
      if (!confirmed) return;

      try {
        const res = await apiFetch(`/api/villages/${v.id}/force-delete`, { method: 'DELETE' });
        showToast(res.message || 'Village permanently deleted.', 'success');
        this.loadVillages();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    get hasSelectedDeletedVillages() {
      return this.selectedVillages.some(id => this.villages.find(v => v.id === id)?.deleted_at);
    },

    async bulkAction(action) {
      if (this.selectedVillages.length === 0) {
        showToast('Please select villages first.', 'warning');
        return;
      }

      if (action === 'delete') {
        const confirmed = await confirmDelete({
          title: 'Delete selected villages?',
          text: `Are you sure you want to delete ${this.selectedVillages.length} village(s)?`,
        });
        if (!confirmed) return;
      }

      try {
        const res = await apiFetch('/api/villages/bulk-action', {
          method: 'POST',
          body: JSON.stringify({ action, ids: this.selectedVillages }),
        });
        showToast(res.message, 'success');
        this.selectedVillages = [];
        this.loadVillages();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    // Stats
    stats: {
      total: 0,
      pincodes: 0,
      districts_count: 0,
      services: 0,
    },

    get districtBreakdown() {
      const counts = {};
      this.villages.forEach(v => {
        const dist = v.district_name || 'Unknown';
        counts[dist] = (counts[dist] || 0) + 1;
      });
      
      const totalInPage = this.villages.length || 1;
      return Object.entries(counts).map(([name, count]) => ({
        name, count,
        percentage: Math.round((count / totalInPage) * 100),
      })).sort((a,b) => b.count - a.count).slice(0, 5);
    },

    initCharts(serverStats) {
      if (serverStats.total) {
        this.stats = serverStats;
      }
      this._initDistributionChart();
    },

    _initDistributionChart() {
      const el = document.querySelector('#serviceDistributionChart');
      if (!el || el.hasAttribute('data-chart-initialized')) return;
      el.setAttribute('data-chart-initialized', 'true');

      // Aggregate how many villages have which services active
      const serviceCounts = {};
      this.servicesOptions.forEach(s => {
        serviceCounts[s.name] = 0;
      });

      this.villages.forEach(v => {
        v.active_mappings.forEach(m => {
          if (m.service) {
            serviceCounts[m.service.name] = (serviceCounts[m.service.name] || 0) + 1;
          }
        });
      });

      const seriesData = Object.values(serviceCounts);
      const categories = Object.keys(serviceCounts);

      this.charts.distribution = new ApexCharts(el, {
        series: [{ name: 'Serviceable Villages', data: seriesData.length > 0 ? seriesData : [0] }],
        chart: { type: 'bar', height: 250, toolbar: { show: false } },
        colors: ['#10b981'],
        plotOptions: { bar: { borderRadius: 4, horizontal: true } },
        xaxis: {
          categories: categories.length > 0 ? categories : ['No Services'],
          labels: { style: { colors: '#64748b' } }
        },
        grid: { show: false },
      });
      this.charts.distribution.render();
    }
  }));

  // ─── Village Form Controller ───────────────────────────────────────────────
  Alpine.data('villageForm', () => ({
    editingVillageId: null,
    saving: false,
    form: {
      village_name: '',
      pincode: '',
      post_so_name: '',
      taluka_name: '',
      district_name: '',
      state_name: '',
    },

    loadVillage(v) {
      this.editingVillageId = v.id;
      this.form = {
        village_name: v.village_name ?? '',
        pincode: v.pincode ?? '',
        post_so_name: v.post_so_name ?? '',
        taluka_name: v.taluka_name ?? '',
        district_name: v.district_name ?? '',
        state_name: v.state_name ?? '',
      };
    },

    resetForm() {
      this.editingVillageId = null;
      this.form = {
        village_name: '',
        pincode: '',
        post_so_name: '',
        taluka_name: '',
        district_name: '',
        state_name: '',
      };
    },

    async saveVillage() {
      this.saving = true;
      try {
        const payload = { ...this.form };
        const url = this.editingVillageId ? `/api/villages/${this.editingVillageId}` : '/api/villages';
        const method = this.editingVillageId ? 'PATCH' : 'POST';

        const res = await apiFetch(url, {
          method,
          body: JSON.stringify(payload)
        });

        showToast(res.message || 'Village saved successfully.', 'success');
        getModal('#villageModal')?.hide();
        window.dispatchEvent(new CustomEvent('village-updated'));
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.saving = false;
      }
    }
  }));

  // ─── Village Services Controller ───────────────────────────────────────────
  Alpine.data('villageServices', () => ({
    villageId: null,
    villageName: '',
    pincode: '',
    services: [],
    mappings: {},
    saving: false,

    async loadVillageServices(v) {
      this.villageId = v.id;
      this.villageName = v.village_name;
      this.pincode = v.pincode;
      this.services = [];
      this.mappings = {};

      try {
        const table = Alpine.$data(document.querySelector('[x-data="villageTable"]'));
        const options = table?.servicesOptions ?? [];
        this.services = options;

        // Initialize empty mappings
        options.forEach(s => {
          this.mappings[s.id] = {
            is_available: false,
            priority: 0,
            remarks: '',
            serviceable_from_date: null,
            serviceable_to_date: null,
          };
        });

        // Load existing mappings
        const data = await apiFetch(`/api/villages/${v.id}`);
        const existing = data.data?.mappings ?? [];
        existing.forEach(m => {
          if (this.mappings[m.service_id]) {
            this.mappings[m.service_id] = {
              is_available: !!m.is_available,
              priority: m.priority ?? 0,
              remarks: m.remarks ?? '',
              serviceable_from_date: m.serviceable_from_date ?? null,
              serviceable_to_date: m.serviceable_to_date ?? null,
            };
          }
        });
      } catch (err) {
        showToast('Failed to load mappings: ' + err.message, 'danger');
      }
    },

    async saveServices() {
      this.saving = true;
      try {
        const res = await apiFetch(`/api/villages/${this.villageId}`, {
          method: 'PATCH',
          body: JSON.stringify({
            village_name: this.villageName,
            pincode: this.pincode,
            services: this.mappings,
          })
        });

        showToast(res.message || 'Services updated successfully.', 'success');
        getModal('#servicesModal')?.hide();
        window.dispatchEvent(new CustomEvent('village-updated'));
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.saving = false;
      }
    }
  }));

  // ─── Bulk Services Form Controller ─────────────────────────────────────────
  Alpine.data('bulkServicesForm', () => ({
    count: 0,
    ids: [],
    serviceId: '',
    status: 'available',
    saving: false,
    services: [],

    resetForm() {
      this.serviceId = '';
      this.status = 'available';
      
      const table = Alpine.$data(document.querySelector('[x-data="villageTable"]'));
      this.services = table?.servicesOptions ?? [];
    },

    async updateServices() {
      this.saving = true;
      try {
        const res = await apiFetch('/api/villages/bulk-action', {
          method: 'POST',
          body: JSON.stringify({
            action: 'service-update',
            ids: this.ids,
            service_id: this.serviceId,
            status: this.status,
          })
        });

        showToast(res.message || 'Bulk update applied.', 'success');
        
        const table = Alpine.$data(document.querySelector('[x-data="villageTable"]'));
        if (table) {
          table.selectedVillages = [];
          table.loadVillages();
        }
        
        getModal('#bulkServicesModal')?.hide();
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.saving = false;
      }
    }
  }));

});
