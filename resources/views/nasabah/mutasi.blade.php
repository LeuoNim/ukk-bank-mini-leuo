@extends('layouts.nasabah')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Riwayat Mutasi</h1>
    <p class="text-gray-600">Catatan transaksi rekening Anda</p>
</div>

<!-- Filter -->
<div class="bg-white p-6 border rounded-lg shadow-sm mb-6">
    <form action="{{ route('nasabah.mutasi') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="px-3.5 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="px-3.5 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Jenis Transaksi</label>
            <select name="type" class="px-3.5 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="">Semua Transaksi</option>
                <option value="Setoran" {{ request('type') == 'Setoran' ? 'selected' : '' }}>Setoran</option>
                <option value="Penarikan" {{ request('type') == 'Penarikan' ? 'selected' : '' }}>Penarikan</option>
            </select>
        </div>
        <div>
            <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-800 font-bold text-sm transition">Filter</button>
            <a href="{{ route('nasabah.mutasi') }}" class="ml-2 text-gray-500 hover:text-gray-900 text-sm">Reset</a>
        </div>
    </form>
</div>

<div class="bg-white p-6 border rounded-lg shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-200">
                    <th class="p-3 font-semibold text-gray-700">Kode Transaksi</th>
                    <th class="p-3 font-semibold text-gray-700">Waktu</th>
                    <th class="p-3 font-semibold text-gray-700">Jenis</th>
                    <th class="p-3 font-semibold text-gray-700">Nominal</th>
                    <th class="p-3 font-semibold text-gray-700">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 font-mono text-xs text-gray-600">{{ $t->transaction_code }}</td>
                    <td class="p-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($t->transaction_date)->format('d M Y H:i') }}</td>
                    <td class="p-3">
                        @if(str_contains($t->transaction_type, 'Setoran'))
                            <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">{{ $t->transaction_type }}</span>
                        @else
                            <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">{{ $t->transaction_type }}</span>
                        @endif
                    </td>
                    <td class="p-3 font-bold text-gray-900">
                        {{ str_contains($t->transaction_type, 'Setoran') ? '+' : '-' }} Rp {{ number_format($t->amount, 0, ',', '.') }}
                    </td>
                    <td class="p-3 text-sm text-gray-600">{{ $t->description ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-6 text-center text-gray-500">Belum ada transaksi di periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
