@extends('layouts.nasabah')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Profil & Keamanan Akun</h1>
    <p class="text-gray-600">Informasi rekening nasabah dan pengaturan kata sandi serta PIN transaksi</p>
    <p class="text-sm text-gray-600 mt-2">Untuk mengubah keduanya satu per satu: simpan password baru terlebih dahulu, lalu gunakan password baru itu saat mengganti PIN. Atau gunakan formulir ubah keduanya di bawah.</p>
</div>

@if(session('success_password'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 text-sm font-semibold">
        {{ session('success_password') }}
    </div>
@endif

@if(session('success_pin'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 text-sm font-semibold">
        {{ session('success_pin') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm font-semibold">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Kolom Kiri: Informasi Rekening (Identik dengan Dashboard Nasabah) -->
    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm h-fit">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-4 pb-2 border-b">Informasi Rekening</h3>
        <table class="w-full text-sm text-left">
            <tr>
                <td class="py-2 text-gray-500 w-1/3">No. Rekening</td>
                <td class="font-semibold font-mono text-gray-900">{{ $customer->account_number }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-500">NIS Siswa</td>
                <td class="font-semibold text-gray-900">{{ $customer->student_id }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-500">Nama Siswa</td>
                <td class="font-semibold text-gray-900">{{ $customer->student_name }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-500">Kelas</td>
                <td class="font-semibold text-gray-900">{{ $customer->class }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-500">Status Akun</td>
                <td>
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold {{ $customer->status === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $customer->status }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="py-2 text-gray-500">Saldo Rekening</td>
                <td class="font-bold text-gray-900 text-base">
                    Rp {{ number_format($customer->balance, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Kolom Kanan: Pengaturan Keamanan -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Form 1: Ubah Password Login -->
        <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Ubah Password Login</h3>
            <p class="text-xs text-gray-500 mb-4 pb-2 border-b">Password digunakan untuk masuk ke akun portal nasabah dan aplikasi mobile</p>

            <form action="{{ route('nasabah.ubah_password') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">PIN Transaksi Saat Ini (Otorisasi)</label>
                    <input type="password" name="current_pin" maxlength="6" required placeholder="Masukkan 6 digit PIN transaksi"
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm">
                    <p class="text-xs text-gray-500 mt-1">Masukkan PIN 6 digit Anda sebagai bukti kepemilikan rekening.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Password Baru</label>
                        <input type="password" name="new_password" required minlength="6" placeholder="Minimal 6 karakter"
                            class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Konfirmasi Password Baru</label>
                        <input type="password" name="new_password_confirmation" required minlength="6" placeholder="Ulangi password baru"
                            class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm">
                    </div>
                </div>

                <button type="submit" class="bg-gray-900 text-white font-bold py-2 px-5 rounded hover:bg-black transition-colors text-sm">
                    Simpan Password Baru
                </button>
            </form>
        </div>

        <!-- Form 2: Ubah PIN Transaksi -->
        <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Ubah PIN Transaksi</h3>
            <p class="text-xs text-gray-500 mb-4 pb-2 border-b">PIN 6 digit digunakan untuk mengonfirmasi penarikan tabungan di loket</p>

            <form action="{{ route('nasabah.ubah_pin') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Password Login Saat Ini</label>
                    <input type="password" name="current_password" required placeholder="Masukkan password akun Anda"
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm">
                    <p class="text-xs text-gray-500 mt-1">Masukkan password login akun Anda untuk otorisasi perubahan PIN.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">PIN Baru (6 Digit)</label>
                        <input type="password" name="new_pin" maxlength="6" required placeholder="Contoh: 123456"
                            class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Konfirmasi PIN Baru</label>
                        <input type="password" name="new_pin_confirmation" maxlength="6" required placeholder="Ulangi 6 digit PIN baru"
                            class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm">
                    </div>
                </div>

                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-5 rounded transition-colors text-sm">
                    Simpan PIN Baru
                </button>
            </form>
        </div>

    </div>

</div>
<div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm mt-6">
    <h2 class="text-lg font-bold text-gray-900 mb-2">Ubah Password dan PIN Sekaligus</h2>
    <p class="text-sm text-gray-600 mb-4">Masukkan password dan PIN yang berlaku sebelum perubahan. Jika belum pernah membuat password, gunakan PIN lama sebagai password saat ini. Keduanya disimpan bersama hanya jika semua isian benar. Aplikasi mobile perlu login ulang.</p>
    <form action="{{ route('nasabah.ubah_keamanan') }}" method="POST" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach([
                'current_password' => 'Password Saat Ini',
                'current_pin' => 'PIN Saat Ini (4–6 Digit)',
                'new_password' => 'Password Baru (Minimal 6 Karakter)',
                'new_password_confirmation' => 'Konfirmasi Password Baru',
                'new_pin' => 'PIN Baru (6 Digit)',
                'new_pin_confirmation' => 'Konfirmasi PIN Baru',
            ] as $field => $label)
                <div>
                    <label for="security-{{ $field }}" class="block text-sm font-bold text-gray-700 mb-1">{{ $label }}</label>
                    <input id="security-{{ $field }}" name="{{ $field }}" type="password" required
                        autocomplete="{{ str_starts_with($field, 'current_') ? 'current-password' : 'new-password' }}"
                        @if(str_contains($field, 'pin')) inputmode="numeric" maxlength="6" pattern="{{ $field === 'current_pin' ? '[0-9]{4,6}' : '[0-9]{6}' }}" @elseif(str_starts_with($field, 'new_')) minlength="6" @endif
                        class="w-full px-4 py-2 border rounded text-sm">
                </div>
            @endforeach
        </div>
        <button type="submit" class="bg-gray-900 text-white font-bold py-2 px-5 rounded text-sm">Simpan Password dan PIN Baru</button>
    </form>
</div>
@endsection
