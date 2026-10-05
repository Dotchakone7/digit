<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.wishlist', [
            'products' => $request->user()->wishlist()->published()->forCard()->latest('wishlist_items.created_at')->paginate(12),
        ]);
    }
}
