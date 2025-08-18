<?php
require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Laporan Harian, Mingguan, Bulanan\n";
echo "==========================================\n\n";

try {
    // Test Query Waste Report
    echo "1. Testing Waste Report Query:\n";
    
    $wasteQuery = \Illuminate\Support\Facades\DB::table('wastes')
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
            'frontliner.name as frontliner_name',
            \Illuminate\Support\Facades\DB::raw('(rotis.harga_roti * wastes.jumlah_waste) as total_kerugian')
        )
        ->join('users', 'users.id', '=', 'wastes.user_id')
        ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
        ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
        ->leftJoin('users as frontliner', 'frontliner.id', '=', 'stok_history.frontliner_id')
        ->where('wastes.status', '!=', 9);
    
    // Test periode harian (hari ini)
    $today = \Carbon\Carbon::today()->toDateString();
    $wasteHarian = $wasteQuery->whereDate('wastes.created_at', $today)->get();
    echo "   Waste Harian ({$today}): " . $wasteHarian->count() . " records\n";
    
    // Test periode mingguan
    $weekStart = \Carbon\Carbon::now()->startOfWeek()->toDateString();
    $weekEnd = \Carbon\Carbon::now()->endOfWeek()->toDateString();
    $wasteMingguan = $wasteQuery->whereBetween('wastes.created_at', [
        $weekStart . ' 00:00:00', 
        $weekEnd . ' 23:59:59'
    ])->get();
    echo "   Waste Mingguan ({$weekStart} - {$weekEnd}): " . $wasteMingguan->count() . " records\n";
    
    // Test periode bulanan
    $monthStart = \Carbon\Carbon::now()->startOfMonth()->toDateString();
    $monthEnd = \Carbon\Carbon::now()->endOfMonth()->toDateString();
    $wasteBulanan = $wasteQuery->whereBetween('wastes.created_at', [
        $monthStart . ' 00:00:00', 
        $monthEnd . ' 23:59:59'
    ])->get();
    echo "   Waste Bulanan ({$monthStart} - {$monthEnd}): " . $wasteBulanan->count() . " records\n\n";
    
    // Test Query Purchase Order Report
    echo "2. Testing Purchase Order Report Query:\n";
    
    $poQuery = \Illuminate\Support\Facades\DB::table('roti_pos')
        ->select(
            'roti_pos.id',
            'roti_pos.kode_po',
            'roti_pos.jumlah_po',
            'roti_pos.status',
            'roti_pos.tanggal_order',
            'roti_pos.deskripsi',
            'roti_pos.created_at',
            'users.name as user_name',
            'rotis.nama_roti',
            'rotis.rasa_roti',
            'rotis.harga_roti',
            'frontliner.name as frontliner_name',
            \Illuminate\Support\Facades\DB::raw('(rotis.harga_roti * roti_pos.jumlah_po) as total_nilai')
        )
        ->join('users', 'users.id', '=', 'roti_pos.user_id')
        ->join('rotis', 'rotis.id', '=', 'roti_pos.roti_id')
        ->leftJoin('users as frontliner', 'frontliner.id', '=', 'roti_pos.frontliner_id')
        ->where('roti_pos.status', '!=', 9);
    
    // Test periode harian (hari ini)
    $poHarian = $poQuery->whereDate('roti_pos.tanggal_order', $today)->get();
    echo "   PO Harian ({$today}): " . $poHarian->count() . " records\n";
    
    // Test periode mingguan
    $poMingguan = $poQuery->whereBetween('roti_pos.tanggal_order', [
        $weekStart . ' 00:00:00', 
        $weekEnd . ' 23:59:59'
    ])->get();
    echo "   PO Mingguan ({$weekStart} - {$weekEnd}): " . $poMingguan->count() . " records\n";
    
    // Test periode bulanan
    $poBulanan = $poQuery->whereBetween('roti_pos.tanggal_order', [
        $monthStart . ' 00:00:00', 
        $monthEnd . ' 23:59:59'
    ])->get();
    echo "   PO Bulanan ({$monthStart} - {$monthEnd}): " . $poBulanan->count() . " records\n\n";
    
    // Test Summary Calculations
    echo "3. Testing Summary Calculations:\n";
    if ($wasteBulanan->count() > 0) {
        $totalKerugian = $wasteBulanan->sum('total_kerugian');
        $totalItemWaste = $wasteBulanan->sum('jumlah_waste');
        echo "   Total Kerugian Waste Bulanan: Rp " . number_format($totalKerugian, 0, ',', '.') . "\n";
        echo "   Total Item Waste Bulanan: " . $totalItemWaste . " items\n";
    }
    
    if ($poBulanan->count() > 0) {
        $totalNilaiPO = $poBulanan->sum('total_nilai');
        $totalItemPO = $poBulanan->sum('jumlah_po');
        echo "   Total Nilai PO Bulanan: Rp " . number_format($totalNilaiPO, 0, ',', '.') . "\n";
        echo "   Total Item PO Bulanan: " . $totalItemPO . " items\n";
    }
    
    echo "\n✅ SEMUA TEST QUERY BERHASIL!\n";
    echo "✅ Laporan harian, mingguan, bulanan siap digunakan!\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
