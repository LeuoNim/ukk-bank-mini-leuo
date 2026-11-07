<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerSecurityController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\NasabahAuthController;
use App\Http\Controllers\Api\NasabahController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Rute login petugas
Route::post('/login', [AuthController::class, 'login']);

// Rute login nasabah & lupa kredensial
Route::post('/nasabah/login', [NasabahAuthController::class, 'login']);
Route::post('/nasabah/forgot-password', [NasabahAuthController::class, 'forgotPassword']);

Route::middleware('auth:sanctum')->group(function () {

    // Rute logout petugas
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rute Master Data Nasabah (Khusus Petugas Teller dan Administrator)
    Route::middleware('role:Teller,Administrator')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index']);
    });

    // Rute Transaksi Loket & Laporan (Khusus Piket Teller)
    Route::middleware('role:Teller')->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::post('/deposit', [TransactionController::class, 'deposit']);
        Route::post('/withdraw', [TransactionController::class, 'withdraw']);
        Route::post('/reports/submit', [ReportController::class, 'submit']);
    });

    // Cari nasabah & scan qr (Teller & Administrator)
    Route::middleware('role:Teller,Administrator')->group(function () {
        Route::get('/customers/search', [CustomerController::class, 'search']);
        Route::get('/customers/qr/{account_number}', [CustomerController::class, 'findByAccountNumber']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);
    });

    // Rute Supervisor
    Route::middleware('role:Supervisor')->group(function () {

        // laporan
        Route::get('/reports/daily', [ReportController::class, 'daily']);
        Route::get('/reports/{id}', [ReportController::class, 'show']);
        Route::post('/reports/approve', [ReportController::class, 'approve']);
    });

    // Rute Administrator
    Route::middleware('role:Administrator')->group(function () {

        // kelola pengguna
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // kelola rekening nasabah
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::put('/customers/{id}', [CustomerController::class, 'update']);
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    });

    // Rute Jurnal (Supervisor & Administrator)
    Route::middleware('role:Supervisor,Administrator')->group(function () {

        // audit jurnal akuntansi
        Route::get('/journals', [JournalController::class, 'index']);
        Route::get('/journals/{id}', [JournalController::class, 'show']);
    });

    // Rute khusus nasabah
    Route::middleware('customer.active')->group(function () {
        Route::get('/nasabah/saldo', [NasabahController::class, 'saldo']);
        Route::get('/nasabah/mutasi', [NasabahController::class, 'mutasi']);
        // Otorisasi Penarikan di Aplikasi Mobile (Mendukung kedua pola URL)
        Route::get('/nasabah/pending-withdrawal', [NasabahController::class, 'getPendingWithdrawal']);
        Route::get('/nasabah/withdrawal/pending', [NasabahController::class, 'getPendingWithdrawal']);
        Route::post('/nasabah/confirm-withdrawal', [NasabahController::class, 'confirmWithdrawal']);
        Route::post('/nasabah/withdrawal/confirm', [NasabahController::class, 'confirmWithdrawal']);
        Route::post('/nasabah/reject-withdrawal', [NasabahController::class, 'rejectWithdrawal']);
        Route::post('/nasabah/withdrawal/reject', [NasabahController::class, 'rejectWithdrawal']);
        Route::get('/nasabah/profile', [NasabahAuthController::class, 'profile']);
        Route::post('/nasabah/change-password', [NasabahAuthController::class, 'changePassword']);
        Route::post('/nasabah/change-pin', [NasabahAuthController::class, 'changePin']);
        Route::post('/nasabah/logout', [NasabahAuthController::class, 'logout']);
        Route::post('/nasabah/security', [CustomerSecurityController::class, 'update'])->middleware('throttle:6,1');
    });

});
