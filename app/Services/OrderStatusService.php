<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    public function transition(Order $order, OrderStatus $to, ?User $actor = null, ?string $comment = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actor, $comment) {
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $order->status;

            if (! $from->canTransitionTo($to)) {
                throw new BusinessException(sprintf(
                    'Impossible de passer la commande de « %s » à « %s ».', $from->label(), $to->label()
                ));
            }

            $this->applySideEffects($order, $from, $to);

            $order->status = $to;
            $order->save();

            $order->statusHistories()->create([
                'user_id' => $actor?->id,
                'from_status' => $from,
                'to_status' => $to,
                'comment' => $comment,
                'created_at' => now(),
            ]);

            DB::afterCommit(fn () => OrderStatusChanged::dispatch($order, $from, $to, $comment));

            return $order;
        });
    }

    private function applySideEffects(Order $order, OrderStatus $from, OrderStatus $to): void
    {
        match ($to) {
            OrderStatus::Confirmed => $order->confirmed_at = now(),
            OrderStatus::Shipped => $this->markShipped($order),
            OrderStatus::Delivered => $this->markDelivered($order),
            OrderStatus::Cancelled => $this->cancel($order, $from),
            OrderStatus::Refunded => $this->refund($order),
            default => null,
        };
    }

    private function markShipped(Order $order): void
    {
        $order->shipped_at = now();
        $order->shipments()->where('status', ShipmentStatus::Pending)
            ->update(['status' => ShipmentStatus::InTransit, 'shipped_at' => now()]);
    }

    private function markDelivered(Order $order): void
    {
        $order->delivered_at = now();
        $order->shipments()->whereIn('status', [ShipmentStatus::Pending, ShipmentStatus::InTransit])
            ->update(['status' => ShipmentStatus::Delivered, 'delivered_at' => now()]);
    }

    private function cancel(Order $order, OrderStatus $from): void
    {
        $order->cancelled_at = now();

        if ($from->holdsStock()) {
            $this->restock($order);
        }

        $order->payments()->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing])
            ->update(['status' => PaymentStatus::Cancelled]);

        if ($order->payment_status !== PaymentStatus::Paid) {
            $order->payment_status = PaymentStatus::Cancelled;
        }
    }

    private function refund(Order $order): void
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            $order->payment_status = PaymentStatus::Refunded;
            $order->payments()->where('status', PaymentStatus::Paid)->update(['status' => PaymentStatus::Refunded]);
        }
    }

    /** Gives reserved stock back to products/variants. */
    private function restock(Order $order): void
    {
        foreach ($order->items()->get() as $item) {
            if ($item->product_variant_id) {
                ProductVariant::query()->whereKey($item->product_variant_id)->increment('stock', $item->quantity);
            }

            if ($item->product_id) {
                Product::withTrashed()->whereKey($item->product_id)->increment('stock', $item->quantity);
                Product::withTrashed()->whereKey($item->product_id)->where('sales_count', '>=', $item->quantity)
                    ->decrement('sales_count', $item->quantity);
            }
        }
    }
}
