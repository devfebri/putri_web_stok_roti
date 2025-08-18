<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING TRANSAKSI DATA ===" . PHP_EOL;

echo "Total transaksi: " . \App\Models\Transaksi::count() . PHP_EOL;

echo "Transaksi terbaru:" . PHP_EOL;
\App\Models\Transaksi::latest()->take(3)->get(['id', 'tanggal_transaksi', 'total_harga', 'created_at'])->each(function($t) {
    echo "ID: {$t->id}, Tanggal: {$t->tanggal_transaksi}, Total: {$t->total_harga}, Created: {$t->created_at}" . PHP_EOL;
});

echo PHP_EOL . "Checking today data:" . PHP_EOL;
$today = date('Y-m-d');
echo "Today: {$today}" . PHP_EOL;
echo "Transaksi hari ini: " . \App\Models\Transaksi::whereDate('tanggal_transaksi', $today)->count() . PHP_EOL;

echo PHP_EOL . "Checking range data:" . PHP_EOL;
$startDate = '2024-01-01';
$endDate = date('Y-m-d');
echo "Range {$startDate} to {$endDate}: " . \App\Models\Transaksi::whereBetween('tanggal_transaksi', [$startDate, $endDate])->count() . PHP_EOL;

echo PHP_EOL . "Sample tanggal_transaksi values:" . PHP_EOL;
\App\Models\Transaksi::select('tanggal_transaksi')->distinct()->limit(5)->get()->each(function($t) {
    echo "Tanggal: {$t->tanggal_transaksi}" . PHP_EOL;
});
