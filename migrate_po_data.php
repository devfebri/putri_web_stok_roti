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
    echo "=== MIGRASI DATA DARI ROTI_POS KE POS ===\n";
    
    // Get all unique PO data from roti_pos (group by characteristics)
    $rotiPosData = Capsule::table('roti_pos')
        ->select('user_id', 'frontliner_id', 'created_at')
        ->groupBy('user_id', 'frontliner_id', 'created_at')
        ->get();
    
    echo "Found " . count($rotiPosData) . " unique PO groups\n";
    
    foreach ($rotiPosData as $group) {
        // Generate kode_po
        $lastPos = Capsule::table('pos')->orderBy('id', 'desc')->first();
        $nextNumber = $lastPos ? (intval(substr($lastPos->kode_po, 2)) + 1) : 1;
        $kode_po = 'PO' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        // Create PO entry
        $posId = Capsule::table('pos')->insertGetId([
            'kode_po' => $kode_po,
            'deskripsi' => 'Migrated from old roti_pos data',
            'tanggal_order' => date('Y-m-d', strtotime($group->created_at)),
            'status' => 0,
            'user_id' => $group->user_id,
            'frontliner_id' => $group->frontliner_id,
            'created_at' => $group->created_at,
            'updated_at' => now()
        ]);
        
        // Update roti_pos records to reference this PO
        $updated = Capsule::table('roti_pos')
            ->where('user_id', $group->user_id)
            ->where('frontliner_id', $group->frontliner_id)
            ->where('created_at', $group->created_at)
            ->update(['pos_id' => $posId]);
        
        echo "Created PO {$kode_po} (ID: {$posId}) and updated {$updated} roti_pos records\n";
    }
    
    echo "\n=== VERIFIKASI MIGRASI ===\n";
    $totalPos = Capsule::table('pos')->count();
    $totalRotiPos = Capsule::table('roti_pos')->count();
    $rotiPosWithoutPos = Capsule::table('roti_pos')->whereNull('pos_id')->count();
    
    echo "Total PO entries: {$totalPos}\n";
    echo "Total roti_pos entries: {$totalRotiPos}\n";
    echo "Roti_pos without pos_id: {$rotiPosWithoutPos}\n";
    
    if ($rotiPosWithoutPos == 0) {
        echo "✅ All roti_pos records now have valid pos_id!\n";
    } else {
        echo "❌ Some roti_pos records still don't have pos_id\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
