<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RotiSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('rotis')->insert([
            [
                'nama_roti' => 'Roti Tawar',
                'rasa_roti' => 'Original',
                'harga_roti' => 12000,
                'gambar_roti' => 'roti_tawar.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_roti' => 'Roti Coklat',
                'rasa_roti' => 'Coklat',
                'harga_roti' => 15000,
                'gambar_roti' => 'roti_coklat.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_roti' => 'Roti Keju',
                'rasa_roti' => 'Keju',
                'harga_roti' => 16000,
                'gambar_roti' => 'roti_keju.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_roti' => 'Roti Pisang',
                'rasa_roti' => 'Pisang',
                'harga_roti' => 14000,
                'gambar_roti' => 'roti_pisang.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_roti' => 'Roti Sosis',
                'rasa_roti' => 'Sosis',
                'harga_roti' => 17000,
                'gambar_roti' => 'roti_sosis.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
