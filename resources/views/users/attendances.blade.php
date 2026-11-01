@extends('layouts.app')

@section('title', 'Attendances')
@section('page', 'attendances')

@push('head')
<style>
.attendance-workspace .card { border-left-width: 1px !important; }
.attendance-workspace-nav { flex-wrap: nowrap; overflow-x: auto; }
.attendance-workspace-nav .btn { white-space: nowrap; flex-shrink: 0; }
.attendance-workspace .calendar-page { min-height: 0 !important; }
.attendance-workspace .calendar-container { overflow-x: auto; }
.attendance-workspace .calendar-content { min-width: 650px; }
.attendance-workspace .card-header > .d-flex { flex-wrap: wrap; }
@media (max-width: 767px) {
    .attendance-workspace-nav { flex-wrap: nowrap !important; overflow-x: auto; }
    .attendance-workspace .card-header { align-items: stretch !important; }
    .attendance-workspace .card-header > .d-flex { width: 100%; }
}
</style>
@endpush

@section('content')
@php
    $isGlobalHr = auth()->user()->hasRole(['Super Admin', 'Admin']) || auth()->user()->can('view-all-data');
@endphp
<div class="attendance-workspace" x-data="{ workspaceSection: 'attendance' }">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div><span class="text-primary small fw-semibold">TIME &amp; LEAVE</span><h1 class="h3 mt-1 mb-2">Attendance workspace</h1><p class="text-muted mb-0">Review daily activity, manage rosters, and track requests in one place.</p></div>
        <a href="{{ url('/admin/leaves') }}" class="btn btn-outline-secondary">Leave management</a>
    </div>
    <nav class="attendance-workspace-nav d-flex gap-2 mb-4 p-2 border rounded-3 bg-body" aria-label="Attendance workspace sections">
        <button type="button" class="btn" :class="workspaceSection === 'attendance' ? 'btn-primary' : 'btn-light'" :aria-current="workspaceSection === 'attendance' ? 'page' : null" @click="workspaceSection = 'attendance'">Attendance</button>
        <button type="button" class="btn" :class="workspaceSection === 'corrections' ? 'btn-primary' : 'btn-light'" :aria-current="workspaceSection === 'corrections' ? 'page' : null" @click="workspaceSection = 'corrections'">Corrections</button>
        <button type="button" class="btn" :class="workspaceSection === 'co' ? 'btn-primary' : 'btn-light'" :aria-current="workspaceSection === 'co' ? 'page' : null" @click="workspaceSection = 'co'">Compensatory off</button>
        @can('attendance-roster-import')<button type="button" class="btn" :class="workspaceSection === 'roster' ? 'btn-primary' : 'btn-light'" :aria-current="workspaceSection === 'roster' ? 'page' : null" @click="workspaceSection = 'roster'">Roster import</button>@endcan
        @can('leave-view')<button type="button" class="btn" :class="workspaceSection === 'balances' ? 'btn-primary' : 'btn-light'" :aria-current="workspaceSection === 'balances' ? 'page' : null" @click="workspaceSection = 'balances'">My leave balances</button>@endcan
    </nav>
