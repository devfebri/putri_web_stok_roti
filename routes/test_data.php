<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaporanController;

Route::get('/test-laporan-data', function () {
    try {
        $controller = new LaporanController();
        
        echo "<h2>Test Data Laporan</h2>";
        
        // Test dengan periode seminggu terakhir
        $request = new Request([
            'periode' => 'mingguan',
            'tanggal_mulai' => date('Y-m-d', strtotime('-7 days')),
            'tanggal_selesai' => date('Y-m-d')
        ]);
        
        echo "<h3>Periode Test: " . $request->tanggal_mulai . " - " . $request->tanggal_selesai . "</h3>";
        
        // Test data penjualan
        $penjualanData = DB::table('transaksi')
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
            ->whereBetween('transaksi.tanggal_transaksi', [$request->tanggal_mulai, $request->tanggal_selesai])
            ->orderBy('transaksi.created_at', 'desc')
            ->get();
            
        echo "<h3>Data Penjualan (" . count($penjualanData) . " records):</h3>";
        foreach($penjualanData as $data) {
            echo "- " . $data->nama_customer . " | " . $data->nama_roti . " | Qty: " . $data->jumlah . " | Total: Rp " . number_format($data->total_harga) . " | Tanggal: " . $data->tanggal_transaksi . "<br>";
        }
        
        // Test data waste
        $wasteData = DB::table('wastes')
            ->select(
                'wastes.*',
                'users.name as user_name',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                'rotis.harga_roti',
                DB::raw('(rotis.harga_roti * wastes.jumlah_waste) as total_kerugian')
            )
            ->join('users', 'users.id', '=', 'wastes.user_id')
            ->join('rotis', 'rotis.id', '=', 'wastes.roti_id')
            ->whereBetween('wastes.tanggal_expired', [$request->tanggal_mulai, $request->tanggal_selesai])
            ->orderBy('wastes.created_at', 'desc')
            ->get();
            
        echo "<h3>Data Waste (" . count($wasteData) . " records):</h3>";
        foreach($wasteData as $data) {
            echo "- " . $data->nama_roti . " | Qty: " . $data->jumlah_waste . " | Kerugian: Rp " . number_format($data->total_kerugian) . " | Expired: " . $data->tanggal_expired . "<br>";
        }
        
        // Test data PO
        $poData = DB::table('roti_pos')
            ->select(
                'roti_pos.*',
                'users.name as user_name',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                'rotis.harga_roti',
                DB::raw('(rotis.harga_roti * roti_pos.jumlah_po) as total_nilai')
            )
            ->join('users', 'users.id', '=', 'roti_pos.user_id')
            ->join('rotis', 'rotis.id', '=', 'roti_pos.roti_id')
            ->where('roti_pos.status', '!=', 9)
            ->whereBetween('roti_pos.tanggal_order', [$request->tanggal_mulai, $request->tanggal_selesai])
            ->orderBy('roti_pos.created_at', 'desc')
            ->get();
            
        echo "<h3>Data Purchase Order (" . count($poData) . " records):</h3>";
        foreach($poData as $data) {
            echo "- " . $data->kode_po . " | " . $data->nama_roti . " | Qty: " . $data->jumlah_po . " | Total: Rp " . number_format($data->total_nilai) . " | Order: " . $data->tanggal_order . "<br>";
        }
        
        return "Test selesai";
        
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage() . "<br>Stack trace: " . $e->getTraceAsString();
    }
});
