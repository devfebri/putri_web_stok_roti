<?php
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing New Waste System (Simplified)\n";
echo "=====================================\n\n";

try {
    // Check available stok_history
    echo "1. Checking available stok_history data:\n";
    $stokHistory = DB::table('stok_history')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->select('stok_history.*', 'rotis.nama_roti', 'rotis.rasa_roti')
        ->where('stok_history.stok', '>', 0)
        ->get();
    
    echo "Found " . $stokHistory->count() . " stok entries with available stock:\n";
    foreach ($stokHistory as $stok) {
        echo "- ID: {$stok->id}, Roti: {$stok->nama_roti} - {$stok->rasa_roti}, ";
        echo "Stok: {$stok->stok}, Tanggal: {$stok->tanggal}\n";
    }
    echo "\n";

    // Check existing waste data
    echo "2. Checking existing waste data:\n";
    $wastes = DB::table('wastes')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->select('wastes.*', 'rotis.nama_roti', 'rotis.rasa_roti', 'stok_history.tanggal')
        ->get();
    
    echo "Found " . $wastes->count() . " waste entries:\n";
    foreach ($wastes as $waste) {
        echo "- ID: {$waste->id}, Roti: {$waste->nama_roti} - {$waste->rasa_roti}, ";
        echo "Jumlah Waste: {$waste->jumlah_waste}, Tanggal: {$waste->tanggal}\n";
    }
    echo "\n";

    echo "Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
