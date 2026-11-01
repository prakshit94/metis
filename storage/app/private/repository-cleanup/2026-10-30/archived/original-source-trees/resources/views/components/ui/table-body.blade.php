@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp
<tbody {{ $attributes->except('class')->merge(['class' => '[&_tr:last-child]:border-0 ' . $extraClass]) }}>
    {{ $slot }}
</tbody>
