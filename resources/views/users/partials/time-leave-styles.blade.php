<style>

.hr-filter-panel { background: var(--bs-tertiary-bg); }
.attendance-workspace .card, .leave-workspace .card { border-radius: .75rem; overflow: visible; }
.attendance-workspace .card-header, .leave-workspace .card-header { padding: 1.25rem; background: var(--bs-body-bg); border-radius: .75rem .75rem 0 0; }
.attendance-workspace .form-label, .leave-workspace .form-label { margin-bottom: .4rem; font-weight: 600; }
.attendance-workspace-nav, .leave-workspace-nav { gap: .5rem; padding: .5rem; }
.attendance-workspace-nav .btn, .leave-workspace-nav .btn { border-radius: .5rem; }
.attendance-workspace-nav .btn-light, .leave-workspace-nav .btn-light { background: transparent !important; color: var(--bs-secondary-color) !important; border-color: transparent !important; }
.attendance-workspace .hr-summary .border { background: var(--bs-body-bg); border-radius: .75rem !important; }
.attendance-workspace .table th, .leave-workspace .table th { font-size: .75rem; letter-spacing: .025em; white-space: nowrap; padding-top: 1rem; padding-bottom: 1rem; }
.attendance-workspace .table td, .leave-workspace .table td { padding-top: 1rem; padding-bottom: 1rem; }
.attendance-workspace .table-responsive { overflow-x: auto; }
.attendance-workspace .table-responsive > table { min-width: 760px; }
.attendance-workspace .calendar-content { width: 100%; }
@media (max-width: 767px) {
    .attendance-workspace .card-header, .leave-workspace .card-header { padding: 1rem; }
    .attendance-workspace .pagination, .leave-workspace .pagination { flex-wrap: wrap; }
    .attendance-workspace .hr-summary strong { font-size: 1.125rem !important; }
    .attendance-workspace .month-header-day { font-size: .75rem; }
}
</style>
