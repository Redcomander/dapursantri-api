<?php

namespace App\Http\Controllers;

use App\Models\PembelianBahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanController extends Controller
{
    /**
     * Get dashboard summary
     */
    public function dashboard(Request $request)
    {
        // Support custom date filter
        $targetDate = $request->has('date') ? Carbon::parse($request->date) : Carbon::today();
        
        $today = $targetDate;
        $startOfWeek = $targetDate->copy()->startOfWeek();
        $endOfWeek = $targetDate->copy()->endOfWeek();
        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();

        // Daily total
        $totalHarian = PembelianBahan::whereDate('tanggal', $today)->sum('total_harga');

        // Weekly total
        $totalMingguan = PembelianBahan::whereBetween('tanggal', [$startOfWeek, $endOfWeek])->sum('total_harga');

        // Monthly total
        $totalBulanan = PembelianBahan::whereBetween('tanggal', [$startOfMonth, $endOfMonth])->sum('total_harga');

        // Recent purchases (for the selected date if filtered, or all recent)
        $recentQuery = PembelianBahan::with(['bahanMakanan.satuan', 'user:id,name']);
        
        if ($request->has('date')) {
            $recentQuery->whereDate('tanggal', $today);
        }
        
        $recentPurchases = $recentQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Weekly trend (7 days around selected date)
        $weeklyTrend = PembelianBahan::select(
                DB::raw('DATE(tanggal) as date'),
                DB::raw('SUM(total_harga) as total')
            )
            ->whereBetween('tanggal', [$targetDate->copy()->subDays(6), $targetDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top items with average price (this month)
        $thisMonthPurchases = PembelianBahan::with(['bahanMakanan.satuan'])
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();

        $topItemsWithAverage = $thisMonthPurchases->groupBy('bahan_makanan_id')->map(function ($items) {
            $first = $items->first();
            $totalJumlah = $items->sum('jumlah');
            $totalHarga = $items->sum('total_harga');
            return [
                'bahan_id' => $first->bahan_makanan_id,
                'bahan' => $first->bahanMakanan->nama,
                'satuan' => $first->bahanMakanan->satuan->nama,
                'total_jumlah' => $totalJumlah,
                'total_harga' => $totalHarga,
                'rata_rata_harga' => $totalJumlah > 0 ? round($totalHarga / $totalJumlah, 0) : 0,
            ];
        })->sortByDesc('total_harga')->take(10)->values();

        return response()->json([
            'selected_date' => $targetDate->toDateString(),
            'summary' => [
                'harian' => $totalHarian,
                'mingguan' => $totalMingguan,
                'bulanan' => $totalBulanan,
            ],
            'recent_purchases' => $recentPurchases,
            'weekly_trend' => $weeklyTrend,
            'top_items_bulan_ini' => $topItemsWithAverage,
        ]);
    }

    /**
     * Get daily report
     */
    public function harian(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date);

        $pembelian = PembelianBahan::with(['bahanMakanan.satuan', 'user:id,name'])
            ->whereDate('tanggal', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $pembelian->sum('total_harga');

        // Group by bahan with average price
        $perBahan = $pembelian->groupBy('bahan_makanan_id')->map(function ($items) {
            $first = $items->first();
            $totalJumlah = $items->sum('jumlah');
            $totalHarga = $items->sum('total_harga');
            return [
                'bahan' => $first->bahanMakanan->nama,
                'satuan' => $first->bahanMakanan->satuan->nama,
                'total_jumlah' => $totalJumlah,
                'total_harga' => $totalHarga,
                'rata_rata_harga' => $totalJumlah > 0 ? round($totalHarga / $totalJumlah, 0) : 0,
            ];
        })->values();

        return response()->json([
            'date' => $date->toDateString(),
            'pembelian' => $pembelian,
            'rekap_per_bahan' => $perBahan,
            'total' => $total,
        ]);
    }

    /**
     * Get weekly report
     */
    public function mingguan(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);

        $pembelian = PembelianBahan::with(['bahanMakanan.satuan', 'user:id,name'])
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $pembelian->sum('total_harga');

        // Daily breakdown
        $perHari = PembelianBahan::select(
                DB::raw('DATE(tanggal) as date'),
                DB::raw('SUM(total_harga) as total')
            )
            ->whereBetween('tanggal', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Group by bahan with average price
        $perBahan = $pembelian->groupBy('bahan_makanan_id')->map(function ($items) {
            $first = $items->first();
            $totalJumlah = $items->sum('jumlah');
            $totalHarga = $items->sum('total_harga');
            return [
                'bahan' => $first->bahanMakanan->nama,
                'satuan' => $first->bahanMakanan->satuan->nama,
                'total_jumlah' => $totalJumlah,
                'total_harga' => $totalHarga,
                'rata_rata_harga' => $totalJumlah > 0 ? round($totalHarga / $totalJumlah, 0) : 0,
            ];
        })->values();

        return response()->json([
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'pembelian' => $pembelian,
            'rekap_per_hari' => $perHari,
            'rekap_per_bahan' => $perBahan,
            'total' => $total,
        ]);
    }

    /**
     * Get monthly report
     */
    public function bulanan(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $month = Carbon::createFromFormat('Y-m', $request->month);
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $pembelian = PembelianBahan::with(['bahanMakanan.satuan', 'user:id,name'])
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $pembelian->sum('total_harga');

        // Weekly breakdown
        $perMinggu = [];
        $weekStart = $start->copy();
        $weekNum = 1;
        while ($weekStart->lte($end)) {
            $weekEnd = $weekStart->copy()->endOfWeek();
            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }
            
            $weekTotal = PembelianBahan::whereBetween('tanggal', [$weekStart, $weekEnd])->sum('total_harga');
            
            $perMinggu[] = [
                'minggu' => $weekNum,
                'start' => $weekStart->toDateString(),
                'end' => $weekEnd->toDateString(),
                'total' => $weekTotal,
            ];
            
            $weekStart = $weekEnd->copy()->addDay();
            $weekNum++;
        }

        // Group by bahan with average price
        $perBahan = $pembelian->groupBy('bahan_makanan_id')->map(function ($items) {
            $first = $items->first();
            $totalJumlah = $items->sum('jumlah');
            $totalHarga = $items->sum('total_harga');
            return [
                'bahan' => $first->bahanMakanan->nama,
                'satuan' => $first->bahanMakanan->satuan->nama,
                'total_jumlah' => $totalJumlah,
                'total_harga' => $totalHarga,
                'rata_rata_harga' => $totalJumlah > 0 ? round($totalHarga / $totalJumlah, 0) : 0,
            ];
        })->values();

        return response()->json([
            'month' => $request->month,
            'pembelian' => $pembelian,
            'rekap_per_minggu' => $perMinggu,
            'rekap_per_bahan' => $perBahan,
            'total' => $total,
        ]);
    }
}
