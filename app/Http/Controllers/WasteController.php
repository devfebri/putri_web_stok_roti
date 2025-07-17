<?php

namespace App\Http\Controllers;

use App\Models\Waste;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WasteController extends Controller
{
    public function indexApi()
    {
        $waste = DB::table('wastes')
            ->select('wastes.id','wastes.rotipo_id','wastes.kode_waste', 'users.name','rotis.nama_roti', 'rotis.rasa_roti', 'rotis.gambar_roti', 'roti_pos.jumlah_po','roti_pos.tanggal_order','wastes.jumlah_waste', 'wastes.keterangan','wastes.jumlah_terjual', 'roti_pos.tanggal_order')
            ->join('roti_pos', 'roti_pos.id', '=', 'wastes.rotipo_id')
            ->join('rotis', 'rotis.id', '=', 'roti_pos.roti_id')
            ->join('users', 'users.id', '=', 'roti_pos.user_id')
            ->where('wastes.status','!=',9)
            ->get();
        // dd($waste);
        return response()->json(['status' => true, 'data' => $waste]);
    }
    public function showApi($id)
    {
        $waste = Waste::find($id);

        if (!$waste) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json(['status' => true, 'data' => $waste]);
    }

    public function getRotiPoApi()
    {
        
        $rotipo = DB::table('roti_pos')
            ->selectRaw('roti_pos.id, CONCAT(COALESCE(kode_po, ""), " || ",COALESCE(nama_roti, ""), " - ", COALESCE(rasa_roti, "")) as tampil')
            ->join('rotis', 'rotis.id', '=', 'roti_pos.roti_id')
            ->where('roti_pos.status', 2)
            ->get();

        return response()->json(['status' => true, 'data' => $rotipo]);
    }

   


    // Tambah roti
    public function storeApi(Request $request)
    {
        // Validasi data dan file
        $request->validate([
            'kode_waste' => 'required',
            'rotipo_id' => 'required',
            'jumlah_waste' => 'required',
            'user_id' => 'required',
            'jumlah_terjual' => 'required',
            'keterangan' => 'nullable|string',
        ]);

        $waste = new Waste();
        $waste->kode_waste = $request->kode_waste;
        $waste->rotipo_id = $request->rotipo_id;
        $waste->user_id = $request->user_id;
        $waste->jumlah_waste = $request->jumlah_waste;
        $waste->jumlah_terjual = $request->jumlah_terjual;
        $waste->keterangan = $request->keterangan;

        if ($waste->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil ditambah',
                'data' => $waste
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
        $waste = Waste::find($id);
        if (!$waste) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        
        // $request->validate([
        //     'kode_waste' => 'required',
        //     'rotipo_id' => 'required',
        //     'jumlah_waste' => 'required',
        //     'user_id' => 'required',
        //     'jumlah_terjual' => 'required',
        //     'keterangan' => 'nullable|string',
        // ]);

        $waste->kode_waste = $request->kode_waste;
        $waste->rotipo_id = $request->rotipo_id;
        $waste->user_id = $request->user_id;
        $waste->jumlah_waste = $request->jumlah_waste;
        $waste->jumlah_terjual = $request->jumlah_terjual;
        $waste->keterangan = $request->keterangan;
        $waste->save();
        return response()->json(['status' => true, 'message' => 'Data berhasil diupdate', 'data' => $waste]);
    }

    // Hapus roti
    public function destroyApi($id)
    {
        $waste = Waste::find($id);
        if (!$waste) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        // Hapus gambar jika ada

        $waste->delete();
        return response()->json(['status' => true, 'message' => 'Data berhasil dihapus']);
    }
}
