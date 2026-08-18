@props(['orientation' => 'horizontal', 'className' => ''])
@php
    $classes = $orientation === 'vertical'
        ? 'shrink-0 bg-border w-px h-full'
        : 'shrink-0 bg-border h-px w-full';
@endphp
<div role="separator" {{ $attributes->merge(['class' => $classes . ' ' . $className]) }}></div>
