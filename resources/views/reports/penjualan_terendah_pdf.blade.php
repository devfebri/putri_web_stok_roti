<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan Terendah</title>
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
            color: #ff5722;
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
            background-color: #fff3e0;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #ff5722;
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
            color: #ff5722;
            font-weight: bold;
        }
        .improvement-note {
            background-color: #e1f5fe;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #2196f3;
            margin-bottom: 20px;
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
            background-color: #fff3e0;
            font-weight: bold;
            color: #ff5722;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .needs-attention {
            background-color: #ffebee !important;
            border-left: 4px solid #f44336;
        }
        .attention-low {
            background-color: #fff8e1 !important;
            border-left: 4px solid #ff9800;
        }
        .attention-medium {
            background-color: #f3e5f5 !important;
            border-left: 4px solid #9c27b0;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            background-color: #ffccbc !important;
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
            color: #ff5722;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-style: italic;
        }
        .attention-badge {
            background-color: #f44336;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .improvement-tips {
            background-color: #e8f5e8;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            border-left: 4px solid #4caf50;
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
            <img src="{{ public_path('img/logo.png') }}" alt="Logo" style="height: 60px; margin-right: 20px;">
            <div style="text-align: left;">
                <h1 style="margin: 0; color: #ff5722; font-size: 24px;">📉 Laporan Penjualan Terendah</h1>
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
        <div class="info-row">
            <span class="info-label">Kriteria:</span>
            <span>{{ $summary['presentase_data'] }} data terbawah (minimum 5 transaksi)</span>
        </div>
    </div>

    <div class="improvement-note">
        <strong>🎯 Area Improvement:</strong> Laporan ini menampilkan <strong>{{ $summary['jumlah_data'] }}</strong> data penjualan terendah 
        dari total yang memenuhi kriteria minimum 5 transaksi. Data ini dapat digunakan untuk identifikasi area yang memerlukan perbaikan.
    </div>

    @if(count($penjualan_list) > 0)
    <div class="summary-section">
        <h3 style="margin-top: 0;">📊 Ringkasan Penjualan Terendah</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Hari Yang Memerlukan Perhatian:</span>
                <span class="summary-value">{{ number_format($summary['jumlah_data']) }} hari</span>
            </div>
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
                <span class="summary-label">Rata-rata per Hari:</span>
                <span class="summary-value currency">Rp {{ number_format($summary['rata_rata_per_hari'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Presentase Data:</span>
                <span class="summary-value">{{ $summary['presentase_data'] }}</span>
            </div>
        </div>
    </div>

    <h3>⚠️ Data Penjualan Yang Memerlukan Perhatian</h3>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">No</th>
                <th class="text-center" style="width: 12%;">Tanggal</th>
                <th style="width: 20%;">Nama Kasir</th>
                <th class="text-center" style="width: 12%;">Jml Transaksi</th>
                <th class="text-center" style="width: 12%;">Total Item</th>
                <th class="text-right" style="width: 15%;">Total Penjualan</th>
                <th class="text-right" style="width: 14%;">Rata-rata</th>
                <th class="text-center" style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penjualan_list as $index => $penjualan)
            <tr class="{{ $index < 3 ? 'needs-attention' : ($index < 6 ? 'attention-low' : 'attention-medium') }}">
                <td class="text-center">
                    @if($index < 3)
                        <span class="attention-badge">{{ $index + 1 }}</span>
                    @else
                        {{ $index + 1 }}
                    @endif
                </td>
                <td class="text-center">{{ date('d/m/Y', strtotime($penjualan['tanggal_transaksi'])) }}</td>
                <td>{{ $penjualan['nama_kasir'] }}</td>
                <td class="text-center">{{ number_format($penjualan['jumlah_transaksi']) }}</td>
                <td class="text-center">{{ number_format($penjualan['total_item']) }}</td>
                <td class="text-right currency">Rp {{ number_format($penjualan['total_harga'], 0, ',', '.') }}</td>
                <td class="text-right currency">Rp {{ number_format($penjualan['rata_rata_transaksi'], 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($index < 3)
                        🔴 KRITIS
                    @elseif($index < 6)
                        🟡 PERHATIAN
                    @else
                        🟠 MONITOR
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-center"><strong>{{ number_format($summary['jumlah_transaksi']) }}</strong></td>
                <td class="text-center"><strong>{{ number_format($summary['total_item_terjual']) }}</strong></td>
                <td class="text-right"><strong class="currency">Rp {{ number_format($summary['total_penjualan'], 0, ',', '.') }}</strong></td>
                <td class="text-right"><strong class="currency">Rp {{ number_format($summary['rata_rata_per_hari'], 0, ',', '.') }}</strong></td>
                <td class="text-center">-</td>
            </tr>
        </tfoot>
    </table>

    <div class="improvement-tips">
        <h3 style="margin-top: 0;">💡 Saran Perbaikan</h3>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Analisis Pola:</strong> Identifikasi faktor-faktor yang menyebabkan penjualan rendah pada hari tersebut</li>
            <li><strong>Training Kasir:</strong> Berikan pelatihan tambahan untuk kasir dengan performa rendah</li>
            <li><strong>Strategi Marketing:</strong> Terapkan promosi atau strategi khusus untuk meningkatkan penjualan</li>
            <li><strong>Evaluasi Produk:</strong> Tinjau kembali jenis produk yang ditawarkan pada periode tersebut</li>
            <li><strong>Follow Up:</strong> Lakukan monitoring khusus untuk memastikan peningkatan performa</li>
        </ul>
    </div>
    @else
    <div class="no-data">
        <h3>Tidak ada data penjualan terendah untuk periode ini</h3>
        <p>Pastikan ada data transaksi dengan minimum 5 transaksi per hari</p>
    </div>
    @endif

    <div class="footer">
        <p>Laporan Penjualan Terendah - Digenerate secara otomatis pada {{ date('d/m/Y H:i:s') }}</p>
        <p>© {{ date('Y') }} Putri Bakery - Sistem Manajemen Stok Roti</p>
    </div>
</body>
</html>