<div class="user-management" x-show="workspaceSection === 'attendance'" x-data="attendancesTable({{ auth()->id() }}, { global: @js($isGlobalHr), edit: @js(auth()->user()->can('attendance-edit')), create: @js(auth()->user()->can('attendance-create')), leaveEdit: @js(auth()->user()->can('leave-edit')), holidays: @js(auth()->user()->can('leave-view')), timeZone: @js(config('app.timezone')) })">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="h5 mb-1">Daily attendance</h2>
            <p class="text-muted mb-0">Track employee check-ins, check-outs, and daily presence</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div class="btn-group me-2 shadow-sm">
                <button type="button" class="btn btn-outline-secondary" aria-label="Attendance list view" :class="{ 'active': currentView === 'list' }" @click="switchView('list')">
                    <i class="bi bi-list-ul"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" aria-label="Attendance calendar view" :class="{ 'active': currentView === 'calendar' }" @click="switchView('calendar')">
                    <i class="bi bi-calendar3"></i>
                </button>
            </div>
            @can('attendance-export')
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle shadow-sm" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download me-2"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                    <li><button class="dropdown-item" @click="exportFiltered()">Current filtered results (CSV)</button></li>
                    <li><button class="dropdown-item" @click="exportSummary()"><i class="bi bi-grid-3x3 me-2"></i>Summary Grid — displayed month (CSV)</button></li>
                    <li><button class="dropdown-item" @click="exportDetailed()"><i class="bi bi-list-task me-2"></i>Detailed Log — displayed month (CSV)</button></li>
                </ul>
            </div>
            @endcan
            @canany(['attendance-create', 'leave-create'])
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle shadow-sm" type="button" id="requestDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-plus-circle me-2"></i>New Request
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="requestDropdown">
                    @can('attendance-create')
                    <li>
                        <button class="dropdown-item" @click="openCreate()">
                            <i class="bi bi-calendar-check-fill text-primary me-2"></i>Log Attendance
                        </button>
                    </li>
                    @endcan
                    @can('leave-create')
                    <li>
                        <button class="dropdown-item" @click="openLeaveCreate()">
                            <i class="bi bi-calendar-minus-fill text-warning me-2"></i>Request Leave
                        </button>
                    </li>
                    @endcan
                </ul>
            </div>
            @endcanany
        </div>
    </div>

    <div class="alert alert-danger" x-show="error" x-text="error" x-cloak role="alert"></div>
    @include('users.partials.attendance-day')
    <div class="card mb-4" x-show="selectedEmployee" x-cloak><div class="card-body d-flex justify-content-between gap-3 flex-wrap">
        <div><h2 class="h6 mb-1" x-text="selectedEmployee?.name"></h2><span class="small text-muted" x-text="[selectedEmployee?.employee_id, selectedEmployee?.designation, selectedEmployee?.department?.name].filter(Boolean).join(' · ')"></span>
            <div class="small mt-1">Manager: <span x-text="selectedEmployee?.manager?.name || 'Unassigned'"></span> · LOB: <span x-text="selectedEmployee?.lob_team_code || 'Global'"></span> · Joined: <span x-text="employeeJoined"></span></div>
        </div>@can('user-view')<a href="{{ url('/admin/users') }}" class="btn btn-outline-secondary btn-sm align-self-start">Manage employees and reporting</a>@endcan
    </div></div>
    <div class="row g-3 mb-4" aria-live="polite">
        <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><span class="small text-muted">Completed session time</span><strong class="d-block fs-5" x-text="completedTime"></strong></div></div>
        <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><span class="small text-muted">Recorded employee-days</span><strong class="d-block fs-5" x-text="insights.recorded_days"></strong></div></div>
        <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><span class="small text-muted">Open sessions</span><strong class="d-block fs-5" x-text="insights.open_sessions"></strong></div></div>
        <div class="col-6 col-lg-3"><div class="border rounded p-3 h-100"><span class="small text-muted">Check-out without check-in</span><strong class="d-block fs-5" x-text="insights.incomplete_sessions"></strong></div></div>
        <div class="col-12 small text-muted">These totals cover all matching records, including records beyond the current list page. Open sessions are excluded from completed time.</div>
    </div>
    <div class="d-flex flex-wrap gap-3 small mb-4 px-1" aria-label="Loaded attendance status counts">
        <span>Loaded records <strong x-text="stats.total"></strong></span><span class="text-success">Present <strong x-text="stats.present"></strong></span><span class="text-danger">Absent <strong x-text="stats.absent"></strong></span><span class="text-warning-emphasis">Late / Half-Day <strong x-text="stats.late + stats.halfDay"></strong></span>
        <span class="text-muted">Status counts cover loaded employee-days.</span>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-body d-flex flex-column flex-md-row justify-content-between align-items-center p-3 p-lg-4 border-bottom-0">
            <h2 class="h5 card-title mb-3 mb-md-0 d-flex align-items-center">
                <i class="bi bi-list-ul text-primary me-2"></i><span x-text="currentView === 'calendar' ? 'Attendance calendar' : 'Attendance records'"></span>
            </h2>
            <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto align-items-md-center">

                <!-- Month Nav (Calendar View Only) -->
                <div class="btn-group input-group-sm me-2" x-show="currentView === 'calendar'">
                    <button class="btn btn-outline-secondary" @click="previousMonth()"><i class="bi bi-chevron-left"></i></button>
                    <button class="btn btn-outline-secondary fw-bold" style="width: 150px;" x-text="currentMonthYear" @click="goToToday()"></button>
                    <button class="btn btn-outline-secondary" @click="nextMonth()"><i class="bi bi-chevron-right"></i></button>
                </div>

                <div class="input-group input-group-sm" style="max-width: 250px;" x-show="currentView === 'list'">
                    <span class="input-group-text bg-body-tertiary border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Search by name/ID..." aria-label="Search attendance by employee name or ID"
                           x-model="searchQuery" @keyup.debounce.300ms="filterItems()">
                </div>

                <input type="date" class="form-control form-control-sm" aria-label="Attendance date filter" x-model="dateFilter" @change="filterItems()" style="max-width: 150px;" x-show="currentView === 'list'">

                <x-searchable-select model="statusFilter" label="Attendance status filter" small width="150px">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" x-model="statusFilter" @change="filterItems()">
                    <option value="">All Statuses</option>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                    <option value="Late">Late</option>
                    <option value="Half-Day">Half-Day</option>
                </select>
