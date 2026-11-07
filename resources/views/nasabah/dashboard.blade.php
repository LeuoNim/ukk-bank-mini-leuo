@extends('layouts.nasabah')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard Nasabah</h1>
    <p class="text-gray-600">Selamat datang, {{ $customer->student_name }}</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <!-- Card Saldo -->
    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm border-l-4 border-l-green-500">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Saldo Saat Ini</h3>
        <p class="text-3xl font-bold text-gray-900">Rp {{ number_format($customer->balance, 0, ',', '.') }}</p>
    </div>

    <!-- Profil Nasabah -->
    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-4">Informasi Rekening</h3>
        <table class="w-full text-sm text-left">
            <tr>
                <td class="py-1 text-gray-500 w-1/3">No. Rekening</td>
                <td class="font-semibold">{{ $customer->account_number }}</td>
            </tr>
            <tr>
                <td class="py-1 text-gray-500">NIS</td>
                <td class="font-semibold">{{ $customer->student_id }}</td>
            </tr>
            <tr>
                <td class="py-1 text-gray-500">Kelas</td>
                <td class="font-semibold">{{ $customer->class }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Pintasan</h3>
    <div class="flex gap-4">
        <a href="{{ route('nasabah.mutasi') }}" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded font-bold transition-colors">
            Lihat Riwayat Mutasi
        </a>
    </div>
</div>
@endsection
