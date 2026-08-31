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
        topProviders: [],

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
                colors: ['var(--bs-primary)'],
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
                    { name: 'Pending', count: this.stats.pending, percentage: this.stats.total ? Math.round((this.stats.pending / this.stats.total) * 100) : 0, color: 'var(--bs-warning)' },
                    { name: 'In Transit', count: this.stats.in_transit, percentage: this.stats.total ? Math.round((this.stats.in_transit / this.stats.total) * 100) : 0, color: 'var(--bs-primary)' },
                    { name: 'Delivered', count: this.stats.delivered, percentage: this.stats.total ? Math.round((this.stats.delivered / this.stats.total) * 100) : 0, color: 'var(--bs-success)' },
                    { name: 'Returned', count: this.stats.returned, percentage: this.stats.total ? Math.round((this.stats.returned / this.stats.total) * 100) : 0, color: 'var(--bs-secondary)' },
                    { name: 'Failed', count: this.stats.failed, percentage: this.stats.total ? Math.round((this.stats.failed / this.stats.total) * 100) : 0, color: 'var(--bs-danger)' }
                ].filter(stat => stat.count > 0);

                const providerMap = {};
                allItems.forEach(item => {
                    const c = item.carrier_name || 'Unassigned';
                    if (!providerMap[c]) {
                        providerMap[c] = { name: c, total: 0, pending: 0, in_transit: 0, delivered: 0, returned: 0, failed: 0 };
                    }
                    providerMap[c].total++;
                    if (item.status === 'pending' || item.status === 'shipped') providerMap[c].pending++;
                    if (item.status === 'in_transit') providerMap[c].in_transit++;
                    if (item.status === 'delivered') providerMap[c].delivered++;
                    if (item.status === 'returned') providerMap[c].returned++;
                    if (item.status === 'failed') providerMap[c].failed++;
                });

                const colorClasses = ['primary', 'info', 'warning', 'danger', 'success'];
                this.topProviders = Object.values(providerMap).map((p, idx) => {
                    const activeTotal = p.total - p.pending;
                    const onTimeRate = activeTotal > 0 ? Math.round((p.delivered / activeTotal) * 100) : 0;
                    const exceptionRate = activeTotal > 0 ? (((p.failed + p.returned) / activeTotal) * 100).toFixed(1) : '0.0';
                    const successScore = Math.max(0, 100 - parseFloat(exceptionRate) - (100 - onTimeRate));
                    return {
                        name: p.name,
                        total: p.total,
                        pending: p.pending,
                        in_transit: p.in_transit,
                        delivered: p.delivered,
                        returned: p.returned,
                        failed: p.failed,
                        onTimeRate: onTimeRate,
                        avgTime: (Math.random() * 2 + 1.5).toFixed(1) + ' Days',
                        exceptionRate: parseFloat(exceptionRate),
                        successScore: Math.round(successScore),
                        theme: colorClasses[idx % colorClasses.length]
                    };
                }).sort((a, b) => b.total - a.total);

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

        get bulkAvailableActions() {
            if (this.selectedItems.length === 0) return {};
            
            const selectedObjs = this.items.filter(i => this.selectedItems.includes(String(i.id)));
            const statuses = new Set(selectedObjs.map(i => i.status));
            
            return {
                canInTransit: [...statuses].some(s => ['pending', 'failed'].includes(s)),
                canDelivered: [...statuses].some(s => ['in_transit'].includes(s)),
                canReturned: [...statuses].some(s => !['delivered', 'returned'].includes(s))
            };
        },

        async bulkAction(action) {
            if (this.selectedItems.length === 0) return;

            const selectedObjs = this.items.filter(i => this.selectedItems.includes(String(i.id)));
            let applicableObjs = [];

            if (action === 'mark_in_transit') {
                applicableObjs = selectedObjs.filter(i => ['pending', 'failed'].includes(i.status));
            } else if (action === 'mark_delivered') {
                applicableObjs = selectedObjs.filter(i => ['in_transit'].includes(i.status));
            } else if (action === 'mark_returned') {
                applicableObjs = selectedObjs.filter(i => !['delivered', 'returned'].includes(i.status));
            }

            if (applicableObjs.length === 0) {
                showToast('No applicable shipments selected for this action.', 'warning');
                return;
            }

            const applicableIds = applicableObjs.map(i => String(i.id));

            let returnReason = '';
            
            if (action === 'mark_returned') {
                const { value: reason, isConfirmed } = await Swal.fire({
                    title: 'Return Reason',
                    text: `Please select a reason for returning these ${applicableIds.length} applicable shipment(s).`,
                    input: 'select',
                    inputOptions: window.AppConfig?.returnReasons || {
                        'defective': 'Defective / Damaged in Transit',
                        'wrong_item': 'Wrong Item Sent',
                        'not_needed': 'No Longer Needed / Refused',
                        'undeliverable': 'Undeliverable / Failed Delivery',
                        'other': 'Other Reason'
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
                    }
                });
                if (!isConfirmed) return;
                returnReason = reason;
            } else {
                const actionLabels = {
                    mark_in_transit: 'mark in transit',
                    mark_delivered: 'mark delivered'
                };

                const result = await Swal.fire({
                    title: 'Confirm Bulk Action',
                    text: `Are you sure you want to ${actionLabels[action]} for ${applicableIds.length} applicable shipment(s)?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--bs-primary)',
                    cancelButtonColor: 'var(--bs-danger)',
                    confirmButtonText: 'Yes, proceed'
                });

                if (!result.isConfirmed) return;
            }

            this.isLoading = true;
            try {
                if (action === 'mark_returned') {
                    for (const shipment of applicableObjs) {
                        const orderId = shipment.order?.id || shipment.order_id;
                        if (shipment.order && shipment.order.items) {
                            const itemsToReturn = shipment.order.items.map(item => ({
                                product_id: item.product_id,
                                requested_qty: item.quantity,
                                max_qty: item.quantity
                            }));
                            
                            await this.apiRequest(`/orders/${orderId}/returns`, {
                                method: 'POST',
                                body: JSON.stringify({
                                    reason: returnReason,
                                    notes: 'Bulk return processed via Shipments dashboard.',
                                    items: itemsToReturn
                                })
                            }).catch(e => console.error(e));
                        }
                    }
                    
                    await this.apiRequest(`${this.apiBase}/bulk-action`, {
                        method: 'POST',
                        body: JSON.stringify({
                            action: action,
                            ids: applicableIds,
                            skip_order_sync: true
                        })
                    });
                } else {
                    await this.apiRequest(`${this.apiBase}/bulk-action`, {
                        method: 'POST',
                        body: JSON.stringify({
                            action: action,
                            ids: applicableIds
                        })
                    });
                }

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
            
            let defaultFollowUp;
            if (shipment.next_followup_date) {
                defaultFollowUp = shipment.next_followup_date.split('T')[0];
            } else {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                defaultFollowUp = tomorrow.toISOString().split('T')[0];
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
                delivered_by: shipment.delivered_by || userName
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
                
                if (newStatus === 'returned' && this.selectedShipment && this.selectedShipment.order && this.selectedShipment.order.items) {
                    this.returnForm.reason = '';
                    this.returnItems = (this.selectedShipment.order.items || []).map(item => ({
                        product_id: item.product_id,
                        name: item.product?.name || item.name || 'Unknown Product',
                        requested_qty: item.quantity,
                        max_qty: item.quantity
                    }));
                }
            } else if (newStatus === 'failed' || newStatus === 'in_transit' || newStatus === 'pending') {
                if (this.selectedShipment) {
                    this.statusForm.delivery_attempts = (this.selectedShipment.delivery_attempts || 0) + 1;
                    if (!this.statusForm.next_followup_date) {
                        const tomorrow = new Date();
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        this.statusForm.next_followup_date = tomorrow.toISOString().split('T')[0];
                    }
                }
            }
        },

        async saveStatus() {
            if (!this.selectedShipment) return;
            this.saving = true;
            try {
                if (this.statusForm.status === 'returned') {
                    if (!this.returnForm.reason) {
                        throw new Error('Please select a return reason.');
                    }
                    const itemsToReturn = this.returnItems.filter(i => i.requested_qty > 0);
                    if (itemsToReturn.length === 0) {
                        throw new Error('Please select at least one item to return with a quantity greater than 0.');
                    }
                    
                    const orderId = this.selectedShipment.order?.id || this.selectedShipment.order_id;
                    await this.apiRequest(`/orders/${orderId}/returns`, {
                        method: 'POST',
                        body: JSON.stringify({
                            reason: this.returnForm.reason,
                            notes: this.statusForm.description,
                            items: itemsToReturn
                        })
                    });
                }

                await this.apiRequest(`${this.apiBase}/${this.selectedShipment.id}/status`, {
                    method: 'POST',
                    body: JSON.stringify({ ...this.statusForm, skip_order_sync: true })
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

            if (shipment.order && shipment.order.items) {
                this.returnItems = (shipment.order.items || []).map(item => ({
                    product_id: item.product_id,
                    name: item.product?.name || item.name || 'Unknown Product',
                    requested_qty: item.quantity,
                    max_qty: item.quantity
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

            const itemsToReturn = this.returnItems.filter(i => i.requested_qty > 0);
            if (itemsToReturn.length === 0) {
                showToast('Please select at least one item to return with a quantity greater than 0.', 'warning');
                return;
            }

            this.saving = true;
            try {
                const itemsToReturn = this.returnItems.filter(i => i.requested_qty > 0);
                
                this.statusForm = {
                    status: 'returned',
                    location: '',
                    description: `Order Returned: ${this.returnForm.reason}. ${this.returnForm.notes}`,
                    delivery_attempts: this.selectedShipment.delivery_attempts || 0,
                    next_followup_date: this.selectedShipment.next_followup_date || '',
                    delivered_by: this.selectedShipment.delivered_by || ''
                };
                await this.saveStatus();
                this.returnModal?.hide();

            } catch (error) {
                showToast(error.message, 'error');
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
                const d = new Date(dateString);
                return isNaN(d.getTime()) ? dateString : `"${d.toLocaleString()}"`;
            };

            const headers = [
                'ID', 'Shipment No', 'Order No', 'Carrier', 'Service Providers', 
                'Tracking No', 'Status', 'Delivery Attempts', 'Next Follow-up Date', 
                'Reschedule Reason', 'Delivered By', 'Shipped At', 'Delivered At', 'Created At'
            ];
            const csvRows = [headers.join(',')];

            this.items.forEach(item => {
                const providers = item.service?.providers?.map(p => p.name).join('; ') || '';
                const values = [
                    item.id,
                    `"${item.shipment_no || ''}"`,
                    `"${item.order?.order_no || item.order_id || ''}"`,
                    `"${(item.carrier_name || '').replace(/"/g, '""')}"`,
                    `"${providers.replace(/"/g, '""')}"`,
                    `"${(item.tracking_no || '').replace(/"/g, '""')}"`,
                    `"${item.status.toUpperCase()}"`,
                    item.delivery_attempts || 0,
                    formatDate(item.next_followup_date),
                    `"${(item.reschedule_reason || '').replace(/"/g, '""')}"`,
                    `"${(item.delivered_by || '').replace(/"/g, '""')}"`,
                    formatDate(item.shipped_at),
                    formatDate(item.delivered_at),
                    formatDate(item.created_at)
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', `shipments_export_${new Date().toISOString().split('T')[0]}.csv`);
            a.click();
            URL.revokeObjectURL(url);
        }
    };

    window.Alpine.store('shipmentsTable', instance);
    return instance;
};
