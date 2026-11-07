@extends('layouts.app')
@section('content')
<div class="max-w-xl bg-white border rounded-lg p-6 space-y-4">
    <h1 class="text-xl font-bold">Kredensial Nasabah</h1>
    <p>{{ $customer->student_name }} — NIS {{ $customer->student_id }} — {{ $customer->account_number }}</p>
    <p>Serahkan secara pribadi kepada siswa yang identitasnya telah diperiksa. Kredensial ini hanya ditampilkan pada hasil proses ini; sistem menyimpan hash, bukan teks aslinya.</p>
    <dl class="space-y-3">
        @if($password !== null)<dt class="font-bold">Password login baru</dt><dd class="font-mono text-lg">{{ $password }}</dd>@endif
        @if($pin !== null)<dt class="font-bold">PIN transaksi baru</dt><dd class="font-mono text-lg">{{ $pin }}</dd>@endif
    </dl>
    <p>Siswa dapat menggantinya di menu Keamanan aplikasi HP atau Profil &amp; Keamanan web. Sesi mobile lama telah dicabut.</p>
    @isset($transaction)
        <p>Aktivasi selesai. Setoran awal Rp {{ number_format($transaction->amount, 0, ',', '.') }} sudah dicatat sekali.</p>
        <a class="inline-block bg-gray-900 text-white px-4 py-2 rounded" href="{{ route('teller.cetak', $transaction->id) }}" target="_blank">Cetak struk setoran</a>
    @endisset
    <a class="inline-block border px-4 py-2 rounded" href="{{ route('teller.transaksi') }}">Kembali ke loket</a>
</div>
@endsection
