<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesiPembelian extends Model
{
    use HasFactory;

    protected $table = 'sesi_pembelian';
    
    protected $fillable = [
        'judul',
        'tanggal',
        'catatan',
        'vendor',
        'total',
        'user_id',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total' => 'decimal:2',
    ];

    /**
     * Get the user who created this session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items in this session
     */
    public function items(): HasMany
    {
        return $this->hasMany(PembelianBahan::class);
    }

    /**
     * Get the proof documents for this session
     */
    public function buktiPembelian(): HasMany
    {
        return $this->hasMany(BuktiPembelian::class);
    }

    /**
     * Calculate and update total from items
     */
    public function updateTotal(): void
    {
        $this->total = $this->items()->sum('total_harga');
        $this->save();
    }

    /**
     * Mark session as completed
     */
    public function complete(): void
    {
        $this->status = 'completed';
        $this->save();
    }
}
