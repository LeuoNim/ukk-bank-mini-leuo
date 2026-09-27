@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Loket Transaksi</h1>
    <p class="text-gray-600">Cari nasabah dan lakukan transaksi</p>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        {{ session('error') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Kolom Kiri: Cari Nasabah -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-bold border-b pb-2 mb-4">Pencarian Nasabah</h2>
        
        <form action="{{ route('teller.transaksi') }}" method="GET" class="mb-6 flex gap-2">
            <input type="text" name="search" placeholder="Ketik NIS atau Nomor Rekening..." value="{{ request('search') }}" class="flex-1 px-4 py-2 border rounded focus:outline-none focus:border-amber-500" required>
            <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-700">Cari</button>
        </form>

        @if(isset($customer))
        <div class="bg-gray-50 border p-4 rounded-lg">
            <h3 class="font-bold text-gray-700 mb-2">Profil Nasabah</h3>
            <table class="w-full text-sm">
                <tr><td class="py-1 text-gray-500 w-1/3">No. Rekening</td><td class="font-semibold">{{ $customer->account_number }}</td></tr>
                <tr><td class="py-1 text-gray-500">NIS</td><td class="font-semibold">{{ $customer->student_id }}</td></tr>
                <tr><td class="py-1 text-gray-500">Nama</td><td class="font-semibold">{{ $customer->student_name }}</td></tr>
                <tr><td class="py-1 text-gray-500">Kelas</td><td class="font-semibold">{{ $customer->class }}</td></tr>
                <tr>
                    <td class="py-1 text-gray-500">Saldo Saat Ini</td>
                    <td class="font-bold text-lg text-green-600">Rp {{ number_format($customer->balance, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
        @elseif(request('search'))
        <div class="text-red-500 text-sm">Data nasabah tidak ditemukan.</div>
        @endif
    </div>

    <!-- Kolom Kanan: Form Transaksi -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-bold border-b pb-2 mb-4">Form Transaksi</h2>
        
        @if(isset($customer))
        <form action="{{ route('teller.deposit') }}" method="POST" id="formTransaksi">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Transaksi</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="jenis" value="setoran" checked onclick="document.getElementById('pinGroup').style.display='none'; this.form.action='{{ route('teller.deposit') }}'">
                        <span class="px-4 py-2 bg-green-100 text-green-800 border border-green-300 rounded hover:bg-green-200">Setoran</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="jenis" value="penarikan" onclick="document.getElementById('pinGroup').style.display='block'; this.form.action='{{ route('teller.withdraw') }}'">
                        <span class="px-4 py-2 bg-red-100 text-red-800 border border-red-300 rounded hover:bg-red-200">Penarikan</span>
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nominal (Rp)</label>
                <input type="number" name="amount" class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500" required min="1000">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Keterangan (Opsional)</label>
                <input type="text" name="description" class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
            </div>

            <div class="mb-6" id="pinGroup" style="display: none;">
                <label class="block text-sm font-bold text-gray-700 mb-2">PIN Nasabah (Wajib untuk Penarikan)</label>
                <input type="password" name="pin" class="w-full px-4 py-2 border rounded focus:outline-none focus:border-red-500">
            </div>

            <button type="submit" class="w-full bg-gray-900 text-white font-bold py-3 px-4 rounded hover:bg-black">
                Submit Transaksi
            </button>
        </form>
        @else
        <div class="text-gray-500 text-sm italic text-center py-8">
            Silakan cari nasabah terlebih dahulu di kolom sebelah kiri untuk melakukan transaksi.
        </div>
        @endif
    </div>

</div>
@endsection
