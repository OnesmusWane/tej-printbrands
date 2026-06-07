<?php

use App\Http\Controllers\SiteController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminPanelController;
use App\Http\Controllers\PublicSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/services', [SiteController::class, 'services'])->name('services');
Route::get('/services/{slug}', [SiteController::class, 'serviceDetail'])->name('service.detail');
Route::get('/blog', [SiteController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [SiteController::class, 'blogDetail'])->name('blog.detail');
Route::get('/work', [SiteController::class, 'work'])->name('work');
Route::get('/gallery', [SiteController::class, 'gallery'])->name('gallery');
Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicSubmissionController::class, 'contact'])->name('contact.submit');
Route::post('/quote-request', [PublicSubmissionController::class, 'quote'])->name('quote.submit');
Route::post('/service-booking', [PublicSubmissionController::class, 'booking'])->name('service-booking.submit');
Route::get('/booking', [SiteController::class, 'booking'])->name('booking');
Route::post('/booking', [SiteController::class, 'submitBooking'])->name('booking.submit');
Route::get('/products', [SiteController::class, 'products'])->name('products');
Route::post('/products/{slug}/cart', [SiteController::class, 'addToCart'])->name('cart.add');
Route::get('/products/{_slug}/checkout', [SiteController::class, 'legacyCheckout'])->name('products.checkout');
Route::get('/cart', [SiteController::class, 'cart'])->name('cart');
Route::patch('/cart', [SiteController::class, 'updateCart'])->name('cart.update');
Route::delete('/cart/{key}', [SiteController::class, 'removeFromCart'])->name('cart.remove');
Route::get('/checkout', [SiteController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [SiteController::class, 'submitCheckout'])->name('checkout.submit');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AccountController::class, 'login'])->name('login');
    Route::post('/login', [AccountController::class, 'authenticate'])->name('login.submit');
    Route::get('/register', [AccountController::class, 'register'])->name('register');
    Route::post('/register', [AccountController::class, 'storeRegistration'])->name('register.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AccountController::class, 'logout'])->name('logout');
    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::patch('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::post('/account/delete-request', [AccountController::class, 'requestDeletion'])->name('account.delete.request');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/account/orders/{order}', [AccountController::class, 'showOrder'])->name('account.orders.show');
    Route::get('/account/orders/{order}/mpesa', [AccountController::class, 'mpesa'])->name('account.orders.mpesa');
    Route::post('/account/orders/{order}/mpesa/confirm', [AccountController::class, 'confirmMpesa'])->name('account.orders.mpesa.confirm');
    Route::post('/account/orders/{order}/mpesa/fail', [AccountController::class, 'failMpesa'])->name('account.orders.mpesa.fail');
    Route::get('/account/orders/{order}/payment/success', [AccountController::class, 'paymentSuccess'])->name('account.orders.payment.success');
    Route::get('/account/orders/{order}/payment/failed', [AccountController::class, 'paymentFailed'])->name('account.orders.payment.failed');
});

Route::get('/admin/2fa',         [App\Http\Controllers\Auth\TwoFactorController::class, 'show'])->name('admin.2fa');
Route::post('/admin/2fa',        [App\Http\Controllers\Auth\TwoFactorController::class, 'verify'])->name('admin.2fa.verify');
Route::post('/admin/2fa/resend', [App\Http\Controllers\Auth\TwoFactorController::class, 'resend'])->name('admin.2fa.resend');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/{any?}', AdminPanelController::class)->name('dashboard')->where('any', '.*');
});
