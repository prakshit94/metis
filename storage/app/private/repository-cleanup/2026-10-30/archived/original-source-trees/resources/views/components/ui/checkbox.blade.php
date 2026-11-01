@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp

<input type="checkbox" {{ $attributes->except('class')->merge(['class' => 'size-4 rounded-lg border-input bg-background text-primary ring-offset-background focus:ring-2 focus:ring-primary/20 focus:ring-offset-0 disabled:cursor-not-allowed disabled:opacity-50 transition-all ' . $extraClass]) }}>
