@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Kelola Nasabah</h1>
        <p class="text-gray-600">Data rekening nasabah bank mini sekolah</p>
    </div>
    <a href="{{ route('admin.nasabah.tambah') }}" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-4 rounded">
        + Tambah Nasabah
    </a>
</div>

<div class="bg-white p-6 border rounded-lg shadow-sm">
    <form method="GET" action="{{ route('home') /* redirect akan dihandle ke nasabah */ }}" class="mb-6 flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NIS, Nama, atau No Rekening..." class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-amber-500 text-gray-900">
        <button type="submit" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded font-bold">Cari</button>
        @if(request('search'))
            <a href="/admin/nasabah" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded font-bold">Reset</a>
        @endif
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-200">
                    <th class="p-3 font-semibold text-gray-700">No. Rekening</th>
                    <th class="p-3 font-semibold text-gray-700">NIS</th>
                    <th class="p-3 font-semibold text-gray-700">Nama Siswa</th>
                    <th class="p-3 font-semibold text-gray-700">Kelas</th>
                    <th class="p-3 font-semibold text-gray-700">Saldo</th>
                    <th class="p-3 font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 font-mono text-sm text-gray-900">{{ $c->account_number }}</td>
                    <td class="p-3 text-gray-900">{{ $c->student_id }}</td>
                    <td class="p-3 font-medium text-gray-900">{{ $c->student_name }}</td>
                    <td class="p-3 text-gray-600">{{ $c->class }}</td>
                    <td class="p-3 font-bold text-gray-900">Rp {{ number_format($c->balance, 0, ',', '.') }}</td>
                    <td class="p-3">
                        <a href="{{ route('admin.nasabah.qr', $c->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-bold mr-2" target="_blank">QR</a>
                        <a href="{{ route('admin.nasabah.edit', $c->id) }}" class="text-amber-600 hover:text-amber-800 text-sm font-bold mr-2">Edit</a>
                        <form action="{{ route('admin.nasabah.hapus', $c->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Hapus nasabah ini?')" class="text-red-600 hover:text-red-800 text-sm font-bold">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-6 text-center text-gray-500">Data nasabah tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
