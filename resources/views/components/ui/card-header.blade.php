@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp
<div {{ $attributes->except('class')->merge(['class' => 'flex flex-col space-y-1.5 p-6 ' . $extraClass]) }}>
    {{ $slot }}
</div>
