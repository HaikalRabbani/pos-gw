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

// Landing page publik (PesenApa) — halaman pertama sebelum daftar.
// Harus sebelum catch-all admin, dan hanya cocok untuk path "/" persis.
Route::get('/', function () {
    return view('landing');
})->name('landing');

// Admin Panel — catch all other routes
Route::get('/{any?}', function () {
    return view('admin');
})->where('any', '.*');
