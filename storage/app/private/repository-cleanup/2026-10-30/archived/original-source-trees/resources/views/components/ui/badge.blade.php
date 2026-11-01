@props([
    'variant' => 'default',
    'className' => '',
])

@php
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
@endphp

<div {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
