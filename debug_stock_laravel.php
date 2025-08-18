<?php
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\User;
use App\Models\Roti;
use App\Models\StokHistory;

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Debugging Stock Issue\n";
echo "====================\n\n";

try {
    // Check user data
    $user = User::where('username', 'front1')->first();
    echo "User data:\n";
    echo "- ID: " . $user->id . "\n";
    echo "- Name: " . $user->name . "\n";
    echo "- kepalatokokios_id: " . ($user->kepalatokokios_id ?? 'null') . "\n";
    echo "- Role: " . $user->role . "\n\n";
    
    // Check stok_history data for kepala toko kios 4
    echo "Stok History for kepalatokokios_id = 4:\n";
    $stokHistory = StokHistory::with('roti')
        ->where('kepalatokokios_id', 4)
        ->get();
        
    foreach ($stokHistory as $stok) {
        echo "- {$stok->roti->nama_roti}: {$stok->stok} pcs (ID: {$stok->id}, Date: {$stok->created_at})\n";
    }
    echo "\n";
    
    // Check which ones are latest
    echo "Latest Stok History for kepalatokokios_id = 4:\n";
    $latestStok = StokHistory::select(DB::raw('MAX(id) as latest_id'), 'roti_id')
        ->where('kepalatokokios_id', 4)
        ->groupBy('roti_id')
        ->get();
        
    foreach ($latestStok as $latest) {
        $stokRecord = StokHistory::with('roti')->find($latest->latest_id);
        echo "- {$stokRecord->roti->nama_roti}: {$stokRecord->stok} pcs (Latest ID: {$stokRecord->id})\n";
    }
    echo "\n";
    
    // Test the exact query from TransaksiController
    echo "Testing exact query from TransaksiController:\n";
    $products = Roti::leftJoin('stok_history', function($join) use ($user) {
        $join->on('rotis.id', '=', 'stok_history.roti_id')
            ->where('stok_history.kepalatokokios_id', '=', $user->kepalatokokios_id)
            ->whereIn('stok_history.id', function($query) use ($user) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('stok_history')
                    ->where('kepalatokokios_id', $user->kepalatokokios_id)
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
        
    echo "Products from API query:\n";
    foreach ($products as $product) {
        echo "- {$product->nama}: {$product->stok} pcs (ID: {$product->id})\n";
    }
    echo "\n";
    
    // Simulate transaction creation with ID 1 (roti ID 1)
    echo "Simulating transaction creation (Roti ID: 1, Qty: 2):\n";
    
    // Get current stock for roti ID 1
    $currentStock = StokHistory::where('roti_id', 1)
        ->where('kepalatokokios_id', $user->kepalatokokios_id)
        ->orderBy('id', 'desc')
        ->first();
        
    if ($currentStock) {
        echo "Current stock: {$currentStock->stok}\n";
        
        // Create new stock entry
        $newStock = $currentStock->stok - 2;
        echo "New stock should be: {$newStock}\n";
        
        $newStockHistory = StokHistory::create([
            'roti_id' => 1,
            'stok' => $newStock,
            'kepalatokokios_id' => $user->kepalatokokios_id,
            'keterangan' => 'Transaksi penjualan test'
        ]);
        
        echo "Created new stock history with ID: {$newStockHistory->id}\n";
        
        // Verify new stock
        $verifyStock = StokHistory::where('roti_id', 1)
            ->where('kepalatokokios_id', $user->kepalatokokios_id)
            ->orderBy('id', 'desc')
            ->first();
            
        echo "Verified stock: {$verifyStock->stok}\n";
    } else {
        echo "No current stock found for roti ID 1\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
