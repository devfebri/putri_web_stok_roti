<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\RotiController;
use App\Http\Controllers\RotiPoController;
use App\Http\Controllers\StokHistoryController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WasteController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\FrontlinerMiddleware;
use App\Http\Middleware\KepalaBakeryMiddleware;
use App\Http\Middleware\KepalaTokoKiosMiddleware;
use App\Http\Middleware\PimpinanMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/proses_login_API', [AuthController::class, 'loginApi'])->name('proses_login');

// Test endpoint untuk debug data
Route::get('/test-laporan-data', function () {
    try {
        echo "<h2>Test Data Laporan</h2>";
        
        // Test dengan periode seminggu terakhir
        $tanggal_mulai = date('Y-m-d', strtotime('-7 days'));
        $tanggal_selesai = date('Y-m-d');
        
        echo "<h3>Periode Test: " . $tanggal_mulai . " - " . $tanggal_selesai . "</h3>";
        
        // Test data penjualan
        $penjualanData = \Illuminate\Support\Facades\DB::table('transaksi')
            ->select(
                'transaksi.id',
                'transaksi.nama_customer',
                'transaksi.jumlah',
                'transaksi.harga_satuan',
                'transaksi.total_harga',
                'transaksi.tanggal_transaksi',
                'transaksi.created_at',
                'users.name as user_name',
                'rotis.nama_roti',
                'rotis.rasa_roti'
            )
            ->join('users', 'users.id', '=', 'transaksi.user_id')
            ->join('rotis', 'rotis.id', '=', 'transaksi.roti_id')
            ->whereBetween('transaksi.tanggal_transaksi', [$tanggal_mulai, $tanggal_selesai])
            ->orderBy('transaksi.created_at', 'desc')
            ->get();
            
        echo "<h3>Data Penjualan (" . count($penjualanData) . " records):</h3>";
        foreach($penjualanData as $data) {
            echo "- " . $data->nama_customer . " | " . $data->nama_roti . " | Qty: " . $data->jumlah . " | Total: Rp " . number_format($data->total_harga) . " | Tanggal: " . $data->tanggal_transaksi . "<br>";
        }
        
        // Test data waste dengan berbagai kondisi
        echo "<h3>Debug Data Waste:</h3>";
        $allWaste = \Illuminate\Support\Facades\DB::table('wastes')->get();
        echo "Total waste records: " . count($allWaste) . "<br>";
        
        if(count($allWaste) > 0) {
            $firstWaste = $allWaste->first();
            echo "Sample waste data:<br>";
            echo "- ID: " . $firstWaste->id . "<br>";
            echo "- Tanggal Expired: " . $firstWaste->tanggal_expired . "<br>";
            echo "- Roti ID: " . $firstWaste->roti_id . "<br>";
            echo "- User ID: " . $firstWaste->user_id . "<br>";
            echo "- Jumlah: " . $firstWaste->jumlah_waste . "<br>";
        }
        
        // Test query waste seperti di controller
        $wasteData = \Illuminate\Support\Facades\DB::table('wastes')
            ->select(
                'wastes.*',
                'users.name as user_name',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                'rotis.harga_roti',
                \Illuminate\Support\Facades\DB::raw('(rotis.harga_roti * wastes.jumlah_waste) as total_kerugian')
            )
            ->join('users', 'users.id', '=', 'wastes.user_id')
            ->join('rotis', 'rotis.id', '=', 'wastes.roti_id')
            ->whereBetween('wastes.tanggal_expired', [$tanggal_mulai, $tanggal_selesai])
            ->orderBy('wastes.created_at', 'desc')
            ->get();
            
        echo "<h3>Data Waste dengan Join (" . count($wasteData) . " records):</h3>";
        foreach($wasteData as $data) {
            echo "- " . $data->nama_roti . " | Qty: " . $data->jumlah_waste . " | Kerugian: Rp " . number_format($data->total_kerugian) . " | Expired: " . $data->tanggal_expired . "<br>";
        }
        
        // Test dengan periode yang lebih luas untuk waste
        $tanggal_mulai_luas = date('Y-m-d', strtotime('-30 days'));
        $wasteDataLuas = \Illuminate\Support\Facades\DB::table('wastes')
            ->select(
                'wastes.*',
                'users.name as user_name',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                'rotis.harga_roti',
                \Illuminate\Support\Facades\DB::raw('(rotis.harga_roti * wastes.jumlah_waste) as total_kerugian')
            )
            ->join('users', 'users.id', '=', 'wastes.user_id')
            ->join('rotis', 'rotis.id', '=', 'wastes.roti_id')
            ->whereBetween('wastes.tanggal_expired', [$tanggal_mulai_luas, $tanggal_selesai])
            ->orderBy('wastes.created_at', 'desc')
            ->get();
            
        echo "<h3>Data Waste 30 hari terakhir (" . count($wasteDataLuas) . " records):</h3>";
        foreach($wasteDataLuas as $data) {
            echo "- " . $data->nama_roti . " | Qty: " . $data->jumlah_waste . " | Kerugian: Rp " . number_format($data->total_kerugian) . " | Expired: " . $data->tanggal_expired . "<br>";
        }
        
        return "Test selesai";
        
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage() . "<br>Stack trace: " . $e->getTraceAsString();
    }
});

