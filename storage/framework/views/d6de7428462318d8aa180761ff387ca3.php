
<div x-show="activeTab === 'addresses'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 fw-bold mb-0 text-dark">Registered Addresses</h3>
        <button type="button" @click.prevent="openAddModal" class="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-4 fw-bold text-uppercase shadow-sm" style="font-size: 11px; letter-spacing: 1px;">
            <i class="bi bi-plus-lg"></i> Add Address
        </button>
    </div>

    <?php if($customer->addresses->count()): ?>
        <div class="row g-4">
            <?php $__currentLoopData = $customer->addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $address): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 position-relative hover-shadow-lg transition-all">
                        <?php if($address->is_default): ?>
                            <div class="position-absolute top-0 end-0 bg-primary text-white px-3 py-1 rounded-bl-4 rounded-tr-4 fw-bold text-uppercase shadow-sm" style="font-size: 9px; letter-spacing: 1px; z-index: 2; border-bottom-left-radius: 1rem; border-top-right-radius: 1rem;">
                                Default
                            </div>
                        <?php endif; ?>

                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                    <i class="bi bi-geo-alt fs-5"></i>
                                </div>
                                <span class="fw-bold text-uppercase text-dark" style="font-size: 12px; letter-spacing: 1px;"><?php echo e($address->label ?: 'Address'); ?></span>
                            </div>

                            <p class="fw-bold text-dark mb-1 fs-6"><?php echo e($address->address_line_1); ?></p>
                            <?php if($address->address_line_2): ?>
                                <p class="text-muted small mb-3"><?php echo e($address->address_line_2); ?></p>
                            <?php else: ?>
                                <div class="mb-3"></div>
                            <?php endif; ?>

                            <div class="mt-auto pt-3 border-top d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Village</span>
                                    <span class="fw-bold text-dark" style="font-size: 12px;"><?php echo e($address->village?->village_name ?? $address->village_name ?? '—'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Post Office</span>
                                    <span class="fw-bold text-dark" style="font-size: 12px;"><?php echo e($address->village?->post_so_name ?? $address->post_office ?? '—'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Taluka</span>
                                    <span class="fw-bold text-dark" style="font-size: 12px;"><?php echo e($address->village?->taluka_name ?? $address->taluka ?? '—'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">District</span>
                                    <span class="fw-bold text-dark" style="font-size: 12px;"><?php echo e($address->village?->district_name ?? $address->city ?? '—'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">State</span>
                                    <span class="fw-bold text-dark" style="font-size: 12px;"><?php echo e(!empty($address->village?->state_name) ? $address->village->state_name : (!empty($address->state) ? $address->state : '—')); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Pincode</span>
                                    <span class="fw-bold text-dark font-monospace" style="font-size: 12px;"><?php echo e($address->village?->pincode ?? $address->pincode ?? '—'); ?></span>
                                </div>

                                
                                <?php echo $__env->make('customers.partials._service-badges', ['addrModel' => $address], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                        </div>

                        
                        <div class="card-footer bg-transparent border-top p-3 d-flex justify-content-end gap-2">
                            <button @click="openEditModal(<?php echo e($address->toJson()); ?>)" class="btn btn-sm btn-light text-primary border rounded-3 px-3 shadow-sm">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button @click="openDeleteModal(<?php echo e($address->toJson()); ?>)" class="btn btn-sm btn-light text-danger border rounded-3 px-3 shadow-sm">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5 px-4 rounded-4 border border-2 border-dashed bg-light">
            <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                <i class="bi bi-map fs-1"></i>
            </div>
            <h4 class="h5 fw-bold text-dark">No Registered Addresses</h4>
            <p class="text-muted small mx-auto" style="max-width: 400px;">This customer has no addresses on file. Add one to enable shipping and billing.</p>
            <button type="button" @click.prevent="openAddModal" class="btn btn-primary rounded-pill px-4 mt-3 fw-bold text-uppercase shadow-sm" style="font-size: 11px; letter-spacing: 1px;">
                <i class="bi bi-plus-lg me-1"></i> Add First Address
            </button>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /home/ubuntu/metis/resources/views/customers/partials/tab-addresses.blade.php ENDPATH**/ ?>