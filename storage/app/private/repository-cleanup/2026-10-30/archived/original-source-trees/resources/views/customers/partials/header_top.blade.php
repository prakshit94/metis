<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        {{-- Avatar --}}
        <div class="position-relative flex-shrink-0 transition-all hover-scale">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-gradient text-white fw-bold fs-4 rounded-circle shadow-sm border border-2 border-body overflow-hidden" style="width: 56px; height: 56px;">
                <img src="{{ isset($customer->avatar) && $customer->avatar ? Storage::url($customer->avatar) : asset('assets/images/farmersprofileimage.png') }}" alt="{{ $customer->name }}" class="w-100 h-100 object-fit-cover">
            </div>
            @php
                $dotClass = match($customer->status) {
                    'active'    => 'bg-success',
                    'suspended' => 'bg-danger',
                    default     => 'bg-warning',
                };
            @endphp
            <span class="position-absolute bottom-0 end-0 p-1 {{ $dotClass }} border border-2 border-body rounded-circle" style="width: 14px; height: 14px; transform: translate(25%, 25%);"></span>
        </div>
        
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 mb-0">{{ $customer->name }}</h1>
                @php
                    $badgeClass = match($customer->status) {
                        'active'    => 'bg-success-subtle text-success border-success-subtle',
                        'suspended' => 'bg-danger-subtle text-danger border-danger-subtle',
                        default     => 'bg-warning-subtle text-warning border-warning-subtle',
                    };
                @endphp
                <span class="badge border {{ $badgeClass }} text-uppercase" style="font-size: 10px; letter-spacing: 1px;">
                    {{ $customer->status }}
                </span>
            </div>
            <p class="text-muted mb-0 d-flex align-items-center gap-2">
                <span class="badge bg-secondary-subtle text-secondary font-monospace">#{{ sprintf('%04d', $customer->id) }}</span>
                <i class="bi bi-dot text-muted"></i>
                <span>Registered {{ $customer->created_at->format('M d, Y') }}</span>
            </p>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        {{-- Editing Order Info Widget --}}
        <template x-if="editingOrderDetails">
            <div class="d-none d-lg-flex flex-column align-items-end bg-warning-subtle border border-warning rounded-4 p-2 shadow-sm">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="spinner-grow spinner-grow-sm text-warning" role="status" style="width: 8px; height: 8px;"></span>
                    <h4 class="mb-0 text-warning text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 1px;">Editing <span x-text="editingOrderDetails.order_no"></span></h4>
                </div>
                <div class="d-flex align-items-center gap-3 text-muted fw-semibold" style="font-size: 10px;">
                    <div class="d-flex align-items-center gap-1" title="Placed At">
                        <i class="bi bi-calendar3"></i> 
                        <span x-text="new Date(editingOrderDetails.created_at).toLocaleString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: 'numeric', minute:'2-digit' })"></span>
                    </div>
                    <template x-if="editingOrderDetails.creator">
                        <div class="d-flex align-items-center gap-1" title="Placed By">
                            <i class="bi bi-person"></i> <span x-text="editingOrderDetails.creator.name"></span>
                        </div>
                    </template>
                    <template x-if="editingOrderDetails.updater && editingOrderDetails.updated_by !== editingOrderDetails.created_by">
                        <div class="d-flex align-items-center gap-1 text-danger fw-bold" title="Last Updated By">
                            <i class="bi bi-pencil-square"></i> <span x-text="editingOrderDetails.updater.name"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <button type="button" @click.prevent="isCartOpen = true" class="btn btn-outline-primary d-flex align-items-center position-relative rounded-pill px-4">
            <i class="bi bi-cart3 me-2"></i> Cart
            <span x-show="cart && cart.length > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" x-text="cart ? cart.length : 0" x-cloak></span>
        </button>
    </div>
</div>
