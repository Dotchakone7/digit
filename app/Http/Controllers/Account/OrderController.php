<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.orders.index', [
            'orders' => $request->user()->orders()
                ->with(['items' => fn ($q) => $q->select(['id', 'order_id', 'product_name', 'image_path', 'quantity'])])
                ->latest()->paginate(10),
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        return view('account.orders.show', [
            'order' => $order->load(['items.product', 'statusHistories', 'payments', 'latestShipment', 'returnRequests']),
        ]);
    }

    public function cancel(Request $request, Order $order, OrderStatusService $statuses): RedirectResponse
    {
        Gate::authorize('cancel', $order);

        $statuses->transition($order, OrderStatus::Cancelled, $request->user(), 'Annulée par le client');

        return back()->with('toast', ['type' => 'success', 'message' => 'Votre commande a été annulée.']);
    }
}
