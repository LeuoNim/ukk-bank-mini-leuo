@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-wrap justify-between items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Laporan Kas Bulanan</h1>
        <p class="text-gray-600">Rekapitulasi transaksi kas masuk dan keluar periode {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</p>
    </div>

    <!-- Filter Bulan & Tahun -->
    <form action="{{ route('supervisor.laporan_bulanan') }}" method="GET" class="flex gap-2">
        <select name="month" class="px-3 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ sprintf('%02d', $m) }}" {{ $month == sprintf('%02d', $m) ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                </option>
            @endfor
        </select>
        <select name="year" class="px-3 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm">
            @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded text-sm font-bold shadow">
            Tampilkan
        </button>
    </form>
</div>

<!-- Card Ringkasan -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-5 border border-gray-200 rounded-lg shadow-sm border-l-4 border-l-green-500">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Setoran Kas (+)</p>
        <p class="text-2xl font-bold text-green-600">Rp {{ number_format($total_deposit, 0, ',', '.') }}</p>
    </div>

    <div class="bg-white p-5 border border-gray-200 rounded-lg shadow-sm border-l-4 border-l-red-500">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Penarikan Kas (-)</p>
        <p class="text-2xl font-bold text-red-600">Rp {{ number_format($total_withdrawal, 0, ',', '.') }}</p>
    </div>

    <div class="bg-white p-5 border border-gray-200 rounded-lg shadow-sm border-l-4 {{ $net_cash >= 0 ? 'border-l-blue-500' : 'border-l-amber-500' }}">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Arus Kas Bersih (Netto)</p>
        <p class="text-2xl font-bold {{ $net_cash >= 0 ? 'text-blue-600' : 'text-amber-600' }}">
            Rp {{ number_format($net_cash, 0, ',', '.') }}
        </p>
    </div>
</div>

<!-- Rekap Per Tanggal -->
@if($dailySummary->isNotEmpty())
<div class="bg-white border border-gray-200 shadow-sm rounded-lg mb-6 overflow-hidden">
    <div class="p-4 bg-gray-50 border-b">
        <h2 class="font-bold text-gray-800 text-sm uppercase">Rekapitulasi Harian Kas Bulan Ini</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="py-2.5 px-4">Tanggal</th>
                    <th class="py-2.5 px-4 text-center">Jumlah Trx</th>
                    <th class="py-2.5 px-4 text-right">Total Setoran</th>
                    <th class="py-2.5 px-4 text-right">Total Penarikan</th>
                    <th class="py-2.5 px-4 text-right">Netto Kas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($dailySummary as $date => $sum)
                <tr class="hover:bg-gray-50">
                    <td class="py-2.5 px-4 font-medium">{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</td>
                    <td class="py-2.5 px-4 text-center">{{ $sum['count'] }}</td>
                    <td class="py-2.5 px-4 text-right text-green-600 font-semibold">Rp {{ number_format($sum['deposit'], 0, ',', '.') }}</td>
                    <td class="py-2.5 px-4 text-right text-red-600 font-semibold">Rp {{ number_format($sum['withdrawal'], 0, ',', '.') }}</td>
                    <td class="py-2.5 px-4 text-right font-bold {{ $sum['net'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        Rp {{ number_format($sum['net'], 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Tabel Rincian Semua Transaksi -->
<div class="bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">
    <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
        <h2 class="font-bold text-gray-800 text-sm uppercase">Daftar Transaksi (Total: {{ $transactions->count() }})</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="py-2.5 px-4">Waktu</th>
                    <th class="py-2.5 px-4">Kode Trx</th>
                    <th class="py-2.5 px-4">Nasabah</th>
                    <th class="py-2.5 px-4">Jenis</th>
                    <th class="py-2.5 px-4 text-right">Nominal</th>
                    <th class="py-2.5 px-4">Teller</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($transactions as $t)
                <tr class="hover:bg-gray-50">
                    <td class="py-2.5 px-4 text-gray-500">{{ \Carbon\Carbon::parse($t->transaction_date)->format('d/m/Y H:i') }}</td>
                    <td class="py-2.5 px-4 font-mono font-bold">{{ $t->transaction_code }}</td>
                    <td class="py-2.5 px-4">
                        <span class="font-semibold text-gray-900 block">{{ $t->customer->student_name ?? '-' }}</span>
                        <span class="text-xs text-gray-500 font-mono">{{ $t->customer->account_number ?? '-' }}</span>
                    </td>
                    <td class="py-2.5 px-4">
                        <span class="px-2 py-0.5 rounded text-xs font-bold {{ $t->transaction_type === 'Setoran' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $t->transaction_type }}
                        </span>
                    </td>
                    <td class="py-2.5 px-4 text-right font-bold {{ $t->transaction_type === 'Setoran' ? 'text-green-600' : 'text-red-600' }}">
                        Rp {{ number_format($t->amount, 0, ',', '.') }}
                    </td>
                    <td class="py-2.5 px-4 text-gray-700">{{ $t->user->staff_name ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-400">Tidak ada transaksi pada bulan ini.</td>
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

