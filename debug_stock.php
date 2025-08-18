<?php
require_once 'vendor/autoload.php';

echo "Debugging Stock Issue\n";
echo "====================\n\n";

try {
    // Check user data
    $user = DB::table('users')->where('username', 'front1')->first();
    echo "User data:\n";
    echo "- ID: " . $user->id . "\n";
    echo "- Name: " . $user->name . "\n";
    echo "- kepalatokokios_id: " . ($user->kepalatokokios_id ?? 'null') . "\n";
    echo "- Role: " . $user->role . "\n\n";
    
    // Check stok_history data
    echo "Stok History for kepalatokokios_id = 4:\n";
    $stokHistory = DB::table('stok_history')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->where('stok_history.kepalatokokios_id', 4)
        ->select('stok_history.*', 'rotis.nama_roti')
        ->get();
        
    foreach ($stokHistory as $stok) {
        echo "- {$stok->nama_roti}: {$stok->stok} pcs (ID: {$stok->id}, Roti ID: {$stok->roti_id})\n";
    }
    echo "\n";
    
    // Check all stok_history
    echo "All Stok History:\n";
    $allStok = DB::table('stok_history')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->select('stok_history.*', 'rotis.nama_roti')
        ->get();
        
    foreach ($allStok as $stok) {
        echo "- {$stok->nama_roti}: {$stok->stok} pcs (kepalatokokios_id: {$stok->kepalatokokios_id})\n";
    }
    echo "\n";
    
    // Test the same query as in TransaksiController
    echo "Testing query from TransaksiController:\n";
    $testQuery = DB::table('rotis')
        ->leftJoin('stok_history', function($join) {
            $join->on('rotis.id', '=', 'stok_history.roti_id')
                ->where('stok_history.kepalatokokios_id', '=', 4)
                ->whereIn('stok_history.id', function($query) {
                    $query->select(DB::raw('MAX(id)'))
                        ->from('stok_history')
                        ->where('kepalatokokios_id', 4)
                        ->groupBy('roti_id');
                });
        })
        ->select(
            'rotis.id',
            'rotis.nama_roti as nama',
            'rotis.rasa_roti', 
            'rotis.harga_roti as harga',
            'rotis.gambar_roti',
            DB::raw('COALESCE(stok_history.stok, 0) as stok')
        )
        ->where(DB::raw('COALESCE(stok_history.stok, 0)'), '>', 0)
        ->orderBy('rotis.nama_roti')
        ->get();
        
    echo "Query results:\n";
    foreach ($testQuery as $product) {
        echo "- {$product->nama}: {$product->stok} pcs (ID: {$product->id})\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
