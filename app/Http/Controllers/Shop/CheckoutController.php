<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\PlaceOrderRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentManager;
use App\Services\Cart\CartService;
use App\Services\Orders\CheckoutData;
use App\Services\Orders\OrderService;
use App\Services\PaymentService;
use App\Services\ShippingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function show(ShippingService $shipping, PaymentManager $payments): View|RedirectResponse
    {
        $summary = $this->cart->summary();

        if ($summary->isEmpty()) {
            return redirect()->route('cart.index');
        }

        if (! $summary->isCheckoutReady()) {
            return redirect()->route('cart.index')
                ->with('toast', ['type' => 'error', 'message' => 'Certains articles ne sont plus disponibles en quantité suffisante. Ajustez votre panier.']);
        }

        $user = auth()->user();

        return view('shop.checkout', [
            'summary' => $summary,
            'addresses' => $user->addresses()->orderByDesc('is_default')->latest()->get(),
            'shippingQuotes' => $shipping->quotes($summary->subtotal - $summary->discount),
            'gateways' => $payments->available(),
            'user' => $user,
        ]);
    }

    public function store(PlaceOrderRequest $request, OrderService $orders, PaymentService $payments): RedirectResponse
    {
        $user = $request->user();
        $cart = $this->cart->current();

        if ($cart === null) {
            return redirect()->route('cart.index');
        }

        $order = $orders->placeOrder($user, $cart, new CheckoutData(
            customerName: $request->string('customer_name')->trim()->value(),
            customerEmail: mb_strtolower($request->string('customer_email')->trim()->value()),
            customerPhone: $request->string('customer_phone')->trim()->value(),
            address: $this->resolveAddress($request),
            shippingMethod: ShippingMethod::query()->findOrFail($request->integer('shipping_method_id')),
            paymentMethod: $request->string('payment_method')->value(),
            notes: $request->input('notes'),
        ));

        try {
            [$payment, $initiation] = $payments->start($order, $order->payment_method);
        } catch (PaymentException $e) {
            // The order exists and keeps its stock reservation: the customer can retry the payment.
            return redirect()->route('payments.show', $order)->with('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        if ($initiation->redirectUrl) {
            return redirect()->away($initiation->redirectUrl);
        }

        return $payment->gateway === 'manual_mobile_money'
            ? redirect()->route('payments.show', $order)
            : redirect()->route('checkout.confirmation', $order);
    }

    public function confirmation(Order $order): View
    {
        Gate::authorize('view', $order);

        return view('shop.confirmation', [
            'order' => $order->load(['items', 'latestPayment']),
        ]);
    }

    private function resolveAddress(PlaceOrderRequest $request): array
    {
        if ($request->filled('address_id')) {
            return Address::query()->whereBelongsTo($request->user())->findOrFail($request->integer('address_id'))->toSnapshot();
        }

        $data = collect($request->validated('address'))->only(['full_name', 'phone', 'city', 'district', 'street', 'landmark'])->all();

        if ($request->boolean('save_address')) {
            $address = $request->user()->addresses()->make($data + ['label' => 'Adresse de livraison']);
            $address->is_default = ! $request->user()->addresses()->exists();
            $address->save();
        }

        return $data + ['district' => null, 'landmark' => null];
    }
}
