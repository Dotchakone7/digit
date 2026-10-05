<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Data\PaymentInitiation;

/**
 * Transfer to the merchant's Mobile Money number. The customer submits the
 * transaction ID; the payment becomes "processing" and a staff member
 * confirms it after checking the operator statement.
 */
class ManualMobileMoneyGateway extends AbstractGateway
{
    /** @return array<string, array{label: string, number: string}> */
    public function operators(): array
    {
        return collect($this->config['operators'] ?? [])
            ->filter(fn (array $operator) => filled($operator['number'] ?? null))
            ->all();
    }

    public function accountName(): ?string
    {
        return $this->config['account_name'] ?? null;
    }

    public function isAvailable(): bool
    {
        return $this->operators() !== [];
    }

    public function initiate(Payment $payment): PaymentInitiation
    {
        return new PaymentInitiation(PaymentStatus::Pending);
    }

    public function requiresManualConfirmation(): bool
    {
        return true;
    }
}
