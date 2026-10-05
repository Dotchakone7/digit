@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4">
        <p class="hidden text-sm text-zinc-500 sm:block">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }}
        </p>
        <div class="flex flex-1 items-center justify-center gap-1 sm:flex-none">
            @if ($paginator->onFirstPage())
                <span class="btn-icon cursor-not-allowed text-zinc-300" aria-disabled="true"><x-icon name="chevron-left" class="size-4" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-icon" aria-label="Page précédente"><x-icon name="chevron-left" class="size-4" /></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-sm text-zinc-400">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="grid size-10 place-items-center rounded-full bg-brand-900 text-sm font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="grid size-10 place-items-center rounded-full text-sm font-medium text-zinc-600 transition hover:bg-zinc-100" aria-label="Page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-icon" aria-label="Page suivante"><x-icon name="chevron-right" class="size-4" /></a>
            @else
                <span class="btn-icon cursor-not-allowed text-zinc-300" aria-disabled="true"><x-icon name="chevron-right" class="size-4" /></span>
            @endif
        </div>
    </nav>
@endif
