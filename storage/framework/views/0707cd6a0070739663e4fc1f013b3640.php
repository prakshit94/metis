
<div x-show="activeTab === 'history'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
    <?php $customer->orders?->loadMissing(['items.product', 'creator', 'updater', 'shipments', 'billingAddress', 'shippingAddress', 'warehouse']); ?>
    <script>
        window.customerOrders_<?php echo e($customer->id); ?> = <?php echo json_encode($customer->orders, 15, 512) ?>;
    </script>
    <div x-data="{ expandedOrder: null }" class="d-flex flex-column gap-4">
        <?php if(isset($customer->orders) && $customer->orders->count()): ?>
            <?php $__currentLoopData = $customer->orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden transition-all">
                    
                    
                    <div @click="expandedOrder = expandedOrder === <?php echo e($order->id); ?> ? null : <?php echo e($order->id); ?>" class="card-body p-4 cursor-pointer hover-bg-light d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                        <div class="d-flex align-items-center gap-4">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="bi bi-box-seam fs-4"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h3 class="h5 fw-bold mb-0 text-dark"><?php echo e($order->order_no); ?></h3>
                                    <?php
                                        $statusColor = match($order->lifecycleStatus()) {
                                            'delivered' => 'success',
                                            'dispatched' => 'primary',
                                            'ready_to_ship' => 'indigo',
                                            'processing' => 'warning',
                                            'confirmed' => 'info',
                                            'cancelled', 'returned', 'return_requested' => 'danger',
                                            'pending' => 'secondary',
                                            'future_order' => 'dark',
                                            default => 'primary'
                                        };
                                    ?>
                                    <span class="badge bg-<?php echo e($statusColor); ?>-subtle text-<?php echo e($statusColor); ?> border border-<?php echo e($statusColor); ?>-subtle text-uppercase fw-bold" style="font-size: 9px; letter-spacing: 1px;">
                                        <?php echo e($order->statusLabel()); ?>

                                    </span>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2 text-muted fw-semibold" style="font-size: 11px;">
                                    <span><i class="bi bi-calendar3"></i> <?php echo e($order->created_at->format('M d, Y h:i A')); ?></span>
                                    <?php if($order->creator): ?>
                                        <i class="bi bi-dot"></i>
                                        <span><i class="bi bi-person"></i> <?php echo e($order->creator->name); ?></span>
                                    <?php endif; ?>
                                    <?php if($order->updater && $order->updated_by !== $order->created_by): ?>
                                        <i class="bi bi-dot"></i>
                                        <span class="text-warning"><i class="bi bi-pencil"></i> <?php echo e($order->updater->name); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-4">
                            <div class="text-end">
                                <p class="mb-0 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Order Total</p>
                                <p class="mb-0 fw-black text-dark fs-5">Rs <?php echo e(number_format($order->net_amount, 2)); ?></p>
                            </div>
                            <div class="bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center transition-all" style="width: 32px; height: 32px;" :class="expandedOrder === <?php echo e($order->id); ?> ? 'bg-secondary text-white' : ''">
                                <i class="bi bi-chevron-down transition-transform" :class="expandedOrder === <?php echo e($order->id); ?> ? 'rotate-180' : ''"></i>
                            </div>
                        </div>
                    </div>

                    
                    <div x-show="expandedOrder === <?php echo e($order->id); ?>" x-collapse x-cloak>
                        <div class="card-body p-4 p-md-5 border-top bg-light bg-opacity-50 d-flex flex-column gap-4">
                            
                            
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="button" @click="$dispatch('edit-order', <?php echo e($order->id); ?>)" class="btn btn-sm btn-outline-warning fw-bold text-uppercase d-flex align-items-center gap-2 shadow-sm rounded-pill px-3" style="font-size: 10px; letter-spacing: 1px;">
                                    <i class="bi bi-pencil-square"></i> Edit Order
                                </button>
                                <a href="<?php echo e(route('orders.receipt', $order->id)); ?>" class="btn btn-sm btn-light border fw-bold text-uppercase d-flex align-items-center gap-2 shadow-sm rounded-pill px-3 text-dark" style="font-size: 10px; letter-spacing: 1px;">
                                    <i class="bi bi-file-earmark-text"></i> View Receipt
                                </a>
                                <button type="button" @click="window.print()" class="btn btn-sm btn-primary fw-bold text-uppercase d-flex align-items-center gap-2 shadow-sm rounded-pill px-3" style="font-size: 10px; letter-spacing: 1px;">
                                    <i class="bi bi-printer"></i> Print
                                </button>
                            </div>

                            
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-3">
                                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                    <h4 class="mb-0 fw-bold text-dark fs-6">Order Items</h4>
                                    <span class="badge bg-light text-secondary border px-2 py-1"><?php echo e(isset($order->items) ? $order->items->count() : 0); ?> items</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 12px;">
                                        <thead class="table-light text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">
                                            <tr>
                                                <th class="py-3 px-4">Product</th>
                                                <th class="py-3 px-4 text-end">Price</th>
                                                <th class="py-3 px-4 text-center">Qty</th>
                                                <th class="py-3 px-4 text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="border-top-0">
                                            <?php if(isset($order->items)): ?>
                                                <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr>
                                                        <td class="px-4 py-3">
                                                            <p class="mb-0 fw-bold text-dark"><?php echo e($item->product?->name ?? 'Unknown Product'); ?></p>
                                                            <p class="mb-0 text-muted font-monospace" style="font-size: 10px;"><?php echo e($item->product?->sku ?? 'N/A'); ?></p>
                                                        </td>
                                                        <td class="px-4 py-3 text-end text-muted fw-semibold">
                                                            Rs <?php echo e(number_format($item->unit_price ?? 0, 2)); ?>

                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <span class="badge bg-light text-dark border"><?php echo e($item->quantity ?? 1); ?></span>
                                                        </td>
                                                        <td class="px-4 py-3 text-end fw-bold text-dark fs-6">
                                                            Rs <?php echo e(number_format((($item->unit_price ?? 0) * ($item->quantity ?? 1)) - ($item->discount_amount ?? 0), 2)); ?>

                                                        </td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <div class="text-center py-5 px-4 rounded-4 border border-2 border-dashed bg-light">
                <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="bi bi-clock-history fs-1"></i>
                </div>
                <h4 class="h5 fw-bold text-dark">No Order History</h4>
                <p class="text-muted small mx-auto" style="max-width: 400px;">This customer hasn't placed any orders yet.</p>
                <button type="button" @click="activeTab = 'order'" class="btn btn-primary rounded-pill px-4 mt-3 fw-bold text-uppercase shadow-sm" style="font-size: 11px; letter-spacing: 1px;">
                    <i class="bi bi-cart me-1"></i> Place an Order
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
<style>
.rotate-180 { transform: rotate(180deg); }
.cursor-pointer { cursor: pointer; }
.hover-bg-light:hover { background-color: rgba(var(--bs-light-rgb), 0.5) !important; }
</style>
<?php /**PATH /home/ubuntu/metis/resources/views/customers/partials/tab-history.blade.php ENDPATH**/ ?>