@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Laporan Harian</h1>
    <p class="text-gray-600">Rekap transaksi hari ini: {{ date('d F Y') }}</p>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- Ringkasan -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-bold border-b pb-2 mb-4">Ringkasan Hari Ini</h2>
        
        <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded border border-gray-200">
                <span class="text-gray-700">Saldo Awal (Kas Kemarin)</span>
                <span class="font-bold text-gray-800 text-lg">Rp {{ number_format($initial_balance, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between items-center p-3 bg-green-50 rounded border border-green-100">
                <span class="text-gray-700">Total Setoran (+)</span>
                <span class="font-bold text-green-600 text-lg">Rp {{ number_format($total_deposit, 0, ',', '.') }}</span>
            </div>
            
            <div class="flex justify-between items-center p-3 bg-red-50 rounded border border-red-100">
                <span class="text-gray-700">Total Penarikan (-)</span>
                <span class="font-bold text-red-600 text-lg">Rp {{ number_format($total_withdrawal, 0, ',', '.') }}</span>
            </div>

            @php
                $isNegative = $final_balance < 0;
            @endphp
            <div class="flex justify-between items-center p-3.5 {{ $isNegative ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200' }} rounded border">
                <div>
                    <span class="{{ $isNegative ? 'text-red-900' : 'text-blue-900' }} font-bold block">Saldo Akhir Sistem (=)</span>
                    @if($isNegative)
                        <span class="text-xs text-red-600 italic">Posisi kas minus karena penarikan melebihi setoran kas hari ini.</span>
                    @endif
                </div>
                <span class="font-bold {{ $isNegative ? 'text-red-700' : 'text-blue-700' }} text-xl" id="finalBalanceText" data-amount="{{ $final_balance }}">Rp {{ number_format($final_balance, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="mt-6 border-t pt-4">
            @if($report_today && $report_today->approval_status !== 'Rejected')
                <div class="text-center">
                    <p class="text-sm text-gray-500 mb-2">Status Laporan Hari Ini:</p>
                    @if($report_today->approval_status == 'Pending')
                        <span class="px-4 py-2 bg-amber-100 text-amber-800 rounded-full font-medium inline-block text-sm">Menunggu Persetujuan Supervisor</span>
                    @else
                        <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full font-medium inline-block text-sm">Disetujui</span>
                    @endif
                </div>
            @else
                @if($report_today && $report_today->approval_status === 'Rejected')
                    <div class="bg-red-50 border border-red-200 text-red-800 p-3.5 rounded-lg mb-4 text-left">
                        <span class="font-bold block text-sm">Laporan Ditolak oleh Supervisor</span>
                        @if($report_today->notes)
                            <p class="text-xs text-red-700 mt-1"><strong>Catatan:</strong> {{ $report_today->notes }}</p>
                        @endif
                        <p class="text-xs text-red-600 mt-1 italic">Silakan lakukan rekonsiliasi kas dan ajukan ulang laporan tutup kas di bawah ini.</p>
                    </div>
                @endif

                @if($total_deposit == 0 && $total_withdrawal == 0)
                    <div class="bg-gray-100 text-gray-500 text-center py-3 px-4 rounded font-medium border border-gray-200 text-sm">
                        Belum ada transaksi hari ini.<br>
                        <span class="text-xs">Laporan tidak dapat di-submit.</span>
                    </div>
                @else
                    <form action="{{ route('teller.laporan.submit') }}" method="POST" id="formLaporan">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Fisik Kas Teller (Rp)</label>
                            <input type="number" id="physicalCash" name="physical_cash" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm" required placeholder="Ketik {{ $final_balance }}">
                            <p class="text-xs text-gray-500 mt-1">
                                Masukkan angka nominal sesuai Saldo Akhir Sistem (<strong>{{ $final_balance }}</strong>) untuk mengonfirmasi tutup kas.
                            </p>
                            <p id="cashWarning" class="text-red-500 text-xs italic mt-1 hidden">Nominal konfirmasi tidak cocok dengan perhitungan sistem!</p>
                        </div>
                        <button type="submit" id="btnSubmitLaporan" class="w-full bg-gray-900 text-white font-bold py-3 px-4 rounded hover:bg-black opacity-50 cursor-not-allowed text-sm" disabled>
                            {{ ($report_today && $report_today->approval_status === 'Rejected') ? 'Ajukan Ulang Laporan Tutup Kas' : 'Submit Laporan Ke Supervisor' }}
                        </button>
                    </form>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const physicalCash = document.getElementById('physicalCash');
                            const btnSubmit = document.getElementById('btnSubmitLaporan');
                            const cashWarning = document.getElementById('cashWarning');
                            const finalBalance = parseInt(document.getElementById('finalBalanceText').getAttribute('data-amount'), 10);

                            physicalCash.addEventListener('input', function() {
                                const inputVal = parseInt(this.value, 10);
                                if (inputVal === finalBalance) {
                                    btnSubmit.disabled = false;
                                    btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
                                    cashWarning.classList.add('hidden');
                                } else {
                                    btnSubmit.disabled = true;
                                    btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
                                    cashWarning.classList.remove('hidden');
                                }
                            });
                        });
                    </script>
                @endif
            @endif
        </div>
    </div>
</div>

<div class="bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">
    <div class="p-4 bg-gray-50 border-b">
        <h2 class="font-bold text-gray-700">Riwayat Laporan Sebelumnya</h2>
    </div>
    
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-100 text-gray-600 text-sm">
                <th class="py-3 px-4 border-b">Tanggal</th>
                <th class="py-3 px-4 border-b">Setoran</th>
                <th class="py-3 px-4 border-b">Penarikan</th>
                <th class="py-3 px-4 border-b">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reports as $report)
            <tr class="hover:bg-gray-50 border-b text-sm">
                <td class="py-3 px-4">{{ \Carbon\Carbon::parse($report->report_date)->format('d/m/Y') }}</td>
                <td class="py-3 px-4 text-green-600">Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</td>
                <td class="py-3 px-4 text-red-600">Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</td>
                <td class="py-3 px-4">
                    @if($report->approval_status == 'Approved')
                        <span class="text-green-600 font-medium">Disetujui</span>
                    @else
                        <span class="text-amber-600 font-medium">Pending</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="py-8 text-center text-gray-500">Belum ada riwayat laporan</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
