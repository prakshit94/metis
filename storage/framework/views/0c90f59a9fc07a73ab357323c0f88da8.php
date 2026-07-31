<?php $__empty_1 = true; $__currentLoopData = $recentActivity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $statusColor = match(strtolower($activity->status ?? '')) {
            'completed', 'delivered', 'published', 'active', 'approved' => 'success',
            'pending', 'processing', 'draft', 'requested', 'in_transit' => 'warning',
            'cancelled', 'failed', 'rejected' => 'danger',
            default => 'secondary'
        };
    ?>
    <div class="d-flex mb-4 activity-item">
        <?php if($activity->feed_type == 'movement'): ?>
            <div class="me-3">
                <div class="bg-light text-secondary rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                    <?php if($activity->type == 'in'): ?>
                        <i class="bi bi-box-arrow-in-right text-success"></i>
                    <?php elseif($activity->type == 'out'): ?>
                        <i class="bi bi-box-arrow-right text-danger"></i>
                    <?php else: ?>
                        <i class="bi bi-arrow-left-right text-warning"></i>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div class="fw-bold text-body-emphasis">
                    <?php echo e($activity->product->name ?? 'Unknown Product'); ?>

                </div>
                <div class="text-muted small">
                    <span class="badge <?php echo e($activity->type == 'in' ? 'bg-success' : ($activity->type == 'out' ? 'bg-danger' : 'bg-warning')); ?> bg-opacity-10 text-<?php echo e($activity->type == 'in' ? 'success' : ($activity->type == 'out' ? 'danger' : 'warning')); ?> border border-<?php echo e($activity->type == 'in' ? 'success' : ($activity->type == 'out' ? 'danger' : 'warning')); ?> border-opacity-25 px-2 py-1 rounded-pill me-1">
                        <?php echo e(strtoupper($activity->type)); ?>

                    </span>
                    <?php echo e(number_format($activity->quantity)); ?> units (<?php echo e($activity->reference_label); ?> #<?php echo e($activity->reference_number); ?>)
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    <i class="bi bi-clock me-1"></i><?php echo e($activity->created_at->diffForHumans()); ?> by <?php echo e($activity->performer->name ?? 'System'); ?>

                </div>
            </div>
        <?php elseif($activity->feed_type == 'receipt'): ?>
            <div class="me-3">
                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-receipt text-info"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-body-emphasis">
                    Goods Receipt: <?php echo e($activity->grn_number); ?>

                </div>
                <div class="text-muted small">
                    <span class="badge bg-<?php echo e($statusColor); ?> bg-opacity-10 text-<?php echo e($statusColor); ?> border border-<?php echo e($statusColor); ?> border-opacity-25 px-2 py-1 rounded-pill me-1">
                        <?php echo e(strtoupper($activity->status)); ?>

                    </span>
                    For PO: <?php echo e($activity->purchaseOrder->po_number ?? 'N/A'); ?>

                </div>
                <div class="text-muted" style="font-size: 11px;">
                    <i class="bi bi-clock me-1"></i><?php echo e($activity->created_at->diffForHumans()); ?> by <?php echo e($activity->creator->name ?? 'System'); ?>

                </div>
            </div>
        <?php elseif($activity->feed_type == 'adjustment'): ?>
            <div class="me-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-sliders text-primary"></i>
                </div>
            </div>
            <div>
                <div class="fw-bold text-body-emphasis">
                    Inventory Adjustment: <?php echo e($activity->reference_no); ?>

                </div>
                <div class="text-muted small">
                    <span class="badge bg-<?php echo e($statusColor); ?> bg-opacity-10 text-<?php echo e($statusColor); ?> border border-<?php echo e($statusColor); ?> border-opacity-25 px-2 py-1 rounded-pill me-1">
                        <?php echo e(strtoupper($activity->status)); ?>

                    </span>
                    Reason: <?php echo e($activity->reason ?? 'N/A'); ?>

                </div>
                <div class="text-muted" style="font-size: 11px;">
                    <i class="bi bi-clock me-1"></i><?php echo e($activity->created_at->diffForHumans()); ?> by <?php echo e($activity->user->name ?? 'System'); ?>

                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="text-center text-muted p-5">
        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
        <p class="mb-0">No recent activities found.</p>
    </div>
<?php endif; ?>
<?php /**PATH /home/user/metis/resources/views/inventory/dashboard/partials/activity-feed-items.blade.php ENDPATH**/ ?>