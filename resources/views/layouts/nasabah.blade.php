<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Nasabah - Bank Mini Sekolah</title>
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
            <a href="{{ route('nasabah.dashboard') }}" class="block px-4 py-2 rounded {{ request()->routeIs('nasabah.dashboard') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Dashboard</a>
            <a href="{{ route('nasabah.mutasi') }}" class="block px-4 py-2 rounded {{ request()->routeIs('nasabah.mutasi') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Riwayat Mutasi</a>
            <a href="{{ route('nasabah.profil') }}" class="block px-4 py-2 rounded {{ request()->routeIs('nasabah.profil') ? 'bg-amber-500 text-gray-900 font-bold' : 'hover:bg-gray-700' }}">Profil & Keamanan</a>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <form method="POST" action="{{ route('nasabah.logout') }}">
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
                Portal Nasabah
            </div>
            <div class="hidden md:block text-xl font-bold text-gray-300">
                Portal Nasabah
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-amber-500 font-medium">{{ auth('nasabah')->user()->student_name }}</span>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 p-6 bg-white overflow-y-auto text-gray-900">
            @yield('content')
        </main>
    </div>

</body>
</html>
