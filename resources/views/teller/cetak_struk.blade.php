<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi {{ $transaction->transaction_code }}</title>
    @vite('resources/css/app.css')
    <style>
        @media print {
            body { font-size: 12pt; }
            .no-print { display: none !important; }
            .print-area { width: 100%; max-width: 100%; border: none !important; box-shadow: none !important; margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="bg-gray-100 p-8 flex flex-col items-center justify-center font-sans">
    
    <!-- Tombol Aksi (Tidak diprint) -->
    <div class="mb-6 no-print flex gap-4">
        <button onclick="window.print()" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-6 rounded shadow">
            Cetak Struk
        </button>
        <a href="{{ route('teller.transaksi') }}" class="bg-gray-900 hover:bg-black text-white font-bold py-2 px-6 rounded shadow">
            Kembali
        </a>
    </div>

    <!-- Area Struk (Untuk Print Thermal/Biasa) -->
    <div class="bg-white p-8 border border-gray-300 shadow-lg print-area w-full max-w-md mx-auto">
        
        <!-- Header -->
        <div class="text-center mb-6 border-b-2 border-gray-200 pb-4">
            <img src="{{ asset('images/logo-smk.png') }}" alt="Logo" class="w-16 h-16 mx-auto mb-2 object-contain grayscale">
            <h1 class="text-xl font-bold uppercase text-gray-900">BANK MINI SEKOLAH</h1>
            <p class="text-sm text-gray-600">SMK Negeri 1 Bantul</p>
            <p class="text-xs text-gray-500 mt-1">Struk Bukti {{ $transaction->transaction_type }}</p>
        </div>

        <!-- Detail Transaksi -->
        <div class="mb-6">
            <table class="w-full text-sm">
                <tr>
                    <td class="py-1 text-gray-600 w-1/3">No. Transaksi</td>
                    <td class="py-1 font-mono font-bold text-gray-900">{{ $transaction->transaction_code }}</td>
                </tr>
                <tr>
                    <td class="py-1 text-gray-600">Jenis Transaksi</td>
                    <td class="py-1 font-bold {{ in_array($transaction->transaction_type, ['Setoran', 'Setoran Koreksi']) ? 'text-green-700' : 'text-red-700' }}">
                        {{ strtoupper($transaction->transaction_type) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-1 text-gray-600">Tanggal</td>
                    <td class="py-1 text-gray-900">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d-m-Y H:i') }}</td>
                </tr>
                <tr>
                    <td class="py-1 text-gray-600">Teller</td>
                    <td class="py-1 text-gray-900">{{ $transaction->user->staff_name }}</td>
                </tr>
            </table>
        </div>
        
        <div class="border-t border-dashed border-gray-300 my-4"></div>

        <!-- Detail Nasabah -->
        <div class="mb-6">
            <table class="w-full text-sm">
                <tr>
                    <td class="py-1 text-gray-600 w-1/3">No. Rekening</td>
                    <td class="py-1 font-mono text-gray-900">{{ $transaction->customer->account_number }}</td>
                </tr>
                <tr>
                    <td class="py-1 text-gray-600">Nama Siswa</td>
                    <td class="py-1 font-bold text-gray-900">{{ $transaction->customer->student_name }}</td>
                </tr>
                <tr>
                    <td class="py-1 text-gray-600">Keterangan</td>
                    <td class="py-1 text-gray-900">{{ $transaction->description }}</td>
                </tr>
            </table>
        </div>

        <div class="border-t border-dashed border-gray-300 my-4"></div>

        <!-- Nominal -->
        <div class="text-center my-6">
            <p class="text-gray-600 text-sm mb-1 uppercase font-semibold">Nominal {{ $transaction->transaction_type }}</p>
            <p class="text-3xl font-bold {{ in_array($transaction->transaction_type, ['Setoran', 'Setoran Koreksi']) ? 'text-green-700' : 'text-red-700' }}">
                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
            </p>
        </div>

        <div class="border-t border-dashed border-gray-300 my-4"></div>

        <!-- Saldo Terakhir -->
        <div class="bg-gray-50 border border-gray-200 rounded p-4 text-center my-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">Sisa Saldo Terakhir Rekening</p>
            <p class="text-2xl font-bold text-gray-900">
                Rp {{ number_format($transaction->customer->balance, 0, ',', '.') }}
            </p>
        </div>

        <div class="border-t border-dashed border-gray-300 my-4"></div>

        <!-- Footer -->
        <div class="text-center mt-6 text-xs text-gray-500">
            <p>Terima kasih telah bertransaksi di Bank Mini.</p>
            <p>Struk ini adalah bukti transaksi yang sah.</p>
            <p class="mt-2">{{ date('d/m/Y H:i:s') }}</p>
        </div>

    </div>

    <!-- Script auto-print jika diperlukan (biasanya bagus untuk UX) -->
    <script>
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
