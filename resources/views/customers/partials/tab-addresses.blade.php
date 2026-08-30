{{-- ══ TAB: Addresses ══ --}}
<div x-show="activeTab === 'addresses'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 fw-bold mb-0 text-body-emphasis">Registered Addresses</h3>
        <button type="button" @click.prevent="openAddModal" class="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-4 fw-bold text-uppercase shadow-sm" style="font-size: 11px; letter-spacing: 1px;">
            <i class="bi bi-plus-lg"></i> Add Address
        </button>
    </div>

    @if($customer->addresses->count())
        <div class="row g-4">
            @foreach($customer->addresses as $address)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 position-relative hover-shadow-lg transition-all">
                        @if($address->is_default)
                            <div class="position-absolute top-0 end-0 bg-primary text-white px-3 py-1 rounded-bl-4 rounded-tr-4 fw-bold text-uppercase shadow-sm" style="font-size: 9px; letter-spacing: 1px; z-index: 2; border-bottom-left-radius: 1rem; border-top-right-radius: 1rem;">
                                Default
                            </div>
                        @endif

                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                    <i class="bi bi-geo-alt fs-5"></i>
                                </div>
                                <span class="fw-bold text-uppercase text-body-emphasis" style="font-size: 12px; letter-spacing: 1px;">{{ $address->label ?: 'Address' }}</span>
                            </div>

                            <p class="fw-bold text-body-emphasis mb-1 fs-6">{{ $address->address_line_1 }}</p>
                            @if($address->address_line_2)
                                <p class="text-muted small mb-3">{{ $address->address_line_2 }}</p>
                            @else
                                <div class="mb-3"></div>
                            @endif

                            <div class="mt-auto pt-3 border-top d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Village</span>
                                    <span class="fw-bold text-body-emphasis" style="font-size: 12px;">{{ $address->village?->village_name ?? $address->village_name ?? '—' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Post Office</span>
                                    <span class="fw-bold text-body-emphasis" style="font-size: 12px;">{{ $address->village?->post_so_name ?? $address->post_office ?? '—' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Taluka</span>
                                    <span class="fw-bold text-body-emphasis" style="font-size: 12px;">{{ $address->village?->taluka_name ?? $address->taluka ?? '—' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">District</span>
                                    <span class="fw-bold text-body-emphasis" style="font-size: 12px;">{{ $address->village?->district_name ?? $address->city ?? '—' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">State</span>
                                    <span class="fw-bold text-body-emphasis" style="font-size: 12px;">{{ !empty($address->village?->state_name) ? $address->village->state_name : (!empty($address->state) ? $address->state : '—') }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Pincode</span>
                                    <span class="fw-bold text-body-emphasis font-monospace" style="font-size: 12px;">{{ $address->village?->pincode ?? $address->pincode ?? '—' }}</span>
                                </div>

                                {{-- ── Services available at this village ── --}}
                                @include('customers.partials._service-badges', ['addrModel' => $address])
                            </div>
                        </div>

                        {{-- Card Actions --}}
                        <div class="card-footer bg-transparent border-top p-3 d-flex justify-content-end gap-2">
                            <button @click="openEditModal({{ $address->toJson() }})" class="btn btn-sm btn-outline-secondary text-primary border rounded-3 px-3 shadow-sm">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button @click="openDeleteModal({{ $address->toJson() }})" class="btn btn-sm btn-outline-secondary text-danger border rounded-3 px-3 shadow-sm">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-5 px-4 rounded-4 border border-2 border-dashed bg-body-tertiary">
            <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                <i class="bi bi-map fs-1"></i>
            </div>
            <h4 class="h5 fw-bold text-body-emphasis">No Registered Addresses</h4>
            <p class="text-muted small mx-auto" style="max-width: 400px;">This customer has no addresses on file. Add one to enable shipping and billing.</p>
            <button type="button" @click.prevent="openAddModal" class="btn btn-primary rounded-pill px-4 mt-3 fw-bold text-uppercase shadow-sm" style="font-size: 11px; letter-spacing: 1px;">
                <i class="bi bi-plus-lg me-1"></i> Add First Address
            </button>
        </div>
    @endif
</div>
