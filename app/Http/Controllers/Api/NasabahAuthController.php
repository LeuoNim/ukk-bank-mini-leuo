<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class NasabahAuthController extends Controller
{
    // login khusus nasabah (siswa)
    public function login(Request $request)
    {
        $loginId = $request->input('login_id') ?? $request->input('account_number') ?? $request->input('student_id');
        $secret = $request->input('password') ?? $request->input('pin');

        if (! $loginId || ! $secret) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor rekening/NIS dan Password/PIN wajib diisi.',
            ], 422);
        }

        // 2. Cari nasabah berdasarkan nomor rekening atau NIS
        $customer = Customer::where('account_number', $loginId)
            ->orWhere('student_id', $loginId)
            ->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data nasabah tidak ditemukan.',
            ], 404);
        }

        if ($customer->status !== 'Aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun nasabah telah dinonaktifkan.',
            ], 403);
        }

        // 3. Cek kecocokan kredensial: periksa kecocokan terhadap password ATAU terhadap PIN transaksi
        $valid = false;
        if (! empty($customer->password) && Hash::check($secret, $customer->password)) {
            $valid = true;
        } elseif (! empty($customer->pin) && Hash::check($secret, $customer->pin)) {
            $valid = true;
        }

        if (! $valid) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor rekening/NIS atau Password/PIN salah.',
            ], 401);
        }

        // 4. Buat token autentikasi nasabah
        $token = $customer->createToken('NasabahToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login nasabah berhasil',
            'data' => $customer,
            'has_custom_password' => ! empty($customer->password),
            'token' => $token,
        ], 200);
    }

    // Ubah password login nasabah
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_pin' => 'required|numeric|digits_between:4,6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $customer = $request->user();

        if (! Hash::check($request->current_pin, $customer->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'PIN transaksi yang dimasukkan salah.',
            ], 400);
        }

        $customer->password = Hash::make($request->new_password);
        $customer->save();
        $customer->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password login nasabah berhasil diubah. Silakan login kembali.',
        ], 200);
    }

    // Ubah PIN transaksi nasabah
    public function changePin(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_pin' => 'required|numeric|digits:6|confirmed',
        ]);

        $customer = $request->user();

        $valid = false;
        if (! empty($customer->password)) {
            $valid = Hash::check($request->current_password, $customer->password);
        } else {
            $valid = Hash::check($request->current_password, $customer->pin);
        }

        if (! $valid) {
            return response()->json([
                'success' => false,
                'message' => 'Password akun yang dimasukkan salah.',
            ], 400);
        }

        $customer->pin = Hash::make($request->new_pin);
        $customer->save();
        $customer->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'PIN transaksi berhasil diperbarui. Silakan login kembali.',
        ], 200);
    }

    // Reset password/PIN jika lupa
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
            'account_number' => 'required|string',
            'reset_type' => 'required|in:password,pin',
            'verification_credential' => 'required|string',
            'new_password' => 'nullable|string|min:6|confirmed',
            'new_pin' => 'nullable|numeric|digits:6|confirmed',
        ]);

        $resetType = $request->input('reset_type');
        if ($resetType === 'password' && empty($request->input('new_password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Password baru wajib diisi minimal 6 karakter.',
            ], 422);
        }
        if ($resetType === 'pin' && empty($request->input('new_pin'))) {
            return response()->json([
                'success' => false,
                'message' => 'PIN baru wajib diisi 6 digit angka.',
            ], 422);
        }

        $customer = Customer::where('student_id', trim($request->input('student_id')))
            ->where('account_number', trim($request->input('account_number')))
            ->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Kombinasi NIS dan Nomor Rekening tidak ditemukan.',
            ], 404);
        }

        if ($customer->status !== 'Aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun nasabah belum aktif atau sedang dinonaktifkan.',
            ], 403);
        }

        // Verifikasi kepemilikan sah via Cross-Credential
        $verificationCred = (string) $request->input('verification_credential');
        if ($resetType === 'password') {
            if (! Hash::check($verificationCred, $customer->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verifikasi Gagal: PIN Transaksi yang Anda masukkan salah. Jika Anda lupa PIN dan Password sekaligus, silakan kunjungi loket Teller sekolah untuk verifikasi identitas fisik.',
                ], 403);
            }
            $customer->password = Hash::make($request->input('new_password'));
            $msg = 'Password login berhasil diperbarui! Silakan login kembali dengan password baru Anda.';
        } else {
            if (! Hash::check($verificationCred, $customer->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verifikasi Gagal: Password Akun yang Anda masukkan salah. Jika Anda lupa PIN dan Password sekaligus, silakan kunjungi loket Teller sekolah untuk verifikasi identitas fisik.',
                ], 403);
            }
            $customer->pin = Hash::make($request->input('new_pin'));
            $msg = 'PIN transaksi berhasil diperbarui!';
        }

        $customer->save();
        $customer->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => $msg,
        ], 200);
    }

    public function logout(Request $request)
    {
        // hapus token yang sedang aktif
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil, token telah dihapus',
        ], 200);
    }

    // menampilkan profil nasabah
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Profil nasabah berhasil diambil',
            'data' => $request->user(),
        ], 200);
    }
}
