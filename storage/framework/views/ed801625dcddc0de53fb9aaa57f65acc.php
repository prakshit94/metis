<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => 'No results found',
    'description' => 'Try adjusting your search or filters to find what you\'re looking for.',
    'icon' => null,
    'className' => '',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title' => 'No results found',
    'description' => 'Try adjusting your search or filters to find what you\'re looking for.',
    'icon' => null,
    'className' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div <?php echo e($attributes->merge(['class' => 'flex flex-col items-center justify-center p-12 text-center ' . $className])); ?>>
    <div class="size-20 rounded-3xl bg-secondary/30 flex items-center justify-center text-muted-foreground mb-6 shadow-inner">
        <?php if($icon): ?>
            <?php echo $icon; ?>

        <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-10"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
        <?php endif; ?>
    </div>
    <h3 class="text-lg font-bold text-foreground"><?php echo e($title); ?></h3>
    <p class="text-sm text-muted-foreground max-w-xs mt-2 leading-relaxed">
        <?php echo e($description); ?>

    </p>
    <?php if($slot->isNotEmpty()): ?>
        <div class="mt-8">
            <?php echo e($slot); ?>

        </div>
    <?php endif; ?>
</div>
<?php /**PATH /home/user/metis/resources/views/components/ui/empty-state.blade.php ENDPATH**/ ?>