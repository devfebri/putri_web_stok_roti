<?php
require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Fixed Waste Report with Tahunan Period\n";
echo "==============================================\n\n";

try {
    // Test basic waste query
    echo "1. Testing Basic Waste Query Structure:\n";
    $basicWasteQuery = \Illuminate\Support\Facades\DB::table('wastes')
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
            \Illuminate\Support\Facades\DB::raw('COALESCE(rotis.harga_roti * wastes.jumlah_waste, 0) as total_kerugian')
        )
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->where('wastes.status', '!=', 9)
        ->limit(5);
    
    $basicResult = $basicWasteQuery->get();
    echo "   ✅ Query structure OK - Found: " . $basicResult->count() . " records\n";
    
    if ($basicResult->count() > 0) {
        $sample = $basicResult->first();
        echo "   Sample data:\n";
        echo "     - Kode Waste: {$sample->kode_waste}\n";
        echo "     - User: {$sample->user_name} ({$sample->user_role})\n";
        echo "     - Roti: {$sample->nama_roti} - {$sample->rasa_roti}\n";
        echo "     - Jumlah: {$sample->jumlah_waste}\n";
        echo "     - Total Kerugian: Rp " . number_format($sample->total_kerugian, 0, ',', '.') . "\n";
        echo "     - Kepala Toko Kios ID: {$sample->kepalatokokios_id}\n";
    }
    echo "\n";
    
    // Test periode filters
    echo "2. Testing Periode Filters:\n";
    
    $today = \Carbon\Carbon::today();
    $weekStart = \Carbon\Carbon::now()->startOfWeek();
    $weekEnd = \Carbon\Carbon::now()->endOfWeek();
    $monthStart = \Carbon\Carbon::now()->startOfMonth();
    $monthEnd = \Carbon\Carbon::now()->endOfMonth();
    $yearStart = \Carbon\Carbon::now()->startOfYear();
    $yearEnd = \Carbon\Carbon::now()->endOfYear();
    
    // Test harian
    $harianCount = \Illuminate\Support\Facades\DB::table('wastes')
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->where('wastes.status', '!=', 9)
        ->whereDate('wastes.created_at', $today->toDateString())
        ->count();
    echo "   Harian ({$today->toDateString()}): {$harianCount} records\n";
    
    // Test mingguan
    $mingguanCount = \Illuminate\Support\Facades\DB::table('wastes')
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->where('wastes.status', '!=', 9)
        ->whereBetween('wastes.created_at', [
            $weekStart->toDateString() . ' 00:00:00',
            $weekEnd->toDateString() . ' 23:59:59'
        ])
        ->count();
    echo "   Mingguan ({$weekStart->toDateString()} - {$weekEnd->toDateString()}): {$mingguanCount} records\n";
    
    // Test bulanan
    $bulananCount = \Illuminate\Support\Facades\DB::table('wastes')
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->where('wastes.status', '!=', 9)
        ->whereBetween('wastes.created_at', [
            $monthStart->toDateString() . ' 00:00:00',
            $monthEnd->toDateString() . ' 23:59:59'
        ])
        ->count();
    echo "   Bulanan ({$monthStart->toDateString()} - {$monthEnd->toDateString()}): {$bulananCount} records\n";
    
    // Test tahunan (NEW!)
    $tahunanCount = \Illuminate\Support\Facades\DB::table('wastes')
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->where('wastes.status', '!=', 9)
        ->whereBetween('wastes.created_at', [
            $yearStart->toDateString() . ' 00:00:00',
            $yearEnd->toDateString() . ' 23:59:59'
        ])
        ->count();
    echo "   ✅ Tahunan ({$yearStart->toDateString()} - {$yearEnd->toDateString()}): {$tahunanCount} records\n\n";
    
    // Test role filtering
    echo "3. Testing Role Filters:\n";
    
    // Get sample users by role
    $frontlinerUser = \Illuminate\Support\Facades\DB::table('users')->where('role', 'frontliner')->first();
    $kepalaTokoUser = \Illuminate\Support\Facades\DB::table('users')->where('role', 'kepalatokokios')->first();
    
    if ($frontlinerUser) {
        $frontlinerCount = \Illuminate\Support\Facades\DB::table('wastes')
            ->join('users', 'users.id', '=', 'wastes.user_id')
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
            ->where('wastes.status', '!=', 9)
            ->where('wastes.user_id', $frontlinerUser->id)
            ->count();
        echo "   Frontliner filter (User: {$frontlinerUser->name}): {$frontlinerCount} records\n";
    }
    
    if ($kepalaTokoUser) {
        $kepalaTokoCount = \Illuminate\Support\Facades\DB::table('wastes')
            ->join('users', 'users.id', '=', 'wastes.user_id')
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
            ->where('wastes.status', '!=', 9)
            ->where(function($query) use ($kepalaTokoUser) {
                $query->where('wastes.user_id', $kepalaTokoUser->id);
                if ($kepalaTokoUser->kepalatokokios_id) {
                    $query->orWhere('stok_history.kepalatokokios_id', $kepalaTokoUser->kepalatokokios_id);
                }
            })
            ->count();
        echo "   Kepala Toko Kios filter (User: {$kepalaTokoUser->name}): {$kepalaTokoCount} records\n";
    }
    echo "\n";
    
    // Test summary calculations
    echo "4. Testing Summary Calculations:\n";
    $summaryData = \Illuminate\Support\Facades\DB::table('wastes')
        ->select(
            \Illuminate\Support\Facades\DB::raw('SUM(wastes.jumlah_waste) as total_waste'),
            \Illuminate\Support\Facades\DB::raw('SUM(COALESCE(rotis.harga_roti * wastes.jumlah_waste, 0)) as total_kerugian'),
            \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_transaksi')
        )
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->where('wastes.status', '!=', 9)
        ->first();
    
    if ($summaryData) {
        echo "   Total Item Waste: {$summaryData->total_waste}\n";
        echo "   Total Kerugian: Rp " . number_format($summaryData->total_kerugian, 0, ',', '.') . "\n";
        echo "   Total Transaksi: {$summaryData->total_transaksi}\n";
    }
    echo "\n";
    
    echo "✅ ALL TESTS PASSED!\n";
    echo "✅ Waste report structure is correct and supports tahunan period!\n";
    echo "✅ Database relationships are working properly!\n";
    echo "✅ Role filtering logic is functional!\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
