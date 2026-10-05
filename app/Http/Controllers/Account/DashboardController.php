<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('account.dashboard', [
            'recentOrders' => $user->orders()->withCount('items')->latest()->limit(3)->get(),
            'stats' => [
                'orders' => $user->orders()->count(),
                'in_progress' => $user->orders()->whereIn('status', [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped])->count(),
                'wishlist' => $user->wishlist()->count(),
                'spent' => (int) $user->orders()->revenue()->sum('total'),
            ],
            'notifications' => $user->unreadNotifications()->latest()->limit(5)->get(),
        ]);
    }
}
