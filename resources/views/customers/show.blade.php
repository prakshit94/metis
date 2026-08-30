@extends('layouts.app')

@section('title', 'Customer Profile: ' . $customer->name)
@section('page', 'customers.show')

@push('head')
    <style>
        .profile-nav .nav-link {
            color: var(--bs-body-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }
        .profile-nav .nav-link i {
            font-size: 1rem;
        }
        .profile-nav .nav-link:hover {
            background-color: var(--bs-tertiary-bg);
        }
        .profile-nav .nav-link.active {
            background-color: var(--bs-primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
        }
        .profile-nav .nav-link.active .chevron-right {
            display: block !important;
            margin-left: auto;
        }
        .profile-nav .nav-link .chevron-right {
            display: none;
        }
    </style>
@endpush

@section('content')

    @include('customers.partials.scripts')

    <div x-data="@include('customers.partials.alpine-state')">
        {{-- ── Header Section ── --}}
        @include('customers.partials.header_top')

        {{-- ── Main Layout: Vertical Tabs + Content ── --}}
        <div class="container-fluid p-4 px-lg-5 pb-5">
            <div class="row g-4">
                
                {{-- Sidebar: Vertical Navigation --}}
                <aside class="col-xl-3 col-lg-4">
                    <div class="sticky-top" style="top: 100px;">
                        <div class="card border-0 shadow-sm rounded-4 mb-3">
                            <div class="card-body p-3">
                                <nav class="nav flex-column profile-nav">
                                    <template x-for="tab in [
                                        { id: 'overview', icon: 'bi-person',         label: 'Profile'        },
                                        { id: 'addresses',icon: 'bi-geo-alt',      label: 'Addresses'      },
                                        { id: 'order',    icon: 'bi-bag', label: 'Order Products' },
                                        { id: 'history',  icon: 'bi-clock-history',        label: 'Order History'  },
                                        { id: 'finance',  icon: 'bi-hash',         label: 'Finance'        },
                                        { id: 'system',   icon: 'bi-gear',     label: 'System'         },
                                        { id: 'review',   icon: 'bi-check2-square', label: 'Order Review'   },
                                        { id: 'close',    icon: 'bi-x-circle',     label: 'Tag & Close Profile' }
                                    ].filter(t => t.id !== 'review' || activeTab === 'review')" :key="tab.id">
                                        <button
                                            type="button"
                                            @click="tab.id === 'close' ? closeCustomerProfile() : activeTab = tab.id"
                                            :class="{'nav-link': true, 'active': activeTab === tab.id}"
                                        >
                                            <i :class="'bi ' + tab.icon"></i>
                                            <span x-text="tab.label"></span>
                                            <i class="bi bi-chevron-right chevron-right"></i>
                                        </button>
                                    </template>
                                </nav>
                            </div>
                        </div>

                        {{-- Quick Info Card in Sidebar --}}
                        <div class="card border border-primary border-opacity-10 bg-primary bg-opacity-10 rounded-4 d-none d-lg-block">
                            <div class="card-body p-4">
                                <p class="mb-1 text-primary fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Customer Since</p>
                                <p class="mb-3 fw-bold text-body-emphasis fs-6">{{ $customer->created_at->format('M Y') }}</p>
                                <div class="pt-3 border-top border-primary border-opacity-10">
                                    <p class="mb-1 text-primary fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Loyalty Points</p>
                                    <p class="mb-0 fw-black text-body-emphasis fs-4">1,250</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- Main Content Area --}}
                <main class="col-xl-9 col-lg-8">
                    @include('customers.partials.tab-overview')
                    @include('customers.partials.tab-addresses')
                    @include('customers.partials.tab-order')
                    @include('customers.partials.tab-history')
                    @include('customers.partials.tab-finance')
                    @include('customers.partials.tab-system')
                    @include('customers.partials.tab-review')
                </main>
            </div>
        </div>

        @include('customers.partials.modals')

    </div>

@endsection
