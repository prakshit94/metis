@extends('layouts.app')

@section('title', 'Customer Profile: ' . $customer->name)
@section('page', 'customers.show')

@push('head')

@endpush

@section('content')

    @include('customers.partials.scripts')

    <div class="security-page" x-data="@include('customers.partials.alpine-state')">
        {{-- ── Header Section ── --}}
        @include('customers.partials.header_top')

        {{-- ── Main Layout: Vertical Tabs + Content ── --}}
        <div class="security-layout container-fluid p-4 p-lg-4">
            <div class="row g-6">
                
                {{-- Sidebar: Vertical Navigation --}}
                <div class="col-lg-3 security-sidebar">
                    <nav class="security-nav nav nav-pills flex-column">
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
                            <a
                                href="#"
                                @click="tab.id === 'close' ? closeCustomerProfile() : activeTab = tab.id"
                                :class="{'nav-link': true, 'active': activeTab === tab.id}"
                            >
                                <i :class="'bi ' + tab.icon + ' me-2'"></i>
                                <span x-text="tab.label"></span>
                            </a>
                        </template>
                    </nav>

                    {{-- Quick Info Card in Sidebar --}}
                    <div class="card border border-primary border-opacity-10 bg-primary bg-opacity-10 rounded-4 d-none d-lg-block mt-4">
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

                {{-- Main Content Area --}}
                <div class="col-lg-9 security-content">
                    @include('customers.partials.tab-overview')
                    @include('customers.partials.tab-addresses')
                    @include('customers.partials.tab-order')
                    @include('customers.partials.tab-history')
                    @include('customers.partials.tab-finance')
                    @include('customers.partials.tab-system')
                    @include('customers.partials.tab-review')
                </div>
            </div>
        </div>

        @include('customers.partials.modals')

    </div>

@endsection
