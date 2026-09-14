    document.addEventListener('alpine:init', () => {
        Alpine.data('complaintModalShared', () => ({
            complaints: [],
            assignableUsers: [],
            stats: { total: 0, open: 0, in_progress: 0, resolved: 0, closed: 0 },
            isLoading: false,
            isSubmitting: false,
            searchQuery: '',
            statusFilter: '',
            priorityFilter: '',
            sortField: 'created_at',
            sortDirection: 'desc',
            currentPage: 1,
            totalPages: 1,
            totalComplaints: 0,
            selectedComplaints: [],
            isEditing: false,
            isLookupLocked: false,
            modalInstance: null,
            selectedComplaint: null,
            form: {
                id: null, order_no: '', customer_id: '', category: 'other',
                priority: 'medium', subject: '', description: '',
                status: 'open', resolution_notes: '', product_ids: []
            },
            replyMessage: '',
            isReplying: false,
            bulkAssignTo: '',
            bulkPriority: '',

            // --- Order Lookup Variables ---
            searchQueryOrder: '',
            fetchedOrders: [],
            isSearchingOrders: false,
            searchOrderError: '',
            selectedOrderDetails: null,

            // ── Helpers ────────────────────────────────────────────────────────
            csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.content || '';
            },
            async api(url, options = {}) {
                const method = (options.method || 'GET').toUpperCase();
                const headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrf(),
                    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                    ...(options.headers || {}),
                };
                const res = await fetch(url, { ...options, method, headers });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({ message: `HTTP ${res.status}` }));
                    throw Object.assign(new Error(err.message || `HTTP ${res.status}`), { status: res.status, data: err });
                }
                return res.json();
            },

            // ── Computed ───────────────────────────────────────────────────────
            get allSelected() {
                return this.complaints.length > 0 && this.selectedComplaints.length === this.complaints.length;
            },
            get timelineFeed() {
                if (!this.selectedComplaint) return [];
                const logs = (this.selectedComplaint.status_logs || []).map(l => ({ ...l, _type: 'log', _date: new Date(l.created_at) }));
                const replies = (this.selectedComplaint.replies || []).map(r => ({ ...r, _type: 'reply', _date: new Date(r.created_at) }));
                
                // Filter out 'created' events since they are redundant with status_logs, and clean up internal fields
                const audits = (this.selectedComplaint.audits || [])
                    .filter(a => a.event !== 'created')
                    .map(a => {
                        const cleanValues = (vals) => {
                            if (!vals) return null;
                            const copy = { ...vals };
                            ['id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'].forEach(k => delete copy[k]);
                            return copy;
                        };
                        return { 
                            ...a, 
                            _type: 'audit', 
                            _date: new Date(a.created_at),
                            new_values: cleanValues(a.new_values),
                            old_values: cleanValues(a.old_values)
                        };
                    })
                    .filter(a => a.new_values && Object.keys(a.new_values).length > 0);
                return [...logs, ...replies, ...audits].sort((a, b) => a._date - b._date);
            },
            get visiblePages() {
                const delta = 2, range = [];
                for (let i = Math.max(2, this.currentPage - delta); i <= Math.min(this.totalPages - 1, this.currentPage + delta); i++) range.push(i);
                if (this.currentPage - delta > 2) range.unshift('...');
                if (this.currentPage + delta < this.totalPages - 1) range.push('...');
                range.unshift(1);
                if (this.totalPages > 1) range.push(this.totalPages);
                return range;
            },

            get alreadyComplainedProductIds() {
                if (!this.selectedOrderDetails || !this.selectedOrderDetails.existing_complaints) return [];
                const ids = [];
                this.selectedOrderDetails.existing_complaints.forEach(c => {
                    if (c.product_ids && Array.isArray(c.product_ids)) {
                        ids.push(...c.product_ids.map(String));
                    }
                });
                return ids;
            },
            get hasEntireOrderComplaint() {
                if (!this.selectedOrderDetails || !this.selectedOrderDetails.existing_complaints) return false;
                return this.selectedOrderDetails.existing_complaints.some(c => !c.product_ids || c.product_ids.length === 0);
            },
            get isClosed() {
                return this.selectedComplaint?.status === 'closed';
            },
            get availableStatuses() {
                const flow = ['open', 'in_progress', 'resolved', 'closed'];
                const currentStatus = this.selectedComplaint?.status || 'open';
                let currentIndex = flow.indexOf(currentStatus);
                if (currentIndex === -1) currentIndex = 0;
                
                return flow.filter((status, index) => index >= currentIndex).map(status => {
                    let label = status.replace(/_/g, ' ');
                    label = label.charAt(0).toUpperCase() + label.slice(1);
                    if (label === 'In progress') label = 'In Progress';
                    return { value: status, label: label };
                });
            },

            // ── Lifecycle ──────────────────────────────────────────────────────
            
            async init() {
                
                try {
                    const data = await this.api('/api/complaints');
                    this.assignableUsers = data.assignable_users || [];
                } catch(e) {}
            },


            openSharedModal(orderNo, customerId) {
                this.isEditing = false;
                this.isLookupLocked = !!orderNo;
                this.form = {
                    id: null,
                    order_no: orderNo,
                    customer_id: customerId || '',
                    category: 'other',
                    priority: 'medium',
                    subject: '',
                    description: '',
                    status: 'open',
                    resolution_notes: '',
                    product_ids: [],
                    is_entire_order: true
                };
                this.searchQueryOrder = orderNo;
                this.modalInstance = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('complaintModal'));
                this.modalInstance.show();
                if (orderNo) {
                    this.searchOrders();
                }
            },

                

            // ── Complaints API ─────────────────────────────────────────────────
            async fetchComplaints() {
                this.isLoading = true;
                try {
                    const params = new URLSearchParams({
                        page: this.currentPage,
                        sort_by: this.sortField,
                        sort_direction: this.sortDirection
                    });
                    if (this.searchQuery)   params.append('search',   this.searchQuery);
                    if (this.statusFilter)  params.append('status',   this.statusFilter);
                    if (this.priorityFilter) params.append('priority', this.priorityFilter);

                    const data = await this.api(`/api/complaints?${params.toString()}`);
                    this.complaints      = data.complaints.data;
                    this.currentPage     = data.complaints.current_page;
                    this.totalPages      = data.complaints.last_page;
                    this.totalComplaints = data.complaints.total;
                    this.stats           = data.stats;
                    this.assignableUsers = data.assignable_users || [];
                } catch (e) {
                    console.error('fetchComplaints error:', e);
                    window.Swal.fire('Error', 'Failed to load complaints', 'error');
                }
                this.isLoading = false;
            },

            async fetchStats() {
                try {
                    const data = await this.api('/api/complaints/stats');
                    this.stats.total       = data.total;
                    this.stats.open        = data.open;
                    this.stats.in_progress = data.in_progress;
                    this.stats.resolved    = data.resolved;
                } catch (e) {
                    console.warn('fetchStats error:', e);
                }
            },

            filterComplaints() { this.currentPage = 1;  },
            clearFilters() {
                this.searchQuery = ''; this.statusFilter = ''; this.priorityFilter = '';
                this.filterComplaints();
            },
            sortBy(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field; this.sortDirection = 'desc';
                }
                
            },
            goToPage(page) {
                if (page >= 1 && page <= this.totalPages) { this.currentPage = page;  }
            },
            toggleAll(checked) {
                this.selectedComplaints = checked ? this.complaints.map(c => String(c.id)) : [];
            },

            // ── Order Lookup ───────────────────────────────────────────────────
            async searchOrders() {
                if (!this.searchQueryOrder.trim()) return;
                this.isSearchingOrders = true;
                this.searchOrderError  = '';
                this.fetchedOrders     = [];
                this.selectedOrderDetails = null;

                try {
                    const data   = await this.api(`/api/orders?search=${encodeURIComponent(this.searchQueryOrder.trim())}&limit=50`);
                    const orders = data.orders?.data ?? data.data ?? [];

                    if (!orders.length) {
                        this.searchOrderError = 'No orders found matching this query.';
                    } else if (orders.length === 1) {
                        await this.selectOrder(orders[0].id);
                    } else {
                        this.fetchedOrders = orders;
                    }
                } catch (e) {
                    this.searchOrderError = e.status === 403
                        ? 'Permission denied — you cannot search orders.'
                        : 'Failed to search orders. Please try again.';
                    console.error('searchOrders error:', e);
                }
                this.isSearchingOrders = false;
            },

            async selectOrder(orderId) {
                if (!orderId) return;
                this.isSearchingOrders = true;
                try {
                    const data = await this.api(`/api/orders/${orderId}`);
                    const raw  = data.order || data;
                    this.selectedOrderDetails = this.mapRawOrder(raw);
                    this.form.order_no    = this.selectedOrderDetails.orderNumber;
                    this.form.customer_id = raw.party_id || '';
                    this.fetchedOrders    = [];

                    // Fetch existing complaints for this order
                    try {
                        const compData = await this.api(`/api/complaints?order_id=${orderId}&per_page=50`);
                        // Ensure it's reactive
                        this.selectedOrderDetails.existing_complaints = compData.complaints.data || [];
                        if (this.hasEntireOrderComplaint && !this.isEditing) {
                            this.form.is_entire_order = false;
                        }
                    } catch (e) {
                        console.error('Failed to fetch order complaints', e);
                    }
                } catch (e) {
                    this.searchOrderError = 'Failed to fetch order details.';
                    console.error('selectOrder error:', e);
                }
                this.isSearchingOrders = false;
            },

            mapRawOrder(o) {
                if (!o) return null;
                const fmt = v => parseFloat(v ?? 0) || 0;
                const fmtAddr = (o, type) => {
                    const addr = type === 'shipping' ? o.shipping_address : o.billing_address;
                    if (!addr) return null;
                    const parts = [addr.address_line_1, addr.address_line_2, addr.village_name, addr.taluka, addr.district, addr.city, addr.state, addr.pincode].filter(Boolean);
                    return { formatted: parts.join(', ') || 'N/A' };
                };
                const invoice  = o.invoice || null;
                const invPmts  = invoice?.payments ?? [];
                const paid     = invPmts.filter(p => p.status === 'completed').reduce((s, p) => s + fmt(p.amount), 0);
                const netInv   = fmt(invoice?.total_amount);
                const allPmts  = o.payments || invPmts;
                const lastPmt  = allPmts.slice().reverse().find(p => p.payment_method);
                const pmtLabel = lastPmt ? lastPmt.payment_method.toUpperCase().replace(/_/g, ' ') : (invoice ? 'PENDING PAYMENT' : 'NOT RECORDED');

                return {
                    id: o.id,
                    orderNumber: o.order_no,
                    type: o.type || 'sale',
                    orderDate: o.order_date,
                    status: o.lifecycle_status || o.status,
                    statusLabel: o.status_label || (o.lifecycle_status || o.status || '').charAt(0).toUpperCase() + (o.lifecycle_status || o.status || '').slice(1).replace(/_/g, ' '),
                    customer: {
                        id: o.party_id || null,
                        name: o.party ? `${o.party.firstname || ''} ${o.party.lastname || ''}`.trim() : 'N/A',
                        email: o.party?.email || 'N/A',
                        avatar: o.party?.avatar || '/assets/images/default_avatar.jpeg',
                        phone: o.party?.phone || 'N/A',
                        company: o.party?.company_name || '',
                    },
                    warehouse: o.warehouse ? {
                        name: o.warehouse.name || 'N/A',
                        address: [o.warehouse.address_line_1, o.warehouse.city, o.warehouse.state].filter(Boolean).join(', ') || 'N/A'
                    } : null,
                    shippingAddress: fmtAddr(o, 'shipping'),
                    billingAddress:  fmtAddr(o, 'billing'),
                    invoice: invoice ? { number: invoice.invoice_no || 'N/A', date: invoice.invoice_date || null, status: invoice.status || 'N/A', total: netInv, paid, due: Math.max(0, netInv - paid) } : null,
                    items: (o.items || []).map(item => ({
                        id:    item.product_id || item.product?.id || item.id,
                        name:  item.product?.name || 'Unknown Product',
                        sku:   item.product?.sku  || '',
                        image: item.product?.image_path ? `/storage/${item.product.image_path}` : null,
                        quantity: item.quantity,
                        price:    fmt(item.unit_price),
                        discount: fmt(item.discount_amount),
                        discountBadgeLabel: fmt(item.discount_amount) > 0 ? `-₹${fmt(item.discount_amount).toFixed(2)}` : '',
                        tax: fmt(item.tax_amount), taxRate: item.tax_rate || 0, net: fmt(item.total_amount)
                    })),
                    itemCount: o.items_count || o.items?.length || 0,
                    total:         fmt(o.net_amount),
                    subtotal:      (o.items || []).reduce((s, i) => s + fmt(i.unit_price) * fmt(i.quantity), 0),
                    taxTotal:      fmt(o.tax_amount),
                    discountTotal: fmt(o.discount_amount),
                    paymentMethod: pmtLabel,
                    couponCode:    o.coupon_code || '',
                    payments: (o.payments || []).map(p => ({
                        id: p.id, amount: fmt(p.amount), method: p.payment_method || 'N/A',
                        status: p.status || 'N/A', date: p.payment_date || null, transactionId: p.transaction_id || 'N/A'
                    })),
                    statusLogs: (o.status_logs || []).map(l => ({
                        status: l.status,
                        notes: l.notes,
                        created_at: l.created_at,
                        user: l.user ? (l.user.name || l.user.first_name) : 'System'
                    })),
                    original: o
                };
            },

            // ── Formatters ─────────────────────────────────────────────────────
            formatDate(str) {
                if (!str) return '—';
                return new Date(str).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            },
            formatCurrency(val) {
                return parseFloat(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            formatDateTime(dateStr) {
                if (!dateStr) return 'N/A';
                return new Date(dateStr).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
            },
            getStatusColor(status) {
                return { open: 'warning', in_progress: 'info', resolved: 'success', closed: 'secondary' }[status] || 'secondary';
            },
            getStatusLabel(status) { return (status || '').replace(/_/g, ' ').toUpperCase(); },
            getPriorityClass(priority) {
                return {
                    low:    'bg-secondary bg-opacity-25 text-body',
                    medium: 'bg-info bg-opacity-25 text-info',
                    high:   'bg-warning bg-opacity-25 text-warning',
                    urgent: 'bg-danger bg-opacity-25 text-danger'
                }[priority] || 'bg-secondary bg-opacity-25 text-body';
            },
            getStatusTheme(status) {
                return { pending:'warning', pending_confirmation:'info', confirmed:'primary', processing:'secondary', ready_to_ship:'dark', dispatched:'info', shipped:'info', delivered:'success', cancelled:'danger', returned:'danger' }[status] || 'secondary';
            },

            // ── Modal ──────────────────────────────────────────────────────────
            openCreateModal() {
                this.isEditing = false;
                this.isLookupLocked = false;
                this.form = { id: null, order_no: '', customer_id: '', assigned_to: '', category: 'other', priority: 'medium', subject: '', description: '', status: 'open', resolution_notes: '', product_ids: [], is_entire_order: true };
                this.searchQueryOrder = ''; this.fetchedOrders = []; this.selectedOrderDetails = null; this.searchOrderError = '';
                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('complaintModal')).show();
            },
            async viewComplaint(cmp) {
                this.isEditing = true;
                this.selectedComplaint = cmp;
                this.form = {
                    id:               cmp.id,
                    order_no:         cmp.order?.order_no || '',
                    customer_id:      cmp.customer_id || '',
                    assigned_to:      cmp.assigned_to || '',
                    category:         cmp.category || 'other',
                    priority:         cmp.priority || 'medium',
                    subject:          cmp.subject || '',
                    description:      cmp.description || '',
                    status:           cmp.status || 'open',
                    resolution_notes: cmp.resolution_notes || '',
                    complaint_number: cmp.complaint_number || '',
                    product_ids:      (cmp.product_ids || []).map(String),
                    is_entire_order:  !cmp.product_ids || cmp.product_ids.length === 0,
                };
                this.searchQueryOrder = ''; this.fetchedOrders = []; this.selectedOrderDetails = null; this.searchOrderError = '';
                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('complaintModal')).show();

                if (cmp.order_id || (cmp.order && cmp.order.id)) {
                    const orderId = cmp.order_id || cmp.order.id;
                    try {
                        const data = await this.api(`/api/orders/${orderId}`);
                        this.selectedOrderDetails = this.mapRawOrder(data.order || data.data || data);
                    } catch (e) {
                        console.error('Failed to fetch order details:', e);
                    }
                }
            },

            async saveComplaint() {
                this.isSubmitting = true;
                try {
                    const payload = { ...this.form };
                    if (this.form.is_entire_order) {
                        payload.product_ids = [];
                    } else {
                        payload.product_ids = this.form.product_ids.map(Number);
                    }
                    
                    if (this.isEditing) {
                        await this.api(`/api/complaints/${this.form.id}`, { method: 'PUT', body: JSON.stringify(payload) });
                        window.Swal.fire('Updated', 'Complaint updated successfully.', 'success');
                    } else {
                        await this.api('/api/complaints', { method: 'POST', body: JSON.stringify(payload) });
                        window.Swal.fire('Created', 'Complaint logged successfully.', 'success');
                    }
                    window.bootstrap.Modal.getOrCreateInstance(document.getElementById('complaintModal')).hide();
                    window.dispatchEvent(new CustomEvent('complaint-saved'));
                } catch (e) {
                    const msg = e.data?.message
                        || (e.data?.errors ? Object.values(e.data.errors).flat().join(' ') : null)
                        || 'Failed to save complaint.';
                    window.Swal.fire('Error', msg, 'error');
                }
                this.isSubmitting = false;
            },

            async postReply() {
                if (!this.replyMessage.trim() || !this.selectedComplaint) return;
                this.isReplying = true;
                try {
                    const res = await this.api(`/api/complaints/${this.selectedComplaint.id}/reply`, {
                        method: 'POST',
                        body: JSON.stringify({ message: this.replyMessage })
                    });
                    if (!this.selectedComplaint.replies) this.selectedComplaint.replies = [];
                    this.selectedComplaint.replies.push(res.data);
                    
                    if (this.selectedComplaint.status === 'open') {
                        this.selectedComplaint.status = 'in_progress';
                        this.form.status = 'in_progress';
                        if (!this.selectedComplaint.status_logs) this.selectedComplaint.status_logs = [];
                        this.selectedComplaint.status_logs.push({
                            status: 'in_progress',
                            notes: 'Status automatically changed to in_progress after first reply.',
                            created_at: res.data.created_at,
                            user: res.data.user
                        });
                         // silently update background table
                    }
                    
                    this.replyMessage = '';
                } catch (e) {
                    window.Swal.fire('Error', e.data?.message || 'Failed to post reply.', 'error');
                }
                this.isReplying = false;
            },

            async bulkAction(action, payload = {}) {
                if (!this.selectedComplaints.length) return;
                const confirmed = await window.Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to perform this action on ${this.selectedComplaints.length} complaint(s).`,
                    icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, proceed!'
                });
                if (!confirmed.isConfirmed) return;
                this.isSubmitting = true;
                try {
                    await this.api('/api/complaints/bulk-action', {
                        method: 'POST',
                        body: JSON.stringify({ action, ids: this.selectedComplaints, ...payload })
                    });
                    window.Swal.fire('Success', 'Bulk action completed.', 'success');
                    this.selectedComplaints = [];
                    
                    
                } catch (e) {
                    window.Swal.fire('Error', e.data?.message || 'Bulk action failed.', 'error');
                }
                this.isSubmitting = false;
            },

            async downloadCsv(url, filename, options = {}) {
                const method = (options.method || 'GET').toUpperCase();
                const headers = {
                    'Accept': 'text/csv',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrf(),
                    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                };
                try {
                    const res = await fetch(url, { ...options, method, headers });
                    if (!res.ok) throw new Error('Download failed');
                    const blob = await res.blob();
                    const urlBlob = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = urlBlob;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(urlBlob);
                } catch (e) {
                    console.error(e);
                    window.Swal.fire('Error', 'Failed to download export.', 'error');
                }
            },

            async bulkExport() {
                const params = new URLSearchParams();
                if (this.searchQuery)   params.append('search',   this.searchQuery);
                if (this.statusFilter)  params.append('status',   this.statusFilter);
                if (this.priorityFilter) params.append('priority', this.priorityFilter);
                
                await this.downloadCsv(`/api/complaints/export?${params.toString()}`, `complaints_export_${new Date().getTime()}.csv`);
            },

            async exportSelected() {
                if (!this.selectedComplaints.length) return;
                await this.downloadCsv('/api/complaints/export-selected', `selected_complaints_${new Date().getTime()}.csv`, {
                    method: 'POST',
                    body: JSON.stringify({ ids: this.selectedComplaints })
                });
            }
        }));
    });
