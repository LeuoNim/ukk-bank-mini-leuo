<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class NasabahAuthController extends Controller
{
    //login khusus nasabah (siswa)
    public function login(Request $request)
    {
        //1. Validasi input
        $request->validate([
            'account_number' => 'required',
            'pin'            => 'required|numeric'
        ]);

        //2. Cari nasabah berdasarkan nomor rekening
        $customer = Customer::where('account_number', $request->account_number)->first();

        //3. Cek apakah nasabah ada dan PIN cocok
        if (!$customer || !Hash::check($request->pin, $customer->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor rekening atau PIN salah'
            ], 401);
        }

        //4. Buat token autentikasi nasabah
        $token = $customer->createToken('NasabahToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login nasabah berhasil',
            'data'    => $customer,
            'token'   => $token
        ], 200);
    }

    //logout nasabah
    public function logout(Request $request)
    {
        //hapus token yang sedang aktif
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil, token telah dihapus'
        ], 200);
    }

    //menampilkan profil nasabah
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Profil nasabah berhasil diambil',
            'data'    => $request->user()
        ], 200);
    }
}
