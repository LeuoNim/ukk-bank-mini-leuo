@extends('layouts.app')

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kelola Nasabah</h1>
            <p class="text-gray-600">Data rekening nasabah bank mini sekolah</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.nasabah.import') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm flex items-center gap-1.5 shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import Massal
            </a>
            <a href="{{ route('admin.nasabah.tambah') }}"
                class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-4 rounded text-sm shadow">
                + Tambah Nasabah
            </a>
        </div>
    </div>

    @if(session('success'))<p class="bg-green-100 text-green-800 rounded p-3 mb-4">{{ session('success') }}</p>@endif
    @if($errors->any())
        <div class="bg-red-100 text-red-800 rounded p-3 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <div class="bg-white p-6 border rounded-lg shadow-sm">
        <form method="GET" action="{{ route('admin.nasabah') /* redirect akan dihandle ke nasabah */ }}"
            class="mb-6 flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Cari NIS, Nama, atau No Rekening..."
                class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-amber-500 text-gray-900">
            <button type="submit" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded font-bold">Cari</button>
            @if(request('search'))
                <a href="/admin/nasabah"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded font-bold">Reset</a>
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
                        <th class="p-3 font-semibold text-gray-700">Status</th>
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
                                @if($c->status === 'Aktif')
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Aktif</span>
                                @elseif($c->status === \App\Models\Customer::STATUS_PENDING)
                                    <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2 py-1 rounded">Nonaktif — belum aktivasi</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">Diblokir</span>
                                @endif
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <a href="{{ route('admin.nasabah.qr', $c->id) }}"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-bold mr-2" target="_blank">QR</a>
                                <a href="{{ route('admin.nasabah.edit', $c->id) }}"
                                    class="text-amber-600 hover:text-amber-800 text-sm font-bold mr-2">Edit</a>

                                @if($c->status === \App\Models\Customer::STATUS_PENDING)
                                    <span class="text-xs text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded font-medium inline-block">Aktivasi di Teller</span>
                                @elseif($c->status === 'Aktif')
                                    <form action="{{ route('admin.nasabah.status', $c->id) }}" method="POST" class="inline mr-2">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" onclick="return confirm('Nonaktifkan/Blokir nasabah ini?')" class="text-red-600 hover:text-red-800 text-sm font-bold">Blokir</button>
                                    </form>
                                    @if($c->balance == 0)
                                        <form action="{{ route('admin.nasabah.pending', $c->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" onclick="return confirm('Kembalikan rekening ini ke status Belum Aktivasi?')" class="text-xs text-gray-500 hover:text-gray-700 underline">Tandai Belum Aktivasi</button>
                                        </form>
                                    @endif
                                @else
                                    <form action="{{ route('admin.nasabah.status', $c->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" onclick="return confirm('Buka blokir dan aktifkan kembali nasabah ini?')" class="text-green-600 hover:text-green-800 text-sm font-bold">Buka Blokir</button>
                                    </form>
                                @endif
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

        @if($customers->hasPages())
            <div class="mt-4 pt-4 border-t border-gray-100">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
@endsection
