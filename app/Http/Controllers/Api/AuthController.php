<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    //
    public function login(Request $request)
    {
        // 1. Untuk validasi inputan
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // 2. cari user berdasarkan username
        $user = User::where('username', $request->username)->first();

        // 3. Ngecek password dan password hash cocok atau tidak
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau Password tidak ditemukan.',
            ], 401);
        }

        // Pastikan status akun petugas aktif
        if (isset($user->status) && $user->status !== 'Aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun petugas tidak aktif atau telah dinonaktifkan.',
            ], 403);
        }

        // 4. Membuat token sanctum/session guard jika sukses
        $token = $user->createToken('BankMiniToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => $user,
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil, token telah dihapus',
        ], 200);
    }
}
