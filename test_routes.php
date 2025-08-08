<?php

// Simple script to test if the controller methods are loadable

require_once 'vendor/autoload.php';

echo "Testing controller syntax...\n";

try {
    require_once 'app/Http/Controllers/LaporanController.php';
    echo "✅ LaporanController loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Error loading LaporanController: " . $e->getMessage() . "\n";
}

try {
    // Test if routes file is syntactically correct
    $routes = file_get_contents('routes/api.php');
    if (strpos($routes, 'dashboard-stats') !== false) {
        echo "✅ Dashboard stats route found\n";
    } else {
        echo "❌ Dashboard stats route not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking routes: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
