@props([
    'title' => 'No results found',
    'description' => 'Try adjusting your search or filters to find what you\'re looking for.',
    'icon' => null,
    'className' => '',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-12 text-center ' . $className]) }}>
    <div class="size-20 rounded-3xl bg-secondary/30 flex items-center justify-center text-muted-foreground mb-6 shadow-inner">
        @if($icon)
            {!! $icon !!}
        @else
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-10"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
        @endif
    </div>
    <h3 class="text-lg font-bold text-foreground">{{ $title }}</h3>
    <p class="text-sm text-muted-foreground max-w-xs mt-2 leading-relaxed">
        {{ $description }}
    </p>
    @if($slot->isNotEmpty())
        <div class="mt-8">
            {{ $slot }}
        </div>
    @endif
</div>
