@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp
<div {{ $attributes->except('class')->merge(['class' => 'p-6 pt-0 ' . $extraClass]) }}>
    {{ $slot }}
</div>
