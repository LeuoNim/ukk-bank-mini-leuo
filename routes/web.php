<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\TellerController;
use App\Http\Controllers\Web\SupervisorController;
use App\Http\Controllers\Web\AdminController;

// Halaman Login
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Redirect ke dashboard sesuai role
Route::get('/', function () {
    // jika belum login, ke halaman login
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    // redirect sesuai role
    return match(auth()->user()->role) {
        'Teller'        => redirect('/teller/transaksi'),
        'Supervisor'    => redirect('/supervisor/dashboard'),
        'Administrator' => redirect('/admin/dashboard'),
    };
})->name('home');

// === TELLER ===
Route::middleware(['auth', 'role:Teller'])->prefix('teller')->group(function () {
    Route::get('/transaksi', [TellerController::class, 'transaksi'])->name('teller.transaksi');
    Route::post('/deposit', [TellerController::class, 'deposit'])->name('teller.deposit');
    Route::post('/withdraw', [TellerController::class, 'withdraw'])->name('teller.withdraw');
    Route::get('/transaksi/{id}/cetak', [TellerController::class, 'cetakStruk'])->name('teller.cetak');
    Route::get('/riwayat', [TellerController::class, 'riwayat'])->name('teller.riwayat');
    Route::get('/laporan', [TellerController::class, 'laporan'])->name('teller.laporan');
    Route::post('/laporan/submit', [TellerController::class, 'submitLaporan'])->name('teller.laporan.submit');
});

// === SUPERVISOR ===
Route::middleware(['auth', 'role:Supervisor'])->prefix('supervisor')->group(function () {
    Route::get('/dashboard', [SupervisorController::class, 'dashboard'])->name('supervisor.dashboard');
    Route::get('/approval', [SupervisorController::class, 'approval'])->name('supervisor.approval');
    Route::get('/approval/{id}', [SupervisorController::class, 'detailLaporan'])->name('supervisor.approval.detail');
    Route::post('/approval/{id}/approve', [SupervisorController::class, 'approve'])->name('supervisor.approve');
    Route::get('/audit', [SupervisorController::class, 'audit'])->name('supervisor.audit');
});

// === ADMINISTRATOR ===
Route::middleware(['auth', 'role:Administrator'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/nasabah', [AdminController::class, 'nasabah'])->name('admin.nasabah');
    Route::get('/nasabah/tambah', [AdminController::class, 'tambahNasabah'])->name('admin.nasabah.tambah');
    Route::post('/nasabah', [AdminController::class, 'simpanNasabah'])->name('admin.nasabah.simpan');
    Route::get('/nasabah/{id}/edit', [AdminController::class, 'editNasabah'])->name('admin.nasabah.edit');
    Route::put('/nasabah/{id}', [AdminController::class, 'updateNasabah'])->name('admin.nasabah.update');
    Route::delete('/nasabah/{id}', [AdminController::class, 'hapusNasabah'])->name('admin.nasabah.hapus');
    Route::get('/nasabah/{id}/qr', [AdminController::class, 'cetakQrNasabah'])->name('admin.nasabah.qr');
    Route::get('/petugas', [AdminController::class, 'petugas'])->name('admin.petugas');
    Route::get('/petugas/tambah', [AdminController::class, 'tambahPetugas'])->name('admin.petugas.tambah');
    Route::post('/petugas', [AdminController::class, 'simpanPetugas'])->name('admin.petugas.simpan');
    Route::get('/petugas/{id}/edit', [AdminController::class, 'editPetugas'])->name('admin.petugas.edit');
    Route::put('/petugas/{id}', [AdminController::class, 'updatePetugas'])->name('admin.petugas.update');
    Route::delete('/petugas/{id}', [AdminController::class, 'hapusPetugas'])->name('admin.petugas.hapus');
    Route::get('/audit', [AdminController::class, 'audit'])->name('admin.audit');
    Route::get('/pengaturan', [AdminController::class, 'pengaturan'])->name('admin.pengaturan');
});
