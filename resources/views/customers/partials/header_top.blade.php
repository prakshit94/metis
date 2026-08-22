{{-- ── Header Section ── --}}
<div class="bg-body-tertiary border-bottom mb-4">
    <div class="container-fluid px-4 px-lg-5 pt-4 pb-3">
        
        {{-- Header Content --}}
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-4 mb-2">
            <div class="d-flex align-items-center gap-4">
                {{-- Avatar --}}
                <div class="position-relative flex-shrink-0">
                    <div class="d-flex align-items-center justify-content-center bg-primary text-white fw-bold fs-3 rounded-4 shadow-sm overflow-hidden" style="width: 64px; height: 64px;">
                        <img src="{{ isset($customer->avatar) && $customer->avatar ? Storage::url($customer->avatar) : asset('assets/images/farmersprofileimage.png') }}" alt="{{ $customer->name }}" class="w-100 h-100 object-fit-cover">
                    </div>
                    @php
                        $dotClass = match($customer->status) {
                            'active'    => 'bg-success',
                            'suspended' => 'bg-danger',
                            default     => 'bg-warning',
                        };
                    @endphp
                    <span class="position-absolute bottom-0 end-0 p-1 {{ $dotClass }} border border-2 border-white rounded-circle" style="width: 16px; height: 16px; transform: translate(25%, 25%);"></span>
                </div>
                
                <div>
                    <div class="d-flex align-items-center gap-3 mb-1">
                        <h1 class="h3 fw-bold mb-0 text-dark">{{ $customer->name }}</h1>
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
                    <p class="text-muted small fw-semibold d-flex align-items-center gap-2 mb-0">
                        <span class="badge bg-secondary-subtle text-secondary font-monospace">#{{ sprintf('%04d', $customer->id) }}</span>
                        <i class="bi bi-dot text-muted"></i>
                        <span>Registered {{ $customer->created_at->format('F d, Y') }}</span>
                    </p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                {{-- Editing Order Info Widget --}}
                <template x-if="editingOrderDetails">
                    <div class="d-none d-lg-flex flex-column align-items-end me-3 bg-warning-subtle border border-warning rounded-4 p-2 shadow-sm">
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

                <button type="button" @click.prevent="isCartOpen = true" class="btn btn-outline-primary d-flex align-items-center position-relative rounded-pill fw-bold text-uppercase px-4 shadow-sm" style="font-size: 11px; letter-spacing: 1px;">
                    <i class="bi bi-cart3 fs-6 me-2"></i> Cart
                    <span x-show="cart && cart.length > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" x-text="cart ? cart.length : 0" x-cloak></span>
                </button>
            </div>
        </div>
    </div>
</div>
