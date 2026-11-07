@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Persetujuan Laporan</h1>
    <p class="text-gray-600">Daftar laporan harian dari Teller yang membutuhkan persetujuan Supervisor</p>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded relative mb-4 text-sm font-medium">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded relative mb-4 text-sm font-medium">
        {{ session('error') }}
    </div>
@endif

<div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-200 text-gray-700">
                    <th class="p-3 font-semibold">Tanggal Laporan</th>
                    <th class="p-3 font-semibold">Teller</th>
                    <th class="p-3 font-semibold text-right">Total Setoran</th>
                    <th class="p-3 font-semibold text-right">Total Penarikan</th>
                    <th class="p-3 font-semibold text-center">Status</th>
                    <th class="p-3 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($reports as $report)
                <tr class="hover:bg-gray-50">
                    <td class="p-3 font-medium text-gray-900">{{ \Carbon\Carbon::parse($report->report_date)->format('d M Y') }}</td>
                    <td class="p-3 text-gray-900">{{ $report->teller->staff_name ?? '-' }}</td>
                    <td class="p-3 text-right text-green-600 font-bold">Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</td>
                    <td class="p-3 text-right text-red-600 font-bold">Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</td>
                    <td class="p-3 text-center">
                        @if($report->approval_status === 'Pending')
                            <span class="inline-block bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-1 rounded">Menunggu Persetujuan</span>
                        @elseif($report->approval_status === 'Approved')
                            <span class="inline-block bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded">Disetujui</span>
                        @elseif($report->approval_status === 'Rejected')
                            <span class="inline-block bg-red-100 text-red-800 text-xs font-bold px-2.5 py-1 rounded" title="{{ $report->notes }}">Ditolak</span>
                        @endif
                    </td>
                    <td class="p-3 text-center">
                        <div class="inline-flex items-center gap-1.5 justify-center">
                            @if($report->approval_status === 'Pending')
                                <!-- Form untuk Setujui -->
                                <form method="POST" action="{{ route('supervisor.approve', $report->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Yakin ingin menyetujui laporan ini?');"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-1.5 px-3 rounded shadow transition">
                                        Setujui
                                    </button>
                                </form>

                                <!-- Button untuk Tolak Laporan -->
                                <button type="button" onclick="openRejectModal({{ $report->id }})"
                                    class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-1.5 px-3 rounded shadow transition">
                                    Tolak
                                </button>
                            @endif

                            <a href="{{ route('supervisor.approval.detail', $report->id) }}"
                                class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs py-1.5 px-3 rounded shadow transition inline-block">
                                Detail
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-6 text-center text-gray-500 italic">Belum ada laporan kas harian yang disubmit oleh Teller.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reports->hasPages())
        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $reports->links() }}
        </div>
    @endif
</div>

<!-- Modal Tolak Laporan -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Tolak Laporan Kas Harian</h3>
        <p class="text-xs text-gray-600 mb-4">
            Jelaskan temuan yang dapat diperiksa: selisih nominal, nomor transaksi atau bukti yang belum sesuai, serta tindakan perbaikan yang diperlukan. Alasan pribadi seperti "saya tidak ingin menerima" tidak membantu rekonsiliasi.
        </p>

        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Alasan Penolakan</label>
                <textarea name="notes" rows="3" required placeholder="Contoh: Terdapat selisih kas fisik di laci dengan mutasi sistem..."
                    class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:border-red-500"></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 border border-gray-300 rounded text-xs font-semibold text-gray-700 hover:bg-gray-100 transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded text-xs font-bold shadow transition">
                    Kirim Penolakan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(reportId) {
        const form = document.getElementById('rejectForm');
        form.action = `/supervisor/approval/${reportId}/reject`;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
</script>
@endsection
