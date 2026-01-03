<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BuktiPembelian extends Model
{
    use HasFactory;

    protected $table = 'bukti_pembelian';
    
    protected $fillable = [
        'sesi_pembelian_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    protected $appends = ['url'];

    /**
     * Get the session this proof belongs to
     */
    public function sesiPembelian(): BelongsTo
    {
        return $this->belongsTo(SesiPembelian::class);
    }

    /**
     * Get the full URL for the file
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
