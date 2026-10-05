<?php

namespace App\Http\Controllers\Admin;

use App\Delivery\CourierManager;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.orders.index', [
            'orders' => $this->query($filters)->withCount('items')->paginate(20)->withQueryString(),
            'filters' => $filters,
            'statusCounts' => Order::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Order $order, CourierManager $couriers): View
    {
        $order->load(['items', 'statusHistories.user:id,name', 'payments', 'shipments', 'user', 'returnRequests', 'coupon']);
        $courier = $couriers->provider();

        return view('admin.orders.show', [
            'order' => $order,
            'transitions' => $order->status->allowedTransitions(),
            'courier' => $courier,
            'courierOptions' => $courier->isConfigured() ? $courier->contactOptions($order) : [],
        ]);
    }

    public function updateStatus(Request $request, Order $order, OrderStatusService $statuses): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_map(fn ($s) => $s->value, $order->status->allowedTransitions()))],
            'comment' => ['nullable', 'string', 'max:500'],
        ], ['status.in' => 'Ce changement de statut n’est pas autorisé.']);

        $statuses->transition($order, OrderStatus::from($data['status']), $request->user(), $data['comment'] ?? null);

        return back()->with('toast', ['type' => 'success', 'message' => 'Statut mis à jour : '.OrderStatus::from($data['status'])->label().'.']);
    }

    public function storeShipment(Request $request, Order $order): RedirectResponse
    {
        abort_if(in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded, OrderStatus::Delivered], true), 422);

        $data = $request->validate([
            'carrier' => ['required', 'string', 'max:60'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['carrier' => 'livreur / transporteur', 'tracking_number' => 'numéro de suivi']);

        $order->shipments()->create($data + ['status' => ShipmentStatus::Pending]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Expédition enregistrée. Passez la commande en « Expédiée » lors de la remise au livreur.']);
    }

    /** "Contacter un livreur": redirects to the configured delivery platform. */
    public function contactCourier(Order $order, CourierManager $couriers): RedirectResponse
    {
        $options = $couriers->provider()->contactOptions($order);
        $platform = collect($options)->firstWhere('type', 'platform') ?? $options[0] ?? null;

        if ($platform === null) {
            return redirect()->route('admin.settings.edit', ['tab' => 'delivery'])
                ->with('toast', ['type' => 'error', 'message' => 'Aucune plateforme de livraison n’est configurée. Renseignez-la dans Paramètres › Livraison.']);
        }

        return redirect()->away($platform['url']);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel.
            fputcsv($out, ['Numéro', 'Date', 'Client', 'E-mail', 'Téléphone', 'Ville', 'Statut', 'Paiement', 'Moyen', 'Sous-total', 'Réduction', 'Livraison', 'Total'], ';');

            $this->query($filters)->lazyById(500)->each(fn (Order $o) => fputcsv($out, [
                $o->number, $o->created_at->format('d/m/Y H:i'), $o->customer_name, $o->customer_email, $o->customer_phone,
                $o->shipping_address['city'] ?? '', $o->status->label(), $o->payment_status->label(), $o->payment_method,
                $o->subtotal, $o->discount_total, $o->shipping_total, $o->total,
            ], ';'));

            fclose($out);
        }, 'commandes-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'total_desc', 'total_asc'])],
        ]);
    }

    private function query(array $filters): Builder
    {
        return Order::query()
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn (Builder $w) => $w->where('number', 'like', '%'.addcslashes(mb_strtoupper($term), '%_\\').'%')
                    ->orWhere('customer_name', 'like', $like)
                    ->orWhere('customer_email', 'like', mb_strtolower($like))
                    ->orWhere('customer_phone', 'like', $like));
            })
            ->when($filters['status'] ?? null, fn (Builder $q, $s) => $q->where('status', $s))
            ->when($filters['payment_status'] ?? null, fn (Builder $q, $s) => $q->where('payment_status', $s))
            ->when($filters['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))
            ->tap(fn (Builder $q) => match ($filters['sort'] ?? 'newest') {
                'oldest' => $q->oldest('id'),
                'total_desc' => $q->orderByDesc('total'),
                'total_asc' => $q->orderBy('total'),
                default => $q->latest('id'),
            });
    }
}
