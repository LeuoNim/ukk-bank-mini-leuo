<?php

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\CustomerLifecycleController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\NasabahController;
use App\Http\Controllers\Web\SupervisorController;
use App\Http\Controllers\Web\TellerController;
use Illuminate\Support\Facades\Route;

// Halaman Login
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Redirect ke dashboard sesuai role
Route::get('/', function () {
    if (auth('nasabah')->check() && ! auth('web')->check()) {
        return redirect()->route('nasabah.dashboard');
    }
    // jika belum login, ke halaman login
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    // redirect sesuai role
    return match (auth()->user()->role) {
        'Teller' => redirect('/teller/transaksi'),
        'Supervisor' => redirect('/supervisor/dashboard'),
        'Administrator' => redirect('/admin/dashboard'),
    };
})->name('home');

// === TELLER ===
Route::middleware(['auth', 'role:Teller'])->prefix('teller')->group(function () {
    Route::get('/transaksi', [TellerController::class, 'transaksi'])->name('teller.transaksi');
    Route::post('/unlock', [TellerController::class, 'unlockCustomer'])->name('teller.unlock');
    Route::post('/deposit', [TellerController::class, 'deposit'])->name('teller.deposit');
    Route::post('/nasabah/{customer}/activate', [CustomerLifecycleController::class, 'activate'])->name('teller.activate');
    Route::post('/nasabah/{customer}/reset', [CustomerLifecycleController::class, 'resetCredentials'])->middleware('throttle:6,1')->name('teller.reset_credentials');
    Route::post('/withdraw', [TellerController::class, 'withdraw'])->name('teller.withdraw');
    Route::get('/transaksi/{id}/status', [TellerController::class, 'checkPendingStatus'])->name('teller.withdraw.status');
    Route::post('/transaksi/{id}/batal', [TellerController::class, 'cancelWithdrawal'])->name('teller.withdraw.cancel');
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
    Route::post('/approval/{id}/reject', [SupervisorController::class, 'reject'])->name('supervisor.reject');
    Route::get('/laporan-mingguan', [SupervisorController::class, 'laporanMingguan'])->name('supervisor.laporan_mingguan');
    Route::get('/laporan-bulanan', [SupervisorController::class, 'laporanBulanan'])->name('supervisor.laporan_bulanan');
    Route::get('/audit', [SupervisorController::class, 'audit'])->name('supervisor.audit');
});

// === ADMINISTRATOR ===
Route::middleware(['auth', 'role:Administrator'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/nasabah', [AdminController::class, 'nasabah'])->name('admin.nasabah');
    Route::get('/nasabah/tambah', [AdminController::class, 'tambahNasabah'])->name('admin.nasabah.tambah');
    Route::get('/nasabah/import', [AdminController::class, 'importNasabahView'])->name('admin.nasabah.import');
    Route::post('/nasabah/import', [AdminController::class, 'importNasabahProcess'])->name('admin.nasabah.import.process');
    Route::get('/nasabah/template', [AdminController::class, 'downloadNasabahTemplate'])->name('admin.nasabah.template');
    Route::post('/nasabah', [AdminController::class, 'simpanNasabah'])->name('admin.nasabah.simpan');
    Route::get('/nasabah/{id}/edit', [AdminController::class, 'editNasabah'])->name('admin.nasabah.edit');
    Route::put('/nasabah/{id}', [AdminController::class, 'updateNasabah'])->name('admin.nasabah.update');
    Route::patch('/nasabah/{id}/status', [AdminController::class, 'statusNasabah'])->name('admin.nasabah.status');
    Route::patch('/nasabah/{customer}/pending', [CustomerLifecycleController::class, 'markPending'])->name('admin.nasabah.pending');
    Route::get('/nasabah/{id}/qr', [AdminController::class, 'cetakQrNasabah'])->name('admin.nasabah.qr');
    Route::get('/petugas', [AdminController::class, 'petugas'])->name('admin.petugas');
    Route::get('/petugas/tambah', [AdminController::class, 'tambahPetugas'])->name('admin.petugas.tambah');
    Route::post('/petugas', [AdminController::class, 'simpanPetugas'])->name('admin.petugas.simpan');
    Route::get('/petugas/{id}/edit', [AdminController::class, 'editPetugas'])->name('admin.petugas.edit');
    Route::put('/petugas/{id}', [AdminController::class, 'updatePetugas'])->name('admin.petugas.update');
    Route::patch('/petugas/{id}/status', [AdminController::class, 'statusPetugas'])->name('admin.petugas.status');
    Route::patch('/petugas/{id}/reset-password', [AdminController::class, 'resetPasswordPetugas'])->name('admin.petugas.reset_password');
    Route::get('/audit', [AdminController::class, 'audit'])->name('admin.audit');
});

// === NASABAH ===
Route::get('/nasabah/login', [LoginController::class, 'showLoginNasabah'])->name('nasabah.login')->middleware('guest:nasabah');
Route::post('/nasabah/login', [LoginController::class, 'loginNasabah']);
Route::get('/nasabah/lupa-password', [LoginController::class, 'showForgotPasswordNasabah'])->name('nasabah.lupa_password');
Route::post('/nasabah/lupa-password', [LoginController::class, 'processForgotPasswordNasabah'])->name('nasabah.lupa_password.process');
Route::post('/nasabah/logout', [LoginController::class, 'logoutNasabah'])->name('nasabah.logout');

Route::middleware(['auth:nasabah', 'customer.active', 'auth.session'])->prefix('nasabah')->name('nasabah.')->group(function () {
    Route::get('/', [NasabahController::class, 'dashboard'])->name('dashboard');
    Route::get('/mutasi', [NasabahController::class, 'mutasi'])->name('mutasi');
    Route::get('/profil', [NasabahController::class, 'profil'])->name('profil');
    Route::post('/ubah-password', [NasabahController::class, 'updatePassword'])->name('ubah_password');
    Route::post('/ubah-pin', [NasabahController::class, 'updatePin'])->name('ubah_pin');
    Route::post('/ubah-keamanan', [NasabahController::class, 'updateSecurity'])->middleware('throttle:6,1')->name('ubah_keamanan');
});
