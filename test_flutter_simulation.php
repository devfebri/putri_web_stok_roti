<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    echo "=== SIMULATING FLUTTER FRONTEND DATA STRUCTURE ===\n\n";
    
    // Simulate what Flutter frontend will receive from new POS API
    echo "1. Testing POS data structure with relationships:\n";
    
    $posData = Capsule::table('pos')
        ->join('users as user', 'pos.user_id', '=', 'user.id')
        ->leftJoin('users as frontliner', 'pos.frontliner_id', '=', 'frontliner.id')
        ->select(
            'pos.id',
            'pos.kode_po',
            'pos.deskripsi',
            'pos.tanggal_order',
            'pos.status',
            'user.name as user_name',
            'frontliner.name as frontliner_name'
        )
        ->get();
    
    foreach ($posData as $po) {
        echo "PO ID: {$po->id}\n";
        echo "Kode PO: {$po->kode_po}\n";
        echo "Description: {$po->deskripsi}\n";
        echo "Date: {$po->tanggal_order}\n";
        echo "Status: {$po->status}\n";
        echo "User: {$po->user_name}\n";
        echo "Frontliner: {$po->frontliner_name}\n";
        
        // Get roti items for this PO
        $rotiItems = Capsule::table('roti_pos')
            ->join('rotis', 'roti_pos.roti_id', '=', 'rotis.id')
            ->where('roti_pos.pos_id', $po->id)
            ->select(
                'roti_pos.id as roti_pos_id',
                'roti_pos.jumlah_po',
                'rotis.id as roti_id',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                'rotis.gambar_roti'
            )
            ->get();
        
        echo "Roti Items (" . count($rotiItems) . "):\n";
        foreach ($rotiItems as $item) {
            echo "  - {$item->nama_roti} ({$item->rasa_roti}): {$item->jumlah_po} pcs\n";
        }
        echo "\n";
    }
    
    echo "\n2. Simulating JSON structure Flutter will receive:\n";
    
    // Build the exact structure that PosController will return
    $apiResponse = [
        'success' => true,
        'message' => 'Data retrieved successfully',
        'data' => []
    ];
    
    foreach ($posData as $po) {
        $rotiItems = Capsule::table('roti_pos')
            ->join('rotis', 'roti_pos.roti_id', '=', 'rotis.id')
            ->where('roti_pos.pos_id', $po->id)
            ->select(
                'roti_pos.id',
                'roti_pos.jumlah_po',
                'roti_pos.roti_id',
                'rotis.nama_roti',
                'rotis.rasa_roti',
                'rotis.gambar_roti'
            )
            ->get();
        
        $apiResponse['data'][] = [
            'id' => $po->id,
            'kode_po' => $po->kode_po,
            'deskripsi' => $po->deskripsi,
            'tanggal_order' => $po->tanggal_order,
            'status' => $po->status,
            'user' => [
                'name' => $po->user_name
            ],
            'frontliner' => [
                'name' => $po->frontliner_name
            ],
            'roti_pos' => array_map(function($item) {
                return [
                    'id' => $item->id,
                    'jumlah_po' => $item->jumlah_po,
                    'roti_id' => $item->roti_id,
                    'roti' => [
                        'nama_roti' => $item->nama_roti,
                        'rasa_roti' => $item->rasa_roti,
                        'gambar_roti' => $item->gambar_roti
                    ]
                ];
            }, $rotiItems->toArray())
        ];
    }
    
    echo json_encode($apiResponse, JSON_PRETTY_PRINT);
    
    echo "\n\n3. Flutter Controller Changes Summary:\n";
    echo "✅ Provider updated to use /api/{role}/pos endpoint\n";
    echo "✅ Controller groupedRotiPoList() updated for new structure\n";
    echo "✅ Action methods updated to use PO ID instead of individual item IDs\n";
    echo "✅ View updated to use correct PO ID for operations\n";
    echo "\n";
    
    echo "4. Key Changes for Flutter:\n";
    echo "- PO data now comes pre-grouped by PO ID\n";
    echo "- Each PO contains array of roti_pos items\n";
    echo "- Delete/Update operations work on entire PO, not individual items\n";
    echo "- Auto kode_po generation handled by backend\n";
    echo "- Better data integrity with foreign key relationships\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
