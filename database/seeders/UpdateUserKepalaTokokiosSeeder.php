<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateUserKepalaTokokiosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari Kepala Toko Kios (untuk dijadikan parent)
        $kepalaTokokios = User::where('role', 'kepalatokokios')
                              ->where('status', '!=', 9)
                              ->first();

        if ($kepalaTokokios) {
            // Update semua frontliner agar memiliki kepalatokokios_id
            User::where('role', 'frontliner')
                ->where('status', '!=', 9)
                ->update(['kepalatokokios_id' => $kepalaTokokios->id]);

            echo "✅ Updated frontliners dengan kepalatokokios_id: {$kepalaTokokios->id}\n";

            // Update admin juga bisa punya kepalatokokios_id untuk testing
            User::where('role', 'admin')
                ->where('status', '!=', 9)
                ->first()
                ?->update(['kepalatokokios_id' => $kepalaTokokios->id]);

            echo "✅ Updated admin dengan kepalatokokios_id untuk testing\n";
        } else {
            echo "❌ Tidak ada Kepala Toko Kios ditemukan\n";
        }

        // Update stok_history yang sudah ada agar menggunakan kepalatokokios_id
        if ($kepalaTokokios) {
            \DB::table('stok_history')
                ->whereNull('kepalatokokios_id')
                ->update(['kepalatokokios_id' => $kepalaTokokios->id]);

            echo "✅ Updated existing stok_history dengan kepalatokokios_id\n";
        }
    }
}
