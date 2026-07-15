<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShiftSchedule;
use Illuminate\Http\Request;

class ShiftScheduleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $query = ShiftSchedule::with(['user', 'shiftType'])
            ->where('outlet_id', $request->outlet_id);

        // Filter by date range
        if ($request->start_date) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('date', '<=', $request->end_date);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('date')->orderBy('shift_type_id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'user_id' => 'required|exists:users,id',
            'shift_type_id' => 'required|exists:shift_types,id',
            'date' => 'required|date_format:Y-m-d',
            'status' => 'nullable|string|in:scheduled,confirmed,absent',
        ]);

        // Check duplicate — unique constraint handles this but let's give a good message
        $exists = ShiftSchedule::where([
            'user_id' => $validated['user_id'],
            'shift_type_id' => $validated['shift_type_id'],
            'date' => $validated['date'],
        ])->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal shift sudah ada untuk user ini pada tanggal & shift yang sama.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'data' => ShiftSchedule::create($validated),
        ], 201);
    }

    public function update(Request $request, ShiftSchedule $shiftSchedule)
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'shift_type_id' => 'sometimes|exists:shift_types,id',
            'date' => 'sometimes|date_format:Y-m-d',
            'status' => 'nullable|string|in:scheduled,confirmed,absent',
        ]);

        $shiftSchedule->update($validated);

        return response()->json([
            'success' => true,
            'data' => $shiftSchedule->load(['user', 'shiftType']),
        ]);
    }

    public function destroy(ShiftSchedule $shiftSchedule)
    {
        $shiftSchedule->delete();
        return response()->json(['success' => true, 'message' => 'Jadwal shift dihapus.']);
    }

    /**
     * Auto-generate schedules for a given month.
     * Distributes users evenly across shift types for each day.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'year' => 'required|integer|min:2024|max:2030',
            'month' => 'required|integer|min:1|max:12',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
            'shift_type_ids' => 'required|array|min:1',
            'shift_type_ids.*' => 'required|exists:shift_types,id',
        ]);

        $outletId = $validated['outlet_id'];
        $year = $validated['year'];
        $month = $validated['month'];
        $userIds = $validated['user_ids'];
        $shiftTypeIds = $validated['shift_type_ids'];

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $created = 0;
        $skipped = 0;

        // Rotate users per shift type for even distribution
        $userCount = count($userIds);
        $userRotations = [];
        foreach ($shiftTypeIds as $stId) {
            $userRotations[$stId] = 0; // start index per shift type
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

            foreach ($shiftTypeIds as $shiftTypeId) {
                // Rotate users evenly: each shift type gets a rotating user
                $userIndex = $userRotations[$shiftTypeId] % $userCount;
                $userId = $userIds[$userIndex];
                $userRotations[$shiftTypeId]++;

                // Check if already exists
                $exists = ShiftSchedule::where([
                    'user_id' => $userId,
                    'shift_type_id' => $shiftTypeId,
                    'date' => $date,
                ])->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                ShiftSchedule::create([
                    'outlet_id' => $outletId,
                    'user_id' => $userId,
                    'shift_type_id' => $shiftTypeId,
                    'date' => $date,
                    'status' => 'scheduled',
                ]);
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Generate selesai: {$created} jadwal baru, {$skipped} dilewati (sudah ada).",
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
            ],
        ]);
    }
}
