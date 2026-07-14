<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOutletAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $outletId = null;

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

        if ($outletId && !$user->outlets()->where('outlet_id', $outletId)->exists()) {
            abort(403, 'You do not have access to this outlet.');
        }

        return $next($request);
    }
}
