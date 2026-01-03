<?php

namespace App\Http\Controllers;

use App\Models\BahanMakanan;
use Illuminate\Http\Request;

class BahanMakananController extends Controller
{
    /**
     * Display a listing of bahan makanan
     */
    public function index(Request $request)
    {
        $query = BahanMakanan::with('satuan');

        // Filter by active status if requested
        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        $bahanMakanan = $query->orderBy('nama')->get();

        return response()->json($bahanMakanan);
    }

    /**
     * Store a newly created bahan makanan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'satuan_id' => 'required|exists:satuan,id',
            'is_active' => 'boolean',
        ]);

        $bahanMakanan = BahanMakanan::create($validated);
        $bahanMakanan->load('satuan');

        return response()->json([
            'message' => 'Bahan makanan berhasil ditambahkan',
            'bahan_makanan' => $bahanMakanan,
        ], 201);
    }

    /**
     * Update the specified bahan makanan
     */
    public function update(Request $request, BahanMakanan $bahan)
    {
        $validated = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'satuan_id' => 'sometimes|required|exists:satuan,id',
            'is_active' => 'boolean',
        ]);

        $bahan->update($validated);
        $bahan->load('satuan');

        return response()->json([
            'message' => 'Bahan makanan berhasil diperbarui',
            'bahan_makanan' => $bahan,
        ]);
    }

    /**
     * Remove the specified bahan makanan
     */
    public function destroy(BahanMakanan $bahan)
    {
        // Check if bahan makanan is used by pembelian
        if ($bahan->pembelianBahan()->exists()) {
            return response()->json([
                'message' => 'Bahan makanan tidak dapat dihapus karena memiliki data pembelian. Ubah status menjadi nonaktif.',
            ], 422);
        }

        $bahan->delete();

        return response()->json([
            'message' => 'Bahan makanan berhasil dihapus',
        ]);
    }
}
