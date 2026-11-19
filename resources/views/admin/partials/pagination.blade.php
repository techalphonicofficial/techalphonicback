@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3 text-[0.8125rem]" aria-label="Pagination">
        <p class="text-slate-500">Showing <span class="font-medium text-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span> of <span class="font-medium text-ink">{{ number_format($paginator->total()) }}</span></p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="a-btn a-btn-light a-btn-sm pointer-events-none opacity-50"><x-icon name="chevron-right" class="size-3.5 rotate-180" /> Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="a-btn a-btn-light a-btn-sm"><x-icon name="chevron-right" class="size-3.5 rotate-180" /> Prev</a>
            @endif
            <div class="hidden items-center gap-1 sm:flex">
                @foreach ($elements as $element)
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="grid size-7 place-items-center rounded-md bg-ink text-xs font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="grid size-7 place-items-center rounded-md text-xs font-medium text-slate-600 transition hover:bg-white hover:text-ink hover:shadow-[var(--shadow-xs)] hover:ring-1 hover:ring-line">{{ $page }}</a>
                            @endif
                        @endforeach
                    @elseif (is_string($element))
                        <span class="px-1 text-slate-400">…</span>
                    @endif
                @endforeach
            </div>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="a-btn a-btn-light a-btn-sm">Next <x-icon name="chevron-right" class="size-3.5" /></a>
            @else
                <span class="a-btn a-btn-light a-btn-sm pointer-events-none opacity-50">Next <x-icon name="chevron-right" class="size-3.5" /></span>
            @endif
        </div>
    </nav>
@endif
