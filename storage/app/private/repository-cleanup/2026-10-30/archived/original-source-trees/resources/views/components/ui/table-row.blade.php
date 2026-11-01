@props(['className' => ''])
@php
    $extraClass = trim($className . ' ' . ($attributes->get('class') ?? ''));
@endphp
<tr {{ $attributes->except('class')->merge(['class' => 'border-b border-border/40 transition-colors hover:bg-muted/10 data-[state=selected]:bg-muted/30 ' . $extraClass]) }}>
    {{ $slot }}
</tr>
