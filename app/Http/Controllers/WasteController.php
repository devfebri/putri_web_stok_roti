<?php

namespace App\Http\Controllers;

use App\Models\Waste;
use App\Models\StokHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WasteController extends Controller
{
    public function indexApi()
    {
        $waste = DB::table('wastes')
            ->select(
                'wastes.id',
                'wastes.stok_history_id',
                'wastes.kode_waste', 
                'users.name',
                'rotis.nama_roti', 
                'rotis.rasa_roti', 
                'rotis.gambar_roti',
                'stok_history.stok_awal',
                'stok_history.stok',
                'stok_history.tanggal',
                'wastes.jumlah_waste', 
                'wastes.tanggal_expired',
                'wastes.keterangan'
            )
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
            ->join('users', 'users.id', '=', 'wastes.user_id')
            ->where('wastes.status','!=',9)
            ->orderBy('wastes.created_at', 'desc')
            ->get();
            
        return response()->json(['status' => true, 'data' => $waste]);
    }

    public function getAvailableStokApi()
    {
        // Ambil stok yang masih ada sisa (stok > 0), dari tanggal hari ini, dan belum di-waste
        $today = Carbon::today()->format('Y-m-d');
        
        $availableStok = DB::table('stok_history')
            ->selectRaw('
                stok_history.id, 
                stok_history.stok,
                stok_history.tanggal,
                rotis.nama_roti,
                rotis.rasa_roti,
                CONCAT(
                    COALESCE(rotis.nama_roti, ""), 
                    " - ", 
                    COALESCE(rotis.rasa_roti, ""),
                    " (Sisa: ", stok_history.stok, ", ",
                    stok_history.tanggal, ")"
                ) as tampil
            ')
            ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
            ->where('stok_history.stok', '>', 0) // Masih ada sisa stok
            ->where('stok_history.tanggal', '=', $today) // Hanya stok dari hari ini
            ->whereNotExists(function($query) {
                // Belum di-waste
                $query->select(DB::raw(1))
                      ->from('wastes')
                      ->whereRaw('wastes.stok_history_id = stok_history.id');
            })
            ->orderBy('stok_history.tanggal', 'desc')
            ->get();

        return response()->json(['status' => true, 'data' => $availableStok]);
    }

    public function storeApi(Request $request)
    {
        $request->validate([
            'kode_waste' => 'required|string|max:50',
            'stok_history_id' => 'required|exists:stok_history,id',
            'jumlah_waste' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        // Validasi stok history
        $stokHistory = StokHistory::find($request->stok_history_id);
        if (!$stokHistory) {
            return response()->json([
                'status' => false,
                'message' => 'Stok history tidak ditemukan'
            ], 404);
        }

        // Validasi jumlah waste tidak melebihi sisa stok
        if ($request->jumlah_waste > $stokHistory->stok) {
            return response()->json([
                'status' => false,
                'message' => 'Jumlah waste tidak boleh melebihi sisa stok (' . $stokHistory->stok . ')'
            ], 422);
        }

        // Validasi belum ada waste untuk stok_history ini
        $existingWaste = Waste::where('stok_history_id', $request->stok_history_id)->first();
        if ($existingWaste) {
            return response()->json([
                'status' => false,
                'message' => 'Stok ini sudah pernah di-waste'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Buat record waste
            $waste = new Waste();
            $waste->kode_waste = $request->kode_waste;
            $waste->stok_history_id = $request->stok_history_id;
            $waste->user_id = Auth::id();
            $waste->jumlah_waste = $request->jumlah_waste;
            $waste->tanggal_expired = $stokHistory->tanggal; // Tanggal yang sama dengan stok
            $waste->keterangan = $request->keterangan;
            $waste->save();

            // Update stok di stok_history (kurangi dengan jumlah waste)
            $stokHistory->stok -= $request->jumlah_waste;
            $stokHistory->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data waste berhasil ditambah',
                'data' => $waste
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambah data waste: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateApi(Request $request, $id)
    {
        $waste = Waste::find($id);
        if (!$waste) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'kode_waste' => 'required|string|max:50',
            'jumlah_waste' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        // Ambil stok history
        $stokHistory = StokHistory::find($waste->stok_history_id);
        if (!$stokHistory) {
            return response()->json([
                'status' => false,
                'message' => 'Stok history tidak ditemukan'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Kembalikan stok lama
            $stokHistory->stok += $waste->jumlah_waste;
            
            // Validasi jumlah waste baru tidak melebihi stok yang tersedia
            if ($request->jumlah_waste > $stokHistory->stok) {
                return response()->json([
                    'status' => false,
                    'message' => 'Jumlah waste tidak boleh melebihi sisa stok (' . $stokHistory->stok . ')'
                ], 422);
            }

            // Update waste
            $waste->kode_waste = $request->kode_waste;
            $waste->jumlah_waste = $request->jumlah_waste;
            $waste->keterangan = $request->keterangan;
            $waste->save();

            // Update stok dengan jumlah waste baru
            $stokHistory->stok -= $request->jumlah_waste;
            $stokHistory->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate',
                'data' => $waste
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Gagal update data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyApi($id)
    {
        $waste = Waste::find($id);
        if (!$waste) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        DB::beginTransaction();
        try {
            // Kembalikan stok ke stok_history
            $stokHistory = StokHistory::find($waste->stok_history_id);
            if ($stokHistory) {
                $stokHistory->stok += $waste->jumlah_waste;
                $stokHistory->save();
            }

            // Hapus record waste
            $waste->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus dan stok dikembalikan'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