</x-searchable-select>

                <x-searchable-select model="userFilter" label="Attendance employee filter" small width="200px">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" x-model="userFilter" @change="filterItems()" x-show="usersList.length > 0" aria-label="Attendance employee">
                    <option value="" disabled selected>Select Employee</option>
                    <template x-for="u in usersList" :key="u.id">
                        <option :value="u.id" :selected="String(u.id) === String(userFilter)" x-text="u.name"></option>
                    </template>
                </select>
</x-searchable-select>

                @canany(['attendance-delete', 'attendance-permanent-delete'])
                <div class="dropdown" x-show="currentView === 'list'">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" :disabled="selectedItems.length === 0">
                        Bulk Actions <span class="badge bg-secondary ms-1" x-text="selectedItems.length" x-show="selectedItems.length > 0"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        @can('attendance-delete')
                        <li><a class="dropdown-item text-danger" href="#" @click.prevent="bulkAction('delete')">
                            <i class="bi bi-trash me-2"></i>Delete Selected
                        </a></li>
                        @endcan
                        @can('attendance-permanent-delete')
                        <li><a class="dropdown-item text-danger" href="#" @click.prevent="bulkAction('force-delete')">
                            <i class="bi bi-trash-fill me-2"></i>Force Delete Selected
                        </a></li>
                        @endcan
                    </ul>
                </div>
                @endcanany

                <button class="btn btn-sm btn-outline-secondary" type="button" @click="loadItems()" :disabled="isLoading" title="Refresh">
                    <i class="bi bi-arrow-clockwise" :class="isLoading ? 'spin' : ''"></i>
                </button>
            </div>
        </div>

        <div class="card-body border-bottom" x-show="currentView === 'list'">
            <div class="d-flex flex-wrap gap-3 align-items-end">
                <div><label class="form-label small" for="attendanceFrom">From date</label><input id="attendanceFrom" type="date" class="form-control form-control-sm" x-model="rangeStart" @change="dateFilter = ''; filterItems()"></div>
                <div><label class="form-label small" for="attendanceThrough">Through date</label><input id="attendanceThrough" type="date" class="form-control form-control-sm" x-model="rangeEnd" :min="rangeStart" @change="dateFilter = ''; filterItems()"></div>
                <button type="button" class="btn btn-outline-primary btn-sm" @click="useMonthRange()">Use displayed month</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="resetFilters()">Reset attendance filters</button>
            </div>
        </div>
        <p class="small text-muted px-3 pt-3 mb-0" x-show="currentView === 'calendar'">Select a day number to inspect all sessions. Holiday markers are informational.</p>
        <p class="small text-warning px-3" x-show="holidayError" x-text="holidayError"></p>
        <div class="card-body p-0">
            <!-- List View -->
            <div class="table-responsive" x-show="currentView === 'list'">
                <table class="table table-hover align-middle mb-0 custom-table">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 40px;" class="ps-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           :checked="items.length > 0 && selectedItems.length === items.length"
                                           @change="toggleAll($event.target.checked)">
                                </div>
                            </th>
                            <th scope="col" class="sortable" @click="sortBy('date')" role="button" tabindex="0" @keydown.enter.prevent="sortBy('date')" @keydown.space.prevent="sortBy('date')">
                                Date <i class="bi" :class="sortField === 'date' ? (sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted'"></i>
                            </th>
                            <th scope="col" class="sortable" @click="sortBy('user')" role="button" tabindex="0" @keydown.enter.prevent="sortBy('user')" @keydown.space.prevent="sortBy('user')">
                                Employee <i class="bi" :class="sortField === 'user' ? (sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted'"></i>
                            </th>
                            <th scope="col" class="sortable" @click="sortBy('check_in')" role="button" tabindex="0" @keydown.enter.prevent="sortBy('check_in')" @keydown.space.prevent="sortBy('check_in')">
                                Check In <i class="bi" :class="sortField === 'check_in' ? (sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted'"></i>
                            </th>
                            <th scope="col" class="sortable" @click="sortBy('check_out')" role="button" tabindex="0" @keydown.enter.prevent="sortBy('check_out')" @keydown.space.prevent="sortBy('check_out')">
                                Check Out <i class="bi" :class="sortField === 'check_out' ? (sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted'"></i>
                            </th>
                            <th scope="col">
                                Total Time
                            </th>
                            <th scope="col" class="sortable" @click="sortBy('status')" role="button" tabindex="0" @keydown.enter.prevent="sortBy('status')" @keydown.space.prevent="sortBy('status')">
                                Status <i class="bi" :class="sortField === 'status' ? (sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted'"></i>
                            </th>
                            <th scope="col" class="text-end pe-4" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mt-2 mb-0">Loading attendances...</p>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!isLoading && items.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="empty-state-icon bg-body-tertiary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                                        <i class="bi bi-calendar-x fs-2 text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-1">No attendance records found</h5>
                                    <p class="text-muted small mb-0">Try adjusting your filters or search query.</p>
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr :class="selectedItems.includes(String(item.id)) ? 'table-primary' : ''">
                                <td class="ps-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               :checked="selectedItems.includes(String(item.id))"
                                               @change="toggleItem(item.id)">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center me-3" style="width: 36px; height: 36px;">
                                            <span class="fw-bold" x-text="new Date(item.date + 'T00:00:00').getDate()"></span>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-medium" x-text="new Date(item.date + 'T00:00:00').toLocaleDateString()"></p>
                                            <small class="text-muted" x-text="new Date(item.date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long' })"></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="mb-0 fw-medium" x-text="item.user ? item.user.name : 'Unknown User'"></p>
                                    <small class="text-muted" x-text="item.user ? item.user.employee_id : ''"></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-box-arrow-in-right text-success me-2"></i>
                                        <span x-text="item.check_in ? item.check_in.substring(0, 5) : '—'"></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-box-arrow-left text-danger me-2"></i>
                                        <span x-text="item.check_out ? item.check_out.substring(0, 5) : '—'"></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center text-muted">
                                        <i class="bi bi-clock-history me-2"></i>
                                        <span x-text="item.total_time ? item.total_time : '—'"></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill"
                                          :class="{
                                              'bg-success-subtle text-success': item.status === 'Present',
                                              'bg-danger-subtle text-danger': item.status === 'Absent',
                                              'bg-warning-subtle text-warning': item.status === 'Late',
                                              'bg-info-subtle text-info': item.status === 'Half-Day'
                                          }"
                                          x-text="item.status"></span>
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-link btn-sm p-0 mb-1" x-show="String(item.user_id) === '{{ auth()->id() }}'" @click="requestCorrection(item)">Request correction</button>
                                    @canany(['attendance-edit', 'attendance-delete'])
                                    <div class="dropdown">
                                        <button x-show="@js($isGlobalHr) || String(item.user_id) === '{{ auth()->id() }}'" class="btn btn-sm btn-outline-secondary btn-icon rounded-circle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            @can('attendance-edit')
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="editItem(item)">
                                                    <i class="bi bi-pencil me-2 text-muted"></i>Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            @endcan
                                            @can('attendance-delete')
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item)">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
                                    @endcanany
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-body border-top p-3 p-lg-4" x-show="currentView === 'list' && totalItems > 0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <p class="text-muted small mb-0">
                        Showing <span class="fw-medium text-body" x-text="pageFrom"></span> to
                        <span class="fw-medium text-body" x-text="pageTo"></span> of
                        <span class="fw-medium text-body" x-text="totalItems"></span> entries
                    </p>
                    <nav aria-label="Table navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="currentPage === 1 ? 'disabled' : ''">
                                <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                            </li>
                            <template x-for="p in visiblePages" :key="p">
                                <li class="page-item" :class="p === currentPage ? 'active' : (p === '...' ? 'disabled' : '')">
                                    <a class="page-link" href="#" @click.prevent="p !== '...' ? goToPage(p) : null" x-text="p"></a>
                                </li>
                            </template>
                            <li class="page-item" :class="currentPage === totalPages ? 'disabled' : ''">
                                <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- Calendar View -->
            <div class="calendar-page pb-0" x-show="currentView === 'calendar'" style="display: none; height: auto;">
                <div class="calendar-container m-0 border-0 shadow-none rounded-0">
                    <div class="calendar-layout bg-transparent">
                        <div class="calendar-main bg-transparent">
                            <div class="calendar-content p-0">
                                <div class="month-view border-0 shadow-none">
                    <div class="month-header">
                        <div class="month-header-day">Sunday</div>
                        <div class="month-header-day">Monday</div>
                        <div class="month-header-day">Tuesday</div>
                        <div class="month-header-day">Wednesday</div>
                        <div class="month-header-day">Thursday</div>
                        <div class="month-header-day">Friday</div>
                        <div class="month-header-day">Saturday</div>
                    </div>

                    <div class="month-grid" style="grid-template-rows: repeat(6, minmax(140px, 1fr));">
                        <template x-for="day in calendarDays" :key="day.date">
                            <div class="month-day"
                                 :class="{
                                     'today': day.isToday,
                                     'other-month': day.isOtherMonth,
                                     'has-events': day.events && day.events.length > 0
                                 }"
                                 @dblclick="addEventForDay(day)">
                                <button type="button" class="day-number border-0 bg-transparent" @click.stop="inspectDay(day)" :aria-label="'View attendance details for ' + day.date" x-text="day.day"></button><template x-for="roster in day.rosters || []" :key="roster.id"><span class="badge d-block my-1 text-wrap" :class="roster.day_type === 'Week Off' ? 'bg-info-subtle text-info-emphasis' : 'bg-secondary-subtle text-secondary-emphasis'" x-text="roster.day_type + (!userFilter ? ' · Employee #' + roster.user_id : '')"></span></template><template x-for="holiday in day.holidays || []" :key="holiday.id"><span class="badge bg-primary-subtle text-primary small text-wrap d-block my-1" :title="holiday.name" x-text="holiday.name"></span></template>
                                <div class="day-events" style="height: 100%; overflow: visible;">
                                    <template x-for="event in day.events" :key="event.id">
                                        <div class="day-event mb-1 p-2 rounded shadow-sm"
                                             :class="event.type"
                                             @click.stop="editItem(event.raw)" role="button" tabindex="0" @keydown.enter.stop.prevent="editItem(event.raw)" @keydown.space.stop.prevent="editItem(event.raw)" :aria-label="event.status + ' on ' + day.date"
                                             style="cursor: pointer; white-space: normal !important; overflow: visible !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold" style="font-size: 0.8rem;" x-text="event.status"></span><span class="small ms-1" x-show="event.userName" x-text="event.userName"></span>
                                            </div>
                                            <div style="font-size: 0.75rem; line-height: 1.2;">
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-nowrap" title="Check In"><i class="bi bi-box-arrow-in-right me-1 opacity-75"></i><span x-text="event.checkIn"></span></span>
                                                    <span class="text-nowrap" title="Check Out" x-show="event.checkOut !== null"><i class="bi bi-box-arrow-left me-1 opacity-75"></i><span x-text="event.checkOut"></span></span>
                                                </div>
                                                <div class="mt-1 pt-1 border-top border-secondary border-opacity-25 fw-medium opacity-75" x-show="event.totalTime !== null">
                                                    <i class="bi bi-clock me-1"></i><span x-text="event.totalTime"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div x-show="workspaceSection === 'corrections'" x-cloak>
    <div class="mb-3"><h2 class="h5">Attendance corrections</h2><p class="text-muted small">Submit corrections to your own records or review requests assigned to you.</p></div>
    @include('users.partials.attendance-corrections')
