@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard Administrator</h1>
    <p class="text-gray-600">Ringkasan sistem bank mini sekolah</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Card Nasabah -->
    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm border-l-4 border-l-amber-500">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Nasabah</h3>
        <p class="text-3xl font-bold text-gray-900">{{ \App\Models\Customer::count() }}</p>
    </div>

    <!-- Card Petugas -->
    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm border-l-4 border-l-gray-900">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Petugas</h3>
        <p class="text-3xl font-bold text-gray-900">{{ \App\Models\User::count() }}</p>
    </div>

    <!-- Card Total Saldo -->
    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm border-l-4 border-l-green-500">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Saldo Bank</h3>
        <p class="text-2xl font-bold text-gray-900">Rp {{ number_format(\App\Models\Customer::sum('balance'), 0, ',', '.') }}</p>
    </div>

    <!-- Card Jurnal -->
    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm border-l-4 border-l-amber-500">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Entry Jurnal</h3>
        <p class="text-3xl font-bold text-gray-900">{{ \App\Models\Accounting_Journal::count() }}</p>
    </div>
</div>

<div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Pintasan Cepat</h3>
    <div class="flex flex-wrap gap-4">
        <a href="{{ route('admin.nasabah') }}" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded font-bold transition-colors">
            + Tambah Nasabah Baru
        </a>
        <a href="{{ route('admin.petugas') }}" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded font-bold transition-colors">
            + Tambah Petugas Baru
        </a>
        <a href="{{ route('admin.audit') }}" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded font-bold transition-colors">
            Lihat Log Audit
        </a>
    </div>
</div>
@endsection
