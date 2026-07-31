<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-success me-2"></i>Fulfillment Performance</h5>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-center align-items-center text-center">
                <div class="position-relative d-flex justify-content-center align-items-center mb-3" style="width: 150px; height: 150px;">
                    <svg viewBox="0 0 36 36" class="circular-chart text-success" style="width: 100%; height: 100%;">
                        <path class="circle-bg"
                        d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none" stroke="#eee" stroke-width="3"/>
                        <path class="circle"
                        stroke-dasharray="<?php echo e($fulfillment['fulfillment_rate']); ?>, 100"
                        d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                    <div class="position-absolute fs-3 fw-bold text-body-emphasis"><?php echo e($fulfillment['fulfillment_rate']); ?>%</div>
                </div>
                <div class="row w-100 text-center mt-3">
                    <div class="col-4 border-end border-opacity-25">
                        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 10px;">Total</div>
                        <div class="fs-5 fw-bold"><?php echo e(number_format($fulfillment['total'])); ?></div>
                    </div>
                    <div class="col-4 border-end border-opacity-25">
                        <div class="text-success small fw-bold text-uppercase mb-1" style="font-size: 10px;">Delivered</div>
                        <div class="fs-5 fw-bold"><?php echo e(number_format($fulfillment['delivered'])); ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-danger small fw-bold text-uppercase mb-1" style="font-size: 10px;">Cancelled</div>
                        <div class="fs-5 fw-bold"><?php echo e(number_format($fulfillment['cancelled'])); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary bg-opacity-10 text-primary-emphasis">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-wallet2 me-2"></i>Estimated Inventory Value</h5>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-center align-items-start">
                <h2 class="display-5 fw-bold mb-3">₹<?php echo e(number_format($inventoryValue, 2)); ?></h2>
                <p class="mb-0 text-muted fw-semibold">
                    <i class="bi bi-info-circle me-1"></i> Based on available physical stock and unit price.
                </p>
                <?php if($shrinkageValue != 0): ?>
                <div class="mt-3 pt-3 border-top border-primary border-opacity-25 w-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-uppercase tracking-wide text-primary">Net Cycle Count Adjustments</span>
                        <span class="badge <?php echo e($shrinkageValue > 0 ? 'bg-success' : 'bg-danger'); ?> bg-opacity-10 border <?php echo e($shrinkageValue > 0 ? 'border-success' : 'border-danger'); ?> border-opacity-25 <?php echo e($shrinkageValue > 0 ? 'text-success' : 'text-danger'); ?>">
                            <?php echo e($shrinkageValue > 0 ? '+' : ''); ?>₹<?php echo e(number_format($shrinkageValue, 2)); ?>

                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/user/metis/resources/views/inventory/dashboard/partials/charts.blade.php ENDPATH**/ ?>