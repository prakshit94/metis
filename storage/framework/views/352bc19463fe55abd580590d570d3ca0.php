<?php $__env->startSection('title', 'Customer Profile: ' . $customer->name); ?>
<?php $__env->startSection('page', 'customers.show'); ?>

<?php $__env->startPush('head'); ?>
    <style>
        .profile-nav .nav-link {
            color: var(--bs-body-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }
        .profile-nav .nav-link i {
            font-size: 1rem;
        }
        .profile-nav .nav-link:hover {
            background-color: var(--bs-tertiary-bg);
        }
        .profile-nav .nav-link.active {
            background-color: var(--bs-primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
        }
        .profile-nav .nav-link.active .chevron-right {
            display: block !important;
            margin-left: auto;
        }
        .profile-nav .nav-link .chevron-right {
            display: none;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('customers.partials.scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div x-data="<?php echo $__env->make('customers.partials.alpine-state', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>">
        
        <?php echo $__env->make('customers.partials.header_top', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div class="container-fluid p-4 px-lg-5 pb-5">
            <div class="row g-4">
                
                
                <aside class="col-xl-3 col-lg-4">
                    <div class="sticky-top" style="top: 100px;">
                        <div class="card border-0 shadow-sm rounded-4 mb-3">
                            <div class="card-body p-3">
                                <nav class="nav flex-column profile-nav">
                                    <template x-for="tab in [
                                        { id: 'overview', icon: 'bi-person',         label: 'Profile'        },
                                        { id: 'addresses',icon: 'bi-geo-alt',      label: 'Addresses'      },
                                        { id: 'order',    icon: 'bi-bag', label: 'Order Products' },
                                        { id: 'history',  icon: 'bi-clock-history',        label: 'Order History'  },
                                        { id: 'finance',  icon: 'bi-hash',         label: 'Finance'        },
                                        { id: 'system',   icon: 'bi-gear',     label: 'System'         },
                                        { id: 'review',   icon: 'bi-check2-square', label: 'Order Review'   },
                                        { id: 'close',    icon: 'bi-x-circle',     label: 'Tag & Close Profile' }
                                    ].filter(t => t.id !== 'review' || activeTab === 'review')" :key="tab.id">
                                        <button
                                            type="button"
                                            @click="tab.id === 'close' ? closeCustomerProfile() : activeTab = tab.id"
                                            :class="{'nav-link': true, 'active': activeTab === tab.id}"
                                        >
                                            <i :class="'bi ' + tab.icon"></i>
                                            <span x-text="tab.label"></span>
                                            <i class="bi bi-chevron-right chevron-right"></i>
                                        </button>
                                    </template>
                                </nav>
                            </div>
                        </div>

                        
                        <div class="card border border-primary border-opacity-10 bg-primary bg-opacity-10 rounded-4 d-none d-lg-block">
                            <div class="card-body p-4">
                                <p class="mb-1 text-primary fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Customer Since</p>
                                <p class="mb-3 fw-bold text-dark fs-6"><?php echo e($customer->created_at->format('M Y')); ?></p>
                                <div class="pt-3 border-top border-primary border-opacity-10">
                                    <p class="mb-1 text-primary fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Loyalty Points</p>
                                    <p class="mb-0 fw-black text-dark fs-4">1,250</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                
                <main class="col-xl-9 col-lg-8">
                    <?php echo $__env->make('customers.partials.tab-overview', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('customers.partials.tab-addresses', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('customers.partials.tab-order', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('customers.partials.tab-history', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('customers.partials.tab-finance', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('customers.partials.tab-system', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('customers.partials.tab-review', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </main>
            </div>
        </div>

        <?php echo $__env->make('customers.partials.cart-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('customers.partials.modals', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/ubuntu/metis/resources/views/customers/show.blade.php ENDPATH**/ ?>