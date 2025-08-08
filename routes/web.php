<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\FrontlinerMiddleware;
use App\Http\Middleware\KepalaBakeryMiddleware;
use App\Http\Middleware\KepalaTokoKiosMiddleware;
use App\Http\Middleware\PimpinanMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'index'])->middleware('guest')->name('login');
Route::post('/proses_login', [AuthController::class, 'proses_login'])->name('proses_login');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('pimpinan')->middleware(PimpinanMiddleware::class)->name('pimpinan_')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::prefix('kepalabakery')->middleware(KepalaBakeryMiddleware::class)->name('kepalabakery_')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::prefix('kepalatokokios')->middleware(KepalaTokoKiosMiddleware::class)->name('kepalatokokios_')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
Route::prefix('frontliner')->middleware(FrontlinerMiddleware::class)->name('frontliner_')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
