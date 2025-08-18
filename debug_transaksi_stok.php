<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUGGING TRANSAKSI & STOK HISTORY ===" . PHP_EOL;

echo "Transaksi data:" . PHP_EOL;
$transaksi = \App\Models\Transaksi::with(['transaksiRoti.roti', 'user'])->get();
foreach($transaksi as $t) {
    echo "ID: " . $t->id . ", Kode: " . $t->kode_transaksi . ", Customer: " . $t->nama_customer . PHP_EOL;
    foreach($t->transaksiRoti as $tr) {
        echo "  - Roti ID: " . $tr->roti_id . ", Nama: " . $tr->roti->nama_roti . ", Jumlah: " . $tr->jumlah . PHP_EOL;
    }
}

echo PHP_EOL . "Stok History data:" . PHP_EOL;
$stokHistories = \App\Models\StokHistory::with('roti')->get();
foreach($stokHistories as $sh) {
    echo "ID: " . $sh->id . ", Roti ID: " . $sh->roti_id . ", Nama: " . $sh->roti->nama_roti . ", Kepalatokokios ID: " . $sh->kepalatokokios_id . PHP_EOL;
}

echo PHP_EOL . "Users data:" . PHP_EOL;
$users = \App\Models\User::where('role', 'frontliner')->get(['id', 'name', 'role', 'kepalatokokios_id']);
foreach($users as $u) {
    echo "ID: " . $u->id . ", Name: " . $u->name . ", Role: " . $u->role . ", Kepalatokokios ID: " . $u->kepalatokokios_id . PHP_EOL;
}
?>
