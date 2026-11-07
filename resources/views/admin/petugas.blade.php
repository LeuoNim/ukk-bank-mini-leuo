@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Kelola Petugas</h1>
        <p class="text-gray-600">Data petugas bank mini sekolah (Teller, Supervisor, Admin)</p>
    </div>
    <a href="{{ route('admin.petugas.tambah') }}" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-4 rounded">
        + Tambah Petugas
    </a>
</div>

<div class="bg-white p-6 border rounded-lg shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-200">
                    <th class="p-3 font-semibold text-gray-700">Username</th>
                    <th class="p-3 font-semibold text-gray-700">Nama Petugas</th>
                    <th class="p-3 font-semibold text-gray-700">Status</th>
                    <th class="p-3 font-semibold text-gray-700">Role</th>
                    <th class="p-3 font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 font-mono text-sm text-gray-900">{{ $user->username }}</td>
                    <td class="p-3 font-medium text-gray-900">{{ $user->staff_name }}</td>
                    <td class="p-3">
                        @if($user->status === 'Aktif')
                            <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Aktif</span>
                        @else
                            <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">Diblokir</span>
                        @endif
                    </td>
                    <td class="p-3">
                        @if($user->role === 'Administrator')
                            <span class="bg-gray-200 text-gray-800 text-xs font-bold px-2 py-1 rounded">Admin</span>
                        @elseif($user->role === 'Supervisor')
                            <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2 py-1 rounded">Supervisor</span>
                        @else
                            <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Teller</span>
                        @endif
                    </td>
                    <td class="p-3 whitespace-nowrap">
                        <a href="{{ route('admin.petugas.edit', $user->id) }}" class="text-amber-600 hover:text-amber-800 text-sm font-bold mr-2">Edit</a>
                        @if($user->id !== auth()->id())
                            <form action="{{ route('admin.petugas.reset_password', $user->id) }}" method="POST" class="inline mr-2">
                                @csrf
                                @method('PATCH')
                                <button type="submit" onclick="return confirm('Reset kata sandi petugas {{ $user->staff_name }} ke password default (password123)?')" class="text-blue-600 hover:text-blue-800 text-sm font-bold">Reset Sandi</button>
                            </form>
                            <form action="{{ route('admin.petugas.status', $user->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" onclick="return confirm('Ubah status petugas ini?')" class="{{ $user->status === 'Aktif' ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }} text-sm font-bold">{{ $user->status === 'Aktif' ? 'Blokir' : 'Aktifkan' }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-6 text-center text-gray-500">Data petugas tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
