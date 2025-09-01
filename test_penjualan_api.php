<?php
// Test penjualan API response dengan produk
$host = 'localhost';
$dbname = 'web_putri';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TEST PENJUALAN API RESPONSE FORMAT ===\n\n";
    
    // Test query structure yang sama dengan LaporanController
    $sql = "SELECT 
        transaksi.id as transaksi_id,
        transaksi.kode_transaksi,
        transaksi.nama_customer,
        transaksi.total_harga,
        transaksi.metode_pembayaran,
        transaksi.tanggal_transaksi,
        transaksi.created_at,
        users.name as user_name,
        transaksi_roti.id as item_id,
        transaksi_roti.jumlah,
        transaksi_roti.harga_satuan,
        rotis.nama_roti,
        rotis.rasa_roti,
        (transaksi_roti.harga_satuan * transaksi_roti.jumlah) as total_nilai_item
    FROM transaksi
    JOIN users ON users.id = transaksi.user_id
    JOIN transaksi_roti ON transaksi_roti.transaksi_id = transaksi.id
    JOIN rotis ON rotis.id = transaksi_roti.roti_id
    ORDER BY transaksi.created_at DESC
    LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "Found " . count($results) . " records\n\n";
    
    // Group by transaksi_id seperti di controller
    $groupedData = [];
    foreach ($results as $row) {
        $transaksiId = $row->transaksi_id;
        if (!isset($groupedData[$transaksiId])) {
            $groupedData[$transaksiId] = [
                'id' => $row->transaksi_id,
                'kode_transaksi' => $row->kode_transaksi ?: ('TRX' . str_pad($row->transaksi_id, 8, '0', STR_PAD_LEFT)),
                'nama_customer' => $row->nama_customer,
                'total_harga' => $row->total_harga,
                'metode_pembayaran' => $row->metode_pembayaran,
                'tanggal_transaksi' => $row->tanggal_transaksi,
                'created_at' => $row->created_at,
                'user' => [
                    'name' => $row->user_name
                ],
                'user_id' => $row->user_id ?? null,
                'nama_user' => $row->user_name,
                'total_item' => 0,
                'transaksi_roti' => []
            ];
        }
        
        // Add item to transaksi_roti
        $groupedData[$transaksiId]['transaksi_roti'][] = [
            'id' => $row->item_id,
            'jumlah' => $row->jumlah,
            'harga_satuan' => $row->harga_satuan,
            'total_nilai_item' => $row->total_nilai_item,
            'nama_roti' => $row->nama_roti,
            'rasa_roti' => $row->rasa_roti,
            'roti' => [
                'nama_roti' => $row->nama_roti,
                'rasa_roti' => $row->rasa_roti,
            ]
        ];
        
        // Update total_item
        $groupedData[$transaksiId]['total_item'] += $row->jumlah;
    }
    
    // Display grouped data
    foreach ($groupedData as $transaksi) {
        echo "Transaksi ID: " . $transaksi['id'] . "\n";
        echo "Kode: " . $transaksi['kode_transaksi'] . "\n";
        echo "Customer: " . $transaksi['nama_customer'] . "\n";
        echo "Total: Rp " . number_format($transaksi['total_harga']) . "\n";
        echo "Total Item: " . $transaksi['total_item'] . "\n";
        echo "User: " . $transaksi['user']['name'] . "\n";
        echo "Produk:\n";
        
        foreach ($transaksi['transaksi_roti'] as $item) {
            echo "  - " . $item['nama_roti'];
            if ($item['rasa_roti']) {
                echo " (" . $item['rasa_roti'] . ")";
            }
            echo " x" . $item['jumlah'] . " = Rp " . number_format($item['total_nilai_item']) . "\n";
        }
        echo "\n";
    }
    
    echo "=== JSON FORMAT ===\n";
    echo json_encode([
        'status' => true,
        'data' => [
            'summary' => [
                'total_penjualan' => array_sum(array_column($groupedData, 'total_harga')),
                'total_item_terjual' => array_sum(array_column($groupedData, 'total_item')),
                'jumlah_transaksi' => count($groupedData),
            ],
            'penjualan_list' => array_values($groupedData)
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
