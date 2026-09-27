@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Persetujuan Laporan</h1>
    <p class="text-gray-600">Daftar laporan harian dari Teller yang membutuhkan persetujuan</p>
</div>

<div class="bg-white p-6 border rounded-lg shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-200">
                    <th class="p-3 font-semibold text-gray-700">Tanggal Laporan</th>
                    <th class="p-3 font-semibold text-gray-700">Teller</th>
                    <th class="p-3 font-semibold text-gray-700">Total Setoran</th>
                    <th class="p-3 font-semibold text-gray-700">Total Penarikan</th>
                    <th class="p-3 font-semibold text-gray-700">Status</th>
                    <th class="p-3 font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 font-medium text-gray-900">{{ \Carbon\Carbon::parse($report->report_date)->format('d M Y') }}</td>
                    <td class="p-3 text-gray-900">{{ $report->teller->staff_name }}</td>
                    <td class="p-3 text-green-600 font-bold">Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</td>
                    <td class="p-3 text-red-600 font-bold">Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</td>
                    <td class="p-3">
                        @if($report->approval_status === 'Pending')
                            <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2 py-1 rounded">Pending</span>
                        @else
                            <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Disetujui</span>
                        @endif
                    </td>
                    <td class="p-3">
                        @if($report->approval_status === 'Pending')
                            <!-- Form untuk Approve -->
                            <form method="POST" action="{{ route('supervisor.approve', $report->id) }}" class="inline" id="form-approve-{{ $report->id }}">
                                @csrf
                                <button type="submit" onclick="return confirm('Yakin ingin menyetujui laporan ini?');" class="bg-green-500 hover:bg-green-600 text-white text-xs font-bold py-1 px-3 rounded">Approve</button>
                            </form>
                        @endif
                        <a href="{{ route('supervisor.approval.detail', $report->id) }}" class="text-amber-600 hover:text-amber-800 text-sm font-bold ml-2">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-6 text-center text-gray-500">Belum ada laporan yang disubmit.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
