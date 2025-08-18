<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUGGING TRANSAKSI DATA ===" . PHP_EOL;
echo "Today: " . date('Y-m-d') . PHP_EOL;
echo "Total transaksi: " . \App\Models\Transaksi::count() . PHP_EOL;

echo PHP_EOL . "All transaksi data:" . PHP_EOL;
\App\Models\Transaksi::latest()->get(['id', 'kode_transaksi', 'nama_customer', 'created_at', 'tanggal_transaksi'])->each(function($t) {
    echo "ID: " . $t->id . ", Kode: " . ($t->kode_transaksi ?? 'NULL') . ", Customer: " . $t->nama_customer . ", Created: " . $t->created_at . ", Tanggal: " . $t->tanggal_transaksi . PHP_EOL;
});

echo PHP_EOL . "Today comparison:" . PHP_EOL;
$today = date('Y-m-d');
$todayTransaksi = \App\Models\Transaksi::whereDate('created_at', $today)->count();
echo "Transaksi created today (by created_at): " . $todayTransaksi . PHP_EOL;

$todayTanggalTransaksi = \App\Models\Transaksi::whereDate('tanggal_transaksi', $today)->count();
echo "Transaksi with tanggal_transaksi today: " . $todayTanggalTransaksi . PHP_EOL;
?>
