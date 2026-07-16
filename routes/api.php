<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\V1\IngredientController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OutletController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ShiftController;
use App\Http\Controllers\Api\V1\ShiftScheduleController;
use App\Http\Controllers\Api\V1\ShiftTypeController;
use App\Http\Controllers\Api\V1\StationController;
use App\Http\Controllers\Api\V1\TableController;
use App\Http\Controllers\Api\V1\TaxController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserOutletController;
use App\Http\Controllers\Api\V1\WithdrawController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Public — with rate limiting to prevent brute force attacks
Route::post('/v1/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,60');
Route::post('/v1/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,60');
Route::post('/v1/auth/login-pin', [AuthController::class, 'loginPin'])->middleware('throttle:10,60');
// Payment callbacks
Route::post('/v1/midtrans/notification', [PaymentController::class, 'midtransNotification']);
Route::post('/v1/xendit/callback', [PaymentController::class, 'xenditCallback']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/v1/auth/me', [AuthController::class, 'me']);
    Route::put('/v1/auth/me', [AuthController::class, 'updateProfile']);
    Route::post('/v1/auth/logout', [AuthController::class, 'logout']);

    // Outlets — index/store are open to any authenticated user
    Route::get('/v1/outlets', [OutletController::class, 'index']);
    Route::post('/v1/outlets', [OutletController::class, 'store']);

    // Role assignment
    Route::post('/v1/user-outlets/assign', [UserOutletController::class, 'assignRole']);

    // Users (tenant-scoped, no outlet check)
    Route::get('/v1/users', [UserController::class, 'index']);
    Route::get('/v1/users/{user}', [UserController::class, 'show']);
    Route::put('/v1/users/{user}', [UserController::class, 'update']);
    Route::post('/v1/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    Route::post('/v1/users/{user}/pin', [UserController::class, 'setPin']);

    // Dashboard (aggregate across all user outlets, no single outlet_id needed)
    Route::get('/v1/dashboard', [DashboardController::class, 'index']);

    // All routes below require outlet access verification
    Route::middleware('outlet.access')->group(function () {
        // Outlets (individual)
        Route::get('/v1/outlets/{outlet}', [OutletController::class, 'show']);
        Route::put('/v1/outlets/{outlet}', [OutletController::class, 'update']);
        Route::delete('/v1/outlets/{outlet}', [OutletController::class, 'destroy']);

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

        // Product Customize (Self-Order)
        Route::get('/v1/products/{product}/customize', [IngredientController::class, 'customize']);

        // Product Ingredients
        Route::get('/v1/products/{product}/ingredients', [IngredientController::class, 'productIngredients']);
        Route::post('/v1/products/{product}/ingredients', [IngredientController::class, 'syncProductIngredients']);

        // Ingredients
        Route::get('/v1/ingredients', [IngredientController::class, 'index']);
        Route::post('/v1/ingredients', [IngredientController::class, 'store']);
        Route::put('/v1/ingredients/{ingredient}', [IngredientController::class, 'update']);
        Route::delete('/v1/ingredients/{ingredient}', [IngredientController::class, 'destroy']);

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

        // Stations
        Route::get('/v1/stations', [StationController::class, 'index']);
        Route::post('/v1/stations', [StationController::class, 'store']);
        Route::put('/v1/stations/{station}', [StationController::class, 'update']);
        Route::delete('/v1/stations/{station}', [StationController::class, 'destroy']);

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

        // Refund
        Route::post('/v1/orders/{order}/refund', [OrderController::class, 'refund']);

        // Split Bill
        Route::post('/v1/orders/{order}/split', [OrderController::class, 'split']);

        // Merge Bill
        Route::post('/v1/orders/merge', [OrderController::class, 'merge']);

        // Print groups by station (for Flutter thermal printer routing)
        Route::get('/v1/orders/{order}/print-groups', [OrderController::class, 'printGroups']);

        // Midtrans
        Route::post('/v1/orders/{order}/pay/midtrans', [PaymentController::class, 'snapToken']);

        // Shift Types (master data)
        Route::get('/v1/shift-types', [ShiftTypeController::class, 'index']);
        Route::post('/v1/shift-types', [ShiftTypeController::class, 'store']);
        Route::put('/v1/shift-types/{shiftType}', [ShiftTypeController::class, 'update']);
        Route::delete('/v1/shift-types/{shiftType}', [ShiftTypeController::class, 'destroy']);

        // Shift Schedules (scheduling)
        Route::get('/v1/shift-schedules', [ShiftScheduleController::class, 'index']);
        Route::post('/v1/shift-schedules', [ShiftScheduleController::class, 'store']);
        Route::put('/v1/shift-schedules/{shiftSchedule}', [ShiftScheduleController::class, 'update']);
        Route::delete('/v1/shift-schedules/{shiftSchedule}', [ShiftScheduleController::class, 'destroy']);
        Route::post('/v1/shift-schedules/generate', [ShiftScheduleController::class, 'generate']);

        // Shifts (actual cashier shifts)
        Route::get('/v1/shifts', [ShiftController::class, 'index']);
        Route::post('/v1/shifts/start', [ShiftController::class, 'start']);
        Route::post('/v1/shifts/{shift}/end', [ShiftController::class, 'end']);

        // Reports
        Route::prefix('/v1/reports')->group(function () {
            Route::get('/summary', [ReportController::class, 'summary']);
            Route::get('/daily-sales', [ReportController::class, 'dailySales']);
            Route::get('/top-products', [ReportController::class, 'topProducts']);
            Route::get('/export-excel', [ReportController::class, 'exportExcel']);
            Route::get('/export-pdf', [ReportController::class, 'exportPdf']);
            Route::get('/export-shift-excel', [ReportController::class, 'exportShiftExcel']);
            Route::get('/export-shift-pdf', [ReportController::class, 'exportShiftPdf']);
        });



        // Upload file
        Route::post('/v1/upload', function (Request $request) {
            $request->validate(['file' => 'required|image|max:2048']);
            $path = $request->file('file')->store('uploads', 'public');
            return response()->json([
                'success' => true,
                'url' => Storage::url($path),
            ]);
        });

        // Withdraw (balance + payout)
        Route::prefix('/v1/withdraw')->group(function () {
            Route::get('/balance', [WithdrawController::class, 'balance']);
            Route::get('/transactions', [WithdrawController::class, 'transactions']);
            Route::get('/withdrawals', [WithdrawController::class, 'withdrawals']);
            Route::post('/withdraw', [WithdrawController::class, 'withdraw']);
        });
    });
});
