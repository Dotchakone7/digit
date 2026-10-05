<?php

namespace App\Delivery;

use App\Delivery\Contracts\CourierProvider;
use InvalidArgumentException;

class CourierManager
{
    public function provider(?string $driver = null): CourierProvider
    {
        $driver ??= config('delivery.courier.driver', 'link');
        $class = config("delivery.drivers.{$driver}");

        if (! $class || ! is_subclass_of($class, CourierProvider::class)) {
            throw new InvalidArgumentException("Unknown courier driver [{$driver}].");
        }

        return app($class);
    }
}
