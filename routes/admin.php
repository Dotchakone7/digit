<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
| Back-office. Every route requires an active staff account with the
| "admin.access" ability; each section then checks its own ability.
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.access'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard');

    Route::middleware('can:catalog.manage')->group(function () {
        Route::resource('produits', Admin\ProductController::class)->parameters(['produits' => 'product'])
            ->names('products')->except('show');
        Route::post('produits/{product}/statut', [Admin\ProductController::class, 'updateStatus'])->name('products.status');
        Route::post('produits/{product}/images', [Admin\ProductImageController::class, 'store'])->name('products.images.store');
        Route::post('produits/{product}/images/{image}/principale', [Admin\ProductImageController::class, 'primary'])->name('products.images.primary');
        Route::delete('produits/{product}/images/{image}', [Admin\ProductImageController::class, 'destroy'])->name('products.images.destroy');

        Route::resource('categories', Admin\CategoryController::class)->parameters(['categories' => 'category'])->except('show');
    });

    Route::middleware('can:orders.manage')->group(function () {
        Route::get('commandes', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('commandes/export', [Admin\OrderController::class, 'export'])->name('orders.export');
        Route::get('commandes/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::post('commandes/{order}/statut', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('commandes/{order}/expedition', [Admin\OrderController::class, 'storeShipment'])->name('orders.shipment');
        Route::get('commandes/{order}/livreur', [Admin\OrderController::class, 'contactCourier'])->name('orders.courier');

        Route::resource('livraisons', Admin\ShippingMethodController::class)->parameters(['livraisons' => 'shippingMethod'])
            ->names('shipping-methods')->except('show');
    });

    Route::middleware('can:payments.manage')->group(function () {
        Route::get('paiements', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::post('paiements/{payment}/confirmer', [Admin\PaymentController::class, 'confirm'])->name('payments.confirm');
        Route::post('paiements/{payment}/rejeter', [Admin\PaymentController::class, 'reject'])->name('payments.reject');
    });

    Route::middleware('can:reviews.moderate')->group(function () {
        Route::get('avis', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::patch('avis/{review}', [Admin\ReviewController::class, 'update'])->name('reviews.update');
        Route::delete('avis/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');
    });

    Route::middleware('can:coupons.manage')->group(function () {
        Route::resource('promotions', Admin\CouponController::class)->parameters(['promotions' => 'coupon'])
            ->names('coupons')->except('show');
    });

    Route::middleware('can:returns.manage')->group(function () {
        Route::get('retours', [Admin\ReturnRequestController::class, 'index'])->name('returns.index');
        Route::patch('retours/{returnRequest}', [Admin\ReturnRequestController::class, 'update'])->name('returns.update');
    });

    Route::middleware('can:customers.view')->group(function () {
        Route::get('utilisateurs', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('utilisateurs/{user}', [Admin\UserController::class, 'show'])->name('users.show');
        Route::patch('utilisateurs/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::delete('utilisateurs/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('can:settings.manage')->group(function () {
        Route::get('parametres', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('parametres', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::get('newsletter', [Admin\NewsletterController::class, 'index'])->name('newsletter.index');
        Route::get('newsletter/export', [Admin\NewsletterController::class, 'export'])->name('newsletter.export');
    });
});
