@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp
<thead {{ $attributes->except('class')->merge(['class' => '[&_tr]:border-b bg-muted/20 ' . $extraClass]) }}>
    {{ $slot }}
</thead>
