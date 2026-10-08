<?php

use App\Http\Controllers\Api\ContainerController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// these feed the blade pages rather than stateless clients, so they run on the
// web stack: the session cookie is encrypted and CSRF still applies
$internal = [User::ROLE_ADMIN, User::ROLE_STAFF];
$readable = [User::ROLE_ADMIN, User::ROLE_STAFF, User::ROLE_DRIVER];

Route::middleware(['web', 'auth', 'role:'.implode(',', $internal)])->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::get('/notifications/low-stock', [InventoryController::class, 'lowStock']);
});

Route::middleware(['web', 'auth'])->group(function () use ($readable) {
    Route::get('/containers', [ContainerController::class, 'index'])
        ->middleware('role:'.implode(',', $readable));

    // placing an order is customer only, matching public registration
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('role:'.User::ROLE_CUSTOMER)
        ->name('api.orders.store');
});
