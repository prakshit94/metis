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
</div>

<!-- Reports Management Container -->
<div class="reports-page" x-data="reportsComponent">
    <!-- Advanced Report Generation -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-body-tertiary">
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
                            <option value="call_performance">Call Center Performance</option>
                            <option value="call_tagging">Call Tagging Analysis</option>
                            <option value="customer_retention">Customer Retention</option>
                            <option value="return_analysis">Return & Cancellation Analysis</option>
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