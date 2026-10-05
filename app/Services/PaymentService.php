<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentConfirmed;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Payments\Data\PaymentInitiation;
use App\Payments\Data\WebhookResult;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PaymentService
{
    public function __construct(
        private readonly PaymentManager $gateways,
        private readonly OrderStatusService $orderStatus,
    ) {}

    /** Creates a payment attempt for the order and asks the gateway to start it. */
    public function start(Order $order, string $gatewayCode): array
    {
        if (! $this->gateways->isAvailable($gatewayCode)) {
            throw new PaymentException("Ce moyen de paiement n'est pas disponible.");
        }

        if (! $order->awaitsPayment()) {
            throw new PaymentException('Cette commande ne peut plus être payée.');
        }

        $gateway = $this->gateways->gateway($gatewayCode);

        $payment = DB::transaction(function () use ($order, $gateway) {
            // Only one open attempt per order.
            $order->payments()->open()->update(['status' => PaymentStatus::Cancelled]);

            $order->forceFill(['payment_method' => $gateway->code(), 'payment_status' => PaymentStatus::Pending])->save();

            $payment = $order->payments()->make([
                'gateway' => $gateway->code(),
                'reference' => $this->newReference(),
                'amount' => $order->total,
                'currency' => $order->currency,
                'expires_at' => ($minutes = $gateway->expiresAfterMinutes()) ? now()->addMinutes($minutes) : null,
            ]);
            $payment->status = PaymentStatus::Pending;
            $payment->save();

            return $payment;
        });

        try {
            $initiation = $gateway->initiate($payment);
        } catch (Throwable $e) {
            Log::error('Payment initiation failed', ['payment' => $payment->reference, 'gateway' => $gatewayCode, 'error' => $e->getMessage()]);
            $this->markFailed($payment, 'initiation_error');

            throw new PaymentException('Le prestataire de paiement est momentanément indisponible. Veuillez réessayer.');
        }

        $this->applyInitiation($payment, $initiation);

        return [$payment, $initiation];
    }

    /** Customer declares a Mobile Money transfer; staff will verify it. */
    public function submitManualTransfer(Payment $payment, string $operator, string $transactionId, string $phone): void
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw new PaymentException('Ce paiement a déjà été traité.');
        }

        $payment->meta = array_merge($payment->meta ?? [], [
            'operator' => $operator,
            'transaction_id' => $transactionId,
            // Keep only the last digits: the full number is not needed to reconcile.
            'payer_phone' => Str::mask($phone, '•', 0, max(strlen($phone) - 4, 0)),
            'submitted_at' => now()->toIso8601String(),
        ]);
        $payment->status = PaymentStatus::Processing;
        $payment->save();

        $payment->order()->update(['payment_status' => PaymentStatus::Processing]);
    }

    /** Handles a provider callback after the gateway authenticated it. */
    public function handleWebhook(string $gatewayCode, WebhookResult $result): Payment
    {
        $payment = Payment::query()
            ->where('gateway', $gatewayCode)
            ->where('reference', $result->reference)
            ->firstOrFail();

        if ($result->amount !== null && $result->amount !== $payment->amount) {
            Log::warning('Payment webhook amount mismatch', ['payment' => $payment->reference]);
            $this->markFailed($payment, 'amount_mismatch');

            return $payment;
        }

        $this->applyStatus($payment, $result->status, $result->providerReference, $result->meta);

        return $payment->refresh();
    }

    /** Re-checks the status with the provider (server-to-server). Used on the return page. */
    public function refresh(Payment $payment): Payment
    {
        if ($payment->status->isFinal()) {
            return $payment;
        }

        if ($payment->isExpired()) {
            $this->markExpired($payment);

            return $payment->refresh();
        }

        $status = $this->gateways->gateway($payment->gateway)->fetchStatus($payment);

        if ($status !== null && $status !== $payment->status) {
            $this->applyStatus($payment, $status);
        }

        return $payment->refresh();
    }

    public function applyStatus(Payment $payment, PaymentStatus $status, ?string $providerReference = null, array $meta = []): void
    {
        match ($status) {
            PaymentStatus::Paid => $this->markPaid($payment, providerReference: $providerReference, meta: $meta),
            PaymentStatus::Failed => $this->markFailed($payment, $meta['reason'] ?? 'provider_declined'),
            PaymentStatus::Cancelled => $this->closeAs($payment, PaymentStatus::Cancelled),
            PaymentStatus::Expired => $this->markExpired($payment),
            PaymentStatus::Processing => $this->closeAs($payment, PaymentStatus::Processing),
            default => null,
        };
    }

    /** Idempotent: calling it twice never double-confirms the order. */
    public function markPaid(Payment $payment, ?User $actor = null, ?string $providerReference = null, array $meta = []): void
    {
        $confirmed = DB::transaction(function () use ($payment, $actor, $providerReference, $meta) {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->with('order')->findOrFail($payment->id);

            if ($locked->status === PaymentStatus::Paid) {
                return false;
            }

            if (in_array($locked->status, [PaymentStatus::Refunded], true)) {
                return false;
            }

            $locked->status = PaymentStatus::Paid;
            $locked->paid_at = now();
            $locked->provider_reference = $providerReference ?? $locked->provider_reference;
            $locked->meta = array_merge($locked->meta ?? [], $meta, $actor ? ['confirmed_by' => $actor->id] : []);
            $locked->save();

            $order = $locked->order;
            $order->forceFill(['payment_status' => PaymentStatus::Paid, 'payment_method' => $locked->gateway])->save();

            if ($order->status === OrderStatus::Pending) {
                $this->orderStatus->transition($order, OrderStatus::Confirmed, $actor, 'Paiement confirmé ('.$locked->gatewayLabel().')');
            }

            DB::afterCommit(fn () => PaymentConfirmed::dispatch($locked));

            return true;
        });

        if ($confirmed) {
            Log::info('Payment confirmed', ['payment' => $payment->reference, 'gateway' => $payment->gateway]);
        }
    }

    public function markFailed(Payment $payment, string $reason): void
    {
        $this->closeAs($payment, PaymentStatus::Failed, $reason);
    }

    /** Expired online payments release the reserved stock by cancelling the order. */
    public function markExpired(Payment $payment): void
    {
        if (! $this->closeAs($payment, PaymentStatus::Expired)) {
            return;
        }

        $order = $payment->order()->firstOrFail();

        if ($order->status === OrderStatus::Pending && ! $order->payments()->open()->exists()) {
            $this->orderStatus->transition($order, OrderStatus::Cancelled, null, 'Annulée automatiquement : délai de paiement dépassé.');
        }
    }

    public function expireOverdue(): int
    {
        $count = 0;

        Payment::query()->open()->whereNotNull('expires_at')->where('expires_at', '<', now())
            ->with('order')->lazyById()
            ->each(function (Payment $payment) use (&$count) {
                $this->markExpired($payment);
                $count++;
            });

        return $count;
    }

    private function closeAs(Payment $payment, PaymentStatus $status, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($payment, $status, $reason) {
            $locked = Payment::query()->lockForUpdate()->with('order')->findOrFail($payment->id);

            if ($locked->status->isFinal() || $locked->status === $status) {
                return false;
            }

            $locked->status = $status;
            $locked->failure_reason = $reason ?? $locked->failure_reason;
            $locked->save();

            $locked->order->forceFill(['payment_status' => $status])->save();

            return true;
        });
    }

    private function applyInitiation(Payment $payment, PaymentInitiation $initiation): void
    {
        $payment->provider_reference = $initiation->providerReference ?? $payment->provider_reference;
        $payment->meta = array_merge($payment->meta ?? [], $initiation->meta);
        $payment->save();

        if ($initiation->status !== PaymentStatus::Pending) {
            $this->applyStatus($payment, $initiation->status);
        }
    }

    private function newReference(): string
    {
        do {
            $reference = 'PAY-'.strtoupper(Str::random(12));
        } while (Payment::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
