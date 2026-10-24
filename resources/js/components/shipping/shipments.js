import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const id = 'toast-' + Date.now();
  const iconMap = {
    success: 'bi-check-circle-fill',
    danger: 'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info: 'bi-info-circle-fill',
    error: 'bi-x-circle-fill',
  };

  const el = document.createElement('div');
  el.id = id;
  el.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0 show mb-2`;
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

export default () => {
  const instance = {
    items: [],
    paginator: {
      current_page: 1,
      last_page: 1,
      total: 0,
      from: 0,
      to: 0,
      per_page: 10,
    },
    stats: {
      total: 0,
      pending: 0,
      in_transit: 0,
      delivered: 0,
      failed: 0,
      returned: 0,
    },

    searchQuery: '',
    statusFilter: 'pending,in_transit,failed',
    carrierFilter: '',
    dateType: 'created_at',
    fromDate: '',
    toDate: '',
    sortField: 'id',
    sortDirection: 'desc',
    currentPage: 1,
    itemsPerPage: 25,
    selectedItems: [],

    // Provider Tracker State
    allFetchedItems: [],
    providerSearchQuery: '',
    providerCurrentPage: 1,
    providerPerPage: 5,
    get filteredProviders() {
      if (!this.topProviders) return [];
      let p = this.topProviders;
      if (this.providerSearchQuery) {
        p = p.filter((x) => x.name.toLowerCase().includes(this.providerSearchQuery.toLowerCase()));
      }
      return p;
    },

    get paginatedProviders() {
      const start = (this.providerCurrentPage - 1) * this.providerPerPage;
      return this.filteredProviders.slice(start, start + Number(this.providerPerPage));
    },

    goToProviderPage(page) {
      if (page >= 1 && page <= this.providerTotalPages) this.providerCurrentPage = page;
    },

    get providerTotalPages() {
      return Math.ceil(this.filteredProviders.length / this.providerPerPage) || 1;
    },

    calculateProviders() {
      if (!this.allFetchedItems) return;

      const providerMap = Object.create(null);
      this.allFetchedItems.forEach((item) => {
        const count = Number(item.total);
        const c = item.carrier_name || 'Unassigned';
        if (!providerMap[c]) {
          providerMap[c] = {
            name: c,
            total: 0,
            pending: 0,
            in_transit: 0,
            delivered: 0,
            returned: 0,
            failed: 0,
            contact_persons: [],
          };
        }

        if (item.service && item.service.providers) {
          item.service.providers.forEach((p) => {
            let contact = providerMap[c].contact_persons.find((existing) => existing.id === p.id);
            if (!contact) {
              contact = { ...p, assigned_orders: 0 };
              providerMap[c].contact_persons.push(contact);
            }
            if (String(item.service_provider_id) === String(p.id)) {
              contact.assigned_orders += count;
            }
          });
        }

        providerMap[c].total += count;
        if (item.status === 'pending') providerMap[c].pending += count;
        if (item.status === 'in_transit') providerMap[c].in_transit += count;
        if (item.status === 'delivered') providerMap[c].delivered += count;
        if (item.status === 'returned') providerMap[c].returned += count;
        if (item.status === 'failed') providerMap[c].failed += count;
      });

      const colorClasses = ['primary', 'info', 'warning', 'danger', 'success'];
      this.topProviders = Object.values(providerMap)
        .map((p, idx) => {
          p.contact_persons.sort((a, b) => {
            const pA = a.pivot && a.pivot.priority ? parseInt(a.pivot.priority) : 999;
            const pB = b.pivot && b.pivot.priority ? parseInt(b.pivot.priority) : 999;
            return pA - pB;
          });
          const activeTotal = p.total - p.pending;
          const onTimeRate = activeTotal > 0 ? Math.round((p.delivered / activeTotal) * 100) : 0;
          const exceptionRate =
            activeTotal > 0 ? (((p.failed + p.returned) / activeTotal) * 100).toFixed(1) : '0.0';
          const successScore = Math.max(0, 100 - parseFloat(exceptionRate) - (100 - onTimeRate));
          const deliveryPercentage = p.total > 0 ? Math.round((p.delivered / p.total) * 100) : 0;
          return {
            name: p.name,
            total: p.total,
            pending: p.pending,
            in_transit: p.in_transit,
            delivered: p.delivered,
            returned: p.returned,
            failed: p.failed,
            contact_persons: p.contact_persons,
            onTimeRate: onTimeRate,

            exceptionRate: parseFloat(exceptionRate),
            successScore: Math.round(successScore),
            deliveryPercentage: deliveryPercentage,
            theme: colorClasses[idx % colorClasses.length],
          };
        })
        .sort((a, b) => b.total - a.total);

      this.providerCurrentPage = 1;
    },

    topProviders: [],

    summaryKey: null,
    loadController: null,
    popstateHandler: null,
    loadSequence: 0,
    trackingSequence: 0,
    liveTrackingSequence: 0,
    isLoading: false,
    saving: false,
    loadError: '',
    trackingLoading: false,
    trackingError: '',

    selectedShipment: null,
    trackingEvents: [],

    // Live India Post tracking state (read-only, no DB writes)
    liveTrackingData: null,
    liveTrackingLoading: false,
    liveTrackingError: null,

    statusModal: null,
    trackingModal: null,
    addEventModal: null,

    statusForm: {
      status: 'pending',
      location: '',
      description: '',
      delivery_attempts: 0,
      next_followup_date: '',
      delivered_by: '',
    },

    eventForm: {
      event_name: '',
      location: '',
      description: '',
    },

    returnModal: null,
    returnForm: {
      reason: '',
      notes: '',
    },
    returnItems: [],

    apiBase: '/api/shipping/shipments',

    normalizeStatus(status) {
      return status;
    },

    init() {
      this.restoreFilters();
      this.popstateHandler = () => { this.restoreFilters(); this.loadData(); };
      window.addEventListener('popstate', this.popstateHandler);
      this.loadData();

      const statusEl = document.getElementById('statusModal');
      if (statusEl) {
        this.statusModal = Modal.getOrCreateInstance(statusEl);
      }

      const trackingEl = document.getElementById('trackingModal');
      if (trackingEl) {
        this.trackingModal = Modal.getOrCreateInstance(trackingEl);
      }

      const addEventEl = document.getElementById('addEventModal');
      if (addEventEl) {
        this.addEventModal = Modal.getOrCreateInstance(addEventEl);
      }

      const returnOrderEl = document.getElementById('returnOrderModal');
      if (returnOrderEl) {
        this.returnModal = Modal.getOrCreateInstance(returnOrderEl);
      }
    },

    destroy() {
      this.loadController?.abort();
      window.removeEventListener('popstate', this.popstateHandler);
    },

    get statusOptions() {
      return [
        { value: 'pending', label: 'Pending' },
        { value: 'in_transit', label: 'Dispatched' },
        { value: 'failed', label: 'Failed' },
        { value: 'delivered', label: 'Delivered' },
        { value: 'returned', label: 'Returned' },
      ];
    },

    hasStatus(status) {
      return this.statusFilter.split(',').includes(status);
    },

    toggleStatus(status) {
      const selected = this.statusOptions.filter((option) => this.hasStatus(option.value)).map((option) => option.value);
      this.statusFilter = (selected.includes(status)
        ? selected.filter((value) => value !== status) : [...selected, status]).join(',') || '__no_status_selected__';
      this.filterData();
    },

    restoreFilters() {
      const params = new URLSearchParams(window.location.search);
      this.searchQuery = params.get('search') || '';
      const statuses = params.has('status') ? params.get('status').split(',') : ['pending', 'in_transit', 'failed'];
      this.statusFilter = statuses.length === 1 && statuses[0] === '__no_status_selected__'
        ? '__no_status_selected__'
        : [...new Set(statuses.filter((status) => this.statusOptions.some((option) => option.value === status)))].join(',');
      this.carrierFilter = params.get('carrier') || '';
      this.dateType = ['created_at', 'shipped_at', 'delivered_at'].includes(params.get('date_type')) ? params.get('date_type') : 'created_at';
      this.fromDate = params.get('from_date') || '';
      this.toDate = params.get('to_date') || '';
      this.itemsPerPage = [10, 15, 20, 25, 50, 100].includes(Number(params.get('limit'))) ? Number(params.get('limit')) : 25;
      this.currentPage = Math.max(1, Number.parseInt(params.get('page'), 10) || 1);
      this.sortField = ['id', 'shipment_no', 'status', 'shipped_at', 'delivered_at'].includes(params.get('sort_field')) ? params.get('sort_field') : 'id';
      this.sortDirection = params.get('sort_direction') === 'asc' ? 'asc' : 'desc';
      this.selectedItems = [];
    },

    filterParams() {
      const params = new URLSearchParams({ status: this.statusFilter || 'all', date_type: this.dateType });
      if (this.searchQuery) params.set('search', this.searchQuery);
      if (this.carrierFilter) params.set('carrier', this.carrierFilter);
      if (this.fromDate) params.set('from_date', this.fromDate);
      if (this.toDate) params.set('to_date', this.toDate);
      return params;
    },

    async apiRequest(url, options = {}) {
      const { headers, ...otherOptions } = options;
      const response = await fetch(url, {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
          'Content-Type': 'application/json',
          ...(headers || {}),
        },
        ...otherOptions,
      });

      const text = await response.text();
      let payload;
      try {
        payload = text ? JSON.parse(text) : {};
      } catch (error) {
        if (!response.ok) {
          throw new Error(`Server error (${response.status}): The server returned an invalid HTML response instead of JSON.`, { cause: error });
        }
        throw new Error('Received invalid JSON from server.', { cause: error });
      }

      if (!response.ok) {
        const validation = payload?.errors ? Object.values(payload.errors).flat().join(' ') : '';
        const message = validation || payload?.message || payload?.error || 'Request failed.';
        throw new Error(message);
      }
      return payload;
    },

    async loadData(refreshSummary = true) {
      const sequence = ++this.loadSequence;
      this.loadController?.abort();
      this.loadController = typeof AbortController === 'undefined' ? null : new AbortController();
      this.isLoading = true;
      this.loadError = '';
      try {
        const params = this.filterParams();
        const summaryKey = params.toString();
        const includeSummary = refreshSummary || this.summaryKey !== summaryKey;
        params.set('page', this.currentPage);
        params.set('limit', this.itemsPerPage);
        params.set('sort_field', this.sortField);
        params.set('sort_direction', this.sortDirection);
        if (window.history && window.location) {
          window.history.replaceState({}, '', `${window.location.pathname}?${params}`);
        }
        params.set('include_summary', includeSummary ? '1' : '0');
        const payload = await this.apiRequest(`${this.apiBase}?${params}`, { signal: this.loadController?.signal });
        if (sequence !== this.loadSequence) return;
        if (payload.stats && payload.providers) {
          this.stats = payload.stats;
          this.allFetchedItems = payload.providers;
          this.calculateProviders();
          this.summaryKey = summaryKey;
        }

        if (sequence !== this.loadSequence) return;
        this.items = (payload.data || []).map((item) => ({
          ...item,
          status: this.normalizeStatus(item.status),
        }));
        this.selectedItems = this.selectedItems.filter((id) => this.items.some((item) => String(item.id) === id));
        if (this.currentPage > payload.last_page) {
          this.currentPage = payload.last_page;
          return await this.loadData(false);
        }
        this.paginator = {
          current_page: payload.current_page,
          last_page: payload.last_page,
          total: payload.total,
          from: payload.from || 0,
          to: payload.to || 0,
          per_page: payload.per_page,
        };
      } catch (error) {
        if (sequence !== this.loadSequence) return;
        this.loadError = error.message || 'Unable to load shipments.';
        console.error('Failed to load shipments:', error);
        showToast(error.message, 'error');
      } finally {
        if (sequence === this.loadSequence) this.isLoading = false;
      }
    },

    filterData() {
      this.currentPage = 1;
      this.loadData();
    },

    clearFilters() {
      this.searchQuery = '';
      this.statusFilter = 'pending,in_transit,failed';
      this.carrierFilter = '';
      this.fromDate = '';
      this.toDate = '';
      this.dateType = 'created_at';
      this.currentPage = 1;
      this.loadData();
    },

    hasActiveAdvancedFilters() {
      return Boolean(this.carrierFilter || this.fromDate || this.toDate);
    },

    toggleAll(checked) {
      if (checked) {
        this.selectedItems = this.items.map((i) => String(i.id));
      } else {
        this.selectedItems = [];
      }
    },

    get bulkAvailableActions() {
      if (this.selectedItems.length === 0) return {};

      const selectedObjs = this.items.filter((i) => this.selectedItems.includes(String(i.id)));
      const statuses = new Set(selectedObjs.map((i) => i.status));

      return {
        canInTransit: [...statuses].some((s) => ['pending', 'failed'].includes(s)),
        canDelivered: [...statuses].some((s) => ['in_transit'].includes(s)),
        canReturned: [...statuses].some((s) => !['delivered', 'returned'].includes(s)),
      };
    },

    async bulkAction(action) {
      if (this.selectedItems.length === 0 || this.isLoading) return;

      const selectedObjs = this.items.filter((i) => this.selectedItems.includes(String(i.id)));
      let applicableObjs = [];

      if (action === 'mark_in_transit') {
        applicableObjs = selectedObjs.filter((i) => ['pending', 'failed'].includes(i.status));
      } else if (action === 'mark_delivered') {
        applicableObjs = selectedObjs.filter((i) => ['in_transit'].includes(i.status));
      } else if (action === 'mark_returned') {
        applicableObjs = selectedObjs.filter((i) => !['delivered', 'returned'].includes(i.status));
      }

      if (applicableObjs.length === 0) {
        showToast('No applicable shipments selected for this action.', 'warning');
        return;
      }

      const applicableIds = applicableObjs.map((i) => String(i.id));

      let returnReason = '';

      if (action === 'mark_returned') {
        const { value: reason, isConfirmed } = await Swal.fire({
          title: 'Return Reason',
          text: `Please select a reason for returning these ${applicableIds.length} applicable shipment(s).`,
          input: 'select',
          inputOptions: window.AppConfig?.returnReasons || {
            defective: 'Defective / Damaged in Transit',
            wrong_item: 'Wrong Item Sent',
            not_needed: 'No Longer Needed / Refused',
            undeliverable: 'Undeliverable / Failed Delivery',
            other: 'Other Reason',
          },
          inputPlaceholder: 'Select a reason...',
          showCancelButton: true,
          confirmButtonText: 'Proceed with Return',
          confirmButtonColor: 'var(--bs-primary)',
          cancelButtonColor: 'var(--bs-danger)',
          inputValidator: (value) => {
            return new Promise((resolve) => {
              if (value) resolve();
              else resolve('You need to select a reason.');
            });
          },
        });
        if (!isConfirmed) return;
        returnReason = reason;
      } else {
        const actionLabels = {
          mark_in_transit: 'mark in transit',
          mark_delivered: 'mark delivered',
        };

        const result = await Swal.fire({
          title: 'Confirm Bulk Action',
          text: `Are you sure you want to ${actionLabels[action]} for ${applicableIds.length} applicable shipment(s)?`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: 'var(--bs-primary)',
          cancelButtonColor: 'var(--bs-danger)',
          confirmButtonText: 'Yes, proceed',
        });

        if (!result.isConfirmed) return;
      }

      this.isLoading = true;
      try {
        if (action === 'mark_returned') {
          // One return request per order even when several selected parcels belong to it.
          const returnedOrders = new Set();
          for (const shipment of applicableObjs) {
            const orderId = shipment.order?.id || shipment.order_id;
            if (!shipment.order?.items?.length) {
              throw new Error('Order items unavailable. Reload shipments before returning.');
            }
            if (!returnedOrders.has(orderId)) {
              await this.apiRequest(`${this.apiBase}/${shipment.id}/return`, {
                method: 'POST',
                body: JSON.stringify({
                  reason: returnReason,
                  notes: 'Bulk return processed via Shipments dashboard.',
                  items: shipment.order.items.map((item) => ({
                    product_id: item.product_id,
                    requested_qty: item.quantity,
                  })),
                }),
              });
              returnedOrders.add(orderId);
            } else {
              await this.apiRequest(`${this.apiBase}/bulk-action`, {
                method: 'POST',
                body: JSON.stringify({ action, ids: [String(shipment.id)], skip_order_sync: true }),
              });
            }
          }

        } else {
          await this.apiRequest(`${this.apiBase}/bulk-action`, {
            method: 'POST',
            body: JSON.stringify({
              action: action,
              ids: applicableIds,
            }),
          });
        }

        showToast(`Bulk action completed successfully.`, 'success');
        this.selectedItems = [];
        this.loadData();
      } catch (error) {
        showToast(error.message || 'Bulk action failed.', 'error');
        await this.loadData();
      }
    },

    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'desc';
      }
      this.currentPage = 1;
      this.loadData(false);
    },

    goToPage(page) {
      if (page >= 1 && page <= this.paginator.last_page) {
        this.currentPage = page;
        this.loadData(false);
      }
    },

    get visiblePages() {
      const total = this.paginator.last_page;
      if (total <= 1) return [1];
      const pages = [];
      pages.push(1);

      if (total <= 7) {
        for (let i = 2; i <= total; i++) pages.push(i);
      } else {
        if (this.currentPage <= 4) {
          for (let i = 2; i <= 5; i++) pages.push(i);
          pages.push('...');
          pages.push(total);
        } else if (this.currentPage >= total - 3) {
          pages.push('...');
          for (let i = total - 4; i <= total; i++) pages.push(i);
        } else {
          pages.push('...');
          for (let i = this.currentPage - 1; i <= this.currentPage + 1; i++) pages.push(i);
          pages.push('...');
          pages.push(total);
        }
      }
      return pages;
    },

    openStatusModal(shipment) {
      this.selectedShipment = shipment;

      let defaultFollowUp;
      if (shipment.next_followup_date) {
        defaultFollowUp = shipment.next_followup_date.split('T')[0];
      } else {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        defaultFollowUp = `${tomorrow.getFullYear()}-${String(tomorrow.getMonth() + 1).padStart(2, '0')}-${String(tomorrow.getDate()).padStart(2, '0')}`;
      }

      const userNameMeta = document.querySelector('meta[name="user-name"]');
      const userName = userNameMeta ? userNameMeta.content : '';

      this.statusForm = {
        status: shipment.status,
        location: '',
        description: '',
        delivery_attempts: shipment.delivery_attempts || 0,
        next_followup_date: defaultFollowUp,
        reschedule_reason: shipment.reschedule_reason || '',
        delivered_by: shipment.delivered_by || userName,
      };

      this.onStatusChange();

      this.statusModal?.show();
    },

    onStatusChange() {
      const newStatus = this.statusForm.status;
      if (newStatus === 'delivered' || newStatus === 'returned') {
        this.statusForm.next_followup_date = '';
        this.statusForm.reschedule_reason = '';
        if (this.selectedShipment) {
          this.statusForm.delivery_attempts = this.selectedShipment.delivery_attempts || 0;
        }

        if (
          newStatus === 'returned' &&
          this.selectedShipment &&
          this.selectedShipment.order &&
          this.selectedShipment.order.items
        ) {
          this.returnForm.reason = '';
          this.returnItems = (this.selectedShipment.order.items || []).map((item) => ({
            product_id: item.product_id,
            name: item.product?.name || item.name || 'Unknown Product',
            requested_qty: item.quantity,
            max_qty: item.quantity,
          }));
        }
      } else if (newStatus === 'failed' || newStatus === 'in_transit' || newStatus === 'pending') {
        if (this.selectedShipment) {
          this.statusForm.delivery_attempts = (this.selectedShipment.delivery_attempts || 0) + 1;
          if (!this.statusForm.next_followup_date) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.statusForm.next_followup_date = `${tomorrow.getFullYear()}-${String(tomorrow.getMonth() + 1).padStart(2, '0')}-${String(tomorrow.getDate()).padStart(2, '0')}`;
          }
        }
      }
    },

    async saveStatus() {
      if (!this.selectedShipment || this.saving) return false;
      this.saving = true;
      try {
        if (this.statusForm.status === 'returned') {
          if (!this.returnForm.reason) {
            throw new Error('Please select a return reason.');
          }
          const itemsToReturn = this.returnItems.filter((i) => i.requested_qty > 0);
          if (itemsToReturn.length === 0) {
            throw new Error(
              'Please select at least one item to return with a quantity greater than 0.'
            );
          }

          await this.apiRequest(`${this.apiBase}/${this.selectedShipment.id}/return`, {
            method: 'POST',
            body: JSON.stringify({
              ...this.statusForm,
              reason: this.returnForm.reason,
              notes: this.statusForm.description,
              items: itemsToReturn,
            }),
          });
        } else {
          await this.apiRequest(`${this.apiBase}/${this.selectedShipment.id}/status`, {
            method: 'POST',
            body: JSON.stringify(this.statusForm),
          });
        }
        showToast('Shipment status updated successfully.', 'success');
        this.statusModal?.hide();
        await this.loadData();
        return true;
      } catch (error) {
        showToast(error.message, 'error');
        return false;
      } finally {
        this.saving = false;
      }
    },

    async openTrackingModal(shipment) {
      const sequence = ++this.trackingSequence;
      ++this.liveTrackingSequence;
      this.selectedShipment = shipment;
      this.trackingEvents = [];
      this.trackingLoading = true;
      this.trackingError = '';
      // Reset live tracking state so previous shipment's data doesn't bleed through
      this.liveTrackingData = null;
      this.liveTrackingError = null;
      this.liveTrackingLoading = false;
      this.trackingModal?.show();

      try {
        const response = await this.apiRequest(`${this.apiBase}/${shipment.id}/tracking`);
        if (sequence !== this.trackingSequence || this.selectedShipment?.id !== shipment.id) return;
        this.selectedShipment = { ...shipment, ...response.shipment };
        this.trackingEvents = response.events || [];
      } catch (error) {
        if (sequence !== this.trackingSequence || this.selectedShipment?.id !== shipment.id) return;
        this.trackingError = error.message || 'Unable to load tracking history.';
      } finally {
        if (sequence === this.trackingSequence) this.trackingLoading = false;
      }
    },


    /**
     * Fetch live tracking data from India Post API on demand.
     * Pure read — does not update DB or change any shipment state.
     */
    async fetchLiveTracking() {
      if (!this.selectedShipment) return;
      const shipmentId = this.selectedShipment.id;
      const sequence = ++this.liveTrackingSequence;
      this.liveTrackingLoading = true;
      this.liveTrackingError = null;
      this.liveTrackingData = null;
      try {
        const response = await this.apiRequest(
          `${this.apiBase}/${this.selectedShipment.id}/live-tracking`
        );
        if (sequence !== this.liveTrackingSequence || this.selectedShipment?.id !== shipmentId) return;
        this.liveTrackingData = response;
      } catch (error) {
        if (sequence !== this.liveTrackingSequence || this.selectedShipment?.id !== shipmentId) return;
        this.liveTrackingError = error.message || 'Failed to fetch live tracking data.';
      } finally {
        if (sequence === this.liveTrackingSequence) this.liveTrackingLoading = false;
      }
    },

    openAddEventModal(shipment) {
      this.selectedShipment = shipment;
      this.eventForm = {
        event_name: '',
        location: '',
        description: '',
      };
      this.addEventModal?.show();
    },

    async saveTrackingEvent() {
      if (!this.selectedShipment || this.saving) return false;
      this.saving = true;
      try {
        await this.apiRequest(`${this.apiBase}/${this.selectedShipment.id}/tracking-event`, {
          method: 'POST',
          body: JSON.stringify(this.eventForm),
        });
        showToast('Tracking event added successfully.', 'success');
        this.addEventModal?.hide();

      } catch (error) {
        showToast(error.message, 'error');
        return false;
      } finally {
        this.saving = false;
      }
    },

    async openReturnModal(shipment) {
      this.selectedShipment = shipment;
      this.returnForm = { reason: '', notes: '' };
      this.returnItems = [];

      if (shipment.order && shipment.order.items) {
        this.returnItems = (shipment.order.items || []).map((item) => ({
          product_id: item.product_id,
          name: item.product?.name || item.name || 'Unknown Product',
          requested_qty: item.quantity,
          max_qty: item.quantity,
        }));
        this.returnModal?.show();
      } else {
        showToast('Order items not available for this shipment.', 'error');
      }
    },

    async submitReturn() {
      if (!this.selectedShipment) return;

      if (!this.returnForm.reason) {
        showToast('Please select a return reason.', 'warning');
        return;
      }

      const itemsToReturn = this.returnItems.filter((i) => i.requested_qty > 0);
      if (itemsToReturn.length === 0) {
        showToast(
          'Please select at least one item to return with a quantity greater than 0.',
          'warning'
        );
        return;
      }

      if (this.saving) return;
      try {

        this.statusForm = {
          status: 'returned',
          location: '',
          description: `Order Returned: ${this.returnForm.reason}. ${this.returnForm.notes}`,
          delivery_attempts: this.selectedShipment.delivery_attempts || 0,
          next_followup_date: this.selectedShipment.next_followup_date || '',
          delivered_by: this.selectedShipment.delivered_by || '',
        };
        if (await this.saveStatus()) this.returnModal?.hide();
      } catch (error) {
        showToast(error.message, 'error');
        return false;
      } finally {
        this.saving = false;
      }
    },

    exportData() {
      if (this.items.length === 0) {
        showToast('No data to export.', 'warning');
        return;
      }

      const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return isNaN(date.getTime()) ? dateString : date.toLocaleString();
      };
      const csvCell = (value) => {
        let text = String(value ?? '');
        // Preserve literal text when opened by a spreadsheet program.
        if (/^[=+@\-\t\r]/.test(text)) text = "'" + text;
        return '"' + text.replace(/"/g, '""') + '"';
      };

      const headers = [
        'ID',
        'Shipment No',
        'Order No',
        'Carrier',
        'Service Providers',
        'Tracking No',
        'Status',
        'Delivery Attempts',
        'Next Follow-up Date',
        'Reschedule Reason',
        'Delivered By',
        'Shipped At',
        'Delivered At',
        'Created At',
        'Weight (g)',
        'Dimensions (cm)',
        'Shipping Cost',
      ];
      const csvRows = [headers.map(csvCell).join(',')];

      this.items.forEach((item) => {
        const providers = item.service?.providers?.map((p) => p.name).join('; ') || '';
        const values = [
          item.id, item.shipment_no, item.order?.order_no || item.order_id,
          item.carrier_name, providers, item.tracking_no, item.status?.toUpperCase(),
          item.delivery_attempts || 0, formatDate(item.next_followup_date),
          item.reschedule_reason, item.delivered_by, formatDate(item.shipped_at),
          formatDate(item.delivered_at), formatDate(item.created_at),
          item.actual_weight_g ?? '',
          item.length_cm ? `${item.length_cm}x${item.width_cm}x${item.height_cm}` : '',
          item.shipping_cost ?? '',
        ];
        csvRows.push(values.map(csvCell).join(','));
      });

      const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.setAttribute('href', url);
      a.setAttribute('download', `shipments_export_${new Date().toISOString().split('T')[0]}.csv`);
      a.click();
      URL.revokeObjectURL(url);
    },
  };

  window.Alpine.store('shipmentsTable', instance);
  return instance;
};
