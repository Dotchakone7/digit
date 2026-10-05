<?php

namespace App\Models;

use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'user_id', 'reason', 'details'])]
class ReturnRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ReturnStatus::class,
            'refund_amount' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function reasonLabel(): string
    {
        return ReturnStatus::reasons()[$this->reason] ?? $this->reason;
    }
}
