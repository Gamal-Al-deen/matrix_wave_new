<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AboutPageContentController;
use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SettingsLogoController;
use App\Http\Controllers\Admin\SocialLinkController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WalletIconController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'show'])->name('about');

Route::get('/products', [CatalogController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [CatalogController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items/{product:slug}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/items/{productId}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/items/{productId}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'edit'])->name('account');
    Route::patch('/account', [AccountController::class, 'update'])->name('account.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/settings', [NotificationController::class, 'updateSettings'])->name('notifications.settings');
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->whereUuid('notification')
        ->name('notifications.read');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [InvoiceController::class, 'show'])->name('orders.invoice');
    Route::get('/orders/{order}/invoice/image', [InvoiceController::class, 'image'])->name('orders.invoice.image');
    Route::post('/orders/{order}/whatsapp', [OrderController::class, 'whatsapp'])->name('orders.whatsapp');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminSessionController::class, 'create'])->name('login');
    Route::post('/login', [AdminSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::middleware('admin')->group(function () {
        Route::post('/logout', [AdminSessionController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])
            ->whereUuid('customer')
            ->name('customers.show');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
            ->name('orders.status');
        Route::patch('/orders/{order}/payment-status', [AdminOrderController::class, 'updatePaymentStatus'])
            ->name('orders.payment-status');

        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::patch('/categories/{category}/status', [CategoryController::class, 'toggleStatus'])
            ->name('categories.status');

        Route::resource('products', ProductController::class)->except(['show']);

        Route::post('/products/{product}/images', [ProductImageController::class, 'store'])
            ->name('products.images.store');
        Route::post('/products/images/draft', [ProductImageController::class, 'storeDraft'])
            ->name('products.images.draft');
        Route::delete('/products/images/draft/{key}', [ProductImageController::class, 'destroyDraft'])
            ->name('products.images.draft.destroy');
        Route::post('/products/{product}/images/{image}/replace', [ProductImageController::class, 'replace'])
            ->name('products.images.replace');
        Route::patch('/products/{product}/images/{image}/move', [ProductImageController::class, 'move'])
            ->name('products.images.move');
        Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy'])
            ->name('products.images.destroy');

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/logo', [SettingsLogoController::class, 'store'])->name('settings.logo.store');
        Route::delete('/settings/logo', [SettingsLogoController::class, 'destroy'])->name('settings.logo.destroy');

        Route::get('/account', [AdminAccountController::class, 'edit'])->name('account.edit');
        Route::put('/account', [AdminAccountController::class, 'update'])->name('account.update');

        Route::get('/about', [AboutPageContentController::class, 'edit'])->name('about.edit');
        Route::put('/about', [AboutPageContentController::class, 'update'])->name('about.update');

        Route::patch('/social-links/{socialLink}/status', [SocialLinkController::class, 'toggleStatus'])
            ->name('social-links.status');
        Route::resource('social-links', SocialLinkController::class)
            ->except(['show'])
            ->parameters(['social-links' => 'socialLink']);

        Route::patch('/banners/{banner}/status', [BannerController::class, 'toggleStatus'])
            ->name('banners.status');
        Route::post('/banners/{banner}/image', [BannerController::class, 'replaceImage'])
            ->name('banners.image.replace');
        Route::resource('banners', BannerController::class)->except(['show']);

        Route::patch('/wallets/{wallet}/status', [WalletController::class, 'toggleStatus'])
            ->name('wallets.status');
        Route::post('/wallets/{wallet}/icon', [WalletIconController::class, 'store'])
            ->name('wallets.icon.store');
        Route::delete('/wallets/{wallet}/icon', [WalletIconController::class, 'destroy'])
            ->name('wallets.icon.destroy');
        Route::resource('wallets', WalletController::class)->except(['show']);
    });
});
