<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembelianBahan extends Model
{
    use HasFactory;

    protected $table = 'pembelian_bahan';
    
    protected $fillable = [
        'sesi_pembelian_id',
        'tanggal',
        'bahan_makanan_id',
        'jumlah',
        'harga_satuan',
        'total_harga',
        'catatan',
        'vendor',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    /**
     * Get the session this item belongs to
     */
    public function sesiPembelian(): BelongsTo
    {
        return $this->belongsTo(SesiPembelian::class);
    }

    /**
     * Get the bahan makanan for this pembelian
     */
    public function bahanMakanan(): BelongsTo
    {
        return $this->belongsTo(BahanMakanan::class);
    }

    /**
     * Get the user who recorded this pembelian
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Auto-calculate total_harga before save
     */
    protected static function booted()
    {
        static::creating(function ($pembelian) {
            $pembelian->total_harga = $pembelian->jumlah * $pembelian->harga_satuan;
        });

        static::updating(function ($pembelian) {
            $pembelian->total_harga = $pembelian->jumlah * $pembelian->harga_satuan;
        });
    }
}
