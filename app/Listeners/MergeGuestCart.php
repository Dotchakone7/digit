<?php

namespace App\Listeners;

use App\Services\Cart\CartService;
use Illuminate\Auth\Events\Login;

class MergeGuestCart
{
    public function __construct(private readonly CartService $cart) {}

    public function handle(Login $event): void
    {
        $this->cart->mergeGuestCartInto($event->user);
        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
    }
}
