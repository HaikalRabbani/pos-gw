<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Serve the Vue SPA for all non-API routes.
|
*/

// Self-Order — public QR menu (sebelum catch-all admin)
// qr_token bersifat unique global di tabel tables
Route::get('/order/{qrToken}', function ($qrToken) {
    return view('self-order');
})->where('qrToken', '[a-zA-Z0-9]+');

// Admin Panel — catch all other routes
Route::get('/{any?}', function () {
    return view('admin');
})->where('any', '.*');
