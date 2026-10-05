<?php

namespace App\Http\Controllers\Shop;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Gateways\ManualMobileMoneyGateway;
use App\Payments\PaymentManager;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentManager $gateways,
    ) {}

    /** Payment instructions / retry page for an order awaiting payment. */
    public function show(Order $order): View|RedirectResponse
    {
        Gate::authorize('view', $order);

        $order->load(['latestPayment', 'items']);
        $payment = $order->latestPayment;

        if ($order->payment_status === PaymentStatus::Paid) {
            return redirect()->route('account.orders.show', $order);
        }

        $momo = $this->gateways->isAvailable('manual_mobile_money') ? $this->gateways->gateway('manual_mobile_money') : null;

        return view('shop.payment', [
            'order' => $order,
            'payment' => $payment,
            'gateways' => $this->gateways->available(),
            'momo' => $momo instanceof ManualMobileMoneyGateway ? $momo : null,
            'canRetry' => Gate::allows('pay', $order),
        ]);
    }

    /** Starts (or restarts) a payment with the chosen gateway. */
    public function store(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('pay', $order);

        $data = $request->validate(['payment_method' => ['required', Rule::in($this->gateways->available()->keys()->all())]]);

        [$payment, $initiation] = $this->payments->start($order, $data['payment_method']);

        if ($initiation->redirectUrl) {
            return redirect()->away($initiation->redirectUrl);
        }

        return $payment->gateway === 'manual_mobile_money'
            ? redirect()->route('payments.show', $order)
            : redirect()->route('checkout.confirmation', $order);
    }

    public function submitTransfer(Request $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('view', $payment->order);
        abort_unless($payment->gateway === 'manual_mobile_money', 404);

        /** @var ManualMobileMoneyGateway $gateway */
        $gateway = $this->gateways->gateway('manual_mobile_money');

        $data = $request->validate([
            'operator' => ['required', Rule::in(array_keys($gateway->operators()))],
            'transaction_id' => ['required', 'string', 'min:4', 'max:64', 'regex:/^[A-Za-z0-9.\-_]+$/'],
            'payer_phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\.]{8,20}$/'],
        ], [
            'transaction_id.regex' => 'La référence ne doit contenir que des lettres et des chiffres.',
            'payer_phone.regex' => 'Le numéro n’est pas valide.',
        ], ['operator' => 'opérateur', 'transaction_id' => 'référence de transaction', 'payer_phone' => 'numéro payeur']);

        $this->payments->submitManualTransfer($payment, $data['operator'], $data['transaction_id'], preg_replace('/\D/', '', $data['payer_phone']));

        return redirect()->route('checkout.confirmation', $payment->order)
            ->with('toast', ['type' => 'success', 'message' => 'Merci ! Votre paiement est en cours de vérification.']);
    }

    /**
     * Return URL used by online providers. The status shown here comes from
     * a server-side check with the provider — never from the URL itself.
     */
    public function return(Payment $payment): View
    {
        Gate::authorize('view', $payment->order);

        $payment = $this->payments->refresh($payment);

        return view('shop.payment-return', ['payment' => $payment->load('order')]);
    }

    public function status(Payment $payment): JsonResponse
    {
        Gate::authorize('view', $payment->order);

        $payment = $this->payments->refresh($payment);

        return response()->json(['status' => $payment->status->value, 'label' => $payment->status->label(), 'final' => $payment->status->isFinal()]);
    }
}
