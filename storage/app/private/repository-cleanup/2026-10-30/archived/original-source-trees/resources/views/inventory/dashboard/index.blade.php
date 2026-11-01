@extends('layouts.app')

@section('title', 'Warehouse Command Center')
@section('page', 'inventory.dashboard')

@section('content')
<div x-data="warehouseDashboard()">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <h1 class="h3 mb-0 fw-bold"><i class="bi bi-buildings text-primary me-2"></i>Warehouse Command Center</h1>
            <p class="text-muted mb-0 small">Real-time operational visibility across all facilities.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <select x-model="warehouseId" @change="updateDashboard()" class="form-select form-select-sm fw-semibold shadow-sm rounded-pill px-3" :class="warehouseId ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-body-tertiary text-muted border-0'" style="min-width: 160px; cursor: pointer; transition: all 0.2s;">
                    <option value="" class="text-body">All Warehouses</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" class="text-body">{{ $wh->name }}</option>
                    @endforeach
                </select>
                
                <select x-model="dateRange" @change="updateDashboard()" class="form-select form-select-sm fw-semibold shadow-sm rounded-pill px-3" :class="dateRange ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-body-tertiary text-muted border-0'" style="min-width: 140px; cursor: pointer; transition: all 0.2s;">
                    <option value="today" class="text-body">Today</option>
                    <option value="yesterday" class="text-body">Yesterday</option>
                    <option value="this_week" class="text-body">This Week</option>
                    <option value="this_month" class="text-body">This Month</option>
                    <option value="prev_month" class="text-body">Previous Month</option>
                </select>
            </div>
            
            <div x-show="isLoading" class="spinner-border spinner-border-sm text-primary ms-2" role="status" x-cloak>
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <div id="dashboard-content" :class="{'opacity-50': isLoading}" style="transition: opacity 0.2s ease-in-out;">
        @include('inventory.dashboard.partials.kpi-widgets')
        @include('inventory.dashboard.partials.pipeline')
        @include('inventory.dashboard.partials.charts')
        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                @include('inventory.dashboard.partials.activity-feed')
            </div>
            <div class="col-lg-4">
                @include('inventory.dashboard.partials.alerts')
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('warehouseDashboard', () => ({
            warehouseId: '{{ $warehouseId ?? '' }}',
            dateRange: '{{ $dateRange ?? 'this_week' }}',
            isLoading: false,

            async updateDashboard() {
                this.isLoading = true;
                try {
                    const url = new URL(window.location.href);
                    if (this.warehouseId) {
                        url.searchParams.set('warehouse_id', this.warehouseId);
                    } else {
                        url.searchParams.delete('warehouse_id');
                    }
                    
                    if (this.dateRange) {
                        url.searchParams.set('date_range', this.dateRange);
                    } else {
                        url.searchParams.delete('date_range');
                    }

                    window.history.pushState({}, '', url);

                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const html = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        const newContent = doc.getElementById('dashboard-content');
                        if (newContent) {
                            document.getElementById('dashboard-content').innerHTML = newContent.innerHTML;
                        }
                    }
                } catch (error) {
                    console.error('Error updating dashboard:', error);
                } finally {
                    this.isLoading = false;
                }
            }
        }));
    });
</script>
@endpush
