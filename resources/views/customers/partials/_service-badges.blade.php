{{--
    ── Service Availability Badges ──────────────────────────────────────
    Expects: $addrModel  (a PartyAddress instance with village.services
             already eager-loaded via addresses.village.services)

    Displays compact colour-coded pills for every active service that is
    flagged as available (pivot.is_available) at the address's village.
    If the address has no linked village, or no services are available,
    nothing is rendered — so this partial is completely safe to include
    anywhere without side-effects on the surrounding layout.
--}}
@php
    $availableServices = ($addrModel->village && $addrModel->village->relationLoaded('services'))
        ? $addrModel->village->services->filter(
            fn($s) => (bool) $s->pivot->is_available && (bool) $s->is_active
          )
        : collect();

    /* Colour palette */
    $palette = [
        ['bg' => 'bg-success',  'text' => 'text-success'],
        ['bg' => 'bg-primary',  'text' => 'text-primary'],
        ['bg' => 'bg-info',     'text' => 'text-info'],
        ['bg' => 'bg-warning',  'text' => 'text-warning'],
        ['bg' => 'bg-danger',   'text' => 'text-danger'],
        ['bg' => 'bg-secondary','text' => 'text-secondary'],
        ['bg' => 'bg-dark',     'text' => 'text-dark'],
    ];
@endphp

@if($availableServices->count())
    <div class="mt-3 pt-3 border-top">
        <span class="text-muted fw-bold text-uppercase d-block mb-2" style="font-size: 9px; letter-spacing: 1px;">
            Services Available
        </span>
        <div class="d-flex flex-wrap gap-2">
            @foreach($availableServices->values() as $idx => $svc)
                @php $clr = $palette[$idx % count($palette)]; @endphp
                <span
                    class="badge {{ $clr['bg'] }} bg-opacity-10 {{ $clr['text'] }} border border-{{ str_replace('bg-', '', $clr['bg']) }} border-opacity-25 d-flex align-items-center gap-1 fw-bold text-uppercase"
                    style="font-size: 9px; letter-spacing: 0.5px;"
                    title="{{ $svc->description ?: $svc->name }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round"
                         width="10" height="10" class="flex-shrink-0">
                        <polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                    {{ $svc->name }}
                </span>
            @endforeach
        </div>
    </div>
@endif
