<!-- Import Payments Modal -->
<div class="modal fade" id="importPaymentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Import Payments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Step 1: Upload -->
                <div x-show="importStep === 1">
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-1"></i> Upload a CSV file to bulk import payments. 
                        Required columns: <strong>reference_type</strong> (invoice/order), <strong>reference_no</strong>, <strong>amount</strong>. 
                        Optional columns: <strong>payment_method</strong>, <strong>transaction_id</strong>, <strong>payment_date</strong>.
                    </div>
                    <div class="mb-4">
                        <a href="{{ route('payments.import.sample') }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </a>
                    </div>
                    <form @submit.prevent="previewImport">
                        <div class="mb-3">
                            <input class="form-control" type="file" id="importFile" accept=".csv" @change="importFile = $event.target.files[0]" required>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4" :disabled="!importFile || isImporting">
                                <span x-show="isImporting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                Preview Data
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 2: Preview -->
                <div x-show="importStep === 2">
                    <div class="alert alert-warning small" x-show="importErrors.length > 0">
                        <strong><i class="bi bi-exclamation-triangle me-1"></i> Please fix the following errors before proceeding:</strong>
                        <ul class="mb-0 mt-2">
                            <template x-for="err in importErrors">
                                <li x-text="err"></li>
                            </template>
                        </ul>
                    </div>
                    
                    <div class="table-responsive" style="max-height: 400px;" x-show="importPreview.length > 0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Row</th>
                                    <th>Ref Type</th>
                                    <th>Ref No</th>
                                    <th>Amount</th>
                                    <th>Due Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in importPreview">
                                    <tr>
                                        <td x-text="row.row"></td>
                                        <td class="text-capitalize" x-text="row.reference_type"></td>
                                        <td class="font-monospace" x-text="row.reference_no"></td>
                                        <td class="text-success fw-medium" x-text="formatCurrency(row.amount)"></td>
                                        <td x-text="formatCurrency(row.due_amount)"></td>
                                        <td x-text="row.payment_method"></td>
                                        <td x-text="row.payment_date"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-light" @click="importStep = 1; importFile = null; document.getElementById('importFile').value = '';">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </button>
                        <button type="button" class="btn btn-success px-4" @click="processImport" :disabled="isImporting || importErrors.length > 0 || importPreview.length === 0">
                            <span x-show="isImporting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            <i class="bi bi-check-lg me-1" x-show="!isImporting"></i> Confirm & Import
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
