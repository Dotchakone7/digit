<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PaymentManager
{
    /** @var array<string, PaymentGateway> */
    private array $resolved = [];

    public function gateway(string $code): PaymentGateway
    {
        if (isset($this->resolved[$code])) {
            return $this->resolved[$code];
        }

        $config = config("payments.gateways.{$code}");

        if (! is_array($config) || ! isset($config['driver'])) {
            throw new InvalidArgumentException("Unknown payment gateway [{$code}].");
        }

        $gateway = new $config['driver']($code, $config);

        if (! $gateway instanceof PaymentGateway) {
            throw new InvalidArgumentException("Gateway [{$code}] must implement PaymentGateway.");
        }

        return $this->resolved[$code] = $gateway;
    }

    /** Gateways enabled in .env and correctly configured. @return Collection<string, PaymentGateway> */
    public function available(): Collection
    {
        return collect(config('payments.enabled', []))
            ->filter(fn (string $code) => config("payments.gateways.{$code}") !== null)
            ->mapWithKeys(fn (string $code) => [$code => $this->gateway($code)])
            ->filter(fn (PaymentGateway $gateway) => $gateway->isAvailable());
    }

    public function isAvailable(string $code): bool
    {
        return $this->available()->has($code);
    }
}
