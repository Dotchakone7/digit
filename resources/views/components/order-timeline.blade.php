@props(['order'])
@php
    use App\Enums\OrderStatus;
    $reached = $order->statusHistories->keyBy(fn ($h) => $h->to_status->value);
    $paidAt = $order->payments->firstWhere('status', \App\Enums\PaymentStatus::Paid)?->paid_at;
    $steps = [
        ['label' => 'Commande créée', 'icon' => 'bag', 'at' => $order->created_at, 'done' => true],
        ['label' => 'Paiement confirmé', 'icon' => 'card', 'at' => $paidAt, 'done' => $paidAt !== null, 'hint' => $paidAt ? null : $order->payment_status->label()],
        ['label' => 'Commande confirmée', 'icon' => 'check-circle', 'at' => $reached[OrderStatus::Confirmed->value]->created_at ?? null, 'done' => $reached->has(OrderStatus::Confirmed->value)],
        ['label' => 'Préparation', 'icon' => 'box', 'at' => $reached[OrderStatus::Processing->value]->created_at ?? null, 'done' => $reached->has(OrderStatus::Processing->value)],
        ['label' => 'Expédition', 'icon' => 'truck', 'at' => $reached[OrderStatus::Shipped->value]->created_at ?? null, 'done' => $reached->has(OrderStatus::Shipped->value)],
        ['label' => 'Livraison', 'icon' => 'home', 'at' => $reached[OrderStatus::Delivered->value]->created_at ?? null, 'done' => $reached->has(OrderStatus::Delivered->value)],
    ];
    $terminal = in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded], true);
    $currentIndex = collect($steps)->search(fn ($s) => ! $s['done']);
@endphp
<ol {{ $attributes->merge(['class' => 'relative space-y-0']) }} aria-label="Suivi de la commande">
    @foreach ($steps as $i => $step)
        @php($isCurrent = ! $terminal && $i === $currentIndex)
        <li class="relative flex gap-4 pb-6 last:pb-0">
            @unless ($loop->last)
                <span @class(['absolute top-9 left-[17px] h-[calc(100%-2.25rem)] w-0.5', 'bg-success-500' => $step['done'] && ($steps[$i + 1]['done'] ?? false), 'bg-zinc-200' => ! ($step['done'] && ($steps[$i + 1]['done'] ?? false))]) aria-hidden="true"></span>
            @endunless
            <span @class(['relative grid size-9 shrink-0 place-items-center rounded-full ring-4 ring-white',
                'bg-success-500 text-white' => $step['done'],
                'bg-brand-900 text-white' => $isCurrent,
                'bg-zinc-100 text-zinc-400' => ! $step['done'] && ! $isCurrent])>
                <x-icon :name="$step['done'] ? 'check' : $step['icon']" class="size-4" />
            </span>
            <div class="pt-1.5">
                <p @class(['text-sm font-semibold', 'text-brand-900' => $step['done'] || $isCurrent, 'text-zinc-500' => ! $step['done'] && ! $isCurrent])>
                    {{ $step['label'] }} @if ($isCurrent)<span class="badge badge-primary ml-1">En cours</span>@endif
                </p>
                @if ($step['at'])
                    <p class="text-xs text-zinc-500">{{ $step['at']->translatedFormat('d M Y, H:i') }}</p>
                @elseif (! empty($step['hint']))
                    <p class="text-xs text-zinc-500">{{ $step['hint'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
    @if ($terminal)
        @php($end = $reached[$order->status->value] ?? null)
        <li class="relative mt-6 flex gap-4 rounded-2xl bg-danger-50 p-3">
            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-danger-600 text-white"><x-icon name="x" class="size-4" /></span>
            <div class="pt-0.5">
                <p class="text-sm font-semibold text-danger-700">Commande {{ mb_strtolower($order->status->label()) }}</p>
                <p class="text-xs text-danger-700/80">{{ $end?->created_at?->translatedFormat('d M Y, H:i') }} @if ($end?->comment) · {{ $end->comment }}@endif</p>
            </div>
        </li>
    @endif
</ol>
