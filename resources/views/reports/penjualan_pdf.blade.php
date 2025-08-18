<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
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
            color: #2563eb;
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
            background-color: #e3f2fd !important;
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
            color: #16a085;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
            <img src="{{ public_path('img/logo.png') }}" alt="Logo" style="height: 60px; margin-right: 20px;">
            <div style="text-align: left;">
                <h1 style="margin: 0; color: #333; font-size: 24px;">Laporan Penjualan</h1>
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


    @if(count($penjualan_list) > 0)
    <div class="summary-section">
        <h3 style="margin-top: 0;">Ringkasan Penjualan</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total Transaksi:</span>
                <span class="summary-value">{{ number_format($summary['jumlah_transaksi']) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Item Terjual:</span>
                <span class="summary-value">{{ number_format($summary['total_item_terjual']) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Penjualan:</span>
                <span class="summary-value currency">Rp {{ number_format($summary['total_penjualan'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Rata-rata per Transaksi:</span>
                <span class="summary-value currency">Rp {{ number_format($summary['rata_rata_per_transaksi'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <h3>Detail Transaksi</h3>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">No</th>
                <th style="width: 15%;">Kode Transaksi</th>
                <th style="width: 20%;">Customer</th>
                <th style="width: 25%;">Produk</th>
                <th class="text-center" style="width: 8%;">Total Item</th>
                <th class="text-right" style="width: 12%;">Total Harga</th>
                <th class="text-center" style="width: 15%;">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penjualan_list as $index => $transaksi)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $transaksi->kode_transaksi }}</td>
                <td>{{ $transaksi->nama_customer ?? 'N/A' }}</td>
                <td>
                    @if(count($transaksi->transaksi_roti) > 0)
                        @foreach($transaksi->transaksi_roti as $idx => $item)
                            {{ $item->nama_roti }}
                            @if($item->rasa_roti)
                                ({{ $item->rasa_roti }})
                            @endif
                            <small>x{{ $item->jumlah }}</small>
                            @if($idx < count($transaksi->transaksi_roti) - 1)
                                <br>
                            @endif
                        @endforeach
                    @else
                        N/A
                    @endif
                </td>
                <td class="text-center">{{ number_format($transaksi->total_item) }}</td>
                <td class="text-right currency">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</td>
                <td class="text-center">{{ date('d/m/Y H:i', strtotime($transaksi->tanggal_transaksi)) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-center"><strong>{{ number_format($summary['total_item_terjual']) }}</strong></td>
                <td class="text-right"><strong class="currency">Rp {{ number_format($summary['total_penjualan'], 0, ',', '.') }}</strong></td>
                <td class="text-center">-</td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="no-data">
        <h3>Tidak ada data transaksi untuk periode ini</h3>
    </div>
    @endif

    <div class="footer">
        <p>Laporan ini digenerate secara otomatis pada {{ date('d/m/Y H:i:s') }}</p>
        <p>© {{ date('Y') }} Putri Bakery - Sistem Manajemen Stok Roti</p>
    </div>
</body>
</html>
