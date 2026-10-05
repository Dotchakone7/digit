<?php

namespace App\Assistant;

use App\Assistant\Contracts\AssistantDriver;
use InvalidArgumentException;

class AssistantManager
{
    public function enabled(): bool
    {
        return (bool) config('assistant.enabled');
    }

    public function driver(): AssistantDriver
    {
        $name = config('assistant.driver', 'rules');
        $class = config("assistant.drivers.{$name}");

        if (! $class || ! is_subclass_of($class, AssistantDriver::class)) {
            throw new InvalidArgumentException("Unknown assistant driver [{$name}].");
        }

        return app($class);
    }
}
