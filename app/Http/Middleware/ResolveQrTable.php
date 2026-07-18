<?php

namespace App\Http\Middleware;

use App\Models\RestTable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dipakai di route publik self-order (customer scan QR di meja).
 * Tidak ada auth:sanctum di sini — qr_token itu sendiri adalah "kredensial"-nya.
 *
 * Resolve qr_token dari route parameter {qrToken}, validasi meja & outlet
 * masih aktif, lalu bind ke request supaya controller tinggal pakai
 * $request->attributes->get('qr_table') / 'qr_outlet'.
 */
class ResolveQrTable
{
    public function handle(Request $request, Closure $next): Response
    {
        $qrToken = $request->route('qrToken');

        $table = RestTable::with('outlet')
            ->where('qr_token', $qrToken)
            ->where('is_active', true)
            ->first();

        if (!$table) {
            abort(404, 'QR meja tidak valid atau sudah tidak aktif. Silakan hubungi staf.');
        }

        if (!$table->outlet || !$table->outlet->is_active) {
            abort(404, 'Outlet sedang tidak aktif. Silakan hubungi staf.');
        }

        $request->attributes->set('qr_table', $table);
        $request->attributes->set('qr_outlet', $table->outlet);

        return $next($request);
    }
}
