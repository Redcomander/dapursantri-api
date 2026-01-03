<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;

class SatuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $satuanList = [
            'kg',
            'gram',
            'liter',
            'ml',
            'pcs',
            'bungkus',
            'botol',
            'karung',
            'ikat',
            'butir',
            'ekor',
            'kotak',
        ];

        foreach ($satuanList as $nama) {
            Satuan::create(['nama' => $nama]);
        }
    }
}
