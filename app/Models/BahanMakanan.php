<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BahanMakanan extends Model
{
    use HasFactory;

    protected $table = 'bahan_makanan';
    
    protected $fillable = [
        'nama',
        'satuan_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the satuan for this bahan makanan
     */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class);
    }

    /**
     * Get all pembelian for this bahan makanan
     */
    public function pembelianBahan(): HasMany
    {
        return $this->hasMany(PembelianBahan::class);
    }

    /**
     * Scope to get only active bahan makanan
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
