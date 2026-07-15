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

    /**
     * Export report as CSV (opens in Excel).
     */
    public function exportExcel(Request $request)
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

        $daily = $this->reportService->dailySales(
            $validated['outlet_id'],
            $validated['start_date'],
            $validated['end_date'],
        );

        $top = $this->reportService->topProducts(
            $validated['outlet_id'],
            $validated['start_date'],
            $validated['end_date'],
            50,
        );

        $summary = $data['summary'];
        $rows = [];

        // Header
        $rows[] = ['LAPORAN KEUANGAN'];
        $rows[] = ['Periode: ' . $validated['start_date'] . ' s/d ' . $validated['end_date']];
        $rows[] = [];

        // Summary
        $rows[] = ['RINGKASAN'];
        $rows[] = ['Total Transaksi', $summary['total_transactions']];
        $rows[] = ['Penjualan Kotor', $this->formatRupiah($summary['gross_sales'])];
        $rows[] = ['Diskon', $this->formatRupiah($summary['total_discount'])];
        $rows[] = ['Pajak', $this->formatRupiah($summary['total_tax'])];
        $rows[] = ['Penjualan Bersih', $this->formatRupiah($summary['net_sales'])];
        $rows[] = ['HPP (Modal)', $this->formatRupiah($summary['hpp'])];
        $rows[] = ['Laba Kotor', $this->formatRupiah($summary['gross_profit'])];
        $rows[] = ['Margin', $summary['gross_margin'] . '%'];
        $rows[] = ['Rata-rata Pesanan', $this->formatRupiah($summary['avg_order_value'])];
        $rows[] = [];

        // Daily Sales
        $rows[] = ['PENJUALAN HARIAN'];
        $rows[] = ['Tanggal', 'Transaksi', 'Penjualan Kotor', 'Diskon', 'Pajak', 'Penjualan Bersih', 'HPP', 'Laba Kotor', 'Margin'];
        foreach ($daily as $d) {
            $rows[] = [
                $d['date'],
                $d['total_orders'],
                $this->formatRupiah($d['gross_sales']),
                $this->formatRupiah($d['total_discount']),
                $this->formatRupiah($d['total_tax']),
                $this->formatRupiah($d['net_sales']),
                $this->formatRupiah($d['hpp']),
                $this->formatRupiah($d['gross_profit']),
                $d['gross_margin'] . '%',
            ];
        }
        $rows[] = [];

        // Top Products
        $rows[] = ['PRODUK TERLARIS'];
        $rows[] = ['Produk', 'Terjual', 'Pendapatan', 'Modal', 'Laba Kotor', 'Margin'];
        foreach ($top as $p) {
            $rows[] = [
                $p['product_name'],
                $p['total_qty'],
                $this->formatRupiah($p['total_revenue']),
                $this->formatRupiah($p['total_hpp']),
                $this->formatRupiah($p['gross_profit']),
                $p['gross_margin'] . '%',
            ];
        }

        // Build CSV
        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($cell) => '"' . str_replace('"', '""', $cell) . '"', $row)) . "\n";
        }

        $filename = 'laporan-keuangan-' . $validated['start_date'] . '_' . $validated['end_date'] . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export report as HTML page (bisa di-print / save as PDF).
     */
    public function exportPdf(Request $request)
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

        $daily = $this->reportService->dailySales(
            $validated['outlet_id'],
            $validated['start_date'],
            $validated['end_date'],
        );

        $top = $this->reportService->topProducts(
            $validated['outlet_id'],
            $validated['start_date'],
            $validated['end_date'],
            50,
        );

        $summary = $data['summary'];
        $outletName = \App\Models\Outlet::find($validated['outlet_id'])?->name ?? 'Outlet';

        $html = '<!DOCTYPE html>';
        $html .= '<html><head><meta charset="utf-8"><title>Laporan Keuangan</title>';
        $html .= '<style>';
        $html .= 'body{font-family:Arial,sans-serif;font-size:12px;color:#333;padding:30px;}';
        $html .= 'h1{font-size:20px;margin-bottom:2px;}';
        $html .= 'h2{font-size:14px;margin:20px 0 8px;border-bottom:2px solid #333;padding-bottom:4px;}';
        $html .= '.sub{color:#666;font-size:11px;margin-bottom:15px;}';
        $html .= 'table{width:100%;border-collapse:collapse;margin-bottom:15px;}';
        $html .= 'th{background:#f5f5f5;text-align:left;padding:6px 8px;font-size:11px;border:1px solid #ddd;}';
        $html .= 'td{padding:5px 8px;border:1px solid #eee;}';
        $html .= 'tr:nth-child(even){background:#fafafa;}';
        $html .= '.text-right{text-align:right;}';
        $html .= '.summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:15px;}';
        $html .= '.summary-item{border:1px solid #ddd;border-radius:6px;padding:8px 10px;}';
        $html .= '.summary-item .label{font-size:10px;color:#888;text-transform:uppercase;}';
        $html .= '.summary-item .value{font-size:16px;font-weight:bold;margin-top:2px;}';
        $html .= '@media print{body{padding:15px;}}';
        $html .= '</style></head><body>';

        $html .= '<h1>Laporan Keuangan</h1>';
        $html .= '<div class="sub">' . $outletName . ' — Periode: ' . $validated['start_date'] . ' s/d ' . $validated['end_date'] . '</div>';

        // Summary grid
        $html .= '<div class="summary-grid">';
        $cards = [
            ['Transaksi', $summary['total_transactions']],
            ['Penjualan Kotor', $this->formatRupiah($summary['gross_sales'])],
            ['Diskon', $this->formatRupiah($summary['total_discount'])],
            ['Pajak', $this->formatRupiah($summary['total_tax'])],
            ['Penjualan Bersih', $this->formatRupiah($summary['net_sales'])],
            ['HPP (Modal)', $this->formatRupiah($summary['hpp'])],
            ['Laba Kotor', $this->formatRupiah($summary['gross_profit'])],
            ['Margin', $summary['gross_margin'] . '%'],
            ['Rata-rata Pesanan', $this->formatRupiah($summary['avg_order_value'])],
        ];
        foreach ($cards as $c) {
            $html .= '<div class="summary-item"><div class="label">' . $c[0] . '</div><div class="value">' . $c[1] . '</div></div>';
        }
        $html .= '</div>';

        // Daily Sales
        $html .= '<h2>Penjualan Harian</h2>';
        $html .= '<table><thead><tr>';
        $headers = ['Tanggal', 'Transaksi', 'Penjualan Kotor', 'Diskon', 'Pajak', 'Penjualan Bersih', 'HPP', 'Laba Kotor', 'Margin'];
        foreach ($headers as $h) {
            $html .= '<th' . (in_array($h, ['Transaksi', 'Penjualan Kotor', 'Diskon', 'Pajak', 'Penjualan Bersih', 'HPP', 'Laba Kotor', 'Margin']) ? ' class="text-right"' : '') . '>' . $h . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($daily as $d) {
            $html .= '<tr>';
            $html .= '<td>' . $d['date'] . '</td>';
            $html .= '<td class="text-right">' . $d['total_orders'] . '</td>';
            $html .= '<td class="text-right">' . $this->formatRupiah($d['gross_sales']) . '</td>';
            $html .= '<td class="text-right">' . $this->formatRupiah($d['total_discount']) . '</td>';
            $html .= '<td class="text-right">' . $this->formatRupiah($d['total_tax']) . '</td>';
            $html .= '<td class="text-right">' . $this->formatRupiah($d['net_sales']) . '</td>';
            $html .= '<td class="text-right">' . $this->formatRupiah($d['hpp']) . '</td>';
            $html .= '<td class="text-right">' . $this->formatRupiah($d['gross_profit']) . '</td>';
            $html .= '<td class="text-right">' . $d['gross_margin'] . '%</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        // Top Products
        $html .= '<h2>Produk Terlaris</h2>';
        $html .= '<table><thead><tr>';
        $headers = ['Produk', 'Terjual', 'Pendapatan', 'Modal', 'Laba Kotor', 'Margin'];
        foreach ($headers as $h) {
            $html .= '<th' . (in_array($h, ['Terjual', 'Pendapatan', 'Modal', 'Laba Kotor', 'Margin']) ? ' class="text-right"' : '') . '>' . $h . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($top as $p) {
            $html .= '<tr>';
            $html .= '<td>' . $p['product_name'] . '</td>';
            $html .= '<td class="text-right">' . $p['total_qty'] . '</td>';
            $html .= '<td class="text-right">' . $this->formatRupiah($p['total_revenue']) . '</td>';
            $html .= '<td class="text-right">' . $this->formatRupiah($p['total_hpp']) . '</td>';
            $html .= '<td class="text-right">' . $this->formatRupiah($p['gross_profit']) . '</td>';
            $html .= '<td class="text-right">' . $p['gross_margin'] . '%</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<div style="text-align:center;color:#999;font-size:10px;margin-top:30px;">';
        $html .= 'Dicetak: ' . now()->format('d/m/Y H:i') . ' — POS Admin';
        $html .= '</div>';

        $html .= '</body></html>';

        return response($html, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    private function formatRupiah(int $val): string
    {
        return 'Rp ' . number_format($val / 100, 0, ',', '.');
    }
}
