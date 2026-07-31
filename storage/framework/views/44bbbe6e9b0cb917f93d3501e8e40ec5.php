<?php $__env->startSection('title', 'Warehouse Command Center'); ?>
<?php $__env->startSection('page', 'inventory.dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
    <div>
        <h1 class="h3 mb-0 fw-bold"><i class="bi bi-buildings text-primary me-2"></i>Warehouse Command Center</h1>
        <p class="text-muted mb-0 small">Real-time operational visibility across all facilities.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form method="GET" action="<?php echo e(route('inventory.dashboard')); ?>" class="d-flex align-items-center gap-2" id="warehouse-filter-form">
            <select name="warehouse_id" class="form-select form-select-sm fw-semibold shadow-sm rounded-pill px-3 <?php echo e($warehouseId ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-body-tertiary text-muted border-0'); ?>" style="min-width: 160px; cursor: pointer; transition: all 0.2s;" onchange="document.getElementById('warehouse-filter-form').submit()">
                <option value="" class="text-body">All Warehouses</option>
                <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($wh->id); ?>" class="text-body" <?php echo e($warehouseId == $wh->id ? 'selected' : ''); ?>><?php echo e($wh->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            
            <select name="date_range" class="form-select form-select-sm fw-semibold shadow-sm rounded-pill px-3 <?php echo e($dateRange ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-body-tertiary text-muted border-0'); ?>" style="min-width: 140px; cursor: pointer; transition: all 0.2s;" onchange="document.getElementById('warehouse-filter-form').submit()">
                <option value="today" class="text-body" <?php echo e($dateRange == 'today' ? 'selected' : ''); ?>>Today</option>
                <option value="yesterday" class="text-body" <?php echo e($dateRange == 'yesterday' ? 'selected' : ''); ?>>Yesterday</option>
                <option value="this_week" class="text-body" <?php echo e($dateRange == 'this_week' ? 'selected' : ''); ?>>This Week</option>
                <option value="this_month" class="text-body" <?php echo e($dateRange == 'this_month' ? 'selected' : ''); ?>>This Month</option>
            </select>
        </form>
    </div>
</div>

<?php echo $__env->make('inventory.dashboard.partials.kpi-widgets', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('inventory.dashboard.partials.pipeline', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('inventory.dashboard.partials.charts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <?php echo $__env->make('inventory.dashboard.partials.activity-feed', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
    <div class="col-lg-4">
        <?php echo $__env->make('inventory.dashboard.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/inventory/dashboard/index.blade.php ENDPATH**/ ?>