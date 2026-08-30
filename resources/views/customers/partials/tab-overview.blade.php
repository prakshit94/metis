{{-- ══ TAB: Overview / Profile ══ --}}
<div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
    <div class="row g-4">

        {{-- LEFT COL: Personal Info --}}
        <div class="col-xl-8 space-y-4">

            {{-- Identity Card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-primary bg-opacity-10 border-bottom-0 py-3 d-flex align-items-center justify-content-between rounded-top-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-fill text-primary fs-5"></i>
                        <h6 class="mb-0 fw-bold text-uppercase text-body-emphasis" style="font-size: 12px; letter-spacing: 1px;">Personal Information</h6>
                    </div>
                    <button type="button" @click="$dispatch('open-modal', { name: 'edit-profile-modal' })" 
                        class="btn btn-sm btn-outline-primary fw-bold text-uppercase d-flex align-items-center gap-2 rounded-pill px-3" style="font-size: 10px; letter-spacing: 1px;">
                        <i class="bi bi-pencil-square"></i>
                        Edit Profile
                    </button>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        @foreach([
                            ['Full Name', $customer->name, 'bi-person'],
                            ['Email', $customer->email ?: '—', 'bi-envelope'],
                            ['Phone', $customer->phone ?: '—', 'bi-phone'],
                            ['Alternate Mobile', $customer->alternatemobile ?? '—', 'bi-telephone'],
                            ['Relative Contact', ($customer->relative_name ?? '—') . ($customer->relative_phone ? " ({$customer->relative_phone})" : ''), 'bi-people'],
                            ['Customer ID', '#'.sprintf('%04d',$customer->id), 'bi-hash'],
                            ['Status', ucfirst($customer->status), 'bi-activity'],
                            ['Registered On', $customer->created_at->format('M d, Y'), 'bi-calendar3'],
                        ] as [$label, $val, $icon])
                        <div class="col-sm-6 d-flex align-items-start gap-3">
                            <div class="bg-body-tertiary text-secondary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 32px; height: 32px;">
                                <i class="{{ $icon }}"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">{{ $label }}</p>
                                <p class="mb-0 fw-bold text-body-emphasis text-truncate" style="font-size: 14px;">{{ $val }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Tax & Business Info --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-info bg-opacity-10 border-bottom-0 py-3 d-flex align-items-center gap-2 rounded-top-4">
                    <i class="bi bi-file-earmark-text-fill text-info fs-5"></i>
                    <h6 class="mb-0 fw-bold text-uppercase text-body-emphasis" style="font-size: 12px; letter-spacing: 1px;">Tax & Business Info</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        @foreach([
                            ['GST Number',    $customer->gst_no       ?: '—', 'bi-shield-check'],
                            ['PAN Number',    $customer->pan_no       ?: '—', 'bi-credit-card-2-front'],
                            ['Aadhaar Last4', $customer->aadhaar_last4 ?? '—', 'bi-lock'],
                            ['Company Name',  $customer->company_name  ?? '—', 'bi-briefcase'],
                            ['Party Code',    $customer->party_code    ?? '—', 'bi-hash'],
                            ['Category',      ucfirst($customer->category ?? '—'), 'bi-tag'],
                            ['Source',        is_array($customer->source) ? implode(', ', $customer->source) : ($customer->source ?: '—'), 'bi-compass'],
                            ['KYC Status',    $customer->kyc_completed ? 'Verified ✓' : 'Pending', 'bi-shield-check'],
                        ] as [$label, $val, $icon])
                        <div class="col-sm-6 d-flex align-items-start gap-3">
                            <div class="bg-info bg-opacity-10 text-info rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 32px; height: 32px;">
                                <i class="{{ $icon }}"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">{{ $label }}</p>
                                <p class="mb-0 fw-bold text-body-emphasis font-monospace" style="font-size: 14px;">{{ $val }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Agriculture Profile Section --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-warning bg-opacity-10 border-bottom-0 py-3 d-flex align-items-center gap-2 rounded-top-4">
                    <i class="bi bi-brightness-high-fill text-warning fs-5"></i>
                    <h6 class="mb-0 fw-bold text-uppercase text-body-emphasis" style="font-size: 12px; letter-spacing: 1px;">Agriculture Profile</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-sm-6 d-flex align-items-start gap-3">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 32px; height: 32px;">
                                <i class="bi bi-aspect-ratio"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Land Area</p>
                                <p class="mb-0 fw-bold text-body-emphasis" style="font-size: 14px;">{{ $customer->land_area ?? 0 }} {{ $customer->land_unit ?? 'Acre' }}</p>
                            </div>
                        </div>

                        <div class="col-sm-6 d-flex align-items-start gap-3">
                            <div class="bg-info bg-opacity-10 text-info rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 32px; height: 32px;">
                                <i class="bi bi-droplet"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Irrigation Type</p>
                                <p class="mb-0 fw-bold text-body-emphasis" style="font-size: 14px;">
                                    {{ is_array($customer->irrigation_type) ? implode(', ', $customer->irrigation_type) : ($customer->irrigation_type ?: '—') }}
                                </p>
                            </div>
                        </div>

                        <div class="col-12 d-flex align-items-start gap-3">
                            <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 32px; height: 32px;">
                                <i class="bi bi-tree"></i>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <p class="mb-2 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Major Crops Cultivated</p>
                                <div class="d-flex flex-wrap gap-2">
                                    @forelse($customer->crops ?? [] as $crop)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 1px;">
                                            {{ $crop }}
                                        </span>
                                    @empty
                                        <span class="fw-bold text-muted fst-italic" style="font-size: 14px;">No crops recorded</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COL: Stats + System --}}
        <div class="col-xl-4 space-y-4">
            {{-- Quick Stats --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-success bg-opacity-10 border-bottom-0 py-3 d-flex align-items-center gap-2 rounded-top-4">
                    <i class="bi bi-bar-chart-fill text-success fs-5"></i>
                    <h6 class="mb-0 fw-bold text-uppercase text-body-emphasis" style="font-size: 12px; letter-spacing: 1px;">Quick Stats</h6>
                </div>
                <div class="card-body p-4 d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-3 p-3 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-10">
                        <div class="bg-success bg-opacity-25 text-success rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-bag-check fs-4"></i>
                        </div>
                        <div>
                            <p class="mb-0 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Total Orders</p>
                            <p class="mb-0 fw-black text-body-emphasis fs-3 lh-1">{{ $customer->orders()->count() }}</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3 p-3 rounded-4 bg-danger bg-opacity-10 border border-danger border-opacity-10">
                        <div class="bg-danger bg-opacity-25 text-danger rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-geo-alt fs-4"></i>
                        </div>
                        <div>
                            <p class="mb-0 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Addresses</p>
                            <p class="mb-0 fw-black text-body-emphasis fs-3 lh-1">{{ $customer->addresses->count() }}</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3 p-3 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-10">
                        <div class="bg-primary bg-opacity-25 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-credit-card fs-4"></i>
                        </div>
                        <div>
                            <p class="mb-0 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Credit Limit</p>
                            <p class="mb-0 fw-black text-body-emphasis fs-4 lh-1 mt-1">₹ {{ number_format($customer->credit_limit ?? 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- System Info --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-secondary bg-opacity-10 border-bottom-0 py-3 d-flex align-items-center gap-2 rounded-top-4">
                    <i class="bi bi-gear-fill text-secondary fs-5"></i>
                    <h6 class="mb-0 fw-bold text-uppercase text-body-emphasis" style="font-size: 12px; letter-spacing: 1px;">System Info</h6>
                </div>
                <div class="card-body p-4 d-flex flex-column gap-3">
                    <div>
                        <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Created At</p>
                        <p class="mb-0 fw-bold text-body-emphasis fs-6">{{ $customer->created_at->format('M d, Y — h:i A') }}</p>
                        <p class="mb-0 text-muted" style="font-size: 11px;">{{ $customer->created_at->diffForHumans() }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Last Updated</p>
                        <p class="mb-0 fw-bold text-body-emphasis fs-6">{{ $customer->updated_at->format('M d, Y — h:i A') }}</p>
                        <p class="mb-0 text-muted" style="font-size: 11px;">{{ $customer->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
