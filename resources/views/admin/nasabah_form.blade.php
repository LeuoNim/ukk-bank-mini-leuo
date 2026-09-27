@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ isset($customer) ? 'Edit Nasabah' : 'Tambah Nasabah Baru' }}</h1>
        <p class="text-gray-600">Formulir data rekening nasabah bank mini sekolah</p>
    </div>
    <a href="{{ route('admin.nasabah') }}" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded font-bold">Kembali</a>
</div>

<div class="bg-white p-6 border rounded-lg shadow-sm max-w-2xl">
    <form method="POST" action="{{ isset($customer) ? route('admin.nasabah.update', $customer->id) : route('admin.nasabah.simpan') }}">
        @csrf
        @if(isset($customer))
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-600 p-4 rounded mb-4 font-bold">
                Ada kesalahan input:
                <ul class="list-disc ml-5 font-normal text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">NIS (Nomor Induk Siswa)</label>
            <input type="text" name="student_id" value="{{ old('student_id', $customer->student_id ?? '') }}" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Nama Siswa</label>
            <input type="text" name="student_name" value="{{ old('student_name', $customer->student_name ?? '') }}" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Kelas</label>
            <input type="text" name="class" value="{{ old('class', $customer->class ?? '') }}" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">PIN Transaksi (6 Digit Angka)</label>
            <input type="password" name="pin" {{ isset($customer) ? '' : 'required' }} minlength="6" maxlength="6"
                class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
            @if(isset($customer))
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah PIN.</p>
            @else
                <p class="text-xs text-gray-500 mt-1">Wajib diisi, misal: 123456</p>
            @endif
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-6 rounded w-full">
                {{ isset($customer) ? 'Simpan Perubahan' : 'Simpan Nasabah' }}
            </button>
        </div>
    </form>
</div>
@endsection
