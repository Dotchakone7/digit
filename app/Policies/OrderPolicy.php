<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.manage');
    }

    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id || $user->can('orders.manage');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.manage');
    }

    public function pay(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->awaitsPayment();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->isCancellableByCustomer();
    }

    public function requestReturn(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->canRequestReturn();
    }
}
