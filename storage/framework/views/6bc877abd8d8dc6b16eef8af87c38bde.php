<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'default',
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
    'variant' => 'default',
    'className' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $variants = [
        'default' => 'bg-primary text-primary-foreground',
        'secondary' => 'bg-secondary text-secondary-foreground',
        'destructive' => 'bg-destructive text-destructive-foreground',
        'outline' => 'text-foreground border border-input bg-background hover:bg-accent hover:text-accent-foreground',
        'success' => 'bg-emerald-500 text-white',
        'warning' => 'bg-amber-500 text-white',
        'info' => 'bg-blue-500 text-white',
    ];

    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
    $classes = "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:ring-offset-0 " . ($variants[$variant] ?? $variants['default']) . " " . $extraClass;
?>

<div <?php echo e($attributes->except('class')->merge(['class' => $classes])); ?>>
    <?php echo e($slot); ?>

</div>
<?php /**PATH /home/user/metis/resources/views/components/ui/badge.blade.php ENDPATH**/ ?>