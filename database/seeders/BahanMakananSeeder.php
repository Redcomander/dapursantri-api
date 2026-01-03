<?php

namespace Database\Seeders;

use App\Models\BahanMakanan;
use App\Models\Satuan;
use Illuminate\Database\Seeder;

class BahanMakananSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bahanList = [
            ['nama' => 'Beras', 'satuan' => 'kg'],
            ['nama' => 'Minyak Goreng', 'satuan' => 'liter'],
            ['nama' => 'Gula Pasir', 'satuan' => 'kg'],
            ['nama' => 'Garam', 'satuan' => 'kg'],
            ['nama' => 'Tepung Terigu', 'satuan' => 'kg'],
            ['nama' => 'Telur Ayam', 'satuan' => 'butir'],
            ['nama' => 'Ayam', 'satuan' => 'ekor'],
            ['nama' => 'Daging Sapi', 'satuan' => 'kg'],
            ['nama' => 'Ikan', 'satuan' => 'kg'],
            ['nama' => 'Tempe', 'satuan' => 'pcs'],
            ['nama' => 'Tahu', 'satuan' => 'pcs'],
            ['nama' => 'Bawang Merah', 'satuan' => 'kg'],
            ['nama' => 'Bawang Putih', 'satuan' => 'kg'],
            ['nama' => 'Cabai Merah', 'satuan' => 'kg'],
            ['nama' => 'Cabai Rawit', 'satuan' => 'kg'],
            ['nama' => 'Tomat', 'satuan' => 'kg'],
            ['nama' => 'Wortel', 'satuan' => 'kg'],
            ['nama' => 'Kentang', 'satuan' => 'kg'],
            ['nama' => 'Kol', 'satuan' => 'kg'],
            ['nama' => 'Sayur Bayam', 'satuan' => 'ikat'],
            ['nama' => 'Kangkung', 'satuan' => 'ikat'],
            ['nama' => 'Kecap Manis', 'satuan' => 'botol'],
            ['nama' => 'Santan', 'satuan' => 'liter'],
            ['nama' => 'Bumbu Masak', 'satuan' => 'bungkus'],
            ['nama' => 'Mie Instan', 'satuan' => 'kotak'],
        ];

        foreach ($bahanList as $bahan) {
            $satuan = Satuan::where('nama', $bahan['satuan'])->first();
            if ($satuan) {
                BahanMakanan::create([
                    'nama' => $bahan['nama'],
                    'satuan_id' => $satuan->id,
                    'is_active' => true,
                ]);
            }
        }
    }
}
