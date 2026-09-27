<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\NasabahAuthController;
use App\Http\Controllers\Api\NasabahController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

//Rute login petugas
Route::post('/login', [AuthController::class, 'login']);

//Rute login nasabah
Route::post('/nasabah/login', [NasabahAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    //Rute logout petugas
    Route::post('/logout', [AuthController::class, 'logout']);

    //Rute Master Data Nasabah
    Route::get('/customers', [CustomerController::class, 'index']);

    //Rute Teller & Administrator
    Route::middleware('role:Teller,Administrator')->group(function () {

        //transaksi
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::post('/deposit', [TransactionController::class, 'deposit']);
        Route::post('/withdraw', [TransactionController::class, 'withdraw']);

        //Laporan teller
        Route::post('/reports/submit', [ReportController::class, 'submit']);

        //cari nasabah & scan qr
        Route::get('/customers/search', [CustomerController::class, 'search']);
        Route::get('/customers/qr/{account_number}', [CustomerController::class, 'findByAccountNumber']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);
    });

    //Rute Supervisor
    Route::middleware('role:Supervisor')->group(function () {

        //laporan
        Route::get('/reports/daily', [ReportController::class, 'daily']);
        Route::get('/reports/{id}', [ReportController::class, 'show']);
        Route::post('/reports/approve', [ReportController::class, 'approve']);
    });

    //Rute Administrator
    Route::middleware('role:Administrator')->group(function () {

        //kelola pengguna
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        //kelola rekening nasabah
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::put('/customers/{id}', [CustomerController::class, 'update']);
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    });

    //Rute Jurnal (Supervisor & Administrator)
    Route::middleware('role:Supervisor,Administrator')->group(function () {

        //audit jurnal akuntansi
        Route::get('/journals', [JournalController::class, 'index']);
        Route::get('/journals/{id}', [JournalController::class, 'show']);
    });

    //Rute khusus nasabah
    Route::get('/nasabah/saldo', [NasabahController::class, 'saldo']);
    Route::get('/nasabah/mutasi', [NasabahController::class, 'mutasi']);
    Route::get('/nasabah/profile', [NasabahAuthController::class, 'profile']);
    Route::post('/nasabah/logout', [NasabahAuthController::class, 'logout']);

});
