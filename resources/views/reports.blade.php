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
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" @click="scheduleReport()">
                                <i class="bi bi-calendar-plus me-2"></i>Schedule
                            </button>
                            <button type="button" class="btn btn-outline-secondary" @click="exportData()">
                                <i class="bi bi-download me-2"></i>Export
                            </button>
                            <button type="button" class="btn btn-primary" @click="generateReport()">
                                <i class="bi bi-plus-lg me-2"></i>New Report
                            </button>
                        </div>
                    </div>

                    <!-- Reports Management Container -->
                    <div class="reports-page" x-data="reportsComponent" x-init="init()">
                        
                        <!-- Report Filters -->
                        <!-- Advanced Report Generation -->
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-body-tertiary">
                            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
                                <h5 class="card-title mb-0 fw-bold">Advanced Report Generation</h5>
                                <p class="text-muted small mb-0">Select criteria to download detailed insights</p>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label fw-medium text-secondary small text-uppercase">Report Type</label>
                                        <select x-select class="form-select form-select-lg shadow-sm border-0 bg-body rounded-3" x-model="reportType" style="font-size: 0.95rem;">
                                            <optgroup label="Sales & Revenue">
                                                <option value="sales_overview">Sales Performance Overview (Detailed)</option>
                                                <option value="product_sales">Product Sales (Bestsellers)</option>
                                                <option value="sales_region">Sales by Region</option>
                                                <option value="payment_reconciliation">Payment Reconciliation</option>
                                            </optgroup>
                                            <optgroup label="Inventory & Warehouse">
                                                <option value="stock_valuation">Current Stock Valuation (Detailed)</option>
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
                                                <option value="customer_retention">Customer Retention & Referrals</option>
                                                <option value="return_analysis">Return & Cancellation Analysis</option>
                                            </optgroup>
                                            <optgroup label="System Auditing">
                                                <option value="audit_trail">System Audit Trail</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-medium text-secondary small text-uppercase">Date From</label>
                                        <input type="datetime-local" class="form-control form-control-lg shadow-sm border-0 bg-body rounded-3" x-model="dateFrom" style="font-size: 0.95rem;">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-medium text-secondary small text-uppercase">Date To</label>
                                        <input type="datetime-local" class="form-control form-control-lg shadow-sm border-0 bg-body rounded-3" x-model="dateTo" style="font-size: 0.95rem;">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button class="btn btn-primary w-100 py-2 shadow-sm fw-medium rounded-3 d-flex align-items-center justify-content-center" @click="downloadAdvancedReport()" title="Download CSV Report" style="height: calc(1.5em + 1rem + 2px);">
                                            <i class="bi bi-download fs-6 me-2"></i> Download CSV
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>


                        
                    </div> <!-- End Reports Management Container -->
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script type="module" src="./scripts/components/reports.js"></script>

<script type="module" src="./scripts/main.js"></script>
@endpush
