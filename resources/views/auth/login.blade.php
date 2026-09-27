<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bank Mini Sekolah</title>
    @vite('resources/css/app.css')
</head>

<body class="bg-white min-h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-md p-8 border border-gray-200 shadow-xl rounded-lg mx-4">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo-smk.png') }}" alt="Logo" class="w-24 h-24 mx-auto mb-4 object-contain">
            <h1 class="text-2xl font-bold text-gray-900">Bank Mini</h1>
            <p class="text-gray-500">Sistem Perbankan Sekolah</p>
        </div>

        @if($errors->any())
            <div class="bg-red-100 text-red-600 p-3 rounded mb-4 text-center text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}" class="space-y-6">
            @csrf
            <div>
                <label class="block text-gray-900 text-sm font-bold mb-2">Username</label>
                <input type="text" name="username"
                    class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-gray-900"
                    value="{{ old('username') }}" required autofocus>
            </div>
            <div>
                <label class="block text-gray-900 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password"
                    class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-gray-900"
                    required>
            </div>
            <button type="submit"
                class="w-full bg-gray-900 text-white font-bold py-3 px-4 rounded hover:bg-black transition-colors">
                Masuk
            </button>
        </form>
    </div>
</body>

</html>