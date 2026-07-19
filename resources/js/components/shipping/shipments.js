import Alpine from 'alpinejs';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';
import ApexCharts from 'apexcharts';

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
            per_page: 10
        },
        stats: {
            total: 0,
            pending: 0,
            in_transit: 0,
            delivered: 0,
            failed: 0,
            returned: 0
        },

        searchQuery: '',
        statusFilter: '',
        carrierFilter: '',
        fromDate: '',
        toDate: '',
        sortField: 'id',
        sortDirection: 'desc',
        currentPage: 1,
        itemsPerPage: 10,
        selectedItems: [],

        charts: {},
        chartsInitialized: false,
        statusStats: [],

        isLoading: false,
        saving: false,

        selectedShipment: null,
        trackingEvents: [],

        statusModal: null,
        trackingModal: null,
        addEventModal: null,

        statusForm: {
            status: 'pending',
            location: '',
            description: '',
            delivery_attempts: 0,
            next_followup_date: '',
            delivered_by: ''
        },

        eventForm: {
            event_name: '',
            location: '',
            description: ''
        },

        returnModal: null,
        returnForm: {
            reason: '',
            notes: ''
        },
        returnItems: [],

        apiBase: '/api/shipping/shipments',

        normalizeStatus(status) {
            return status === 'shipped' ? 'in_transit' : status;
        },

        init() {
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

            setTimeout(() => {
                this.initCharts();
            }, 300);
        },

        initCharts() {
            if (this.chartsInitialized) {
                this.updateCharts();
                return;
            }
            this.renderTrendChart();
            this.renderStatusChart();
            this.chartsInitialized = true;
        },

        renderTrendChart() {
            const chartElement = document.getElementById('shipmentTrendsChart');
            if (!chartElement) return;

            // Generate some mock trend data based on the stats
            const trendsData = {
                series: [{
                    name: 'Shipments',
                    data: [12, 19, 15, 25, 22, 30, this.stats.total]
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: { show: false },
                    fontFamily: 'inherit'
                },
                colors: ['#3b82f6'],
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.3 }
                },
                stroke: { curve: 'smooth', width: 2 },
                xaxis: {
                    categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                }
            };

            this.charts.trends = new ApexCharts(chartElement, trendsData);
            this.charts.trends.render();
        },

        renderStatusChart() {
            const chartElement = document.getElementById('statusChart');
            if (!chartElement) return;

            const chartData = {
                series: this.statusStats.map(stat => stat.count),
                chart: { type: 'donut', height: 200 },
                labels: this.statusStats.map(stat => stat.name),
                colors: this.statusStats.map(stat => stat.color),
                plotOptions: { pie: { donut: { size: '70%' } } },
                legend: { show: false }
            };

            this.charts.status = new ApexCharts(chartElement, chartData);
            this.charts.status.render();
        },

        updateCharts() {
            if (this.charts.status) {
                this.charts.status.updateSeries(this.statusStats.map(stat => stat.count));
            }
        },

        async apiRequest(url, options = {}) {
            const { headers, ...otherOptions } = options;
            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(headers || {})
                },
                ...otherOptions,
            });

            const text = await response.text();
            const payload = text ? JSON.parse(text) : {};

            if (!response.ok) {
                const validation = payload?.errors ? Object.values(payload.errors).flat().join(' ') : '';
                const message = validation || payload?.message || payload?.error || 'Request failed.';
                throw new Error(message);
            }
            return payload;
        },

        async loadData() {
            this.isLoading = true;
            try {
                // Fetch stats and all items to calculate metrics
                const allPayload = await this.apiRequest(`${this.apiBase}?per_page=100`);
                const allItems = (allPayload.data || []).map(item => ({
                    ...item,
                    status: this.normalizeStatus(item.status)
                }));

                this.stats.total = allItems.length;
                this.stats.pending = allItems.filter(i => i.status === 'pending').length;
                this.stats.in_transit = allItems.filter(i => i.status === 'in_transit').length;
                this.stats.delivered = allItems.filter(i => i.status === 'delivered').length;
                this.stats.failed = allItems.filter(i => i.status === 'failed').length;
                this.stats.returned = allItems.filter(i => i.status === 'returned').length;

                this.statusStats = [
                    { name: 'Pending', count: this.stats.pending, percentage: this.stats.total ? Math.round((this.stats.pending / this.stats.total) * 100) : 0, color: '#f97316' },
                    { name: 'In Transit', count: this.stats.in_transit, percentage: this.stats.total ? Math.round((this.stats.in_transit / this.stats.total) * 100) : 0, color: '#3b82f6' },
                    { name: 'Delivered', count: this.stats.delivered, percentage: this.stats.total ? Math.round((this.stats.delivered / this.stats.total) * 100) : 0, color: '#10b981' },
                    { name: 'Returned', count: this.stats.returned, percentage: this.stats.total ? Math.round((this.stats.returned / this.stats.total) * 100) : 0, color: '#64748b' },
                    { name: 'Failed', count: this.stats.failed, percentage: this.stats.total ? Math.round((this.stats.failed / this.stats.total) * 100) : 0, color: '#ef4444' }
                ].filter(stat => stat.count > 0);

                if (this.chartsInitialized) {
                    this.updateCharts();
                }

                // Fetch paginated active list with all filters
                let query = `page=${this.currentPage}&per_page=${this.itemsPerPage}&search=${encodeURIComponent(this.searchQuery)}&sort_by=${this.sortField}&sort_dir=${this.sortDirection}`;
                if (this.statusFilter) query += `&status=${this.statusFilter}`;
                if (this.carrierFilter) query += `&carrier=${this.carrierFilter}`;
                if (this.fromDate) query += `&from_date=${this.fromDate}`;
                if (this.toDate) query += `&to_date=${this.toDate}`;

                const payload = await this.apiRequest(`${this.apiBase}?${query}`);

                this.items = (payload.data || []).map(item => ({
                    ...item,
                    status: this.normalizeStatus(item.status)
                }));
                this.paginator = {
                    current_page: payload.current_page,
                    last_page: payload.last_page,
                    total: payload.total,
                    from: payload.from || 0,
                    to: payload.to || 0,
                    per_page: payload.per_page
                };
            } catch (error) {
                console.error('Failed to load shipments:', error);
                showToast(error.message, 'error');
            } finally {
                this.isLoading = false;
            }
        },

        filterData() {
            this.currentPage = 1;
            this.selectedItems = [];
            this.loadData();
        },

        clearFilters() {
            this.searchQuery = '';
            this.statusFilter = '';
            this.carrierFilter = '';
            this.fromDate = '';
            this.toDate = '';
            this.currentPage = 1;
            this.loadData();
        },

        hasActiveAdvancedFilters() {
            return Boolean(this.carrierFilter || this.fromDate || this.toDate);
        },

        toggleAll(checked) {
            if (checked) {
                this.selectedItems = this.items.map(i => String(i.id));
            } else {
                this.selectedItems = [];
            }
        },

        async bulkAction(action) {
            if (this.selectedItems.length === 0) return;

            const actionLabels = {
                mark_in_transit: 'mark in transit',
                mark_delivered: 'mark delivered',
                mark_returned: 'mark returned'
            };

            const result = await Swal.fire({
                title: 'Confirm Bulk Action',
                text: `Are you sure you want to ${actionLabels[action]} for ${this.selectedItems.length} shipment(s)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed'
            });

            if (!result.isConfirmed) return;

            this.isLoading = true;
            try {
                await this.apiRequest(`${this.apiBase}/bulk-action`, {
                    method: 'POST',
                    body: JSON.stringify({
                        action: action,
                        ids: this.selectedItems
                    })
                });

                showToast(`Bulk action completed successfully.`, 'success');
                this.selectedItems = [];
                this.loadData();
            } catch (error) {
                showToast(error.message || 'Bulk action failed.', 'error');
                this.isLoading = false;
            }
        },

        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'desc';
            }
            this.loadData();
        },

        goToPage(page) {
            if (page >= 1 && page <= this.paginator.last_page) {
                this.currentPage = page;
                this.loadData();
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
            this.statusForm = {
                status: shipment.status,
                location: '',
                description: '',
                delivery_attempts: shipment.delivery_attempts || 0,
                next_followup_date: shipment.next_followup_date ? shipment.next_followup_date.split('T')[0] : '',
                delivered_by: shipment.delivered_by || ''
            };
            this.statusModal?.show();
        },

        async saveStatus() {
            if (!this.selectedShipment) return;
            this.saving = true;
            try {
                await this.apiRequest(`${this.apiBase}/${this.selectedShipment.id}/status`, {
                    method: 'POST',
                    body: JSON.stringify(this.statusForm)
                });
                showToast('Shipment status updated successfully.', 'success');
                this.statusModal?.hide();
                this.loadData();
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async openTrackingModal(shipment) {
            this.selectedShipment = shipment;
            this.trackingEvents = [];
            this.trackingModal?.show();

            try {
                const response = await this.apiRequest(`${this.apiBase}/${shipment.id}/tracking`);
                this.trackingEvents = response.events || [];
            } catch (error) {
                showToast(error.message, 'error');
            }
        },

        openAddEventModal(shipment) {
            this.selectedShipment = shipment;
            this.eventForm = {
                event_name: '',
                location: '',
                description: ''
            };
            this.addEventModal?.show();
        },

        async saveTrackingEvent() {
            if (!this.selectedShipment) return;
            this.saving = true;
            try {
                await this.apiRequest(`${this.apiBase}/${this.selectedShipment.id}/tracking-event`, {
                    method: 'POST',
                    body: JSON.stringify(this.eventForm)
                });
                showToast('Tracking event added successfully.', 'success');
                this.addEventModal?.hide();

                // If tracking modal is open or needs to refresh, load again
                if (this.trackingModal && this.selectedShipment) {
                    this.openTrackingModal(this.selectedShipment);
                }
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async openReturnModal(shipment) {
            this.selectedShipment = shipment;
            this.returnForm = { reason: '', notes: '' };
            this.returnItems = [];

            try {
                const orderId = shipment.order?.id || shipment.order_id;
                const orderPayload = await this.apiRequest(`/orders/${orderId}`);
                if (!orderPayload || !orderPayload.order) {
                    throw new Error("Order details not found.");
                }
                const order = orderPayload.order;
                this.returnItems = (order.items || []).map(item => ({
                    product_id: item.product_id,
                    name: item.product?.name || item.name || 'Unknown Product',
                    requested_qty: item.quantity,
                    max_qty: item.quantity
                }));
                this.returnModal?.show();
            } catch (error) {
                showToast(error.message, 'error');
            }
        },

        async submitReturn() {
            if (!this.selectedShipment) return;

            if (!this.returnForm.reason) {
                showToast('Please select a return reason.', 'warning');
                return;
            }

            const itemsToReturn = this.returnItems.filter(i => i.requested_qty > 0);
            if (itemsToReturn.length === 0) {
                showToast('Please select at least one item to return with a quantity greater than 0.', 'warning');
                return;
            }

            this.saving = true;
            try {
                const orderId = this.selectedShipment.order?.id || this.selectedShipment.order_id;
                const payload = {
                    reason: this.returnForm.reason,
                    notes: this.returnForm.notes,
                    items: itemsToReturn
                };

                await this.apiRequest(`/orders/${orderId}/returns`, {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });

                showToast('Order return initiated successfully.', 'success');
                this.returnModal?.hide();

                this.statusForm = {
                    status: 'returned',
                    location: '',
                    description: 'Order Returned: ' + this.returnForm.reason,
                    delivery_attempts: this.selectedShipment.delivery_attempts || 0,
                    next_followup_date: this.selectedShipment.next_followup_date || '',
                    delivered_by: this.selectedShipment.delivered_by || ''
                };
                await this.saveStatus();

            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        exportData() {
            // Simple export logic
            if (this.items.length === 0) {
                showToast('No data to export.', 'warning');
                return;
            }

            const headers = ['ID', 'Shipment No', 'Order ID', 'Carrier', 'Tracking No', 'Status', 'Shipped At', 'Delivered At'];
            const csvRows = [headers.join(',')];

            this.items.forEach(item => {
                const values = [
                    item.id,
                    item.shipment_no,
                    item.order?.order_number || item.order_id,
                    `"${(item.carrier_name || '').replace(/"/g, '""')}"`,
                    item.tracking_no || '',
                    item.status,
                    item.shipped_at || '',
                    item.delivered_at || ''
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', 'shipments_export.csv');
            a.click();
            URL.revokeObjectURL(url);
        }
    };

    window.Alpine.store('shipmentsTable', instance);
    return instance;
};
