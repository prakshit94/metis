@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp
<div {{ $attributes->except('class')->merge(['class' => 'rounded-2xl border border-border/60 bg-card/40 text-card-foreground shadow-xl backdrop-blur-xl ' . $extraClass]) }}>
    {{ $slot }}
</div>
