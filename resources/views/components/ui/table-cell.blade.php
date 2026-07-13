@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp
<td {{ $attributes->except('class')->merge(['class' => 'p-4 align-middle [&:has([role=checkbox])]:pr-0 ' . $extraClass]) }}>
    {{ $slot }}
</td>
