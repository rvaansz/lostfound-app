<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Dompet', 'HP', 'Kunci', 'Tas', 'Dokumen', 'Elektronik', 'Pakaian', 'Lainnya'] as $name) {
        ItemCategory::firstOrCreate(['name' => $name]);
        }

        foreach (['Perpustakaan', 'Kantin', 'Masjid', 'Gedung Kuliah', 'Parkiran', 'Lab Komputer'] as $name) {
        Location::firstOrCreate(['name' => $name]);
        }

        User::firstOrCreate(['email' => 'admin@lostfound.test'], [
        'name' => 'Admin LostFound',
        'password' => Hash::make('password'),
        'role' => 'admin',
        ]);
    }
}
