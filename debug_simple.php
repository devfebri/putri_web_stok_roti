<?php
try {
    require 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

    echo "=== DEBUGGING TRANSAKSI & STOK HISTORY ===" . PHP_EOL;

    // Cek transaksi
    echo "Checking Transaksi..." . PHP_EOL;
    $transaksiCount = \App\Models\Transaksi::count();
    echo "Total transaksi: " . $transaksiCount . PHP_EOL;

    // Cek transaksi roti
    echo "Checking TransaksiRoti..." . PHP_EOL;
    $transaksiRotiCount = \App\Models\TransaksiRoti::count();
    echo "Total transaksi roti: " . $transaksiRotiCount . PHP_EOL;

    // Cek stok history
    echo "Checking StokHistory..." . PHP_EOL;
    $stokHistoryCount = \App\Models\StokHistory::count();
    echo "Total stok history: " . $stokHistoryCount . PHP_EOL;

    if ($transaksiCount > 0) {
        echo PHP_EOL . "Transaksi data:" . PHP_EOL;
        $transaksi = \App\Models\Transaksi::with('transaksiRoti')->get();
        foreach($transaksi as $t) {
            echo "Transaksi ID: " . $t->id . ", Kode: " . $t->kode_transaksi . ", Customer: " . $t->nama_customer . PHP_EOL;
            foreach($t->transaksiRoti as $tr) {
                echo "  - TransaksiRoti ID: " . $tr->id . ", Roti ID: " . $tr->roti_id . ", Jumlah: " . $tr->jumlah . PHP_EOL;
            }
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . PHP_EOL;
    echo "Line: " . $e->getLine() . PHP_EOL;
}
?>
