<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Livewire\Concerns\AuthorizesAbility;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Dashboard figures, refreshed every minute while the tab is visible.
 * Every figure comes from the database — no placeholder statistics.
 */
class Dashboard extends Component
{
    use AuthorizesAbility;

    #[Locked]
    public int $latestOrderId = 0;

    protected function ability(): string
    {
        return 'dashboard.view';
    }

    public function mount(): void
    {
        $this->latestOrderId = (int) Order::query()->max('id');
    }

    public function render(): View
    {
        $this->announceNewOrders();

        $monthStart = now()->startOfMonth();
        $previousMonthStart = now()->subMonthNoOverflow()->startOfMonth();

        $revenueThisMonth = (int) Order::query()->revenue()->where('created_at', '>=', $monthStart)->sum('total');
        $revenuePreviousMonth = (int) Order::query()->revenue()
            ->whereBetween('created_at', [$previousMonthStart, $monthStart])->sum('total');

        return view('livewire.admin.dashboard', [
            'kpis' => [
                'revenue_total' => (int) Order::query()->revenue()->sum('total'),
                'revenue_month' => $revenueThisMonth,
                'revenue_trend' => $revenuePreviousMonth > 0 ? round(($revenueThisMonth - $revenuePreviousMonth) * 100 / $revenuePreviousMonth) : null,
                'orders' => Order::query()->count(),
                'orders_pending' => Order::query()->where('status', OrderStatus::Pending)->count(),
                'customers' => User::query()->customers()->count(),
                'products' => Product::query()->published()->count(),
                'low_stock' => Product::query()->published()->lowStock()->count(),
            ],
            'salesChart' => $this->salesChart(30),
            'recentOrders' => Order::query()->latest()->limit(6)->get(),
            'lowStockProducts' => Product::query()->published()->lowStock()->with('primaryImage')->orderBy('stock')->limit(6)->get(),
            'topProducts' => OrderItem::query()
                ->select('product_id', 'product_name', DB::raw('SUM(quantity) as units'), DB::raw('SUM(line_total) as revenue'))
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Refunded]))
                ->groupBy('product_id', 'product_name')->orderByDesc('units')->limit(5)->get(),
            'activity' => OrderStatusHistory::query()->with(['order:id,number', 'user:id,name'])->latest('created_at')->latest('id')->limit(8)->get(),
            'todo' => [
                'pending_reviews' => Review::query()->where('status', 'pending')->count(),
                'payments_to_check' => Payment::query()->where('status', 'processing')->count(),
                'open_returns' => ReturnRequest::query()->whereIn('status', ['requested', 'approved', 'received'])->count(),
            ],
        ]);
    }

    private function announceNewOrders(): void
    {
        $latest = (int) Order::query()->max('id');

        if ($latest > $this->latestOrderId) {
            $count = Order::query()->where('id', '>', $this->latestOrderId)->count();
            $this->dispatch('toast', message: $count > 1 ? "{$count} nouvelles commandes !" : 'Nouvelle commande reçue !', type: 'info');
            $this->latestOrderId = $latest;
        }
    }

    /** Daily paid revenue for the last N days (days without sales included as 0). */
    private function salesChart(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = Order::query()->revenue()->where('created_at', '>=', $from)
            ->get(['total', 'created_at'])
            ->groupBy(fn (Order $o) => $o->created_at->toDateString())
            ->map(fn ($orders) => ['total' => (int) $orders->sum('total'), 'count' => $orders->count()]);

        return collect(range(0, $days - 1))->map(function (int $i) use ($from, $rows) {
            $date = $from->copy()->addDays($i);
            $row = $rows[$date->toDateString()] ?? ['total' => 0, 'count' => 0];

            return ['date' => $date, 'label' => $date->translatedFormat('d M'), 'total' => $row['total'], 'count' => $row['count']];
        })->all();
    }
}
