<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Kredensial Nasabah - Bank Mini Sekolah</title>
    @vite('resources/css/app.css')
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4 font-sans">
    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md border border-gray-200">
        <div class="text-center mb-6">
            <img src="{{ asset('images/logo-smk.png') }}" alt="Logo SMK Negeri 1 Bantul"
                class="w-20 h-20 mx-auto mb-3 object-contain">
            <h1 class="text-2xl font-bold text-gray-900">Atur Ulang Kredensial</h1>
            <p class="text-gray-500 text-sm">Lupa Password / PIN Nasabah SMK N 1 Bantul</p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('nasabah.lupa_password.process') }}" method="POST">
            @csrf

            <!-- Toggle Jenis Reset: Password Login atau PIN Transaksi -->
            <div class="mb-5">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Pilih Kredensial yang Ingin Direset</label>
                <div class="grid grid-cols-2 gap-2 bg-gray-100 p-1 rounded-lg">
                    <label class="flex items-center justify-center py-2 px-3 rounded-md cursor-pointer transition text-xs font-bold text-center"
                        id="labelResetPassword"
                        onclick="switchResetType('password')">
                        <input type="radio" name="reset_type" value="password" id="radioPassword" class="sr-only" checked>
                        <span>Password Login</span>
                    </label>
                    <label class="flex items-center justify-center py-2 px-3 rounded-md cursor-pointer transition text-xs font-bold text-center text-gray-600"
                        id="labelResetPin"
                        onclick="switchResetType('pin')">
                        <input type="radio" name="reset_type" value="pin" id="radioPin" class="sr-only">
                        <span>PIN Transaksi (6 Digit)</span>
                    </label>
                </div>
            </div>

            <!-- Identitas Siswa -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">NIS Siswa</label>
                <input type="text" name="student_id" value="{{ old('student_id') }}" required
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                    placeholder="Contoh: 2024001">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor Rekening</label>
                <input type="text" name="account_number" value="{{ old('account_number') }}" required
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm font-mono uppercase"
                    placeholder="Contoh: AK-0001 atau RPL-0001">
                <span class="text-xs text-gray-500 mt-1 block">Cek nomor rekening pada buku tabungan atau kartu nasabah Anda.</span>
            </div>

            <!-- Bagian Password Baru -->
            <div id="passwordFields" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Password Baru (Login Web & Mobile)</label>
                    <input type="password" name="new_password" id="new_password"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                        placeholder="Minimal 6 karakter">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                        placeholder="Ulangi password baru">
                </div>
            </div>

            <!-- Bagian PIN Baru (6 Digit) -->
            <div id="pinFields" class="space-y-4 hidden">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">PIN Transaksi Baru (6 Digit Angka)</label>
                    <input type="password" inputmode="numeric" maxlength="6" name="new_pin" id="new_pin"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm tracking-widest text-center"
                        placeholder="••••••">
                    <span class="text-xs text-gray-500 mt-1 block">PIN digunakan untuk otorisasi penarikan dana di teller.</span>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Konfirmasi PIN Baru (6 Digit Angka)</label>
                    <input type="password" inputmode="numeric" maxlength="6" name="new_pin_confirmation" id="new_pin_confirmation"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm tracking-widest text-center"
                        placeholder="••••••">
                </div>
            </div>

            <button type="submit"
                class="w-full mt-6 bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 px-4 rounded-lg shadow transition text-sm">
                Simpan &amp; Perbarui Akun
            </button>
        </form>

        <div class="mt-5 text-center">
            <a href="{{ route('login') }}" class="text-xs text-gray-600 hover:text-slate-900 underline font-medium">
                &larr; Kembali ke Halaman Login
            </a>
        </div>
    </div>

    <script>
        function switchResetType(type) {
            const passFields = document.getElementById('passwordFields');
            const pinFields = document.getElementById('pinFields');
            const labelPass = document.getElementById('labelResetPassword');
            const labelPin = document.getElementById('labelResetPin');
            const radioPass = document.getElementById('radioPassword');
            const radioPin = document.getElementById('radioPin');

            if (type === 'password') {
                passFields.classList.remove('hidden');
                pinFields.classList.add('hidden');
                radioPass.checked = true;

                labelPass.className = 'flex items-center justify-center py-2 px-3 rounded-md cursor-pointer transition text-xs font-bold text-center bg-white shadow text-slate-900';
                labelPin.className = 'flex items-center justify-center py-2 px-3 rounded-md cursor-pointer transition text-xs font-bold text-center text-gray-600 hover:text-slate-900';
            } else {
                passFields.classList.add('hidden');
                pinFields.classList.remove('hidden');
                radioPin.checked = true;

                labelPin.className = 'flex items-center justify-center py-2 px-3 rounded-md cursor-pointer transition text-xs font-bold text-center bg-white shadow text-slate-900';
                labelPass.className = 'flex items-center justify-center py-2 px-3 rounded-md cursor-pointer transition text-xs font-bold text-center text-gray-600 hover:text-slate-900';
            }
        }

        // Jalankan saat pertama load
        document.addEventListener('DOMContentLoaded', function() {
            const selected = document.querySelector('input[name="reset_type"]:checked');
            switchResetType(selected ? selected.value : 'password');
        });
    </script>
</body>

</html>
