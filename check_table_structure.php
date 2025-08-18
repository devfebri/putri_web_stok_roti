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
    echo "=== STRUKTUR TABEL ROTI_POS ===\n";
    $columns = Capsule::select("DESCRIBE roti_pos");
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type}) - {$column->Null} - {$column->Key} - Default: {$column->Default}\n";
    }
    
    echo "\n=== DATA SAMPLE ROTI_POS ===\n";
    $samples = Capsule::table('roti_pos')->limit(3)->get();
    foreach ($samples as $sample) {
        echo "ID: {$sample->id}, Kode PO: " . ($sample->kode_po ?? 'NULL') . ", Jumlah PO: " . ($sample->jumlah_po ?? 'NULL') . "\n";
    }

    echo "\n=== STRUKTUR TABEL POS ===\n";
    $posCols = Capsule::select("DESCRIBE pos");
    foreach ($posCols as $column) {
        echo "- {$column->Field} ({$column->Type}) - {$column->Null} - {$column->Key} - Default: {$column->Default}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
