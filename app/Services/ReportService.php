<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get financial summary for a date range.
     */
    public function summary(int $outletId, string $startDate, string $endDate): array
    {
        $orders = Order::where('outlet_id', $outletId)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

        $orderIds = (clone $orders)->pluck('id');

        // Gross sales (total penjualan kotor)
        $grossSales = (clone $orders)->sum('grand_total');

        // Total discounts applied
        $totalDiscount = (clone $orders)->sum('discount_total');

        // Total taxes
        $totalTax = (clone $orders)->sum('tax_total');

        // Subtotal (before tax & discount)
        $subtotal = (clone $orders)->sum('subtotal');

        // Net sales = subtotal - discount (before tax)
        $netSales = $subtotal - $totalDiscount;

        // HPP (Cost of Goods Sold) — sum of all unit_cost × qty from paid orders
        $hpp = OrderItem::whereIn('order_id', $orderIds)
            ->sum(DB::raw('unit_cost * qty'));

        // Gross profit
        $grossProfit = $netSales - $hpp;

        // Gross profit margin (%)
        $grossMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0;

        // Total transactions
        $totalTransactions = (clone $orders)->count();

        // Average order value
        $avgOrderValue = $totalTransactions > 0 ? (int) round($grossSales / $totalTransactions) : 0;

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => [
                'total_transactions' => $totalTransactions,
                'subtotal' => (int) $subtotal,
                'total_discount' => (int) $totalDiscount,
                'total_tax' => (int) $totalTax,
                'gross_sales' => (int) $grossSales,
                'net_sales' => (int) $netSales,
                'hpp' => (int) $hpp,
                'gross_profit' => (int) $grossProfit,
                'gross_margin' => $grossMargin,
                'avg_order_value' => $avgOrderValue,
            ],
        ];
    }

    /**
     * Get daily sales breakdown for chart.
     */
    public function dailySales(int $outletId, string $startDate, string $endDate): array
    {
        $days = DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(grand_total) as gross_sales'),
                DB::raw('SUM(subtotal) as subtotal'),
                DB::raw('SUM(discount_total) as total_discount'),
                DB::raw('SUM(tax_total) as total_tax'),
            )
            ->where('outlet_id', $outletId)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Get daily HPP
        $dailyHpp = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('SUM(order_items.unit_cost * order_items.qty) as hpp'),
            )
            ->where('orders.outlet_id', $outletId)
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate . ' 23:59:59'])
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        foreach ($days as $day) {
            $hpp = (int) ($dailyHpp[$day->date]->hpp ?? 0);
            $netSales = (int) $day->subtotal - (int) $day->total_discount;
            $grossProfit = $netSales - $hpp;

            $result[] = [
                'date' => $day->date,
                'total_orders' => (int) $day->total_orders,
                'subtotal' => (int) $day->subtotal,
                'gross_sales' => (int) $day->gross_sales,
                'total_discount' => (int) $day->total_discount,
                'total_tax' => (int) $day->total_tax,
                'net_sales' => $netSales,
                'hpp' => $hpp,
                'gross_profit' => $grossProfit,
                'gross_margin' => $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0,
            ];
        }

        return $result;
    }

    /**
     * Get top products by sales volume/profit.
     */
    public function topProducts(int $outletId, string $startDate, string $endDate, int $limit = 10): array
    {
        $products = OrderItem::select(
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.qty) as total_qty'),
                DB::raw('SUM(order_items.total_price) as total_revenue'),
                DB::raw('SUM(order_items.unit_cost * order_items.qty) as total_hpp'),
            )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.outlet_id', $outletId)
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate . ' 23:59:59'])
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return $products->map(function ($p) {
            $profit = (int) $p->total_revenue - (int) $p->total_hpp;
            $margin = (int) $p->total_revenue > 0 ? round(($profit / (int) $p->total_revenue) * 100, 2) : 0;

            return [
                'product_id' => $p->product_id,
                'product_name' => $p->product_name,
                'total_qty' => (int) $p->total_qty,
                'total_revenue' => (int) $p->total_revenue,
                'total_hpp' => (int) $p->total_hpp,
                'gross_profit' => $profit,
                'gross_margin' => $margin,
            ];
        })->toArray();
    }
}
