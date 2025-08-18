<?php

namespace App\Http\Controllers;

use App\Models\Pos;
use App\Models\RotiPo;
use App\Models\Roti;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    // Generate automatic PO code
    private function generateKodePo()
    {
        return DB::transaction(function () {
            $date = Carbon::now()->format('Ymd');
            
            $lastPo = Pos::whereDate('created_at', Carbon::now())
                        ->where('kode_po', 'LIKE', "PO{$date}%")
                        ->lockForUpdate()
                        ->orderBy('kode_po', 'desc')
                        ->first();
            
            if ($lastPo) {
                $lastNumber = intval(substr($lastPo->kode_po, -3));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            $newKodePo = "PO{$date}" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            
            while (Pos::where('kode_po', $newKodePo)->exists()) {
                $newNumber++;
                $newKodePo = "PO{$date}" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
            
            return $newKodePo;
        });
    }

    // Get next PO code preview
    public function getNextKodePoApi()
    {
        $nextKodePo = $this->generateKodePo();
        return response()->json([
            'status' => true,
            'kode_po' => $nextKodePo,
            'message' => 'Kode PO yang akan digunakan'
        ]);
    }

    // List all POs with filtering
    public function indexApi(Request $request)
    {
        $user = $request->user();
        
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
        
        // Filter based on user role
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
                'nama_roti' => $item->nama_roti,
                'rasa_roti' => $item->rasa_roti,
                'harga_roti' => $item->harga_roti,
                'gambar_roti' => $item->gambar_roti,
            ];
        }

        // Tambahkan array products ke setiap PO
        $result = $posList->map(function ($po) use ($rotiPosByPosId) {
            $po = (array) $po;
            $po['products'] = $rotiPosByPosId[$po['id']] ?? [];
            return $po;
        });

        // dd($result);

        return response()->json(['status' => true, 'data' => $result]);
    }

    // Get PO details with roti items
    public function showApi($id)
    {
    $pos = Pos::with(['rotiPos.roti', 'user'])->find($id);
        
        if (!$pos) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        
        return response()->json(['status' => true, 'data' => $pos]);
    }

    // Create new PO
    public function storeApi(Request $request)
    {
        // Support both old format (roti_id, jumlah_po arrays) and new format (roti_items)
        if ($request->has('roti_items')) {
            // New JSON format from Flutter
            $request->validate([
                'roti_items' => 'required|array|min:1',
                'roti_items.*.roti_id' => 'required|exists:rotis,id',
                'roti_items.*.jumlah_po' => 'required|integer|min:1',
                'deskripsi' => 'nullable',
                'tanggal_order' => 'required|date',
            ]);
        } else {
            // Old format
            $request->validate([
                'roti_id' => 'required|array|min:1',
                'roti_id.*' => 'required|exists:rotis,id',
                'user_id' => 'required|exists:users,id',
                'jumlah_po' => 'required|array|min:1',
                'jumlah_po.*' => 'required|integer|min:1',
                'deskripsi' => 'nullable',
                'tanggal_order' => 'required|date',
            ]);
        }

        $kodePo = $this->generateKodePo();

        try {
            DB::beginTransaction();

            // Get user_id from authenticated user if not provided
            $userId = $request->user_id ?? $request->user()->id;

            // Create PO
            $pos = new Pos();
            $pos->kode_po = $kodePo;
            $pos->user_id = $userId;
            $pos->deskripsi = $request->deskripsi ?? '';
            $pos->tanggal_order = $request->tanggal_order;
            $pos->status = 0;
            $pos->save();

            // Create roti items
            $rotiItems = [];

            if ($request->has('roti_items')) {
                // New JSON format from Flutter
                foreach ($request->roti_items as $item) {
                    $rotiPo = new RotiPo();
                    $rotiPo->pos_id = $pos->id;
                    $rotiPo->roti_id = $item['roti_id'];
                    $rotiPo->user_id = $userId;
                    $rotiPo->jumlah_po = $item['jumlah_po'];
                    $rotiPo->save();
                    
                    $rotiItems[] = $rotiPo;
                }
            } else {
                // Old format
                for ($i = 0; $i < count($request->roti_id); $i++) {
                    $rotiPo = new RotiPo();
                    $rotiPo->pos_id = $pos->id;
                    $rotiPo->roti_id = $request->roti_id[$i];
                    $rotiPo->user_id = $userId;
                    $rotiPo->jumlah_po = $request->jumlah_po[$i];
                    $rotiPo->save();
                    
                    $rotiItems[] = $rotiPo;
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase Order berhasil dibuat dengan kode: ' . $kodePo,
                'kode_po' => $kodePo,
                'data' => [
                    'pos' => $pos,
                    'roti_items' => $rotiItems
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Purchase Order gagal dibuat: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update PO
    public function updateApi(Request $request, $id)
    {
        $pos = Pos::find($id);
        if (!$pos) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Support both old format and new format like storeApi
        if ($request->has('roti_items')) {
            // New JSON format from Flutter
            $request->validate([
                'roti_items' => 'required|array|min:1',
                'roti_items.*.roti_id' => 'required|exists:rotis,id',
                'roti_items.*.jumlah_po' => 'required|integer|min:1',
                'deskripsi' => 'nullable',
                'tanggal_order' => 'required|date',
            ]);
        } else {
            // Old format
            $request->validate([
                'deskripsi' => 'nullable',
                'tanggal_order' => 'required|date',
            ]);
        }

        try {
            DB::beginTransaction();

            // Update PO basic info
            $pos->deskripsi = $request->deskripsi ?? $pos->deskripsi;
            $pos->tanggal_order = $request->tanggal_order;
            $pos->save();

            // If roti_items provided, update the roti items
            if ($request->has('roti_items')) {
                // Delete existing roti items
                RotiPo::where('pos_id', $pos->id)->delete();

                // Create new roti items
                foreach ($request->roti_items as $item) {
                    $rotiPo = new RotiPo();
                    $rotiPo->pos_id = $pos->id;
                    $rotiPo->roti_id = $item['roti_id'];
                    $rotiPo->user_id = $pos->user_id;
                    $rotiPo->jumlah_po = $item['jumlah_po'];
                    $rotiPo->save();
                }
            }

            DB::commit();

            return response()->json([
                'status' => true, 
                'message' => 'Data berhasil diupdate', 
                'data' => $pos
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengupdate PO: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete PO
    public function destroyApi($id)
    {
        $pos = Pos::find($id);
        RotiPo::where('pos_id', $id)->delete();
        $pos->delete();
        return response()->json([
            'status' => true,
            'message' => 'PO berhasil dihapus',
        ]);
    }

    // Delivery PO
    public function deliveryPoApi(Request $request, $id)
    {
        $pos = Pos::find($id);
        if (!$pos) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        if ($pos->status != 0) {
            return response()->json(['status' => false, 'message' => 'PO sudah dikirim atau tidak valid'], 400);
        }

        $pos->status = 1;
        $pos->save();

        return response()->json([
            'status' => true,
            'message' => 'PO berhasil dikirim',
            'data' => $pos
        ]);
    }

    // Complete PO
    public function selesaiPoApi(Request $request, $id)
    {
        $pos = Pos::with('rotiPos')->find($id);
        if (!$pos) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        if ($pos->status != 1) {
            return response()->json(['status' => false, 'message' => 'PO belum dikirim atau tidak valid'], 400);
        }

        try {
            DB::beginTransaction();
            
            $pos->status = 2;
            $pos->save();

            // Create or update stok history for each roti item
            foreach ($pos->rotiPos as $rotiPo) {
                $tanggal = Carbon::now()->toDateString();
                $stokHistory = \App\Models\StokHistory::where('roti_id', $rotiPo->roti_id)
                    ->where('kepalatokokios_id', $pos->user_id)
                    ->whereDate('tanggal', $tanggal)
                    ->first();

                if ($stokHistory) {
                    // Update stok_masuk dan stok_akhir jika sudah ada
                    $stokHistory->stok += $rotiPo->jumlah_po;
                    $stokHistory->stok_awal += $rotiPo->jumlah_po;
                    $stokHistory->save();
                } else {
                    // Insert baru
                    $stokHistory = new \App\Models\StokHistory();
                    $stokHistory->roti_id = $rotiPo->roti_id;
                    $stokHistory->stok_awal = $rotiPo->jumlah_po;
                    $stokHistory->stok = $rotiPo->jumlah_po;
                    $stokHistory->kepalatokokios_id = $pos->user_id;
                    $stokHistory->tanggal = $tanggal;
                    $stokHistory->save();
                }
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

    // Get available roti for dropdown
    public function getRotiApi()
    {
        $roti = Roti::selectRaw('id, CONCAT(COALESCE(nama_roti, ""), " - ", COALESCE(rasa_roti, "")) as tampil')
            ->where('status', '!=', 9)
            ->get();
        return response()->json(['status' => true, 'data' => $roti]);
    }

    // Get frontliners for dropdown
    public function getFrontlinersApi(Request $request)
    {
        $user = $request->user();
        
        if ($user && $user->role === 'kepalatokokios') {
            $frontliners = User::select('id', 'name')
                ->where('role', 'frontliner')
                ->where('status', '!=', 9)
                ->where('kepalatokokios_id', $user->id)
                ->get();
        } else {
            $frontliners = User::select('id', 'name')
                ->where('role', 'frontliner')
                ->where('status', '!=', 9)
                ->get();
        }
        
        return response()->json(['status' => true, 'data' => $frontliners]);
    }
}
