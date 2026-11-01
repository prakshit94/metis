<div x-data="{ open: false }" 
     class="relative inline-block"
     @mouseenter="open = true" 
     @mouseleave="open = false"
>
    {{ $slot }}

    <div x-show="open" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         class="absolute z-[100] px-3 py-1.5 text-xs font-bold text-popover-foreground bg-popover border border-border/60 rounded-lg shadow-xl whitespace-nowrap pointer-events-none -translate-x-1/2 left-1/2 bottom-full mb-2"
    >
        {{ $content }}
        <!-- Arrow -->
        <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-popover"></div>
    </div>
</div>
