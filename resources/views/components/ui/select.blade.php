@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp

<div class="relative group">
    <select {{ $attributes->except('class')->merge(['class' => 'flex h-10 w-full rounded-xl border border-input bg-background/50 px-4 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:ring-offset-0 disabled:cursor-not-allowed disabled:opacity-50 transition-all appearance-none ' . $extraClass]) }}>
        {{ $slot }}
    </select>
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted-foreground">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="m6 9 12 12 6-6"/></svg>
    </div>
</div>
