<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->boot();

use Illuminate\Support\Facades\DB;

echo "TRANSAKSI TABLE STRUCTURE:\n";
$transaksi = DB::select('DESCRIBE transaksi');
foreach($transaksi as $col) {
    echo "  " . $col->Field . " (" . $col->Type . ")\n";
}

echo "\nTRANSAKSI_ROTI TABLE STRUCTURE:\n";
$transaksiRoti = DB::select('DESCRIBE transaksi_roti');
foreach($transaksiRoti as $col) {
    echo "  " . $col->Field . " (" . $col->Type . ")\n";
}

echo "\nROTIS TABLE STRUCTURE:\n";
$rotis = DB::select('DESCRIBE rotis');
foreach($rotis as $col) {
    echo "  " . $col->Field . " (" . $col->Type . ")\n";
}

echo "\nSample data from transaksi:\n";
$sampleTransaksi = DB::table('transaksi')->limit(2)->get();
foreach($sampleTransaksi as $t) {
    echo "  ID: " . $t->id . ", Code: " . $t->kode_transaksi . ", Customer: " . $t->nama_customer . ", Total: " . $t->total_harga . "\n";
}

echo "\nSample data from transaksi_roti:\n";
$sampleTR = DB::table('transaksi_roti')->limit(5)->get();
foreach($sampleTR as $tr) {
    echo "  ID: " . $tr->id . ", Transaksi ID: " . $tr->transaksi_id . ", Roti ID: " . $tr->roti_id . ", Jumlah: " . $tr->jumlah . ", Harga: " . $tr->harga_satuan . "\n";
}
