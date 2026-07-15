<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Outlet;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get all dashboard data in a single optimized endpoint.
     * Scoped to user's outlets.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->format('Y-m-d');

        // Dapetin semua outlet ID milik user
        $outletIds = $user->outlets()->pluck('id');

        if ($outletIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'outlets' => 0,
                        'products' => 0,
                        'total_transactions' => 0,
                        'gross_sales' => 0,
                        'net_sales' => 0,
                        'hpp' => 0,
                        'gross_profit' => 0,
                        'gross_margin' => 0,
                    ],
                    'recent_orders' => [],
                ],
            ]);
        }

        // ── Outlets & Products count ──
        $outletsCount = Outlet::whereIn('id', $outletIds)->count();
        $productsCount = Product::whereIn('outlet_id', $outletIds)->where('is_active', true)->count();

        // ── Optimized: 1 query buat semua summary ──
        $summaryQuery = Order::whereIn('outlet_id', $outletIds)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$today . ' 00:00:00', $today . ' 23:59:59']);

        $summary = (clone $summaryQuery)
            ->selectRaw('
                COUNT(*) as total_transactions,
                COALESCE(SUM(grand_total), 0) as gross_sales,
                COALESCE(SUM(subtotal), 0) as subtotal,
                COALESCE(SUM(discount_total), 0) as total_discount,
                COALESCE(SUM(tax_total), 0) as total_tax
            ')
            ->first();

        // HPP hari ini
        $todayOrderIds = (clone $summaryQuery)->pluck('id');
        $hpp = 0;
        if ($todayOrderIds->isNotEmpty()) {
            $hpp = (int) OrderItem::whereIn('order_id', $todayOrderIds)
                ->sum(DB::raw('unit_cost * qty'));
        }

        $totalTransactions = (int) $summary->total_transactions;
        $grossSales = (int) $summary->gross_sales;
        $netSales = (int) $summary->subtotal - (int) $summary->total_discount;
        $grossProfit = $netSales - $hpp;
        $grossMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0;

        // ── Recent orders ──
        $recentOrders = Order::whereIn('outlet_id', $outletIds)
            ->where('payment_status', 'paid')
            ->whereDate('created_at', $today)
            ->latest()
            ->limit(5)
            ->get(['id', 'customer_name', 'status', 'grand_total', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'outlets' => $outletsCount,
                    'products' => $productsCount,
                    'total_transactions' => $totalTransactions,
                    'gross_sales' => $grossSales,
                    'net_sales' => $netSales,
                    'hpp' => $hpp,
                    'gross_profit' => $grossProfit,
                    'gross_margin' => $grossMargin,
                ],
                'recent_orders' => $recentOrders,
            ],
        ]);
    }
}
