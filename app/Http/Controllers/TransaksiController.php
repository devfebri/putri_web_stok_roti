<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\TransaksiRoti;
use App\Models\Roti;
use App\Models\StokHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TransaksiController extends Controller
{
    /**
     * Generate unique kode transaksi
     */
    private function generateKodeTransaksi()
    {
        return DB::transaction(function () {
            $date = Carbon::now()->format('Ymd');
            
            $lastTransaksi = Transaksi::whereDate('created_at', Carbon::now())
                        ->where('kode_transaksi', 'LIKE', "TRX{$date}%")
                        ->lockForUpdate()
                        ->orderBy('kode_transaksi', 'desc')
                        ->first();
            
            if ($lastTransaksi) {
                $lastNumber = intval(substr($lastTransaksi->kode_transaksi, -3));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            $newKodeTransaksi = "TRX{$date}" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            
            while (Transaksi::where('kode_transaksi', $newKodeTransaksi)->exists()) {
                $newNumber++;
                $newKodeTransaksi = "TRX{$date}" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
            
            return $newKodeTransaksi;
        });
    }

    /**
     * Get next kode transaksi preview for form
     */
    public function getNextKodeTransaksiApi()
    {
        $nextKodeTransaksi = $this->generateKodeTransaksi();
        return response()->json([
            'status' => true,
            'kode_transaksi' => $nextKodeTransaksi,
            'message' => 'Kode Transaksi yang akan digunakan'
        ]);
    }

    /**
     * Display a listing of transactions.
     */
    public function indexApi(): JsonResponse
    {
        try {
            // Get user info from token
            $user = Auth::user();
            $userRole = $user->role ?? '';
            $userId = $user->id;
            // dd($u)
            if (strtolower($userRole) === 'frontliner') {
                // Frontliner hanya melihat transaksi dari stok kepala toko kios mereka
                $kepalatokokiosId = $user->kepalatokokios_id;
                
                if (!$kepalatokokiosId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Frontliner belum di-assign ke Kepala Toko Kios',
                        'data' => []
                    ]);
                }
                
                $transaksi = Transaksi::with(['transaksiRoti.roti', 'user'])
                    ->whereHas('transaksiRoti', function($query) use ($kepalatokokiosId) {
                        $query->whereExists(function($subQuery) use ($kepalatokokiosId) {
                            $subQuery->select(DB::raw(1))
                                ->from('stok_history')
                                ->whereColumn('stok_history.roti_id', 'transaksi_roti.roti_id')
                                ->where('stok_history.kepalatokokios_id', $kepalatokokiosId);
                        });
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
                // dd($transaksi);
                   
            } elseif (strtolower($userRole) === 'kepalatokokios') {
                // Kepala Toko Kios melihat transaksi dari stok mereka sendiri
                $transaksi = Transaksi::with(['transaksiRoti.roti', 'user'])
                    ->whereHas('transaksiRoti', function($query) use ($userId) {
                        $query->whereExists(function($subQuery) use ($userId) {
                            $subQuery->select(DB::raw(1))
                                ->from('stok_history')
                                ->whereColumn('stok_history.roti_id', 'transaksi_roti.roti_id')
                                ->where('stok_history.kepalatokokios_id', $userId);
                        });
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                // Admin, pimpinan, bakery melihat semua transaksi
                $transaksi = Transaksi::with(['transaksiRoti.roti', 'user'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $transaksi,
                'message' => 'Data transaksi berhasil diambil',
                'user_role' => $userRole,
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
    {
        try {
            $transaksi = Transaksi::with(['transaksiRoti.roti', 'user'])
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

    public function storeApi(Request $request): JsonResponse
    {
        \Log::info('=== DEBUGGING MULTIPLE PRODUCTS REQUEST ===');
        \Log::info('Request data received:', $request->all());

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'nama_customer' => 'nullable|string|max:255',
            'metode_pembayaran' => 'nullable|string|max:50',
            'tanggal_transaksi' => 'nullable|date',
            'products' => 'required|array|min:1',
            'products.*.stok_history_id' => 'required|exists:stok_history,id',
            'products.*.jumlah' => 'required|integer|min:1',
            'products.*.harga_satuan' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate total harga
            $totalHarga = 0;
            foreach ($request->products as $product) {
                $totalHarga += $product['jumlah'] * $product['harga_satuan'];
            }

            // Generate kode_transaksi using the same method as preview
            $kodeTransaksi = $this->generateKodeTransaksi();

            // Create main transaksi record
            $transaksi = Transaksi::create([
                'kode_transaksi' => $kodeTransaksi,
                'user_id' => $request->user_id,
                'nama_customer' => $request->nama_customer,
                'total_harga' => $totalHarga,
                'metode_pembayaran' => $request->metode_pembayaran ?? 'Cash',
                'tanggal_transaksi' => $request->tanggal_transaksi ?? now(),
            ]);

            // dd($request->products);
            // Process each product
            foreach ($request->products as $product) {
                // Get user's kepalatokokios_id
                $user = Auth::user();
                $kepalatokokiosId = $user->kepalatokokios_id;
                
                if (!$kepalatokokiosId) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'User tidak memiliki kepalatokokios_id yang valid'
                    ], 400);
                }

                // Check stock availability from specific stok_history
                $stokHistory = StokHistory::where('id', $product['stok_history_id'])
                    ->where('kepalatokokios_id', $kepalatokokiosId)
                    ->first();

                if (!$stokHistory) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Stok history tidak ditemukan atau bukan milik kepala toko kios Anda'
                    ], 404);
                }
                
                if ($stokHistory->stok < $product['jumlah']) {
                    DB::rollback();
                    $roti = Roti::find($stokHistory->roti_id);
                    $namaRoti = $roti ? $roti->nama_roti : 'produk';
                    return response()->json([
                        'success' => false,
                        'message' => "Stok tidak cukup untuk {$namaRoti}. Tersedia: {$stokHistory->stok}, diminta: {$product['jumlah']}"
                    ], 400);
                }

                // Create transaksi_roti record
                TransaksiRoti::create([
                    'transaksi_id' => $transaksi->id,
                    'user_id' => $request->user_id,
                    'roti_id' => $stokHistory->roti_id,
                    'stok_history_id' => $product['stok_history_id'],
                    'jumlah' => $product['jumlah'],
                    'harga_satuan' => $product['harga_satuan'],
                ]);

                // Create new stock history record - kurangi stok
                $newStok = $stokHistory->stok - $product['jumlah'];
                $StokHistory = StokHistory::find($product['stok_history_id']);
                $StokHistory->stok= $newStok;
                $StokHistory->updated_at=now();
                $StokHistory->save();
                

                \Log::info('Stock reduced for stok_history_id: ' . $product['stok_history_id'], [
                    'transaction_id' => $transaksi->id,
                    'old_stock' => $stokHistory->stok,
                    'quantity_sold' => $product['jumlah'],
                    'new_stock' => $newStok
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $transaksi->load(['transaksiRoti.roti', 'user']),
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
            'total_harga' => 'sometimes|required|min:0',
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
            $transaksi = Transaksi::with('transaksiRoti')->findOrFail($id);
            
            // Get user's kepalatokokios_id
            $user = Auth::user();
            $kepalatokokiosId = $user->kepalatokokios_id;
            
            if (!$kepalatokokiosId) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki kepalatokokios_id yang valid'
                ], 400);
            }
            
            // Restore stock in stok_history for each product before deleting transaction
            foreach ($transaksi->transaksiRoti as $transaksiRoti) {
                // Find the exact stok_history record that was used for this transaction
                if ($transaksiRoti->stok_history_id) {
                    $stokHistory = StokHistory::find($transaksiRoti->stok_history_id);
                } else {
                    // Fallback for old transactions without stok_history_id
                    $stokHistory = StokHistory::where('roti_id', $transaksiRoti->roti_id)
                        ->where('kepalatokokios_id', $kepalatokokiosId)
                        ->orderBy('tanggal', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();
                }
                
                if ($stokHistory) {
                    // Kembalikan stok dengan membuat record baru
                    $latestStok = StokHistory::where('roti_id', $transaksiRoti->roti_id)
                        ->where('kepalatokokios_id', $kepalatokokiosId)
                        ->orderBy('tanggal', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    $oldStok = $latestStok ? $latestStok->stok : 0;
                    $newStok = $oldStok + $transaksiRoti->jumlah;
                    
                    StokHistory::create([
                        'roti_id' => $transaksiRoti->roti_id,
                        'stok' => $newStok,
                        'stok_awal' => $oldStok,
                        'kepalatokokios_id' => $kepalatokokiosId,
                        'tanggal' => now()->toDateString(),
                    ]);
                    
                    \Log::info('Stock restored for stok_history_id: ' . $transaksiRoti->stok_history_id, [
                        'transaction_id' => $transaksi->id,
                        'kepalatokokios_id' => $kepalatokokiosId,
                        'old_stock' => $oldStok,
                        'restored_quantity' => $transaksiRoti->jumlah,
                        'new_stock' => $newStok
                    ]);
                } else {
                    \Log::warning('Stock history not found for restoration', [
                        'stok_history_id' => $transaksiRoti->stok_history_id,
                        'roti_id' => $transaksiRoti->roti_id,
                        'kepalatokokios_id' => $kepalatokokiosId,
                        'transaction_id' => $transaksi->id
                    ]);
                }
            }
            
            // Delete transaksi_roti records (will be cascade deleted anyway)
            $transaksi->transaksiRoti()->delete();
            
            // Delete main transaksi record
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

    /**
     * Get all available roti/products for transaction dropdown
     */
    public function getRotiApi(): JsonResponse
    {
        try {
            $roti = Roti::select('id', 'nama_roti', 'rasa_roti', 'harga_roti', 'gambar_roti')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $roti,
                'message' => 'Data roti berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data roti: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available products with current stock for transaction based on user's kepalatokokios_id
     */
    public function getProdukTransaksiApi(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get user's kepalatokokios_id
            $kepalatokokiosId = $user->kepalatokokios_id;
            
            // If user doesn't have kepalatokokios_id, return empty result
            if (!$kepalatokokiosId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki kepalatokokios_id yang valid'
                ], 403);
            }

            $produkWithStok = DB::table('rotis')
                ->leftJoin('stok_history', function($join) use ($kepalatokokiosId) {
                    $join->on('rotis.id', '=', 'stok_history.roti_id')
                        ->where('stok_history.kepalatokokios_id', '=', $kepalatokokiosId)
                        ->whereIn('stok_history.id', function($query) use ($kepalatokokiosId) {
                            $query->select(DB::raw('MAX(id)'))
                                ->from('stok_history')
                                ->where('kepalatokokios_id', $kepalatokokiosId)
                                ->groupBy('roti_id');
                        });
                })
                ->select(
                    'rotis.id',
                    'stok_history.id as stok_history_id',
                    'rotis.nama_roti as nama',
                    'rotis.rasa_roti', 
                    'rotis.harga_roti as harga',
                    'rotis.gambar_roti',
                    DB::raw('COALESCE(stok_history.stok, 0) as stok')
                )
                ->where(DB::raw('COALESCE(stok_history.stok, 0)'), '>', 0)
                ->orderBy('rotis.nama_roti')
                ->get();
                    // dd($produkWithStok);
            return response()->json([
                'success' => true,
                'data' => $produkWithStok,
                'message' => "Data produk dengan stok berhasil diambil untuk kepala toko kios ID: {$kepalatokokiosId}"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data produk: ' . $e->getMessage()
            ], 500);
        }
    }
}
