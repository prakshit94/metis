@extends('layouts.app')
@section('title', 'Targets & Achievements')
@section('page', 'targets.index')

@section('content')
<div class="user-management" x-data="targetsModule()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-award-fill text-primary me-2"></i>Targets &amp; Achievements</h1>
            <p class="text-muted mb-0">Manage performance metrics across Users, Teams, and Departments</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload me-2"></i>Import
            </button>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download me-2"></i>Export
                </button>
                <ul class="dropdown-menu shadow border-0 rounded-3 mt-2">
                    <li><h6 class="dropdown-header">Export by Period</h6></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="{{ route('targets.export', array_merge(request()->query(), ['period_type' => 'daily'])) }}"><i class="bi bi-calendar-day me-2 text-primary"></i>Daily Targets</a></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="{{ route('targets.export', array_merge(request()->query(), ['period_type' => 'monthly'])) }}"><i class="bi bi-calendar-month me-2 text-primary"></i>Monthly Targets</a></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="{{ route('targets.export', array_merge(request()->query(), ['period_type' => 'yearly'])) }}"><i class="bi bi-calendar me-2 text-primary"></i>Yearly Targets</a></li>
                    <li><hr class="dropdown-divider opacity-50"></li>
                    <li><a class="dropdown-item d-flex align-items-center fw-medium" href="{{ route('targets.export', request()->query()) }}"><i class="bi bi-filter me-2 text-primary"></i>Export Current View</a></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="{{ route('targets.export') }}"><i class="bi bi-asterisk me-2 text-primary"></i>Export All Data</a></li>
                </ul>
            </div>

            {{-- Recalculate button: syncs achieved_amount from live order/payment/invoice data --}}
            <form method="POST" action="{{ route('targets.recalculate') }}" id="recalculate-form">
                @csrf
                {{-- Pass active filters so only the currently-viewed targets are recalculated --}}
                <input type="hidden" name="metric_type" value="{{ request('metric_type') }}">
                <input type="hidden" name="period_type" value="{{ request('period_type', 'daily') }}">
                <button type="submit" class="btn btn-outline-info"
                        onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-2\' role=\'status\'></span>Syncing…'; this.form.submit();">
                    <i class="bi bi-arrow-repeat me-2"></i>Sync Achieved
                </button>
            </form>

            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>New Target
            </button>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1080;">
        @if(session('success'))
        <div class="toast align-items-center text-bg-success border-0 shadow-lg mb-3" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
            <div class="d-flex">
                <div class="toast-body fw-medium">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        @endif
        @if ($errors->any())
            @foreach ($errors->all() as $error)
            <div class="toast align-items-center text-bg-danger border-0 shadow-lg mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
                <div class="d-flex">
                    <div class="toast-body fw-medium">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $error }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            @endforeach
        @endif
    </div>

    <!-- Stats Row -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-bullseye fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Total Targets</p>
                            <div class="h3 mb-0 fw-bold">{{ $stats['total'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-activity fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Active</p>
                            <div class="h3 mb-0 fw-bold">{{ $stats['active'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-trophy-fill fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Achieved</p>
                            <div class="h3 mb-0 fw-bold">{{ $stats['achieved'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-x-circle-fill fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Failed</p>
                            <div class="h3 mb-0 fw-bold">{{ $stats['failed'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-body border-bottom p-4">
            <div class="row align-items-center g-3">
                <div class="col">
                    <h2 class="h5 card-title mb-0 fw-bold"><i class="bi bi-list-task text-primary me-2"></i>Targets Directory</h2>
                </div>
                <div class="col-auto">
                    @php
                        $currentMonth = (int)date('n');
                        $currentYear = (int)date('Y');
                        $defaultFinancialYear = $currentMonth < 4 ? $currentYear - 1 : $currentYear;
                        $selectedYear = request('financial_year', $defaultFinancialYear);
                        $selectedPeriod = request('period_type', 'daily');
                        $selectedMonth = request('month', $currentMonth);
                    @endphp
                    <form method="GET" action="{{ route('targets.index') }}" class="d-flex flex-wrap gap-2 justify-content-end" x-ref="searchForm" @submit.prevent="filterTable($refs.searchForm)" x-data="{ searchPeriodType: '{{ $selectedPeriod }}' }">
                        @php
                            $searchQuery = request('search');
                            $selectedSearches = is_array($searchQuery) ? $searchQuery : ($searchQuery ? [$searchQuery] : []);
                        @endphp
                        <div class="dropdown" x-data="{ filterSearch: '', selectedAssignees: {{ \Illuminate\Support\Js::from($selectedSearches) }}, get filterAssignees() { return [...(this.availableAssignees['User']||[]), ...(this.availableAssignees['Team']||[]), ...(this.availableAssignees['Department']||[])].filter(a => a.name && a.name.toLowerCase().includes(this.filterSearch.toLowerCase())); } }">
                            <button class="btn btn-sm text-start d-flex justify-content-between align-items-center border border-secondary border-opacity-25 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 200px; height: 36px; border-radius: 8px; background-color: var(--bs-body-bg);" data-bs-auto-close="outside">
                                <span class="text-truncate" :class="selectedAssignees.length ? 'text-body-emphasis fw-medium' : 'text-muted'" x-text="selectedAssignees.length ? selectedAssignees.length + ' selected' : 'Search assignee...'"></span>
                                <i class="bi bi-chevron-down ms-2 text-muted"></i>
                            </button>
                            <div class="dropdown-menu shadow border-0 p-0" style="width: 250px;">
                                <div class="p-2 border-bottom bg-body-tertiary">
                                    <input type="text" x-model="filterSearch" class="form-control form-control-sm shadow-none border-secondary border-opacity-25" placeholder="Search assignees..." autofocus>
                                </div>
                                <div class="p-2 border-bottom d-flex justify-content-between align-items-center" style="font-size: 11px;">
                                    <div>
                                        <a href="#" @click.prevent="selectedAssignees = filterAssignees.map(a => a.name); filterTable($refs.searchForm)" class="text-decoration-none text-primary me-2">Select All</a>
                                        <a href="#" @click.prevent="selectedAssignees = []; filterTable($refs.searchForm)" class="text-decoration-none text-muted">Clear</a>
                                    </div>
                                    <span class="text-muted"><span x-text="selectedAssignees.length"></span> selected</span>
                                </div>
                                <div class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                                    <template x-for="assignee in filterAssignees" :key="assignee.id + '_' + assignee.name">
                                        <label class="list-group-item d-flex align-items-center gap-2 border-0 py-2 cursor-pointer" style="font-size: 13px;">
                                            <input type="checkbox" name="search[]" :value="assignee.name" class="form-check-input mt-0" :checked="selectedAssignees.includes(assignee.name)" @change="if($el.checked) { selectedAssignees.push(assignee.name) } else { selectedAssignees = selectedAssignees.filter(n => n !== assignee.name) }; filterTable($refs.searchForm)">
                                            <span x-text="assignee.name"></span>
                                        </label>
                                    </template>
                                    <div x-show="filterAssignees.length === 0" class="p-3 text-center text-muted small">No results found</div>
                                </div>
                            </div>
                        </div>
                        <select name="per_page" class="form-select form-select-sm border-secondary border-opacity-25 shadow-none" style="width: 80px; height: 36px; border-radius: 8px;" @change="filterTable($refs.searchForm)">
                            <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                            <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                        </select>
                        <select name="metric_type" class="form-select form-select-sm border-secondary border-opacity-25 shadow-none" style="width: 150px; height: 36px; border-radius: 8px;" @change="filterTable($refs.searchForm)">
                            <option value="">All Metrics</option>
                            <option value="sales_revenue" {{ request('metric_type') == 'sales_revenue' ? 'selected' : '' }}>Sales Revenue</option>
                            <option value="orders_count" {{ request('metric_type') == 'orders_count' ? 'selected' : '' }}>Orders Count</option>
                            <option value="invoice_collection" {{ request('metric_type') == 'invoice_collection' ? 'selected' : '' }}>Invoice Collection</option>
                            <option value="payment_collection" {{ request('metric_type') == 'payment_collection' ? 'selected' : '' }}>Payment Collection</option>
                            <option value="calls_made" {{ request('metric_type') == 'calls_made' ? 'selected' : '' }}>Calls Made</option>
                        </select>
                        <select name="period_type" class="form-select form-select-sm border-secondary border-opacity-25 shadow-none" style="width: 120px; height: 36px; border-radius: 8px;" x-model="searchPeriodType" @change="filterTable($refs.searchForm)">
                            <option value="daily" {{ $selectedPeriod == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="monthly" {{ $selectedPeriod == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="yearly" {{ $selectedPeriod == 'yearly' ? 'selected' : '' }}>Yearly</option>
                        </select>
                        <select name="month" class="form-select form-select-sm border-secondary border-opacity-25 shadow-none" style="width: 140px; height: 36px; border-radius: 8px;" :disabled="searchPeriodType === 'yearly'" @change="filterTable($refs.searchForm)">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                            @endforeach
                        </select>
                        <select name="financial_year" class="form-select form-select-sm border-secondary border-opacity-25 shadow-none" style="width: 130px; height: 36px; border-radius: 8px;" @change="filterTable($refs.searchForm)">
                            @foreach(range(date('Y') - 2, date('Y') + 2) as $y)
                                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}-{{ substr($y + 1, -2) }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body p-0" id="targetsTableBody">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">
                        <tr class="table-light">
                            <th class="ps-4"><i class="bi bi-person-lines-fill me-1 text-muted opacity-50"></i> Assignee</th>
                            <th><i class="bi bi-activity me-1 text-muted opacity-50"></i> Metric Type</th>
                            <th><i class="bi bi-clock-history me-1 text-muted opacity-50"></i> Period &amp; Timeline</th>
                            <th><i class="bi bi-bullseye me-1 text-muted opacity-50"></i> Target vs Achieved</th>
                            <th><i class="bi bi-bar-chart-fill me-1 text-muted opacity-50"></i> Achievement Progress</th>
                            <th><i class="bi bi-flag-fill me-1 text-muted opacity-50"></i> Status</th>
                            <th class="text-end pe-4"><i class="bi bi-gear-fill me-1 text-muted opacity-50"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($targets as $target)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 rounded-3 me-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 shadow-sm" style="width: 42px; height: 42px;">
                                            @if(class_basename($target->targetable_type) === 'User')
                                                <i class="fs-5 bi bi-person-fill"></i>
                                            @elseif(class_basename($target->targetable_type) === 'Team')
                                                <i class="fs-5 bi bi-people-fill"></i>
                                            @else
                                                <i class="fs-5 bi bi-building"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-bold text-body-emphasis mb-1">{{ $target->targetable->name ?? $target->targetable->email ?? 'Unknown' }}</div>
                                            <div class="text-muted d-flex align-items-center gap-2" style="font-size: 11px;">
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2 py-1 rounded-pill">
                                                    {{ class_basename($target->targetable_type) }}
                                                </span>
                                                <span>ID: #{{ $target->id }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $metricIcon = match($target->metric_type) {
                                            'sales_revenue' => 'bi-currency-rupee',
                                            'orders_count' => 'bi-cart',
                                            'invoice_collection' => 'bi-receipt',
                                            'payment_collection' => 'bi-wallet',
                                            'calls_made' => 'bi-telephone',
                                            default => 'bi-graph-up'
                                        };
                                    @endphp
                                    <span class="badge rounded-pill px-3 py-2 fw-medium border bg-body-tertiary text-body-emphasis border-secondary-subtle shadow-sm">
                                        <i class="bi {{ $metricIcon }} me-1 text-primary opacity-75"></i>
                                        {{ ucwords(str_replace('_', ' ', $target->metric_type)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        @php
                                            $periodIcon = match($target->period_type) {
                                                'daily' => 'bi-calendar-day',
                                                'monthly' => 'bi-calendar-month',
                                                'yearly' => 'bi-calendar',
                                                default => 'bi-calendar3'
                                            };
                                        @endphp
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 align-self-start rounded-pill shadow-sm">
                                            <i class="bi {{ $periodIcon }} me-1"></i>{{ ucfirst($target->period_type) }}
                                        </span>
                                        @php
                                            $now   = \Carbon\Carbon::now();
                                            $start = $target->start_date;
                                            $end   = $target->end_date;
                                            $isDaily = $target->period_type === 'daily';

                                            if ($isDaily) {
                                                // For a daily target show hours + minutes within the day
                                                $dayStart = $start->copy()->startOfDay();
                                                $dayEnd   = $start->copy()->endOfDay();

                                                if ($now < $dayStart) {
                                                    $elapsedH = 0; $elapsedM = 0;
                                                    $leftH = 23; $leftM = 59;
                                                } elseif ($now > $dayEnd) {
                                                    $elapsedH = 23; $elapsedM = 59;
                                                    $leftH = 0; $leftM = 0;
                                                } else {
                                                    $elapsedMins = (int) $dayStart->diffInMinutes($now);
                                                    $elapsedH = (int) floor($elapsedMins / 60);
                                                    $elapsedM = $elapsedMins % 60;
                                                    $leftMins = (int) $now->diffInMinutes($dayEnd);
                                                    $leftH = (int) floor($leftMins / 60);
                                                    $leftM = $leftMins % 60;
                                                }
                                            } else {
                                                $totalDays = $start->diffInDays($end) + 1;
                                                if ($now < $start) {
                                                    $pastDays = 0; $remainingDays = $totalDays;
                                                } elseif ($now > $end) {
                                                    $pastDays = $totalDays; $remainingDays = 0;
                                                } else {
                                                    $pastDays = (int) $start->copy()->startOfDay()->diffInDays($now->copy()->startOfDay());
                                                    $remainingDays = (int) $now->copy()->startOfDay()->diffInDays($end->copy()->endOfDay());
                                                }
                                            }
                                        @endphp
                                        <div style="font-size: 11px; line-height: 1.4;">
                                            @if($isDaily)
                                                <div class="text-nowrap text-muted"><span class="fw-semibold text-body-emphasis">Date:</span> {{ $start->format('d M Y') }}</div>
                                            @else
                                                <div class="text-nowrap text-muted"><span class="fw-semibold text-body-emphasis">Start:</span> {{ $start->format('d M Y') }}</div>
                                                <div class="text-nowrap text-muted"><span class="fw-semibold text-body-emphasis">End:</span> {{ $end->format('d M Y') }}</div>
                                            @endif
                                            <div class="text-nowrap mt-1 pt-1 border-top border-secondary border-opacity-25 d-flex gap-2">
                                                @if($isDaily)
                                                    <span class="text-primary fw-medium" title="Hours elapsed today">
                                                        <i class="bi bi-clock-history me-1"></i>{{ $elapsedH }}h {{ str_pad($elapsedM, 2, '0', STR_PAD_LEFT) }}m past
                                                    </span>
                                                    <span class="text-muted">•</span>
                                                    <span class="{{ $leftH > 0 || $leftM > 0 ? 'text-warning-emphasis' : 'text-danger' }} fw-medium" title="Hours remaining today">
                                                        <i class="bi bi-hourglass-split me-1"></i>{{ $leftH }}h {{ str_pad($leftM, 2, '0', STR_PAD_LEFT) }}m left
                                                    </span>
                                                @else
                                                    <span class="text-primary fw-medium" title="Days elapsed since start">
                                                        <i class="bi bi-clock-history me-1"></i>{{ $pastDays }} past
                                                    </span>
                                                    <span class="text-muted">•</span>
                                                    <span class="{{ $remainingDays > 0 ? 'text-warning-emphasis' : 'text-danger' }} fw-medium" title="Days remaining till end">
                                                        <i class="bi bi-hourglass-split me-1"></i>{{ $remainingDays }} left
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $gap = max(0, (float)$target->target_amount - (float)$target->achieved_amount);
                                    @endphp
                                    <div class="d-flex align-items-baseline gap-1 mb-1">
                                        <span class="text-muted" style="font-size: 11px; min-width: 60px;">🎯 Target:</span>
                                        <span class="fw-bold text-body-emphasis" style="font-size: 15px;">₹{{ number_format($target->target_amount, 0) }}</span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-1 mb-1">
                                        <span class="text-muted" style="font-size: 11px; min-width: 60px;">✅ Achieved:</span>
                                        <span class="fw-bold {{ $target->achievement_percentage >= 100 ? 'text-success' : 'text-primary' }}" style="font-size: 13px;">₹{{ number_format($target->achieved_amount, 0) }}</span>
                                    </div>
                                    @if($gap > 0)
                                    <div class="d-flex align-items-baseline gap-1">
                                        <span class="text-muted" style="font-size: 11px; min-width: 60px;">⬜ Remaining:</span>
                                        <span class="fw-semibold text-danger-emphasis" style="font-size: 12px;">₹{{ number_format($gap, 0) }}</span>
                                    </div>
                                    @endif
                                </td>
                                <td style="width: 200px;">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="text-muted" style="font-size: 10px; letter-spacing: 0.5px; text-transform: uppercase;">Achieved</span>
                                        <span class="fw-bold {{ $target->achievement_percentage >= 100 ? 'text-success' : ($target->achievement_percentage >= 75 ? 'text-warning-emphasis' : 'text-primary') }}" style="font-size: 13px;">
                                            {{ number_format($target->achievement_percentage, 1) }}%
                                        </span>
                                    </div>
                                    <div class="progress shadow-sm bg-secondary bg-opacity-10" style="height: 10px; border-radius: 6px;" title="{{ number_format($target->achievement_percentage, 1) }}% of target achieved">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated
                                            {{ $target->achievement_percentage >= 100 ? 'bg-success' : ($target->achievement_percentage >= 75 ? 'bg-warning' : 'bg-primary') }}" 
                                             role="progressbar" 
                                             style="width: {{ min(100, $target->achievement_percentage) }}%; border-radius: 6px;" 
                                             aria-valuenow="{{ number_format($target->achievement_percentage, 1) }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 10px;">
                                        @if($target->achievement_percentage >= 100)
                                            <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Target Met!</span>
                                        @elseif($target->achievement_percentage >= 75)
                                            <span class="text-warning-emphasis fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Almost there</span>
                                        @else
                                            <span class="text-danger fw-semibold"><i class="bi bi-arrow-up-circle me-1"></i>Needs attention</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $statusClass = $target->status == 'active' ? 'bg-primary-subtle text-primary-emphasis border-primary-subtle' : 
                                                       ($target->status == 'achieved' ? 'bg-success-subtle text-success-emphasis border-success-subtle' : 'bg-danger-subtle text-danger-emphasis border-danger-subtle');
                                        $statusIcon = $target->status == 'active' ? 'bg-primary' : 
                                                       ($target->status == 'achieved' ? 'bg-success' : 'bg-danger');
                                    @endphp
                                    <span class="badge rounded-pill px-3 py-2 fw-medium border shadow-sm {{ $statusClass }}">
                                        <span class="d-inline-block rounded-circle me-1 {{ $statusIcon }}" style="width: 6px; height: 6px; vertical-align: middle;"></span>
                                        {{ ucfirst($target->status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border shadow-sm dropdown-toggle rounded-3 px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2">
                                            <li>
                                                <button type="button" class="dropdown-item px-3 py-2 fw-medium d-flex align-items-center" @click="openModal({{ \Illuminate\Support\Js::from([
                                                    'id' => $target->id,
                                                    'targetable_type' => class_basename($target->targetable_type),
                                                    'targetable_id' => $target->targetable_id,
                                                    'assignee_name' => optional($target->targetable)->name ?? optional($target->targetable)->email ?? 'Unknown',
                                                    'metric_type' => $target->metric_type,
                                                    'period_type' => $target->period_type,
                                                    'start_date' => $target->start_date->format('Y-m-d'),
                                                    'end_date' => $target->end_date->format('Y-m-d'),
                                                    'target_amount' => (float) $target->target_amount,
                                                    'achieved_amount' => (float) $target->achieved_amount,
                                                    'status' => $target->status,
                                                ]) }})">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <i class="bi bi-pencil" style="font-size: 12px;"></i>
                                                    </div>
                                                    Edit Target
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider opacity-50 my-1"></li>
                                            <li>
                                                <form action="{{ route('targets.destroy', $target) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this target?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item px-3 py-2 fw-medium text-danger d-flex align-items-center">
                                                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                            <i class="bi bi-trash" style="font-size: 12px;"></i>
                                                        </div>
                                                        Delete Target
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <i class="bi bi-award fs-1 d-block mb-3 text-opacity-25 text-secondary"></i>
                                        <h5 class="fw-bold mb-1">No Targets Found</h5>
                                        <p class="small mb-0">Try adjusting your filters or create a new target.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-4 bg-body-tertiary border-top">
                <div class="w-100 m-0">
                    {{ $targets->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal --}}
    <div class="modal fade" id="targetModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden bg-body">
                
                {{-- GLOSSY STYLE HEADER --}}
                <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi bi-magic fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-body"><span x-text="isEdit ? 'Edit Target Details' : 'Create New Target'"></span></h4>
                            <p class="mb-0 small text-muted">Configure performance metrics and assignment</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 p-md-5 pt-4">
                    <form method="POST" :action="isEdit ? '{{ url('targets') }}/' + form.id : '{{ route('targets.store') }}'">
                        @csrf
                        <input type="hidden" :name="isEdit ? '_method' : ''" value="PUT">

                        <div class="row g-4">
                            <div class="col-12">
                                
                                {{-- Card 1: Assignment --}}
                                <div class="card mb-4 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center gap-2 pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                <i class="bi bi-person-badge-fill fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bolder text-uppercase text-body" style="font-size: 11px; letter-spacing: 1.5px;">Assignment</h6>
                                        </div>
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Assignee Type *</label>
                                                <select name="targetable_type" x-model="form.targetable_type" @change="clearAssignees()" class="form-select form-select-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" required style="font-size: 14px;" :disabled="isEdit">
                                                    <option value="User">User</option>
                                                    <option value="Team">Team</option>
                                                    <option value="Department">Department</option>
                                                </select>
                                                <template x-if="isEdit">
                                                    <input type="hidden" name="targetable_type" :value="form.targetable_type">
                                                </template>
                                            </div>
                                            
                                            <div class="col-md-6" x-show="!isEdit">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Assignees (Multi-Select) *</label>
                                                <div class="card shadow-none border border-secondary border-opacity-25 rounded-3 overflow-hidden bg-body">
                                                    <div class="p-2 border-bottom bg-body-tertiary">
                                                        <input type="text" x-model="searchQuery" class="form-control form-control-sm border-secondary border-opacity-25 shadow-none" placeholder="Search assignees...">
                                                    </div>
                                                    <div class="p-2 border-bottom d-flex justify-content-between align-items-center" style="font-size: 11px;">
                                                        <div>
                                                            <a href="#" @click.prevent="selectAllFiltered()" class="text-decoration-none text-primary me-2">Select All</a>
                                                            <a href="#" @click.prevent="clearAssignees()" class="text-decoration-none text-muted">Clear</a>
                                                        </div>
                                                        <span class="text-muted"><span x-text="form.targetable_ids.length"></span> selected</span>
                                                    </div>
                                                    <div class="list-group list-group-flush" style="max-height: 140px; overflow-y: auto;">
                                                        <template x-for="assignee in filteredAssignees" :key="assignee.id">
                                                            <label class="list-group-item d-flex align-items-center gap-2 border-0 py-2 cursor-pointer" style="font-size: 13px;">
                                                                <input type="checkbox" name="targetable_ids[]" :value="assignee.id" class="form-check-input mt-0" :checked="form.targetable_ids.includes(assignee.id)" @change="toggleAssignee(assignee.id)">
                                                                <span x-text="assignee.name"></span>
                                                            </label>
                                                        </template>
                                                        <div x-show="filteredAssignees.length === 0" class="p-3 text-center text-muted small">No results found</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6" x-show="isEdit">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Assignee *</label>
                                                <input type="text" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" disabled :value="form.assignee_name">
                                                <input type="hidden" :name="isEdit ? 'targetable_ids[]' : ''" :value="form.targetable_id">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card 2: Configuration --}}
                                <div class="card mb-4 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center gap-2 pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                <i class="bi bi-sliders fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bolder text-uppercase text-body" style="font-size: 11px; letter-spacing: 1.5px;">Metric & Target</h6>
                                        </div>
                                        <div class="row g-4">
                                            <div class="col-md-4">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Metric Type *</label>
                                                <select name="metric_type" x-model="form.metric_type" class="form-select form-select-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" required style="font-size: 14px;" :disabled="isEdit">
                                                    <option value="sales_revenue">Sales Revenue</option>
                                                    <option value="orders_count">Orders Count</option>
                                                    <option value="invoice_collection">Invoice Collection</option>
                                                    <option value="payment_collection">Payment Collection</option>
                                                    <option value="calls_made">Calls Made</option>
                                                </select>
                                                <template x-if="isEdit">
                                                    <!-- Send value when disabled -->
                                                    <input type="hidden" name="metric_type" :value="form.metric_type">
                                                </template>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Target Amount *</label>
                                                <div class="input-group input-group-lg bg-body border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                                                    <span class="input-group-text border-0 bg-transparent text-muted fw-bold">₹</span>
                                                    <input type="number" step="0.01" name="target_amount" x-model="form.target_amount" class="form-control fw-semibold border-0 bg-transparent shadow-none px-2" required placeholder="50000" style="font-size: 14px;">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Achieved Amount</label>
                                                <div class="input-group input-group-lg bg-body border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                                                    <span class="input-group-text border-0 bg-transparent text-muted fw-bold">₹</span>
                                                    <input type="number" step="0.01" name="achieved_amount" x-model="form.achieved_amount" class="form-control fw-semibold border-0 bg-transparent shadow-none px-2" placeholder="0" style="font-size: 14px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card 3: Timeline & Status --}}
                                <div class="card mb-4 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center gap-2 pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                <i class="bi bi-calendar-range fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bolder text-uppercase text-body" style="font-size: 11px; letter-spacing: 1.5px;">Timeline & Status</h6>
                                        </div>
                                        <div class="row g-4">
                                            <div class="col-md-3">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Period Type *</label>
                                                <select name="period_type" x-model="form.period_type" class="form-select form-select-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" required style="font-size: 14px;" :disabled="isEdit">
                                                    <option value="daily">Daily</option>
                                                    <option value="monthly">Monthly</option>
                                                    <option value="yearly">Yearly</option>
                                                </select>
                                                <template x-if="isEdit">
                                                    <input type="hidden" name="period_type" :value="form.period_type">
                                                </template>
                                            </div>
                                            
                                            <div class="col-md-3" x-show="form.period_type === 'monthly' && !isEdit" style="display: none;">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Month *</label>
                                                <select name="target_month" x-model="form.target_month" class="form-select form-select-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" :required="form.period_type === 'monthly' && !isEdit" style="font-size: 14px;" :disabled="form.period_type !== 'monthly' || isEdit">
                                                    @foreach(range(1, 12) as $m)
                                                        <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3" x-show="(form.period_type === 'monthly' || form.period_type === 'yearly') && !isEdit" style="display: none;">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Year *</label>
                                                <select name="target_year" x-model="form.target_year" class="form-select form-select-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" :required="(form.period_type === 'monthly' || form.period_type === 'yearly') && !isEdit" style="font-size: 14px;" :disabled="(form.period_type !== 'monthly' && form.period_type !== 'yearly') || isEdit">
                                                    @foreach(range(date('Y') - 1, date('Y') + 3) as $y)
                                                        <option value="{{ $y }}">{{ $y }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3" x-show="form.period_type === 'daily' || isEdit">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Start Date *</label>
                                                <input type="date" name="start_date" x-model="form.start_date" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" :required="form.period_type === 'daily' || isEdit" style="font-size: 14px;" :disabled="isEdit">
                                                <template x-if="isEdit"><input type="hidden" name="start_date" :value="form.start_date"></template>
                                            </div>
                                            
                                            <div class="col-md-3" x-show="form.period_type === 'daily' || isEdit">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">End Date *</label>
                                                <input type="date" name="end_date" x-model="form.end_date" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" :required="form.period_type === 'daily' || isEdit" style="font-size: 14px;" :disabled="isEdit">
                                                <template x-if="isEdit"><input type="hidden" name="end_date" :value="form.end_date"></template>
                                            </div>
                                            <div class="col-md-3" x-show="isEdit">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Status</label>
                                                <select name="status" x-model="form.status" class="form-select form-select-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" style="font-size: 14px;">
                                                    <option value="active">Active</option>
                                                    <option value="achieved">Achieved</option>
                                                    <option value="failed">Failed</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-lg btn-light border-secondary border-opacity-25 fw-semibold px-4 rounded-3 shadow-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-lg btn-primary fw-semibold px-5 rounded-3 shadow-sm">
                                <i class="bi bi-save me-2"></i><span x-text="isEdit ? 'Save Changes' : 'Create Target'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden bg-body">
                
                <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi bi-cloud-upload fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-body">Bulk Import</h4>
                            <p class="mb-0 small text-muted">Upload an Excel or CSV file</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 p-md-5 pt-4">
                    <div class="mb-4">
                        <a href="{{ route('targets.import.template') }}" class="btn btn-outline-info w-100 fw-semibold rounded-3 shadow-sm">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Download Sample Template
                        </a>
                    </div>
                    
                    <form action="{{ route('targets.import.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary mb-4">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-2 pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                        <i class="bi bi-file-earmark-excel-fill fs-6"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bolder text-uppercase text-body" style="font-size: 11px; letter-spacing: 1.5px;">File Upload</h6>
                                </div>
                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Select File (XLSX, CSV) *</label>
                                <input type="file" name="file" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required style="font-size: 14px;">
                                <small class="text-muted d-block mt-3" style="font-size: 11px;">
                                    Headers must include: <code>targetable_type</code>, <code>targetable_identifier</code>, <code>metric_type</code>, <code>period_type</code>, <code>start_date</code>, <code>end_date</code>, <code>target_amount</code>, <code>achieved_amount</code>
                                </small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-lg btn-light border-secondary border-opacity-25 fw-semibold px-4 rounded-3 shadow-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-lg btn-primary fw-semibold px-5 rounded-3 shadow-sm">
                                <i class="bi bi-upload me-2"></i>Process Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    var toastList = toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl, {
            autohide: true
        });
    });
    toastList.forEach(toast => toast.show());
});

function targetsModule() {
    return {
        init() {
            document.addEventListener('click', e => {
                let link = e.target.closest('#targetsTableBody .pagination a');
                if (link) {
                    e.preventDefault();
                    this.fetchUrl(link.href);
                }
            });
        },
        async fetchUrl(url) {
            try {
                let response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                let html = await response.text();
                let doc = new DOMParser().parseFromString(html, 'text/html');
                
                let newTable = doc.querySelector('#targetsTableBody');
                let oldTable = document.querySelector('#targetsTableBody');
                
                if (newTable && oldTable) {
                    oldTable.innerHTML = newTable.innerHTML;
                    window.history.pushState({}, '', url);
                } else {
                    window.location.href = url;
                }
            } catch (e) {
                window.location.href = url;
            }
        },
        async filterTable(form) {
            let formData = new FormData(form);
            let params = new URLSearchParams();
            for (let pair of formData.entries()) {
                params.append(pair[0], pair[1]);
            }
            let url = form.action + '?' + params.toString();
            this.fetchUrl(url);
        },
        availableAssignees: @json($availableAssignees),
        searchQuery: '',
        isEdit: false,
        form: {
            id: null,
            targetable_type: 'User',
            targetable_ids: [],
            targetable_id: '',
            metric_type: 'sales_revenue',
            target_amount: '',
            achieved_amount: 0,
            status: 'active',
            period_type: 'monthly',
            target_month: new Date().getMonth() + 1,
            target_year: new Date().getFullYear(),
            start_date: '',
            end_date: ''
        },
        get filteredAssignees() {
            let list = this.availableAssignees[this.form.targetable_type] || [];
            if (this.searchQuery) {
                return list.filter(a => a.name && a.name.toLowerCase().includes(this.searchQuery.toLowerCase()));
            }
            return list;
        },
        toggleAssignee(id) {
            id = Number(id);
            if (this.form.targetable_ids.includes(id)) {
                this.form.targetable_ids = this.form.targetable_ids.filter(i => i !== id);
            } else {
                this.form.targetable_ids.push(id);
            }
        },
        selectAllFiltered() {
            this.filteredAssignees.forEach(a => {
                if (!this.form.targetable_ids.includes(Number(a.id))) {
                    this.form.targetable_ids.push(Number(a.id));
                }
            });
        },
        clearAssignees() {
            this.form.targetable_ids = [];
        },
        openModal(target = null) {
            if (target) {
                this.isEdit = true;
                // Spread target data but always ensure targetable_ids is an array
                // (edit payload doesn't include it — so it must be explicitly set)
                this.form = {
                    targetable_ids: [],
                    target_month: new Date().getMonth() + 1,
                    target_year: new Date().getFullYear(),
                    ...target
                };
            } else {
                this.isEdit = false;
                this.searchQuery = '';
                this.form = {
                    id: null,
                    targetable_type: 'User',
                    targetable_ids: [],
                    targetable_id: '',
                    metric_type: 'sales_revenue',
                    target_amount: '',
                    achieved_amount: 0,
                    status: 'active',
                    period_type: 'monthly',
                    target_month: new Date().getMonth() + 1,
                    target_year: new Date().getFullYear(),
                    start_date: '',
                    end_date: ''
                };
            }
            new bootstrap.Modal(document.getElementById('targetModal')).show();
        }
    }
}
</script>
@endsection
