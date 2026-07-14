<?php

use App\Models\Outlet;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('outlet.{outletId}', function ($user, $outletId) {
    return $user->outlets()->where('outlet_id', $outletId)->exists();
});
