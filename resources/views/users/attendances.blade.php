@extends('layouts.app')

@section('title', '🕒 Attendances')
@section('page', 'attendances')

@section('content')
<div class="user-management" x-data="attendancesTable">
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-calendar-check-fill text-primary me-2"></i>Attendances</h1>
            <p class="text-muted mb-0">Track employee check-ins, check-outs, and daily presence</p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group me-2 shadow-sm">
                <button type="button" class="btn btn-outline-secondary" :class="{ 'active': currentView === 'list' }" @click="switchView('list')">
                    <i class="bi bi-list-ul"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" :class="{ 'active': currentView === 'calendar' }" @click="switchView('calendar')">
                    <i class="bi bi-calendar3"></i>
                </button>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle shadow-sm" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download me-2"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                    <li><button class="dropdown-item" @click="exportSummary()"><i class="bi bi-grid-3x3 me-2"></i>Summary Grid (CSV)</button></li>
                    <li><button class="dropdown-item" @click="exportDetailed()"><i class="bi bi-list-task me-2"></i>Detailed Log (CSV)</button></li>
                </ul>
            </div>
            @can('attendance-create')
            <button type="button" class="btn btn-primary" @click="openCreate()">
                <i class="bi bi-plus-circle me-2"></i>Log Attendance
            </button>
            @endcan
        </div>
    </div>

    <!-- Stats Widgets -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Records</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Present</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.present"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Absent</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.absent"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Late / Half-Day</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.late + stats.halfDay"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-body d-flex flex-column flex-md-row justify-content-between align-items-center p-3 p-lg-4 border-bottom-0">
            <h2 class="h5 card-title mb-3 mb-md-0 d-flex align-items-center">
                <i class="bi bi-list-ul text-primary me-2"></i>Attendance List
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
                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Search by name/ID..."
                           x-model="searchQuery" @keyup.debounce.300ms="filterItems()">
                </div>
                
                <input type="date" class="form-select form-select-sm" x-model="dateFilter" @change="filterItems()" style="max-width: 150px;" x-show="currentView === 'list'">
                
                <select class="form-select form-select-sm" x-model="statusFilter" @change="filterItems()" style="max-width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                    <option value="Late">Late</option>
                    <option value="Half-Day">Half-Day</option>
                </select>

                <div class="dropdown" x-show="currentView === 'list'">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" :disabled="selectedItems.length === 0">
                        Bulk Actions <span class="badge bg-secondary ms-1" x-text="selectedItems.length" x-show="selectedItems.length > 0"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        @can('attendance-delete')
                        <li><a class="dropdown-item text-danger" href="#" @click.prevent="bulkAction('delete')">
                            <i class="bi bi-trash me-2"></i>Delete Selected
                        </a></li>
                        <li><a class="dropdown-item text-danger" href="#" @click.prevent="bulkAction('force-delete')">
                            <i class="bi bi-trash-fill me-2"></i>Force Delete Selected
                        </a></li>
                        @endcan
                    </ul>
                </div>
                
                <button class="btn btn-sm btn-outline-secondary" type="button" @click="loadItems()" :disabled="isLoading" title="Refresh">
                    <i class="bi bi-arrow-clockwise" :class="isLoading ? 'spin' : ''"></i>
                </button>
            </div>
        </div>

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
                            <th scope="col" class="sortable" @click="sortBy('date')">
                                Date <i class="bi" :class="sortField === 'date' ? (sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted'"></i>
                            </th>
                            <th scope="col" class="sortable" @click="sortBy('user')">
                                Employee <i class="bi" :class="sortField === 'user' ? (sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted'"></i>
                            </th>
                            <th scope="col" class="sortable" @click="sortBy('check_in')">
                                Check In <i class="bi" :class="sortField === 'check_in' ? (sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted'"></i>
                            </th>
                            <th scope="col" class="sortable" @click="sortBy('check_out')">
                                Check Out <i class="bi" :class="sortField === 'check_out' ? (sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted'"></i>
                            </th>
                            <th scope="col">
                                Total Time
                            </th>
                            <th scope="col" class="sortable" @click="sortBy('status')">
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
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light btn-icon rounded-circle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
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
            <div class="calendar-page pb-4" x-show="currentView === 'calendar'" style="display: none; min-height: 900px; height: auto;">
                <div class="calendar-container" style="padding: 0;">
                    <div class="calendar-layout" style="padding: 0; background: transparent;">
                        <div class="calendar-main" style="padding: 0; background: transparent;">
                            <div class="calendar-content">
                                <div class="month-view border-0 shadow-none">
                    <div class="month-header bg-light">
                        <div class="month-header-day text-muted fw-bold">Sun</div>
                        <div class="month-header-day text-muted fw-bold">Mon</div>
                        <div class="month-header-day text-muted fw-bold">Tue</div>
                        <div class="month-header-day text-muted fw-bold">Wed</div>
                        <div class="month-header-day text-muted fw-bold">Thu</div>
                        <div class="month-header-day text-muted fw-bold">Fri</div>
                        <div class="month-header-day text-muted fw-bold">Sat</div>
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
                                <div class="day-number" x-text="day.day"></div>
                                <div class="day-events" style="height: 100%; overflow: visible;">
                                    <template x-for="event in day.events" :key="event.id">
                                        <div class="day-event mb-1 p-2 rounded shadow-sm" 
                                             :class="event.type"
                                             @click.stop="editItem(event.raw)"
                                             style="cursor: pointer; white-space: normal !important; overflow: visible !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold" style="font-size: 0.8rem;" x-text="event.status"></span>
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

<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" x-data="attendanceForm">
        <form class="modal-content shadow" @submit.prevent="saveItem()">
            <div class="modal-header bg-body-tertiary">
                <h5 class="modal-title d-flex align-items-center" id="attendanceModalLabel">
                    <i class="bi bi-calendar-check-fill text-primary me-2"></i>Log Attendance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-danger" x-show="error" x-text="error" style="display: none;"></div>
                
                <div class="mb-3">
                    <label for="attUser" class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                    <select class="form-select" id="attUser" x-model="form.user_id" required>
                        <option value="">Select Employee</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" x-text="user.name + (user.employee_id ? ` (${user.employee_id})` : '')"></option>
                        </template>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="attDate" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="attDate" x-model="form.date" required>
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
                    <select class="form-select" id="attStatus" x-model="form.status" required>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                        <option value="Half-Day">Half-Day</option>
                    </select>
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
@endsection
