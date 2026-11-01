@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp
<th {{ $attributes->except('class')->merge(['class' => 'h-12 px-4 text-left align-middle font-black uppercase text-[10px] tracking-widest text-muted-foreground/70 [&:has([role=checkbox])]:pr-0 ' . $extraClass]) }}>
    {{ $slot }}
</th>
