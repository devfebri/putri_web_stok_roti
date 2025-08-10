<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Purchase Order</title>
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
            grid-template-columns: 1fr 1fr 1fr;
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
            color: #1d4ed8;
            font-weight: bold;
        }
        .status-section {
            background-color: #eff6ff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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
            background-color: #dbeafe !important;
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
            color: #1d4ed8;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-style: italic;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
        .status-delivery {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
        .status-selesai {
            background-color: #dcfce7;
            color: #166534;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Purchase Order</h1>
        <h2>{{ $summary['periode_text'] ?? 'Laporan Periode' }}</h2>
        <p>{{ $summary['tanggal_mulai'] }} - {{ $summary['tanggal_selesai'] }}</p>
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

    <div class="summary-section">
        <h3 style="margin-top: 0;">Ringkasan Purchase Order</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total PO:</span>
                <span class="summary-value">{{ number_format($summary['jumlah_po']) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Item:</span>
                <span class="summary-value">{{ number_format($summary['total_item_po']) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Nilai:</span>
                <span class="summary-value currency">Rp {{ number_format($summary['total_nilai'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="status-section">
        <h3 style="margin-top: 0;">Status Purchase Order</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Pending:</span>
                <span class="summary-value">{{ number_format($summary['po_pending']) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Delivery:</span>
                <span class="summary-value">{{ number_format($summary['po_delivery']) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Selesai:</span>
                <span class="summary-value">{{ number_format($summary['po_selesai']) }}</span>
            </div>
        </div>
    </div>

    @if(count($po_list) > 0)
    <h3>Detail Purchase Order</h3>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">No</th>
                <th style="width: 12%;">Kode PO</th>
                <th style="width: 25%;">Produk</th>
                <th class="text-center" style="width: 8%;">Qty</th>
                <th class="text-right" style="width: 12%;">Harga</th>
                <th class="text-right" style="width: 15%;">Total</th>
                <th class="text-center" style="width: 10%;">Status</th>
                <th class="text-center" style="width: 13%;">Tanggal Order</th>
            </tr>
        </thead>
        <tbody>
            @foreach($po_list as $index => $po)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $po->kode_po ?? 'N/A' }}</td>
                <td>{{ $po->nama_roti ?? 'N/A' }}
                    @if($po->rasa_roti)
                        <br><small style="color: #666;">{{ $po->rasa_roti }}</small>
                    @endif
                </td>
                <td class="text-center">{{ number_format($po->jumlah_po) }}</td>
                <td class="text-right">Rp {{ number_format($po->harga_roti, 0, ',', '.') }}</td>
                <td class="text-right currency">Rp {{ number_format($po->total_nilai, 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($po->status == 0)
                        <span class="status-pending">PENDING</span>
                    @elseif($po->status == 1)
                        <span class="status-delivery">DELIVERY</span>
                    @elseif($po->status == 2)
                        <span class="status-selesai">SELESAI</span>
                    @else
                        <span>UNKNOWN</span>
                    @endif
                </td>
                <td class="text-center">{{ date('d/m/Y', strtotime($po->tanggal_order)) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-center"><strong>{{ number_format($po_list->sum('jumlah_po')) }}</strong></td>
                <td class="text-right">-</td>
                <td class="text-right"><strong class="currency">Rp {{ number_format($po_list->sum('total_nilai'), 0, ',', '.') }}</strong></td>
                <td colspan="2" class="text-center">-</td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="no-data">
        <h3>Tidak ada data purchase order untuk periode ini</h3>
    </div>
    @endif

    <div class="footer">
        <p>Laporan ini digenerate secara otomatis pada {{ date('d/m/Y H:i:s') }}</p>
        <p>© {{ date('Y') }} Putri Bakery - Sistem Manajemen Stok Roti</p>
    </div>
</body>
</html>
