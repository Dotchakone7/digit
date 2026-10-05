<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Notifications\NewOrderAdminNotification;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Support\Facades\Notification;

class SendOrderPlacedNotifications
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        if ($order->user) {
            $order->user->notify(new OrderPlacedNotification($order));
        } else {
            Notification::route('mail', $order->customer_email)->notify(new OrderPlacedNotification($order));
        }

        if ($email = config('shop.admin_notification_email')) {
            Notification::route('mail', $email)->notify(new NewOrderAdminNotification($order));
        }
    }
}
