@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-wrap justify-between items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Import Nasabah Massal</h1>
        <p class="text-gray-600 text-sm mt-1">Daftarkan calon nasabah nonaktif melalui CSV, tanpa mencatat penerimaan uang.</p>
    </div>
    <a href="{{ route('admin.nasabah') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-4 rounded border border-gray-300 transition">
        Kembali ke Kelola Nasabah
    </a>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm font-medium">
        {{ session('success') }}
    </div>
@endif

@if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="bg-amber-50 border border-amber-200 text-amber-900 p-4 rounded-lg mb-6 text-sm">
        <h3 class="font-bold mb-2">Catatan Data yang Dilewati:</h3>
        <ul class="list-disc pl-5 space-y-1 text-xs">
            @foreach(session('import_errors') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
        <ul class="list-disc pl-5 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Form Unggah Berkas -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-bold text-gray-800 border-b pb-3 mb-5">Unggah Berkas Data Siswa</h2>

        <form action="{{ route('admin.nasabah.import.process') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Berkas CSV</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition bg-gray-50">
                    <input type="file" name="file" accept=".csv,text/csv,text/plain" required
                        class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-black cursor-pointer">
                    <p class="text-xs text-gray-500 mt-3">
                        Format yang didukung: <strong>CSV (Comma-Separated Values)</strong>. Anda dapat menyimpan langsung dari Microsoft Excel dengan format <em>CSV (Comma delimited) (*.csv)</em>.
                    </p>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white font-medium py-3 px-4 rounded shadow transition text-sm">
                    Mulai Proses Import Nasabah
                </button>
            </div>
        </form>
    </div>

    <!-- Panduan Format Berkas -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-center border-b pb-3 mb-5">
            <h2 class="text-lg font-bold text-gray-800">Panduan Format Kolom</h2>
            <a href="{{ route('admin.nasabah.template') }}" class="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-800 border border-blue-200 bg-blue-50 hover:bg-blue-100 py-1.5 px-3 rounded transition">
                Unduh Template CSV
            </a>
        </div>

        <p class="text-xs text-gray-600 leading-relaxed mb-4">
            Nomor rekening dibuat otomatis. Semua baris baru berstatus nonaktif — belum aktivasi dan bersaldo Rp0. Siswa datang ke teller untuk verifikasi dan setoran awal minimal Rp10.000. Password serta PIN dibuat otomatis saat aktivasi, lalu diserahkan kepada siswa. Kolom pin tetap dipertahankan agar CSV lama kompatibel, tetapi PIN lama tidak digunakan.
        </p>

        <div class="overflow-x-auto border border-gray-200 rounded-lg mb-4">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-700 font-semibold border-b">
                    <tr>
                        <th class="py-2.5 px-3">Kolom</th>
                        <th class="py-2.5 px-3">Nama Field</th>
                        <th class="py-2.5 px-3">Keterangan</th>
                        <th class="py-2.5 px-3">Contoh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-600">
                    <tr>
                        <td class="py-2 px-3 font-mono font-semibold">1</td>
                        <td class="py-2 px-3 font-mono text-gray-900 font-semibold">nis</td>
                        <td class="py-2 px-3">Nomor Induk Siswa (unik)</td>
                        <td class="py-2 px-3 font-mono">2024001</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-3 font-mono font-semibold">2</td>
                        <td class="py-2 px-3 font-mono text-gray-900 font-semibold">nama_siswa</td>
                        <td class="py-2 px-3">Nama lengkap siswa</td>
                        <td class="py-2 px-3">Ahmad Dahlan</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-3 font-mono font-semibold">3</td>
                        <td class="py-2 px-3 font-mono text-gray-900 font-semibold">kelas</td>
                        <td class="py-2 px-3">Kelas & jurusan siswa</td>
                        <td class="py-2 px-3 font-mono">X RPL 1</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-3 font-mono font-semibold">4</td>
                        <td class="py-2 px-3 font-mono text-gray-900 font-semibold">pin</td>
                        <td class="py-2 px-3">Kosongkan; PIN baru dibuat saat aktivasi</td>
                        <td class="py-2 px-3 font-mono">(kosong)</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-3 font-mono font-semibold">5</td>
                        <td class="py-2 px-3 font-mono text-gray-900 font-semibold">setoran_awal</td>
                        <td class="py-2 px-3">Wajib 0 atau kosong; uang diterima teller saat aktivasi</td>
                        <td class="py-2 px-3 font-mono">0</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded p-3 text-xs text-gray-600 leading-relaxed">
            <span class="font-semibold text-gray-800">Penomoran Rekening Otomatis:</span><br>
            Sistem secara otomatis mendeteksi kode jurusan dari nama kelas (misal: RPL menjadi <code>RPL-xxxx</code>, AK/Akuntansi menjadi <code>AK-xxxx</code>, dsb).
        </div>
    </div>
</div>
@endsection
