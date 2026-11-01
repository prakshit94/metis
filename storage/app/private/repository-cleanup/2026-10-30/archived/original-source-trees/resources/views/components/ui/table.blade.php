@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp
<div class="relative w-full overflow-auto custom-scrollbar">
    <table {{ $attributes->except('class')->merge(['class' => 'w-full caption-bottom text-sm ' . $extraClass]) }}>
        {{ $slot }}
    </table>
</div>
