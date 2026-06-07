<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\GalleryItemController;
use App\Http\Controllers\Admin\SiteSectionController;
use App\Http\Controllers\Admin\QuoteRequestController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminResourceController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\BookingPaymentController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\ServiceRequestController;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->name('api.admin.')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

    // Image upload — stores file and returns public URL
    Route::post('/upload', function (Request $request) {
        $request->validate(['file' => ['required', 'file', 'image', 'max:8192']]);
        $path = $request->file('file')->store('uploads/content', 'public');
        return response()->json(['url' => asset('storage/' . $path)]);
    })->name('upload');

    // Site settings: key-based upsert (Vue Settings page patches by key string)
    Route::patch('/site-settings/{key}', function (Request $request, string $key) {
        $record = SiteSetting::updateOrCreate(['key' => $key], ['value' => $request->input('value')]);
        return response()->json($record);
    })->name('settings.update')->where('key', '[a-z_]+');

    Route::post('/orders/create', [AdminOrderController::class, 'store'])->name('orders.create');
    Route::get('/orders/products', [AdminOrderController::class, 'products'])->name('orders.products');
    Route::get('/orders/clients', [AdminOrderController::class, 'clients'])->name('orders.clients');

    // Gallery items — must be before wildcard catch-all (handles multipart file upload)
    Route::post('/gallery-items', [GalleryItemController::class, 'store'])->name('gallery-items.store');
    Route::delete('/gallery-items/{galleryItem}', [GalleryItemController::class, 'destroy'])->name('gallery-items.destroy');

    // Site sections — must be before wildcard catch-all
    Route::get('/site-sections', [SiteSectionController::class, 'index'])->name('site-sections.index');

    // Quote Requests
    Route::patch('/quote-requests/{quoteRequest}/status', [QuoteRequestController::class, 'updateStatus'])->name('quote-requests.status');

    // Quotations
    Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
    Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
    Route::patch('/quotations/{quotation}', [QuotationController::class, 'update'])->name('quotations.update');
    Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy');
    Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quotations.send');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');

    // Service Requests
    Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests.index');
    Route::get('/service-requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('service-requests.show');
    Route::patch('/service-requests/{serviceRequest}/status', [ServiceRequestController::class, 'updateStatus'])->name('service-requests.status');
    Route::post('/service-requests/{serviceRequest}/convert', [ServiceRequestController::class, 'convertToBooking'])->name('service-requests.convert');

    // Bookings
    Route::get('/service-bookings', [BookingController::class, 'index'])->name('service-bookings.index');
    Route::post('/service-bookings', [BookingController::class, 'store'])->name('service-bookings.store');
    Route::patch('/service-bookings/{serviceBooking}', [BookingController::class, 'update'])->name('service-bookings.update');

    // Booking payments
    Route::post('/service-bookings/{booking}/pay', [BookingPaymentController::class, 'store'])->name('bookings.pay');

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.record');
    Route::patch('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');

    Route::post('profile/change-password', function(Illuminate\Http\Request $request) {
        $request->validate(['current_password'=>['required','current_password'],'password'=>['required','min:8','confirmed']]);
        $request->user()->update(['password'=>bcrypt($request->password)]);
        return response()->json(['message'=>'Password updated successfully.']);
    })->name('profile.change-password');

    // User management
    Route::get('/admin-users', [UserController::class, 'index'])->name('admin-users.index');
    Route::post('/admin-users', [UserController::class, 'store'])->name('admin-users.store');
    Route::patch('/admin-users/{user}', [UserController::class, 'update'])->name('admin-users.update');
    Route::delete('/admin-users/{user}', [UserController::class, 'destroy'])->name('admin-users.destroy');

    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/add-job', [ProjectController::class, 'addJob'])->name('projects.add-job');

    Route::get('/{resource}', [AdminResourceController::class, 'index'])->name('resources.index');
    Route::post('/{resource}', [AdminResourceController::class, 'store'])->name('resources.store');
    Route::get('/{resource}/{id}', [AdminResourceController::class, 'show'])->name('resources.show');
    Route::patch('/{resource}/{id}', [AdminResourceController::class, 'update'])->name('resources.update');
    Route::delete('/{resource}/{id}', [AdminResourceController::class, 'destroy'])->name('resources.destroy');
});
