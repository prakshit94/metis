@extends('layouts.app')

@section('title', 'Reports & Analytics')
@section('page', 'reports')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
    <div>
        <h1 class="h3 mb-0 fw-bold">Reports & Analytics</h1>
        <p class="text-muted mb-0">Generate insights and export business data</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm bg-body-tertiary" onclick="window.location.reload()" data-bs-toggle="tooltip" title="Refresh data">
            <i class="bi bi-arrow-clockwise icon-hover"></i>
        </button>
    </div>
</div>

<!-- Reports Management Container -->
<div class="reports-page" x-data="reportsComponent">

    <!-- KPI Widgets -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-file-earmark-text fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Available Reports</p>
                            <div class="h4 mb-0 fw-bold">16</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-cloud-download fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Export Status</p>
                            <div class="h4 mb-0 fw-bold text-success">Online</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-start border-4 border-warning">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-server fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Server Load</p>
                            <div class="h4 mb-0 fw-bold">Normal</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-start border-4 border-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Last Refresh</p>
                            <div class="h4 mb-0 fw-bold">Just Now</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Report Generation -->
    <div class="card overflow-hidden mb-4 bg-body-tertiary border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5 position-relative">
            <!-- Background Icon Decoration -->
            <div class="position-absolute end-0 bottom-0 opacity-10 me-4 mb-n4" style="pointer-events: none;">
                <i class="bi bi-file-earmark-spreadsheet text-primary" style="font-size: 10rem;"></i>
            </div>
            
            <h4 class="fw-bold mb-2 text-body">Export Complete Datasets</h4>
            <p class="mb-4 text-muted fs-6">Generate deep-dive CSV reports for accounting, auditing, and analysis.</p>
            
            <div class="row g-3 align-items-end position-relative z-1">
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">Report Type</label>
                    <select class="form-select form-select-lg border-0 shadow-sm" x-model="reportType" style="font-size: 0.95rem;">
                        <optgroup label="Sales & Revenue">
                            <option value="sales_overview">Sales Performance Overview</option>
                            <option value="product_sales">Product Sales (Bestsellers)</option>
                            <option value="sales_region">Sales by Region</option>
                            <option value="payment_reconciliation">Payment Reconciliation</option>
                        </optgroup>
                        <optgroup label="Inventory & Warehouse">
                            <option value="stock_valuation">Current Stock Valuation</option>
                            <option value="stock_ledger">Stock Movement Ledger</option>
                            <option value="low_stock">Low Stock Alerts</option>
                        </optgroup>
                        <optgroup label="Procurement">
                            <option value="po_fulfillment">Purchase Order Fulfillment</option>
                            <option value="grn_discrepancy">GRN Discrepancy</option>
                        </optgroup>
                        <optgroup label="CRM & Support">
                            <option value="suppliers_report">Suppliers Directory</option>
                            <option value="call_performance">Call Center Performance</option>
                            <option value="call_tagging">Call Tagging Analysis</option>
                            <option value="customer_retention">Customer Retention</option>
                            <option value="return_analysis">Return & Cancellation Analysis</option>
                        </optgroup>
                        <optgroup label="HR & Team">
                            <option value="attendance_report">Employee Attendance</option>
                        </optgroup>
                        <optgroup label="System Auditing">
                            <option value="audit_trail">System Audit Trail</option>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">Date From</label>
                    <input type="date" class="form-control form-control-lg border-0 shadow-sm" x-model="dateFrom" style="font-size: 0.95rem;">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small text-uppercase text-muted">Date To</label>
                    <input type="date" class="form-control form-control-lg border-0 shadow-sm" x-model="dateTo" style="font-size: 0.95rem;">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary btn-lg w-100 shadow-sm fw-bold d-flex align-items-center justify-content-center" @click="downloadAdvancedReport()">
                        <i class="bi bi-download me-2"></i> Download CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
</div> <!-- End Reports Management Container -->
@endsection