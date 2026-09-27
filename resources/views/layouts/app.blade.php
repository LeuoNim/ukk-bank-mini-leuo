<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Mini Sekolah</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 flex min-h-screen font-sans">

    <!-- Sidebar -->
    <div class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
        <div class="p-4 text-center font-bold text-xl border-b border-gray-700">
            <img src="{{ asset('images/logo-smk.png') }}" alt="Logo" class="w-16 h-16 mx-auto mb-2 object-contain">
            Bank Mini
        </div>
        <nav class="flex-1 p-4 space-y-2">
            @if(auth()->user()->role === 'Teller')
                <a href="{{ route('teller.transaksi') }}" class="block px-4 py-2 rounded {{ request()->routeIs('teller.transaksi') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Loket Transaksi</a>
                <a href="{{ route('teller.riwayat') }}" class="block px-4 py-2 rounded {{ request()->routeIs('teller.riwayat') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Riwayat Transaksi</a>
                <a href="{{ route('teller.laporan') }}" class="block px-4 py-2 rounded {{ request()->routeIs('teller.laporan') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Laporan Harian</a>
            @elseif(auth()->user()->role === 'Supervisor')
                <a href="{{ route('supervisor.dashboard') }}" class="block px-4 py-2 rounded {{ request()->routeIs('supervisor.dashboard') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Dashboard</a>
                <a href="{{ route('supervisor.approval') }}" class="block px-4 py-2 rounded {{ request()->routeIs('supervisor.approval') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Persetujuan Laporan</a>
                <a href="{{ route('supervisor.audit') }}" class="block px-4 py-2 rounded {{ request()->routeIs('supervisor.audit') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Log Audit</a>
            @elseif(auth()->user()->role === 'Administrator')
                <a href="{{ route('admin.nasabah') }}" class="block px-4 py-2 rounded {{ request()->routeIs('admin.nasabah') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Kelola Nasabah</a>
                <a href="{{ route('admin.petugas') }}" class="block px-4 py-2 rounded {{ request()->routeIs('admin.petugas') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Kelola Petugas</a>
                <a href="{{ route('admin.audit') }}" class="block px-4 py-2 rounded {{ request()->routeIs('admin.audit') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Log Audit</a>
            @endif
        </nav>
        <div class="p-4 border-t border-gray-700">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-700 text-red-400 rounded transition-colors">Keluar</button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <!-- Topbar -->
        <header class="bg-gray-900 text-white p-4 flex justify-between items-center shadow-md">
            <div class="md:hidden font-bold flex items-center gap-2">
                <img src="{{ asset('images/logo-smk.png') }}" alt="Logo" class="w-8 h-8 object-contain">
                Bank Mini
            </div>
            <div class="hidden md:block text-xl font-bold text-gray-300">
                Sistem Perbankan Sekolah
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-amber-500 font-medium">{{ auth()->user()->staff_name }} ({{ auth()->user()->role }})</span>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 p-6 bg-white overflow-y-auto text-gray-900">
            @yield('content')
        </main>
    </div>

</body>
</html>
