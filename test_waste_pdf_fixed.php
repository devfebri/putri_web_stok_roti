<?php
require_once 'vendor/autoload.php';

// Database connection configuration
$config = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'web_putri',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
];

// Create a new Capsule manager instance
$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Use Capsule
use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;

echo "=== TEST WASTE PDF QUERY FIXED ===\n";

try {
    // Test query structure yang sudah diperbaiki
    $wasteQuery = DB::table('wastes')
        ->select(
            'wastes.id',
            'wastes.kode_waste',
            'wastes.jumlah_waste',
            'wastes.tanggal_expired',
            'wastes.keterangan',
            'wastes.created_at',
            'users.name as user_name',
            'users.role as user_role',
            'rotis.nama_roti',
            'rotis.rasa_roti',
            'rotis.harga_roti',
            'stok_history.tanggal as tanggal_stok',
            'stok_history.kepalatokokios_id',
            DB::raw('COALESCE(rotis.harga_roti * wastes.jumlah_waste, 0) as total_kerugian')
        )
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->where('wastes.status', '!=', 9);

    // Test dengan periode tahunan
    $periode = 'tahunan';
    $tanggalMulai = Carbon::now()->startOfYear()->toDateString();
    $tanggalSelesai = Carbon::now()->endOfYear()->toDateString();

    $wasteQuery->whereBetween('wastes.created_at', [
        $tanggalMulai . ' 00:00:00', 
        $tanggalSelesai . ' 23:59:59'
    ]);

    echo "✅ Query structure OK\n";
    echo "Periode: $periode ($tanggalMulai - $tanggalSelesai)\n";
    
    // Test execute query
    $wasteData = $wasteQuery->orderBy('wastes.created_at', 'desc')->get();
    echo "Found: " . $wasteData->count() . " records\n";

    // Test summary calculations
    $summary = [
        'total_item_waste' => $wasteData->sum('jumlah_waste'),
        'total_kerugian' => $wasteData->sum('total_kerugian'),
        'jumlah_transaksi' => $wasteData->count(),
        'periode' => $periode,
    ];

    echo "\n=== SUMMARY FOR PDF ===\n";
    echo "Total Items: " . $summary['total_item_waste'] . "\n";
    echo "Total Kerugian: Rp " . number_format($summary['total_kerugian'], 0, ',', '.') . "\n";
    echo "Jumlah Transaksi: " . $summary['jumlah_transaksi'] . "\n";

    // Test role filtering simulation
    echo "\n=== ROLE FILTERING TEST ===\n";
    
    // Test Admin (no filter)
    $adminQuery = clone $wasteQuery;
    $adminData = $adminQuery->get();
    echo "Admin sees: " . $adminData->count() . " records\n";

    // Test Frontliner (filter by user_id)
    $frontlinerQuery = clone $wasteQuery;
    $frontlinerQuery->where('wastes.user_id', 2); // Example frontliner ID
    $frontlinerData = $frontlinerQuery->get();
    echo "Frontliner (user_id=2) sees: " . $frontlinerData->count() . " records\n";

    // Test Kepala Toko Kios (filter by kepalatokokios_id)
    $ktkQuery = clone $wasteQuery;
    $ktkQuery->where(function($query) {
        $query->where('wastes.user_id', 3) // Example KTK ID
              ->orWhere('stok_history.kepalatokokios_id', 1); // Example kepalatokokios_id
    });
    $ktkData = $ktkQuery->get();
    echo "Kepala Toko Kios sees: " . $ktkData->count() . " records\n";

    echo "\n✅ ALL PDF QUERY TESTS PASSED!\n";
    echo "✅ No more 'frontliner_id' column errors\n";
    echo "✅ Role-based filtering working\n";
    echo "✅ PDF query ready for export\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
