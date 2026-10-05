<?php

namespace App\Http\Controllers\Shop;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Payments\Gateways\SandboxGateway;
use App\Payments\PaymentManager;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Plays the role of an external provider's hosted payment page. The outcome
 * is delivered through a signed webhook, exactly like a real provider.
 */
class SandboxPaymentController extends Controller
{
    public function show(Payment $payment, PaymentManager $gateways): View
    {
        $this->guard($payment, $gateways);

        return view('shop.sandbox', ['payment' => $payment->load('order')]);
    }

    public function complete(Request $request, Payment $payment, PaymentManager $gateways, PaymentService $payments): RedirectResponse
    {
        $this->guard($payment, $gateways);

        $outcome = $request->validate(['outcome' => ['required', 'in:paid,failed,cancelled']])['outcome'];

        /** @var SandboxGateway $gateway */
        $gateway = $gateways->gateway('sandbox');

        // Provider-side state, then a signed notification to our webhook pipeline.
        $payment->forceFill(['meta' => array_merge($payment->meta ?? [], ['sandbox_status' => $outcome])])->save();
        $body = json_encode(['reference' => $payment->reference, 'status' => $outcome, 'amount' => $payment->amount, 'provider_reference' => $payment->provider_reference]);
        $webhook = Request::create(route('payments.webhook', 'sandbox'), 'POST', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_'.str_replace('-', '_', strtoupper(SandboxGateway::SIGNATURE_HEADER)) => $gateway->sign($body),
        ], content: $body);

        $payments->handleWebhook('sandbox', $gateway->parseWebhook($webhook));

        return redirect()->route('payments.return', $payment);
    }

    private function guard(Payment $payment, PaymentManager $gateways): void
    {
        abort_unless($payment->gateway === 'sandbox' && $gateways->isAvailable('sandbox'), 404);
        Gate::authorize('view', $payment->order);
        abort_unless($payment->status === PaymentStatus::Pending, 410);
    }
}
