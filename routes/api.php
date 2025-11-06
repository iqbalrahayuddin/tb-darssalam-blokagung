<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController; // <-- Import
use App\Http\Controllers\Api\ForgotPasswordController; // <-- Import
use App\Http\Controllers\Api\PemasukanController; // <-- Import
use App\Http\Controllers\Api\PengeluaranController; // <-- Import
use App\Http\Controllers\Api\DashboardController; // <-- Import
use App\Http\Controllers\Api\UserController; // <-- Import
use App\Http\Controllers\Api\LaporanController; // <-- Import
use App\Http\Controllers\Api\AppVersionController; // <-- Import

// Endpoint publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- RUTE LUPA PASSWORD ---
Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
Route::post('/forgot-password/reset-password', [ForgotPasswordController::class, 'resetPassword']);
// ---------------------------

// Endpoint yang butuh autentikasi (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    // Route untuk cek user yang sedang login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Tambahkan route resource untuk PemasukanController
    Route::apiResource('pemasukan', PemasukanController::class);

    // Tambahkan route resource untuk PengeluaranController
    Route::apiResource('pengeluaran', PengeluaranController::class);

    // Route untuk data dashboard
    Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);

    // Route untuk logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- RUTE BARU UNTUK MANAJEMEN USER (CRUD) ---
    Route::apiResource('/users', UserController::class)->except(['show']);

    // Rute untuk mengunduh laporan keuangan dalam format PDF
    Route::get('/laporan-pdf', [LaporanController::class, 'downloadLaporanKeuangan']);
    // (Anda bisa tambahkan route API lain yang aman di sini)
});

// Ini adalah endpoint yang akan dicek oleh aplikasi Flutter Anda
Route::get('/app-version', [AppVersionController::class, 'getLatestVersion']);