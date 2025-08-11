<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Waste</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0;
            color: #666;
            font-size: 16px;
            font-weight: normal;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        .summary-section {
            background-color: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .summary-label {
            font-weight: bold;
        }
        .summary-value {
            color: #dc2626;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            background-color: #fee2e2 !important;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .currency {
            color: #dc2626;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-style: italic;
        }
        .waste-reason {
            background-color: #fef3c7;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo" style="height: 60px; margin-right: 20px;">
            @endif
            <div style="text-align: left;">
                <h1 style="margin: 0; color: #333; font-size: 24px;">Laporan Waste</h1>
                <h2 style="margin: 5px 0; color: #666; font-size: 16px; font-weight: normal;">{{ $summary['periode_text'] ?? 'Laporan Periode' }}</h2>
                <p style="margin: 5px 0;">{{ $summary['tanggal_mulai'] }} - {{ $summary['tanggal_selesai'] }}</p>
            </div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Tanggal Cetak:</span>
            <span>{{ date('d/m/Y H:i:s') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Periode:</span>
            <span>{{ ucfirst($summary['periode']) }}</span>
        </div>
    </div>

    <!-- Debug Production -->
    <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">
        <h4>Debug Info (Production):</h4>
        <p><strong>Environment:</strong> {{ config('app.env') }}</p>
        <p><strong>Waste List Count:</strong> {{ count($waste_list) }}</p>
        <p><strong>Summary:</strong> {{ json_encode($summary) }}</p>
        @if(count($waste_list) > 0)
            <p><strong>First Waste Data:</strong> {{ json_encode($waste_list->first()) }}</p>
        @else
            <p><strong>No waste data found!</strong></p>
        @endif
        <p><strong>Logo Path:</strong> {{ public_path('img/logo.png') }}</p>
        <p><strong>Logo Exists:</strong> {{ file_exists(public_path('img/logo.png')) ? 'YES' : 'NO' }}</p>
    </div>

    <div class="summary-section">
        <h3 style="margin-top: 0;">Ringkasan Waste</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total Item Waste:</span>
                <span class="summary-value">{{ number_format($summary['total_item_waste']) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Transaksi:</span>
                <span class="summary-value">{{ number_format($summary['jumlah_transaksi']) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Kerugian:</span>
                <span class="summary-value currency">Rp {{ number_format($summary['total_kerugian'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Rata-rata per Item:</span>
                <span class="summary-value currency">Rp {{ number_format($summary['total_item_waste'] > 0 ? $summary['total_kerugian'] / $summary['total_item_waste'] : 0, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    @if(count($waste_list) > 0)
    <h3>Detail Waste</h3>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">No</th>
                <th style="width: 15%;">Kode Waste</th>
                <th style="width: 25%;">Produk</th>
                <th class="text-center" style="width: 8%;">Qty</th>
                <th class="text-right" style="width: 12%;">Harga</th>
                <th class="text-right" style="width: 15%;">Kerugian</th>
                <th class="text-center" style="width: 10%;">Expired</th>
                <th style="width: 10%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($waste_list as $index => $waste)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $waste->kode_waste ?? 'N/A' }}</td>
                <td>{{ $waste->nama_roti ?? 'N/A' }}
                    @if($waste->rasa_roti)
                        <br><small style="color: #666;">{{ $waste->rasa_roti }}</small>
                    @endif
                </td>
                <td class="text-center">{{ number_format($waste->jumlah_waste) }}</td>
                <td class="text-right">Rp {{ number_format($waste->harga_roti, 0, ',', '.') }}</td>
                <td class="text-right currency">Rp {{ number_format($waste->total_kerugian, 0, ',', '.') }}</td>
                <td class="text-center">{{ date('d/m/Y', strtotime($waste->tanggal_expired)) }}</td>
                <td>
                    @if($waste->keterangan)
                        <span class="waste-reason">{{ $waste->keterangan }}</span>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-center"><strong>{{ number_format($waste_list->sum('jumlah_waste')) }}</strong></td>
                <td class="text-right">-</td>
                <td class="text-right"><strong class="currency">Rp {{ number_format($waste_list->sum('total_kerugian'), 0, ',', '.') }}</strong></td>
                <td colspan="2" class="text-center">-</td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="no-data">
        <h3>Tidak ada data waste untuk periode ini</h3>
    </div>
    @endif

    <div class="footer">
        <p>Laporan ini digenerate secara otomatis pada {{ date('d/m/Y H:i:s') }}</p>
        <p>© {{ date('Y') }} Putri Bakery - Sistem Manajemen Stok Roti</p>
    </div>
</body>
</html>
