{{-- Pagination for Livewire components: changes page without reloading. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4">
        <p class="hidden text-sm text-zinc-500 sm:block">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }}</p>
        <div class="flex flex-1 items-center justify-center gap-1 sm:flex-none">
            <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="$el.closest('[wire\\:id]')?.scrollIntoView({ behavior: 'smooth' })" @disabled($paginator->onFirstPage()) class="btn-icon disabled:cursor-not-allowed disabled:text-zinc-300" aria-label="Page précédente"><x-icon name="chevron-left" class="size-4" /></button>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-sm text-zinc-400">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" wire:key="page-{{ $page }}" class="grid size-10 place-items-center rounded-full bg-brand-900 text-sm font-semibold text-white">{{ $page }}</span>
                        @else
                            <button type="button" wire:key="page-{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="$el.closest('[wire\\:id]')?.scrollIntoView({ behavior: 'smooth' })" class="grid size-10 place-items-center rounded-full text-sm font-medium text-zinc-600 transition hover:bg-zinc-100" aria-label="Page {{ $page }}">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach
            <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="$el.closest('[wire\\:id]')?.scrollIntoView({ behavior: 'smooth' })" @disabled(! $paginator->hasMorePages()) class="btn-icon disabled:cursor-not-allowed disabled:text-zinc-300" aria-label="Page suivante"><x-icon name="chevron-right" class="size-4" /></button>
        </div>
    </nav>
@endif
