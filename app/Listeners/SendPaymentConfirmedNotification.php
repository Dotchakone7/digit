<?php

namespace App\Listeners;

use App\Events\PaymentConfirmed;
use App\Notifications\PaymentConfirmedNotification;

class SendPaymentConfirmedNotification
{
    public function handle(PaymentConfirmed $event): void
    {
        $order = $event->payment->order;
        $order->user?->notify(new PaymentConfirmedNotification($order));
    }
}
