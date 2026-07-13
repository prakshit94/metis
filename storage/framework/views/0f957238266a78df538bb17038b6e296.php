
<div x-show="activeTab === 'system'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-5">
                    <h4 class="mb-5 text-muted fw-bold text-uppercase d-flex align-items-center gap-2" style="font-size: 11px; letter-spacing: 2px;">
                        <span class="bg-secondary rounded-circle shadow-sm" style="width: 8px; height: 8px;"></span> Timestamps
                    </h4>
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex align-items-center gap-4">
                            <div class="bg-light text-secondary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="bi bi-calendar3 fs-4"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Created On</p>
                                <p class="mb-0 fw-bold text-dark fs-6"><?php echo e($customer->created_at->format('M d, Y h:i A')); ?></p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-4">
                            <div class="bg-light text-secondary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="bi bi-arrow-repeat fs-4"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Last Updated</p>
                                <p class="mb-0 fw-bold text-dark fs-6"><?php echo e($customer->updated_at->diffForHumans()); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border border-danger border-opacity-25 bg-danger bg-opacity-10 rounded-4 h-100 position-relative overflow-hidden">
                <div class="position-absolute bottom-0 end-0 p-4 opacity-10" style="z-index: 0; transform: translate(25%, 25%);">
                    <i class="bi bi-exclamation-triangle text-danger" style="font-size: 10rem;"></i>
                </div>
                <div class="card-body p-5 position-relative" style="z-index: 1;">
                    <h4 class="mb-4 text-danger fw-bold text-uppercase d-flex align-items-center gap-2" style="font-size: 11px; letter-spacing: 2px;">
                        <span class="bg-danger rounded-circle shadow-sm" style="width: 8px; height: 8px; animation: pulse 2s infinite;"></span> Danger Zone
                    </h4>
                    <p class="fs-5 fw-bold text-dark mb-2">Archive Customer Record</p>
                    <p class="text-muted small mb-4 pb-2" style="max-width: 300px;">This action will hide the customer from main views but can be restored by an admin.</p>
                    
                    <form action="<?php echo e(route('api.customers.destroy', $customer)); ?>" method="POST" onsubmit="return confirm('Archive this customer?')" class="m-0">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-outline-danger fw-bold text-uppercase d-flex align-items-center gap-2 rounded-pill px-4 shadow-sm" style="font-size: 11px; letter-spacing: 1px;">
                            <i class="bi bi-archive"></i> Archive Record
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.5); opacity: 0.5; }
    100% { transform: scale(1); opacity: 1; }
}
</style>
<?php /**PATH /home/ubuntu/metis/resources/views/customers/partials/tab-system.blade.php ENDPATH**/ ?>