</div>
<div x-show="workspaceSection === 'co' || workspaceSection === 'roster'" x-cloak>
    @include('users.partials.attendance-roster')
</div>
@can('leave-view')
<div x-show="workspaceSection === 'balances'" x-cloak>
    <!-- My Leave Balances Widget -->
    @php
        $userLeaveBalances = \App\Modules\Users\Models\LeaveBalance::where('user_id', auth()->id())->where('is_active', true)->get();
    @endphp
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-body p-3 p-lg-4 border-bottom-0">
            <h2 class="h5 card-title mb-0 d-flex align-items-center">
                <i class="bi bi-wallet2 text-primary me-2"></i>My Leave Balances
            </h2>
        </div>
        <div class="card-body p-3 p-lg-4 pt-0">
            @if($userLeaveBalances->count() > 0)
            <div class="row g-3">
                @foreach($userLeaveBalances as $balance)
                <div class="col-sm-6 col-md-4 col-xl-auto" style="min-width: 200px;">
                    <div class="d-flex align-items-center p-3 border rounded-3 bg-body-tertiary shadow-sm">
                        <div class="fs-2 text-primary me-3 fw-bold lh-1">
                            {{ rtrim(rtrim(number_format($balance->balance, 1), '0'), '.') }}
                        </div>
                        <div>
                            <div class="fw-bold">{{ $balance->leave_type }}</div>
                            <small class="text-muted">Available</small>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-4 bg-body-tertiary rounded border border-dashed">
                <p class="text-muted mb-0">No active leave balances found.</p>
            </div>
            @endif
        </div>
    </div>

