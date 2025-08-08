<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "Checking stok_history data for today...\n";
$today = Carbon::today()->format('Y-m-d');
echo "Today's date: $today\n\n";

// Check all stok_history records
echo "All stok_history records:\n";
$allStok = DB::table('stok_history')
    ->select('id', 'roti_id', 'stok', 'tanggal', 'created_at')
    ->orderBy('tanggal', 'desc')
    ->get();

foreach ($allStok as $stok) {
    echo "ID: {$stok->id}, Roti ID: {$stok->roti_id}, Stok: {$stok->stok}, Tanggal: {$stok->tanggal}\n";
}

echo "\n\nStok for today only:\n";
$todayStok = DB::table('stok_history')
    ->select('id', 'roti_id', 'stok', 'tanggal')
    ->where('tanggal', '=', $today)
    ->where('stok', '>', 0)
    ->get();

if ($todayStok->count() > 0) {
    foreach ($todayStok as $stok) {
        echo "ID: {$stok->id}, Roti ID: {$stok->roti_id}, Stok: {$stok->stok}, Tanggal: {$stok->tanggal}\n";
    }
} else {
    echo "No stock found for today ($today)\n";
}

echo "\n\nChecking existing waste records:\n";
$existingWastes = DB::table('wastes')
    ->select('id', 'stok_history_id', 'jumlah_waste')
    ->get();

if ($existingWastes->count() > 0) {
    foreach ($existingWastes as $waste) {
        echo "Waste ID: {$waste->id}, Stok History ID: {$waste->stok_history_id}, Jumlah: {$waste->jumlah_waste}\n";
    }
} else {
    echo "No waste records found\n";
}

echo "\nCheck completed.\n";
