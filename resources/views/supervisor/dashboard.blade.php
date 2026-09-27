@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard Supervisor</h1>
    <p class="text-gray-600">Ringkasan aktivitas bank mini hari ini ({{ date('d F Y') }})</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Card Setoran -->
    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm border-l-4 border-l-green-500">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Setoran Hari Ini</h3>
        <p class="text-3xl font-bold text-gray-900">Rp {{ number_format($total_deposit, 0, ',', '.') }}</p>
    </div>

    <!-- Card Penarikan -->
    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm border-l-4 border-l-red-500">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Penarikan Hari Ini</h3>
        <p class="text-3xl font-bold text-gray-900">Rp {{ number_format($total_withdrawal, 0, ',', '.') }}</p>
    </div>

    <!-- Card Laporan Pending -->
    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm border-l-4 border-l-amber-500">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Laporan Pending</h3>
        <p class="text-3xl font-bold text-gray-900">{{ $pending_reports }} <span class="text-sm font-normal text-gray-500">menunggu persetujuan</span></p>
    </div>
</div>

<div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Pintasan Cepat</h3>
    <div class="flex gap-4">
        <a href="{{ route('supervisor.approval') }}" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded font-bold transition-colors">
            Cek Laporan Teller
        </a>
        <a href="#" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded font-bold transition-colors">
            Lihat Log Audit Jurnal
        </a>
    </div>
</div>
@endsection
