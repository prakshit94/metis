<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['className' => '']));

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

foreach (array_filter((['className' => '']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
?>

<input type="checkbox" <?php echo e($attributes->except('class')->merge(['class' => 'size-4 rounded-lg border-input bg-background text-primary ring-offset-background focus:ring-2 focus:ring-primary/20 focus:ring-offset-0 disabled:cursor-not-allowed disabled:opacity-50 transition-all ' . $extraClass])); ?>>
<?php /**PATH /home/user/metis/resources/views/components/ui/checkbox.blade.php ENDPATH**/ ?>