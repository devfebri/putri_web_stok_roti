<?php

try {
    require_once 'vendor/autoload.php';
    
    // Load Laravel app
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "Testing Waste Data Structure...\n";
    
    // Test table wastes
    $wastes = DB::table('wastes')->get();
    echo "Total wastes records: " . count($wastes) . "\n";
    
    if(count($wastes) > 0) {
        $firstWaste = $wastes->first();
        echo "Sample waste data:\n";
        print_r($firstWaste);
        echo "\n";
    }
    
    // Test table stok_history
    $stokHistory = DB::table('stok_history')->get();
    echo "Total stok_history records: " . count($stokHistory) . "\n";
    
    if(count($stokHistory) > 0) {
        $firstStok = $stokHistory->first();
        echo "Sample stok_history data:\n";
        print_r($firstStok);
        echo "\n";
    }
    
    // Test join query dari controller
    echo "Testing controller query:\n";
    $tanggalMulai = date('Y-m-d', strtotime('-30 days'));
    $tanggalSelesai = date('Y-m-d');
    
    $controllerQuery = DB::table('wastes')
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
        ->whereBetween('stok_history.tanggal', [$tanggalMulai, $tanggalSelesai])
        ->orderBy('wastes.created_at', 'desc')
        ->get();
        
    echo "Controller query result: " . count($controllerQuery) . " records\n";
    
    // Test query alternatif dengan tanggal_expired
    $altQuery = DB::table('wastes')
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
            DB::raw('(rotis.harga_roti * wastes.jumlah_waste) as total_kerugian')
        )
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('rotis', 'rotis.id', '=', 'wastes.roti_id')
        ->where('wastes.status', '!=', 9)
        ->whereBetween('wastes.tanggal_expired', [$tanggalMulai, $tanggalSelesai])
        ->orderBy('wastes.created_at', 'desc')
        ->get();
        
    echo "Alternative query result: " . count($altQuery) . " records\n";
    
    if(count($altQuery) > 0) {
        $firstAlt = $altQuery->first();
        echo "Sample alternative query result:\n";
        print_r($firstAlt);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
