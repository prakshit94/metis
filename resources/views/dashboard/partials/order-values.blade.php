<section class="mb-4 dashboard-sales-section" x-data="dashboardOrderValues(@js($orderValueReport))" aria-label="Sales and order value report">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><h2 class="h5 mb-1">Sales summary</h2><span class="small text-muted">{{ $reportPeriod['from'] }} — {{ $reportPeriod['to'] }} · Orders placed in this date range · Cancelled orders excluded</span></div>
        <details class="small sales-report-help" style="max-width:620px"><summary class="text-primary">How amounts are calculated</summary><div class="text-muted mt-2">
            <ul class="mb-0 ps-3">
                <li><strong>Net sales:</strong> the same amount as the Summary card above. Uses net payable and excludes cancelled, future and returned orders, and orders with completed returns.</li>
                <li><strong>Delivered / Shipped:</strong> net sales for orders currently delivered (including completed) / dispatched.</li>
                <li><strong>Payments received / Refunds paid:</strong> completed transactions for these orders, even if the transaction happened on another date.</li>
                <li><strong>Amount due:</strong> unpaid net payable before return or refund adjustments.</li>
                <li><strong>Order value incl. wallet:</strong> net payable plus wallet used. Wallet is shown separately and is not added to net sales.</li>
            </ul>
            <p class="mb-0 mt-2">All figures use current order status. Cancelled orders are excluded throughout this section.</p>
        </div></details>
    </div>
    <div class="row g-3 mb-3">
        @foreach(['booked'=>'Net sales','delivered'=>'Delivered','dispatched'=>'Shipped','paid'=>'Payments received','refunded'=>'Refunds paid','outstanding'=>'Amount due'] as $key=>$label)
        @php($metricColor = ['booked'=>'primary','delivered'=>'success','dispatched'=>'info','paid'=>'success','refunded'=>'danger','outstanding'=>'warning'][$key])
        <div class="col-6 col-lg-4 col-xxl-2"><div class="card sales-value-metric h-100 border-start border-3 border-{{ $metricColor }}"><div class="card-body p-3"><span class="small text-muted">{{ $label }}</span><strong class="d-block fs-5 mt-1" x-text="money(report.totals.{{ $key }})"></strong></div></div></div>
        @endforeach
    </div>
    <details class="card mb-3"><summary class="p-3 fw-semibold">Order amounts · <span x-text="report.totals.orders"></span> sales orders</summary><div class="card-body pt-0"><div class="row g-3">
        @foreach(['gross'=>'Subtotal','discount'=>'Discount','tax'=>'Tax','payable'=>'Net payable','wallet'=>'Wallet used','value'=>'Order value incl. wallet'] as $key=>$label)
        <div class="col-6 col-lg-4 col-xl-2"><span class="small text-muted">{{ $label }}</span><strong class="d-block" x-text="money(report.totals.{{ $key }})"></strong></div>
        @endforeach
    </div></div></details>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="sales-group-tabs" role="group" aria-label="Group sales values">
                <template x-for="choice in [{id:'states',name:'Shipping state'},{id:'offices',name:'Office'},{id:'statuses',name:'Order status'}]" :key="choice.id"><button type="button" class="btn btn-sm" :class="tab === choice.id ? 'btn-primary' : 'btn-outline-primary'" :aria-pressed="tab === choice.id" @click="tab=choice.id; page=1; search=''" x-text="choice.name"></button></template>
            </div>
            <input type="search" class="form-control form-control-sm sales-report-search" style="max-width:260px" placeholder="Search state, office or status…" aria-label="Search sales groups" x-model="search" @input="page=1">
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle small mb-0 sales-report-table"><thead class="table-light"><tr><th x-text="tab === 'states' ? 'Shipping state' : tab === 'offices' ? 'Office' : 'Order status'"></th><th class="text-end">Orders</th>
            @foreach(['value'=>'Order value incl. wallet','booked'=>'Net sales','delivered'=>'Delivered','dispatched'=>'Shipped','paid'=>'Payments received','refunded'=>'Refunds paid','outstanding'=>'Amount due'] as $key=>$label)<th class="text-end text-nowrap">{{ $label }}</th>@endforeach
        </tr></thead><tbody>
            <template x-for="row in visibleRows" :key="tab === 'offices' ? (row.id ?? 'none') : row.name"><tr><th class="text-nowrap" scope="row" x-text="label(row.name)"></th><td class="text-end" x-text="row.orders"></td>
                @foreach(['value','booked','delivered','dispatched','paid','refunded','outstanding'] as $key)<td class="text-end text-nowrap" x-text="money(row.{{ $key }})"></td>@endforeach
            </tr></template>
            <tr x-show="!filteredRows.length"><td colspan="9" class="text-center text-muted py-4">No sales orders match this period and your access.</td></tr>
        </tbody></table></div>
        <div class="card-footer sales-report-footer d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="small text-muted"><span x-text="filteredRows.length"></span> results</span><nav class="d-flex align-items-center gap-2" aria-label="Sales group pagination"><button type="button" class="btn btn-sm btn-outline-secondary" :disabled="page <= 1" @click="page--">Previous</button><span class="small">Page <span x-text="page"></span> / <span x-text="pages"></span></span><button type="button" class="btn btn-sm btn-outline-secondary" :disabled="page >= pages" @click="page++">Next</button></nav></div>
    </div>
</section>
<script>
window.dashboardOrderValues = function(report) {
    return {
        report, tab:'states', search:'', page:1,
        get filteredRows() { const term=this.search.trim().toLowerCase(); return (this.report[this.tab] || []).filter(row => this.label(row.name).toLowerCase().includes(term)); },
        get pages() { return Math.max(1,Math.ceil(this.filteredRows.length / 10)); },
        get visibleRows() { return this.filteredRows.slice((this.page-1)*10,this.page*10); },
        money(value) { return new Intl.NumberFormat('en-IN',{style:'currency',currency:'INR',maximumFractionDigits:2}).format(Number(value)||0); },
        label(value) { return this.tab === 'statuses' ? String(value).replaceAll('_',' ').replace(/\b\w/g,letter=>letter.toUpperCase()) : value; }
    };
};
</script>
