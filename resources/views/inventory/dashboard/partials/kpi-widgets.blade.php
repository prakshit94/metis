<div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-4 g-lg-5 mb-4">
    <div class="col">
        <div class="card h-100 border-0 bg-primary bg-opacity-10 shadow-sm rounded-4 metric-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-primary text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Pending Orders</h6>
                    <div class="h3 mb-0 fw-bold text-body-emphasis">{{ number_format($kpis['pending_orders']) }}</div>
                    <small class="text-muted fw-semibold">of {{ number_format($kpis['total_orders']) }} total</small>
                </div>
                <div class="stats-icon bg-primary bg-opacity-25 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card h-100 border-0 bg-warning bg-opacity-10 shadow-sm rounded-4 metric-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-warning text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Pending Transfers</h6>
                    <div class="h3 mb-0 fw-bold text-body-emphasis">{{ number_format($kpis['pending_transfers']) }}</div>
                    <small class="text-muted fw-semibold">Awaiting action</small>
                </div>
                <div class="stats-icon bg-warning bg-opacity-25 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-truck fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card h-100 border-0 bg-danger bg-opacity-10 shadow-sm rounded-4 metric-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-danger text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Pending Returns</h6>
                    <div class="h3 mb-0 fw-bold text-body-emphasis">{{ number_format($kpis['pending_returns']) }}</div>
                    <small class="text-muted fw-semibold">Requires inspection</small>
                </div>
                <div class="stats-icon bg-danger bg-opacity-25 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-arrow-return-left fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card h-100 border-0 bg-info bg-opacity-10 shadow-sm rounded-4 metric-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-info text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Low Stock Alerts</h6>
                    <div class="h3 mb-0 fw-bold text-body-emphasis">{{ number_format($kpis['low_stock_items']) }}</div>
                    <small class="text-muted fw-semibold">Items below threshold</small>
                </div>
                <div class="stats-icon bg-info bg-opacity-25 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card h-100 border-0 bg-secondary bg-opacity-10 shadow-sm rounded-4 metric-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-secondary-emphasis text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Inbound POs</h6>
                    <div class="h3 mb-0 fw-bold text-body-emphasis">{{ number_format($kpis['pending_purchase_orders']) }}</div>
                    <small class="text-muted fw-semibold">Pending receipt</small>
                </div>
                <div class="stats-icon bg-secondary bg-opacity-25 text-secondary-emphasis rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-box-arrow-in-down fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>
