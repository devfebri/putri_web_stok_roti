<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StokHistorySeeder extends Seeder
{
    public function run(): void
    {
        // Get all roti IDs
        $rotiIds = DB::table('rotis')->pluck('id');
        
        // Get kepala toko kios IDs (role = kepalatokokios)
        $kepalaTokokiosIds = DB::table('users')->where('role', 'kepalatokokios')->pluck('id');
        
        // Create stock history for each roti and each kepala toko kios
        foreach ($rotiIds as $rotiId) {
            foreach ($kepalaTokokiosIds as $kepalaTokokiosId) {
                $stokAwal = rand(50, 150); // Random initial stock between 50-150
                DB::table('stok_history')->insert([
                    'roti_id' => $rotiId,
                    'kepalatokokios_id' => $kepalaTokokiosId,
                    'stok' => $stokAwal, // Current stock same as initial
                    'stok_awal' => $stokAwal, // Initial stock
                    'tanggal' => Carbon::today(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
