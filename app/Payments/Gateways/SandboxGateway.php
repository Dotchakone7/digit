<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Data\PaymentInitiation;
use App\Payments\Data\WebhookResult;
use App\Payments\Exceptions\InvalidWebhookSignature;
use Illuminate\Http\Request;

/**
 * Simulates an online provider (hosted payment page + HMAC-signed webhook)
 * so the full asynchronous flow can be demonstrated and tested locally.
 * Disabled in production by PaymentManager.
 */
class SandboxGateway extends AbstractGateway
{
    public const SIGNATURE_HEADER = 'X-Sandbox-Signature';

    public function isAvailable(): bool
    {
        return ! app()->isProduction() && filled($this->secret());
    }

    public function initiate(Payment $payment): PaymentInitiation
    {
        return new PaymentInitiation(
            status: PaymentStatus::Pending,
            redirectUrl: route('payments.sandbox.show', $payment),
            providerReference: 'SBX-'.strtoupper(bin2hex(random_bytes(6))),
        );
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        $signature = (string) $request->header(self::SIGNATURE_HEADER);

        if (! hash_equals($this->sign($request->getContent()), $signature)) {
            throw new InvalidWebhookSignature('Invalid sandbox signature.');
        }

        $status = PaymentStatus::tryFrom((string) $request->input('status'));

        if ($status === null || ! filled($request->input('reference'))) {
            throw new InvalidWebhookSignature('Malformed sandbox payload.');
        }

        return new WebhookResult(
            reference: (string) $request->input('reference'),
            status: $status,
            providerReference: $request->input('provider_reference'),
            amount: $request->integer('amount') ?: null,
        );
    }

    /** The simulated provider keeps its own state in the payment meta. */
    public function fetchStatus(Payment $payment): ?PaymentStatus
    {
        return PaymentStatus::tryFrom($payment->meta['sandbox_status'] ?? '') ?? PaymentStatus::Pending;
    }

    public function expiresAfterMinutes(): ?int
    {
        return (int) config('payments.expiration_minutes', 60);
    }

    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, (string) $this->secret());
    }

    private function secret(): ?string
    {
        return $this->config['secret'] ?? null;
    }
}
