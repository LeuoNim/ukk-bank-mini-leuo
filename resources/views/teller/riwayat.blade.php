@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Riwayat Transaksi</h1>
    <p class="text-gray-600">Daftar transaksi yang Anda proses hari ini</p>
</div>

<div class="bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">
    <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
        <h2 class="font-bold text-gray-700">Transaksi Terbaru</h2>
        <!-- Form filter sederhana -->
        <form action="{{ route('teller.riwayat') }}" method="GET" class="flex gap-2">
            <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" class="border rounded px-2 py-1 text-sm">
            <select name="type" class="border rounded px-2 py-1 text-sm">
                <option value="">Semua Jenis</option>
                <option value="Setoran" {{ request('type') == 'Setoran' ? 'selected' : '' }}>Setoran</option>
                <option value="Penarikan" {{ request('type') == 'Penarikan' ? 'selected' : '' }}>Penarikan</option>
            </select>
            <button type="submit" class="bg-gray-900 text-white px-3 py-1 rounded text-sm">Filter</button>
        </form>
    </div>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-100 text-gray-600 text-sm">
                <th class="py-3 px-4 border-b">Waktu</th>
                <th class="py-3 px-4 border-b">Kode Trx</th>
                <th class="py-3 px-4 border-b">Nasabah</th>
                <th class="py-3 px-4 border-b">Jenis</th>
                <th class="py-3 px-4 border-b">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $trx)
            <tr class="hover:bg-gray-50 border-b text-sm">
                <td class="py-3 px-4">{{ \Carbon\Carbon::parse($trx->transaction_date)->format('H:i:s') }}</td>
                <td class="py-3 px-4 text-gray-500">{{ $trx->transaction_code }}</td>
                <td class="py-3 px-4 font-medium">{{ $trx->customer->student_name }}</td>
                <td class="py-3 px-4">
                    @if($trx->transaction_type == 'Setoran')
                        <span class="text-green-600 font-medium">Setoran</span>
                    @else
                        <span class="text-red-600 font-medium">Penarikan</span>
                    @endif
                </td>
                <td class="py-3 px-4 font-bold">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="py-8 text-center text-gray-500">Belum ada transaksi</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
