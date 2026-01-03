<?php

namespace App\Http\Controllers;

use App\Models\PembelianBahan;
use Illuminate\Http\Request;

class PembelianBahanController extends Controller
{
    /**
     * Display a listing of pembelian bahan
     */
    public function index(Request $request)
    {
        $query = PembelianBahan::with(['bahanMakanan.satuan', 'user:id,name']);

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        }

        // Filter by specific date
        if ($request->has('date')) {
            $query->whereDate('tanggal', $request->date);
        }

        $pembelian = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($pembelian);
    }

    /**
     * Store a newly created pembelian bahan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'bahan_makanan_id' => 'required|exists:bahan_makanan,id',
            'jumlah' => 'required|numeric|min:0.01',
            'harga_satuan' => 'required|numeric|min:0',
            'catatan' => 'nullable|string|max:500',
            'vendor' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = auth()->id();

        $pembelian = PembelianBahan::create($validated);
        $pembelian->load(['bahanMakanan.satuan', 'user:id,name']);

        return response()->json([
            'message' => 'Pembelian berhasil dicatat',
            'pembelian' => $pembelian,
        ], 201);
    }

    /**
     * Get today's pembelian
     */
    public function today()
    {
        $pembelian = PembelianBahan::with(['bahanMakanan.satuan', 'user:id,name'])
            ->whereDate('tanggal', now()->toDateString())
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $pembelian->sum('total_harga');

        return response()->json([
            'pembelian' => $pembelian,
            'total' => $total,
        ]);
    }

    /**
     * Update the specified pembelian bahan
     */
    public function update(Request $request, PembelianBahan $pembelian)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'bahan_makanan_id' => 'required|exists:bahan_makanan,id',
            'jumlah' => 'required|numeric|min:0.01',
            'harga_satuan' => 'required|numeric|min:0',
            'catatan' => 'nullable|string|max:500',
            'vendor' => 'nullable|string|max:255',
        ]);

        $pembelian->update($validated);
        $pembelian->load(['bahanMakanan.satuan', 'user:id,name']);

        return response()->json([
            'message' => 'Pembelian berhasil diperbarui',
            'pembelian' => $pembelian,
        ]);
    }

    /**
     * Remove the specified pembelian bahan
     */
    public function destroy(PembelianBahan $pembelian)
    {
        $pembelian->delete();

        return response()->json([
            'message' => 'Pembelian berhasil dihapus',
        ]);
    }
}
