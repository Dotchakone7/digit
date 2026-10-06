<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/** Live search, column sorting and pagination, all reflected in the URL. */
trait WithTableState
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'tri', except: '')]
    public string $sortField = '';

    #[Url(as: 'ordre', except: 'desc')]
    public string $sortDirection = 'desc';

    /** @return list<string> columns the user may sort by */
    abstract protected function sortable(): array;

    /** Any filter or search change goes back to page 1. */
    public function updated(string $property): void
    {
        if (! in_array($property, ['sortField', 'sortDirection', 'paginators'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->sortable(), true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
    }

    protected function applySort(Builder $query, string $default = 'id'): Builder
    {
        $field = in_array($this->sortField, $this->sortable(), true) ? $this->sortField : null;
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return $field ? $query->orderBy($field, $direction)->orderBy('id', 'desc') : $query->latest($default);
    }

    protected function like(): string
    {
        return '%'.addcslashes(trim(mb_substr($this->search, 0, 100)), '%_\\').'%';
    }

    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }

    public function paginationView(): string
    {
        return 'livewire.pagination';
    }
}
