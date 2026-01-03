<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\BahanMakananController;
use App\Http\Controllers\PembelianBahanController;
use App\Http\Controllers\SesiPembelianController;
use App\Http\Controllers\LaporanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Master Data - Satuan (Read: All, Write: Admin only)
    Route::get('/satuan', [SatuanController::class, 'index']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/satuan', [SatuanController::class, 'store']);
        Route::put('/satuan/{satuan}', [SatuanController::class, 'update']);
        Route::delete('/satuan/{satuan}', [SatuanController::class, 'destroy']);
    });

    // Master Data - Bahan Makanan (Read: All, Write: Admin only)
    Route::get('/bahan', [BahanMakananController::class, 'index']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/bahan', [BahanMakananController::class, 'store']);
        Route::put('/bahan/{bahan}', [BahanMakananController::class, 'update']);
        Route::delete('/bahan/{bahan}', [BahanMakananController::class, 'destroy']);
    });

    // Master Data - Users (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    // Sesi Pembelian (Read: All, Write: Admin & Petugas)
    Route::get('/sesi-pembelian', [SesiPembelianController::class, 'index']);
    Route::get('/sesi-pembelian/today', [SesiPembelianController::class, 'today']);
    Route::get('/sesi-pembelian/{sesiPembelian}', [SesiPembelianController::class, 'show']);
    Route::middleware('role:admin,petugas')->group(function () {
        Route::post('/sesi-pembelian', [SesiPembelianController::class, 'store']);
        Route::put('/sesi-pembelian/{sesiPembelian}', [SesiPembelianController::class, 'update']);
        Route::delete('/sesi-pembelian/{sesiPembelian}', [SesiPembelianController::class, 'destroy']);
        Route::post('/sesi-pembelian/{sesiPembelian}/items', [SesiPembelianController::class, 'addItem']);
        Route::delete('/sesi-pembelian/{sesiPembelian}/items/{item}', [SesiPembelianController::class, 'removeItem']);
        Route::post('/sesi-pembelian/{sesiPembelian}/bukti', [SesiPembelianController::class, 'uploadBukti']);
        Route::delete('/sesi-pembelian/{sesiPembelian}/bukti/{bukti}', [SesiPembelianController::class, 'deleteBukti']);
        Route::post('/sesi-pembelian/{sesiPembelian}/complete', [SesiPembelianController::class, 'complete']);
    });

    // Pembelian Bahan (Read: All, Write: Admin & Petugas)
    Route::get('/pembelian', [PembelianBahanController::class, 'index']);
    Route::get('/pembelian/today', [PembelianBahanController::class, 'today']);
    Route::middleware('role:admin,petugas')->group(function () {
        Route::post('/pembelian', [PembelianBahanController::class, 'store']);
        Route::put('/pembelian/{pembelian}', [PembelianBahanController::class, 'update']);
        Route::delete('/pembelian/{pembelian}', [PembelianBahanController::class, 'destroy']);
    });

    // Laporan (All authenticated users)
    Route::prefix('laporan')->group(function () {
        Route::get('/dashboard', [LaporanController::class, 'dashboard']);
        Route::get('/harian', [LaporanController::class, 'harian']);
        Route::get('/mingguan', [LaporanController::class, 'mingguan']);
        Route::get('/bulanan', [LaporanController::class, 'bulanan']);
    });
});
