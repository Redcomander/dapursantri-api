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
     * Get sessions by date (default today Jakarta timezone)
     */
    public function today(Request $request)
    {
        // Use provided date or default to today in Jakarta timezone
        $date = $request->get('date', now('Asia/Jakarta')->toDateString());

        $sessions = SesiPembelian::with(['user:id,name', 'items.bahanMakanan.satuan', 'buktiPembelian'])
            ->withCount('items')
            ->whereDate('tanggal', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $sessions->sum('total');

        return response()->json([
            'sessions' => $sessions,
            'total' => $total,
            'date' => $date,
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
     * Upload proof document with auto-compression for images
     */
    public function uploadBukti(Request $request, SesiPembelian $sesiPembelian)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,pdf,webp',
        ]);

        $file = $request->file('file');
        $mimeType = $file->getMimeType();
        $originalName = $file->getClientOriginalName();
        
        // Check if file is an image (not PDF)
        if (str_starts_with($mimeType, 'image/')) {
            try {
                // Compress image using Intervention Image
                $image = \Intervention\Image\Laravel\Facades\Image::read($file);
                
                // Resize if larger than 1920px
                $image->scaleDown(1920, 1920);
                
                // Generate unique filename
                $filename = 'bukti-pembelian/' . uniqid() . '_' . time() . '.jpg';
                
                // Encode and save (quality 75%)
                $encoded = $image->toJpeg(75);
                Storage::disk('public')->put($filename, $encoded);
                
                $path = $filename;
                $fileSize = Storage::disk('public')->size($path);
                $mimeType = 'image/jpeg';
            } catch (\Exception $e) {
                // Fallback: store original file if compression fails (e.g., no GD/Imagick)
                $path = $file->store('bukti-pembelian', 'public');
                $fileSize = $file->getSize();
            }
        } else {
            // Store PDF as-is
            $path = $file->store('bukti-pembelian', 'public');
            $fileSize = $file->getSize();
        }

        $bukti = BuktiPembelian::create([
            'sesi_pembelian_id' => $sesiPembelian->id,
            'file_path' => $path,
            'file_name' => $originalName,
            'file_type' => $mimeType,
            'file_size' => $fileSize,
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
