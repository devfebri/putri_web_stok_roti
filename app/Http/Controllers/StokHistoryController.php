<?php

namespace App\Http\Controllers;

use App\Models\StokHistory;
use App\Models\Transaksi;
use App\Models\Roti;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StokHistoryController extends Controller
{

    /**
     * Get products for transaction (with stock info)
     */
    public function indexForTransaksi()
    {
        try {
            // Get user info from token
            $user = Auth::user();
            $userRole = $user->role ?? '';
            $userId = $user->id;

            $stokhistoryQuery = DB::table('stok_history')
                ->select(
                    'stok_history.id as stok_history_id',
                    'stok_history.roti_id',
                    'rotis.nama_roti', 
                    'rotis.rasa_roti', 
                    'rotis.harga_roti', 
                    'stok_history.stok', 
                    'rotis.gambar_roti', 
                    'stok_history.stok_awal', 
                    'stok_history.tanggal',
                    'frontliner.name as frontliner_name'
                )
                ->join('rotis', 'stok_history.roti_id', '=', 'rotis.id')
                ->leftJoin('users as frontliner', 'frontliner.id', '=', 'stok_history.frontliner_id')
                ->where('stok_history.stok', '>', 0);

            // Filter berdasarkan role
            if (strtolower($userRole) === 'frontliner') {
                // Frontliner hanya melihat stok yang ditugaskan kepada mereka
                $stokhistoryQuery->where('stok_history.frontliner_id', $userId);
            }
            // Admin, kepalatokokios, pimpinan, bakery melihat semua data (tidak ada filter tambahan)

            $stokhistory = $stokhistoryQuery->get();

            return response()->json([
                'success' => true,
                'data' => $stokhistory,
                'message' => 'Data produk untuk transaksi berhasil diambil',
                'user_role' => $userRole,
                'filtered_by_frontliner' => strtolower($userRole) === 'frontliner',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock information for all products
     */
    public function getStok()
    {
        try {
            $stokhistory = DB::table('stok_history')
                ->select(
                    'stok_history.id as stok_history_id',
                    'stok_history.roti_id',
                    'rotis.nama_roti', 
                    'rotis.rasa_roti', 
                    'rotis.harga_roti', 
                    'stok_history.stok', 
                    'rotis.gambar_roti', 
                    'stok_history.stok_awal', 
                    'stok_history.tanggal'
                )
                ->join('rotis', 'stok_history.roti_id', '=', 'rotis.id')
                ->where('stok_history.stok', '>', 0)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stokhistory,
                'message' => 'Data stok berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data stok: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update stock for a product
     */
    // public function updateStok(Request $request, $id)
    // {
    //     try {
    //         // $request->validate([
    //         //     'jumlah' => 'required|integer',
    //         //     'jenis' => 'required|in:tambah,kurang',
    //         //     'keterangan' => 'nullable|string|max:255'
    //         // ]);

    //         $roti = StokHistory::findOrFail($id);
    //         $stokSebelum = $roti->stok;

    //         // Validate that stock won't go negative
    //         if ($roti->stok + $request->jumlah < 0) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Stok tidak boleh kurang dari 0'
    //             ], 422);
    //         }

    //         // Start transaction
    //         DB::beginTransaction();

    //         // Update stock
    //         $roti->increment('stok', $jumlahPerubahan);
    //         $stokSesudah = $roti->stok;

    //         // Record stock history
    //         DB::table('stok_history')->insert([
    //             'roti_id' => $id,
    //             'user_id' => auth()->id(),
    //             'jenis_perubahan' => $request->jenis === 'tambah' ? 'penambahan' : 'pengurangan',
    //             'jumlah_perubahan' => $jumlahPerubahan,
    //             'stok_sebelum' => $stokSebelum,
    //             'stok_sesudah' => $stokSesudah,
    //             'keterangan' => $request->keterangan ?? 'Update stok manual',
    //             'created_at' => now(),
    //             'updated_at' => now()
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'data' => $roti->fresh(),
    //             'message' => 'Stok berhasil diupdate'
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal mengupdate stok: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * Get stock history
     */
    // public function getStokHistory(Request $request)
    // {
    //     try {
    //         $query = DB::table('stok_history')
    //             ->join('roti', 'stok_history.roti_id', '=', 'roti.id')
    //             ->join('users', 'stok_history.user_id', '=', 'users.id')
    //             ->select(
    //                 'stok_history.*',
    //                 'roti.nama_roti',
    //                 'roti.rasa_roti',
    //                 'users.name as user_name'
    //             )
    //             ->orderBy('stok_history.created_at', 'desc');

    //         // Filter by product if specified
    //         if ($request->has('roti_id')) {
    //             $query->where('stok_history.roti_id', $request->roti_id);
    //         }

    //         // Filter by date range if specified
    //         if ($request->has('tanggal_mulai')) {
    //             $query->whereDate('stok_history.created_at', '>=', $request->tanggal_mulai);
    //         }

    //         if ($request->has('tanggal_akhir')) {
    //             $query->whereDate('stok_history.created_at', '<=', $request->tanggal_akhir);
    //         }

    //         $history = $query->paginate(50);

    //         return response()->json([
    //             'success' => true,
    //             'data' => $history,
    //             'message' => 'Data history stok berhasil diambil'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal mengambil data history: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
}
