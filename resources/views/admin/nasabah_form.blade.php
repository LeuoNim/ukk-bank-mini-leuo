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
            <label class="block text-gray-700 font-bold mb-1.5 text-sm">
                NIS (Nomor Induk Siswa)
                @if(isset($customer))
                    <span class="text-xs font-normal text-amber-700 ml-1">(Permanen / Tidak dapat diubah)</span>
                @endif
            </label>
            <input type="text" name="student_id" id="student_id"
                value="{{ old('student_id', $customer->student_id ?? '') }}" required
                pattern="[0-9]{4,12}"
                placeholder="Contoh: 2024001 (hanya angka)"
                {{ isset($customer) ? 'readonly' : '' }}
                class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm {{ isset($customer) ? 'bg-gray-100 text-gray-500 cursor-not-allowed select-none' : '' }}">
            <span class="text-xs text-gray-500 mt-1 block">
                {{ isset($customer) ? 'NIS resmi siswa bersifat unik dan tidak dapat diubah demi integritas data rekening.' : 'Wajib diisi berupa angka NIS resmi siswa (4 - 12 digit).' }}
            </span>
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
                    <option value="AKL">Akuntansi dan Keuangan Lembaga (AKL)</option>
                    <option value="LPS">Layanan Perbankan Syariah (LPS)</option>
                    <option value="MP">Manajemen Perkantoran (MP)</option>
                    <option value="BR">Bisnis Ritel (BR)</option>
                    <option value="BD">Bisnis Digital (BD)</option>
                    <option value="TKJ">Teknik Komputer dan Jaringan (TKJ)</option>
                    <option value="DKV">Desain Komunikasi Visual (DKV)</option>
                    <option value="RPL">Rekayasa Perangkat Lunak (RPL)</option>
                </select>
                <span class="text-xs text-gray-500 mt-1 block">Otomatis terpilih saat kelas diketik.</span>
            </div>
        </div>

        @if(!isset($customer))
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-1.5 text-sm">
                    Setoran Awal (Rp)
                    <span class="text-xs font-normal text-amber-700 ml-1">(Opsional — Minimal Rp 10.000 jika langsung diaktifkan)</span>
                </label>
                <input type="number" name="initial_deposit" id="initial_deposit"
                    value="{{ old('initial_deposit', 0) }}" min="0" step="1000"
                    placeholder="Contoh: 10000"
                    class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                <span class="text-xs text-gray-500 mt-1 block">
                    Jika diisi nominal &ge; Rp 10.000, rekening langsung Aktif dan sistem langsung membuat Password &amp; PIN. Jika dibiarkan 0, status nasabah adalah "Belum Aktivasi" dan setoran dilakukan di loket Teller.
                </span>
            </div>
        @endif

        <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-lg mb-6 text-xs text-amber-800">
            <strong>Catatan Fleksibilitas Pendaftaran:</strong><br>
            Anda dapat mendaftarkan nasabah langsung aktif dengan mengisi Setoran Awal (min. Rp 10.000), atau mendaftarkannya terlebih dahulu (saldo Rp0) agar setoran fisik diserahkan langsung oleh siswa ke Teller.
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
            if (upper.includes('AKL') || upper.includes('AK')) return 'AKL';
            if (upper.includes('LPS') || upper.includes('PS')) return 'LPS';
            if (upper.includes('MPLB') || upper.includes('MP') || upper.includes('OTKP')) return 'MP';
            if (upper.includes('BR') || upper.includes('RITEL') || upper.includes('BDP')) return 'BR';
            if (upper.includes('BD') || upper.includes('DIGITAL')) return 'BD';
            if (upper.includes('TKJ')) return 'TKJ';
            if (upper.includes('DKV')) return 'DKV';
            if (upper.includes('RPL')) return 'RPL';
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
