<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOutletAccess
{
    /**
     * Hierarki role: kunci = role, nilai = semua role yang dianggap "punya akses minimal sekelas"
     * Digunakan untuk whitelist aksi berdasarkan role user di outlet tersebut.
     */
    const ROLE_HIERARCHY = [
        'developer' => 5,
        'admin'     => 4, // owner
        'manager'   => 3,
        'cashier'   => 2,
        'kitchen'   => 1,
    ];

    /**
     * Minimum role level per kombinasi method + path pattern.
     * Pattern dievaluasi secara berurutan (first match wins).
     */
    const ROUTE_PERMISSIONS = [
        // Laporan — hanya developer, admin, manager
        ['method' => 'GET',    'pattern' => 'v1/reports',          'min_role' => 'manager'],

        // Outlet delete — hanya developer & admin
        ['method' => 'DELETE', 'pattern' => 'v1/outlets',          'min_role' => 'admin'],
        ['method' => 'PUT',    'pattern' => 'v1/outlets',          'min_role' => 'admin'],

        // Pajak & Diskon — manager ke atas
        ['method' => 'POST',   'pattern' => 'v1/taxes',            'min_role' => 'manager'],
        ['method' => 'PUT',    'pattern' => 'v1/taxes',            'min_role' => 'manager'],
        ['method' => 'DELETE', 'pattern' => 'v1/taxes',            'min_role' => 'manager'],
        ['method' => 'POST',   'pattern' => 'v1/discounts',        'min_role' => 'manager'],
        ['method' => 'PUT',    'pattern' => 'v1/discounts',        'min_role' => 'manager'],
        ['method' => 'DELETE', 'pattern' => 'v1/discounts',        'min_role' => 'manager'],

        // Produk & Kategori — manager ke atas untuk write
        ['method' => 'POST',   'pattern' => 'v1/products',         'min_role' => 'manager'],
        ['method' => 'PUT',    'pattern' => 'v1/products',         'min_role' => 'manager'],
        ['method' => 'DELETE', 'pattern' => 'v1/products',         'min_role' => 'manager'],
        ['method' => 'POST',   'pattern' => 'v1/categories',       'min_role' => 'manager'],
        ['method' => 'PUT',    'pattern' => 'v1/categories',       'min_role' => 'manager'],
        ['method' => 'DELETE', 'pattern' => 'v1/categories',       'min_role' => 'manager'],

        // Withdraw — hanya admin ke atas
        ['method' => 'POST',   'pattern' => 'v1/withdraw/withdraw', 'min_role' => 'admin'],

        // Refund — manager ke atas
        ['method' => 'POST',   'pattern' => 'v1/orders',           'min_role' => 'cashier'],
        ['method' => 'POST',   'pattern' => 'refund',              'min_role' => 'manager'],

        // Tables write — manager ke atas
        ['method' => 'POST',   'pattern' => 'v1/tables',           'min_role' => 'manager'],
        ['method' => 'PUT',    'pattern' => 'v1/tables',           'min_role' => 'manager'],
        ['method' => 'DELETE', 'pattern' => 'v1/tables',           'min_role' => 'manager'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user     = $request->user();
        $outletId = null;

        // Resolve outlet_id dari query param atau dari route model
        if ($request->filled('outlet_id')) {
            $outletId = $request->input('outlet_id');
        }

        if (!$outletId) {
            $models = ['outlet', 'product', 'category', 'tax', 'discount', 'table', 'order', 'shift'];
            foreach ($models as $param) {
                $model = $request->route($param);
                if ($model && isset($model->outlet_id)) {
                    $outletId = $model->outlet_id;
                    break;
                }
                if ($model && $model instanceof \App\Models\Outlet) {
                    $outletId = $model->id;
                    break;
                }
            }
        }

        // Cek apakah user punya akses ke outlet ini
        if ($outletId) {
            $pivot = $user->outlets()->where('outlet_id', $outletId)->first()?->pivot;

            if (!$pivot) {
                abort(403, 'You do not have access to this outlet.');
            }

            $userRole = $pivot->role;

            // Cek apakah role user cukup untuk aksi ini
            if (!$this->hasRoleAccess($request, $userRole)) {
                abort(403, "Role '{$userRole}' tidak diizinkan melakukan aksi ini.");
            }
        }

        return $next($request);
    }

    /**
     * Periksa apakah role user memenuhi minimum role yang dibutuhkan untuk route ini.
     */
    protected function hasRoleAccess(Request $request, string $userRole): bool
    {
        $method  = $request->method();
        $path    = $request->path(); // e.g. "api/v1/reports/summary"

        foreach (self::ROUTE_PERMISSIONS as $rule) {
            $methodMatch  = strtoupper($rule['method']) === strtoupper($method);
            $patternMatch = str_contains($path, $rule['pattern']);

            if ($methodMatch && $patternMatch) {
                $required = self::ROLE_HIERARCHY[$rule['min_role']] ?? 0;
                $actual   = self::ROLE_HIERARCHY[$userRole] ?? 0;
                return $actual >= $required;
            }
        }

        // Default: semua role yang punya akses outlet boleh (GET read, dll)
        return true;
    }
}
