{{-- ══ TAB: Addresses ══ --}}
<div x-show="activeTab === 'addresses'" class="security-section" x-cloak>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5>Registered Addresses</h5>
            <p>Manage customer shipping and billing addresses</p>
        </div>
        <button type="button" @click.prevent="openAddModal" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Address
        </button>
    </div>

    @forelse($customer->addresses as $address)
    <div class="security-item align-items-start">
        <div class="security-info w-100 pe-4">
            <h6 class="d-flex align-items-center gap-2">
                {{ $address->label ?: 'Address' }}
                @if($address->is_default)
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-0 fw-bold" style="font-size: 10px;">DEFAULT</span>
                @endif
            </h6>
            <small class="d-block mb-1 text-body fw-medium">{{ $address->address_line_1 }} {{ $address->address_line_2 }}</small>
            <small class="text-muted d-block mb-2">
                {{ $address->village?->village_name ?? $address->village_name ?? '—' }}, 
                {{ $address->village?->post_so_name ?? $address->post_office ?? '—' }}, 
                {{ $address->village?->taluka_name ?? $address->taluka ?? '—' }}, 
                {{ $address->village?->district_name ?? $address->city ?? '—' }}, 
                {{ !empty($address->village?->state_name) ? $address->village->state_name : (!empty($address->state) ? $address->state : '—') }} - 
                <span class="font-monospace fw-bold">{{ $address->village?->pincode ?? $address->pincode ?? '—' }}</span>
            </small>
            <div class="mt-2">
                @include('customers.partials._service-badges', ['addrModel' => $address])
            </div>
        </div>
        <div class="d-flex flex-column gap-2 flex-shrink-0">
            <button @click="openEditModal({{ $address->toJson() }})" class="btn btn-sm btn-outline-secondary w-100 text-start">
                <i class="bi bi-pencil-square me-1"></i> Edit
            </button>
            <button @click="openDeleteModal({{ $address->toJson() }})" class="btn btn-sm btn-outline-danger w-100 text-start">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </div>
    </div>
    @empty
    <div class="security-item justify-content-center text-center py-5 bg-body-tertiary border-dashed rounded-3">
        <div class="security-info text-center w-100">
            <i class="bi bi-map text-muted mb-3 d-block fs-2"></i>
            <h6>No Registered Addresses</h6>
            <small>This customer has no addresses on file. Add one to enable shipping and billing.</small>
            <button type="button" @click.prevent="openAddModal" class="btn btn-outline-primary btn-sm mt-3">
                <i class="bi bi-plus-lg me-1"></i> Add First Address
            </button>
        </div>
    </div>
    @endforelse
</div>
