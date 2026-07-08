<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OutletController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ShiftController;
use App\Http\Controllers\Api\V1\TableController;
use App\Http\Controllers\Api\V1\TaxController;
use App\Http\Controllers\Api\V1\UserOutletController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/v1/auth/register', [AuthController::class, 'register']);
Route::post('/v1/auth/login', [AuthController::class, 'login']);
Route::post('/v1/auth/login-pin', [AuthController::class, 'loginPin']);
Route::post('/v1/midtrans/notification', [PaymentController::class, 'notification']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/v1/auth/me', [AuthController::class, 'me']);
    Route::post('/v1/auth/logout', [AuthController::class, 'logout']);

    // Outlets
    Route::get('/v1/outlets', [OutletController::class, 'index']);
    Route::post('/v1/outlets', [OutletController::class, 'store']);
    Route::get('/v1/outlets/{outlet}', [OutletController::class, 'show']);
    Route::put('/v1/outlets/{outlet}', [OutletController::class, 'update']);
    Route::delete('/v1/outlets/{outlet}', [OutletController::class, 'destroy']);

    // Role assignment
    Route::post('/v1/user-outlets/assign', [UserOutletController::class, 'assignRole']);

    // Categories
    Route::get('/v1/categories', [CategoryController::class, 'index']);
    Route::post('/v1/categories', [CategoryController::class, 'store']);
    Route::put('/v1/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/v1/categories/{category}', [CategoryController::class, 'destroy']);

    // Products
    Route::get('/v1/products', [ProductController::class, 'index']);
    Route::post('/v1/products', [ProductController::class, 'store']);
    Route::get('/v1/products/{product}', [ProductController::class, 'show']);
    Route::put('/v1/products/{product}', [ProductController::class, 'update']);
    Route::delete('/v1/products/{product}', [ProductController::class, 'destroy']);

    // Taxes
    Route::get('/v1/taxes', [TaxController::class, 'index']);
    Route::post('/v1/taxes', [TaxController::class, 'store']);
    Route::put('/v1/taxes/{tax}', [TaxController::class, 'update']);
    Route::delete('/v1/taxes/{tax}', [TaxController::class, 'destroy']);

    // Discounts
    Route::get('/v1/discounts', [DiscountController::class, 'index']);
    Route::post('/v1/discounts', [DiscountController::class, 'store']);
    Route::put('/v1/discounts/{discount}', [DiscountController::class, 'update']);
    Route::delete('/v1/discounts/{discount}', [DiscountController::class, 'destroy']);

    // Tables
    Route::get('/v1/tables', [TableController::class, 'index']);
    Route::post('/v1/tables', [TableController::class, 'store']);
    Route::put('/v1/tables/{table}', [TableController::class, 'update']);
    Route::delete('/v1/tables/{table}', [TableController::class, 'destroy']);
    Route::post('/v1/tables/{table}/regenerate-qr', [TableController::class, 'regenerateQr']);

    // Orders
    Route::get('/v1/orders', [OrderController::class, 'index']);
    Route::post('/v1/orders', [OrderController::class, 'store']);
    Route::get('/v1/orders/{order}', [OrderController::class, 'show']);
    Route::post('/v1/orders/{order}/items', [OrderController::class, 'addItem']);
    Route::delete('/v1/orders/{order}/items/{itemId}', [OrderController::class, 'removeItem']);
    Route::put('/v1/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/v1/orders/{order}/pay/cash', [OrderController::class, 'payCash']);

    // Midtrans
    Route::post('/v1/orders/{order}/pay/midtrans', [PaymentController::class, 'snapToken']);

    // Shifts
    Route::get('/v1/shifts', [ShiftController::class, 'index']);
    Route::post('/v1/shifts/start', [ShiftController::class, 'start']);
    Route::post('/v1/shifts/{shift}/end', [ShiftController::class, 'end']);
});
