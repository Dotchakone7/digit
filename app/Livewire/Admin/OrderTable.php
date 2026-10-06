<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Concerns\AuthorizesAbility;
use App\Livewire\Concerns\WithTableState;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Orders list refreshed every few seconds: new orders appear by themselves
 * and trigger a notification, without reloading the page.
 */
class OrderTable extends Component
{
    use AuthorizesAbility, WithTableState;

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    #[Url(as: 'paiement', except: '')]
    public string $paymentStatus = '';

    #[Url(as: 'du', except: '')]
    public string $from = '';

    #[Url(as: 'au', except: '')]
    public string $to = '';

    #[Locked]
    public int $latestId = 0;

    protected function ability(): string
    {
        return 'orders.manage';
    }

    protected function sortable(): array
    {
        return ['created_at', 'total'];
    }

    public function mount(): void
    {
        $this->status = OrderStatus::tryFrom($this->status)?->value ?? (string) request()->query('status', '');
        $this->latestId = (int) Order::query()->max('id');
    }

    public function setStatus(string $status): void
    {
        $this->status = OrderStatus::tryFrom($status)?->value ?? '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'paymentStatus', 'from', 'to');
        $this->resetPage();
    }

    public function render(): View
    {
        $this->announceNewOrders();

        return view('livewire.admin.order-table', [
            'orders' => $this->query()->withCount('items')->paginate(20),
            'statusCounts' => Order::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'exportQuery' => array_filter([
                'q' => $this->search, 'status' => $this->status, 'payment_status' => $this->paymentStatus,
                'from' => $this->from, 'to' => $this->to,
            ]),
        ]);
    }

    private function announceNewOrders(): void
    {
        $latest = (int) Order::query()->max('id');

        if ($latest > $this->latestId) {
            $count = Order::query()->where('id', '>', $this->latestId)->count();
            $this->dispatch('toast', message: $count > 1 ? "{$count} nouvelles commandes !" : 'Nouvelle commande reçue !', type: 'info');
            $this->latestId = $latest;
        }
    }

    private function query(): Builder
    {
        $date = fn (string $value) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;

        return Order::query()
            ->when(trim($this->search) !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereLike('number', $this->like(), caseSensitive: false)
                ->orWhereLike('customer_name', $this->like(), caseSensitive: false)
                ->orWhereLike('customer_email', $this->like(), caseSensitive: false)
                ->orWhereLike('customer_phone', $this->like(), caseSensitive: false)))
            ->when(OrderStatus::tryFrom($this->status), fn (Builder $q, $s) => $q->where('status', $s))
            ->when(PaymentStatus::tryFrom($this->paymentStatus), fn (Builder $q, $s) => $q->where('payment_status', $s))
            ->when($date($this->from), fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($date($this->to), fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))
            ->tap(fn (Builder $q) => $this->applySort($q));
    }
}
