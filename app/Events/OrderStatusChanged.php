<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?OrderStatus $from,
        public OrderStatus $to,
        public ?string $comment = null,
    ) {}
}
