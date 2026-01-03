<?php

namespace App\Http\Controllers;

use App\Models\SesiPembelian;
use App\Models\PembelianBahan;
use App\Models\BuktiPembelian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SesiPembelianController extends Controller
{
    /**
     * Display a listing of sessions
     */
    public function index(Request $request)
    {
        $query = SesiPembelian::with(['user:id,name', 'items.bahanMakanan.satuan', 'buktiPembelian'])
            ->withCount('items');

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('tanggal', $request->date);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($sessions);
    }

    /**
     * Get today's sessions
     */
    public function today()
    {
        $sessions = SesiPembelian::with(['user:id,name', 'items.bahanMakanan.satuan', 'buktiPembelian'])
            ->withCount('items')
            ->whereDate('tanggal', now()->toDateString())
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $sessions->sum('total');

        return response()->json([
            'sessions' => $sessions,
            'total' => $total,
        ]);
    }

    /**
     * Store a newly created session
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'catatan' => 'nullable|string|max:1000',
            'vendor' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'draft';
        $validated['total'] = 0;

        $session = SesiPembelian::create($validated);
        $session->load(['user:id,name']);

        return response()->json([
            'message' => 'Sesi pembelian berhasil dibuat',
            'session' => $session,
        ], 201);
    }

    /**
     * Display the specified session
     */
    public function show(SesiPembelian $sesiPembelian)
    {
        $sesiPembelian->load(['user:id,name', 'items.bahanMakanan.satuan', 'buktiPembelian']);

        return response()->json($sesiPembelian);
    }

    /**
     * Update the specified session
     */
    public function update(Request $request, SesiPembelian $sesiPembelian)
    {
        $validated = $request->validate([
            'judul' => 'sometimes|required|string|max:255',
            'tanggal' => 'sometimes|required|date',
            'catatan' => 'nullable|string|max:1000',
            'vendor' => 'nullable|string|max:255',
        ]);

        $sesiPembelian->update($validated);
        $sesiPembelian->load(['user:id,name', 'items.bahanMakanan.satuan', 'buktiPembelian']);

        return response()->json([
            'message' => 'Sesi pembelian berhasil diperbarui',
            'session' => $sesiPembelian,
        ]);
    }

    /**
     * Remove the specified session
     */
    public function destroy(SesiPembelian $sesiPembelian)
    {
        // Delete all proof files
        foreach ($sesiPembelian->buktiPembelian as $bukti) {
            Storage::delete($bukti->file_path);
        }

        $sesiPembelian->delete();

        return response()->json([
            'message' => 'Sesi pembelian berhasil dihapus',
        ]);
    }

    /**
     * Add item to session
     */
    public function addItem(Request $request, SesiPembelian $sesiPembelian)
    {
        $validated = $request->validate([
            'bahan_makanan_id' => 'required|exists:bahan_makanan,id',
            'jumlah' => 'required|numeric|min:0.01',
            'harga_satuan' => 'required|numeric|min:0',
            'catatan' => 'nullable|string|max:500',
        ]);

        $validated['sesi_pembelian_id'] = $sesiPembelian->id;
        $validated['tanggal'] = $sesiPembelian->tanggal;
        $validated['vendor'] = $sesiPembelian->vendor;
        $validated['user_id'] = auth()->id();

        $item = PembelianBahan::create($validated);
        $item->load(['bahanMakanan.satuan']);

        // Update session total
        $sesiPembelian->updateTotal();

        return response()->json([
            'message' => 'Item berhasil ditambahkan',
            'item' => $item,
            'session_total' => $sesiPembelian->fresh()->total,
        ], 201);
    }

    /**
     * Remove item from session
     */
    public function removeItem(SesiPembelian $sesiPembelian, PembelianBahan $item)
    {
        // Verify item belongs to session
        if ($item->sesi_pembelian_id !== $sesiPembelian->id) {
            return response()->json([
                'message' => 'Item tidak ditemukan dalam sesi ini',
            ], 404);
        }

        $item->delete();

        // Update session total
        $sesiPembelian->updateTotal();

        return response()->json([
            'message' => 'Item berhasil dihapus',
            'session_total' => $sesiPembelian->fresh()->total,
        ]);
    }

    /**
     * Upload proof document
     */
    public function uploadBukti(Request $request, SesiPembelian $sesiPembelian)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,pdf|max:5120', // 5MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('bukti-pembelian', 'public');

        $bukti = BuktiPembelian::create([
            'sesi_pembelian_id' => $sesiPembelian->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Bukti pembelian berhasil diunggah',
            'bukti' => $bukti,
        ], 201);
    }

    /**
     * Delete proof document
     */
    public function deleteBukti(SesiPembelian $sesiPembelian, BuktiPembelian $bukti)
    {
        // Verify bukti belongs to session
        if ($bukti->sesi_pembelian_id !== $sesiPembelian->id) {
            return response()->json([
                'message' => 'Bukti tidak ditemukan dalam sesi ini',
            ], 404);
        }

        // Delete file from storage
        Storage::delete($bukti->file_path);

        $bukti->delete();

        return response()->json([
            'message' => 'Bukti pembelian berhasil dihapus',
        ]);
    }

    /**
     * Mark session as completed
     */
    public function complete(SesiPembelian $sesiPembelian)
    {
        if ($sesiPembelian->items()->count() === 0) {
            return response()->json([
                'message' => 'Tidak dapat menyelesaikan sesi tanpa item',
            ], 422);
        }

        $sesiPembelian->complete();

        return response()->json([
            'message' => 'Sesi pembelian berhasil diselesaikan',
            'session' => $sesiPembelian->fresh(['user:id,name', 'items.bahanMakanan.satuan', 'buktiPembelian']),
        ]);
    }
}
