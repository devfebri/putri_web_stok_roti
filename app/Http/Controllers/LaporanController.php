<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class LaporanController extends Controller
{
    public function wasteReportApi(Request $request)
    {
        try {
            \Log::info('=== DEBUGGING LAPORAN WASTE ===');
            \Log::info('Request data received:', $request->all());
            
            $request->validate([
                'periode' => 'required|string',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
            ]);

            $periode = $request->periode;
            $tanggalMulai = $request->tanggal_mulai;
            $tanggalSelesai = $request->tanggal_selesai;

            // Get user info from token
            $user = Auth::user();
            $userRole = $user->role ?? '';
            $userId = $user->id;

            \Log::info("User role: {$userRole}, User ID: {$userId}");
            \Log::info("Periode: {$periode}, Tanggal: {$tanggalMulai} - {$tanggalSelesai}");

            // Query laporan waste dengan struktur database yang benar
            $wasteQuery = DB::table('wastes')
                ->select(
                    'wastes.id',
                    'wastes.kode_waste',
                    'wastes.jumlah_waste',
                    'wastes.tanggal_expired',
                    'wastes.keterangan',
                    'wastes.created_at',
                    'users.name as user_name',
                    'users.role as user_role',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    'rotis.harga_roti',
                    'stok_history.tanggal as tanggal_stok',
                    'stok_history.kepalatokokios_id',
                    DB::raw('COALESCE(rotis.harga_roti * wastes.jumlah_waste, 0) as total_kerugian')
                )
                ->join('users', 'users.id', '=', 'wastes.user_id')
                ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
                ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
                ->where('wastes.status', '!=', 9);

            // Filter berdasarkan periode tanggal
            $wasteQuery->whereBetween('wastes.created_at', [
                $tanggalMulai . ' 00:00:00', 
                $tanggalSelesai . ' 23:59:59'
            ]);

            // Filter berdasarkan role dengan struktur yang benar
            if (strtolower($userRole) === 'frontliner') {
                // Frontliner hanya melihat data mereka sendiri
                $wasteQuery->where('wastes.user_id', $userId);
                \Log::info('Applied frontliner filter');
            } elseif (strtolower($userRole) === 'kepalatokokios') {
                // Kepala toko kios melihat data dari area mereka
                $wasteQuery->where(function($query) use ($user, $userId) {
                    // Data yang dibuat oleh kepala toko kios sendiri
                    $query->where('wastes.user_id', $userId);
                    
                    // Atau data dari stok_history yang sesuai dengan kepalatokokios_id
                    if (isset($user->kepalatokokios_id) && $user->kepalatokokios_id) {
                        $query->orWhere('stok_history.kepalatokokios_id', $user->kepalatokokios_id);
                    }
                });
                \Log::info('Applied kepalatokokios filter');
            }
            // Admin, pimpinan, bakery melihat semua data (tidak ada filter tambahan)

            $wasteData = $wasteQuery->orderBy('wastes.created_at', 'desc')->get();
            
            \Log::info('Query executed, found records: ' . $wasteData->count());

            // Summary data
            $summary = [
                'total_item_waste' => $wasteData->sum('jumlah_waste'),
                'total_kerugian' => $wasteData->sum('total_kerugian'),
                'jumlah_transaksi' => $wasteData->count(),
                'periode' => $periode,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'user_role' => $userRole,
                'filtered_by_role' => in_array(strtolower($userRole), ['frontliner', 'kepalatokokios']),
            ];

            // Group by roti untuk statistik
            $wasteByRoti = $wasteData->groupBy(function($item) {
                return $item->nama_roti . ' - ' . $item->rasa_roti;
            })->map(function($group) {
                return [
                    'nama_roti' => $group->first()->nama_roti,
                    'rasa_roti' => $group->first()->rasa_roti,
                    'total_waste' => $group->sum('jumlah_waste'),
                    'total_kerugian' => $group->sum('total_kerugian'),
                    'frekuensi' => $group->count(),
                ];
            })->values();

            // Group by periode untuk trend analysis
            $trendData = [];
            if ($periode === 'tahunan') {
                $trendData = $wasteData->groupBy(function($item) {
                    return Carbon::parse($item->created_at)->format('Y-m');
                })->map(function($group, $month) {
                    return [
                        'periode' => $month,
                        'total_waste' => $group->sum('jumlah_waste'),
                        'total_kerugian' => $group->sum('total_kerugian'),
                        'jumlah_transaksi' => $group->count(),
                    ];
                })->values();
            } elseif ($periode === 'bulanan') {
                $trendData = $wasteData->groupBy(function($item) {
                    return Carbon::parse($item->created_at)->format('Y-m-d');
                })->map(function($group, $date) {
                    return [
                        'periode' => $date,
                        'total_waste' => $group->sum('jumlah_waste'),
                        'total_kerugian' => $group->sum('total_kerugian'),
                        'jumlah_transaksi' => $group->count(),
                    ];
                })->values();
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'waste_list' => $wasteData,
                    'summary' => $summary,
                    'waste_by_roti' => $wasteByRoti,
                    'trend_data' => $trendData,
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in wasteReportApi: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil laporan waste: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function purchaseOrderReportApi(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|string',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
            ]);

            $periode = $request->periode;
            $tanggalMulai = $request->tanggal_mulai;
            $tanggalSelesai = $request->tanggal_selesai;

            // Get user info from token
            $user = Auth::user();
            $userRole = $user->role ?? '';
            $userId = $user->id;

            // Query laporan purchase order dengan struktur database yang benar: pos -> roti_pos -> rotis
            $poQuery = DB::table('pos')
                ->select(
                    'pos.id as po_id',
                    'pos.kode_po',
                    'pos.deskripsi as po_deskripsi',
                    'pos.tanggal_order',
                    'pos.status as po_status',
                    'pos.created_at',
                    'users.name as user_name',
                    'users.role as user_role',
                    'roti_pos.id as roti_po_id',
                    'roti_pos.jumlah_po',
                    'roti_pos.deskripsi as roti_deskripsi',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    'rotis.harga_roti',
                    DB::raw('COALESCE(rotis.harga_roti * roti_pos.jumlah_po, 0) as total_nilai')
                )
                ->join('users', 'users.id', '=', 'pos.user_id')
                ->join('roti_pos', 'roti_pos.pos_id', '=', 'pos.id')
                ->join('rotis', 'rotis.id', '=', 'roti_pos.roti_id')
                ->where('pos.status', '!=', 9);

            // Filter berdasarkan periode tanggal
            $poQuery->whereBetween('pos.tanggal_order', [
                $tanggalMulai . ' 00:00:00', 
                $tanggalSelesai . ' 23:59:59'
            ]);

            // Filter berdasarkan role
            if (strtolower($userRole) === 'kepalatokokios') {
                // Kepala toko kios melihat PO berdasarkan user_id yang sama dengan id login
                $poQuery->where('pos.user_id', $userId);
            }
            // Admin melihat semua data (tidak ada filter tambahan)

            $poData = $poQuery->orderBy('pos.created_at', 'desc')->get();

            // Summary data
            $summary = [
                'total_item_po' => $poData->sum('jumlah_po'),
                'total_nilai' => $poData->sum('total_nilai'),
                'jumlah_po' => $poData->unique('po_id')->count(), // Count unique PO
                'jumlah_item' => $poData->count(), // Count total items
                'po_pending' => $poData->where('po_status', 0)->unique('po_id')->count(),
                'po_delivery' => $poData->where('po_status', 1)->unique('po_id')->count(),
                'po_selesai' => $poData->where('po_status', 2)->unique('po_id')->count(),
                'periode' => $periode,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'user_role' => $userRole,
                'filtered_by_role' => in_array(strtolower($userRole), ['kepalatokokios']),
            ];

            // Group by roti untuk statistik
            $poByRoti = $poData->groupBy(function($item) {
                return $item->nama_roti . ' - ' . $item->rasa_roti;
            })->map(function($group) {
                return [
                    'nama_roti' => $group->first()->nama_roti,
                    'rasa_roti' => $group->first()->rasa_roti,
                    'total_po' => $group->sum('jumlah_po'),
                    'total_nilai' => $group->sum('total_nilai'),
                    'frekuensi' => $group->count(),
                ];
            })->values();

            // Group by status
            $poByStatus = $poData->groupBy('po_status')->map(function($group, $status) {
                $statusText = '';
                switch ($status) {
                    case 0: $statusText = 'Pending'; break;
                    case 1: $statusText = 'Delivery'; break;
                    case 2: $statusText = 'Selesai'; break;
                    default: $statusText = 'Unknown'; break;
                }
                
                return [
                    'status' => $status,
                    'status_text' => $statusText,
                    'jumlah_po' => $group->unique('po_id')->count(),
                    'jumlah_item' => $group->count(),
                    'total_item' => $group->sum('jumlah_po'),
                    'total_nilai' => $group->sum('total_nilai'),
                ];
            })->values();

            // Group by periode untuk trend analysis
            $trendData = [];
            if ($periode === 'tahunan') {
                $trendData = $poData->groupBy(function($item) {
                    return Carbon::parse($item->tanggal_order)->format('Y-m');
                })->map(function($group, $month) {
                    return [
                        'periode' => $month,
                        'total_po' => $group->unique('po_id')->count(),
                        'total_item' => $group->sum('jumlah_po'),
                        'total_nilai' => $group->sum('total_nilai'),
                    ];
                })->values();
            } elseif ($periode === 'bulanan') {
                $trendData = $poData->groupBy(function($item) {
                    return Carbon::parse($item->tanggal_order)->format('Y-m-d');
                })->map(function($group, $date) {
                    return [
                        'periode' => $date,
                        'total_po' => $group->unique('po_id')->count(),
                        'total_item' => $group->sum('jumlah_po'),
                        'total_nilai' => $group->sum('total_nilai'),
                    ];
                })->values();
            }

            $result = [
                'status' => true,
                'data' => [
                    'po_list' => $poData,
                    'summary' => $summary,
                    'po_by_roti' => $poByRoti,
                    'po_by_status' => $poByStatus,
                    'trend_data' => $trendData,
                ]
            ];

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil laporan purchase order: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Debug method for troubleshooting PO queries
     * Can be removed in production
     */
    public function debugPurchaseOrderApi()
    {
        try {
            // Test simple query with correct column names
            $poData = DB::table('roti_pos')
                ->select('roti_pos.*')
                ->limit(3)
                ->get();
                
            return response()->json([
                'status' => true,
                'message' => 'Debug query successful',
                'data' => [
                    'count' => $poData->count(),
                    'sample' => $poData,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function penjualanReportApi(Request $request)
    {
        $request->validate([
            'periode' => 'required|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
        ]);

        $periode = $request->periode;
        $tanggalMulai = $request->tanggal_mulai;
        $tanggalSelesai = $request->tanggal_selesai;

        // Get user info from token
        $user = Auth::user();
        $userRole = $user->role ?? '';
        $userId = $user->id;        // Query laporan penjualan dengan struktur database yang benar
        $penjualanQuery = DB::table('transaksi')
            ->select(
                'transaksi.id as transaksi_id',
                'transaksi.kode_transaksi',
                'transaksi.nama_customer',
                'transaksi.total_harga',
                'transaksi.metode_pembayaran',
                'transaksi.tanggal_transaksi',
                'transaksi.created_at',
                'transaksi.user_id',
                'users.name as user_name',
                'transaksi_roti.id as item_id',
                'transaksi_roti.jumlah',
                'transaksi_roti.harga_satuan',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                DB::raw('(transaksi_roti.harga_satuan * transaksi_roti.jumlah) as total_nilai_item')
            )
            ->join('users', 'users.id', '=', 'transaksi.user_id')
            ->join('transaksi_roti', 'transaksi_roti.transaksi_id', '=', 'transaksi.id')
            ->join('rotis', 'rotis.id', '=', 'transaksi_roti.roti_id')
            ->whereBetween('transaksi.tanggal_transaksi', [$tanggalMulai, $tanggalSelesai]);

        // Filter berdasarkan role
        if (strtolower($userRole) === 'frontliner') {
            // Frontliner hanya melihat transaksi yang mereka buat sendiri
            $penjualanQuery->where('transaksi.user_id', $userId);
        }
        // Admin dan pimpinan melihat semua data (tidak ada filter tambahan)

        $penjualanData = $penjualanQuery->orderBy('transaksi.created_at', 'desc')->get();

        // Group data by transaksi_id untuk menggabungkan items
        $transaksiGrouped = $penjualanData->groupBy('transaksi_id')->map(function($group) {
            $firstItem = $group->first();
            return [
                'id' => $firstItem->transaksi_id,
                'kode_transaksi' => $firstItem->kode_transaksi ?: ('TRX' . str_pad($firstItem->transaksi_id, 8, '0', STR_PAD_LEFT)),
                'nama_customer' => $firstItem->nama_customer,
                'total_harga' => $firstItem->total_harga,
                'metode_pembayaran' => $firstItem->metode_pembayaran,
                'tanggal_transaksi' => $firstItem->tanggal_transaksi,
                'created_at' => $firstItem->created_at,
                'user' => [
                    'id' => $firstItem->user_id,
                    'name' => $firstItem->user_name
                ],
                'user_id' => $firstItem->user_id,
                'nama_user' => $firstItem->user_name,
                'total_item' => $group->sum('jumlah'),
                'transaksi_roti' => $group->map(function($item) {
                    return [
                        'id' => $item->item_id,
                        'jumlah' => $item->jumlah,
                        'harga_satuan' => $item->harga_satuan,
                        'total_nilai_item' => $item->total_nilai_item,
                        'nama_roti' => $item->nama_roti,
                        'rasa_roti' => $item->rasa_roti,
                        'roti' => [
                            'nama_roti' => $item->nama_roti,
                            'rasa_roti' => $item->rasa_roti,
                        ],
                    ];
                })->toArray(),
            ];
        })->values();

        // Summary data dengan struktur yang benar
        $summary = [
            'total_penjualan' => $penjualanData->sum('total_nilai_item'),
            'total_item_terjual' => $penjualanData->sum('jumlah'),
            'jumlah_transaksi' => $penjualanData->unique('transaksi_id')->count(),
            'rata_rata_per_transaksi' => $penjualanData->unique('transaksi_id')->count() > 0 ? 
                $penjualanData->sum('total_nilai_item') / $penjualanData->unique('transaksi_id')->count() : 0,
            'periode' => $periode,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'user_role' => $userRole,
            'filtered_by_frontliner' => strtolower($userRole) === 'frontliner',
        ];

        // Group by tanggal untuk trend
        $penjualanByDate = $penjualanData->groupBy(function($item) {
            return Carbon::parse($item->tanggal_transaksi)->toDateString();
        })->map(function($group, $date) {
            return [
                'tanggal' => $date,
                'total_penjualan' => $group->sum('total_harga'),
                'total_item' => $group->sum('jumlah'),
                'jumlah_transaksi' => $group->count(),
            ];
        })->values();

        return response()->json([
            'status' => true,
            'data' => [
                'penjualan_list' => $transaksiGrouped,
                'summary' => $summary,
                'penjualan_by_date' => $penjualanByDate,
            ]
        ]);
    }

    public function penjualanTertinggiReportApi(Request $request)
    {
        $request->validate([
            'periode' => 'required|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
        ]);

        $periode = $request->periode;
        $tanggalMulai = $request->tanggal_mulai;
        $tanggalSelesai = $request->tanggal_selesai;

        // Get user info from token
        $user = Auth::user();
        $userRole = $user->role ?? '';
        $userId = $user->id;

        // Query laporan penjualan dengan struktur database yang benar
        $penjualanQuery = DB::table('transaksi')
            ->select(
                'transaksi.id as transaksi_id',
                'transaksi.kode_transaksi',
                'transaksi.nama_customer',
                'transaksi.total_harga',
                'transaksi.metode_pembayaran',
                'transaksi.tanggal_transaksi',
                'transaksi.created_at',
                'transaksi.user_id',
                'users.name as user_name',
                'transaksi_roti.id as item_id',
                'transaksi_roti.jumlah',
                'transaksi_roti.harga_satuan',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                DB::raw('(transaksi_roti.harga_satuan * transaksi_roti.jumlah) as total_nilai_item')
            )
            ->join('users', 'users.id', '=', 'transaksi.user_id')
            ->join('transaksi_roti', 'transaksi_roti.transaksi_id', '=', 'transaksi.id')
            ->join('rotis', 'rotis.id', '=', 'transaksi_roti.roti_id')
            ->whereBetween('transaksi.tanggal_transaksi', [$tanggalMulai, $tanggalSelesai]);

        // Filter berdasarkan role
        if (strtolower($userRole) === 'frontliner') {
            // Frontliner hanya melihat transaksi yang mereka buat sendiri
            $penjualanQuery->where('transaksi.user_id', $userId);
        }

        // Order by total_harga DESC untuk penjualan tertinggi
        $penjualanData = $penjualanQuery->orderBy('transaksi.total_harga', 'desc')->get();

        // Group by transaksi_id untuk mengumpulkan total per transaksi
        $transaksiGrouped = $penjualanData->groupBy('transaksi_id');
        
        // Ambil top 20% atau minimal 5 transaksi dengan nilai tertinggi
        $totalTransaksi = $transaksiGrouped->count();
        $topCount = max(5, ceil($totalTransaksi * 0.2));
        
        // Urutkan transaksi berdasarkan total_harga dan ambil yang tertinggi
        $topTransaksi = $transaksiGrouped->sortByDesc(function($group) {
            return $group->first()->total_harga;
        })->take($topCount);

        // Format data untuk response
        $formattedTransaksi = $topTransaksi->map(function($group) {
            $firstItem = $group->first();
            return [
                'id' => $firstItem->transaksi_id,
                'kode_transaksi' => $firstItem->kode_transaksi ?: ('TRX' . str_pad($firstItem->transaksi_id, 8, '0', STR_PAD_LEFT)),
                'nama_customer' => $firstItem->nama_customer,
                'total_harga' => $firstItem->total_harga,
                'metode_pembayaran' => $firstItem->metode_pembayaran,
                'tanggal_transaksi' => $firstItem->tanggal_transaksi,
                'created_at' => $firstItem->created_at,
                'user' => [
                    'id' => $firstItem->user_id,
                    'name' => $firstItem->user_name
                ],
                'user_id' => $firstItem->user_id,
                'nama_user' => $firstItem->user_name,
                'total_item' => $group->sum('jumlah'),
                'transaksi_roti' => $group->map(function($item) {
                    return [
                        'id' => $item->item_id,
                        'jumlah' => $item->jumlah,
                        'harga_satuan' => $item->harga_satuan,
                        'total_nilai_item' => $item->total_nilai_item,
                        'nama_roti' => $item->nama_roti,
                        'rasa_roti' => $item->rasa_roti,
                        'roti' => [
                            'nama_roti' => $item->nama_roti,
                            'rasa_roti' => $item->rasa_roti,
                        ],
                    ];
                })->toArray(),
            ];
        })->values();

        // Hitung summary untuk transaksi tertinggi
        $topTransaksiFlat = $topTransaksi->flatten(1);
        $summary = [
            'total_penjualan' => $topTransaksiFlat->sum('total_nilai_item'),
            'total_item_terjual' => $topTransaksiFlat->sum('jumlah'),
            'jumlah_transaksi' => $topTransaksi->count(),
            'rata_rata_per_transaksi' => $topTransaksi->count() > 0 ? 
                $topTransaksiFlat->sum('total_nilai_item') / $topTransaksi->count() : 0,
            'periode' => $periode,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'user_role' => $userRole,
            'filtered_by_frontliner' => strtolower($userRole) === 'frontliner',
            'type' => 'penjualan_tertinggi',
            'top_count' => $topCount,
            'total_all_transactions' => $totalTransaksi,
        ];

        return response()->json([
            'status' => true,
            'data' => [
                'penjualan_list' => $formattedTransaksi,
                'summary' => $summary,
            ]
        ]);
    }

    public function penjualanTerendahReportApi(Request $request)
    {
        $request->validate([
            'periode' => 'required|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
        ]);

        $periode = $request->periode;
        $tanggalMulai = $request->tanggal_mulai;
        $tanggalSelesai = $request->tanggal_selesai;

        // Get user info from token
        $user = Auth::user();
        $userRole = $user->role ?? '';
        $userId = $user->id;

        // Query laporan penjualan dengan struktur database yang benar
        $penjualanQuery = DB::table('transaksi')
            ->select(
                'transaksi.id as transaksi_id',
                'transaksi.kode_transaksi',
                'transaksi.nama_customer',
                'transaksi.total_harga',
                'transaksi.metode_pembayaran',
                'transaksi.tanggal_transaksi',
                'transaksi.created_at',
                'transaksi.user_id',
                'users.name as user_name',
                'transaksi_roti.id as item_id',
                'transaksi_roti.jumlah',
                'transaksi_roti.harga_satuan',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                DB::raw('(transaksi_roti.harga_satuan * transaksi_roti.jumlah) as total_nilai_item')
            )
            ->join('users', 'users.id', '=', 'transaksi.user_id')
            ->join('transaksi_roti', 'transaksi_roti.transaksi_id', '=', 'transaksi.id')
            ->join('rotis', 'rotis.id', '=', 'transaksi_roti.roti_id')
            ->whereBetween('transaksi.tanggal_transaksi', [$tanggalMulai, $tanggalSelesai]);

        // Filter berdasarkan role
        if (strtolower($userRole) === 'frontliner') {
            // Frontliner hanya melihat transaksi yang mereka buat sendiri
            $penjualanQuery->where('transaksi.user_id', $userId);
        }

        // Order by total_harga ASC untuk penjualan terendah
        $penjualanData = $penjualanQuery->orderBy('transaksi.total_harga', 'asc')->get();

        // Group by transaksi_id untuk mengumpulkan total per transaksi
        $transaksiGrouped = $penjualanData->groupBy('transaksi_id');
        
        // Ambil bottom 20% atau minimal 5 transaksi dengan nilai terendah
        $totalTransaksi = $transaksiGrouped->count();
        $bottomCount = max(5, ceil($totalTransaksi * 0.2));
        
        // Urutkan transaksi berdasarkan total_harga dan ambil yang terendah
        $bottomTransaksi = $transaksiGrouped->sortBy(function($group) {
            return $group->first()->total_harga;
        })->take($bottomCount);

        // Format data untuk response
        $formattedTransaksi = $bottomTransaksi->map(function($group) {
            $firstItem = $group->first();
            return [
                'id' => $firstItem->transaksi_id,
                'kode_transaksi' => $firstItem->kode_transaksi ?: ('TRX' . str_pad($firstItem->transaksi_id, 8, '0', STR_PAD_LEFT)),
                'nama_customer' => $firstItem->nama_customer,
                'total_harga' => $firstItem->total_harga,
                'metode_pembayaran' => $firstItem->metode_pembayaran,
                'tanggal_transaksi' => $firstItem->tanggal_transaksi,
                'created_at' => $firstItem->created_at,
                'user' => [
                    'id' => $firstItem->user_id,
                    'name' => $firstItem->user_name
                ],
                'user_id' => $firstItem->user_id,
                'nama_user' => $firstItem->user_name,
                'total_item' => $group->sum('jumlah'),
                'transaksi_roti' => $group->map(function($item) {
                    return [
                        'id' => $item->item_id,
                        'jumlah' => $item->jumlah,
                        'harga_satuan' => $item->harga_satuan,
                        'total_nilai_item' => $item->total_nilai_item,
                        'nama_roti' => $item->nama_roti,
                        'rasa_roti' => $item->rasa_roti,
                        'roti' => [
                            'nama_roti' => $item->nama_roti,
                            'rasa_roti' => $item->rasa_roti,
                        ],
                    ];
                })->toArray(),
            ];
        })->values();

        // Hitung summary untuk transaksi terendah
        $bottomTransaksiFlat = $bottomTransaksi->flatten(1);
        $summary = [
            'total_penjualan' => $bottomTransaksiFlat->sum('total_nilai_item'),
            'total_item_terjual' => $bottomTransaksiFlat->sum('jumlah'),
            'jumlah_transaksi' => $bottomTransaksi->count(),
            'rata_rata_per_transaksi' => $bottomTransaksi->count() > 0 ? 
                $bottomTransaksiFlat->sum('total_nilai_item') / $bottomTransaksi->count() : 0,
            'periode' => $periode,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'user_role' => $userRole,
            'filtered_by_frontliner' => strtolower($userRole) === 'frontliner',
            'type' => 'penjualan_terendah',
            'bottom_count' => $bottomCount,
            'total_all_transactions' => $totalTransaksi,
        ];

        return response()->json([
            'status' => true,
            'data' => [
                'penjualan_list' => $formattedTransaksi,
                'summary' => $summary,
            ]
        ]);
    }

    public function dashboardStatsApi()
    {
        // Get user info from token
        $user = Auth::user();
        $userRole = $user->role ?? '';
        $userId = $user->id;

        $today = Carbon::today()->toDateString();
        $thisWeek = [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()];
        $thisMonth = [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->toDateString()];

        // Waste stats dengan filtering berdasarkan role
        $wasteToday = DB::table('wastes')
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->where('stok_history.tanggal', $today)
            ->where('wastes.status', '!=', 9)
            ->when(strtolower($userRole) === 'frontliner', function($query) use ($userId) {
                return $query->where('stok_history.frontliner_id', $userId);
            })
            ->when(strtolower($userRole) === 'kepalatokokios', function($query) use ($user) {
                if ($user->kepalatokokios_id) {
                    return $query->where('stok_history.kepalatokokios_id', $user->kepalatokokios_id);
                }
                return $query;
            })
            ->sum('wastes.jumlah_waste');

        $wasteThisWeek = DB::table('wastes')
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->whereBetween('stok_history.tanggal', $thisWeek)
            ->where('wastes.status', '!=', 9)
            ->when(strtolower($userRole) === 'frontliner', function($query) use ($userId) {
                return $query->where('stok_history.frontliner_id', $userId);
            })
            ->when(strtolower($userRole) === 'kepalatokokios', function($query) use ($user) {
                if ($user->kepalatokokios_id) {
                    return $query->where('stok_history.kepalatokokios_id', $user->kepalatokokios_id);
                }
                return $query;
            })
            ->sum('wastes.jumlah_waste');

        $wasteThisMonth = DB::table('wastes')
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->whereBetween('stok_history.tanggal', $thisMonth)
            ->where('wastes.status', '!=', 9)
            ->when(strtolower($userRole) === 'frontliner', function($query) use ($userId) {
                return $query->where('stok_history.frontliner_id', $userId);
            })
            ->when(strtolower($userRole) === 'kepalatokokios', function($query) use ($user) {
                if ($user->kepalatokokios_id) {
                    return $query->where('stok_history.kepalatokokios_id', $user->kepalatokokios_id);
                }
                return $query;
            })
            ->sum('wastes.jumlah_waste');

        // PO stats dengan filtering berdasarkan role
        $poToday = DB::table('roti_pos')
            ->where('tanggal_order', $today)
            ->where('status', '!=', 9)
            ->when(strtolower($userRole) === 'frontliner', function($query) use ($userId) {
                return $query->where('frontliner_id', $userId);
            })
            ->when(strtolower($userRole) === 'kepalatokokios', function($query) use ($user) {
                if ($user->kepalatokokios_id) {
                    return $query->where('kepalatokokios_id', $user->kepalatokokios_id);
                }
                return $query;
            })
            ->count();

        $poThisWeek = DB::table('roti_pos')
            ->whereBetween('tanggal_order', $thisWeek)
            ->where('status', '!=', 9)
            ->when(strtolower($userRole) === 'frontliner', function($query) use ($userId) {
                return $query->where('frontliner_id', $userId);
            })
            ->when(strtolower($userRole) === 'kepalatokokios', function($query) use ($user) {
                if ($user->kepalatokokios_id) {
                    return $query->where('kepalatokokios_id', $user->kepalatokokios_id);
                }
                return $query;
            })
            ->count();

        $poThisMonth = DB::table('roti_pos')
            ->whereBetween('tanggal_order', $thisMonth)
            ->where('status', '!=', 9)
            ->when(strtolower($userRole) === 'frontliner', function($query) use ($userId) {
                return $query->where('frontliner_id', $userId);
            })
            ->when(strtolower($userRole) === 'kepalatokokios', function($query) use ($user) {
                if ($user->kepalatokokios_id) {
                    return $query->where('kepalatokokios_id', $user->kepalatokokios_id);
                }
                return $query;
            })
            ->count();

        return response()->json([
            'status' => true,
            'data' => [
                'waste_stats' => [
                    'today' => $wasteToday,
                    'this_week' => $wasteThisWeek,
                    'this_month' => $wasteThisMonth,
                ],
                'po_stats' => [
                    'today' => $poToday,
                    'this_week' => $poThisWeek,
                    'this_month' => $poThisMonth,
                ],
            ]
        ]);
    }

    public function penjualanPdfExport(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|string',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
                'token' => 'nullable|string', // Token dari query parameter
            ]);

            // Jika token ada di query parameter, set ke header
            $token = $request->get('token');
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }

            $periode = $request->periode;
            $tanggalMulai = $request->tanggal_mulai;
            $tanggalSelesai = $request->tanggal_selesai;

            // Debug log input
            \Log::info('PDF Export Input', [
                'periode' => $periode,
                'tanggal_mulai_input' => $tanggalMulai,
                'tanggal_selesai_input' => $tanggalSelesai,
            ]);

            // Debug log after default
            \Log::info('PDF Export Tanggal Final', [
                'tanggal_mulai_final' => $tanggalMulai,
                'tanggal_selesai_final' => $tanggalSelesai,
            ]);

            // Query laporan penjualan dengan struktur database yang benar
            $penjualanQuery = DB::table('transaksi')
                ->select(
                    'transaksi.id as transaksi_id',
                    'transaksi.kode_transaksi',
                    'transaksi.nama_customer',
                    'transaksi.total_harga',
                    'transaksi.metode_pembayaran',
                    'transaksi.tanggal_transaksi',
                    'transaksi.created_at',
                    'users.name as user_name',
                    'transaksi_roti.id as item_id',
                    'transaksi_roti.jumlah',
                    'transaksi_roti.harga_satuan',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    DB::raw('(transaksi_roti.harga_satuan * transaksi_roti.jumlah) as total_nilai_item')
                )
                ->join('users', 'users.id', '=', 'transaksi.user_id')
                ->join('transaksi_roti', 'transaksi_roti.transaksi_id', '=', 'transaksi.id')
                ->join('rotis', 'rotis.id', '=', 'transaksi_roti.roti_id');

            // Filter berdasarkan periode tanggal - FIXED: handle datetime properly
            switch ($periode) {
                case 'harian':
                    $penjualanQuery->whereDate('transaksi.tanggal_transaksi', '>=', $tanggalMulai)
                                  ->whereDate('transaksi.tanggal_transaksi', '<=', $tanggalSelesai);
                    break;
                case 'mingguan':
                case 'bulanan':
                case 'tahunan':
                    // Use datetime range to handle time component
                    $penjualanQuery->where('transaksi.tanggal_transaksi', '>=', $tanggalMulai . ' 00:00:00')
                                  ->where('transaksi.tanggal_transaksi', '<=', $tanggalSelesai . ' 23:59:59');
                    break;
            }

            // Debug query sebelum execute
            \Log::info('PDF Export Query', [
                'sql' => $penjualanQuery->toSql(),
                'bindings' => $penjualanQuery->getBindings(),
            ]);

            // Filter berdasarkan role user yang login
            $user = Auth::user();
            
            // Jika user null, coba authenticate via token di query parameter
            if (!$user && $token) {
                try {
                    // Manually authenticate using Sanctum token
                    $tokenModel = PersonalAccessToken::findToken($token);
                    if ($tokenModel) {
                        $user = $tokenModel->tokenable;
                        Auth::setUser($user);
                    }
                } catch (\Exception $e) {
                    // Token invalid, user tetap null
                }
            }
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak terautentikasi untuk PDF export'
                ], 401);
            }
            
            $userRole = $user->role;
            $userId = $user->id;

            if (strtolower($userRole) === 'frontliner') {
                // Frontliner hanya melihat transaksi yang mereka buat sendiri
                $penjualanQuery->where('transaksi.user_id', $userId);
            }
            // Admin dan pimpinan melihat semua data (tidak ada filter tambahan)

            $penjualanData = $penjualanQuery->orderBy('transaksi.created_at', 'desc')->get();

            // Debug hasil query
            \Log::info('PDF Export Raw Data', [
                'total_raw_records' => $penjualanData->count(),
                'user_role' => $userRole,
                'user_id' => $userId,
                'sample_data' => $penjualanData->take(2)->toArray(),
            ]);

            // Group data by transaksi_id untuk menggabungkan items (sama seperti API)
            $transaksiGrouped = $penjualanData->groupBy('transaksi_id')->map(function($group) {
                $firstItem = $group->first();
                return (object)[
                    'id' => $firstItem->transaksi_id,
                    'kode_transaksi' => $firstItem->kode_transaksi ?: ('TRX' . str_pad($firstItem->transaksi_id, 8, '0', STR_PAD_LEFT)),
                    'nama_customer' => $firstItem->nama_customer,
                    'total_harga' => $firstItem->total_harga,
                    'metode_pembayaran' => $firstItem->metode_pembayaran,
                    'tanggal_transaksi' => $firstItem->tanggal_transaksi,
                    'created_at' => $firstItem->created_at,
                    'user_name' => $firstItem->user_name,
                    'total_item' => $group->sum('jumlah'),
                    'transaksi_roti' => $group->map(function($item) {
                        return (object)[
                            'id' => $item->item_id,
                            'jumlah' => $item->jumlah,
                            'harga_satuan' => $item->harga_satuan,
                            'nama_roti' => $item->nama_roti,
                            'rasa_roti' => $item->rasa_roti,
                        ];
                    })->toArray(),
                ];
            })->values();

            // Debug log untuk melihat data
            \Log::info('Penjualan PDF Data Final', [
                'periode' => $periode,
                'tanggal_range' => $tanggalMulai . ' - ' . $tanggalSelesai,
                'total_raw_data' => $penjualanData->count(),
                'total_grouped' => $transaksiGrouped->count(),
                'first_grouped_item' => $transaksiGrouped->first(),
                'total_penjualan' => $transaksiGrouped->sum('total_harga'),
                'query_has_data' => $penjualanData->count() > 0,
                'grouped_has_data' => $transaksiGrouped->count() > 0,
            ]);

            // Summary data dengan struktur yang benar
            $summary = [
                'total_penjualan' => $transaksiGrouped->sum('total_harga'),
                'total_item_terjual' => $transaksiGrouped->sum('total_item'),
                'jumlah_transaksi' => $transaksiGrouped->count(),
                'rata_rata_per_transaksi' => $transaksiGrouped->count() > 0 ? 
                    $transaksiGrouped->sum('total_harga') / $transaksiGrouped->count() : 0,
                'periode' => $periode,
                'periode_text' => $this->getPeriodeText($periode, $tanggalMulai, $tanggalSelesai),
                'tanggal_mulai' => Carbon::parse($tanggalMulai)->format('d/m/Y'),
                'tanggal_selesai' => Carbon::parse($tanggalSelesai)->format('d/m/Y'),
            ];

            // Generate PDF
            $pdf = Pdf::loadView('reports.penjualan_pdf', [
                'penjualan_list' => $transaksiGrouped,
                'summary' => $summary,
            ]);

            // Set paper size dan orientasi
            $pdf->setPaper('A4', 'portrait');

            // Generate filename
            $filename = 'laporan-penjualan-' . $periode . '-' . date('Y-m-d-H-i-s') . '.pdf';

            // Return PDF as download
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal generate PDF: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    private function getPeriodeText($periode, $tanggalMulai, $tanggalSelesai)
    {
        $mulai = Carbon::parse($tanggalMulai);
        $selesai = Carbon::parse($tanggalSelesai);

        switch ($periode) {
            case 'harian':
                return 'Laporan Harian - ' . $mulai->format('d F Y');
            case 'mingguan':
                return 'Laporan Mingguan - ' . $mulai->format('d F Y') . ' s.d ' . $selesai->format('d F Y');
            case 'bulanan':
                return 'Laporan Bulanan - ' . $mulai->format('F Y');
            default:
                return 'Laporan Penjualan';
        }
    }

    public function wastePdfExport(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|string',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
                'token' => 'nullable|string',
            ]);

            // Jika token ada di query parameter, set ke header
            $token = $request->get('token');
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }

            $periode = $request->periode;
            $tanggalMulai = $request->tanggal_mulai;
            $tanggalSelesai = $request->tanggal_selesai;

            // Query laporan waste dengan struktur database yang benar
            $wasteQuery = DB::table('wastes')
                ->select(
                    'wastes.id',
                    'wastes.kode_waste',
                    'wastes.jumlah_waste',
                    'wastes.tanggal_expired',
                    'wastes.keterangan',
                    'wastes.created_at',
                    'users.name as user_name',
                    'users.role as user_role',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    'rotis.harga_roti',
                    'stok_history.tanggal as tanggal_stok',
                    'stok_history.kepalatokokios_id',
                    DB::raw('COALESCE(rotis.harga_roti * wastes.jumlah_waste, 0) as total_kerugian')
                )
                ->join('users', 'users.id', '=', 'wastes.user_id')
                ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
                ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
                ->where('wastes.status', '!=', 9);

            // Filter berdasarkan periode tanggal
            switch ($periode) {
                case 'harian':
                    $wasteQuery->whereDate('wastes.created_at', '>=', $tanggalMulai)
                              ->whereDate('wastes.created_at', '<=', $tanggalSelesai);
                    break;
                case 'mingguan':
                case 'bulanan':
                case 'tahunan':
                    $wasteQuery->whereBetween('wastes.created_at', [
                        $tanggalMulai . ' 00:00:00', 
                        $tanggalSelesai . ' 23:59:59'
                    ]);
                    break;
            }

            // Filter berdasarkan role user yang login
            $user = Auth::user();
            
            // Jika user null, coba authenticate via token di query parameter
            if (!$user && $token) {
                try {
                    // Manually authenticate using Sanctum token
                    $tokenModel = PersonalAccessToken::findToken($token);
                    if ($tokenModel) {
                        $user = $tokenModel->tokenable;
                        Auth::setUser($user);
                    }
                } catch (\Exception $e) {
                    // Token invalid, user tetap null
                }
            }
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak terautentikasi untuk PDF export'
                ], 401);
            }
            
            $userRole = $user->role;
            $userId = $user->id;

            if (strtolower($userRole) === 'frontliner') {
                // Frontliner hanya melihat data yang mereka buat sendiri
                $wasteQuery->where('wastes.user_id', $userId);
            } elseif (strtolower($userRole) === 'kepalatokokios') {
                // Kepala toko kios melihat data mereka + data dari stok dengan kepalatokokios_id yang sama
                $wasteQuery->where(function($query) use ($user, $userId) {
                    // Data yang dibuat oleh kepala toko kios sendiri
                    $query->where('wastes.user_id', $userId);
                    
                    // Atau data dari stok_history yang sesuai dengan kepalatokokios_id
                    if (isset($user->kepalatokokios_id) && $user->kepalatokokios_id) {
                        $query->orWhere('stok_history.kepalatokokios_id', $user->kepalatokokios_id);
                    }
                });
            }
            // Admin dan pimpinan melihat semua data (tidak ada filter tambahan)

            $wasteData = $wasteQuery->orderBy('wastes.created_at', 'desc')->get();

            // Summary data
            $summary = [
                'total_item_waste' => $wasteData->sum('jumlah_waste'),
                'total_kerugian' => $wasteData->sum('total_kerugian'),
                'jumlah_transaksi' => $wasteData->count(),
                'periode' => $periode,
                'periode_text' => $this->getPeriodeText($periode, $tanggalMulai, $tanggalSelesai),
                'tanggal_mulai' => Carbon::parse($tanggalMulai)->format('d/m/Y'),
                'tanggal_selesai' => Carbon::parse($tanggalSelesai)->format('d/m/Y'),
            ];

            // Generate PDF
            $pdf = Pdf::loadView('reports.waste_pdf', [
                'waste_list' => $wasteData,
                'summary' => $summary,
            ]);

            // Set paper size dan orientasi
            $pdf->setPaper('A4', 'landscape'); // Landscape untuk tabel yang lebih lebar

            // Generate filename
            $filename = 'laporan-waste-' . $periode . '-' . date('Y-m-d-H-i-s') . '.pdf';

            // Return PDF as download
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal generate PDF Waste: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function purchaseOrderPdfExport(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|string',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
                'token' => 'nullable|string',
            ]);

            // Jika token ada di query parameter, set ke header
            $token = $request->get('token');
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }

            $periode = $request->periode;
            $tanggalMulai = $request->tanggal_mulai;
            $tanggalSelesai = $request->tanggal_selesai;

            // Query laporan purchase order dengan struktur yang benar
            $poQuery = DB::table('pos')
                ->select(
                    'pos.id as pos_id',
                    'pos.kode_po',
                    'pos.tanggal_order', 
                    'pos.status as po_status',
                    'pos.deskripsi',
                    'pos.created_at',
                    'users.name as user_name',
                    'roti_pos.id as item_id',
                    'roti_pos.jumlah_po',
                    'rotis.nama_roti',
                    'rotis.rasa_roti', 
                    'rotis.harga_roti',
                    DB::raw('(rotis.harga_roti * roti_pos.jumlah_po) as total_nilai')
                )
                ->join('users', 'users.id', '=', 'pos.user_id')
                ->join('roti_pos', 'roti_pos.pos_id', '=', 'pos.id')
                ->join('rotis', 'rotis.id', '=', 'roti_pos.roti_id')
                ->where('pos.status', '!=', 9);

            // Filter berdasarkan periode tanggal
            switch ($periode) {
                case 'harian':
                    $poQuery->whereDate('pos.tanggal_order', '>=', $tanggalMulai)
                           ->whereDate('pos.tanggal_order', '<=', $tanggalSelesai);
                    break;
                case 'mingguan':
                case 'bulanan':
                    $poQuery->whereBetween('pos.tanggal_order', [
                        $tanggalMulai . ' 00:00:00', 
                        $tanggalSelesai . ' 23:59:59'
                    ]);
                    break;
            }

            // Filter berdasarkan role user yang login
            $user = Auth::user();
            
            // Jika user null, coba authenticate via token di query parameter
            if (!$user && $token) {
                try {
                    // Manually authenticate using Sanctum token
                    $tokenModel = PersonalAccessToken::findToken($token);
                    if ($tokenModel) {
                        $user = $tokenModel->tokenable;
                        Auth::setUser($user);
                    }
                } catch (\Exception $e) {
                    // Token invalid, user tetap null
                }
            }
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak terautentikasi untuk PDF export'
                ], 401);
            }
            
            $userRole = $user->role;
            $userId = $user->id;

            if (strtolower($userRole) === 'frontliner') {
                // Frontliner hanya melihat PO yang mereka buat sendiri
                $poQuery->where('pos.user_id', $userId);
            } elseif (strtolower($userRole) === 'kepalatokokios') {
                // Kepala toko kios melihat PO yang mereka buat sendiri
                $poQuery->where('pos.user_id', $userId);
            }
            // Admin, pimpinan, bakery melihat semua data

            $poData = $poQuery->orderBy('pos.created_at', 'desc')->get();

            // Summary data
            $summary = [
                'total_item_po' => $poData->sum('jumlah_po'),
                'total_nilai' => $poData->sum('total_nilai'),
                'jumlah_po' => $poData->count(),
                'po_pending' => $poData->where('po_status', 0)->count(),
                'po_delivery' => $poData->where('po_status', 1)->count(),
                'po_selesai' => $poData->where('po_status', 2)->count(),
                'periode' => $periode,
                'periode_text' => $this->getPeriodeText($periode, $tanggalMulai, $tanggalSelesai),
                'tanggal_mulai' => Carbon::parse($tanggalMulai)->format('d/m/Y'),
                'tanggal_selesai' => Carbon::parse($tanggalSelesai)->format('d/m/Y'),
            ];

            // Generate PDF
            $pdf = PDF::loadView('reports.purchase_order_pdf', [
                'po_list' => $poData,
                'summary' => $summary,
            ]);

            // Set paper size dan orientasi
            $pdf->setPaper('A4', 'landscape'); // Landscape untuk tabel yang lebih lebar

            // Generate filename
            $filename = 'laporan-purchase-order-' . $periode . '-' . date('Y-m-d-H-i-s') . '.pdf';

            // Return PDF as download
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal generate PDF Purchase Order: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function stokReportApi(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|string',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
            ]);

            $periode = $request->periode;
            $tanggalMulai = $request->tanggal_mulai;
            $tanggalSelesai = $request->tanggal_selesai;

            // Get user info from token
            $user = Auth::user();
            $userRole = $user->role ?? '';
            $userId = $user->id;

            // Query stok history dengan filtering berdasarkan role
            $stokQuery = DB::table('stok_history')
                ->select(
                    'stok_history.id',
                    'stok_history.roti_id',
                    'stok_history.stok',
                    'stok_history.stok_awal',
                    'stok_history.tanggal',
                    'stok_history.keterangan',
                    'stok_history.created_at',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    'rotis.harga_roti',
                    'frontliner.name as frontliner_name',
                    'kepalatokokios.name as kepalatokokios_name',
                    DB::raw('(stok_history.stok * rotis.harga_roti) as nilai_stok'),
                    DB::raw('(stok_history.stok_awal - stok_history.stok) as stok_terpakai')
                )
                ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
                ->leftJoin('users as frontliner', 'frontliner.id', '=', 'stok_history.frontliner_id')
                ->leftJoin('users as kepalatokokios', 'kepalatokokios.id', '=', 'stok_history.kepalatokokios_id')
                ->whereBetween('stok_history.tanggal', [$tanggalMulai, $tanggalSelesai]);

            // Filter berdasarkan role
            if (strtolower($userRole) === 'frontliner') {
                // Frontliner hanya melihat stok mereka sendiri
                $stokQuery->where('stok_history.frontliner_id', $userId);
            } elseif (strtolower($userRole) === 'kepalatokokios') {
                // Kepala toko kios hanya melihat stok dari toko kios mereka
                if ($user->kepalatokokios_id) {
                    $stokQuery->where('stok_history.kepalatokokios_id', $user->kepalatokokios_id);
                }
            }
            // Admin, pimpinan, bakery melihat semua data

            $stokData = $stokQuery->orderBy('stok_history.created_at', 'desc')->get();

            // Summary data
            $summary = [
                'total_nilai_stok' => $stokData->sum('nilai_stok'),
                'total_stok_tersedia' => $stokData->sum('stok'),
                'total_stok_terpakai' => $stokData->sum('stok_terpakai'),
                'jumlah_record' => $stokData->count(),
                'periode' => $periode,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'user_role' => $userRole,
                'filtered_by_frontliner' => strtolower($userRole) === 'frontliner',
            ];

            // Group by roti untuk statistik
            $stokByRoti = $stokData->groupBy(function($item) {
                return $item->nama_roti . ' - ' . $item->rasa_roti;
            })->map(function($group) {
                return [
                    'nama_roti' => $group->first()->nama_roti,
                    'rasa_roti' => $group->first()->rasa_roti,
                    'total_stok' => $group->sum('stok'),
                    'total_nilai' => $group->sum('nilai_stok'),
                    'total_terpakai' => $group->sum('stok_terpakai'),
                    'frekuensi' => $group->count(),
                ];
            })->values();

            // Group by tanggal untuk trend
            $stokByDate = $stokData->groupBy(function($item) {
                return Carbon::parse($item->tanggal)->toDateString();
            })->map(function($group, $date) {
                return [
                    'tanggal' => $date,
                    'total_stok' => $group->sum('stok'),
                    'total_nilai' => $group->sum('nilai_stok'),
                    'total_terpakai' => $group->sum('stok_terpakai'),
                    'jumlah_record' => $group->count(),
                ];
            })->values();

            return response()->json([
                'status' => true,
                'data' => [
                    'stok_list' => $stokData,
                    'summary' => $summary,
                    'stok_by_roti' => $stokByRoti,
                    'stok_by_date' => $stokByDate,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function stokPdfExport(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|string',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
                'token' => 'nullable|string',
            ]);

            // Jika token ada di query parameter, set ke header
            $token = $request->get('token');
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }

            $periode = $request->periode;
            $tanggalMulai = $request->tanggal_mulai;
            $tanggalSelesai = $request->tanggal_selesai;

            // Query laporan stok
            $stokQuery = DB::table('stok_history')
                ->select(
                    'stok_history.id',
                    'stok_history.roti_id',
                    'stok_history.stok',
                    'stok_history.stok_awal',
                    'stok_history.tanggal',
                    'stok_history.keterangan',
                    'stok_history.created_at',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    'rotis.harga_roti',
                    'frontliner.name as frontliner_name',
                    'kepalatokokios.name as kepalatokokios_name',
                    DB::raw('(stok_history.stok * rotis.harga_roti) as nilai_stok'),
                    DB::raw('(stok_history.stok_awal - stok_history.stok) as stok_terpakai')
                )
                ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
                ->leftJoin('users as frontliner', 'frontliner.id', '=', 'stok_history.frontliner_id')
                ->leftJoin('users as kepalatokokios', 'kepalatokokios.id', '=', 'stok_history.kepalatokokios_id')
                ->whereBetween('stok_history.tanggal', [$tanggalMulai, $tanggalSelesai]);

            // Filter berdasarkan role
            $user = Auth::user();
            $userRole = $user->role;
            $userId = $user->id;

            if (strtolower($userRole) === 'frontliner') {
                // Frontliner hanya melihat stok mereka sendiri
                $stokQuery->where('stok_history.frontliner_id', $userId);
            } elseif (strtolower($userRole) === 'kepalatokokios') {
                // Kepala toko kios hanya melihat stok dari toko kios mereka
                if ($user->kepalatokokios_id) {
                    $stokQuery->where('stok_history.kepalatokokios_id', $user->kepalatokokios_id);
                }
            }
            // Admin, pimpinan, bakery melihat semua data

            $stokData = $stokQuery->orderBy('stok_history.created_at', 'desc')->get();

            // Summary data
            $summary = [
                'total_nilai_stok' => $stokData->sum('nilai_stok'),
                'total_stok_tersedia' => $stokData->sum('stok'),
                'total_stok_terpakai' => $stokData->sum('stok_terpakai'),
                'jumlah_record' => $stokData->count(),
                'periode' => $periode,
                'periode_text' => $this->getPeriodeText($periode, $tanggalMulai, $tanggalSelesai),
                'tanggal_mulai' => Carbon::parse($tanggalMulai)->format('d/m/Y'),
                'tanggal_selesai' => Carbon::parse($tanggalSelesai)->format('d/m/Y'),
            ];

            // Generate PDF
            $pdf = Pdf::loadView('reports.stok_pdf', [
                'stok_list' => $stokData,
                'summary' => $summary,
            ]);

            // Set paper size dan orientasi
            $pdf->setPaper('A4', 'landscape'); // Landscape untuk tabel yang lebih lebar

            // Generate filename
            $filename = 'laporan-stok-' . $periode . '-' . date('Y-m-d-H-i-s') . '.pdf';

            // Return PDF as download
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal generate PDF Stok: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function penjualanTertinggiPdf(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|string',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
                'token' => 'nullable|string', // Token dari query parameter
            ]);

            // Jika token ada di query parameter, set ke header
            $token = $request->get('token');
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }

            $periode = $request->periode;
            $tanggalMulai = $request->tanggal_mulai;
            $tanggalSelesai = $request->tanggal_selesai;

            // Debug log input
            \Log::info('PDF Export Input', [
                'periode' => $periode,
                'tanggal_mulai_input' => $tanggalMulai,
                'tanggal_selesai_input' => $tanggalSelesai,
            ]);

            // Debug log after default
            \Log::info('PDF Export Tanggal Final', [
                'tanggal_mulai_final' => $tanggalMulai,
                'tanggal_selesai_final' => $tanggalSelesai,
            ]);

            // Query laporan penjualan dengan struktur database yang benar
            $penjualanQuery = DB::table('transaksi')
                ->select(
                    'transaksi.id as transaksi_id',
                    'transaksi.kode_transaksi',
                    'transaksi.nama_customer',
                    'transaksi.total_harga',
                    'transaksi.metode_pembayaran',
                    'transaksi.tanggal_transaksi',
                    'transaksi.created_at',
                    'users.name as user_name',
                    'transaksi_roti.id as item_id',
                    'transaksi_roti.jumlah',
                    'transaksi_roti.harga_satuan',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    DB::raw('(transaksi_roti.harga_satuan * transaksi_roti.jumlah) as total_nilai_item')
                )
                ->join('users', 'users.id', '=', 'transaksi.user_id')
                ->join('transaksi_roti', 'transaksi_roti.transaksi_id', '=', 'transaksi.id')
                ->join('rotis', 'rotis.id', '=', 'transaksi_roti.roti_id');

            // Filter berdasarkan periode tanggal - FIXED: handle datetime properly
            switch ($periode) {
                case 'harian':
                    $penjualanQuery->whereDate('transaksi.tanggal_transaksi', '>=', $tanggalMulai)
                        ->whereDate('transaksi.tanggal_transaksi', '<=', $tanggalSelesai);
                    break;
                case 'mingguan':
                case 'bulanan':
                case 'tahunan':
                    // Use datetime range to handle time component
                    $penjualanQuery->where('transaksi.tanggal_transaksi', '>=', $tanggalMulai . ' 00:00:00')
                        ->where('transaksi.tanggal_transaksi', '<=', $tanggalSelesai . ' 23:59:59');
                    break;
            }

            // Debug query sebelum execute
            \Log::info('PDF Export Query', [
                'sql' => $penjualanQuery->toSql(),
                'bindings' => $penjualanQuery->getBindings(),
            ]);

            // Filter berdasarkan role user yang login
            $user = Auth::user();

            // Jika user null, coba authenticate via token di query parameter
            if (!$user && $token) {
                try {
                    // Manually authenticate using Sanctum token
                    $tokenModel = PersonalAccessToken::findToken($token);
                    if ($tokenModel) {
                        $user = $tokenModel->tokenable;
                        Auth::setUser($user);
                    }
                } catch (\Exception $e) {
                    // Token invalid, user tetap null
                }
            }

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak terautentikasi untuk PDF export'
                ], 401);
            }

            $userRole = $user->role;
            $userId = $user->id;

            if (strtolower($userRole) === 'frontliner') {
                // Frontliner hanya melihat transaksi yang mereka buat sendiri
                $penjualanQuery->where('transaksi.user_id', $userId);
            }
            // Admin dan pimpinan melihat semua data (tidak ada filter tambahan)

            $penjualanData = $penjualanQuery->orderBy('transaksi.created_at', 'desc')->get();

            // Debug hasil query
            \Log::info('PDF Export Raw Data', [
                'total_raw_records' => $penjualanData->count(),
                'user_role' => $userRole,
                'user_id' => $userId,
                'sample_data' => $penjualanData->take(2)->toArray(),
            ]);


            // Group data per hari per user (konsisten dengan laporan tertinggi/terendah)
            $grouped = $penjualanData->groupBy(function($item) {
                return $item->user_name . '_' . Carbon::parse($item->tanggal_transaksi)->format('Y-m-d');
            })->map(function($group) {
                $firstItem = $group->first();
                $totalHarga = $group->sum('total_harga');
                $totalItem = $group->sum('jumlah');
                $jumlahTransaksi = $group->count();
                return [
                    'tanggal_transaksi' => Carbon::parse($firstItem->tanggal_transaksi)->format('Y-m-d'),
                    'nama_kasir' => $firstItem->user_name,
                    'jumlah_transaksi' => $jumlahTransaksi,
                    'total_item' => $totalItem,
                    'total_harga' => $totalHarga,
                    'rata_rata_transaksi' => $jumlahTransaksi > 0 ? $totalHarga / $jumlahTransaksi : 0,
                ];
            })->sortByDesc('total_harga')->values();

            // Filter minimum 5 transaksi
            $filtered = $grouped->filter(function($item) {
                return $item['jumlah_transaksi'] >= 5;
            })->values();

            if ($filtered->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data dengan minimum 5 transaksi'
                ], 404);
            }

            // Ambil 20% teratas
            $count = $filtered->count();
            $topCount = max(1, intval($count * 0.2));
            $penjualanTertinggi = $filtered->take($topCount)->values();

            // Summary data konsisten dengan view
            $summary = [
                'total_penjualan' => $penjualanTertinggi->sum('total_harga'),
                'total_item_terjual' => $penjualanTertinggi->sum('total_item'),
                'jumlah_transaksi' => $penjualanTertinggi->sum('jumlah_transaksi'),
                'rata_rata_per_hari' => $penjualanTertinggi->count() > 0 ? $penjualanTertinggi->sum('total_harga') / $penjualanTertinggi->count() : 0,
                'periode' => $periode,
                'periode_text' => 'Laporan Penjualan Tertinggi - ' . $this->getPeriodeText($periode, $tanggalMulai, $tanggalSelesai),
                'tanggal_mulai' => Carbon::parse($tanggalMulai)->format('d/m/Y'),
                'tanggal_selesai' => Carbon::parse($tanggalSelesai)->format('d/m/Y'),
                'jumlah_data' => $penjualanTertinggi->count(),
                'presentase_data' => round(($penjualanTertinggi->count() / max(1, $count)) * 100, 1) . '%'
            ];


            // Generate PDF
            $pdf = Pdf::loadView('reports.penjualan_tertinggi_pdf', [
                'penjualan_list' => $penjualanTertinggi,
                'summary' => $summary,
            ]);

            // Set paper size dan orientasi
            $pdf->setPaper('A4', 'portrait');

            // Generate filename
            $filename = 'laporan-penjualan-' . $periode . '-' . date('Y-m-d-H-i-s') . '.pdf';

            // Return PDF as download
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal generate PDF: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function penjualanTerendahPdf(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|string',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
                'token' => 'nullable|string', // Token dari query parameter
            ]);

            // Jika token ada di query parameter, set ke header
            $token = $request->get('token');
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }

            $periode = $request->periode;
            $tanggalMulai = $request->tanggal_mulai;
            $tanggalSelesai = $request->tanggal_selesai;

            // Query laporan penjualan dengan struktur database yang benar
            $penjualanQuery = DB::table('transaksi')
                ->select(
                    'transaksi.id as transaksi_id',
                    'transaksi.kode_transaksi',
                    'transaksi.nama_customer',
                    'transaksi.total_harga',
                    'transaksi.metode_pembayaran',
                    'transaksi.tanggal_transaksi',
                    'transaksi.created_at',
                    'users.name as user_name',
                    'transaksi_roti.id as item_id',
                    'transaksi_roti.jumlah',
                    'transaksi_roti.harga_satuan',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    DB::raw('(transaksi_roti.harga_satuan * transaksi_roti.jumlah) as total_nilai_item')
                )
                ->join('users', 'users.id', '=', 'transaksi.user_id')
                ->join('transaksi_roti', 'transaksi_roti.transaksi_id', '=', 'transaksi.id')
                ->join('rotis', 'rotis.id', '=', 'transaksi_roti.roti_id');

            // Filter berdasarkan periode tanggal
            switch ($periode) {
                case 'harian':
                    $penjualanQuery->whereDate('transaksi.tanggal_transaksi', '>=', $tanggalMulai)
                        ->whereDate('transaksi.tanggal_transaksi', '<=', $tanggalSelesai);
                    break;
                case 'mingguan':
                case 'bulanan':
                case 'tahunan':
                    $penjualanQuery->where('transaksi.tanggal_transaksi', '>=', $tanggalMulai . ' 00:00:00')
                        ->where('transaksi.tanggal_transaksi', '<=', $tanggalSelesai . ' 23:59:59');
                    break;
            }

            // Filter berdasarkan role user yang login
            $user = Auth::user();

            // Jika user null, coba authenticate via token di query parameter
            if (!$user && $token) {
                try {
                    $tokenModel = PersonalAccessToken::findToken($token);
                    if ($tokenModel) {
                        $user = $tokenModel->tokenable;
                        Auth::setUser($user);
                    }
                } catch (\Exception $e) {
                    // Token invalid, user tetap null
                }
            }

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak terautentikasi untuk PDF export'
                ], 401);
            }

            $userRole = $user->role;
            $userId = $user->id;

            if (strtolower($userRole) === 'frontliner') {
                $penjualanQuery->where('transaksi.user_id', $userId);
            }

            $penjualanData = $penjualanQuery->orderBy('transaksi.created_at', 'desc')->get();

            if ($penjualanData->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data transaksi dalam periode tersebut'
                ], 404);
            }

            // Group data per hari per user (konsisten dengan laporan tertinggi)
            $grouped = $penjualanData->groupBy(function($item) {
                return $item->user_name . '_' . Carbon::parse($item->tanggal_transaksi)->format('Y-m-d');
            })->map(function($group) {
                $firstItem = $group->first();
                $totalHarga = $group->sum('total_harga');
                $totalItem = $group->sum('jumlah');
                $jumlahTransaksi = $group->count();
                return [
                    'tanggal_transaksi' => Carbon::parse($firstItem->tanggal_transaksi)->format('Y-m-d'),
                    'nama_kasir' => $firstItem->user_name,
                    'jumlah_transaksi' => $jumlahTransaksi,
                    'total_item' => $totalItem,
                    'total_harga' => $totalHarga,
                    'rata_rata_transaksi' => $jumlahTransaksi > 0 ? $totalHarga / $jumlahTransaksi : 0,
                ];
            })->sortBy('total_harga')->values(); // TERENDAH: sortBy asc

            // Filter minimum 5 transaksi
            $filtered = $grouped->filter(function($item) {
                return $item['jumlah_transaksi'] >= 5;
            })->values();

            if ($filtered->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data dengan minimum 5 transaksi'
                ], 404);
            }

            // Ambil 20% terbawah
            $count = $filtered->count();
            $bottomCount = max(1, intval($count * 0.2));
            $penjualanTerendah = $filtered->take($bottomCount)->values();

            // Summary data konsisten dengan view
            $summary = [
                'total_penjualan' => $penjualanTerendah->sum('total_harga'),
                'total_item_terjual' => $penjualanTerendah->sum('total_item'),
                'jumlah_transaksi' => $penjualanTerendah->sum('jumlah_transaksi'),
                'rata_rata_per_hari' => $penjualanTerendah->count() > 0 ? $penjualanTerendah->sum('total_harga') / $penjualanTerendah->count() : 0,
                'periode' => $periode,
                'periode_text' => 'Laporan Penjualan Terendah - ' . $this->getPeriodeText($periode, $tanggalMulai, $tanggalSelesai),
                'tanggal_mulai' => Carbon::parse($tanggalMulai)->format('d/m/Y'),
                'tanggal_selesai' => Carbon::parse($tanggalSelesai)->format('d/m/Y'),
                'jumlah_data' => $penjualanTerendah->count(),
                'presentase_data' => round(($penjualanTerendah->count() / max(1, $count)) * 100, 1) . '%'
            ];

            // Generate PDF
            $pdf = Pdf::loadView('reports.penjualan_terendah_pdf', [
                'penjualan_list' => $penjualanTerendah,
                'summary' => $summary,
            ]);

            $pdf->setPaper('A4', 'portrait');
            $filename = 'laporan-penjualan-terendah-' . $periode . '-' . date('Y-m-d-H-i-s') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal generate PDF: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }
}
