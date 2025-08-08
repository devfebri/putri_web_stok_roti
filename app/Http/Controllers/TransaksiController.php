<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Roti;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class TransaksiController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function indexApi(): JsonResponse
    {        try {
            $transaksi = Transaksi::with('roti')
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $transaksi,
                'message' => 'Data transaksi berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the specified transaction.
     */
    public function showApi($id): JsonResponse
    {        try {
            $transaksi = Transaksi::with('roti')
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $transaksi,
                'message' => 'Detail transaksi berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Store a newly created transaction.
     */    public function storeApi(Request $request): JsonResponse
    {
        // Log semua data yang diterima untuk debugging
        \Log::info('=== DEBUGGING REQUEST ===');
        \Log::info('Request method: ' . $request->method());
        \Log::info('Request URL: ' . $request->fullUrl());
        \Log::info('Request headers:', $request->headers->all());
        \Log::info('Raw input:', ['raw' => $request->getContent()]);
        \Log::info('Request data received:', $request->all());
        \Log::info('nama_customer specifically:', [
            'nama_customer' => $request->nama_customer,
            'nama_customer_input' => $request->input('nama_customer'),
            'has_nama_customer' => $request->has('nama_customer'),
            'filled_nama_customer' => $request->filled('nama_customer')
        ]);

        $validator = Validator::make($request->all(), [
            'stok_history_id' => 'required|exists:stok_history,id',
            'user_id' => 'required|exists:users,id',
            'nama_customer' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'harga_satuan' => 'required|numeric|min:0',
            'total_harga' => 'required|numeric|min:0',
            'metode_pembayaran' => 'nullable|string|max:50',
            'tanggal_transaksi' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }        DB::beginTransaction();
        try {
            // Get stok_history data berdasarkan stok_history_id
            $stokHistory = DB::table('stok_history')
                ->where('id', $request->stok_history_id)
                ->first();
            
            if (!$stokHistory) {
                return response()->json([
                    'success' => false,
                    'message' => "Data stok history tidak ditemukan"
                ], 400);
            }
            
            // Validasi stok mencukupi
            if ($stokHistory->stok < $request->jumlah) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok tidak cukup. Tersedia: {$stokHistory->stok}, diminta: {$request->jumlah}"
                ], 400);
            }
              // Debug log
            \Log::info('Stock check for stok_history_id: ' . $request->stok_history_id, [
                'stok_history' => $stokHistory,
                'requested_quantity' => $request->jumlah,                'roti_id_from_history' => $stokHistory->roti_id,
                'nama_customer' => $request->nama_customer,
                'all_request_data' => $request->all()
            ]);

            // Create transaksi dengan roti_id dari stok_history
            $transaksi = Transaksi::create([
                'roti_id' => $stokHistory->roti_id, // Ambil roti_id dari stok_history
                'stok_history_id' => $request->stok_history_id, // Simpan referensi ke stok_history
                'user_id' => $request->user_id,
                'nama_customer' => $request->nama_customer,
                'jumlah' => $request->jumlah,                'harga_satuan' => $request->harga_satuan,
                'total_harga' => $request->total_harga,
                'metode_pembayaran' => $request->metode_pembayaran ?? 'Cash',
                'tanggal_transaksi' => $request->tanggal_transaksi ?? now(),
            ]);

            // Kurangi stok di table stok_history
            DB::table('stok_history')
                ->where('id', $request->stok_history_id)
                ->update([
                    'stok' => $stokHistory->stok - $request->jumlah,
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $transaksi->load('roti'),
                'message' => 'Transaksi berhasil dibuat'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified transaction.
     */
    public function updateApi(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_customer' => 'sometimes|required|string|max:255',
            'nama_barang' => 'sometimes|required|string|max:255',
            'harga' => 'sometimes|required|numeric|min:0',
            'jumlah' => 'sometimes|required|integer|min:1',
            'total_harga' => 'sometimes|required|numeric|min:0',
            'metode_pembayaran' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'tanggal' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }        try {
            $transaksi = Transaksi::where('user_id', Auth::id())->findOrFail($id);
            $transaksi->update($request->only([
                'nama_customer', 'nama_barang', 'harga', 'jumlah', 
                'total_harga', 'metode_pembayaran', 'status', 'tanggal'
            ]));

            return response()->json([
                'success' => true,
                'data' => $transaksi->load('roti'),
                'message' => 'Transaksi berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate transaksi: ' . $e->getMessage()
            ], 500);
        }
    }    /**
     * Remove the specified transaction.
     */
    public function destroyApi($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $transaksi = Transaksi::findOrFail($id);
            
            // Restore stock in stok_history before deleting transaction
            if ($transaksi->stok_history_id) {
                $stokHistory = DB::table('stok_history')
                    ->where('id', $transaksi->stok_history_id)
                    ->first();
                
                if ($stokHistory) {
                    // Kembalikan stok
                    DB::table('stok_history')
                        ->where('id', $transaksi->stok_history_id)
                        ->update([
                            'stok' => $stokHistory->stok + $transaksi->jumlah,
                            'updated_at' => now()
                        ]);
                    
                    \Log::info('Stock restored for stok_history_id: ' . $transaksi->stok_history_id, [
                        'transaction_id' => $transaksi->id,
                        'restored_quantity' => $transaksi->jumlah,
                        'new_stock' => $stokHistory->stok + $transaksi->jumlah
                    ]);
                }
            }
            
            $transaksi->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dihapus dan stok dikembalikan'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus transaksi: ' . $e->getMessage()
            ], 500);
        }
    }
}
