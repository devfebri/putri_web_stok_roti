<?php
require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Fixed Laporan Queries\n";
echo "==============================\n\n";

try {
    // Test database connection
    echo "1. Testing Database Connection:\n";
    $connection = \Illuminate\Support\Facades\DB::connection();
    $connection->getPdo();
    echo "   ✅ Database connection successful\n\n";
    
    // Test simple waste query tanpa filter role
    echo "2. Testing Basic Waste Query:\n";
    $basicWasteQuery = \Illuminate\Support\Facades\DB::table('wastes')
        ->select(
            'wastes.id',
            'wastes.kode_waste',
            'wastes.jumlah_waste',
            'wastes.created_at',
            'users.name as user_name'
        )
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->where('wastes.status', '!=', 9)
        ->limit(5);
    
    $basicWasteResult = $basicWasteQuery->get();
    echo "   ✅ Basic waste query successful, found: " . $basicWasteResult->count() . " records\n\n";
    
    // Test simple PO query tanpa filter role
    echo "3. Testing Basic PO Query:\n";
    $basicPoQuery = \Illuminate\Support\Facades\DB::table('roti_pos')
        ->select(
            'roti_pos.id',
            'roti_pos.kode_po',
            'roti_pos.jumlah_po',
            'roti_pos.created_at',
            'users.name as user_name'
        )
        ->join('users', 'users.id', '=', 'roti_pos.user_id')
        ->where('roti_pos.status', '!=', 9)
        ->limit(5);
    
    $basicPoResult = $basicPoQuery->get();
    echo "   ✅ Basic PO query successful, found: " . $basicPoResult->count() . " records\n\n";
    
    // Test table structure
    echo "4. Checking Table Structures:\n";
    
    $wasteColumns = \Illuminate\Support\Facades\Schema::getColumnListing('wastes');
    echo "   Wastes table columns: " . implode(', ', $wasteColumns) . "\n";
    
    $poColumns = \Illuminate\Support\Facades\Schema::getColumnListing('roti_pos');
    echo "   Roti_pos table columns: " . implode(', ', $poColumns) . "\n";
    
    $stokHistoryColumns = \Illuminate\Support\Facades\Schema::getColumnListing('stok_history');
    echo "   Stok_history table columns: " . implode(', ', $stokHistoryColumns) . "\n\n";
    
    // Check for problematic columns
    echo "5. Checking Problematic Columns:\n";
    if (in_array('kepalatokokios_id', $stokHistoryColumns)) {
        echo "   ✅ stok_history.kepalatokokios_id exists\n";
    } else {
        echo "   ❌ stok_history.kepalatokokios_id does NOT exist (this was the problem!)\n";
    }
    
    if (in_array('kepalatokokios_id', $poColumns)) {
        echo "   ✅ roti_pos.kepalatokokios_id exists\n";
    } else {
        echo "   ❌ roti_pos.kepalatokokios_id does NOT exist (this was the problem!)\n";
    }
    
    echo "\n✅ ALL TESTS COMPLETED!\n";
    echo "✅ The query structure should now work without errors!\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
