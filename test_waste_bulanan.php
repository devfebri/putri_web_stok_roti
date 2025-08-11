<?php

try {
    require_once 'vendor/autoload.php';
    
    // Load Laravel app
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "Debug Periode Bulanan Waste\n";
    echo "Tanggal hari ini: " . date('Y-m-d') . "\n";
    
    // Simulasi periode bulanan dari controller
    $tanggalMulai = \Carbon\Carbon::now()->startOfMonth()->toDateString();
    $tanggalSelesai = \Carbon\Carbon::now()->endOfMonth()->toDateString();
    
    echo "Periode bulanan: $tanggalMulai s.d $tanggalSelesai\n";
    
    // Check all waste data
    $allWastes = DB::table('wastes')->get();
    echo "\nAll waste records:\n";
    foreach($allWastes as $waste) {
        echo "- ID: {$waste->id}, Expired: {$waste->tanggal_expired}, Status: {$waste->status}, Created: {$waste->created_at}\n";
    }
    
    // Test query exact dari controller
    echo "\nTesting controller query:\n";
    $wasteData = DB::table('wastes')
        ->select(
            'wastes.id',
            'wastes.kode_waste',
            'wastes.jumlah_waste',
            'wastes.tanggal_expired',
            'wastes.keterangan',
            'wastes.created_at',
            'users.name as user_name',
            'rotis.nama_roti',
            'rotis.rasa_roti',
            'rotis.harga_roti',
            'stok_history.tanggal as tanggal_stok',
            DB::raw('(rotis.harga_roti * wastes.jumlah_waste) as total_kerugian')
        )
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->where('wastes.status', '!=', 9)
        ->whereBetween('wastes.tanggal_expired', [$tanggalMulai, $tanggalSelesai])
        ->orderBy('wastes.created_at', 'desc')
        ->get();
        
    echo "Query result: " . count($wasteData) . " records\n";
    
    if(count($wasteData) > 0) {
        foreach($wasteData as $data) {
            echo "- {$data->kode_waste} | {$data->nama_roti} | Expired: {$data->tanggal_expired}\n";
        }
    }
    
    // Test dengan periode yang lebih spesifik
    echo "\nTesting dengan periode 2025-08-01 s.d 2025-08-31:\n";
    $wasteDataAugust = DB::table('wastes')
        ->select(
            'wastes.id',
            'wastes.kode_waste',
            'wastes.jumlah_waste',
            'wastes.tanggal_expired',
            'wastes.keterangan',
            'wastes.created_at',
            'users.name as user_name',
            'rotis.nama_roti',
            'rotis.rasa_roti',
            'rotis.harga_roti',
            'stok_history.tanggal as tanggal_stok',
            DB::raw('(rotis.harga_roti * wastes.jumlah_waste) as total_kerugian')
        )
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->where('wastes.status', '!=', 9)
        ->whereBetween('wastes.tanggal_expired', ['2025-08-01', '2025-08-31'])
        ->orderBy('wastes.created_at', 'desc')
        ->get();
        
    echo "August query result: " . count($wasteDataAugust) . " records\n";
    
    if(count($wasteDataAugust) > 0) {
        foreach($wasteDataAugust as $data) {
            echo "- {$data->kode_waste} | {$data->nama_roti} | Expired: {$data->tanggal_expired}\n";
        }
    }
    
    // Debug apakah data ada tapi tidak match criteria
    echo "\nChecking if data exists but doesn't match criteria:\n";
    $wasteWithoutFilter = DB::table('wastes')
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->select('wastes.*', 'rotis.nama_roti')
        ->get();
        
    echo "Waste without date filter: " . count($wasteWithoutFilter) . " records\n";
    foreach($wasteWithoutFilter as $data) {
        echo "- {$data->kode_waste} | {$data->nama_roti} | Expired: {$data->tanggal_expired} | Status: {$data->status}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
