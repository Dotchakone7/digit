<?php

use App\Services\PaymentService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('payments:expire', function (PaymentService $payments) {
    $count = $payments->expireOverdue();
    $this->info("{$count} paiement(s) expiré(s).");
})->purpose('Expire unpaid online payments and release their reserved stock');

Schedule::command('payments:expire')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->daily();
