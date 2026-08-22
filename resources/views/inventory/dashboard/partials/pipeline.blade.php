@php
    $totalOrders = 0;
    $totalValue = 0;
    $deliveredOrders = $pipeline['delivered']['count'];
    
    foreach(['pending', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'returned', 'cancelled'] as $status) {
        $totalOrders += $pipeline[$status]['count'];
        $totalValue += $pipeline[$status]['amount'];
    }
    
    $deliveryRate = $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100) : 0;

    $selectedWarehouseName = 'All Warehouses';
    if ($warehouseId) {
        $selectedWarehouse = collect($warehouses)->firstWhere('id', $warehouseId);
        if ($selectedWarehouse) {
            $selectedWarehouseName = $selectedWarehouse->name;
        }
    }
@endphp

<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4 position-relative">
    <!-- Left accent line -->
    <div class="position-absolute top-0 bottom-0 start-0 bg-primary" style="width: 4px;"></div>
    
    <div class="card-body p-4">
        <div class="row align-items-center g-4">
            <!-- Info Section -->
            <div class="col-lg-3 col-md-12 border-end border-secondary-subtle pe-lg-4 mb-4 mb-lg-0 pb-4 pb-lg-0">
                <h3 class="h5 fw-bold mb-3 d-flex align-items-center text-body-emphasis">
                    <i class="bi bi-buildings text-primary me-2 fs-5"></i>{{ $selectedWarehouseName }}
                </h3>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-2 px-3 fs-6 rounded-pill">
                        {{ $totalOrders }} Orders
                    </div>
                </div>
                <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-4 border border-secondary-subtle mb-3 shadow-sm">
                    <span class="d-block text-muted small fw-medium mb-1 text-uppercase tracking-wider">Total Value</span>
                    <span class="fs-4 fw-bold text-success">₹{{ number_format($totalValue, 2) }}</span>
                </div>
                
                <div class="px-1">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted fw-bold text-uppercase tracking-wide" style="font-size: 0.7rem;">Delivery Rate</span>
                        <span class="small fw-bold text-success">{{ $deliveryRate }}%</span>
                    </div>
                    <div class="progress rounded-pill bg-success bg-opacity-10" style="height: 6px;">
                        <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: {{ $deliveryRate }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Status Breakdown Section -->
            <div class="col-lg-9 col-md-12 ps-lg-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted small fw-bold text-uppercase tracking-wide mb-0" style="font-size: 0.75rem; letter-spacing: 0.5px;">Pipeline Stages</p>
                    
                    <!-- Exceptions / Returns Badge -->
                    @if($pipeline['returns_requested']['count'] > 0)
                    <div class="badge bg-danger bg-opacity-10 border border-danger border-opacity-25 text-danger-emphasis py-1 px-3 rounded-pill d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>{{ $pipeline['returns_requested']['count'] }} Return(s) Req (₹{{ number_format($pipeline['returns_requested']['amount']) }})</span>
                    </div>
                    @endif
                </div>

                <!-- Horizontal Pipeline -->
                <div class="d-flex align-items-stretch flex-nowrap overflow-x-auto pb-2 gap-2" style="scrollbar-width: thin;">
                    
                    <!-- Pending -->
                    <div class="flex-fill p-3 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-25 d-flex flex-column text-center position-relative transition-hover" style="min-width: 130px;">
                        <span class="small text-warning-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Pending</span>
                        <span class="fw-bold fs-4 text-warning-emphasis lh-1 mb-1">{{ $pipeline['pending']['count'] }}</span>
                        <small class="text-warning-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;">₹{{ number_format($pipeline['pending']['amount']) }}</small>
                        <i class="bi bi-caret-right-fill position-absolute top-50 start-100 translate-middle text-warning opacity-50 d-none d-sm-block" style="font-size: 1.5rem; transform: translate(-50%, -50%) !important; z-index: 2;"></i>
                    </div>

                    <!-- Confirmed -->
                    <div class="flex-fill p-3 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-25 d-flex flex-column text-center position-relative transition-hover" style="min-width: 130px;">
                        <span class="small text-info-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Confirmed</span>
                        <span class="fw-bold fs-4 text-info-emphasis lh-1 mb-1">{{ $pipeline['confirmed']['count'] }}</span>
                        <small class="text-info-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;">₹{{ number_format($pipeline['confirmed']['amount']) }}</small>
                        <i class="bi bi-caret-right-fill position-absolute top-50 start-100 translate-middle text-info opacity-50 d-none d-sm-block" style="font-size: 1.5rem; transform: translate(-50%, -50%) !important; z-index: 2;"></i>
                    </div>

                    <!-- Processing -->
                    <div class="flex-fill p-3 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 d-flex flex-column text-center position-relative transition-hover" style="min-width: 130px;">
                        <span class="small text-primary-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Processing</span>
                        <span class="fw-bold fs-4 text-primary-emphasis lh-1 mb-1">{{ $pipeline['processing']['count'] }}</span>
                        <small class="text-primary-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;">₹{{ number_format($pipeline['processing']['amount']) }}</small>
                        <i class="bi bi-caret-right-fill position-absolute top-50 start-100 translate-middle text-primary opacity-50 d-none d-sm-block" style="font-size: 1.5rem; transform: translate(-50%, -50%) !important; z-index: 2;"></i>
                    </div>

                    <!-- Ready -->
                    <div class="flex-fill p-3 rounded-4 bg-warning-subtle bg-opacity-50 border border-warning border-opacity-25 d-flex flex-column text-center position-relative transition-hover" style="min-width: 130px;">
                        <span class="small text-warning-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Ready</span>
                        <span class="fw-bold fs-4 text-warning-emphasis lh-1 mb-1">{{ $pipeline['ready_to_ship']['count'] }}</span>
                        <small class="text-warning-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;">₹{{ number_format($pipeline['ready_to_ship']['amount']) }}</small>
                        <i class="bi bi-caret-right-fill position-absolute top-50 start-100 translate-middle text-warning opacity-50 d-none d-sm-block" style="font-size: 1.5rem; transform: translate(-50%, -50%) !important; z-index: 2;"></i>
                    </div>

                    <!-- Dispatched -->
                    <div class="flex-fill p-3 rounded-4 bg-secondary bg-opacity-10 border border-secondary border-opacity-25 d-flex flex-column text-center position-relative transition-hover" style="min-width: 130px;">
                        <span class="small text-secondary-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Dispatched</span>
                        <span class="fw-bold fs-4 text-secondary-emphasis lh-1 mb-1">{{ $pipeline['dispatched']['count'] }}</span>
                        <small class="text-secondary-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;">₹{{ number_format($pipeline['dispatched']['amount']) }}</small>
                        <i class="bi bi-caret-right-fill position-absolute top-50 start-100 translate-middle text-secondary opacity-50 d-none d-sm-block" style="font-size: 1.5rem; transform: translate(-50%, -50%) !important; z-index: 2;"></i>
                    </div>

                    <!-- Delivered -->
                    <div class="flex-fill p-3 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 d-flex flex-column text-center transition-hover" style="min-width: 130px;">
                        <span class="small text-success-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Delivered</span>
                        <span class="fw-bold fs-4 text-success-emphasis lh-1 mb-1">{{ $pipeline['delivered']['count'] }}</span>
                        <small class="text-success-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;">₹{{ number_format($pipeline['delivered']['amount']) }}</small>
                    </div>
                </div>
                
                <!-- Non-Lifecycle Statuses -->
                <div class="d-flex gap-4 mt-4 ps-1">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-secondary rounded-circle" style="width: 8px; height: 8px;"></div>
                        <span class="small text-secondary-emphasis fw-medium">Returned: <span>{{ $pipeline['returned']['count'] }}</span> (₹{{ number_format($pipeline['returned']['amount']) }})</span>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-body-emphasis rounded-circle" style="width: 8px; height: 8px;"></div>
                        <span class="small text-body-emphasis fw-medium">Cancelled: <span>{{ $pipeline['cancelled']['count'] }}</span> (₹{{ number_format($pipeline['cancelled']['amount']) }})</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
