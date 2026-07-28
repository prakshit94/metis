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
<th <?php echo e($attributes->except('class')->merge(['class' => 'h-12 px-4 text-left align-middle font-black uppercase text-[10px] tracking-widest text-muted-foreground/70 [&:has([role=checkbox])]:pr-0 ' . $extraClass])); ?>>
    <?php echo e($slot); ?>

</th>
<?php /**PATH /home/user/metis/resources/views/components/ui/table-head.blade.php ENDPATH**/ ?>