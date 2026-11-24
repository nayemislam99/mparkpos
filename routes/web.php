<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarcodePrintController;
use App\Http\Controllers\BarcodeSearchController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchRolePermissionController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataTableController;
use App\Http\Controllers\DataTableExportController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\PosTerminalController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UnitTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// Language switcher
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'bn'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');

Route::prefix('/dashboard')->middleware(['auth'])->group(function () {
    // logout route
    Route::controller(AuthController::class)->prefix('/')->name('auth.')->group(function () {
        Route::post('/logout', 'logout')->name('logout');
    });

    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('can:dashboard.view')
        ->name('dashboard.index');

    Route::get('/dt/{key}', [DataTableController::class, 'handle'])->name('dt.handle');
    Route::get('/dt/{key}/export/{format}/{language?}', [DataTableExportController::class, 'download'])
        ->name('dt.export');

    // Users Management
    Route::prefix('/users')->name('users.')->controller(UserController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:users.view')->name('index');
        Route::get('/create', 'create')->middleware('can:users.create')->name('create');
        Route::post('/', 'store')->middleware('can:users.create')->name('store');
        Route::get('/{user}/edit', 'edit')->middleware('can:users.update')->name('edit');
        Route::put('/{user}', 'update')->middleware('can:users.update')->name('update');
        Route::post('/{user}/toggle', 'toggleStatus')->middleware('can:users.update')->name('toggle');
        Route::delete('/{user}', 'destroy')->middleware('can:users.delete')->name('destroy');
    });

    // Shop Branch: Branch management
    Route::prefix('/branches')->name('branches.')->controller(BranchController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:branches.view')->name('index');
        Route::post('/', 'store')->middleware('can:branches.create')->name('store');
        Route::get('/{branch}', 'show')->middleware('can:branches.view')->name('show');
        Route::put('/{branch}', 'update')->middleware('can:branches.update')->name('update');
        Route::delete('/{branch}', 'destroy')->middleware('can:branches.delete')->name('destroy');
    });

    // Shop Branch: Branch Role Permissions
    Route::prefix('/branch-roles')->name('branch-roles.')->controller(BranchRolePermissionController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:roles.view')->name('index');
        Route::post('/role', 'storeRole')->middleware('can:roles.update')->name('role.store');
        Route::get('/permissions/{role}', 'getPermissions')->middleware('can:roles.view')->name('permissions');
        Route::post('/', 'update')->middleware('can:roles.update')->name('update');
        Route::post('/reset', 'reset')->middleware('can:roles.update')->name('reset');
        Route::post('/assign', 'assignStaff')->middleware('can:roles.update')->name('assign');
    });

    // Shop Branch: CRM (Branch Users / Staff)
    Route::prefix('/crm')->name('crm.')->controller(CrmController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:users.view')->name('index');
        Route::post('/users', 'store')->middleware('can:users.create')->name('users.store');
        Route::get('/users/{crmUser}', 'show')->middleware('can:users.view')->name('users.show');
        Route::put('/users/{crmUser}', 'update')->middleware('can:users.update')->name('users.update');
        Route::post('/users/{crmUser}/status', 'toggleStatus')->middleware('can:users.update')->name('users.status');
        Route::post('/users/{crmUser}/login', 'loginAs')->middleware('can:users.view')->name('users.login');
    });

    // Universal Barcode Search API (Used by Barcode Print, POS, Purchase, Damage, Return)
    Route::get('/api/barcode-search', [BarcodeSearchController::class, 'search'])->name('api.barcode-search');

    // Products Management
    Route::prefix('/products')->name('products.')->controller(ProductController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:products.view')->name('index');
        Route::get('/create', 'create')->middleware('can:products.create')->name('create');
        Route::post('/', 'store')->middleware('can:products.create')->name('store');
        Route::post('/{product}/toggle', 'toggleStatus')->middleware('can:products.update')->name('toggle');
        Route::delete('/{product}', 'destroy')->middleware('can:products.delete')->name('destroy');
        Route::get('/generate-sku', 'generateSku')->name('generate-sku');
        Route::get('/generate-barcode', 'generateBarcode')->name('generate-barcode');
    });

    // Brands Management
    Route::prefix('/brands')->name('brands.')->controller(BrandController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:products.view')->name('index');
        Route::post('/', 'store')->middleware('can:products.create')->name('store');
        Route::get('/{brand}', 'show')->middleware('can:products.view')->name('show');
        Route::put('/{brand}', 'update')->middleware('can:products.update')->name('update');
        Route::post('/{brand}/toggle', 'toggleStatus')->middleware('can:products.update')->name('toggle');
        Route::delete('/{brand}', 'destroy')->middleware('can:products.delete')->name('destroy');
    });

    // Categories Management
    Route::prefix('/categories')->name('categories.')->controller(CategoryController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:categories.view')->name('index');
        Route::post('/', 'store')->middleware('can:categories.create')->name('store');
        Route::get('/{category}', 'show')->middleware('can:categories.view')->name('show');
        Route::put('/{category}', 'update')->middleware('can:categories.update')->name('update');
        Route::post('/{category}/toggle', 'toggleStatus')->middleware('can:categories.update')->name('toggle');
        Route::delete('/{category}', 'destroy')->middleware('can:categories.delete')->name('destroy');
    });

    // Unit Types Management
    Route::prefix('/unit-types')->name('unit-types.')->controller(UnitTypeController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:products.view')->name('index');
        Route::post('/', 'store')->middleware('can:products.create')->name('store');
        Route::get('/{unitType}', 'show')->middleware('can:products.view')->name('show');
        Route::put('/{unitType}', 'update')->middleware('can:products.update')->name('update');
        Route::post('/{unitType}/toggle', 'toggleStatus')->middleware('can:products.update')->name('toggle');
        Route::delete('/{unitType}', 'destroy')->middleware('can:products.delete')->name('destroy');
    });

    // Variations Management
    Route::prefix('/variations')->name('variations.')->controller(VariationController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:products.view')->name('index');
        Route::post('/', 'store')->middleware('can:products.create')->name('store');
        Route::get('/{variation}', 'show')->middleware('can:products.view')->name('show');
        Route::put('/{variation}', 'update')->middleware('can:products.update')->name('update');
        Route::post('/{variation}/add-item', 'addItem')->middleware('can:products.update')->name('add-item');
        Route::post('/{variation}/toggle', 'toggleStatus')->middleware('can:products.update')->name('toggle');
    });

    // Barcode Printing & Reports
    Route::prefix('/barcodes')->name('barcodes.')->controller(BarcodePrintController::class)->group(function () {
        Route::get('/print', 'printPage')->middleware('can:products.view')->name('print');
        Route::post('/log-print', 'logPrint')->middleware('can:products.view')->name('log-print');
        Route::get('/report', 'report')->middleware('can:products.view')->name('report');
    });

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
