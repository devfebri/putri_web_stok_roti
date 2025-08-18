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
    // Generate automatic Waste code
    private function generateKodeWaste()
    {
        return DB::transaction(function () {
            $date = Carbon::now()->format('Ymd');
            
            $lastWaste = Waste::whereDate('created_at', Carbon::now())
                        ->where('kode_waste', 'LIKE', "WS{$date}%")
                        ->lockForUpdate()
                        ->orderBy('kode_waste', 'desc')
                        ->first();
            
            if ($lastWaste) {
                $lastNumber = intval(substr($lastWaste->kode_waste, -3));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            $newKodeWaste = "WS{$date}" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            
            while (Waste::where('kode_waste', $newKodeWaste)->exists()) {
                $newNumber++;
                $newKodeWaste = "WS{$date}" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
            
            return $newKodeWaste;
        });
    }

    // Get next Waste code preview
    public function getNextKodeWasteApi()
    {
        $nextKodeWaste = $this->generateKodeWaste();
        return response()->json([
            'status' => true,
            'kode_waste' => $nextKodeWaste,
            'message' => 'Kode Waste yang akan digunakan'
        ]);
    }

    public function indexApi()
    {
        $user = Auth::user();
        // $kepalatokokiosId = $user->kepalatokokios_id;
        
        // if (!$kepalatokokiosId) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'User tidak memiliki kepalatokokios_id yang valid'
        //     ], 400);
        // }

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
            ->where('stok_history.kepalatokokios_id', $user->id) // Filter berdasarkan kepalatokokios_id
            ->orderBy('wastes.created_at', 'desc')
            ->get();
            
        return response()->json(['status' => true, 'data' => $waste]);
    }

    public function getAvailableStokApi()
    {
        $user = Auth::user();
        // Ambil stok yang masih ada sisa (stok > 0), berdasarkan kepalatokokios_id, dan belum di-waste
        $availableStok = DB::table('stok_history')
            ->selectRaw('
                stok_history.id, 
                stok_history.stok,
                stok_history.tanggal,
                rotis.id as roti_id,
                rotis.nama_roti,
                rotis.rasa_roti,
                rotis.gambar_roti,
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
            ->where('stok_history.kepalatokokios_id', $user->id) // Filter berdasarkan kepalatokokios_id
            // ->whereNotExists(function($query) {
            //     // Belum di-waste
            //     $query->select(DB::raw(1))
            //           ->from('wastes')
            //           ->whereRaw('wastes.stok_history_id = stok_history.id')
            //           ->where('wastes.status', '!=', 9);
            // })
            // ->whereIn('stok_history.id', function($query) use ($user) {
            //     // Ambil stok history terbaru untuk setiap roti_id
            //     $query->select(DB::raw('MAX(id)'))
            //         ->from('stok_history')
            //         ->where('kepalatokokios_id', $user->id)
            //         ->groupBy('roti_id');
            // })
            ->orderBy('rotis.nama_roti')
            ->orderBy('stok_history.tanggal', 'desc')
            ->get();
            // dd($availableStok);

        return response()->json(['status' => true, 'data' => $availableStok]);
    }

    public function storeApi(Request $request)
    {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.stok_history_id' => 'required|exists:stok_history,id',
            'products.*.jumlah_waste' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $user = Auth::user();
        // $kepalatokokiosId = $user->kepalatokokios_id;
        
        // if (!$kepalatokokiosId) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'User tidak memiliki kepalatokokios_id yang valid'
        //     ], 400);
        // }

        DB::beginTransaction();
        try {
            // Generate kode waste otomatis
            $kodeWaste = $this->generateKodeWaste();
            
            $wastes = [];
            foreach ($request->products as $product) {
                // Validasi stok history
                $stokHistory = StokHistory::where('id', $product['stok_history_id'])
                    ->where('kepalatokokios_id', $user->id)
                    ->first();
                    
                if (!$stokHistory) {
                    DB::rollback();
                    return response()->json([
                        'status' => false,
                        'message' => 'Stok history tidak ditemukan atau bukan milik kepala toko kios Anda'
                    ], 404);
                }

                // Validasi jumlah waste tidak melebihi sisa stok
                if ($product['jumlah_waste'] > $stokHistory->stok) {
                    DB::rollback();
                    return response()->json([
                        'status' => false,
                        'message' => 'Jumlah waste tidak boleh melebihi sisa stok (' . $stokHistory->stok . ')'
                    ], 422);
                }

                // Validasi belum ada waste untuk stok_history ini
                $existingWaste = Waste::where('stok_history_id', $product['stok_history_id'])
                    ->where('status', '!=', 9)
                    ->first();
                if ($existingWaste) {
                    DB::rollback();
                    return response()->json([
                        'status' => false,
                        'message' => 'Stok ini sudah pernah di-waste'
                    ], 422);
                }

                // Buat record waste
                $waste = new Waste();
                $waste->kode_waste = $kodeWaste . '-' . str_pad(count($wastes) + 1, 2, '0', STR_PAD_LEFT);
                $waste->stok_history_id = $product['stok_history_id'];
                $waste->user_id = Auth::id();
                $waste->jumlah_waste = $product['jumlah_waste'];
                $waste->tanggal_expired = $stokHistory->tanggal; // Tanggal yang sama dengan stok
                $waste->keterangan = $request->keterangan;
                $waste->save();

                // Create new stock history record - kurangi stok
                $newStok = $stokHistory->stok - $product['jumlah_waste'];
                $stokHistory=StokHistory::find($product['stok_history_id']);
                $stokHistory->stok=$newStok;
                $stokHistory->updated_at=now();
                $stokHistory->save();
                // StokHistory::create([
                //     'roti_id' => $stokHistory->roti_id,
                //     'stok' => $newStok,
                //     'stok_awal' => $stokHistory->stok,
                //     'kepalatokokios_id' => $user->id,
                //     'tanggal' => now()->toDateString(),
                // ]);

                $wastes[] = $waste;
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data waste berhasil ditambah',
                'kode_waste' => $kodeWaste,
                'data' => $wastes
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

        $user = Auth::user();
        // $kepalatokokiosId = $user->kepalatokokios_id;
        
        // if (!$kepalatokokiosId) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'User tidak memiliki kepalatokokios_id yang valid'
        //     ], 400);
        // }

        $request->validate([
            'kode_waste' => 'required|string|max:50',
            'jumlah_waste' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        // Ambil stok history dan pastikan milik kepalatokokios yang sama
        $stokHistory = StokHistory::where('id', $waste->stok_history_id)
            ->where('kepalatokokios_id', $user->id)
            ->orderBy('id', 'desc')
            ->first();
            
        if (!$stokHistory) {
            return response()->json([
                'status' => false,
                'message' => 'Stok history tidak ditemukan atau bukan milik kepala toko kios Anda'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Kembalikan stok lama dengan membuat record baru
            $restoredStok = $stokHistory->stok + $waste->jumlah_waste;
            
            // Validasi jumlah waste baru tidak melebihi stok yang tersedia
            if ($request->jumlah_waste > $restoredStok) {
                return response()->json([
                    'status' => false,
                    'message' => 'Jumlah waste tidak boleh melebihi sisa stok (' . $restoredStok . ')'
                ], 422);
            }

            // Update waste
            $waste->kode_waste = $request->kode_waste;
            $waste->jumlah_waste = $request->jumlah_waste;
            $waste->keterangan = $request->keterangan;
            $waste->save();

            // Create new stock history record dengan stok yang disesuaikan
            $newStok = $restoredStok - $request->jumlah_waste;
            StokHistory::create([
                'roti_id' => $stokHistory->roti_id,
                'stok' => $newStok,
                'stok_awal' => $restoredStok,
                'kepalatokokios_id' => $user->id,
                'tanggal' => now()->toDateString(),
            ]);

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

        $user = Auth::user();
        // $kepalatokokiosId = $user->kepalatokokios_id;
        
        // if (!$kepalatokokiosId) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'User tidak memiliki kepalatokokios_id yang valid'
        //     ], 400);
        // }

        DB::beginTransaction();
        try {
            // Ambil stok history original untuk mendapat roti_id
            $originalStokHistory = StokHistory::find($waste->stok_history_id);
            if (!$originalStokHistory) {
                DB::rollback();
                return response()->json([
                    'status' => false,
                    'message' => 'Stok history original tidak ditemukan'
                ], 404);
            }

            // Kembalikan stok dengan membuat record baru
            $latestStokHistory = StokHistory::where('roti_id', $originalStokHistory->roti_id)
                ->where('kepalatokokios_id', $user->id)
                ->orderBy('id', 'desc')
                ->first();
            
            if ($latestStokHistory) {
                $newStok = $latestStokHistory->stok + $waste->jumlah_waste;
                $stokHistory = StokHistory::find($waste->stok_history_id);
                $stokHistory->stok = $newStok;
                $stokHistory->updated_at = now();
                $stokHistory->save();

                // StokHistory::create([
                //     'roti_id' => $latestStokHistory->roti_id,
                //     'stok' => $newStok,
                //     'stok_awal' => $latestStokHistory->stok,
                //     'kepalatokokios_id' => $user->id,
                //     'tanggal' => now()->toDateString(),
                // ]);
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
