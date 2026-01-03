<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    /**
     * Display a listing of satuan
     */
    public function index()
    {
        $satuan = Satuan::orderBy('nama')->get();

        return response()->json($satuan);
    }

    /**
     * Store a newly created satuan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:satuan,nama',
        ]);

        $satuan = Satuan::create($validated);

        return response()->json([
            'message' => 'Satuan berhasil ditambahkan',
            'satuan' => $satuan,
        ], 201);
    }

    /**
     * Update the specified satuan
     */
    public function update(Request $request, Satuan $satuan)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:satuan,nama,' . $satuan->id,
        ]);

        $satuan->update($validated);

        return response()->json([
            'message' => 'Satuan berhasil diperbarui',
            'satuan' => $satuan,
        ]);
    }

    /**
     * Remove the specified satuan
     */
    public function destroy(Satuan $satuan)
    {
        // Check if satuan is used by bahan makanan
        if ($satuan->bahanMakanan()->exists()) {
            return response()->json([
                'message' => 'Satuan tidak dapat dihapus karena digunakan oleh bahan makanan',
            ], 422);
        }

        $satuan->delete();

        return response()->json([
            'message' => 'Satuan berhasil dihapus',
        ]);
    }
}
