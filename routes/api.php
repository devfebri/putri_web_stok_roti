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

// Test PDF endpoint tanpa middleware untuk testing
Route::get('/test/laporan/penjualan/pdf', [LaporanController::class, 'penjualanPdfExport'])->name('test_laporan_penjualan_pdf');
Route::get('/test/laporan/waste/pdf', [LaporanController::class, 'wastePdfExport'])->name('test_laporan_waste_pdf');
Route::get('/test/laporan/purchase-order/pdf', [LaporanController::class, 'purchaseOrderPdfExport'])->name('test_laporan_po_pdf');

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