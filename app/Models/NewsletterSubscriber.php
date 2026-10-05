<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'token', 'unsubscribed_at'])]
class NewsletterSubscriber extends Model
{
    protected function casts(): array
    {
        return ['unsubscribed_at' => 'datetime'];
    }
}
