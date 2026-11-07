@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Log Audit Jurnal</h1>
    <p class="text-gray-600">Catatan jurnal akuntansi seluruh transaksi bank mini</p>
</div>

<!-- Filter -->
<div class="bg-white p-6 border rounded-lg shadow-sm mb-6">
    <form action="{{ route('supervisor.audit') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal</label>
            <input type="date" name="date" value="{{ request('date') }}" class="px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Teller</label>
            <select name="teller_id" class="px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
                <option value="">Semua Teller</option>
                @foreach($tellers as $t)
                    <option value="{{ $t->id }}" {{ request('teller_id') == $t->id ? 'selected' : '' }}>{{ $t->staff_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-700 font-bold">Filter</button>
            <a href="{{ route('supervisor.audit') }}" class="ml-2 text-gray-600 hover:text-gray-900 text-sm">Reset</a>
        </div>
    </form>
</div>

<!-- Ringkasan Keseimbangan Jurnal -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white p-6 border rounded-lg shadow-sm border-l-4 {{ $total_debet === $total_kredit ? 'border-l-green-500' : 'border-l-red-500' }}">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Debet</h3>
        <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($total_debet, 0, ',', '.') }}</p>
    </div>
    <div class="bg-white p-6 border rounded-lg shadow-sm border-l-4 {{ $total_debet === $total_kredit ? 'border-l-green-500' : 'border-l-red-500' }}">
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Total Kredit</h3>
        <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($total_kredit, 0, ',', '.') }}</p>
    </div>
</div>

@if($total_debet !== $total_kredit)
<div class="bg-red-100 text-red-600 p-4 rounded mb-6 font-bold flex items-center">
    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
    PERINGATAN: Total Debet dan Kredit tidak seimbang (Unbalanced). Terdapat indikasi kesalahan pencatatan transaksi!
</div>
@else
<div class="bg-green-100 text-green-700 p-4 rounded mb-6 font-bold flex items-center">
    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
    Jurnal seimbang (Balanced).
</div>
@endif

<div class="bg-white p-6 border rounded-lg shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-200">
                    <th class="p-3 font-semibold text-gray-700">No. Transaksi</th>
                    <th class="p-3 font-semibold text-gray-700">Waktu</th>
                    <th class="p-3 font-semibold text-gray-700">Kode Akun</th>
                    <th class="p-3 font-semibold text-gray-700">Teller</th>
                    <th class="p-3 font-semibold text-gray-700">Nasabah</th>
                    <th class="p-3 font-semibold text-gray-700 text-right">Debet</th>
                    <th class="p-3 font-semibold text-gray-700 text-right">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($journals as $j)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 font-mono text-xs text-gray-600">{{ $j->transaction->transaction_code }}</td>
                    <td class="p-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($j->created_at)->format('d M Y H:i') }}</td>
                    <td class="p-3 text-sm font-bold text-gray-900">
                        {{ $j->account_code }}
                        <span class="text-xs font-normal text-gray-500 block">
                            {{ $j->account_code == '111' ? 'Kas Bank' : 'Titipan Nasabah' }}
                        </span>
                    </td>
                    <td class="p-3 text-sm text-gray-900">{{ $j->transaction->user->staff_name ?? '-' }}</td>
                    <td class="p-3 text-sm text-gray-900">{{ $j->transaction->customer->student_name ?? '-' }}</td>
                    <td class="p-3 text-right font-medium text-gray-900">
                        {{ $j->position === 'Debet' ? 'Rp '.number_format($j->amount, 0, ',', '.') : '-' }}
                    </td>
                    <td class="p-3 text-right font-medium text-gray-900">
                        {{ $j->position === 'Kredit' ? 'Rp '.number_format($j->amount, 0, ',', '.') : '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-6 text-center text-gray-500">Belum ada catatan jurnal.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($journals->hasPages())
        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $journals->links() }}
        </div>
    @endif
</div>
@endsection
