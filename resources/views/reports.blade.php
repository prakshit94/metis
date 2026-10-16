@extends('layouts.app')

@section('title', 'Reports & Analytics')
@section('page', 'reports')

@section('content')
<div class="reports-page" x-data="reportsComponent">
    <header class="reports-header d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-2">Reports & Analytics</h1>
            <p class="text-body-secondary mb-0">Choose a report, set your dates, and export your business data.</p>
        </div>
        <button type="button" class="btn btn-outline-secondary reports-refresh flex-shrink-0" onclick="window.location.reload()" aria-label="Refresh reports" title="Refresh reports">
            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
        </button>
    </header>

    <div class="row g-3 mb-4" aria-label="Report export information">
        <div class="col-sm-6 col-xl-3">
            <div class="card report-summary h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="report-summary-icon report-tone-primary" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                    <div class="min-w-0">
                        <p class="report-summary-label text-body-secondary mb-1">Available reports</p>
                        <p class="h5 fw-semibold mb-0">16</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card report-summary h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="report-summary-icon report-tone-success" aria-hidden="true"><i class="bi bi-download"></i></span>
                    <div class="min-w-0">
                        <p class="report-summary-label text-body-secondary mb-1">Export format</p>
                        <p class="h5 fw-semibold mb-0">CSV</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card report-summary h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="report-summary-icon report-tone-warning" aria-hidden="true"><i class="bi bi-sliders"></i></span>
                    <div class="min-w-0">
                        <p class="report-summary-label text-body-secondary mb-1">Report categories</p>
                        <p class="h5 fw-semibold mb-0">6</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card report-summary h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="report-summary-icon report-tone-info" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
                    <div class="min-w-0">
                        <p class="report-summary-label text-body-secondary mb-1">Date selection</p>
                        <p class="h5 fw-semibold mb-0">Custom range</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="card report-export-card" aria-labelledby="reportExportHeading">
        <div class="card-header d-flex align-items-start gap-3 p-4">
            <span class="report-summary-icon report-tone-primary flex-shrink-0" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
            <div>
                <h2 class="h5 fw-bold mb-2" id="reportExportHeading">Export a report</h2>
                <p class="text-body-secondary mb-0">Download a CSV for accounting, auditing, or further analysis.</p>
            </div>
        </div>
        <form @submit.prevent="downloadAdvancedReport()" aria-labelledby="reportExportHeading">
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <label for="reportType" class="form-label fw-semibold">Report type</label>
                        <select id="reportType" name="type" class="form-select report-control" x-model="reportType" aria-describedby="reportTypeHelp" required>
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
                        <div id="reportTypeHelp" class="form-text">Browse reports grouped by business area.</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="reportDateFrom" class="form-label fw-semibold">Date from</label>
                        <input id="reportDateFrom" name="from" type="date" class="form-control report-control" x-model="dateFrom" :max="dateTo || null" aria-describedby="reportDateHelp" required>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="reportDateTo" class="form-label fw-semibold">Date to</label>
                        <input id="reportDateTo" name="to" type="date" class="form-control report-control" x-model="dateTo" :min="dateFrom || null" aria-describedby="reportDateHelp" required>
                    </div>
                </div>
                <p id="reportDateHelp" class="small text-body-secondary mt-3 mb-0 d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle flex-shrink-0" aria-hidden="true"></i>
                    <span>Date filters apply where supported by the selected report.</span>
                </p>
            </div>
            <div class="card-footer report-export-footer p-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                <p class="small text-body-secondary mb-0">CSV files open in Excel, Google Sheets, and other spreadsheet apps.</p>
                <button type="submit" class="btn btn-primary report-download fw-semibold d-inline-flex align-items-center justify-content-center gap-2 flex-shrink-0">
                    <i class="bi bi-download" aria-hidden="true"></i> Download CSV
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
