@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ isset($customer) ? 'Edit Data Nasabah' : 'Tambah Nasabah Baru' }}</h1>
        <p class="text-gray-600">Formulir pendaftaran dan pembaruan data rekening nasabah SMK Negeri 1 Bantul</p>
    </div>
    <a href="{{ route('admin.nasabah') }}" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded font-bold text-sm">Kembali</a>
</div>

<div class="bg-white p-6 border rounded-xl shadow-sm max-w-2xl">
    <form method="POST" action="{{ isset($customer) ? route('admin.nasabah.update', $customer->id) : route('admin.nasabah.simpan') }}" id="formNasabah">
        @csrf
        @if(isset($customer))
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-300 text-red-700 p-4 rounded-lg mb-5 text-sm">
                <p class="font-bold mb-1">Terdapat kesalahan input:</p>
                <ul class="list-disc ml-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-1.5 text-sm">NIS (Nomor Induk Siswa)</label>
            <input type="text" name="student_id" id="student_id"
                value="{{ old('student_id', $customer->student_id ?? '') }}" required
                pattern="[0-9]{4,12}"
                placeholder="Contoh: 2024001 (hanya angka)"
                class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm">
            <span class="text-xs text-gray-500 mt-1 block">Wajib diisi berupa angka NIS resmi siswa (4 - 12 digit).</span>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-1.5 text-sm">Nama Lengkap Siswa</label>
            <input type="text" name="student_name" id="student_name"
                value="{{ old('student_name', $customer->student_name ?? '') }}" required minlength="3"
                placeholder="Contoh: Muhammad Reno Cahya"
                class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm">
            <span class="text-xs text-gray-500 mt-1 block">Nama resmi siswa sesuai data pokok pendidikan.</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 font-bold mb-1.5 text-sm">Kelas Siswa</label>
                <input type="text" name="class" id="class_input"
                    value="{{ old('class', $customer->class ?? '') }}" required
                    placeholder="Contoh: XII RPL 1 atau X AKL 2"
                    class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm uppercase">
                <span class="text-xs text-gray-500 mt-1 block">Format: [Tingkat] [Jurusan] [Nomor Kelas]</span>
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-1.5 text-sm">Program Keahlian / Jurusan</label>
                <select name="jurusan" id="jurusan_select"
                    class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm bg-gray-50">
                    <option value="">-- Pilih / Terisi Otomatis --</option>
                    <option value="RPL">Rekayasa Perangkat Lunak (RPL)</option>
                    <option value="AKL">Akuntansi dan Keuangan Lembaga (AKL)</option>
                    <option value="MPLB">Manajemen Perkantoran dan Layanan Bisnis (MPLB)</option>
                    <option value="BD">Bisnis Digital (BD)</option>
                    <option value="DKV">Desain Komunikasi Visual (DKV)</option>
                    <option value="TKJ">Teknik Komputer dan Jaringan (TKJ)</option>
                </select>
                <span class="text-xs text-gray-500 mt-1 block">Otomatis terpilih saat kelas diketik.</span>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-lg mb-6 text-xs text-amber-800">
            <strong>Catatan Keamanan Pendaftaran:</strong><br>
            Nasabah baru disimpan dengan saldo Rp0. Aktivasi resmi, setoran awal tabungan, dan penyerahan kredensial awal (Password &amp; PIN) dilakukan di loket Teller saat siswa hadir, atau diaktifkan langsung oleh Administrator pada menu Kelola Nasabah.
        </div>

        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-6 rounded-lg w-full text-sm shadow transition">
            {{ isset($customer) ? 'Simpan Perubahan Data' : 'Simpan Nasabah Baru' }}
        </button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const classInput = document.getElementById('class_input');
        const jurusanSelect = document.getElementById('jurusan_select');

        function detectJurusan(className) {
            const upper = className.toUpperCase();
            if (upper.includes('RPL')) return 'RPL';
            if (upper.includes('AKL') || upper.includes('AK')) return 'AKL';
            if (upper.includes('MPLB') || upper.includes('MP') || upper.includes('OTKP')) return 'MPLB';
            if (upper.includes('DKV')) return 'DKV';
            if (upper.includes('TKJ')) return 'TKJ';
            if (upper.includes('BD') || upper.includes('BDP') || upper.includes('BR')) return 'BD';
            return '';
        }

        // Sinkronisasi dari input Kelas ke dropdown Jurusan
        classInput.addEventListener('input', function() {
            const detected = detectJurusan(this.value);
            if (detected) {
                jurusanSelect.value = detected;
            }
        });

        // Sinkronisasi dari dropdown Jurusan ke input Kelas jika kelas masih kosong
        jurusanSelect.addEventListener('change', function() {
            const jur = this.value;
            if (jur && !classInput.value.trim()) {
                classInput.value = 'X ' + jur + ' 1';
            }
        });

        // Jalankan saat halaman pertama kali dibuka
        if (classInput.value) {
            const initialJur = detectJurusan(classInput.value);
            if (initialJur) jurusanSelect.value = initialJur;
        }
    });
</script>
@endsection
