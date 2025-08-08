<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "Creating test stok_history data for today...\n";

$today = Carbon::today()->format('Y-m-d');
echo "Today's date: $today\n\n";

// Cek apakah sudah ada data hari ini
$existingToday = DB::table('stok_history')->where('tanggal', $today)->count();
echo "Existing records for today: $existingToday\n";

if ($existingToday == 0) {
    echo "No data for today, creating sample data...\n";
    
    // Ambil data roti yang ada
    $rotis = DB::table('rotis')->select('id', 'nama_roti', 'rasa_roti')->limit(3)->get();
    
    if ($rotis->count() > 0) {
        echo "Found " . $rotis->count() . " roti records\n";
        
        foreach ($rotis as $roti) {
            $inserted = DB::table('stok_history')->insert([
                'roti_id' => $roti->id,
                'stok' => rand(5, 20), // Random stock 5-20
                'stok_awal' => rand(20, 50), // Random initial stock 20-50
                'tanggal' => $today,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            if ($inserted) {
                echo "Created stok_history for: {$roti->nama_roti} - {$roti->rasa_roti}\n";
            }
        }
    } else {
        echo "No roti records found. Please add some roti data first.\n";
    }
} else {
    echo "Data for today already exists.\n";
}

// Tampilkan data yang ada
echo "\nCurrent stok_history data:\n";
$currentData = DB::table('stok_history')
    ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
    ->select('stok_history.*', 'rotis.nama_roti', 'rotis.rasa_roti')
    ->orderBy('tanggal', 'desc')
    ->get();

foreach ($currentData as $data) {
    echo "ID: {$data->id}, Roti: {$data->nama_roti} - {$data->rasa_roti}, Stok: {$data->stok}, Tanggal: {$data->tanggal}\n";
}

echo "\nTest data creation completed.\n";
