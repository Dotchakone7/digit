<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Notifications\OrderStatusUpdatedNotification;

class SendOrderStatusNotification
{
    public function handle(OrderStatusChanged $event): void
    {
        // The payment confirmation e-mail already covers pending → confirmed after payment.
        if ($event->to === OrderStatus::Confirmed && $event->order->payment_status->value === 'paid') {
            return;
        }

        $event->order->user?->notify(new OrderStatusUpdatedNotification($event->order, $event->to, $event->comment));
    }
}
