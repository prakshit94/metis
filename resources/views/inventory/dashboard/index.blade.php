@extends('layouts.app')

@section('title', 'Warehouse Command Center')
@section('page', 'inventory.dashboard')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
    <div>
        <h1 class="h3 mb-0 fw-bold"><i class="bi bi-buildings text-primary me-2"></i>Warehouse Command Center</h1>
        <p class="text-muted mb-0 small">Real-time operational visibility across all facilities.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form method="GET" action="{{ route('inventory.dashboard') }}" class="d-flex align-items-center gap-2" id="warehouse-filter-form">
            <select x-select name="warehouse_id" class="form-select form-select-sm fw-semibold shadow-sm rounded-pill px-3 {{ $warehouseId ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-body-tertiary text-muted border-0' }}" style="min-width: 160px; cursor: pointer; transition: all 0.2s;" onchange="document.getElementById('warehouse-filter-form').submit()">
                <option value="" class="text-body">All Warehouses</option>
                @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}" class="text-body" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                @endforeach
            </select>
            
            <select x-select name="date_range" class="form-select form-select-sm fw-semibold shadow-sm rounded-pill px-3 {{ $dateRange ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-body-tertiary text-muted border-0' }}" style="min-width: 140px; cursor: pointer; transition: all 0.2s;" onchange="document.getElementById('warehouse-filter-form').submit()">
                <option value="today" class="text-body" {{ $dateRange == 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" class="text-body" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" class="text-body" {{ $dateRange == 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" class="text-body" {{ $dateRange == 'this_month' ? 'selected' : '' }}>This Month</option>
            </select>
        </form>
    </div>
</div>

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

@endsection
