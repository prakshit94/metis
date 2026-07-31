<div class="card border-0 shadow-sm rounded-4 h-100">
    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4">
        <h5 class="fw-bold mb-0"><i class="bi bi-bell text-warning me-2"></i>System Alerts</h5>
    </div>
    <div class="card-body p-4">
        <?php if($kpis['low_stock_items'] > 0): ?>
        <div class="alert alert-warning border-warning border-opacity-25 bg-warning bg-opacity-10 d-flex flex-column mb-3 rounded-3">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle fs-4 text-warning me-3"></i>
                <div>
                    <h6 class="fw-bold mb-1 text-warning-emphasis">Low Stock Warning</h6>
                    <div class="small text-muted">There are <?php echo e($kpis['low_stock_items']); ?> products below threshold.</div>
                </div>
            </div>
            <?php if(isset($lowStockAlerts) && count($lowStockAlerts) > 0): ?>
            <div class="mt-3">
                <ul class="list-group list-group-flush border-top border-warning border-opacity-25 pt-2 bg-transparent">
                    <?php $__currentLoopData = $lowStockAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="list-group-item bg-transparent px-0 py-1 border-0 d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold text-warning-emphasis text-truncate" style="max-width: 70%;" title="<?php echo e($alert->product->name ?? 'Unknown'); ?>">
                                <?php echo e($alert->product->name ?? 'Unknown'); ?> 
                                <span class="text-muted ms-1">(<?php echo e($alert->product->sku ?? 'N/A'); ?>)</span>
                            </span>
                            <span class="badge bg-warning bg-opacity-25 text-warning-emphasis border border-warning border-opacity-25">Qty: <?php echo e(number_format($alert->quantity)); ?></span>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                <?php if($kpis['low_stock_items'] > count($lowStockAlerts)): ?>
                    <div class="text-end mt-2">
                        <a href="<?php echo e(route('inventory.stock-management', ['filter_stock' => 'low'])); ?>" class="small text-warning-emphasis fw-bold text-decoration-none">View All <?php echo e($kpis['low_stock_items']); ?> <i class="bi bi-arrow-right"></i></a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if($kpis['pending_returns'] > 0): ?>
        <div class="alert alert-danger border-danger border-opacity-25 bg-danger bg-opacity-10 d-flex align-items-center mb-3 rounded-3">
            <i class="bi bi-arrow-return-left fs-4 text-danger me-3"></i>
            <div>
                <h6 class="fw-bold mb-1 text-danger-emphasis">Action Required: Returns</h6>
                <div class="small text-muted"><?php echo e($kpis['pending_returns']); ?> returns waiting for inspection.</div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($kpis['pending_transfers'] > 0): ?>
        <div class="alert alert-info border-info border-opacity-25 bg-info bg-opacity-10 d-flex align-items-center mb-0 rounded-3">
            <i class="bi bi-truck fs-4 text-info me-3"></i>
            <div>
                <h6 class="fw-bold mb-1 text-info-emphasis">Pending Transfers</h6>
                <div class="small text-muted"><?php echo e($kpis['pending_transfers']); ?> transfers need to be received.</div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($kpis['low_stock_items'] == 0 && $kpis['pending_returns'] == 0 && $kpis['pending_transfers'] == 0): ?>
        <div class="text-center text-muted p-4">
            <i class="bi bi-check-circle fs-1 text-success d-block mb-3"></i>
            <p class="mb-0">All operations are running smoothly.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH /home/user/metis/resources/views/inventory/dashboard/partials/alerts.blade.php ENDPATH**/ ?>