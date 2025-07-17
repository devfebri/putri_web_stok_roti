<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RotiController;
use App\Http\Controllers\RotiPoController;
use App\Http\Controllers\WasteController;
use App\Http\Middleware\BakerMiddleware;
use App\Http\Middleware\KepalaTokoMiddleware;
use App\Http\Middleware\PimpinanMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return User::all();
});


Route::post('/proses_login_API', [AuthController::class, 'loginApi'])->name('proses_login');





Route::prefix('pimpinan')->middleware(['auth:sanctum', PimpinanMiddleware::class])->name('pimpinan_')->group(function () {
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/roti', [RotiController::class, 'indexApi'])->name('roti_index');      // List semua roti
    Route::get('/roti/{id}', [RotiController::class, 'showApi'])->name('roti_show');   // Detail roti
    Route::post('/roti', [RotiController::class, 'storeApi'])->name('roti_store');     // Tambah roti
    Route::put('/roti/{id}', [RotiController::class, 'updateApi'])->name('roti_update'); // Update roti
    Route::delete('/roti/{id}', [RotiController::class, 'destroyApi'])->name('roti_destroy'); // Hapus roti

    Route::get('/rotipo', [RotiPoController::class, 'indexApi'])->name('rotipo_index');      // List semua roti
    Route::get('/getroti', [RotiPoController::class, 'getRotiApi'])->name('rotipo_getroti'); // List semua roti untuk dropdown
    Route::get('/rotipo/{id}', [RotiPoController::class, 'showApi'])->name('rotipo_show');   // Detail roti
    Route::post('/rotipo', [RotiPoController::class, 'storeApi'])->name('rotipo_store');     // Tambah roti
    Route::put('/rotipo/{id}', [RotiPoController::class, 'updateApi'])->name('rotipo_update'); // Update roti
    Route::delete('/rotipo/{id}', [RotiPoController::class, 'destroyApi'])->name('rotipo_destroy'); // Hapus roti
    Route::post('/rotipo/{id}/delivery', [RotiPoController::class, 'deliveryPoApi'])->name('rotipo_delivery');
});

Route::prefix('baker')->middleware(['auth:sanctum', BakerMiddleware::class])->name('baker_')->group(function () {
    // CRUD Roti
    Route::get('/roti', [RotiController::class, 'indexApi'])->name('roti_index');      // List semua roti
    Route::get('/roti/{id}', [RotiController::class, 'showApi'])->name('roti_show');   // Detail roti
    Route::post('/roti', [RotiController::class, 'storeApi'])->name('roti_store');     // Tambah roti
    Route::put('/roti/{id}', [RotiController::class, 'updateApi'])->name('roti_update'); // Update roti
    Route::delete('/roti/{id}', [RotiController::class, 'destroyApi'])->name('roti_destroy'); // Hapus roti

    Route::get('/rotipo', [RotiPoController::class, 'indexApi'])->name('rotipo_index');      // List semua roti
    Route::get('/getroti', [RotiPoController::class, 'getRotiApi'])->name('rotipo_getroti'); // List semua roti untuk dropdown
    Route::get('/rotipo/{id}', [RotiPoController::class, 'showApi'])->name('rotipo_show');   // Detail roti
    Route::post('/rotipo', [RotiPoController::class, 'storeApi'])->name('rotipo_store');     // Tambah roti
    Route::put('/rotipo/{id}', [RotiPoController::class, 'updateApi'])->name('rotipo_update'); // Update roti
    Route::delete('/rotipo/{id}', [RotiPoController::class, 'destroyApi'])->name('rotipo_destroy'); // Hapus roti
    Route::post('/rotipo/{id}/delivery',[RotiPoController::class,'deliveryPoApi'])->name('rotipo_delivery');
    Route::post('/rotipo/{id}/selesai', [RotiPoController::class, 'selesaiPoApi'])->name('rotipo_selesai');
    

});

Route::prefix('kepalatoko')->middleware(['auth:sanctum', KepalaTokoMiddleware::class])->name('kepalatoko_')->group(function () {
    //CRUD Roti PO
    Route::get('/rotipo', [RotiPoController::class, 'indexApi'])->name('rotipo_index');      // List semua roti
    Route::get('/getroti',[RotiPoController::class, 'getRotiApi'])->name('rotipo_getroti'); // List semua roti untuk dropdown
    Route::get('/rotipo/{id}', [RotiPoController::class, 'showApi'])->name('rotipo_show');   // Detail roti
    Route::post('/rotipo', [RotiPoController::class, 'storeApi'])->name('rotipo_store');     // Tambah roti
    Route::put('/rotipo/{id}', [RotiPoController::class, 'updateApi'])->name('rotipo_update'); // Update roti
    Route::delete('/rotipo/{id}', [RotiPoController::class, 'destroyApi'])->name('rotipo_destroy'); // Hapus roti

    Route::post('/rotipo/{id}/selesai', [RotiPoController::class, 'selesaiPoApi'])->name('rotipo_selesai');

    //CRUD Waste
    Route::get('/waste', [WasteController::class, 'indexApi'])->name('waste_index');      // List semua roti
    Route::get('/getrotipo', [WasteController::class, 'getRotiPoApi'])->name('waste_getroti'); // List semua roti untuk dropdown
    Route::get('/waste/{id}', [WasteController::class, 'showApi'])->name('waste_show');   // Detail roti
    Route::post('/waste', [WasteController::class, 'storeApi'])->name('waste_store');     // Tambah roti
    Route::put('/waste/{id}', [WasteController::class, 'updateApi'])->name('waste_update'); // Update roti
    Route::delete('/waste/{id}', [WasteController::class, 'destroyApi'])->name('waste_destroy'); // Hapus roti
}); 
