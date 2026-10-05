<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** status is not fillable: moderation goes through the admin ReviewController. */
#[Fillable(['product_id', 'user_id', 'order_id', 'rating', 'title', 'comment'])]
class Review extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'moderated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function authorName(): string
    {
        $parts = preg_split('/\s+/', trim($this->user?->name ?? 'Client'));
        $first = $parts[0] ?? 'Client';
        $initial = isset($parts[1]) ? ' '.mb_strtoupper(mb_substr($parts[1], 0, 1)).'.' : '';

        return $first.$initial;
    }
}
