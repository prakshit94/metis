import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';
import { createSearchComponent } from '../utils/search-component.js';

// ─── CSRF helper ─────────────────────────────────────────────────────────────
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

// ─── API fetch helper ────────────────────────────────────────────────────────
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
    if (res.status === 403 || message.toLowerCase().includes("authoriz") || message.toLowerCase().includes("forbidden")) { window.location.href = "/"; return; }
    throw new Error(message);
  }

  return data;
}

// ─── Modal helper ────────────────────────────────────────────────────────────
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

document.addEventListener('alpine:init', () => {
  Alpine.data('orderTable', () => ({
    orders: [],
    selectedOrders: [],
    currentPage: 1,
    totalPages: 1,
    totalOrders: 0,
    itemsPerPage: 15,
    
    // Filters state
    searchQuery: '',
    statusFilter: [],
    dateFilter: '',
    productFilter: '',
    fulfillmentFilter: '',
    stateFilter: [],
    districtFilter: [],
    talukaFilter: [],
    villageFilter: [],
    carrierFilter: '',
    warehouseFilter: '',
    fromDate: '',
    toDate: '',
    sortField: 'id',
    sortDirection: 'desc',
    isLoading: false,
    
    // ApexCharts settings
    charts: {},
    _resizeHandler: null,
    chartsInitialized: false,

    // Statistics
    stats: {
      total: 0,
      future_order: 0,
      future_order_amount: 0,
      pending: 0,
      pending_amount: 0,
      pending_confirmation: 0,
      pending_confirmation_amount: 0,
      confirmed: 0,
      confirmed_amount: 0,
      processing: 0,
      processing_amount: 0,
      ready_to_ship: 0,
      ready_to_ship_amount: 0,
      dispatched: 0,
      dispatched_amount: 0,
      delivered: 0,
      delivered_amount: 0,
      cancelled: 0,
      cancelled_amount: 0,
      revenue: 0
    },

    statusStats: [],
    warehouseStats: [],
    showWarehouseStats: false,
    visibleWarehouseStat: '',
    trendsData: [],

    // Dropdown lists
    productsList: [],
    statesList: [],
    districtsList: [],
    talukasList: [],
    villagesList: [],
    carriersList: [],
    warehousesList: [],
    allowedFilterStatuses: [],
    allFilterStatuses: ['future_order', 'pending', 'pending_confirmation', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'return_requested', 'returned', 'cancelled'],

    // Modal data state
    selectedOrder: null,
    shipOrderId: '',
    shipOrderNo: '',
    shipCarrierName: '',
    shipCarrierOptions: [],
    shipTrackingNo: '',
    importRows: [],
    importing: false,

    // Items Modal state
    selectedItemsOrder: null,

    // Return Modal state
    returnModalOrder: null,
    returnReason: '',
    returnNotes: '',
    returnItems: [],

    // Confirm Modal state
    confirmModalOrder: null,
    confirmAction: 'now', // 'now' or 'schedule'
    scheduledConfirmDate: '',
    scheduleReason: '',
    confirmNotes: '',

    // Cancel Modal state
    cancelModalOrder: null,
    cancelReason: '',
    cancelNotes: '',

    showStatusDropdown: false,

    // Multi-select dropdown states
    showStateDropdown: false,
    stateSearch: '',
    showDistrictDropdown: false,
    districtSearch: '',
    showTalukaDropdown: false,
    talukaSearch: '',
    showVillageDropdown: false,
    villageSearch: '',

    get filteredStates() {
        let list = Object.values(this.statesList || {});
        if (!this.stateSearch) return list;
        return list.filter(s => s && s.toLowerCase().includes(this.stateSearch.toLowerCase()));
    },
    
    get filteredDistricts() {
        let list = Object.values(this.districtsList || {});
        if (!this.districtSearch) return list;
        return list.filter(d => d && d.toLowerCase().includes(this.districtSearch.toLowerCase()));
    },

    get filteredTalukas() {
        let list = Object.values(this.talukasList || {});
        if (!this.talukaSearch) return list;
        return list.filter(t => t && t.toLowerCase().includes(this.talukaSearch.toLowerCase()));
    },

    get filteredVillages() {
        let list = Object.values(this.villagesList || {});
        if (!this.villageSearch) return list;
        return list.filter(v => v && v.toLowerCase().includes(this.villageSearch.toLowerCase()));
    },

    toggleFilter(type, value) {
        if (type === 'status') {
            if (this.statusFilter.includes(value)) this.statusFilter = this.statusFilter.filter(v => v !== value);
            else this.statusFilter.push(value);
        } else if (type === 'state') {
            if (this.stateFilter.includes(value)) this.stateFilter = this.stateFilter.filter(v => v !== value);
            else this.stateFilter.push(value);
            this.districtFilter = [];
            this.talukaFilter = [];
            this.villageFilter = [];
        } else if (type === 'district') {
            if (this.districtFilter.includes(value)) this.districtFilter = this.districtFilter.filter(v => v !== value);
            else this.districtFilter.push(value);
            this.talukaFilter = [];
            this.villageFilter = [];
        } else if (type === 'taluka') {
            if (this.talukaFilter.includes(value)) this.talukaFilter = this.talukaFilter.filter(v => v !== value);
            else this.talukaFilter.push(value);
            this.villageFilter = [];
        } else if (type === 'village') {
            if (this.villageFilter.includes(value)) this.villageFilter = this.villageFilter.filter(v => v !== value);
            else this.villageFilter.push(value);
        }
        this.filterOrders();
    },

    toggleAllFilter(type) {
        if (type === 'status') {
            let list = this.allowedFilterStatuses || [];
            if (this.statusFilter.length === list.length) this.statusFilter = [];
            else this.statusFilter = [...list];
        } else if (type === 'state') {
            let list = Object.values(this.statesList || {});
            if (this.stateFilter.length === list.length) this.stateFilter = [];
            else this.stateFilter = [...list];
            this.districtFilter = [];
            this.talukaFilter = [];
            this.villageFilter = [];
        } else if (type === 'district') {
            let list = Object.values(this.districtsList || {});
            if (this.districtFilter.length === list.length) this.districtFilter = [];
            else this.districtFilter = [...list];
            this.talukaFilter = [];
            this.villageFilter = [];
        } else if (type === 'taluka') {
            let list = Object.values(this.talukasList || {});
            if (this.talukaFilter.length === list.length) this.talukaFilter = [];
            else this.talukaFilter = [...list];
            this.villageFilter = [];
        } else if (type === 'village') {
            let list = Object.values(this.villagesList || {});
            if (this.villageFilter.length === list.length) this.villageFilter = [];
            else this.villageFilter = [...list];
        }
        this.filterOrders();
    },

    init() {
      this.statusFilter = [...(this.allowedFilterStatuses || [])];
      this.loadOrders();
      
      this.$watch('visibleWarehouseStat', value => {
        let whId = '';
        if (value) {
          const wh = Object.values(this.warehousesList || {}).find(w => w.name === value);
          if (wh) whId = wh.id;
        }
        if (this.warehouseFilter !== whId) {
          this.warehouseFilter = whId;
          this.filterOrders();
        }
      });
      
      this.$watch('warehouseFilter', value => {
        let whName = '';
        if (value) {
          const wh = Object.values(this.warehousesList || {}).find(w => w.id == value);
          if (wh) whName = wh.name;
        }
        if (this.visibleWarehouseStat !== whName) {
          this.visibleWarehouseStat = whName;
        }
      });
      
      const params = new URLSearchParams(window.location.search);
      if (params.has('success')) {
        showToast(params.get('success'));
        params.delete('success');
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
      }

      // Delay chart initialization to ensure DOM is fully ready
      setTimeout(() => {
        this.initCharts();
        this.initResizeHandler();
      }, 500);

      const onHide = () => this.destroy();
      window.addEventListener('pagehide', onHide, { once: true });
    },

    destroy() {
      if (this._resizeHandler) {
        window.removeEventListener('resize', this._resizeHandler);
        this._resizeHandler = null;
      }
      this.clearExistingCharts();
    },

    clearExistingCharts() {
      Object.values(this.charts).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') {
          chart.destroy();
        }
      });
      this.charts = {};
      this.chartsInitialized = false;
    },

    initResizeHandler() {
      this._resizeHandler = () => {
        Object.values(this.charts).forEach(chart => {
          if (chart && typeof chart.updateOptions === 'function') {
            chart.updateOptions({ chart: { width: '100%' } }, false, true);
          }
        });
      };
      window.addEventListener('resize', this._resizeHandler);
    },

    loadOrders() {
      this.isLoading = true;
      const params = new URLSearchParams();
      
      if (this.searchQuery) params.append('search', this.searchQuery);
      if (this.statusFilter.length) params.append('status', this.statusFilter.join(','));
      if (this.productFilter) params.append('product', this.productFilter);
      if (this.fulfillmentFilter) params.append('fulfillment', this.fulfillmentFilter);
      if (this.stateFilter.length) params.append('state', this.stateFilter.join(','));
      if (this.districtFilter.length) params.append('district', this.districtFilter.join(','));
      if (this.talukaFilter.length) params.append('taluka', this.talukaFilter.join(','));
      if (this.villageFilter.length) params.append('village', this.villageFilter.join(','));
      if (this.carrierFilter) params.append('carrier', this.carrierFilter);
      if (this.warehouseFilter) params.append('warehouse', this.warehouseFilter);
      
      // Handle Date selection
      let activeFromDate = this.fromDate;
      let activeToDate = this.toDate;
      
      if (this.dateFilter) {
        const today = new Date();
        const formatDate = (date) => {
          const y = date.getFullYear();
          const m = String(date.getMonth() + 1).padStart(2, '0');
          const d = String(date.getDate()).padStart(2, '0');
          return `${y}-${m}-${d}`;
        };
        
        if (this.dateFilter === 'today') {
          activeFromDate = formatDate(today);
          activeToDate = formatDate(today);
        } else if (this.dateFilter === 'yesterday') {
          const yesterday = new Date(today.getTime() - 24 * 60 * 60 * 1000);
          activeFromDate = formatDate(yesterday);
          activeToDate = formatDate(yesterday);
        } else if (this.dateFilter === 'week') {
          const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
          activeFromDate = formatDate(weekAgo);
          activeToDate = formatDate(today);
        } else if (this.dateFilter === 'month') {
          const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
          activeFromDate = formatDate(monthAgo);
          activeToDate = formatDate(today);
        } else if (this.dateFilter === 'prev_month') {
          const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1, 12, 0, 0);
          const lastDay = new Date(today.getFullYear(), today.getMonth(), 0, 12, 0, 0);
          activeFromDate = formatDate(firstDay);
          activeToDate = formatDate(lastDay);
        }
      }
      
      if (activeFromDate) params.append('from_date', activeFromDate);
      if (activeToDate) params.append('to_date', activeToDate);
      
      params.append('limit', this.itemsPerPage);
      params.append('page', this.currentPage);
      params.append('sort_field', this.sortField);
      params.append('sort_direction', this.sortDirection);

      apiFetch(`/orders?${params.toString()}`)
        .then(data => {
          this.orders = (data.orders.data || []).map(o => this.mapOrder(o));
          this.currentPage = data.orders.current_page || 1;
          this.totalPages = data.orders.last_page || 1;
          this.totalOrders = data.orders.total || 0;
          
          if (data.stats) {
            this.stats = {
              total: data.stats.total,
              future_order: data.stats.future_order,
              future_order_amount: data.stats.future_order_amount,
              pending: data.stats.pending,
              pending_amount: data.stats.pending_amount,
              pending_confirmation: data.stats.pending_confirmation,
              pending_confirmation_amount: data.stats.pending_confirmation_amount,
              confirmed: data.stats.confirmed,
              confirmed_amount: data.stats.confirmed_amount,
              processing: data.stats.processing,
              processing_amount: data.stats.processing_amount,
              ready_to_ship: data.stats.ready_to_ship,
              ready_to_ship_amount: data.stats.ready_to_ship_amount,
              dispatched: data.stats.dispatched,
              dispatched_amount: data.stats.dispatched_amount,
              delivered: data.stats.delivered,
              delivered_amount: data.stats.delivered_amount,
              cancelled: data.stats.cancelled,
              cancelled_amount: data.stats.cancelled_amount,
              returned: data.stats.returned,
              returned_amount: data.stats.returned_amount,
              return_requested: data.stats.return_requested,
              return_requested_amount: data.stats.return_requested_amount,
              revenue: data.stats.total_amount
            };

            this.statusStats = [
              { name: 'Future', count: this.stats.future_order, percentage: this.stats.total ? Math.round((this.stats.future_order / this.stats.total) * 100) : 0, color: '#a855f7' },
              { name: 'Pending', count: this.stats.pending, percentage: this.stats.total ? Math.round((this.stats.pending / this.stats.total) * 100) : 0, color: '#f97316' },
              { name: 'Pending Confirmation', count: this.stats.pending_confirmation, percentage: this.stats.total ? Math.round((this.stats.pending_confirmation / this.stats.total) * 100) : 0, color: '#f97316' },
              { name: 'Confirmed', count: this.stats.confirmed, percentage: this.stats.total ? Math.round((this.stats.confirmed / this.stats.total) * 100) : 0, color: '#0ea5e9' },
              { name: 'Processing', count: this.stats.processing, percentage: this.stats.total ? Math.round((this.stats.processing / this.stats.total) * 100) : 0, color: '#3b82f6' },
              { name: 'Ready to Ship', count: this.stats.ready_to_ship, percentage: this.stats.total ? Math.round((this.stats.ready_to_ship / this.stats.total) * 100) : 0, color: '#6366f1' },
              { name: 'Dispatched', count: this.stats.dispatched, percentage: this.stats.total ? Math.round((this.stats.dispatched / this.stats.total) * 100) : 0, color: '#14b8a6' },
              { name: 'Delivered', count: this.stats.delivered, percentage: this.stats.total ? Math.round((this.stats.delivered / this.stats.total) * 100) : 0, color: '#10b981' },
              { name: 'Cancelled', count: this.stats.cancelled, percentage: this.stats.total ? Math.round((this.stats.cancelled / this.stats.total) * 100) : 0, color: '#ef4444' },
              { name: 'Return Requested', count: this.stats.return_requested, percentage: this.stats.total ? Math.round((this.stats.return_requested / this.stats.total) * 100) : 0, color: '#f59e0b' },
              { name: 'Returned', count: this.stats.returned, percentage: this.stats.total ? Math.round((this.stats.returned / this.stats.total) * 100) : 0, color: '#6b7280' }
            ].filter(stat => stat.count > 0);

            if (data.trends) {
              this.trendsData = data.trends;
            }
            if (data.warehouseStats) {
              this.warehouseStats = data.warehouseStats;
            }

            this.initCharts();
          }

          if (data.districts) this.districtsList = data.districts;
          if (data.talukas) this.talukasList = data.talukas;
          if (data.villages) this.villagesList = data.villages;
          if (data.allowed_filter_statuses) this.allowedFilterStatuses = data.allowed_filter_statuses;
          if (data.carriers && data.carriers.length) this.carriersList = data.carriers;
          if (data.warehousesList) this.warehousesList = data.warehousesList;
        })
        .catch(err => {
          showToast(err.message, 'danger');
        })
        .finally(() => {
          this.isLoading = false;
        });
    },

    mapOrder(o) {
      const formatAddress = (orderObj, prefix) => {
        const addressObj = prefix === 'shipping' ? orderObj.shipping_address : (prefix === 'billing' ? orderObj.billing_address : null);
        
        if (addressObj) {
            const villageName = addressObj.village ? addressObj.village.village_name : addressObj.village_name;
            const taluka = addressObj.village ? addressObj.village.taluka_name : addressObj.taluka;
            const district = addressObj.village ? addressObj.village.district_name : addressObj.district;
            const po = addressObj.village ? addressObj.village.post_so_name : addressObj.post_office;

            const parts = [
              addressObj.address_line_1,
              addressObj.address_line_2,
              villageName ? `Vill: ${villageName}` : null,
              taluka ? `Ta: ${taluka}` : null,
              district ? `Dist: ${district}` : null,
              po ? `PO: ${po}` : null,
              addressObj.city,
              addressObj.state,
              addressObj.pincode,
            ].filter(Boolean);

            return {
              id: addressObj.id,
              label: addressObj.label || '',
              line1: addressObj.address_line_1 || '',
              line2: addressObj.address_line_2 || '',
              city: addressObj.city || '',
              state: addressObj.state || '',
              pincode: addressObj.pincode || '',
              country: 'India',
              village: {
                name: villageName || '',
                taluka: taluka || '',
                district: district || '',
                state: addressObj.state || '',
                postOffice: po || '',
              },
              formatted: parts.join(', ') || 'N/A',
              raw: addressObj,
            };
        }

        // Fallback to old flat structure if relation is missing
        if (!orderObj || !orderObj[`${prefix}_address_id`]) return null;

        const parts = [
          orderObj[`${prefix}_address_line_1`],
          orderObj[`${prefix}_address_line_2`],
          orderObj[`${prefix}_village_name`] ? `Vill: ${orderObj[`${prefix}_village_name`]}` : null,
          orderObj[`${prefix}_taluka`] ? `Ta: ${orderObj[`${prefix}_taluka`]}` : null,
          orderObj[`${prefix}_district`] ? `Dist: ${orderObj[`${prefix}_district`]}` : null,
          orderObj[`${prefix}_post_office`] ? `PO: ${orderObj[`${prefix}_post_office`]}` : null,
          orderObj[`${prefix}_city`],
          orderObj[`${prefix}_state`],
          orderObj[`${prefix}_pincode`],
        ].filter(Boolean);

        return {
          id: orderObj[`${prefix}_address_id`],
          label: '',
          line1: orderObj[`${prefix}_address_line_1`] || '',
          line2: orderObj[`${prefix}_address_line_2`] || '',
          city: orderObj[`${prefix}_city`] || '',
          state: orderObj[`${prefix}_state`] || '',
          pincode: orderObj[`${prefix}_pincode`] || '',
          country: 'India',
          village: orderObj[`${prefix}_village_name`] ? {
            name: orderObj[`${prefix}_village_name`] || '',
            taluka: orderObj[`${prefix}_taluka`] || '',
            district: orderObj[`${prefix}_district`] || '',
            state: orderObj[`${prefix}_state`] || '',
            postOffice: orderObj[`${prefix}_post_office`] || '',
          } : null,
          formatted: parts.join(', ') || 'N/A',
          raw: orderObj,
        };
      };

      const formatMoney = (value) => {
        const amount = Number.parseFloat(value ?? 0);
        return Number.isFinite(amount) ? amount : 0;
      };

      const shipment = Array.isArray(o.shipments) && o.shipments.length ? o.shipments[0] : null;
      const availableServices = (o.shipping_address?.village?.services || [])
        .filter(service => {
          const pivot = service.pivot || {};
          return service.is_active && (pivot.is_available === true || pivot.is_available === 1 || pivot.is_available === '1');
        })
        .sort((a, b) => {
          const priorityA = Number.isFinite(Number(a.pivot?.priority)) ? Number(a.pivot.priority) : 0;
          const priorityB = Number.isFinite(Number(b.pivot?.priority)) ? Number(b.pivot.priority) : 0;
          return priorityA - priorityB || String(a.name).localeCompare(String(b.name));
        })
        .map(service => ({
          name: service.name || 'N/A',
          code: service.code || '',
          description: service.description || '',
          priority: Number.isFinite(Number(service.pivot?.priority)) ? Number(service.pivot.priority) : 0,
          providers: (service.providers || []).map(provider => ({
            name: provider.name || 'N/A',
            phone: provider.phone || '',
          })),
        }));
      const availableCarrierOptions = availableServices.map(service => ({
        name: service.name,
        priority: service.priority,
      }));
      const assignedService = shipment
        ? availableServices.find(service =>
          service.name.trim().toLowerCase() === String(shipment.carrier_name || '').trim().toLowerCase()
        ) || null
        : null;
      const invoice = o.invoice || null;
      const invoicePayments = invoice && Array.isArray(invoice.payments) ? invoice.payments : [];
      const paidAmount = invoicePayments
        .filter(payment => payment.status === 'completed')
        .reduce((sum, payment) => sum + formatMoney(payment.amount || 0), 0);
      const netAmount = formatMoney(invoice ? (invoice.net_amount ?? 0) : 0);
      const payments = Array.isArray(o.payments) ? o.payments : [];
      const latestPaymentWithMethod = [...payments, ...invoicePayments]
        .filter(payment => String(payment.payment_method || '').trim())
        .sort((a, b) => new Date(b.payment_date || 0) - new Date(a.payment_date || 0))[0] || null;
      const formattedPaymentMethod = latestPaymentWithMethod
        ? latestPaymentWithMethod.payment_method.toUpperCase().replace(/_/g, ' ')
        : (invoice ? 'PENDING PAYMENT' : 'NOT RECORDED');

      return {
        id: o.id,
        partyId: o.party_id || null,
        warehouseId: o.warehouse_id || null,
        shippingAddressId: o.shipping_address_id || null,
        billingAddressId: o.billing_address_id || null,
        orderNumber: o.order_no,
        type: o.type || 'sale',
        orderDate: o.order_date,
        rawStatus: o.status,
        status: o.lifecycle_status || o.status,
        scheduledConfirmDate: o.scheduled_confirmation_date,
        confirmAttempts: o.confirmation_attempts || 0,
        statusLabel: o.status_label || (o.lifecycle_status || o.status || '').charAt(0).toUpperCase() + (o.lifecycle_status || o.status || '').slice(1).replace(/_/g, ' '),
        customer: {
          name: o.party ? `${o.party.firstname} ${o.party.lastname}` : 'N/A',
          email: o.party ? o.party.email : 'N/A',
          avatar: o.party && o.party.avatar ? o.party.avatar : '/assets/images/default_avatar.jpeg',
          phone: o.party ? o.party.phone : '',
          relativeName: o.party ? (o.party.relative_name || o.party.relative_name) : '',
          relativePhone: o.party ? o.party.relative_phone : '',
          company: o.party ? o.party.company_name : '',
          pan: o.party ? o.party.pan_number : '',
          gstin: o.party ? o.party.gstin : ''
        },
        warehouse: o.warehouse ? {
          name: o.warehouse.name || o.warehouse.company_name || 'N/A',
          phone: o.warehouse.phone || 'N/A',
          gstin: o.warehouse.gstin || 'N/A',
          address: [
            o.warehouse.address_line_1,
            o.warehouse.address_line_2,
            o.warehouse.city,
            o.warehouse.state,
            o.warehouse.pincode,
          ].filter(Boolean).join(', ') || 'N/A',
        } : null,
        shippingAddress: formatAddress(o, 'shipping'),
        availableCarrierOptions,
        assignedService,
        billingAddress: formatAddress(o, 'billing'),
        invoice: invoice ? {
          number: invoice.invoice_no || 'N/A',
          date: invoice.invoice_date || null,
          status: invoice.status || 'N/A',
          total: formatMoney(invoice.total_amount ?? invoice.total_amount),
          tax: formatMoney(invoice.tax_amount ?? 0),
          net: netAmount,
          paid: paidAmount,
          due: Math.max(0, netAmount - paidAmount),
          paymentCount: invoicePayments.length,
        } : null,
        shipment: shipment ? {
          no: shipment.shipment_no || 'N/A',
          carrier: shipment.carrier_name || 'N/A',
          trackingNo: shipment.tracking_no || 'N/A',
          status: shipment.status || 'N/A',
          shippedAt: shipment.shipped_at || null,
          deliveredAt: shipment.delivered_at || null,
          delivery_attempts: shipment.delivery_attempts || 0,
          next_followup_date: shipment.next_followup_date || null,
          reschedule_reason: shipment.reschedule_reason || null,
          events: Array.isArray(shipment.events) ? shipment.events : [],
        } : null,
        payments: payments.map(payment => ({
          id: payment.id,
          no: payment.payment_no || 'N/A',
          amount: formatMoney(payment.amount || 0),
          method: payment.payment_method || 'N/A',
          status: payment.status || 'N/A',
          statusLabel: payment.status || 'N/A',
          date: payment.payment_date || null,
          transactionId: payment.transaction_id || 'N/A',
        })),
        items: (o.items || []).map(item => {
          const qty = formatMoney(item.quantity) || 1;
          const uPrice = formatMoney(item.unit_price);
          const discAmt = formatMoney(item.discount_amount);
          const type = item.product ? (item.product.default_discount_type || 'percent') : 'percent';
          const baseAmount = uPrice * qty;
          const val = item.product && formatMoney(item.product.default_discount) > 0
            ? formatMoney(item.product.default_discount)
            : (discAmt > 0 
                ? (['flat', 'fixed', 'amount'].includes(type.toLowerCase()) 
                    ? (qty > 0 ? discAmt / qty : 0) 
                    : (baseAmount > 0 ? (discAmt / baseAmount) * 100 : 0)) 
                : 0);

          const isFlat = ['flat', 'fixed', 'amount'].includes(type.toLowerCase());
          const displayVal = Number.isFinite(val) ? val : 0;
          const formattedVal = displayVal % 1 === 0 ? displayVal.toFixed(0) : displayVal.toFixed(2);
          const badgeLabel = displayVal > 0 ? (isFlat ? `₹ ${formattedVal} off` : `${formattedVal}% off`) : '';

          return {
            product_id: item.product_id || (item.product ? item.product.id : null),
            name: item.product ? item.product.name : 'Unknown Product',
            sku: item.product ? item.product.sku || '' : '',
            image: item.product && item.product.image_path ? `/storage/${item.product.image_path}` : null,
            quantity: item.quantity,
            price: item.unit_price,
            discount: discAmt,
            discountType: type,
            discountValue: displayVal,
            discountBadgeLabel: badgeLabel,
            tax: item.tax_amount || 0,
            taxRate: item.tax_rate || 0,
            net: item.total_amount || 0,
            isOutOfStock: item.is_out_of_stock || false,
            availableStock: item.available_stock || 0
          };
        }),
        itemCount: o.items_count || (o.items ? o.items.length : 0),
        total: formatMoney(o.net_amount),
        subtotal: (o.items || []).reduce((sum, item) => sum + (formatMoney(item.unit_price) * formatMoney(item.quantity)), 0),
        taxTotal: formatMoney(o.tax_amount),
        discountTotal: Math.max(0, (o.items || []).reduce((sum, item) => sum + (formatMoney(item.unit_price) * formatMoney(item.quantity)), 0) + formatMoney(o.tax_amount) - formatMoney(o.net_amount)),
        paymentMethod: formattedPaymentMethod,
        couponCode: o.coupon_code || '',
        appliedOfferName: o.applied_offer ? o.applied_offer.name : '',
        isDraft: o.status === 'future_order',
        futureOrderDate: o.future_order_date || null,
        createdBy: {
          name: o.creator ? (o.creator.name || '').trim() : 'N/A',
          email: o.creator ? (o.creator.email || '') : '',
          avatar: o.creator && o.creator.avatar ? o.creator.avatar : '/assets/images/default_avatar.jpeg',
        },
        updatedBy: o.updater ? `${o.updater.name || ''}`.trim() : 'N/A',
        isUnfulfillable: o.is_unfulfillable || false,
        original: o
      };
    },

    formatCurrency(value) {
      const amount = Number.parseFloat(value ?? 0);
      return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
    },

    formatDateTime(value) {
      if (!value) return 'N/A';
      const date = new Date(value);
      return Number.isNaN(date.getTime()) ? 'N/A' : date.toLocaleString();
    },

    formatDate(value) {
      if (!value) return 'N/A';
      const date = new Date(value);
      return Number.isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString();
    },

    getStatusTheme(status) {
      const themes = {
        future_order: 'info',
        pending: 'warning',
        pending_confirmation: 'warning',
        confirmed: 'info',
        processing: 'primary',
        ready_to_ship: 'dark',
        dispatched: 'secondary',
        shipped: 'secondary',
        delivered: 'success',
        cancelled: 'danger',
        return_requested: 'warning',
        returned: 'secondary'
      };
      return themes[status] || 'secondary';
    },

    filterOrders() {
      this.currentPage = 1;
      this.loadOrders();
    },

    clearFilters() {
      this.searchQuery = '';
      this.statusFilter = [...(this.allowedFilterStatuses || [])];
      this.dateFilter = '';
      this.productFilter = '';
      this.fulfillmentFilter = '';
      this.stateFilter = [];
      this.districtFilter = [];
      this.talukaFilter = [];
      this.villageFilter = [];
      this.carrierFilter = '';
      this.warehouseFilter = '';
      this.fromDate = '';
      this.toDate = '';
      this.sortField = 'id';
      this.sortDirection = 'desc';
      this.currentPage = 1;
      this.loadOrders();
    },

    hasActiveAdvancedFilters() {
      return Boolean(
        this.productFilter ||
        this.fulfillmentFilter ||
        this.carrierFilter ||
        this.warehouseFilter ||
        this.fromDate ||
        this.toDate ||
        this.stateFilter.length > 0 ||
        this.districtFilter.length > 0 ||
        this.talukaFilter.length > 0 ||
        this.villageFilter.length > 0
      );
    },

    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'desc';
      }
      this.currentPage = 1;
      this.loadOrders();
    },

    toggleAll(checked) {
      if (checked) {
        this.selectedOrders = this.orders.map(o => String(o.id));
      } else {
        this.selectedOrders = [];
      }
    },

    // ─── Bulk Actions Lifecycle Visibility ──────────────────────────────────

    /**
     * Returns an object of flags indicating which bulk action buttons should
     * be shown based on the lifecycle statuses of the currently selected orders.
     * Only shows the next valid transition for each status present in selection.
     */
    get bulkAvailableActions() {
      if (this.selectedOrders.length === 0) {
        return {
          canConfirm: false,
          canProcess: false,
          canReadyToShip: false,
          canDispatch: false,
          canDeliver: false,
          canCancel: false,
        };
      }

      // Build a Set of statuses for all selected orders
      const selectedOrderObjs = this.orders.filter(o => this.selectedOrders.includes(String(o.id)));
      const statuses = new Set(selectedOrderObjs.map(o => o.status));

      // Cancellable statuses
      const cancellableStatuses = ['pending', 'pending_confirmation', 'confirmed', 'processing', 'ready_to_ship'];

      return {
        // Pending → Confirmed
        canConfirm: statuses.has('pending') || statuses.has('pending_confirmation'),
        // Confirmed → Processing
        canProcess: statuses.has('confirmed'),
        // Processing → Ready to Ship
        canReadyToShip: statuses.has('processing'),
        // Ready to Ship → Dispatched
        canDispatch: statuses.has('ready_to_ship'),
        // Dispatched/Shipped → Delivered
        canDeliver: statuses.has('dispatched') || statuses.has('shipped'),
        // Cancel (any order that is still active)
        canCancel: [...statuses].some(s => cancellableStatuses.includes(s)),
      };
    },

    get bulkDocumentActions() {
      const selectedOrderObjs = this.orders.filter(o => this.selectedOrders.includes(String(o.id)));
      
      const allowedStatuses = ['processing', 'ready_to_ship', 'dispatched', 'shipped', 'delivered'];
      const canPrint = selectedOrderObjs.length > 0 && selectedOrderObjs.some(o => {
          const status = String(o.lifecycle_status || o.status || '').toLowerCase();
          return allowedStatuses.includes(status);
      });

      return {
        canPrint: canPrint,
        canGenerateInvoices: false,
      };
    },

    // ─── Lifecycle Actions ───────────────────────────────────────────────────
    
    confirmOrder(order) {
      if (!order) return;
      if (order.isUnfulfillable) {
        showToast('Cannot confirm: Order contains items with insufficient stock in the assigned warehouse.', 'danger');
        return;
      }
      const query = new URLSearchParams();
      query.set('order_id', order.id);
      if (order.partyId || order.original?.party_id) {
          query.set('customer_id', order.partyId || order.original?.party_id);
      }
      query.set('step', 'confirm');
      window.location.href = `/orders/create?${query.toString()}`;
    },

    async submitConfirmOrder() {
      if (!this.confirmModalOrder) return;
      if (this.confirmAction === 'schedule' && !this.scheduledConfirmDate) {
        showToast('Please select a scheduled date.', 'warning');
        return;
      }
      if (this.confirmAction === 'schedule' && !this.scheduleReason) {
        showToast('Please select a reason for rescheduling.', 'warning');
        return;
      }

      try {
        const payload = {
          action: this.confirmAction,
          scheduled_date: this.confirmAction === 'schedule' ? this.scheduledConfirmDate : null,
          reason: this.confirmAction === 'schedule' ? this.scheduleReason : null,
          notes: this.confirmNotes
        };
        
        const res = await apiFetch(`/orders/${this.confirmModalOrder.id}/confirm`, { 
          method: 'POST',
          body: JSON.stringify(payload)
        });
        
        showToast(res.message || 'Order updated successfully.');
        const modal = getModal('#confirmOrderModal');
        if (modal) modal.hide();
        this.loadOrders();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async processOrder(order) {
      getModal('#orderDetailModal')?.hide();
      const confirmed = await Swal.fire({
        title: 'Process Order?',
        text: `Are you sure you want to mark order ${order.orderNumber} as processing?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, process',
        cancelButtonText: 'Cancel',
        customClass: {
          confirmButton: 'btn btn-primary me-2',
          cancelButton: 'btn btn-secondary',
          popup: 'rounded-4 shadow-lg border-0 bg-body',
          title: 'fs-4 fw-bold text-body-emphasis',
          htmlContainer: 'text-body text-start'
        },
        buttonsStyling: false
      });
      if (!confirmed.isConfirmed) return;

      try {
        const res = await apiFetch(`/orders/${order.id}/processing`, { method: 'POST' });
        showToast(res.message || 'Order moved to processing.');
        this.loadOrders();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async editShipmentDetails(order) {
      if (!order || !order.shipment || !order.original?.shipments?.[0]?.id) return;
      
      getModal('#orderDetailModal')?.hide();
      
      const shipment = order.original.shipments[0];
      const shipmentId = shipment.id;

      const result = await Swal.fire({
        title: 'Edit Shipping Details',
        html: `
          <div class="text-start">
            <label class="form-label fw-bold">Carrier Name <span class="text-danger">*</span></label>
            <select id="swal-edit-carrier" class="form-select mb-3">
              <option value="" disabled>Select Carrier</option>
              ${(this.carriersList || []).map(c => `<option value="${c}" ${c === order.shipment.carrier ? 'selected' : ''}>${c}</option>`).join('')}
            </select>
            <label class="form-label fw-bold">Tracking Number</label>
            <input type="text" id="swal-edit-tracking" class="form-control" value="${order.shipment.trackingNo !== 'N/A' ? order.shipment.trackingNo : ''}" placeholder="Enter tracking details">
            <small class="text-muted mt-1 d-block">For India Post, tracking number is optional.</small>
          </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Save Details',
        cancelButtonText: 'Cancel',
        customClass: {
          confirmButton: 'btn btn-primary me-2',
          cancelButton: 'btn btn-secondary',
          popup: 'rounded-4 shadow-lg border-0 bg-body',
          title: 'fs-4 fw-bold text-body-emphasis',
          htmlContainer: 'text-body text-start'
        },
        buttonsStyling: false,
        preConfirm: () => {
          const cName = document.getElementById('swal-edit-carrier').value;
          const tNo = document.getElementById('swal-edit-tracking').value;
          if (!cName) {
            Swal.showValidationMessage('Please select a carrier');
            return false;
          }
          if (cName !== 'India Post' && (!tNo || !tNo.trim())) {
            Swal.showValidationMessage('Tracking number is required for carriers other than India Post');
            return false;
          }
          return { carrierName: cName, trackingNo: tNo };
        }
      });

      if (!result.isConfirmed) return;
      const { carrierName, trackingNo } = result.value;

      try {
        const res = await apiFetch(`/api/shipping/shipments/${shipmentId}`, {
          method: 'PATCH',
          body: JSON.stringify({
            carrier_name: carrierName,
            tracking_no: trackingNo
          })
        });
        showToast(res.message || 'Shipping details updated.');
        
        // Reload details in modal
        const details = await apiFetch(`/orders/${order.id}`);
        if (details && details.order) {
          this.selectedOrder = this.mapOrder(details.order);
          // Also update it in the main list
          const index = this.orders.findIndex(o => o.id === order.id);
          if (index !== -1) {
            this.orders[index] = this.selectedOrder;
          }
        }
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    openShipModal(order) {
      getModal('#orderDetailModal')?.hide();
      this.shipOrderId = order.id;
      this.shipOrderNo = order.orderNumber;
      const availableCarriers = order?.availableCarrierOptions || [];
      // When this address has mapped services, show only those options in
      // priority order. Otherwise retain the complete carrier list.
      this.shipCarrierOptions = availableCarriers.length
        ? availableCarriers
        : this.carriersList.map(name => ({ name, priority: null }));

      // Available carriers are already sorted by priority, so always default
      // to the highest-priority (lowest number) option.
      this.shipCarrierName = this.shipCarrierOptions[0]?.name || '';
      this.shipTrackingNo = '';
      if (!this.shipCarrierOptions.length) {
        this.shipCarrierOptions = this.carriersList.map(name => ({ name, priority: null }));
        this.shipCarrierName = this.shipCarrierOptions[0]?.name || '';
      }
      getModal('#createShipmentModal')?.show();
    },

    async shipOrder() {
      if (!this.shipCarrierName) {
        showToast('Please select a Carrier name.', 'warning');
        return;
      }
      if (this.shipCarrierName !== 'India Post' && (!this.shipTrackingNo || !this.shipTrackingNo.trim())) {
        showToast('Please enter a Tracking Number.', 'warning');
        return;
      }
      try {
        const res = await apiFetch(`/orders/${this.shipOrderId}/ship`, {
          method: 'POST',
          body: JSON.stringify({
            carrier_name: this.shipCarrierName,
            tracking_no: this.shipTrackingNo
          })
        });
        showToast(res.message || 'Order marked as ready to ship.');
        getModal('#createShipmentModal')?.hide();
        this.loadOrders();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async dispatchOrder(order) {
      getModal('#orderDetailModal')?.hide();
      const confirmed = await Swal.fire({
        title: 'Dispatch Order?',
        text: `Mark order ${order.orderNumber} as dispatched?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, dispatch',
        cancelButtonText: 'Cancel',
        customClass: {
          confirmButton: 'btn btn-primary me-2',
          cancelButton: 'btn btn-secondary',
          popup: 'rounded-4 shadow-lg border-0 bg-body',
          title: 'fs-4 fw-bold text-body-emphasis',
          htmlContainer: 'text-body text-start'
        },
        buttonsStyling: false
      });
      if (!confirmed.isConfirmed) return;

      try {
        const res = await apiFetch(`/orders/${order.id}/dispatch`, { method: 'POST' });
        showToast(res.message || 'Order marked as dispatched.');
        this.loadOrders();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    deliverOrder(order) {
      getModal('#orderDetailModal')?.hide();
      const userNameMeta = document.querySelector('meta[name="user-name"]');
      const userName = userNameMeta ? userNameMeta.content : '';

      this.deliverModalOrder = order;
      this.deliverAction = 'now';
      this.scheduledDeliveryDate = '';
      this.scheduleDeliveryReason = '';
      this.deliveredBy = userName;
      this.deliverNotes = '';
      const modal = getModal('#deliverOrderModal');
      if (modal) modal.show();
    },

    async submitDeliverOrder() {
      if (!this.deliverModalOrder) return;
      if (this.deliverAction === 'schedule' && !this.scheduledDeliveryDate) {
        showToast('Please select a scheduled date.', 'warning');
        return;
      }
      if (this.deliverAction === 'schedule' && !this.scheduleDeliveryReason) {
        showToast('Please select a reason for rescheduling.', 'warning');
        return;
      }

      try {
        const payload = {
          action: this.deliverAction,
          scheduled_date: this.deliverAction === 'schedule' ? this.scheduledDeliveryDate : null,
          reason: this.deliverAction === 'schedule' ? this.scheduleDeliveryReason : null,
          delivered_by: this.deliverAction === 'now' ? this.deliveredBy : null,
          notes: this.deliverNotes
        };
        
        const res = await apiFetch(`/orders/${this.deliverModalOrder.id}/deliver`, { 
          method: 'POST',
          body: JSON.stringify(payload)
        });
        
        showToast(res.message || 'Order updated successfully.');
        const modal = getModal('#deliverOrderModal');
        if (modal) modal.hide();
        this.loadOrders();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async returnOrder(order) {
      getModal('#orderDetailModal')?.hide();
      this.returnModalOrder = order;
      this.returnReason = '';
      this.returnNotes = '';
      this.returnItems = (order.items || []).map(item => ({
        product_id: item.product_id,
        name: item.name,
        requested_qty: item.quantity,
        max_qty: item.quantity
      }));
      getModal('#initiateReturnModal')?.show();
    },

    async submitReturn() {
      if (!this.returnReason) {
        showToast('Please select a return reason.', 'warning');
        return;
      }
      
      const itemsToReturn = this.returnItems.filter(i => i.requested_qty > 0);
      if (itemsToReturn.length === 0) {
        showToast('Please select at least one item to return with a quantity greater than 0.', 'warning');
        return;
      }

      try {
        const res = await apiFetch(`/orders/${this.returnModalOrder.id}/returns`, { 
          method: 'POST',
          body: JSON.stringify({
            reason: this.returnReason,
            notes: this.returnNotes,
            items: itemsToReturn
          })
        });
        showToast(res.message || 'Return request initiated.');
        getModal('#initiateReturnModal')?.hide();
        this.loadOrders();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async cancelOrder(order) {
      getModal('#orderDetailModal')?.hide();
      this.cancelModalOrder = order;
      this.cancelReason = '';
      this.cancelNotes = '';
      getModal('#cancelOrderModal')?.show();
    },

    async submitCancelOrder() {
      if (!this.cancelReason) {
        showToast('Please select a cancellation reason.', 'warning');
        return;
      }
      
      try {
        const payload = {
          reason: this.cancelReason,
          notes: this.cancelNotes
        };
        
        const res = await apiFetch(`/orders/${this.cancelModalOrder.id}/cancel`, { 
          method: 'POST',
          body: JSON.stringify(payload)
        });
        
        showToast(res.message || 'Order cancelled successfully.');
        getModal('#cancelOrderModal')?.hide();
        this.loadOrders();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async revertStatus(order) {
      getModal('#orderDetailModal')?.hide();
      let options;
      switch (order.status) {
        case 'confirmed': options = { pending: 'Pending' }; break;
        case 'processing': options = { confirmed: 'Confirmed' }; break;
        case 'ready_to_ship': options = { processing: 'Processing' }; break;
        case 'dispatched':
        case 'shipped': options = { ready_to_ship: 'Ready to Ship' }; break;
        case 'delivered': options = { dispatched: 'Dispatched' }; break;
        case 'cancelled': options = { pending: 'Pending' }; break;
        default:
          showToast('Cannot revert from this status.', 'warning');
          return;
      }

      const { value: status } = await Swal.fire({
        title: 'Revert Order Status',
        html: `<p class="text-muted small mb-4">You are about to move this order one step back in the lifecycle. Please confirm the previous status.</p>`,
        icon: 'question',
        input: 'select',
        inputOptions: options,
        inputPlaceholder: 'Select status...',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-arrow-counterclockwise me-1"></i> Revert Status',
        cancelButtonText: 'Cancel',
        customClass: {
          confirmButton: 'btn btn-primary shadow-sm rounded-pill px-4 fw-semibold me-2',
          cancelButton: 'btn btn-light shadow-sm rounded-pill px-4 fw-semibold border-secondary border-opacity-25',
          popup: 'rounded-4 shadow-lg border-0 bg-body',
          title: 'fs-5 fw-bolder text-body-emphasis mt-2',
          input: 'form-select form-select-lg mx-auto w-75 shadow-sm border-secondary border-opacity-25 rounded-3 mb-3',
          icon: 'text-primary border-primary'
        },
        buttonsStyling: false,
        inputValidator: (value) => {
          return new Promise((resolve) => {
            if (value) {
              resolve();
            } else {
              resolve('You need to select a status.');
            }
          });
        }
      });

      if (!status) return;

      try {
        const res = await apiFetch(`/orders/${order.id}/revert-status`, {
          method: 'POST',
          body: JSON.stringify({ status })
        });
        showToast(res.message || 'Order status reverted.');
        this.loadOrders();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    viewOrder(order) {
      this.selectedOrder = order;
      getModal('#orderDetailModal')?.show();
    },

    viewItems(order) {
      this.selectedItemsOrder = order;
      getModal('#orderItemsModal')?.show();
    },

    editOrder(order) {
      const customerId = order.partyId || order.original?.party_id || '';
      const query = new URLSearchParams();
      if (customerId) query.set('customer_id', customerId);
      query.set('order_id', order.id);
      query.set('step', 'review');
      window.location.href = `/orders/create?${query.toString()}`;
    },

    printInvoice(order) {
      window.open(`/orders/${order.id}/invoice-pdf`, '_blank');
    },

    printCOD(order) {
      window.open(`/orders/${order.id}/cod-pdf`, '_blank');
    },

    async generateAndPrintInvoice(order) {
      try {
        const res = await apiFetch(`/orders/${order.id}/generate-invoice`, { method: 'POST' });
        showToast(res.message || 'Invoice generated successfully.');
        
        // Open/Print the PDF invoice in a new tab
        this.printInvoice(order);
        
        // Reload list of orders
        this.loadOrders();
        
        // If the detail modal is currently showing the selected order, update it too
        if (this.selectedOrder && this.selectedOrder.id === order.id) {
          const details = await apiFetch(`/orders/${order.id}`);
          if (details && details.order) {
            this.selectedOrder = this.mapOrder(details.order);
          }
        }
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    // ─── Verification Logs ───────────────────────────────────────────────────
    
    // ─── Bulk Actions ────────────────────────────────────────────────────────
    
    async bulkUpdateStatus(status) {
      if (this.selectedOrders.length === 0) return;

      if (status === 'confirmed') {
        const unfulfillableSelected = this.orders.filter(o => this.selectedOrders.includes(String(o.id)) && o.isUnfulfillable);
        if (unfulfillableSelected.length > 0) {
          showToast(`Skipped ${unfulfillableSelected.length} unfulfillable order(s) due to insufficient stock.`, 'warning');
          this.selectedOrders = this.selectedOrders.filter(id => !unfulfillableSelected.find(u => String(u.id) === id));
          if (this.selectedOrders.length === 0) return;
        }
      }

      let carrierName = null;
      let trackingNo = null;

      if (status === 'ready_to_ship') {
        const result = await Swal.fire({
          title: 'Ready to Ship (Bulk)',
          html: `
            <div class="text-start">
              <label class="form-label fw-bold">Carrier Name <span class="text-danger">*</span></label>
              <select id="swal-carrier" class="form-select mb-3">
                <option value="" disabled selected>Select Carrier</option>
                ${(this.carriersList || []).map(c => `<option value="${c}">${c}</option>`).join('')}
              </select>
              <label class="form-label fw-bold">Tracking Number (Base) <span class="text-danger">*</span></label>
              <input type="text" id="swal-tracking" class="form-control" placeholder="Enter tracking details">
              <small class="text-muted mt-1 d-block">This tracking number will be applied to all selected orders. You can update them individually later if needed.</small>
            </div>
          `,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, update',
          cancelButtonText: 'Cancel',
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-secondary',
            popup: 'rounded-4 shadow-lg border-0 bg-body',
            title: 'fs-4 fw-bold text-body-emphasis',
            htmlContainer: 'text-body text-start'
          },
          buttonsStyling: false,
          preConfirm: () => {
            const cName = document.getElementById('swal-carrier').value;
            const tNo = document.getElementById('swal-tracking').value;
            if (!cName) {
              Swal.showValidationMessage('Please select a carrier');
              return false;
            }
            if (!tNo) {
              Swal.showValidationMessage('Please enter a tracking number');
              return false;
            }
            return { carrierName: cName, trackingNo: tNo };
          }
        });

        if (!result.isConfirmed) return;
        carrierName = result.value.carrierName;
        trackingNo = result.value.trackingNo;
      } else {
        const confirmed = await Swal.fire({
          title: 'Bulk Update Status',
          text: `Update status of ${this.selectedOrders.length} order(s) to "${status.replace(/_/g, ' ')}"?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, update',
          cancelButtonText: 'Cancel',
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-secondary',
            popup: 'rounded-4 shadow-lg border-0 bg-body',
            title: 'fs-4 fw-bold text-body-emphasis',
            htmlContainer: 'text-body text-start'
          },
          buttonsStyling: false
        });
        if (!confirmed.isConfirmed) return;
      }

      try {
        const res = await apiFetch('/orders/bulk-status', {
          method: 'POST',
          body: JSON.stringify({
            order_ids: this.selectedOrders,
            status: status,
            ...(carrierName ? { carrier_name: carrierName } : {}),
            ...(trackingNo ? { tracking_no: trackingNo } : {})
          })
        });
        showToast(res.message || 'Bulk status update completed.');
        this.selectedOrders = [];
        this.loadOrders();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async bulkPrint(type) {
      if (this.selectedOrders.length === 0) return;
      const allowedStatuses = ['processing', 'ready_to_ship', 'dispatched', 'shipped', 'delivered'];
      const validIds = this.orders
          .filter(o => this.selectedOrders.includes(String(o.id)))
          .filter(o => {
              const status = String(o.lifecycle_status || o.status || '').toLowerCase();
              return allowedStatuses.includes(status);
          })
          .map(o => String(o.id));

      if (validIds.length === 0) {
          showToast('No eligible orders selected for printing. Orders must be processing or above.', 'warning');
          return;
      }

      const params = new URLSearchParams();
      validIds.forEach(id => params.append('order_ids[]', id));
      params.append('type', type);

      try {
        const response = await fetch(`/orders/bulk-print?${params.toString()}`, {
          headers: {
            'Accept': 'application/json, application/pdf'
          }
        });

        if (!response.ok) {
          const data = await response.json();
          showToast(data.message || 'Failed to generate bulk print.', 'danger');
          return;
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `bulk-${type}-${new Date().getTime()}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        showToast(`Bulk ${type} generated successfully.`, 'success');
        this.selectedOrders = [];
      } catch (err) {
        showToast('Network error or download failed.', 'danger');
      }
    },

    async generateBulkInvoices() {
      if (this.selectedOrders.length === 0) return;

      const confirmed = await Swal.fire({
        title: 'Generate Bulk Invoices?',
        text: `Generate invoices for ${this.selectedOrders.length} selected order(s)? Invoices will be created only for orders that do not already have one.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, generate',
        cancelButtonText: 'Cancel',
        customClass: {
          confirmButton: 'btn btn-primary me-2',
          cancelButton: 'btn btn-secondary',
          popup: 'rounded-4 shadow-lg border-0 bg-body',
          title: 'fs-4 fw-bold text-body-emphasis',
          htmlContainer: 'text-body text-start'
        },
        buttonsStyling: false
      });
      if (!confirmed.isConfirmed) return;

      try {
        const res = await apiFetch('/orders/bulk-generate-invoices', {
          method: 'POST',
          body: JSON.stringify({ order_ids: this.selectedOrders })
        });
        showToast(res.message || 'Bulk invoices generated successfully.');
        this.selectedOrders = [];
        this.loadOrders();
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    exportOrders() {
      window.open(`/orders/export?${new URLSearchParams({
        search: this.searchQuery,
        status: this.statusFilter.length ? this.statusFilter.join(',') : '',
        product: this.productFilter,
        fulfillment: this.fulfillmentFilter,
        state: this.stateFilter.join(','),
        district: this.districtFilter.join(','),
        taluka: this.talukaFilter.join(','),
        village: this.villageFilter.join(','),
        carrier: this.carrierFilter,
        from_date: this.fromDate,
        to_date: this.toDate
      }).toString()}`, '_blank');
    },

    async exportSelectedOrders() {
      if (!this.selectedOrders.length) return;
      try {
        const res = await fetch('/orders/export-selected', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
          },
          body: JSON.stringify({ ids: this.selectedOrders })
        });
        
        if (!res.ok) {
          const text = await res.text();
          const errData = text ? JSON.parse(text) : {};
          throw new Error(errData.message || 'Export failed');
        }

        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `orders-export-selected-${new Date().getTime()}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    // ─── CSV Import Preview & Confirm ────────────────────────────────────────
    
    async handleImportFileSelect(event) {
      const file = event.target.files[0];
      if (!file) return;

      this.importing = true;
      const formData = new FormData();
      formData.append('file', file);
      formData.append('preview', '1');

      try {
        const res = await fetch('/orders/import', {
          method: 'POST',
          body: formData,
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
          }
        });

        const data = await res.json();
        if (data.preview) {
          this.importRows = data.preview;
          getModal('#importPreviewModal')?.show();
        } else if (data.error) {
          showToast(data.error, 'danger');
        }
      } catch (err) {
        showToast('Error uploading CSV preview.', 'danger');
      } finally {
        this.importing = false;
      }
    },

    async confirmImport() {
      const fileInput = document.getElementById('import-file');
      if (!fileInput.files.length) return;
      
      this.importing = true;
      const formData = new FormData();
      formData.append('file', fileInput.files[0]);
      
      try {
        const res = await fetch('/orders/import', {
          method: 'POST',
          body: formData,
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
          }
        });
        
        const data = await res.json();
        if (data.error) {
          showToast(data.error, 'danger');
        } else {
          showToast(data.message || 'Import successful.', 'success');
          this.cancelImport();
          this.loadOrders();
        }
      } catch (err) {
        showToast('Error finalizing import.', 'danger');
      } finally {
        this.importing = false;
      }
    },

    cancelImport() {
      document.getElementById('import-form').reset();
      this.importRows = [];
      getModal('#importPreviewModal')?.hide();
    },

    // ─── Charts Rendering ────────────────────────────────────────────────────
    
    initCharts() {
      if (this.chartsInitialized) {
        if (this.charts.status) {
          this.charts.status.updateSeries(this.statusStats.map(stat => stat.count));
          this.charts.status.updateOptions({
            labels: this.statusStats.map(stat => stat.name),
            colors: this.statusStats.map(stat => stat.color)
          });
        }
        if (this.charts.orderTrends && this.trendsData && this.trendsData.length) {
          this.charts.orderTrends.updateSeries([{
            name: 'Orders',
            data: this.trendsData.map(t => t.orders)
          }, {
            name: 'Revenue',
            data: this.trendsData.map(t => t.revenue)
          }]);
          this.charts.orderTrends.updateOptions({
            xaxis: {
              categories: this.trendsData.map(t => t.date)
            }
          });
        }
        return;
      }

      this.initOrderTrendsChart();
      this.initStatusChart();
      this.chartsInitialized = true;
    },

    initOrderTrendsChart() {
      const chartElement = document.getElementById('orderTrendsChart');
      if (!chartElement) return;

      chartElement.innerHTML = '';

      try {
        const trendsData = {
          series: [{
            name: 'Orders',
            data: this.trendsData && this.trendsData.length ? this.trendsData.map(t => t.orders) : [0, 0, 0, 0, 0, 0, 0]
          }, {
            name: 'Revenue',
            data: this.trendsData && this.trendsData.length ? this.trendsData.map(t => t.revenue) : [0, 0, 0, 0, 0, 0, 0]
          }],
          chart: {
            type: 'area',
            height: 300,
            toolbar: { show: false }
          },
          colors: ['#6366f1', '#10b981'],
          fill: {
            type: 'gradient',
            gradient: {
              shadeIntensity: 1,
              opacityFrom: 0.7,
              opacityTo: 0.3,
            }
          },
          stroke: {
            curve: 'smooth',
            width: 2
          },
          xaxis: {
            categories: this.trendsData && this.trendsData.length ? this.trendsData.map(t => t.date) : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
          },
          yaxis: [{
            title: { text: 'Orders' }
          }, {
            opposite: true,
            title: { text: 'Revenue (₹)' }
          }],
          tooltip: {
            y: [{
              formatter: (val) => val + " orders"
            }, {
              formatter: (val) => "₹ " + val
            }]
          }
        };

        this.charts.orderTrends = new ApexCharts(chartElement, trendsData);
        this.charts.orderTrends.render();
      } catch (error) {
        console.error('Error rendering order trends chart:', error);
      }
    },

    initStatusChart() {
      const chartElement = document.getElementById('statusChart');
      if (!chartElement) return;

      chartElement.innerHTML = '';

      try {
        const chartData = {
          series: this.statusStats.map(stat => stat.count),
          chart: {
            type: 'donut',
            height: 200
          },
          labels: this.statusStats.map(stat => stat.name),
          colors: this.statusStats.map(stat => stat.color),
          plotOptions: {
            pie: {
              donut: { size: '70%' }
            }
          },
          legend: { show: false },
          tooltip: {
            y: {
              formatter: (val) => val + " orders"
            }
          }
        };

        this.charts.status = new ApexCharts(chartElement, chartData);
        this.charts.status.render();
      } catch (error) {
        console.error('Error rendering status chart:', error);
      }
    },

    get visiblePages() {
      if (this.totalPages <= 1) return [1];

      const pages = [];
      pages.push(1);
      
      if (this.totalPages <= 7) {
        for (let i = 2; i <= this.totalPages; i++) {
          pages.push(i);
        }
      } else {
        if (this.currentPage <= 4) {
          for (let i = 2; i <= 5; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(this.totalPages);
        } else if (this.currentPage >= this.totalPages - 3) {
          pages.push('...');
          for (let i = this.totalPages - 4; i <= this.totalPages; i++) {
            pages.push(i);
          }
        } else {
          pages.push('...');
          for (let i = this.currentPage - 1; i <= this.currentPage + 1; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(this.totalPages);
        }
      }
      return pages;
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadOrders();
      }
    }
  }));

  // Shared navbar search
  Alpine.data('searchComponent', createSearchComponent({ getResults: () => [] }));
});
