<?php

// Script untuk menambahkan POST routes laporan penjualan

$routeFile = __DIR__ . '/routes/api.php';
$content = file_get_contents($routeFile);

// Backup original file
file_put_contents($routeFile . '.backup', $content);

// Define the pattern to find and replacement
$patterns = [
    // Admin section
    [
        'search' => "    Route::get('/laporan/penjualan', [LaporanController::class, 'penjualanReportApi'])->name('laporan_penjualan'); // Laporan penjualan\n    Route::get('/laporan/penjualan/pdf'",
        'replace' => "    Route::get('/laporan/penjualan', [LaporanController::class, 'penjualanReportApi'])->name('laporan_penjualan'); // Laporan penjualan\n    Route::post('/laporan/penjualan', [LaporanController::class, 'penjualanReportApi'])->name('laporan_penjualan_post'); // Laporan penjualan POST\n    Route::get('/laporan/penjualan/pdf'"
    ],
];

// Apply replacements
foreach ($patterns as $pattern) {
    if (strpos($content, $pattern['search']) !== false) {
        $content = str_replace($pattern['search'], $pattern['replace'], $content);
        echo "Added POST route for admin section\n";
    }
}

// Find all sections with penjualan routes and add POST for each
$sections = [
    'admin_',
    'pimpinan_', 
    'kepalatokokios_',
    'frontliner_'
];

foreach ($sections as $section) {
    // Find the section and add POST route
    $pattern = "/(\s+Route::get\('\/laporan\/penjualan', \[LaporanController::class, 'penjualanReportApi'\]\)->name\('laporan_penjualan'\);[^\n]*\n)(\s+Route::get\('\/laporan\/penjualan\/pdf')/";
    $replacement = '$1$2Route::post(\'/laporan/penjualan\', [LaporanController::class, \'penjualanReportApi\'])->name(\'laporan_penjualan_post\'); // Laporan penjualan POST' . "\n" . '$2';
    
    $content = preg_replace($pattern, $replacement, $content);
}

// Save the modified content
file_put_contents($routeFile, $content);

echo "POST routes added successfully to all sections!\n";
echo "Backup saved as: " . $routeFile . ".backup\n";

// Verify the changes
$lines = explode("\n", $content);
$postRoutes = 0;
foreach ($lines as $line) {
    if (strpos($line, "Route::post('/laporan/penjualan'") !== false) {
        $postRoutes++;
        echo "Found POST route: " . trim($line) . "\n";
    }
}

echo "Total POST routes added: $postRoutes\n";

?>
