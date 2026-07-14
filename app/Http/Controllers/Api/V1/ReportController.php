<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Get financial summary for a date range.
     */
    public function summary(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $data = $this->reportService->summary(
            $validated['outlet_id'],
            $validated['start_date'],
            $validated['end_date'],
        );

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Get daily sales breakdown for chart.
     */
    public function dailySales(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $data = $this->reportService->dailySales(
            $validated['outlet_id'],
            $validated['start_date'],
            $validated['end_date'],
        );

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Get top products by sales/profit.
     */
    public function topProducts(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $data = $this->reportService->topProducts(
            $validated['outlet_id'],
            $validated['start_date'],
            $validated['end_date'],
            $validated['limit'] ?? 10,
        );

        return response()->json(['success' => true, 'data' => $data]);
    }
}
