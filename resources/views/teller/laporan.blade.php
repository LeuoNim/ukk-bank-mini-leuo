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
            <div class="flex justify-between items-center p-3 bg-green-50 rounded border border-green-100">
                <span class="text-gray-700">Total Setoran</span>
                <span class="font-bold text-green-600 text-lg">Rp {{ number_format($total_deposit, 0, ',', '.') }}</span>
            </div>
            
            <div class="flex justify-between items-center p-3 bg-red-50 rounded border border-red-100">
                <span class="text-gray-700">Total Penarikan</span>
                <span class="font-bold text-red-600 text-lg">Rp {{ number_format($total_withdrawal, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="mt-6 border-t pt-4">
            @if($report_today)
                <div class="text-center">
                    <p class="text-sm text-gray-500 mb-2">Status Laporan Hari Ini:</p>
                    @if($report_today->approval_status == 'Pending')
                        <span class="px-4 py-2 bg-amber-100 text-amber-800 rounded-full font-medium inline-block">Menunggu Persetujuan Supervisor</span>
                    @else
                        <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full font-medium inline-block">Disetujui</span>
                    @endif
                </div>
            @else
                <form action="{{ route('teller.laporan.submit') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-gray-900 text-white font-bold py-3 px-4 rounded hover:bg-black">
                        Submit Laporan Ke Supervisor
                    </button>
                </form>
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
