<?php

namespace App\Livewire\Admin;

use App\Enums\RoleSlug;
use App\Livewire\Concerns\AuthorizesAbility;
use App\Livewire\Concerns\WithTableState;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class UserTable extends Component
{
    use AuthorizesAbility, WithTableState;

    #[Url(as: 'role', except: '')]
    public string $role = '';

    #[Url(as: 'etat', except: '')]
    public string $active = '';

    protected function ability(): string
    {
        return 'customers.view';
    }

    protected function sortable(): array
    {
        return ['name', 'created_at', 'orders_count', 'revenue'];
    }

    public function mount(): void
    {
        $this->role = RoleSlug::tryFrom($this->role ?: (string) request()->query('role', ''))?->value ?? '';
    }

    public function render(): View
    {
        return view('livewire.admin.user-table', [
            'users' => User::query()->with('role')
                ->withCount('orders')
                ->withSum(['orders as revenue' => fn ($q) => $q->revenue()], 'total')
                ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->whereLike('name', $this->like(), caseSensitive: false)
                    ->orWhereLike('email', $this->like(), caseSensitive: false)
                    ->orWhereLike('phone', $this->like(), caseSensitive: false)))
                ->when(RoleSlug::tryFrom($this->role), fn ($q, $r) => $q->whereHas('role', fn ($w) => $w->where('slug', $r)))
                ->when(in_array($this->active, ['1', '0'], true), fn ($q) => $q->where('is_active', $this->active === '1'))
                ->tap(fn ($q) => $this->applySort($q))
                ->paginate(20),
        ]);
    }
}
