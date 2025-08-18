<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

try {
    $user = User::where('username', 'front1')->first();
    if ($user) {
        $user->password = bcrypt('password123');
        $user->save();
        echo "Password updated for front1 to 'password123'\n";
        
        // Verify the update
        $updated = User::where('username', 'front1')->first();
        echo "Verification: " . (password_verify('password123', $updated->password) ? 'SUCCESS' : 'FAILED') . "\n";
    } else {
        echo "User front1 not found\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