</div>
@endcan
</div>


<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" x-data="attendanceForm({{ auth()->id() }})">
        <form class="modal-content shadow" @submit.prevent="saveItem()">
            <div class="modal-header bg-body-tertiary">
                <h5 class="modal-title d-flex align-items-center" id="attendanceModalLabel">
                    <i class="bi bi-calendar-check-fill text-primary me-2"></i><span x-text="editingId ? 'Edit attendance' : 'Log attendance'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-4">Record the attendance date, session times, and status. Fields marked * are required.</p>
                <div class="alert alert-danger" role="alert" x-show="error" x-text="error" style="display: none;"></div>

                <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="attUser" class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                    <x-searchable-select model="form.user_id" label="Attendance employee">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="attUser" x-model="form.user_id" required @disabled(!$isGlobalHr)>
                        <option value="">Select Employee</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" :selected="String(user.id) === String(form.user_id)" x-text="user.name + (user.employee_id ? ` (${user.employee_id})` : '')"></option>
                        </template>
                    </select>
</x-searchable-select>
                </div>

                <div class="col-md-6">
                    <label for="attDate" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="attDate" x-model="form.date" required>
                </div>

                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="attCheckIn" class="form-label fw-semibold">Check In Time</label>
                        <input type="datetime-local" class="form-control" id="attCheckIn" x-model="form.check_in_time">
                    </div>
                    <div class="col-md-6">
                        <label for="attCheckOut" class="form-label fw-semibold">Check Out Time</label>
                        <input type="datetime-local" class="form-control" id="attCheckOut" x-model="form.check_out_time">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="attStatus" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <x-searchable-select model="form.status" label="Attendance status">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="attStatus" x-model="form.status" required>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                        <option value="Half-Day">Half-Day</option>
                    </select>