// Test waste PDF debug
Route::get('/test-waste-pdf-debug', function () {
    try {
        $periode = 'bulanan';
        $tanggalMulai = \Carbon\Carbon::now()->startOfMonth()->toDateString();
        $tanggalSelesai = \Carbon\Carbon::now()->endOfMonth()->toDateString();
        
        echo "<h2>Debug Waste PDF Production</h2>";
        echo "<h3>Periode: $periode ($tanggalMulai - $tanggalSelesai)</h3>";
        
        // Check server info
        echo "<h3>Server Info:</h3>";
        echo "- PHP Version: " . phpversion() . "<br>";
        echo "- Laravel Version: " . app()->version() . "<br>";
        echo "- Environment: " . config('app.env') . "<br>";
        echo "- Debug Mode: " . (config('app.debug') ? 'true' : 'false') . "<br>";
        
        // Check database connection
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            echo "- Database: Connected<br>";
        } catch(Exception $e) {
            echo "- Database: ERROR - " . $e->getMessage() . "<br>";
        }
        
        // Check all waste records first
        $allWastes = \Illuminate\Support\Facades\DB::table('wastes')->get();
        echo "<h3>All Waste Records: " . count($allWastes) . "</h3>";
        
        if(count($allWastes) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Kode</th><th>Expired</th><th>Status</th><th>Created</th></tr>";
            foreach($allWastes as $waste) {
                echo "<tr>";
                echo "<td>" . $waste->id . "</td>";
                echo "<td>" . $waste->kode_waste . "</td>";
                echo "<td>" . $waste->tanggal_expired . "</td>";
                echo "<td>" . $waste->status . "</td>";
                echo "<td>" . $waste->created_at . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Query exact dari controller
        $wasteData = \Illuminate\Support\Facades\DB::table('wastes')
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
                \Illuminate\Support\Facades\DB::raw('(rotis.harga_roti * wastes.jumlah_waste) as total_kerugian')
            )
            ->join('users', 'users.id', '=', 'wastes.user_id')
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
            ->where('wastes.status', '!=', 9)
            ->whereBetween('wastes.tanggal_expired', [$tanggalMulai, $tanggalSelesai])
            ->orderBy('wastes.created_at', 'desc')
            ->get();
            
        echo "<h3>Controller Query Result: " . count($wasteData) . " records</h3>";
        
        if(count($wasteData) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>Kode</th><th>Produk</th><th>Qty</th><th>Harga</th><th>Kerugian</th><th>Expired</th></tr>";
            foreach($wasteData as $waste) {
                echo "<tr>";
                echo "<td>" . $waste->kode_waste . "</td>";
                echo "<td>" . $waste->nama_roti . "</td>";
                echo "<td>" . $waste->jumlah_waste . "</td>";
                echo "<td>Rp " . number_format($waste->harga_roti) . "</td>";
                echo "<td>Rp " . number_format($waste->total_kerugian) . "</td>";
                echo "<td>" . $waste->tanggal_expired . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check logo file
        echo "<h3>Logo File Check:</h3>";
        $logoPath = public_path('img/logo.png');
        echo "- Logo Path: " . $logoPath . "<br>";
        echo "- Logo Exists: " . (file_exists($logoPath) ? 'YES' : 'NO') . "<br>";
        
        // Check DomPDF
        echo "<h3>DomPDF Check:</h3>";
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<h1>Test</h1>');
            echo "- DomPDF: Working<br>";
        } catch(Exception $e) {
            echo "- DomPDF: ERROR - " . $e->getMessage() . "<br>";
        }
        
        // Summary
        $summary = [
            'total_item_waste' => $wasteData->sum('jumlah_waste'),
            'total_kerugian' => $wasteData->sum('total_kerugian'),
            'jumlah_transaksi' => $wasteData->count(),
            'periode' => $periode,
            'tanggal_mulai' => \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y'),
            'tanggal_selesai' => \Carbon\Carbon::parse($tanggalSelesai)->format('d/m/Y'),
        ];
        
        echo "<h3>Summary:</h3>";
        echo "- Total Item Waste: " . number_format($summary['total_item_waste']) . "<br>";
        echo "- Total Kerugian: Rp " . number_format($summary['total_kerugian']) . "<br>";
        echo "- Jumlah Transaksi: " . number_format($summary['jumlah_transaksi']) . "<br>";
        
        return "";
        
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage() . "<br>Stack trace: " . $e->getTraceAsString();
    }
});

// Test PDF generation simple
Route::get('/test-waste-pdf-simple', function () {
    try {
        echo "<h2>Simple PDF Test</h2>";
        
        // Test simple HTML to PDF
        $html = '
        <h1>Test PDF</h1>
        <p>Tanggal: ' . date('d/m/Y H:i:s') . '</p>
        <table border="1">
            <tr><th>No</th><th>Data</th></tr>
            <tr><td>1</td><td>Test Data</td></tr>
        </table>';
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download('test-simple.pdf');
        
    } catch (\Exception $e) {
        return "PDF Error: " . $e->getMessage();
    }
});

// PDF Routes - Public access (no authentication required)
Route::get('/laporan/penjualan/pdf', [LaporanController::class, 'penjualanPdfExport'])->name('public_laporan_penjualan_pdf');
Route::get('/laporan/waste/pdf', [LaporanController::class, 'wastePdfExport'])->name('public_laporan_waste_pdf');
Route::get('/laporan/purchase-order/pdf', [LaporanController::class, 'purchaseOrderPdfExport'])->name('public_laporan_po_pdf');

// User CRUD API routes (for admin management)
Route::get('/user', [UserController::class, 'indexApi'])->name('users_index');
Route::get('/user/{id}', [UserController::class, 'showApi'])->name('users_show');
Route::post('/user', [UserController::class, 'storeApi'])->name('users_store');
Route::put('/user/{id}', [UserController::class, 'updateApi'])->name('users_update');
Route::delete('/user/{id}', [UserController::class, 'destroyApi'])->name('users_destroy');

Route::prefix('admin')->middleware(['auth:sanctum', AdminMiddleware::class])->name('admin_')->group(function () {
    // CRUD Roti
    Route::get('/roti', [RotiController::class, 'indexApi'])->name('roti_index');      // List semua roti
    Route::get('/roti/{id}', [RotiController::class, 'showApi'])->name('roti_show');   // Detail roti
    Route::post('/roti', [RotiController::class, 'storeApi'])->name('roti_store');     // Tambah roti
    Route::put('/roti/{id}', [RotiController::class, 'updateApi'])->name('roti_update'); // Update roti
    Route::delete('/roti/{id}', [RotiController::class, 'destroyApi'])->name('roti_destroy'); // Hapus roti

    // CRUD Roti PO
    Route::get('/rotipo', [RotiPoController::class, 'indexApi'])->name('rotipo_index');      // List semua roti
    Route::get('/getroti', [RotiPoController::class, 'getRotiApi'])->name('rotipo_getroti'); // List semua roti untuk dropdown
    Route::get('/rotipo/{id}', [RotiPoController::class, 'showApi'])->name('rotipo_show');   // Detail roti
    Route::post('/rotipo', [RotiPoController::class, 'storeApi'])->name('rotipo_store');     // Tambah roti
    Route::put('/rotipo/{id}', [RotiPoController::class, 'updateApi'])->name('rotipo_update'); // Update roti
    Route::delete('/rotipo/{id}', [RotiPoController::class, 'destroyApi'])->name('rotipo_destroy'); // Hapus roti
    Route::post('/rotipo/{id}/delivery', [RotiPoController::class, 'deliveryPoApi'])->name('rotipo_delivery');
    Route::post('/rotipo/{id}/selesai', [RotiPoController::class, 'selesaiPoApi'])->name('rotipo_selesai');

    // CRUD Waste
    Route::get('/waste', [WasteController::class, 'indexApi'])->name('waste_index');      // List semua waste
    Route::get('/getavailablestok', [WasteController::class, 'getAvailableStokApi'])->name('waste_getstok'); // List stok tersedia untuk waste
    Route::post('/waste', [WasteController::class, 'storeApi'])->name('waste_store');     // Tambah waste
    Route::put('/waste/{id}', [WasteController::class, 'updateApi'])->name('waste_update'); // Update waste
    Route::delete('/waste/{id}', [WasteController::class, 'destroyApi'])->name('waste_destroy'); // Hapus waste

    // Manajemen Stok
    Route::get('/stok', [StokHistoryController::class, 'getStok'])->name('stok_index');      // List semua stok

    // Laporan
    Route::get('/laporan/waste', [LaporanController::class, 'wasteReportApi'])->name('laporan_waste'); // Laporan waste
    Route::get('/laporan/waste/pdf', [LaporanController::class, 'wastePdfExport'])->name('laporan_waste_pdf'); // Export PDF waste
    Route::get('/laporan/purchase-order', [LaporanController::class, 'purchaseOrderReportApi'])->name('laporan_po'); // Laporan PO
    Route::get('/laporan/purchase-order/pdf', [LaporanController::class, 'purchaseOrderPdfExport'])->name('laporan_po_pdf'); // Export PDF PO
    Route::get('/laporan/penjualan', [LaporanController::class, 'penjualanReportApi'])->name('laporan_penjualan'); // Laporan penjualan
    Route::get('/laporan/penjualan/pdf', [LaporanController::class, 'penjualanPdfExport'])->name('laporan_penjualan_pdf'); // Export PDF penjualan
    Route::get('/laporan/dashboard-stats', [LaporanController::class, 'dashboardStatsApi'])->name('dashboard_stats'); // Dashboard stats
    Route::get('/laporan/debug-po', [LaporanController::class, 'debugPurchaseOrderApi'])->name('debug_po'); // Debug PO
});

Route::prefix('pimpinan')->middleware(['auth:sanctum', PimpinanMiddleware::class])->name('pimpinan_')->group(function () {
    // Laporan untuk Pimpinan (akses semua jenis laporan untuk overview)
    Route::get('/laporan/waste', [LaporanController::class, 'wasteReportApi'])->name('laporan_waste'); // Laporan waste
    Route::get('/laporan/waste/pdf', [LaporanController::class, 'wastePdfExport'])->name('laporan_waste_pdf'); // Export PDF waste
    Route::get('/laporan/purchase-order', [LaporanController::class, 'purchaseOrderReportApi'])->name('laporan_po'); // Laporan PO
    Route::get('/laporan/purchase-order/pdf', [LaporanController::class, 'purchaseOrderPdfExport'])->name('laporan_po_pdf'); // Export PDF PO
    Route::get('/laporan/penjualan', [LaporanController::class, 'penjualanReportApi'])->name('laporan_penjualan'); // Laporan penjualan
    Route::get('/laporan/penjualan/pdf', [LaporanController::class, 'penjualanPdfExport'])->name('laporan_penjualan_pdf'); // Export PDF penjualan
    Route::get('/laporan/dashboard-stats', [LaporanController::class, 'dashboardStatsApi'])->name('dashboard_stats'); // Dashboard stats
});

Route::prefix('kepalabakery')->middleware(['auth:sanctum', KepalaBakeryMiddleware::class])->name('kepalabakery_')->group(function () {
    // CRUD Roti PO
    Route::get('/rotipo', [RotiPoController::class, 'indexApi'])->name('rotipo_index');      // List semua roti
    Route::get('/getroti', [RotiPoController::class, 'getRotiApi'])->name('rotipo_getroti'); // List semua roti untuk dropdown
    Route::get('/rotipo/{id}', [RotiPoController::class, 'showApi'])->name('rotipo_show');   // Detail roti
    Route::post('/rotipo', [RotiPoController::class, 'storeApi'])->name('rotipo_store');     // Tambah roti
    Route::put('/rotipo/{id}', [RotiPoController::class, 'updateApi'])->name('rotipo_update'); // Update roti
    Route::delete('/rotipo/{id}', [RotiPoController::class, 'destroyApi'])->name('rotipo_destroy'); // Hapus roti
    Route::post('/rotipo/{id}/delivery',[RotiPoController::class,'deliveryPoApi'])->name('rotipo_delivery');
    Route::post('/rotipo/{id}/selesai', [RotiPoController::class, 'selesaiPoApi'])->name('rotipo_selesai');
    
    // Laporan untuk Kepala Bakery (hanya Purchase Order)
    Route::get('/laporan/purchase-order', [LaporanController::class, 'purchaseOrderReportApi'])->name('laporan_po'); // Laporan PO
    Route::get('/laporan/purchase-order/pdf', [LaporanController::class, 'purchaseOrderPdfExport'])->name('laporan_po_pdf'); // Export PDF PO
    Route::get('/laporan/dashboard-stats', [LaporanController::class, 'dashboardStatsApi'])->name('dashboard_stats'); // Dashboard stats

});

Route::prefix('kepalatokokios')->middleware(['auth:sanctum', KepalaTokoKiosMiddleware::class])->name('kepalatokokios_')->group(function () {
    //CRUD Roti PO
    Route::get('/rotipo', [RotiPoController::class, 'indexApi'])->name('rotipo_index');      // List semua roti
    Route::get('/getroti',[RotiPoController::class, 'getRotiApi'])->name('rotipo_getroti'); // List semua roti untuk dropdown
    Route::get('/rotipo/{id}', [RotiPoController::class, 'showApi'])->name('rotipo_show');   // Detail roti
    Route::post('/rotipo', [RotiPoController::class, 'storeApi'])->name('rotipo_store');     // Tambah roti
    Route::put('/rotipo/{id}', [RotiPoController::class, 'updateApi'])->name('rotipo_update'); // Update roti
    Route::delete('/rotipo/{id}', [RotiPoController::class, 'destroyApi'])->name('rotipo_destroy'); // Hapus roti

    Route::post('/rotipo/{id}/selesai', [RotiPoController::class, 'selesaiPoApi'])->name('rotipo_selesai');

    //CRUD Waste
    Route::get('/waste', [WasteController::class, 'indexApi'])->name('waste_index');      // List semua waste
    Route::get('/getavailablestok', [WasteController::class, 'getAvailableStokApi'])->name('waste_getstok'); // List stok tersedia untuk waste
    Route::post('/waste', [WasteController::class, 'storeApi'])->name('waste_store');     // Tambah waste
    Route::put('/waste/{id}', [WasteController::class, 'updateApi'])->name('waste_update'); // Update waste
    Route::delete('/waste/{id}', [WasteController::class, 'destroyApi'])->name('waste_destroy'); // Hapus waste

    // Laporan
    Route::get('/laporan/waste', [LaporanController::class, 'wasteReportApi'])->name('laporan_waste'); // Laporan waste
    Route::get('/laporan/waste/pdf', [LaporanController::class, 'wastePdfExport'])->name('laporan_waste_pdf'); // Export PDF waste
    Route::get('/laporan/purchase-order', [LaporanController::class, 'purchaseOrderReportApi'])->name('laporan_po'); // Laporan PO
    Route::get('/laporan/purchase-order/pdf', [LaporanController::class, 'purchaseOrderPdfExport'])->name('laporan_po_pdf'); // Export PDF PO
    Route::get('/laporan/penjualan', [LaporanController::class, 'penjualanReportApi'])->name('laporan_penjualan'); // Laporan penjualan
    Route::get('/laporan/penjualan/pdf', [LaporanController::class, 'penjualanPdfExport'])->name('laporan_penjualan_pdf'); // Export PDF penjualan
    Route::get('/laporan/dashboard-stats', [LaporanController::class, 'dashboardStatsApi'])->name('dashboard_stats'); // Dashboard stats

});

Route::prefix('frontliner')->middleware(['auth:sanctum', FrontlinerMiddleware::class])->name('frontliner_')->group(function () {
    // CRUD Transaksi Penjualan
    Route::get('/transaksi', [TransaksiController::class, 'indexApi'])->name('transaksi_index');      // List semua transaksi
    Route::get('/getroti', [TransaksiController::class, 'getRotiApi'])->name('transaksi_getroti'); // List roti untuk dropdown
    Route::get('/transaksi/{id}', [TransaksiController::class, 'showApi'])->name('transaksi_show');   // Detail transaksi
    Route::post('/transaksi', [TransaksiController::class, 'storeApi'])->name('transaksi_store');     // Tambah transaksi
    Route::put('/transaksi/{id}', [TransaksiController::class, 'updateApi'])->name('transaksi_update'); // Update transaksi
    Route::delete('/transaksi/{id}', [TransaksiController::class, 'destroyApi'])->name('transaksi_destroy'); // Hapus transaksi

    // Stok untuk Transaksi
    Route::get('/stok', [StokHistoryController::class, 'indexForTransaksi'])->name('stok_for_transaksi'); // List stok untuk transaksi

    // Laporan untuk Frontliner (hanya Penjualan)
    Route::get('/laporan/penjualan', [LaporanController::class, 'penjualanReportApi'])->name('laporan_penjualan'); // Laporan penjualan
    Route::get('/laporan/penjualan/pdf', [LaporanController::class, 'penjualanPdfExport'])->name('laporan_penjualan_pdf'); // Export PDF penjualan
    Route::get('/laporan/dashboard-stats', [LaporanController::class, 'dashboardStatsApi'])->name('dashboard_stats'); // Dashboard stats
});