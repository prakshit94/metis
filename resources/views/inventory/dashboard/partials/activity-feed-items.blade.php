@forelse($recentActivity as $activity)
    @php
        $statusColor = match(strtolower($activity->status ?? '')) {
            'completed', 'delivered', 'published', 'active', 'approved' => 'success',
            'pending', 'processing', 'draft', 'requested', 'in_transit' => 'warning',
            'cancelled', 'failed', 'rejected' => 'danger',
            default => 'secondary'
        };
    @endphp
    <div class="d-flex mb-4 activity-item">
        @if($activity->feed_type == 'movement')
            <div class="me-3">
                <div class="bg-body-tertiary text-secondary rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                    @if($activity->type == 'in')
                        <i class="bi bi-box-arrow-in-right text-success"></i>
                    @elseif($activity->type == 'out')
                        <i class="bi bi-box-arrow-right text-danger"></i>
                    @else
                        <i class="bi bi-arrow-left-right text-warning"></i>
                    @endif
                </div>
            </div>
            <div>
                <div class="fw-bold text-body-emphasis">
                    {{ $activity->product->name ?? 'Unknown Product' }}
                </div>
                <div class="text-muted small">
                    <span class="badge {{ $activity->type == 'in' ? 'bg-success' : ($activity->type == 'out' ? 'bg-danger' : 'bg-warning') }} bg-opacity-10 text-{{ $activity->type == 'in' ? 'success' : ($activity->type == 'out' ? 'danger' : 'warning') }} border border-{{ $activity->type == 'in' ? 'success' : ($activity->type == 'out' ? 'danger' : 'warning') }} border-opacity-25 px-2 py-1 rounded-pill me-1">
                        {{ strtoupper($activity->type) }}
                    </span>
                    {{ number_format($activity->quantity) }} units ({{ $activity->reference_label }} #{{ $activity->reference_number }})
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    <i class="bi bi-clock me-1"></i>{{ $activity->created_at->diffForHumans() }} by {{ $activity->performer->name ?? 'System' }}
                </div>
            </div>
        @elseif($activity->feed_type == 'receipt')
            <div class="me-3">
                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-receipt text-info"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-body-emphasis">
                    Goods Receipt: {{ $activity->grn_number }}
                </div>
                <div class="text-muted small">
                    <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25 px-2 py-1 rounded-pill me-1">
                        {{ strtoupper($activity->status) }}
                    </span>
                    For PO: {{ $activity->purchaseOrder->po_number ?? 'N/A' }}
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    <i class="bi bi-clock me-1"></i>{{ $activity->created_at->diffForHumans() }} by {{ $activity->creator->name ?? 'System' }}
                </div>
            </div>
        @elseif($activity->feed_type == 'adjustment')
            <div class="me-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-sliders text-primary"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-body-emphasis">
                    Inventory Adjustment: {{ $activity->reference_no }}
                </div>
                <div class="text-muted small">
                    <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25 px-2 py-1 rounded-pill me-1">
                        {{ strtoupper($activity->status) }}
                    </span>
                    Reason: {{ $activity->reason ?? 'N/A' }}
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    <i class="bi bi-clock me-1"></i>{{ $activity->created_at->diffForHumans() }} by {{ $activity->user->name ?? 'System' }}
                </div>
            </div>
        @endif
    </div>
@empty
    <div class="text-center text-muted p-5">
        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
        <p class="mb-0">No recent activities found.</p>
    </div>
@endforelse
