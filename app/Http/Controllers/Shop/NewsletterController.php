<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:190']], [], ['email' => 'adresse e-mail']);

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => mb_strtolower($data['email'])]);
        $subscriber->token ??= Str::random(48);
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        // Same answer whether the address was already known (no e-mail enumeration).
        $message = 'Merci ! Vous êtes inscrit(e) à notre newsletter.';

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('toast', ['type' => 'success', 'message' => $message]);
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        NewsletterSubscriber::query()->where('token', $token)->update(['unsubscribed_at' => now()]);

        return redirect()->route('home')->with('toast', ['type' => 'success', 'message' => 'Vous êtes désinscrit(e) de la newsletter.']);
    }
}
