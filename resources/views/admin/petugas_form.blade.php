@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ isset($user) ? 'Edit Petugas' : 'Tambah Petugas Baru' }}</h1>
        <p class="text-gray-600">Formulir data petugas bank mini sekolah</p>
    </div>
    <a href="{{ route('admin.petugas') }}" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded font-bold">Kembali</a>
</div>

<div class="bg-white p-6 border rounded-lg shadow-sm max-w-2xl">
    <form method="POST" action="{{ isset($user) ? route('admin.petugas.update', $user->id) : route('admin.petugas.simpan') }}">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-600 p-4 rounded mb-4 font-bold">
                Ada kesalahan input:
                <ul class="list-disc ml-5 font-normal text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Username</label>
            <input type="text" name="username" value="{{ old('username', $user->username ?? '') }}" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Nama Lengkap Petugas</label>
            <input type="text" name="staff_name" value="{{ old('staff_name', $user->staff_name ?? '') }}" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Role Akses</label>
            <select name="role" class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500" required>
                <option value="">-- Pilih Role --</option>
                <option value="Administrator" {{ old('role', $user->role ?? '') === 'Administrator' ? 'selected' : '' }}>Administrator (Kepala Bank)</option>
                <option value="Supervisor" {{ old('role', $user->role ?? '') === 'Supervisor' ? 'selected' : '' }}>Supervisor (Pengawas Laporan)</option>
                <option value="Teller" {{ old('role', $user->role ?? '') === 'Teller' ? 'selected' : '' }}>Teller (Petugas Loket)</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Password (Minimal 6 karakter)</label>
            <input type="password" name="password" {{ isset($user) ? '' : 'required' }} minlength="6"
                class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
            @if(isset($user))
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p>
            @endif
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-6 rounded w-full">
                {{ isset($user) ? 'Simpan Perubahan' : 'Simpan Petugas' }}
            </button>
        </div>
    </form>
</div>
@endsection
