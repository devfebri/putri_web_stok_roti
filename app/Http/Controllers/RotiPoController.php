<?php

namespace App\Http\Controllers;

use App\Models\Pos;
use App\Models\Roti;
use App\Models\RotiPo;
use App\Models\StokHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RotiPoController extends Controller
{
   

    // Get next PO code preview - DEPRECATED: gunakan PosController::getNextKodePoApi
    public function getNextKodePoApi()
    {
        return response()->json([
            'status' => false,
            'message' => 'Endpoint ini sudah tidak digunakan. Gunakan PosController::getNextKodePoApi'
        ], 410);
    }

    // Public method for testing auto generate kode PO - DEPRECATED
    public function testGenerateKodePo()
    {
        return 'DEPRECATED';
    }

    public function indexApi(Request $request)
    {
        // Get authenticated user
        $user = $request->user();

        // Query pos table untuk mendapatkan daftar PO
        $query = DB::table('pos')
            ->select(
                'pos.id',
                'pos.kode_po',
                'pos.deskripsi',
                'pos.tanggal_order',
                'pos.status',
                'pos.user_id',
                'users.name as user_name',
                'pos.created_at'
            )
            ->join('users', 'users.id', '=', 'pos.user_id')
            ->where('pos.status', '!=', '9');

        // Filter berdasarkan role user
        if ($user) {
            if ($user->role === 'kepalatokokios') {
                $query->where('pos.user_id', $user->id);
            } elseif ($user->role === 'kepalabakery') {
                // Kepala bakery sees all POs (for processing)
            } elseif ($user->role === 'admin' || $user->role === 'pimpinan') {
                // Admin and pimpinan see all POs
            } else {
                $query->where('pos.user_id', $user->id);
            }
        }

        $posList = $query->orderBy('pos.status', 'asc')
            ->orderBy('pos.tanggal_order', 'asc')
            ->get();

        // Ambil semua pos_id
        $posIds = $posList->pluck('id')->toArray();

        // Ambil semua roti_pos yang terkait dengan pos_id di atas, join ke rotis
        $rotiPos = DB::table('roti_pos')
            ->select(
                'roti_pos.id',
                'roti_pos.pos_id',
                'roti_pos.roti_id',
                'roti_pos.jumlah_po',
                'roti_pos.deskripsi',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                'rotis.harga_roti',
                'rotis.gambar_roti'
            )
            ->join('rotis', 'rotis.id', '=', 'roti_pos.roti_id')
            ->whereIn('roti_pos.pos_id', $posIds)
            ->get();

        // Group roti_pos by pos_id
        $rotiPosByPosId = [];
        foreach ($rotiPos as $item) {
            $rotiPosByPosId[$item->pos_id][] = [
                'id' => $item->id,
                'roti_id' => $item->roti_id,
                'jumlah_po' => $item->jumlah_po,
                'deskripsi' => $item->deskripsi,
                'nama_roti' => $item->nama_roti,
                'rasa_roti' => $item->rasa_roti,
                'harga_roti' => $item->harga_roti,
                'gambar_roti' => $item->gambar_roti,
            ];
        }

        // Tambahkan array products ke setiap PO
        $result = $posList->map(function ($po) use ($rotiPosByPosId) {
            $po = (array) $po;
            $products = $rotiPosByPosId[$po['id']] ?? [];
            $po['products'] = $products;
            $po['total_quantity'] = array_sum(array_column($products, 'jumlah_po'));
            $po['name'] = $po['user_name']; // Alias untuk konsistensi
            return $po;
        });

        return response()->json(['status' => true, 'data' => $result]);
    }
    public function showApi($id)
    {
        $roti = RotiPo::find($id);
        
        if (!$roti) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json(['status' => true, 'data' => $roti]);
    }

    public function getRotiApi()
    {
        $roti = Roti::selectRaw('id, CONCAT(COALESCE(nama_roti, ""), " - ", COALESCE(rasa_roti, "")) as tampil')
        ->where('status','!=',9)
            ->get();
        return response()->json(['status' => true, 'data' => $roti]);
    }

    public function getFrontlinersApi(Request $request)
    {
        // Get authenticated user
        $user = $request->user();
        
        // If user is kepalatokokios, filter frontliners by kepalatokokios_id
        if ($user && $user->role === 'kepalatokokios') {
            $frontliners = User::select('id', 'name')
                ->where('role', 'frontliner')
                ->where('status', '!=', 9)
                ->where('kepalatokokios_id', $user->id)
                ->get();
        } else {
            // For other roles (admin, etc), show all frontliners
            $frontliners = User::select('id', 'name')
                ->where('role', 'frontliner')
                ->where('status', '!=', 9)
                ->get();
        }
        
        return response()->json(['status' => true, 'data' => $frontliners]);
    }

    // Tambah roti - DEPRECATED: gunakan PosController::storeApi
    public function storeApi(Request $request)
    {
        return response()->json([
            'status' => false,
            'message' => 'Endpoint ini sudah tidak digunakan. Gunakan PosController::storeApi'
        ], 410);
    }

    // Update roti - DEPRECATED: gunakan PosController::updateApi
    public function updateApi(Request $request, $id)
    {
        return response()->json([
            'status' => false,
            'message' => 'Endpoint ini sudah tidak digunakan. Gunakan PosController::updateApi'
        ], 410);
    }

    // Hapus roti
    public function destroyApi($id)
    {
        $pos = Pos::find($id);
        
        if (!$pos) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        
        try {
            DB::beginTransaction();
            
            // Delete related roti_pos items
            RotiPo::where('pos_id', $pos->id)->delete();
            
            // Delete pos
            $pos->delete();
            
            DB::commit();
            
            return response()->json([
                'status' => true, 
                'message' => "PO dengan kode {$pos->kode_po} berhasil dihapus"
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false, 
                'message' => 'Gagal menghapus PO: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deliveryPoApi(Request $request, $id)
    {
        $pos = Pos::find($id);
        if (!$pos) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Validasi status
        if ($pos->status != 0) {
            return response()->json(['status' => false, 'message' => 'PO sudah dikirim atau tidak valid'], 400);
        }

        // Update status menjadi 1 (delivery)
        $pos->status = 1;
        $pos->save();

        return response()->json([
            'status' => true,
            'message' => 'PO berhasil dikirim',
            'data' => $pos
        ]);
    }

    public function selesaiPoApi(Request $request, $id)
    {
        dd('ok');
        $pos = Pos::with('rotiPos')->find($id);
        if (!$pos) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Validasi status
        if ($pos->status != 1) {
            return response()->json(['status' => false, 'message' => 'PO belum dikirim atau tidak valid'], 400);
        }

        try {
            DB::beginTransaction();
            
            // Update status menjadi 2 (selesai)
            $pos->status = 2;
            $pos->save();

            // Create stok history for each roti item
            foreach ($pos->rotiPos as $rotiPo) {
                $stokHistory = new StokHistory();
                $stokHistory->roti_id = $rotiPo->roti_id;
                $stokHistory->stok = $rotiPo->jumlah_po;
                $stokHistory->stok_awal = $rotiPo->jumlah_po;
                $stokHistory->kepalatokokios_id = $pos->user_id;
                $stokHistory->tanggal = Carbon::now();
                $stokHistory->save();
            }
            
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Proses PO Selesai',
                'data' => $pos
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menyelesaikan PO: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatusApi(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|integer|in:0,1,2,3,4'
        ]);

        // First, try to find in the pos table (new structure)
        $pos = Pos::find($id);
        
        if (!$pos) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        try {
            $oldStatus = $pos->status;
            $newStatus = $request->status;
            
            // Allow any status change for now (remove strict validation)
            // This is because we're implementing a new status system
            
            $pos->status = $newStatus;
            $pos->save();
            if($newStatus==4){
                foreach ($pos->rotiPos as $rotiPo) {
                    $stokHistory = new StokHistory();
                    $stokHistory->roti_id = $rotiPo->roti_id;
                    $stokHistory->stok = $rotiPo->jumlah_po;
                    $stokHistory->stok_awal = $rotiPo->jumlah_po;
                    $stokHistory->kepalatokokios_id = $pos->user_id;
                    $stokHistory->tanggal = Carbon::now();
                    $stokHistory->save();
                }
            }

            $statusLabels = [
                0 => 'Pending',
                1 => 'Proses', 
                2 => 'Ditolak',
                3 => 'Delivery',
                4 => 'Selesai'
            ];

            return response()->json([
                'status' => true,
                'message' => "Status berhasil diubah menjadi {$statusLabels[$newStatus]}",
                'data' => [
                    'id' => $pos->id,
                    'kode_po' => $pos->kode_po,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'status_label' => $statusLabels[$newStatus]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }
}
