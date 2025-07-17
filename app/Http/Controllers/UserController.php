<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function indexApi()
    {
        $roti = User::where('status','!=',9)->get();
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
            ->where('status', '!=', 9)
            ->get();
        return response()->json(['status' => true, 'data' => $roti]);
    }

    // Tambah roti
    public function storeApi(Request $request)
    {
        // Validasi data dan file
        $request->validate([
            'roti_id' => 'required',
            'user_id' => 'required',
            'kode_po' => 'required',
            'jumlah_po' => 'required',
            'deskripsi' => 'nullable|string',
            'tanggal_order' => 'required|date',
        ]);

        $roti = new RotiPo();
        $roti->kode_po = $request->kode_po;
        $roti->roti_id = $request->roti_id;
        $roti->user_id = $request->user_id;
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
            'jumlah_po' => 'required',
            'deskripsi' => 'nullable|string',
            'tanggal_order' => 'required|date',
        ]);

        $roti->roti_id = $request->roti_id ?? $roti->roti_id;
        $roti->user_id = $request->user_id ?? $roti->user_id;
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

        $roti->update(['status' => 9]);
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

        return response()->json([
            'status' => true,
            'message' => 'Roti PO berhasil dikirim',
            'data' => $rotiPo
        ]);
    }
}
