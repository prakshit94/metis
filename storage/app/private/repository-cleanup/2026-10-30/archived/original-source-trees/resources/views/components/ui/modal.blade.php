@props(['id', 'maxWidth' => '2xl'])

@php
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
@endphp

<div
    x-data="{ show: false }"
    x-on:open-modal.window="if ($event.detail.name == '{{ $id }}') show = true"
    x-on:close-modal.window="if ($event.detail.name == '{{ $id }}') show = false"
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
                class="relative z-10 mb-6 bg-card text-card-foreground rounded-[32px] overflow-hidden shadow-2xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto border border-border/70"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                {{ $slot }}
            </div>
        </div>
    </template>
</div>
