<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Gate;

/**
 * Re-checks the component's ability on EVERY Livewire request (initial
 * render and each subsequent action), never trusting the page it lives in.
 */
trait AuthorizesAbility
{
    abstract protected function ability(): string;

    public function bootAuthorizesAbility(): void
    {
        abort_unless(auth()->check() && Gate::allows($this->ability()), 403);
    }
}