</x-searchable-select>
                </div>

                <div class="mb-0">
                    <label for="attNotes" class="form-label fw-semibold">Notes</label>
                    <textarea class="form-control" id="attNotes" x-model="form.notes" style="height: 100px;" placeholder="Add any optional notes here..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                    <span x-show="saving" class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
                    <span x-text="editingId ? 'Save Changes' : 'Log Attendance'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" x-data="leaveForm({{ auth()->id() }})">
        <form class="modal-content" @submit.prevent="saveItem()">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="leaveModalLabel">
                    <i class="bi bi-calendar-minus-fill text-primary me-2"></i>
                    <span x-text="editingId ? 'Edit Leave Request' : 'Request Leave'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-4">Choose the employee and leave type, then enter the requested dates and reason.</p>
                <div class="alert alert-danger" role="alert" x-show="error" x-text="error" style="display: none;"></div>

                <div class="row g-3 mb-4">
                <div class="col-md-6">
<label class="form-label" for="leaveUser">Employee <span class="text-danger">*</span></label>
<x-searchable-select model="form.user_id" label="Leave employee">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="leaveUser" x-model="form.user_id" required @disabled(!$isGlobalHr)>
                        <option value="">Select Employee</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" :selected="String(user.id) === String(form.user_id)" x-text="user.name"></option>
                        </template>
                    </select>
