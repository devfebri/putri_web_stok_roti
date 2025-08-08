<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing getAvailableStokApi for today's date filter...\n\n";

// Test tanggal hari ini
$today = Carbon::today()->format('Y-m-d');
echo "Today's date: $today\n\n";

// Simulasi query yang sama seperti di getAvailableStokApi
$availableStok = DB::table('stok_history')
    ->selectRaw('
        stok_history.id, 
        stok_history.stok,
        stok_history.tanggal,
        rotis.nama_roti,
        rotis.rasa_roti,
        CONCAT(
            COALESCE(rotis.nama_roti, ""), 
            " - ", 
            COALESCE(rotis.rasa_roti, ""),
            " (Sisa: ", stok_history.stok, ", ",
            stok_history.tanggal, ")"
        ) as tampil
    ')
    ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
    ->where('stok_history.stok', '>', 0) // Masih ada sisa stok
    ->where('stok_history.tanggal', '=', $today) // Hanya stok dari hari ini
    ->whereNotExists(function($query) {
        // Belum di-waste
        $query->select(DB::raw(1))
              ->from('wastes')
              ->whereRaw('wastes.stok_history_id = stok_history.id');
    })
    ->orderBy('stok_history.tanggal', 'desc')
    ->get();

echo "Available stok for today:\n";
if ($availableStok->count() > 0) {
    foreach ($availableStok as $stok) {
        echo "- ID: {$stok->id}, Stok: {$stok->stok}, Tanggal: {$stok->tanggal}, Roti: {$stok->nama_roti} - {$stok->rasa_roti}\n";
    }
} else {
    echo "No available stock for today.\n";
}

// Cek juga semua stok_history yang ada untuk debugging
echo "\n\nAll stok_history records:\n";
$allStok = DB::table('stok_history')
    ->select('id', 'stok', 'tanggal', 'roti_id')
    ->where('stok', '>', 0)
    ->orderBy('tanggal', 'desc')
    ->get();

foreach ($allStok as $stok) {
    echo "- ID: {$stok->id}, Stok: {$stok->stok}, Tanggal: {$stok->tanggal}, Roti ID: {$stok->roti_id}\n";
}

echo "\nTest completed.\n";
