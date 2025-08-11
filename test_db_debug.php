<?php

try {
    require_once 'vendor/autoload.php';
    
    // Load Laravel app
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "Testing Database Connection...\n";
    
    // Test database connection
    $transaksiCount = DB::table('transaksi')->count();
    echo "Total transaksi: " . $transaksiCount . "\n";
    
    $wasteCount = DB::table('wastes')->count();
    echo "Total waste: " . $wasteCount . "\n";
    
    $poCount = DB::table('roti_pos')->count();
    echo "Total PO: " . $poCount . "\n";
    
    // Test dengan tanggal hari ini
    $today = date('Y-m-d');
    echo "Tanggal hari ini: " . $today . "\n";
    
    $transaksiToday = DB::table('transaksi')
        ->whereDate('tanggal_transaksi', $today)
        ->count();
    echo "Transaksi hari ini: " . $transaksiToday . "\n";
    
    // Sample data
    $sampleTransaksi = DB::table('transaksi')
        ->limit(1)
        ->get();
    echo "Sample transaksi:\n";
    print_r($sampleTransaksi);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
