
<?php
    $availableServices = ($addrModel->village && $addrModel->village->relationLoaded('services'))
        ? $addrModel->village->services->filter(
            fn($s) => (bool) $s->pivot->is_available && (bool) $s->is_active
          )
        : collect();

    /* Colour palette */
    $palette = [
        ['bg' => 'bg-success',  'text' => 'text-success'],
        ['bg' => 'bg-primary',  'text' => 'text-primary'],
        ['bg' => 'bg-info',     'text' => 'text-info'],
        ['bg' => 'bg-warning',  'text' => 'text-warning'],
        ['bg' => 'bg-danger',   'text' => 'text-danger'],
        ['bg' => 'bg-secondary','text' => 'text-secondary'],
        ['bg' => 'bg-dark',     'text' => 'text-dark'],
    ];
?>

<?php if($availableServices->count()): ?>
    <div class="mt-3 pt-3 border-top">
        <span class="text-muted fw-bold text-uppercase d-block mb-2" style="font-size: 9px; letter-spacing: 1px;">
            Services Available
        </span>
        <div class="d-flex flex-wrap gap-2">
            <?php $__currentLoopData = $availableServices->values(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $svc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $clr = $palette[$idx % count($palette)]; ?>
                <span
                    class="badge <?php echo e($clr['bg']); ?> bg-opacity-10 <?php echo e($clr['text']); ?> border border-<?php echo e(str_replace('bg-', '', $clr['bg'])); ?> border-opacity-25 d-flex align-items-center gap-1 fw-bold text-uppercase"
                    style="font-size: 9px; letter-spacing: 0.5px;"
                    title="<?php echo e($svc->description ?: $svc->name); ?>"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round"
                         width="10" height="10" class="flex-shrink-0">
                        <polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                    <?php echo e($svc->name); ?>

                </span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /home/user/metis/resources/views/customers/partials/_service-badges.blade.php ENDPATH**/ ?>