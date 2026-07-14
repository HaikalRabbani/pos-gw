<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shift;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    public function startShift(int $userId, int $outletId, int $cashBegin): Shift
    {
        $active = Shift::where('user_id', $userId)
            ->whereNull('end_at')
            ->first();

        if ($active) {
            throw ValidationException::withMessages([
                'shift' => ['Already have an active shift.'],
            ]);
        }

        return Shift::create([
            'user_id' => $userId,
            'outlet_id' => $outletId,
            'start_at' => now(),
            'cash_begin' => $cashBegin,
        ]);
    }

    public function endShift(Shift $shift, int $cashActual): Shift
    {
        if ($shift->end_at) {
            throw ValidationException::withMessages([
                'shift' => ['Shift already ended.'],
            ]);
        }

        $expected = $shift->cash_begin + (int) Order::where('outlet_id', $shift->outlet_id)
            ->where('payment_method', 'cash')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$shift->start_at, now()])
            ->sum('grand_total');

        $shift->update([
            'end_at' => now(),
            'cash_expected' => $expected,
            'cash_actual' => $cashActual,
            'cash_diff' => $cashActual - $expected,
        ]);

        return $shift->fresh();
    }
}
