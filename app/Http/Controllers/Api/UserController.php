<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // menampilkan daftar data pengguna / petugas
    public function index(Request $request)
    {
        $query = User::query();

        // filter berdasarkan role jika ada
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil',
            'data' => $users,
        ], 200);
    }

    // menambah pengguna / petugas baru
    public function store(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:4',
            'staff_name' => 'required|string|max:100',
            'role' => 'required|in:Administrator,Teller,Supervisor',
        ]);

        // 2. Simpan user baru
        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'staff_name' => $request->staff_name,
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun pengguna berhasil ditambahkan',
            'data' => $user,
        ], 201);
    }

    // menampilkan detail data pengguna
    public function show($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengguna tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pengguna berhasil diambil',
            'data' => $user,
        ], 200);
    }

    // mengupdate data pengguna
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengguna tidak ditemukan',
            ], 404);
        }

        // 1. Validasi input
        $request->validate([
            'username' => 'required|string|unique:users,username,'.$id,
            'staff_name' => 'required|string|max:100',
            'role' => 'required|in:Administrator,Teller,Supervisor',
            'password' => 'nullable|string|min:4',
        ]);

        // 2. Siapkan data yang diupdate
        $data = [
            'username' => $request->username,
            'staff_name' => $request->staff_name,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data pengguna berhasil diperbarui',
            'data' => $user,
        ], 200);
    }

    // menghapus data pengguna
    public function destroy(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengguna tidak ditemukan',
            ], 404);
        }

        // cegah menghapus akun sendiri
        if ($request->user()->id == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun pengguna berhasil dihapus',
        ], 200);
    }
}
