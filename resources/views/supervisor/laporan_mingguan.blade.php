@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-wrap justify-between items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Laporan Kas Mingguan</h1>
        <p class="text-gray-600 text-sm mt-1">
            Periode: <strong>Minggu ke-{{ $week }}</strong> ({{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }})
        </p>
    </div>

    <!-- Filter Pilihan Minggu Anti-Manipulasi -->
    <form action="{{ route('supervisor.laporan_mingguan') }}" method="GET" class="flex flex-wrap items-center gap-2">
        <!-- Pilihan Minggu -->
        <div>
            <select name="week" class="px-3 py-2 border rounded-md text-sm font-semibold bg-white focus:outline-none focus:border-gray-900 shadow-sm">
                <option value="1" {{ $week == 1 ? 'selected' : '' }}>Minggu 1 (Tgl 01 - 07)</option>
                <option value="2" {{ $week == 2 ? 'selected' : '' }}>Minggu 2 (Tgl 08 - 14)</option>
                <option value="3" {{ $week == 3 ? 'selected' : '' }}>Minggu 3 (Tgl 15 - 21)</option>
                <option value="4" {{ $week == 4 ? 'selected' : '' }}>Minggu 4 (Tgl 22 - 28)</option>
                @if($daysInMonth > 28)
                    <option value="5" {{ $week == 5 ? 'selected' : '' }}>Minggu 5 (Tgl 29 - {{ $daysInMonth }})</option>
                @endif
            </select>
        </div>

        <!-- Pilihan Bulan -->
        <div>
            <select name="month" class="px-3 py-2 border rounded-md text-sm bg-white focus:outline-none focus:border-gray-900 shadow-sm">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
        </div>

        <!-- Pilihan Tahun -->
        <div>
            <select name="year" class="px-3 py-2 border rounded-md text-sm bg-white focus:outline-none focus:border-gray-900 shadow-sm">
                @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>

        <button type="submit" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded-md text-sm font-bold shadow transition">
            Tampilkan
        </button>
    </form>
</div>

<p class="text-sm text-gray-600 mb-4">Periode mengikuti pembagian tanggal dalam bulan, bukan Senin–Minggu. Minggu ke-5 mencakup tanggal 29 sampai akhir bulan (29, 30, atau 31). Rekap memuat transaksi berhasil, termasuk laporan harian yang belum disetujui; netto bukan saldo kas akhir. Klik Tampilkan untuk menerapkan pilihan periode.</p>
<!-- Card Ringkasan Kas Mingguan -->
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

<!-- Rekap Per Hari dalam Minggu -->
@if($dailySummary->isNotEmpty())
<div class="bg-white border border-gray-200 shadow-sm rounded-lg mb-6 overflow-hidden">
    <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
        <h2 class="font-bold text-gray-800 text-sm uppercase">Rekapitulasi Harian Kas Minggu Ini</h2>
        <span class="text-xs text-gray-500">Maksimal 7 hari kalender</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="py-2.5 px-4">Hari / Tanggal</th>
                    <th class="py-2.5 px-4 text-center">Jumlah Trx</th>
                    <th class="py-2.5 px-4 text-right">Total Setoran</th>
                    <th class="py-2.5 px-4 text-right">Total Penarikan</th>
                    <th class="py-2.5 px-4 text-right">Netto Kas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($dailySummary as $date => $sum)
                <tr class="hover:bg-gray-50">
                    <td class="py-2.5 px-4 font-medium">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</td>
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
        <h2 class="font-bold text-gray-800 text-sm uppercase">Daftar Transaksi Minggu Ini (Total: {{ $transactions->total() }})</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="py-2.5 px-4">Waktu</th>
                    <th class="py-2.5 px-4">Kode Trx</th>
                    <th class="py-2.5 px-4">Nasabah</th>
                    <th class="py-2.5 px-4">Jenis</th>
                    <th class="py-2.5 px-4 text-right">Nominal</th>
                    <th class="py-2.5 px-4">Petugas Teller</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($transactions as $t)
                <tr class="hover:bg-gray-50">
                    <td class="py-2.5 px-4 text-xs text-gray-500">{{ \Carbon\Carbon::parse($t->transaction_date)->format('d/m/Y H:i') }}</td>
                    <td class="py-2.5 px-4 font-mono font-semibold">{{ $t->transaction_code }}</td>
                    <td class="py-2.5 px-4">
                        <div class="font-semibold">{{ $t->customer->student_name ?? '-' }}</div>
                        <div class="text-xs text-gray-500">{{ $t->customer->account_number ?? '-' }}</div>
                    </td>
                    <td class="py-2.5 px-4">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold
                            {{ $t->transaction_type === 'Setoran' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $t->transaction_type }}
                        </span>
                    </td>
                    <td class="py-2.5 px-4 text-right font-bold {{ $t->transaction_type === 'Setoran' ? 'text-green-600' : 'text-red-600' }}">
                        Rp {{ number_format($t->amount, 0, ',', '.') }}
                    </td>
                    <td class="py-2.5 px-4 text-xs text-gray-600">{{ $t->user->staff_name ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-6 text-gray-500 italic">
                        Tidak ada transaksi pada periode minggu ini.
                    </td>
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
<script>
    const periodForm = document.querySelector('select[name="week"]').form;
    function updateLastWeek() {
        const days = new Date(Number(periodForm.elements.year.value), Number(periodForm.elements.month.value), 0).getDate();
        const weeks = periodForm.elements.week;
        const lastWeek = weeks.querySelector('option[value="5"]');
        if (days > 28) {
            if (lastWeek) {
                lastWeek.textContent = `Minggu 5 (Tgl 29 - ${days})`;
            } else {
                weeks.add(new Option(`Minggu 5 (Tgl 29 - ${days})`, '5'));
            }
        } else if (lastWeek) {
            if (weeks.value === '5') weeks.value = '4';
            lastWeek.remove();
        }
    }
    periodForm.elements.month.addEventListener('change', updateLastWeek);
    periodForm.elements.year.addEventListener('change', updateLastWeek);
</script>
@endsection
