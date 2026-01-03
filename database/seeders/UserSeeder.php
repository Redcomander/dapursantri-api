<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin user
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Create Petugas Dapur user
        User::create([
            'name' => 'Petugas Dapur',
            'username' => 'petugas',
            'password' => Hash::make('petugas123'),
            'role' => 'petugas',
        ]);

        // Create Viewer user
        User::create([
            'name' => 'Pimpinan',
            'username' => 'viewer',
            'password' => Hash::make('viewer123'),
            'role' => 'viewer',
        ]);
    }
}
