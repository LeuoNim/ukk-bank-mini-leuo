@extends('layouts.app')
@section('content')
    <div class="max-w-xl mx-auto bg-white border border-gray-200 rounded-xl p-6 lg:p-8 shadow-sm space-y-5">
        <div class="border-b pb-4 flex justify-between items-start">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Kredensial Resmi Nasabah</h1>
                <p class="text-xs text-gray-500 mt-1">Bank Mini Sekolah — SMK Negeri 1 Bantul</p>
            </div>
            <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-1 rounded-full">Kerahasiaan
                Terjaga</span>
        </div>

        <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg text-sm space-y-1.5">
            <p><span class="text-gray-500 font-medium">Nama Siswa:</span> <strong
                    class="text-gray-900">{{ $customer->student_name }}</strong></p>
            <p><span class="text-gray-500 font-medium">NIS:</span> <strong
                    class="font-mono text-gray-900">{{ $customer->student_id }}</strong></p>
            <p><span class="text-gray-500 font-medium">No. Rekening:</span> <strong
                    class="font-mono text-gray-900">{{ $customer->account_number }}</strong></p>
            <p><span class="text-gray-500 font-medium">Kelas:</span> <span
                    class="font-medium text-gray-800">{{ $customer->class }}</span></p>
        </div>

        <div class="space-y-3 bg-amber-50/70 border border-amber-200 p-4 rounded-lg">
            @if($password !== null)
                <div>
                    <span class="text-xs font-bold text-amber-900 uppercase tracking-wide block mb-1">Password Login
                        Baru:</span>
                    <div
                        class="font-mono text-lg font-bold bg-white px-3 py-1.5 rounded border border-amber-300 text-gray-900 inline-block select-all">
                        {{ $password }}
                    </div>
                </div>
            @endif
            @if($pin !== null)
                <div>
                    <span class="text-xs font-bold text-amber-900 uppercase tracking-wide block mb-1">PIN Transaksi Baru (6
                        Digit):</span>
                    <div
                        class="font-mono text-lg font-bold bg-white px-3 py-1.5 rounded border border-amber-300 text-emerald-700 tracking-widest inline-block select-all">
                        {{ $pin }}
                    </div>
                </div>
            @endif
            <p class="text-xs text-amber-800 pt-1 leading-relaxed">
                * Serahkan informasi kredensial ini langsung kepada siswa bersangkutan. Sesi aktif lama pada aplikasi mobile
                telah dicabut otomatis demi keamanan.
            </p>
        </div>

        @isset($transaction)
            <div
                class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-800 flex items-center justify-between">
                <span>Aktivasi selesai! Setoran awal tunai <strong>Rp
                        {{ number_format($transaction->amount, 0, ',', '.') }}</strong> telah dibukukan.</span>
                <a class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded shadow-sm transition"
                    href="{{ route('teller.cetak', $transaction->id) }}" target="_blank">
                    Cetak Struk Setoran
                </a>
            </div>
        @endisset

        <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
            <a class="bg-gray-900 hover:bg-black text-white text-xs font-bold px-5 py-2.5 rounded-lg transition flex items-center shadow-sm"
                href="{{ route('teller.transaksi') }}">
                Kembali ke Loket Transaksi
            </a>
        </div>
    </div>
@endsection