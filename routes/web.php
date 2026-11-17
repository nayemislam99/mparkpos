<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataTableController;
use App\Http\Controllers\DataTableExportController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\PosTerminalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::prefix('/dashboard')->middleware(['auth'])->group(function () {
    // logout route
    Route::controller(AuthController::class)->prefix('/')->name('auth.')->group(function () {
        Route::post('/logout', 'logout')->name('logout');
    });

    Route::get('/', function () {
        return view('admin.layouts.dashboard');
    })->middleware('can:dashboard.view')->name('dashboard.index');

    Route::get('/dt/{key}', [DataTableController::class, 'handle'])->name('dt.handle');
    Route::get('/dt/{key}/export/{format}/{language?}', [DataTableExportController::class, 'download'])
        ->name('dt.export');
    Route::view('/users', 'users.index')->middleware('can:users.view')->name('users.index');

    Route::prefix('/pos')->name('pos.')->middleware('can:sales.create')->controller(PosTerminalController::class)->group(function () {
        Route::get('/', 'page')->name('terminal');
        Route::get('/context', 'context')->name('context');
        Route::get('/products', 'products')->middleware('can:products.view')->name('products');
        Route::get('/customers', 'customers')->middleware('can:customers.view')->name('customers');
        Route::post('/customers', 'storeCustomer')->middleware('can:customers.create')->name('customers.store');
        Route::get('/due-invoices', 'dueInvoices')->middleware('can:sales.view')->name('due-invoices');
        Route::post('/checkout', 'checkout')->name('checkout');
        Route::post('/invoices/{sale}/payments', 'collectInvoicePayment')
            ->middleware('can:sales.collect_payment')
            ->name('invoice-payments.store');
    });
});

/**
 * outer section without authentication
 */
Route::middleware('guest')->group(function () {
    // Login (controller based)
    Route::controller(AuthController::class)->prefix('/')->name('auth.')->group(function () {
        Route::get('/login', 'login')->name('login');
        Route::post('/login', 'authenticate')->name('login.submit');
    });

    // Forgot password (optional, future use)
    Route::controller(ForgotPasswordController::class)->prefix('/')->name('forgot.')->group(function () {
        Route::get('/forgot/password', 'forgotPasswordView')->name('password');
        Route::post('/forgot/password', 'forgotPasswordSend')->name('password.submit');

        Route::get('/reset/password/{token}', 'resetPasswordView')->name('password.reset');
        Route::post('/reset/password', 'resetPassword')->name('password.reset.submit');
    });
});
