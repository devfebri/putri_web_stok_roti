<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function wasteReportApi(Request $request)
    {
        $request->validate([
            'periode' => 'required|in:harian,mingguan,bulanan',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
        ]);

        $periode = $request->periode;
        $tanggalMulai = $request->tanggal_mulai;
        $tanggalSelesai = $request->tanggal_selesai;

        // Set default tanggal berdasarkan periode
        if (!$tanggalMulai || !$tanggalSelesai) {
            switch ($periode) {
                case 'harian':
                    $tanggalMulai = Carbon::today()->toDateString();
                    $tanggalSelesai = Carbon::today()->toDateString();
                    break;
                case 'mingguan':
                    $tanggalMulai = Carbon::now()->startOfWeek()->toDateString();
                    $tanggalSelesai = Carbon::now()->endOfWeek()->toDateString();
                    break;
                case 'bulanan':
                    $tanggalMulai = Carbon::now()->startOfMonth()->toDateString();
                    $tanggalSelesai = Carbon::now()->endOfMonth()->toDateString();
                    break;
            }
        }

        // Query laporan waste
        $wasteData = DB::table('wastes')
            ->select(
                'wastes.id',
                'wastes.kode_waste',
                'wastes.jumlah_waste',
                'wastes.tanggal_expired',
                'wastes.keterangan',
                'wastes.created_at',
                'users.name as user_name',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                'rotis.harga_roti',
                'stok_history.tanggal as tanggal_stok',
                DB::raw('(rotis.harga_roti * wastes.jumlah_waste) as total_kerugian')
            )
            ->join('users', 'users.id', '=', 'wastes.user_id')
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
            ->where('wastes.status', '!=', 9)
            ->whereBetween('stok_history.tanggal', [$tanggalMulai, $tanggalSelesai])
            ->orderBy('wastes.created_at', 'desc')
            ->get();

        // Summary data
        $summary = [
            'total_item_waste' => $wasteData->sum('jumlah_waste'),
            'total_kerugian' => $wasteData->sum('total_kerugian'),
            'jumlah_transaksi' => $wasteData->count(),
            'periode' => $periode,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
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

        return response()->json([
            'status' => true,
            'data' => [
                'waste_list' => $wasteData,
                'summary' => $summary,
                'waste_by_roti' => $wasteByRoti,
            ]
        ]);
    }    public function purchaseOrderReportApi(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|in:harian,mingguan,bulanan',
                'tanggal_mulai' => 'nullable|date',
                'tanggal_selesai' => 'nullable|date',
            ]);

            $periode = $request->periode;
            $tanggalMulai = $request->tanggal_mulai;
            $tanggalSelesai = $request->tanggal_selesai;

            // Set default tanggal berdasarkan periode
            if (!$tanggalMulai || !$tanggalSelesai) {
                switch ($periode) {
                    case 'harian':
                        $tanggalMulai = Carbon::today()->toDateString();
                        $tanggalSelesai = Carbon::today()->toDateString();
                        break;
                    case 'mingguan':
                        $tanggalMulai = Carbon::now()->startOfWeek()->toDateString();
                        $tanggalSelesai = Carbon::now()->endOfWeek()->toDateString();
                        break;
                    case 'bulanan':
                        $tanggalMulai = Carbon::now()->startOfMonth()->toDateString();
                        $tanggalSelesai = Carbon::now()->endOfMonth()->toDateString();
                        break;
                }
            }

            // Query laporan purchase order
            $poData = DB::table('roti_pos')
                ->select(
                    'roti_pos.id',
                    'roti_pos.kode_po',
                    'roti_pos.jumlah_po',
                    'roti_pos.status',
                    'roti_pos.tanggal_order',
                    'roti_pos.deskripsi',
                    'roti_pos.created_at',
                    'users.name as user_name',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    'rotis.harga_roti',
                    DB::raw('(rotis.harga_roti * roti_pos.jumlah_po) as total_nilai')
                )
                ->join('users', 'users.id', '=', 'roti_pos.user_id')
                ->join('rotis', 'rotis.id', '=', 'roti_pos.roti_id')
                ->where('roti_pos.status', '!=', 9)
                ->whereBetween('roti_pos.tanggal_order', [$tanggalMulai, $tanggalSelesai])
                ->orderBy('roti_pos.created_at', 'desc')
                ->get();

            // Summary data
            $summary = [
                'total_item_po' => $poData->sum('jumlah_po'),
                'total_nilai' => $poData->sum('total_nilai'),
                'jumlah_po' => $poData->count(),
                'po_pending' => $poData->where('status', 0)->count(),
                'po_delivery' => $poData->where('status', 1)->count(),
                'po_selesai' => $poData->where('status', 2)->count(),
                'periode' => $periode,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
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
            $poByStatus = $poData->groupBy('status')->map(function($group, $status) {
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
                    'jumlah' => $group->count(),
                    'total_item' => $group->sum('jumlah_po'),
                    'total_nilai' => $group->sum('total_nilai'),
                ];
            })->values();

            $result = [
                'status' => true,
                'data' => [
                    'po_list' => $poData,
                    'summary' => $summary,
                    'po_by_roti' => $poByRoti,
                    'po_by_status' => $poByStatus,
                ]
            ];

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);        }
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
            'periode' => 'required|in:harian,mingguan,bulanan',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
        ]);

        $periode = $request->periode;
        $tanggalMulai = $request->tanggal_mulai;
        $tanggalSelesai = $request->tanggal_selesai;

        // Set default tanggal berdasarkan periode
        if (!$tanggalMulai || !$tanggalSelesai) {
            switch ($periode) {
                case 'harian':
                    $tanggalMulai = Carbon::today()->toDateString();
                    $tanggalSelesai = Carbon::today()->toDateString();
                    break;
                case 'mingguan':
                    $tanggalMulai = Carbon::now()->startOfWeek()->toDateString();
                    $tanggalSelesai = Carbon::now()->endOfWeek()->toDateString();
                    break;
                case 'bulanan':
                    $tanggalMulai = Carbon::now()->startOfMonth()->toDateString();
                    $tanggalSelesai = Carbon::now()->endOfMonth()->toDateString();
                    break;
            }
        }        // Query laporan penjualan (menggunakan tabel transaksi)
        $penjualanData = DB::table('transaksi')
            ->select(
                'transaksi.id',
                'transaksi.nama_customer',
                'transaksi.jumlah',
                'transaksi.harga_satuan',
                'transaksi.total_harga',
                'transaksi.tanggal_transaksi',
                'transaksi.created_at',
                'users.name as user_name',
                'rotis.nama_roti',
                'rotis.rasa_roti'
            )
            ->join('users', 'users.id', '=', 'transaksi.user_id')
            ->join('rotis', 'rotis.id', '=', 'transaksi.roti_id')
            ->whereBetween('transaksi.tanggal_transaksi', [$tanggalMulai, $tanggalSelesai])
            ->orderBy('transaksi.created_at', 'desc')
            ->get();        // Summary data
        $summary = [
            'total_penjualan' => $penjualanData->sum('total_harga'),
            'total_item_terjual' => $penjualanData->sum('jumlah'),
            'jumlah_transaksi' => $penjualanData->count(),
            'rata_rata_per_transaksi' => $penjualanData->count() > 0 ? $penjualanData->sum('total_harga') / $penjualanData->count() : 0,
            'periode' => $periode,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
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
                'penjualan_list' => $penjualanData,
                'summary' => $summary,
                'penjualan_by_date' => $penjualanByDate,
            ]
        ]);
    }

    public function dashboardStatsApi()
    {
        $today = Carbon::today()->toDateString();
        $thisWeek = [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()];
        $thisMonth = [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->toDateString()];

        // Waste stats
        $wasteToday = DB::table('wastes')
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->where('stok_history.tanggal', $today)
            ->where('wastes.status', '!=', 9)
            ->sum('wastes.jumlah_waste');

        $wasteThisWeek = DB::table('wastes')
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->whereBetween('stok_history.tanggal', $thisWeek)
            ->where('wastes.status', '!=', 9)
            ->sum('wastes.jumlah_waste');

        $wasteThisMonth = DB::table('wastes')
            ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
            ->whereBetween('stok_history.tanggal', $thisMonth)
            ->where('wastes.status', '!=', 9)
            ->sum('wastes.jumlah_waste');

        // PO stats
        $poToday = DB::table('roti_pos')
            ->where('tanggal_order', $today)
            ->where('status', '!=', 9)
            ->count();

        $poThisWeek = DB::table('roti_pos')
            ->whereBetween('tanggal_order', $thisWeek)
            ->where('status', '!=', 9)
            ->count();

        $poThisMonth = DB::table('roti_pos')
            ->whereBetween('tanggal_order', $thisMonth)
            ->where('status', '!=', 9)
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
                'periode' => 'required|in:harian,mingguan,bulanan',
                'tanggal_mulai' => 'nullable|date',
                'tanggal_selesai' => 'nullable|date',
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

            // Set default tanggal berdasarkan periode jika tidak ada
            if (!$tanggalMulai || !$tanggalSelesai) {
                switch ($periode) {
                    case 'harian':
                        $tanggalMulai = Carbon::today()->toDateString();
                        $tanggalSelesai = Carbon::today()->toDateString();
                        break;
                    case 'mingguan':
                        $tanggalMulai = Carbon::now()->startOfWeek()->toDateString();
                        $tanggalSelesai = Carbon::now()->endOfWeek()->toDateString();
                        break;
                    case 'bulanan':
                        $tanggalMulai = Carbon::now()->startOfMonth()->toDateString();
                        $tanggalSelesai = Carbon::now()->endOfMonth()->toDateString();
                        break;
                }
            }

            // Query laporan penjualan (menggunakan tabel transaksi)
            $penjualanData = DB::table('transaksi')
                ->select(
                    'transaksi.id',
                    'transaksi.nama_customer',
                    'transaksi.jumlah',
                    'transaksi.harga_satuan',
                    'transaksi.total_harga',
                    'transaksi.tanggal_transaksi',
                    'transaksi.created_at',
                    'users.name as user_name',
                    'rotis.nama_roti',
                    'rotis.rasa_roti'
                )
                ->join('users', 'users.id', '=', 'transaksi.user_id')
                ->join('rotis', 'rotis.id', '=', 'transaksi.roti_id')
                ->whereBetween('transaksi.tanggal_transaksi', [$tanggalMulai, $tanggalSelesai])
                ->orderBy('transaksi.created_at', 'desc')
                ->get();

            // Summary data
            $summary = [
                'total_penjualan' => $penjualanData->sum('total_harga'),
                'total_item_terjual' => $penjualanData->sum('jumlah'),
                'jumlah_transaksi' => $penjualanData->count(),
                'rata_rata_per_transaksi' => $penjualanData->count() > 0 ? $penjualanData->sum('total_harga') / $penjualanData->count() : 0,
                'periode' => $periode,
                'periode_text' => $this->getPeriodeText($periode, $tanggalMulai, $tanggalSelesai),
                'tanggal_mulai' => Carbon::parse($tanggalMulai)->format('d/m/Y'),
                'tanggal_selesai' => Carbon::parse($tanggalSelesai)->format('d/m/Y'),
            ];

            // Generate PDF
            $pdf = Pdf::loadView('reports.penjualan_pdf', [
                'penjualan_list' => $penjualanData,
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
                'periode' => 'required|in:harian,mingguan,bulanan',
                'tanggal_mulai' => 'nullable|date',
                'tanggal_selesai' => 'nullable|date',
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

            // Set default tanggal berdasarkan periode jika tidak ada
            if (!$tanggalMulai || !$tanggalSelesai) {
                switch ($periode) {
                    case 'harian':
                        $tanggalMulai = Carbon::today()->toDateString();
                        $tanggalSelesai = Carbon::today()->toDateString();
                        break;
                    case 'mingguan':
                        $tanggalMulai = Carbon::now()->startOfWeek()->toDateString();
                        $tanggalSelesai = Carbon::now()->endOfWeek()->toDateString();
                        break;
                    case 'bulanan':
                        $tanggalMulai = Carbon::now()->startOfMonth()->toDateString();
                        $tanggalSelesai = Carbon::now()->endOfMonth()->toDateString();
                        break;
                }
            }

            // Query laporan waste
            $wasteData = DB::table('wastes')
                ->select(
                    'wastes.id',
                    'wastes.kode_waste',
                    'wastes.jumlah_waste',
                    'wastes.tanggal_expired',
                    'wastes.keterangan',
                    'wastes.created_at',
                    'users.name as user_name',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    'rotis.harga_roti',
                    'stok_history.tanggal as tanggal_stok',
                    DB::raw('(rotis.harga_roti * wastes.jumlah_waste) as total_kerugian')
                )
                ->join('users', 'users.id', '=', 'wastes.user_id')
                ->join('stok_history', 'stok_history.id', '=', 'wastes.stok_history_id')
                ->join('rotis', 'rotis.id', '=', 'stok_history.roti_id')
                ->where('wastes.status', '!=', 9)
                ->whereBetween('stok_history.tanggal', [$tanggalMulai, $tanggalSelesai])
                ->orderBy('wastes.created_at', 'desc')
                ->get();

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
                'periode' => 'required|in:harian,mingguan,bulanan',
                'tanggal_mulai' => 'nullable|date',
                'tanggal_selesai' => 'nullable|date',
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

            // Set default tanggal berdasarkan periode jika tidak ada
            if (!$tanggalMulai || !$tanggalSelesai) {
                switch ($periode) {
                    case 'harian':
                        $tanggalMulai = Carbon::today()->toDateString();
                        $tanggalSelesai = Carbon::today()->toDateString();
                        break;
                    case 'mingguan':
                        $tanggalMulai = Carbon::now()->startOfWeek()->toDateString();
                        $tanggalSelesai = Carbon::now()->endOfWeek()->toDateString();
                        break;
                    case 'bulanan':
                        $tanggalMulai = Carbon::now()->startOfMonth()->toDateString();
                        $tanggalSelesai = Carbon::now()->endOfMonth()->toDateString();
                        break;
                }
            }

            // Query laporan purchase order
            $poData = DB::table('roti_pos')
                ->select(
                    'roti_pos.id',
                    'roti_pos.kode_po',
                    'roti_pos.jumlah_po',
                    'roti_pos.status',
                    'roti_pos.tanggal_order',
                    'roti_pos.deskripsi',
                    'roti_pos.created_at',
                    'users.name as user_name',
                    'rotis.nama_roti',
                    'rotis.rasa_roti',
                    'rotis.harga_roti',
                    DB::raw('(rotis.harga_roti * roti_pos.jumlah_po) as total_nilai')
                )
                ->join('users', 'users.id', '=', 'roti_pos.user_id')
                ->join('rotis', 'rotis.id', '=', 'roti_pos.roti_id')
                ->where('roti_pos.status', '!=', 9)
                ->whereBetween('roti_pos.tanggal_order', [$tanggalMulai, $tanggalSelesai])
                ->orderBy('roti_pos.created_at', 'desc')
                ->get();

            // Summary data
            $summary = [
                'total_item_po' => $poData->sum('jumlah_po'),
                'total_nilai' => $poData->sum('total_nilai'),
                'jumlah_po' => $poData->count(),
                'po_pending' => $poData->where('status', 0)->count(),
                'po_delivery' => $poData->where('status', 1)->count(),
                'po_selesai' => $poData->where('status', 2)->count(),
                'periode' => $periode,
                'periode_text' => $this->getPeriodeText($periode, $tanggalMulai, $tanggalSelesai),
                'tanggal_mulai' => Carbon::parse($tanggalMulai)->format('d/m/Y'),
                'tanggal_selesai' => Carbon::parse($tanggalSelesai)->format('d/m/Y'),
            ];

            // Generate PDF
            $pdf = Pdf::loadView('reports.purchase_order_pdf', [
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
}
