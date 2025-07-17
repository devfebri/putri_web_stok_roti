<?php

namespace App\Http\Controllers;

use App\Models\Roti;
use App\Models\RotiPo;
use Illuminate\Http\Request;

class RotiController extends Controller
{
   

    // List semua roti
    public function indexApi()
    {
        $roti = Roti::where('status','!=',9)->get();
        return response()->json(['status' => true, 'data' => $roti]);
    }

    // Detail roti
    public function showApi($id)
    {
        $roti = Roti::find($id);
        if (!$roti) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json(['status' => true, 'data' => $roti]);
    }

    // Tambah roti
    public function storeApi(Request $request)
    {
        // Validasi data dan file
        // $request->validate([
        //     'nama_roti' => 'required|string|max:255',
        //     'rasa_roti' => 'required|string|max:255',
        //     'harga_roti' => 'required|numeric',
        //     'deskripsi_roti' => 'nullable|string',
        //     'gambar_roti' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // max 2MB
        // ]);

        $roti = new Roti();
        $roti->nama_roti = $request->nama_roti;
        $roti->rasa_roti = $request->rasa_roti;
        $roti->harga_roti = $request->harga_roti;
        $roti->deskripsi_roti = $request->deskripsi_roti;

        // Proses upload file jika ada
        if ($request->hasFile('gambar_roti')) {
            $file = $request->file('gambar_roti');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/roti'), $filename);
            $roti->gambar_roti = 'uploads/roti/' . $filename;
        }

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
        $roti = Roti::find($id);
        if (!$roti) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'nama_roti' => 'sometimes|required|string|max:255',
            'rasa_roti' => 'sometimes|required|string|max:255',
            'harga_roti' => 'sometimes|required|numeric',
            'deskripsi_roti' => 'nullable|string',
            'gambar_roti' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $roti->nama_roti = $request->nama_roti ?? $roti->nama_roti;
        $roti->rasa_roti = $request->rasa_roti ?? $roti->rasa_roti;
        $roti->harga_roti = $request->harga_roti ?? $roti->harga_roti;
        $roti->deskripsi_roti = $request->deskripsi_roti ?? $roti->deskripsi_roti;

        // Jika ada file gambar baru, hapus gambar lama lalu upload baru
        if ($request->hasFile('gambar_roti')) {
            if ($roti->gambar_roti && file_exists(public_path($roti->gambar_roti))) {
                @unlink(public_path($roti->gambar_roti));
            }
            $file = $request->file('gambar_roti');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/roti'), $filename);
            $roti->gambar_roti = 'uploads/roti/' . $filename;
        }

        $roti->save();
        return response()->json(['status' => true, 'message' => 'Data berhasil diupdate', 'data' => $roti]);
    }

    // Hapus roti
    public function destroyApi($id)
    {
        $roti = Roti::find($id);
        if (!$roti) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        // Hapus gambar jika ada
        // if ($roti->gambar_roti && file_exists(public_path($roti->gambar_roti))) {
        //     @unlink(public_path($roti->gambar_roti));
        // }
        $roti->update(['status'=>9]);
        return response()->json(['status' => true, 'message' => 'Data berhasil dihapus']);
    }
}
