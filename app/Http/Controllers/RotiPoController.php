<?php

namespace App\Http\Controllers;

use App\Models\Roti;
use App\Models\RotiPo;
use App\Models\StokHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RotiPoController extends Controller
{
   

    public function indexApi()
    {
        $roti = DB::table('roti_pos')
            ->select(
                'roti_pos.id',
                'roti_pos.kode_po',
                'roti_pos.roti_id',
                'rotis.nama_roti',
                'rotis.gambar_roti', 
                'rotis.rasa_roti',
                'roti_pos.user_id',
                'roti_pos.frontliner_id',
                'roti_pos.deskripsi', 
                'users.name', 
                'frontliner.name as frontliner_name',
                'roti_pos.jumlah_po',
                'roti_pos.tanggal_order', 
                'roti_pos.status'
            )
            ->join('rotis', 'rotis.id', '=', 'roti_pos.roti_id')
            ->join('users', 'users.id', '=', 'roti_pos.user_id')
            ->leftJoin('users as frontliner', 'frontliner.id', '=', 'roti_pos.frontliner_id')
            ->where('roti_pos.status','!=','9')
            ->orderBy('roti_pos.status', 'asc')
            ->orderBy('roti_pos.tanggal_order', 'asc')
            ->get();
        // dd($roti);
        return response()->json(['status' => true, 'data' => $roti]);
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

    public function getFrontlinersApi()
    {
        $frontliners = User::select('id', 'name')
            ->where('role', 'frontliner')
            ->where('status', '!=', 9)
            ->get();
        return response()->json(['status' => true, 'data' => $frontliners]);
    }

    // Tambah roti
    public function storeApi(Request $request)
    {
        // Validasi data dan file
        $request->validate([
            'roti_id' => 'required',
            'user_id' => 'required',
            'frontliner_id' => 'required|exists:users,id',
            'kode_po' => 'required',
            'jumlah_po' => 'required',
            'deskripsi' => 'nullable|string',
            'tanggal_order' => 'required|date',
        ]);

        $roti = new RotiPo();
        $roti->kode_po = $request->kode_po;
        $roti->roti_id = $request->roti_id;
        $roti->user_id = $request->user_id;
        $roti->frontliner_id = $request->frontliner_id;
        $roti->jumlah_po = $request->jumlah_po;
        $roti->deskripsi = $request->deskripsi;
        $roti->tanggal_order = $request->tanggal_order;
        $roti->status = 0;

        if ($roti->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil ditambah',
                'data' => $roti
            ], 201);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Data gagal ditambah'
            ], 500);
        }
    }

    // Update roti
    public function updateApi(Request $request, $id)
    {
        $roti = RotiPo::find($id);
        if (!$roti) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'roti_id' => 'required',
            'user_id' => 'required',
            'frontliner_id' => 'sometimes|exists:users,id',
            'jumlah_po' => 'required',
            'deskripsi' => 'nullable|string',
            'tanggal_order' => 'required|date',
        ]);

        $roti->kode_po = $request->kode_po ?? $roti->kode_po;
        $roti->roti_id = $request->roti_id ?? $roti->roti_id;
        $roti->user_id = $request->user_id ?? $roti->user_id;
        $roti->frontliner_id = $request->frontliner_id ?? $roti->frontliner_id;
        $roti->jumlah_po = $request->jumlah_po ?? $roti->jumlah_po;
        $roti->deskripsi = $request->deskripsi ?? $roti->deskripsi;
        $roti->tanggal_order = $request->tanggal_order;

        $roti->save();
        return response()->json(['status' => true, 'message' => 'Data berhasil diupdate', 'data' => $roti]);
    }

    // Hapus roti
    public function destroyApi($id)
    {
        $roti = RotiPo::find($id);
        if (!$roti) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        // Hapus gambar jika ada
        
        $roti->update(['status'=>9]);
        // $roti->delete();
        return response()->json(['status' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function deliveryPoApi(Request $request, $id)
    {
        $rotiPo = RotiPo::find($id);
        if (!$rotiPo) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Validasi status
        if ($rotiPo->status != 0) {
            return response()->json(['status' => false, 'message' => 'Roti PO sudah dikirim atau tidak valid'], 400);
        }

        // Update status menjadi 1 (dikirim)
        $rotiPo->status = 1;
        $rotiPo->save();

        return response()->json([
            'status' => true,
            'message' => 'Roti PO berhasil dikirim',
            'data' => $rotiPo
        ]);
    }

    public function selesaiPoApi(Request $request, $id)
    {
        $rotiPo = RotiPo::find($id);
        if (!$rotiPo) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Validasi status
        if ($rotiPo->status != 1) {
            return response()->json(['status' => false, 'message' => 'Roti PO sudah dikirim atau tidak valid'], 400);
        }

        // Update status menjadi 2 (dikirim)
        $rotiPo->status = 2;
        $rotiPo->save();

        $rotiHistory = new StokHistory();
        $rotiHistory->roti_id = $rotiPo->roti_id;
        $rotiHistory->stok = $rotiPo->jumlah_po;
        $rotiHistory->stok_awal = $rotiPo->jumlah_po;
        $rotiHistory->frontliner_id = $rotiPo->frontliner_id;
        $rotiHistory->tanggal = Carbon::now();
        $rotiHistory->save();


        return response()->json([
            'status' => true,
            'message' => 'Proses Po Selesai',
            'data' => $rotiPo
        ]);
    }
}
