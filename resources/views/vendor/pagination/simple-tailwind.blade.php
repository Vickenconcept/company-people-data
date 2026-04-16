@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-3">

        @if ($paginator->onFirstPage())
            <span class="inline-flex h-10 items-center rounded-2xl border border-violet-300 bg-slate-100 px-4 text-sm font-semibold text-slate-400 cursor-not-allowed">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-10 items-center rounded-2xl border border-violet-300 bg-white px-4 text-sm font-semibold text-violet-700 transition hover:bg-violet-50">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-10 items-center rounded-2xl border border-violet-300 bg-white px-4 text-sm font-semibold text-violet-700 transition hover:bg-violet-50">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="inline-flex h-10 items-center rounded-2xl border border-violet-300 bg-slate-100 px-4 text-sm font-semibold text-slate-400 cursor-not-allowed">
                {!! __('pagination.next') !!}
            </span>
        @endif

    </nav>
@endif
