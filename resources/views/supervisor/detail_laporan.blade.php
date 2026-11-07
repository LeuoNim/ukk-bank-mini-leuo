@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Detail Laporan Harian</h1>
        <p class="text-gray-600">Tanggal: {{ \Carbon\Carbon::parse($report->report_date)->format('d F Y') }}</p>
    </div>
    <a href="{{ route('supervisor.approval') }}" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded font-bold">Kembali</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white p-6 border rounded-lg shadow-sm border-l-4 border-l-gray-900">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Informasi Teller</h3>
        <p class="text-xl font-bold text-gray-900">{{ $report->teller->staff_name }}</p>
        <p class="text-gray-600">ID: {{ $report->teller->username }}</p>
    </div>
    
    <div class="bg-white p-6 border rounded-lg shadow-sm border-l-4 {{ $report->approval_status == 'Approved' ? 'border-l-green-500' : ($report->approval_status == 'Rejected' ? 'border-l-red-500' : 'border-l-amber-500') }}">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Status Persetujuan</h3>
        @if($report->approval_status == 'Approved')
            <p class="text-xl font-bold text-green-600">Disetujui</p>
            <p class="text-xs text-gray-500 mt-1">Oleh: {{ $report->supervisor->staff_name ?? 'Supervisor' }}</p>
        @elseif($report->approval_status == 'Rejected')
            <p class="text-xl font-bold text-red-600">Ditolak</p>
            <p class="text-xs text-gray-500 mt-1">Oleh: {{ $report->supervisor->staff_name ?? 'Supervisor' }}</p>
            @if($report->notes)
                <div class="mt-2 p-2.5 bg-red-50 border border-red-200 rounded text-xs text-red-800">
                    <strong>Catatan Penolakan:</strong> {{ $report->notes }}
                </div>
            @endif
        @else
            <p class="text-xl font-bold text-amber-600">Pending</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('supervisor.approve', $report->id) }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Yakin ingin menyetujui laporan ini?');" class="bg-green-600 hover:bg-green-700 text-white font-bold py-1.5 px-4 rounded text-xs shadow transition">
                        Setujui Laporan
                    </button>
                </form>

                <button type="button" onclick="document.getElementById('rejectBox').classList.toggle('hidden')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1.5 px-4 rounded text-xs shadow transition">
                    Tolak Laporan
                </button>
            </div>

            <!-- Form Penolakan Inline -->
            <div id="rejectBox" class="hidden mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                <form method="POST" action="{{ route('supervisor.reject', $report->id) }}">
                    @csrf
                    <label class="block text-xs font-bold text-red-800 mb-1">Catatan Penolakan untuk Teller:</label>
                    <textarea name="notes" rows="2" required placeholder="Tuliskan alasan penolakan..."
                        class="w-full p-2 border border-red-300 rounded text-xs focus:outline-none focus:border-red-500 mb-2"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('rejectBox').classList.add('hidden')" class="px-3 py-1 bg-white border border-gray-300 rounded text-xs text-gray-700">
                            Batal
                        </button>
                        <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-bold">
                            Konfirmasi Tolak
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white p-6 border rounded-lg shadow-sm">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Setoran</h3>
        <p class="text-3xl font-bold text-green-600">Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</p>
    </div>
    
    <div class="bg-white p-6 border rounded-lg shadow-sm">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Penarikan</h3>
        <p class="text-3xl font-bold text-red-600">Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</p>
    </div>
</div>

<div class="bg-white p-6 border rounded-lg shadow-sm">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Rincian Transaksi</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-200">
                    <th class="p-3 font-semibold text-gray-700">Waktu</th>
                    <th class="p-3 font-semibold text-gray-700">Kode Transaksi</th>
                    <th class="p-3 font-semibold text-gray-700">Jenis</th>
                    <th class="p-3 font-semibold text-gray-700">Nasabah</th>
                    <th class="p-3 font-semibold text-gray-700">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($trx->transaction_date)->format('H:i:s') }}</td>
                    <td class="p-3 font-mono text-sm text-gray-600">{{ $trx->transaction_code }}</td>
                    <td class="p-3 text-sm font-bold {{ $trx->transaction_type == 'Setoran' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $trx->transaction_type }}
                    </td>
                    <td class="p-3 text-sm text-gray-900">{{ $trx->customer->student_name ?? '-' }}</td>
                    <td class="p-3 font-bold text-gray-900">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-6 text-center text-gray-500">Tidak ada transaksi terperinci (Laporan kosong).</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
