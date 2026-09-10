@props(['paginator', 'className' => ''])

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="d-flex align-items-center justify-content-between flex-wrap gap-2 {{ $className }}">
        {{-- Mobile: Prev/Next only --}}
        <div class="d-flex d-sm-none gap-2">
            @if ($paginator->onFirstPage())
                <span class="btn btn-sm btn-outline-secondary disabled rounded-pill px-3 fw-semibold">{{ __('Previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">{{ __('Previous') }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">{{ __('Next') }}</a>
            @else
                <span class="btn btn-sm btn-outline-secondary disabled rounded-pill px-3 fw-semibold">{{ __('Next') }}</span>
            @endif
        </div>

        {{-- Desktop: Full pagination --}}
        <div class="d-none d-sm-flex align-items-center justify-content-between w-100 flex-wrap gap-2">
            {{-- Info text --}}
            <p class="text-muted small fw-semibold mb-0">
                Showing
                <span class="fw-bold text-body">{{ $paginator->firstItem() }}</span>
                to
                <span class="fw-bold text-body">{{ $paginator->lastItem() }}</span>
                of
                <span class="fw-bold text-body">{{ $paginator->total() }}</span>
                results
            </p>

            {{-- Page links --}}
            <ul class="pagination pagination-sm mb-0 gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <span class="page-link rounded-pill border-0 bg-body-secondary text-muted" aria-hidden="true">
                            <i class="bi bi-chevron-left" style="font-size: 11px;"></i>
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="page-link rounded-pill border-0 bg-body-secondary text-body fw-semibold" aria-label="{{ __('pagination.previous') }}">
                            <i class="bi bi-chevron-left" style="font-size: 11px;"></i>
                        </a>
                    </li>
                @endif

                {{-- Page Numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link border-0 bg-transparent text-muted">{{ $element }}</span>
                        </li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link rounded-pill border-0 fw-bold shadow-sm">{{ $page }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a href="{{ $url }}" class="page-link rounded-pill border-0 bg-body-secondary text-body fw-semibold" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="page-link rounded-pill border-0 bg-body-secondary text-body fw-semibold" aria-label="{{ __('pagination.next') }}">
                            <i class="bi bi-chevron-right" style="font-size: 11px;"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <span class="page-link rounded-pill border-0 bg-body-secondary text-muted" aria-hidden="true">
                            <i class="bi bi-chevron-right" style="font-size: 11px;"></i>
                        </span>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
@endif
