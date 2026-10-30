<?php

use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\DeliveryController as DriverDeliveryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\OperationsController;
use App\Http\Controllers\Staff\OrderController as StaffOrderController;
use App\Http\Controllers\Staff\ReportController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

$marketingPage = fn (string $view) => auth()->check()
    ? redirect()->route('dashboard')
    : view($view);

Route::get('/', fn () => $marketingPage('home'))->name('home');
Route::get('/about', fn () => $marketingPage('about'))->name('about');
Route::get('/services', fn () => $marketingPage('services'))->name('services');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin')->name('admin.dashboard');

    Route::get('/staff/dashboard', [OperationsController::class, 'dashboard'])
        ->middleware('role:'.User::ROLE_STAFF)->name('staff.dashboard');

    Route::prefix('staff')->name('staff.')->middleware('role:'.User::ROLE_STAFF)->group(function () {
        Route::get('/orders', [StaffOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [StaffOrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}/status', [StaffOrderController::class, 'updateStatus'])->name('orders.status');
        Route::put('/orders/{order}/driver', [StaffOrderController::class, 'assignDriver'])->name('orders.driver');
        Route::put('/orders/{order}/payment', [StaffOrderController::class, 'recordPayment'])->name('orders.payment');

        Route::get('/inventory', [OperationsController::class, 'inventory'])->name('inventory');
        Route::get('/containers', [OperationsController::class, 'containers'])->name('containers');

        Route::get('/reports', fn () => redirect()->route('staff.reports.index', ['type' => 'sales']))->name('reports');
        Route::get('/reports/{type}', [ReportController::class, 'index'])->name('reports.index');
    });

    Route::get('/driver/dashboard', [DriverDashboardController::class, 'index'])
        ->middleware('role:'.User::ROLE_DRIVER)->name('driver.dashboard');

    Route::prefix('driver')->name('driver.')->middleware('role:'.User::ROLE_DRIVER)->group(function () {
        Route::get('/deliveries', [DriverDeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('/deliveries/{delivery}', [DriverDeliveryController::class, 'show'])->name('deliveries.show');
        Route::put('/deliveries/{delivery}/status', [DriverDeliveryController::class, 'updateStatus'])->name('deliveries.status');
        Route::put('/deliveries/{delivery}/return', [DriverDeliveryController::class, 'recordReturn'])->name('deliveries.return');
    });

    Route::get('/customer/dashboard', [DashboardController::class, 'customer'])
        ->middleware('role:'.User::ROLE_CUSTOMER)->name('customer.dashboard');

    Route::prefix('customer')->name('customer.')->middleware('role:'.User::ROLE_CUSTOMER)->group(function () {
        Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [CustomerOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [CustomerOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');

        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
        Route::put('/cart/{key}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{key}', [CartController::class, 'destroy'])->name('cart.destroy');
        Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
    });

    // shared operations area: admin and staff see the same fulfillment tools
    Route::prefix('operations')->name('operations.')->middleware('role:'.User::ROLE_ADMIN.','.User::ROLE_STAFF)->group(function () {
        Route::get('/', [OperationsController::class, 'dashboard'])->name('dashboard');

        Route::get('/orders', [StaffOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [StaffOrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}/status', [StaffOrderController::class, 'updateStatus'])->name('orders.status');
        Route::put('/orders/{order}/driver', [StaffOrderController::class, 'assignDriver'])->name('orders.driver');
        Route::put('/orders/{order}/payment', [StaffOrderController::class, 'recordPayment'])->name('orders.payment');

        Route::get('/inventory', [OperationsController::class, 'inventory'])->name('inventory');
        Route::put('/inventory/{item}', [OperationsController::class, 'adjustInventory'])->name('inventory.adjust');
        Route::get('/containers', [OperationsController::class, 'containers'])->name('containers');
        Route::put('/containers/{status}', [OperationsController::class, 'adjustContainer'])->name('containers.adjust');
        Route::get('/rentals', [OperationsController::class, 'rentals'])->name('rentals.index');
        Route::put('/rentals/{rental}/return', [OperationsController::class, 'returnRental'])->name('rentals.return');

        Route::get('/reports', fn () => redirect()->route('operations.reports.index', ['type' => 'sales']))->name('reports');
        Route::get('/reports/{type}', [ReportController::class, 'index'])->name('reports.index');
    });

    // admin module
    Route::prefix('admin')->name('admin.')->middleware('role:'.User::ROLE_ADMIN)->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::put('/inventory', [InventoryController::class, 'update'])->name('inventory.update');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
