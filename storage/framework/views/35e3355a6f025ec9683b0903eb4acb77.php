
<div x-show="activeTab === 'finance'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 p-4 opacity-25" style="z-index: 0;">
                    <i class="bi bi-file-earmark-text text-secondary" style="font-size: 8rem;"></i>
                </div>
                <div class="card-body p-5 position-relative" style="z-index: 1;">
                    <h4 class="mb-4 text-muted fw-bold text-uppercase d-flex align-items-center gap-2" style="font-size: 11px; letter-spacing: 2px;">
                        <span class="bg-primary rounded-circle shadow-sm" style="width: 8px; height: 8px;"></span> Tax Details
                    </h4>
                    <div class="d-flex flex-column gap-4">
                        <div>
                            <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">GST Number</p>
                            <p class="mb-0 fs-5 font-monospace fw-bold text-dark"><?php echo e($customer->gst_no ?: 'Not Provided'); ?></p>
                        </div>
                        <div>
                            <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">PAN Number</p>
                            <p class="mb-0 fs-5 font-monospace fw-bold text-dark"><?php echo e($customer->pan_no ?: 'Not Provided'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 p-4 opacity-25" style="z-index: 0;">
                    <i class="bi bi-credit-card text-success" style="font-size: 8rem;"></i>
                </div>
                <div class="card-body p-5 position-relative" style="z-index: 1;">
                    <h4 class="mb-4 text-muted fw-bold text-uppercase d-flex align-items-center gap-2" style="font-size: 11px; letter-spacing: 2px;">
                        <span class="bg-success rounded-circle shadow-sm" style="width: 8px; height: 8px;"></span> Credit Policy
                    </h4>
                    <div class="d-flex flex-column gap-4">
                        <div>
                            <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Credit Limit</p>
                            <p class="mb-0 fs-2 fw-black text-success lh-1">Rs <?php echo e(number_format($customer->credit_limit, 2)); ?></p>
                        </div>
                        <div>
                            <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Payment Terms</p>
                            <p class="mb-0 fs-4 fw-bold text-dark lh-1"><?php echo e($customer->credit_days ?: 0); ?> <span class="fs-6 text-muted fw-normal">Days</span></p>
                        </div>
                        <div>
                            <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Outstanding Balance</p>
                            <p class="mb-0 fs-3 fw-black <?php echo e(($customer->outstanding_balance ?? 0) > 0 ? 'text-danger' : 'text-dark'); ?> lh-1">Rs <?php echo e(number_format($customer->outstanding_balance ?? 0, 2)); ?></p>
                        </div>
                        <div>
                            <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Credit Valid Till</p>
                            <p class="mb-0 fw-bold text-dark"><?php echo e($customer->credit_valid_till ? $customer->credit_valid_till->format('M d, Y') : 'No Expiry'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/user/metis/resources/views/customers/partials/tab-finance.blade.php ENDPATH**/ ?>