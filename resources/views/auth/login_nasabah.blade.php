<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Nasabah - Bank Mini Sekolah</title>
    @vite('resources/css/app.css')
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md border-t-4 border-amber-500">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo-smk.png') }}" alt="Logo" class="w-24 h-24 mx-auto mb-4 object-contain">
            <h1 class="text-2xl font-bold text-gray-900">Portal Nasabah</h1>
            <p class="text-gray-500 text-sm">Bank Mini Sekolah</p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 text-sm" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm" role="alert">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('nasabah.login') }}">
            @csrf

            <div class="mb-4">
                <label for="student_id" class="block text-sm font-bold text-gray-700 mb-2">NIS atau Nomor Rekening</label>
                <input type="text" name="student_id" id="student_id" value="{{ old('student_id') }}" required autofocus
                    placeholder="Contoh: 2024001 atau RPL-0001"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
            </div>

            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <label for="password" class="block text-sm font-bold text-gray-700">Password / PIN</label>
                    <a href="{{ route('nasabah.lupa_password') }}" class="text-xs text-amber-600 hover:text-amber-800 font-semibold">
                        Lupa Password / PIN?
                    </a>
                </div>
                <input type="password" name="password" id="password" required
                    placeholder="Masukkan password atau PIN nasabah"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
            </div>

            <button type="submit"
                class="w-full bg-gray-900 text-white font-bold py-2 px-4 rounded hover:bg-black transition-colors">
                Masuk
            </button>
            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-amber-600">Login Petugas</a>
            </div>
        </form>
    </div>
</body>

</html>