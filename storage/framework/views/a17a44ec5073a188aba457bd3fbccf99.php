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
<div class="relative w-full overflow-auto custom-scrollbar">
    <table <?php echo e($attributes->except('class')->merge(['class' => 'w-full caption-bottom text-sm ' . $extraClass])); ?>>
        <?php echo e($slot); ?>

    </table>
</div>
<?php /**PATH /home/user/metis/resources/views/components/ui/table.blade.php ENDPATH**/ ?>