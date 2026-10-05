<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Shop;
use Illuminate\Support\Facades\Route;

// ---- Storefront -----------------------------------------------------------------
Route::get('/', [Shop\HomeController::class, 'index'])->name('home');
Route::get('/boutique', [Shop\CatalogController::class, 'index'])->name('catalog.index');
Route::get('/categorie/{category:slug}', [Shop\CatalogController::class, 'category'])->name('catalog.category');
Route::get('/produit/{product:slug}', [Shop\ProductController::class, 'show'])->name('products.show');
Route::get('/recherche/suggestions', [Shop\SearchController::class, 'suggestions'])
    ->middleware('throttle:search')->name('search.suggestions');
Route::get('/informations/{page}', [Shop\PageController::class, 'show'])
    ->whereIn('page', array_keys(Shop\PageController::PAGES))->name('pages.show');
Route::get('/contact', [Shop\PageController::class, 'contact'])->name('contact');

Route::post('/newsletter', [Shop\NewsletterController::class, 'store'])->middleware('throttle:forms')->name('newsletter.store');
Route::get('/newsletter/desinscription/{token}', [Shop\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::get('/sitemap.xml', [Shop\SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [Shop\SeoController::class, 'robots'])->name('robots');

// ---- Cart (guests allowed) -------------------------------------------------------
Route::prefix('panier')->name('cart.')->middleware('throttle:cart')->group(function () {
    Route::get('/', [Shop\CartController::class, 'index'])->name('index')->withoutMiddleware('throttle:cart');
    Route::get('/resume', [Shop\CartController::class, 'summary'])->name('summary');
    Route::post('/articles', [Shop\CartController::class, 'store'])->name('items.store');
    Route::patch('/articles/{item}', [Shop\CartController::class, 'update'])->name('items.update');
    Route::delete('/articles/{item}', [Shop\CartController::class, 'destroy'])->name('items.destroy');
    Route::post('/code-promo', [Shop\CartController::class, 'applyCoupon'])->name('coupon.apply');
    Route::delete('/code-promo', [Shop\CartController::class, 'removeCoupon'])->name('coupon.remove');
});

// ---- Payment provider callbacks ---------------------------------------------------
Route::post('/webhooks/paiements/{gateway}', [Shop\PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')->name('payments.webhook');

Route::post('/assistant', [Shop\AssistantController::class, 'message'])
    ->middleware('throttle:forms')->name('assistant.message');

// ---- Authentication ----------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [Auth\LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [Auth\LoginController::class, 'store'])->middleware('throttle:auth');
    Route::get('/inscription', [Auth\RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [Auth\RegisterController::class, 'store'])->middleware('throttle:auth');
    Route::get('/mot-de-passe/oublie', [Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/mot-de-passe/oublie', [Auth\PasswordResetController::class, 'email'])->middleware('throttle:auth')->name('password.email');
    Route::get('/mot-de-passe/reinitialiser/{token}', [Auth\PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/mot-de-passe/reinitialiser', [Auth\PasswordResetController::class, 'update'])->middleware('throttle:auth')->name('password.update');
});

Route::post('/deconnexion', [Auth\LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ---- Authenticated customer area ------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::post('/favoris/{product}', [Shop\WishlistController::class, 'toggle'])->middleware('throttle:cart')->name('wishlist.toggle');
    Route::post('/produit/{product:slug}/avis', [Shop\ReviewController::class, 'store'])->middleware('throttle:forms')->name('reviews.store');

    Route::get('/commande', [Shop\CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/commande', [Shop\CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');

    Route::get('/commande/{order}/paiement', [Shop\PaymentController::class, 'show'])->name('payments.show');
    Route::post('/commande/{order}/paiement', [Shop\PaymentController::class, 'store'])->middleware('throttle:checkout')->name('payments.store');
    Route::post('/paiements/{payment}/transfert', [Shop\PaymentController::class, 'submitTransfer'])->middleware('throttle:forms')->name('payments.transfer');
    Route::get('/paiements/{payment}/retour', [Shop\PaymentController::class, 'return'])->name('payments.return');
    Route::get('/paiements/{payment}/statut', [Shop\PaymentController::class, 'status'])->name('payments.status');
    Route::get('/commande/{order}/confirmation', [Shop\CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

    // Simulated provider page — only reachable when the sandbox gateway is available.
    Route::get('/paiements/sandbox/{payment}', [Shop\SandboxPaymentController::class, 'show'])->name('payments.sandbox.show');
    Route::post('/paiements/sandbox/{payment}', [Shop\SandboxPaymentController::class, 'complete'])->name('payments.sandbox.complete');

    Route::prefix('compte')->name('account.')->group(function () {
        Route::get('/', [Account\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profil', [Account\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profil', [Account\ProfileController::class, 'update'])->name('profile.update');
        Route::get('/securite', [Account\PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/securite', [Account\PasswordController::class, 'update'])->middleware('throttle:forms')->name('password.update');

        Route::get('/commandes', [Account\OrderController::class, 'index'])->name('orders.index');
        Route::get('/commandes/{order}', [Account\OrderController::class, 'show'])->name('orders.show');
        Route::post('/commandes/{order}/annuler', [Account\OrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('/commandes/{order}/retour', [Account\ReturnRequestController::class, 'create'])->name('returns.create');
        Route::post('/commandes/{order}/retour', [Account\ReturnRequestController::class, 'store'])->middleware('throttle:forms')->name('returns.store');

        Route::get('/adresses', [Account\AddressController::class, 'index'])->name('addresses.index');
        Route::post('/adresses', [Account\AddressController::class, 'store'])->name('addresses.store');
        Route::put('/adresses/{address}', [Account\AddressController::class, 'update'])->name('addresses.update');
        Route::delete('/adresses/{address}', [Account\AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::post('/adresses/{address}/defaut', [Account\AddressController::class, 'makeDefault'])->name('addresses.default');

        Route::get('/favoris', [Account\WishlistController::class, 'index'])->name('wishlist');
        Route::get('/avis', [Account\ReviewController::class, 'index'])->name('reviews');
        Route::delete('/avis/{review}', [Account\ReviewController::class, 'destroy'])->name('reviews.destroy');
    });
});
