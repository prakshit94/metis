
<div x-show="activeTab === 'review'" 
     x-transition:enter="transition ease-out duration-500" 
     x-transition:enter-start="opacity-0 translate-y-4" 
     x-transition:enter-end="opacity-100 translate-y-0" 
     x-cloak>
    
    <div class="d-flex flex-column gap-4 mx-auto" style="max-width: 1200px;">
        
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-light bg-opacity-50">
            <div class="card-body p-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                <button type="button" @click="activeTab = 'order'" 
                    class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2 rounded-pill px-4 fw-bold text-uppercase w-100 w-sm-auto shadow-sm bg-white" style="font-size: 10px; letter-spacing: 1px;">
                    <i class="bi bi-arrow-left"></i> Back to Cart
                </button>
                <div class="d-none d-sm-block text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 3px;">Final Validation Phase</div>
            </div>
        </div>
        
        <template x-if="editingOrderId">
            <div class="alert alert-warning border border-warning border-opacity-25 shadow-sm rounded-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-25 text-warning rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1 fs-6 text-dark">Edit Mode Active</h4>
                        <p class="mb-0 text-muted small">You are updating an existing order. Your changes will overwrite the current record.</p>
                    </div>
                </div>
                <button type="button" @click="cancelEditOrder" class="btn btn-warning text-white fw-bold text-uppercase rounded-pill px-4 shadow-sm text-nowrap" style="font-size: 10px; letter-spacing: 1px;">
                    Cancel Edit
                </button>
            </div>
        </template>

        
        <div class="d-flex flex-column gap-4">
            
            
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h4 class="mb-4 text-primary fw-bold text-uppercase d-flex align-items-center gap-3" style="font-size: 10px; letter-spacing: 2px;">
                        <span class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-person"></i>
                        </span>
                        Customer Identification
                    </h4>
                    <div class="row g-4">
                        <div class="col-sm-6 col-lg-3">
                            <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Full Name</p>
                            <p class="mb-0 fw-bold text-dark fs-6"><?php echo e($customer->name); ?></p>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Mobile</p>
                            <p class="mb-0 fw-bold text-dark fs-6"><?php echo e($customer->phone ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Alt Mobile</p>
                            <p class="mb-0 fw-bold text-dark fs-6"><?php echo e($customer->alternatemobile ?? '—'); ?></p>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Relative Mobile</p>
                            <p class="mb-0 fw-bold text-dark fs-6"><?php echo e($customer->relative_mobile ?? '—'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                        <h4 class="mb-0 text-primary fw-bold text-uppercase d-flex align-items-center gap-3" style="font-size: 10px; letter-spacing: 2px;">
                            <span class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-file-earmark-text"></i>
                            </span>
                            Billing Address
                        </h4>
                        <button type="button" @click.prevent="openAddModal" class="btn btn-link text-primary p-0 text-decoration-none fw-bold text-uppercase d-flex align-items-center gap-1" style="font-size: 9px; letter-spacing: 1px;">
                            <i class="bi bi-plus"></i> New Address
                        </button>
                    </div>
                    
                    <div class="d-flex flex-column gap-3">
                        <?php $__currentLoopData = $customer->addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $addr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="position-relative d-flex flex-column p-4 rounded-4 border border-2 cursor-pointer transition-all w-100"
                                :class="selectedBillingAddressId == <?php echo e($addr->id); ?> ? 'border-primary bg-primary bg-opacity-10' : 'border-light bg-light hover-border-secondary'">
                                <input type="radio" x-model="selectedBillingAddressId" value="<?php echo e($addr->id); ?>" class="d-none">
                                
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge border fw-bold text-uppercase text-dark bg-white" style="font-size: 9px; letter-spacing: 1px;"
                                            :class="selectedBillingAddressId == <?php echo e($addr->id); ?> ? 'border-primary text-primary' : ''">
                                            <?php echo e($addr->label ?: 'Address'); ?>

                                        </span>
                                        <?php if($addr->is_default): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-bold text-uppercase" style="font-size: 8px; letter-spacing: 1px;">Default</span>
                                        <?php endif; ?>
                                    </div>
                                    <template x-if="selectedBillingAddressId == <?php echo e($addr->id); ?>">
                                        <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                                    </template>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Street / Landmark</p>
                                        <p class="mb-0 fw-bold text-dark fs-6"><?php echo e($addr->address_line_1); ?></p>
                                        <?php if($addr->address_line_2): ?>
                                            <p class="mb-0 text-muted small mt-1"><?php echo e($addr->address_line_2); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Village</p>
                                                <p class="mb-0 fw-bold text-dark small"><?php echo e($addr->village?->village_name ?? $addr->village_name ?? '—'); ?></p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Post Office</p>
                                                <p class="mb-0 fw-bold text-dark small"><?php echo e($addr->village?->post_so_name ?? $addr->post_office ?? '—'); ?></p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Taluka</p>
                                                <p class="mb-0 fw-bold text-dark small"><?php echo e($addr->village?->taluka_name ?? $addr->taluka ?? '—'); ?></p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">District / State / Pin</p>
                                                <p class="mb-0 fw-bold text-dark small"><?php echo e($addr->village?->district_name ?? $addr->city ?? '—'); ?>, <?php echo e(!empty($addr->village?->state_name) ? $addr->village->state_name : (!empty($addr->state) ? $addr->state : '—')); ?> - <?php echo e($addr->village?->pincode ?? $addr->pincode); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                
                                <?php echo $__env->make('customers.partials._service-badges', ['addrModel' => $addr], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>

            
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                        <h4 class="mb-0 text-primary fw-bold text-uppercase d-flex align-items-center gap-3" style="font-size: 10px; letter-spacing: 2px;">
                            <span class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-truck"></i>
                            </span>
                            Shipping Address
                        </h4>
                        <div class="d-flex align-items-center gap-4">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="sameAsBilling" x-model="sameAsBilling">
                                <label class="form-check-label text-dark fw-bold text-uppercase" for="sameAsBilling" style="font-size: 10px; letter-spacing: 1px; cursor: pointer;">Same as Billing</label>
                            </div>
                            <button type="button" @click.prevent="openAddModal" class="btn btn-link text-primary p-0 text-decoration-none fw-bold text-uppercase d-flex align-items-center gap-1" style="font-size: 9px; letter-spacing: 1px;">
                                <i class="bi bi-plus"></i> New Address
                            </button>
                        </div>
                    </div>

                    <div x-show="!sameAsBilling" x-transition class="d-flex flex-column gap-3">
                        <?php $__currentLoopData = $customer->addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $addr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="position-relative d-flex flex-column p-4 rounded-4 border border-2 cursor-pointer transition-all w-100"
                                :class="selectedShippingAddressId == <?php echo e($addr->id); ?> ? 'border-primary bg-primary bg-opacity-10' : 'border-light bg-light hover-border-secondary'">
                                <input type="radio" x-model="selectedShippingAddressId" value="<?php echo e($addr->id); ?>" class="d-none">
                                
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge border fw-bold text-uppercase text-dark bg-white" style="font-size: 9px; letter-spacing: 1px;"
                                            :class="selectedShippingAddressId == <?php echo e($addr->id); ?> ? 'border-primary text-primary' : ''">
                                            <?php echo e($addr->label ?: 'Address'); ?>

                                        </span>
                                    </div>
                                    <template x-if="selectedShippingAddressId == <?php echo e($addr->id); ?>">
                                        <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                                    </template>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Street / Landmark</p>
                                        <p class="mb-0 fw-bold text-dark fs-6"><?php echo e($addr->address_line_1); ?></p>
                                        <?php if($addr->address_line_2): ?>
                                            <p class="mb-0 text-muted small mt-1"><?php echo e($addr->address_line_2); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Village</p>
                                                <p class="mb-0 fw-bold text-dark small"><?php echo e($addr->village?->village_name ?? $addr->village_name ?? '—'); ?></p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Post Office</p>
                                                <p class="mb-0 fw-bold text-dark small"><?php echo e($addr->village?->post_so_name ?? $addr->post_office ?? '—'); ?></p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Taluka</p>
                                                <p class="mb-0 fw-bold text-dark small"><?php echo e($addr->village?->taluka_name ?? $addr->taluka ?? '—'); ?></p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">District / State / Pin</p>
                                                <p class="mb-0 fw-bold text-dark small"><?php echo e($addr->village?->district_name ?? $addr->city ?? '—'); ?>, <?php echo e(!empty($addr->village?->state_name) ? $addr->village->state_name : (!empty($addr->state) ? $addr->state : '—')); ?> - <?php echo e($addr->village?->pincode ?? $addr->pincode); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                
                                <?php echo $__env->make('customers.partials._service-badges', ['addrModel' => $addr], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div x-show="sameAsBilling" class="p-5 rounded-4 border border-2 border-dashed bg-light d-flex flex-column align-items-center justify-content-center text-center">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 40px; height: 40px;">
                            <i class="bi bi-check fs-4"></i>
                        </div>
                        <p class="mb-0 fw-bold text-muted small">Synchronized with Billing Address</p>
                    </div>
                </div>
            </div>

            
            <div class="card border-0 shadow-sm rounded-4" x-data="{ warehousesMap: <?php echo \Illuminate\Support\Js::from($warehouses->keyBy('id'))->toHtml() ?> }">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border-bottom pb-4 mb-4 gap-3">
                        <h4 class="mb-0 text-primary fw-bold text-uppercase d-flex align-items-center gap-3" style="font-size: 10px; letter-spacing: 2px;">
                            <span class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-building"></i>
                            </span>
                            Dispatch & Billing Details
                        </h4>
                        <span class="text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Required for GST Invoice</span>
                    </div>

                    <div class="mb-4" style="max-width: 400px;">
                        <label class="form-label text-muted fw-bold text-uppercase ms-1" style="font-size: 9px; letter-spacing: 1px;">Select Dispatch Hub</label>
                        <select x-model="selectedWarehouseId" class="form-select bg-light border-0 shadow-sm fw-bold px-3 py-2">
                            <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($wh->id); ?>"><?php echo e($wh->name); ?> (<?php echo e($wh->code); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="row g-4">
                        
                        <div class="col-md-6">
                            <div class="p-4 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-10 h-100">
                                <div class="d-flex align-items-center gap-2 text-primary fw-bold text-uppercase mb-3" style="font-size: 12px; letter-spacing: 1px;">
                                    <i class="bi bi-shield-check"></i> Company Information (Sender)
                                </div>
                                <div class="text-muted small">
                                    <p class="fw-bold text-dark fs-6 mb-1" x-text="selectedWarehouseId && warehousesMap[selectedWarehouseId]?.company_name ? warehousesMap[selectedWarehouseId].company_name : 'Krushify Agro Pvt. Ltd.'"></p>
                                    <p class="mb-3" x-text="selectedWarehouseId && warehousesMap[selectedWarehouseId]?.address_line_1 ? `${warehousesMap[selectedWarehouseId].address_line_1}${warehousesMap[selectedWarehouseId].address_line_2 ? ', ' + warehousesMap[selectedWarehouseId].address_line_2 : ''}, ${warehousesMap[selectedWarehouseId].city || 'Rajkot'}, ${warehousesMap[selectedWarehouseId].state || 'Gujarat'} - ${warehousesMap[selectedWarehouseId].pincode || '360003'}` : 'Plot No 19, Raj Ind Amul Cross Road, Ruda Transport Nagar, 360003 Rajkot, Gujarat.'"></p>
                                    <div class="d-flex flex-wrap gap-3" style="font-size: 11px;">
                                        <span class="d-flex align-items-center gap-1 font-monospace text-primary fw-bold"><i class="bi bi-file-earmark-text"></i> GSTIN: <span x-text="selectedWarehouseId && warehousesMap[selectedWarehouseId]?.gstin ? warehousesMap[selectedWarehouseId].gstin : '24AAMCK0386L1Z6'"></span></span>
                                        <span class="d-flex align-items-center gap-1 fw-bold"><i class="bi bi-telephone"></i> Mobile: <span x-text="selectedWarehouseId && warehousesMap[selectedWarehouseId]?.phone ? warehousesMap[selectedWarehouseId].phone : '+91 9199125925'"></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="col-md-6">
                            <div class="p-4 rounded-4 bg-light border h-100">
                                <div class="d-flex align-items-center gap-2 text-dark fw-bold text-uppercase mb-3" style="font-size: 12px; letter-spacing: 1px;">
                                    <i class="bi bi-house"></i> Selected Hub Address
                                </div>
                                <div class="text-muted small">
                                    <template x-if="selectedWarehouseId && warehousesMap[selectedWarehouseId]">
                                        <div>
                                            <p class="fw-bold text-dark fs-6 mb-1" x-text="`${warehousesMap[selectedWarehouseId].name} (${warehousesMap[selectedWarehouseId].code})`"></p>
                                            <p class="mb-0">
                                                <span x-text="warehousesMap[selectedWarehouseId].address_line_1"></span>
                                                <template x-if="warehousesMap[selectedWarehouseId].address_line_2">
                                                    <span>, <span x-text="warehousesMap[selectedWarehouseId].address_line_2"></span></span>
                                                </template>
                                                <br>
                                                <span x-text="warehousesMap[selectedWarehouseId].village?.village_name || warehousesMap[selectedWarehouseId].village_name || warehousesMap[selectedWarehouseId].city || '—'"></span>,
                                                <span x-text="warehousesMap[selectedWarehouseId].state || '—'"></span> - 
                                                <span x-text="warehousesMap[selectedWarehouseId].pincode || '—'"></span>
                                            </p>
                                        </div>
                                    </template>
                                    <template x-if="!selectedWarehouseId || !warehousesMap[selectedWarehouseId]">
                                        <div>
                                            <p class="fw-bold text-dark fs-6 mb-1">Default Central Warehouse</p>
                                            <p class="mb-0">Rajkot Hub, Gujarat - 360003</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-light border-bottom p-4 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 text-dark fw-bold text-uppercase d-flex align-items-center gap-3" style="font-size: 10px; letter-spacing: 2px;">
                        <i class="bi bi-bag"></i> Order Items
                    </h4>
                    <span class="text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;" x-text="cart.length + ' Product Units'"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">
                            <tr>
                                <th class="px-4 py-3">Product Specification</th>
                                <th class="px-4 py-3 text-center">Qty</th>
                                <th class="px-4 py-3 text-end">Unit Price</th>
                                <th class="px-4 py-3 text-end">Net Total</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <template x-for="item in cart" :key="item.id">
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width: 48px; height: 48px;">
                                                <template x-if="item.image_url">
                                                    <img :src="item.image_url" class="w-100 h-100" style="object-fit: cover;">
                                                </template>
                                                <template x-if="!item.image_url">
                                                    <i class="bi bi-box-seam text-muted opacity-50 fs-4"></i>
                                                </template>
                                            </div>
                                            <div>
                                                <p class="mb-0 fw-bold text-dark" x-text="item.name"></p>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <span class="font-monospace fw-bold text-primary text-uppercase" style="font-size: 10px; letter-spacing: -0.5px;" x-text="item.sku"></span>
                                                    <span class="text-muted">|</span>
                                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Brand Ref: <?php echo e($customer->brand ?? 'N/A'); ?></span>
                                                </div>
                                                <template x-if="item.discountValue > 0">
                                                    <div class="d-flex align-items-center gap-2 mt-1">
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 9px;"
                                                            x-text="(item.discountType === 'flat' ? '₹' : '') + Number(item.discountValue).toFixed(item.discountValue % 1 === 0 ? 0 : 2) + (item.discountType === 'flat' ? ' off' : '% off')">
                                                        </span>
                                                        <span class="text-muted fw-semibold" style="font-size: 9px;"
                                                            x-text="item.discountType === 'percent' 
                                                                ? '(Saved ₹' + Number(item.price * (item.discountValue / 100)).toFixed(2) + ' per unit × ' + item.quantity + ' = ₹' + Number(item.price * (item.discountValue / 100) * item.quantity).toFixed(2) + ')' 
                                                                : '(Saved ₹' + Number(item.discountValue).toFixed(2) + ' per unit × ' + item.quantity + ' = ₹' + Number(item.discountValue * item.quantity).toFixed(2) + ')'">
                                                        </span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="badge bg-light text-dark border px-3 py-2 fs-6" x-text="item.quantity"></span>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <span class="text-muted fw-bold" style="font-size: 12px;" x-text="'₹' + Number(item.price).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <span class="fw-bold text-dark fs-6" x-text="'₹' + Number(itemLineTotal(item)).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                
                <div class="card-footer bg-light p-4 p-md-5 border-top">
                    <div class="row g-5">
                        <div class="col-lg-7">
                            <div class="p-4 rounded-4 bg-white border shadow-sm d-flex gap-3 align-items-start">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                    <i class="bi bi-shield-check fs-5"></i>
                                </div>
                                <p class="mb-0 text-muted small fw-semibold">
                                    By confirming, you authorize inventory allocation and logistics protocol initiation for the specified destinations.
                                </p>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Gross Subtotal</span>
                                    <span class="fw-bold text-dark" x-text="'₹' + Number(subtotal).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                </div>
                                <template x-if="bogoDiscountTotal > 0">
                                    <div class="d-flex justify-content-between align-items-center text-success">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">BOGO Savings</span>
                                            <span class="text-muted fw-semibold" style="font-size: 9px;">Auto-applied backend offer</span>
                                        </div>
                                        <span class="fw-bold" x-text="'- ₹' + Number(bogoDiscountTotal).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </template>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex flex-column gap-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;"
                                                  :class="orderDiscountAmount > 0 ? 'text-success' : 'text-muted'">
                                                Order Offer
                                            </span>
                                            <button type="button" @click.prevent="isOffersModalOpen = true"
                                                class="btn btn-sm rounded-pill py-0 px-2 fw-bold text-uppercase" style="font-size: 8px; letter-spacing: 1px;"
                                                :class="orderDiscountAmount > 0
                                                    ? 'btn-outline-success'
                                                    : 'btn-outline-primary'"
                                                x-text="orderDiscountAmount > 0 ? 'Change' : 'Apply Offer'">
                                            </button>
                                        </div>
                                        <template x-if="orderDiscountAmount > 0">
                                            <span class="text-muted fw-semibold" style="font-size: 9px;" x-text="orderDiscountLabel"></span>
                                        </template>
                                        <template x-if="orderDiscountAmount === 0 && availableOrderOffers.length > 0">
                                            <span class="text-warning fw-semibold" style="font-size: 9px;"
                                                  x-text="availableOrderOffers.length + ' offer(s) available'"></span>
                                        </template>
                                    </div>
                                    <template x-if="orderDiscountAmount > 0">
                                        <span class="fw-bold text-success" x-text="'- ₹' + Number(orderDiscountAmount).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </template>
                                    <template x-if="orderDiscountAmount === 0">
                                        <span class="fw-bold text-muted opacity-50" style="font-size: 10px;">—</span>
                                    </template>
                                </div>
                                <template x-if="couponDiscount > 0">
                                    <div class="d-flex justify-content-between align-items-center text-success">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Coupon Savings</span>
                                            <span class="text-muted fw-semibold" style="font-size: 9px;" x-text="'(Code: ' + couponCode + ')'"></span>
                                        </div>
                                        <span class="fw-bold" x-text="'- ₹' + Number(couponDiscount).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </template>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Statutory Tax</span>
                                    <span class="fw-bold text-dark" x-text="'₹' + Number(taxAmount).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                </div>
                                
                                <div class="pt-4 mt-2 border-top d-flex flex-column gap-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 3px;">Final Payable</span>
                                        <span class="fs-2 fw-black text-primary lh-1" style="letter-spacing: -1px;" x-text="'₹' + Number(grandTotal).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    
                                    
                                    <form action="<?php echo e(route('customers.orders.place', $customer)); ?>" method="POST" class="w-100 m-0" x-data="{ isFutureOrder: false }">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="order_id" :value="editingOrderId" :disabled="!editingOrderId">
                                        <input type="hidden" name="cart" :value="JSON.stringify(cart)">
                                        <input type="hidden" name="applied_offer_id" :value="appliedOrderOfferId ?? ''">
                                        <input type="hidden" name="order_discount_amount" :value="orderDiscountAmount">
                                        <input type="hidden" name="coupon_code" :value="couponApplied ? couponCode : ''">
                                        <input type="hidden" name="coupon_discount" :value="couponDiscount">
                                        <input type="hidden" name="tax_amount" :value="taxAmount">
                                        <input type="hidden" name="subtotal" :value="subtotal">
                                        <input type="hidden" name="grand_total" :value="grandTotal">
                                        <input type="hidden" name="warehouse_id" :value="selectedWarehouseId">
                                        <input type="hidden" name="billing_address_id" :value="selectedBillingAddressId">
                                        <input type="hidden" name="address_id" :value="selectedShippingAddressId">
                                        <input type="hidden" name="is_draft" :value="isFutureOrder ? '1' : '0'">
                                        
                                        <div class="mb-4 p-3 rounded-4 border bg-white" x-show="!editingOrderId">
                                            <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                                        <i class="bi bi-clock"></i>
                                                    </div>
                                                    <div>
                                                        <label class="form-check-label fw-bold text-dark m-0" for="isFutureOrderSwitch">Place as Future Order</label>
                                                        <p class="mb-0 text-muted fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Save as draft for later</p>
                                                    </div>
                                                </div>
                                                <input class="form-check-input fs-4 m-0" type="checkbox" role="switch" id="isFutureOrderSwitch" x-model="isFutureOrder">
                                            </div>
                                            
                                            <div x-show="isFutureOrder" x-collapse>
                                                <div class="pt-3 mt-3 border-top">
                                                    <label class="form-label text-muted fw-bold text-uppercase d-flex align-items-center gap-2" style="font-size: 9px; letter-spacing: 1px;">
                                                        <i class="bi bi-calendar3"></i> Future Follow-up Date <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="date" name="future_order_date" min="<?php echo e(date('Y-m-d')); ?>" :required="isFutureOrder"
                                                        class="form-control form-control-lg border bg-light shadow-sm fw-semibold fs-6">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" 
                                            class="btn btn-lg w-100 rounded-pill fw-bold text-uppercase d-flex align-items-center justify-content-center gap-2 shadow-sm text-white transition-all"
                                            style="font-size: 12px; letter-spacing: 2px;"
                                            :class="editingOrderId ? 'btn-warning text-dark' : (isFutureOrder ? 'btn-success' : 'btn-primary')">
                                            <span x-show="!editingOrderId" x-text="isFutureOrder ? 'Place Future Order' : 'Place Order'"></span>
                                            <span x-show="editingOrderId">Update Existing Order</span>
                                            <i class="bi bi-lightning-charge-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.hover-border-secondary:hover { border-color: rgba(var(--bs-secondary-rgb), 0.5) !important; }
</style>
<?php /**PATH /home/ubuntu/metis/resources/views/customers/partials/tab-review.blade.php ENDPATH**/ ?>