</x-searchable-select>
</div>

                <div class="col-md-6">
<label class="form-label" for="leaveType">Leave Type <span class="text-danger">*</span></label>
<x-searchable-select model="form.leave_type" label="Leave type">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="leaveType" x-model="form.leave_type" required :disabled="isLoadingBalances || userBalances.length === 0">
                        <option value="">Select Leave Type</option>
                        <template x-for="balance in userBalances" :key="balance.id">
                            <option :value="balance.leave_type"
                                    x-text="`${balance.leave_type} Leave (Balance: ${balance.balance})`"
                                    :disabled="balance.balance <= 0 && balance.leave_type !== originalLeaveType"></option>
                        </template>
                    </select>
</x-searchable-select>
                    <div class="form-text text-warning" x-show="form.user_id && userBalances.length === 0 && !isLoadingBalances">
                        No active leave balances found for this employee.
                    </div>
                </div>

                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div>
                            <label class="form-label fw-semibold" for="leaveStart">Start date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="leaveStart" x-model="form.start_date" required>

</div>
                    </div>
                    <div class="col-md-6">
                        <div>
                            <label class="form-label fw-semibold" for="leaveEnd">End date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="leaveEnd" x-model="form.end_date" :min="form.start_date" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="leaveReason">Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="leaveReason" x-model="form.reason" rows="3" placeholder="Explain the reason for your leave request" required></textarea>
                </div>

                @if($isGlobalHr && auth()->user()->can('leave-approve'))
                <div class="mb-3" x-show="editingId" x-cloak>
<label class="form-label" for="leaveStatus">Status</label>
<x-searchable-select model="form.status" label="Leave status">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="leaveStatus" x-model="form.status">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
</x-searchable-select>
</div>
                @endif
            </div>
            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                    <span x-show="saving" class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
                    <span x-text="editingId ? 'Save Changes' : 'Submit Request'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
