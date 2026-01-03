<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Satuan extends Model
{
    use HasFactory;

    protected $table = 'satuan';
    
    protected $fillable = ['nama'];

    /**
     * Get all bahan makanan with this satuan
     */
    public function bahanMakanan(): HasMany
    {
        return $this->hasMany(BahanMakanan::class);
    }
}
