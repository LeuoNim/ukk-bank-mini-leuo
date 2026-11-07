<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bank Mini Sekolah</title>
    @vite('resources/css/app.css')
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans p-4">
    <div class="w-full max-w-sm p-6 sm:p-8 bg-white border border-gray-200 shadow-md rounded-xl">
        <!-- Logo & Header -->
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo-smk.png') }}" alt="Logo SMK"
                class="w-16 h-16 mx-auto mb-3 object-contain">
            <h1 class="text-xl font-bold text-gray-900 tracking-tight">Bank Mini Sekolah</h1>
            <p class="text-gray-500 text-xs mt-0.5">SMK Negeri 1 Bantul</p>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded-lg mb-4 text-xs font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 p-3 rounded-lg mb-4 text-xs font-medium">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-gray-800 text-xs font-bold mb-1.5 uppercase tracking-wide">
                    Username / NIS Siswa
                </label>
                <input type="text" name="username"
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 text-gray-900 text-sm placeholder-gray-400 transition"
                    placeholder="Username Petugas atau NIS Siswa" value="{{ old('username') }}"
                    required autofocus>
            </div>

            <div>
                <label class="block text-gray-800 text-xs font-bold mb-1.5 uppercase tracking-wide">
                    Password / PIN
                </label>
                <input type="password" name="password"
                    placeholder="Masukkan sandi akun Anda"
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 text-gray-900 text-sm placeholder-gray-400 transition"
                    required>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full bg-gray-900 hover:bg-black text-white font-bold py-3 px-4 rounded-lg transition-colors text-sm shadow-md flex items-center justify-center">
                    Masuk ke Sistem
                </button>
            </div>
        </form>

        <div class="mt-5 pt-3 border-t border-gray-100 text-center">
            <p class="text-[11px] text-gray-500">
                <span class="font-semibold text-gray-600">Info Petugas (Teller / Supervisor):</span><br>
                Jika lupa kata sandi akun, hubungi Administrator Sistem untuk mereset kata sandi Anda.
            </p>
        </div>
    </div>
</body>

</html>
