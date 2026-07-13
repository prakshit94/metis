<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['id', 'maxWidth' => '2xl']));

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

foreach (array_filter((['id', 'maxWidth' => '2xl']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    '6xl' => 'sm:max-w-6xl',
    '7xl' => 'sm:max-w-7xl',
][$maxWidth];
?>

<div
    x-data="{ show: false }"
    x-on:open-modal.window="if ($event.detail.name == '<?php echo e($id); ?>') show = true"
    x-on:close-modal.window="if ($event.detail.name == '<?php echo e($id); ?>') show = false"
    x-on:keydown.escape.window="show = false"
    class="relative"
>
    <template x-teleport="body">
        <div
            x-show="show"
            x-cloak
            class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-[1000]"
        >
            <!-- Backdrop -->
            <div 
                x-show="show" 
                class="fixed inset-0 transform transition-all" 
                x-on:click="show = false" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            >
                <div class="absolute inset-0 bg-background/80 backdrop-blur-sm"></div>
            </div>

            <!-- Content -->
            <div 
                x-show="show" 
                class="relative z-10 mb-6 bg-card text-card-foreground rounded-[32px] overflow-hidden shadow-2xl transform transition-all sm:w-full <?php echo e($maxWidth); ?> sm:mx-auto border border-border/70"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <?php echo e($slot); ?>

            </div>
        </div>
    </template>
</div>
<?php /**PATH /home/ubuntu/metis/resources/views/components/ui/modal.blade.php ENDPATH**/ ?>