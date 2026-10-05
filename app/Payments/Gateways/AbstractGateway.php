<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\WebhookResult;
use App\Payments\Exceptions\InvalidWebhookSignature;
use Illuminate\Http\Request;

abstract class AbstractGateway implements PaymentGateway
{
    public function __construct(protected readonly string $code, protected readonly array $config = []) {}

    public function code(): string
    {
        return $this->code;
    }

    public function label(): string
    {
        return $this->config['label'] ?? $this->code;
    }

    public function description(): string
    {
        return $this->config['description'] ?? '';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        throw new InvalidWebhookSignature("Gateway [{$this->code}] does not accept webhooks.");
    }

    public function fetchStatus(Payment $payment): ?PaymentStatus
    {
        return null;
    }

    public function requiresManualConfirmation(): bool
    {
        return false;
    }

    public function expiresAfterMinutes(): ?int
    {
        return null;
    }
}
