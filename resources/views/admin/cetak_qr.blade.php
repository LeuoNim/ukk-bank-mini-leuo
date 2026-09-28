<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu QR Nasabah - {{ $customer->student_name }}</title>
    @vite('resources/css/app.css')
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-6 font-sans">
    
    <div class="max-w-md w-full">
        <!-- Print Button -->
        <div class="text-center mb-6 no-print">
            <button onclick="window.print()" class="bg-gray-900 hover:bg-black text-white font-bold py-2 px-6 rounded shadow">
                🖨️ Cetak Kartu
            </button>
            <a href="{{ route('admin.nasabah') }}" class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded shadow">
                Kembali
            </a>
        </div>

        <!-- Card Design -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
            <!-- Header -->
            <div class="bg-gray-900 text-white text-center py-4 border-b-4 border-amber-500">
                <img src="{{ asset('images/logo-smk.png') }}" alt="Logo" class="w-12 h-12 mx-auto mb-2 object-contain" onerror="this.style.display='none'">
                <h1 class="font-bold text-lg uppercase tracking-wide">Bank Mini Sekolah</h1>
                <p class="text-xs text-gray-400">Kartu Identitas Nasabah</p>
            </div>
            
            <!-- Body -->
            <div class="p-6 text-center">
                <div class="mb-6 flex justify-center p-4 bg-white border-2 border-dashed border-gray-300 inline-block rounded-lg">
                    <!-- QR Code -->
                    {!! QrCode::size(180)->generate($customer->account_number) !!}
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ $customer->student_name }}</h2>
                <p class="text-gray-500 font-mono mb-4 text-lg">{{ $customer->account_number }}</p>
                
                <div class="grid grid-cols-2 gap-4 text-left bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold">NIS</p>
                        <p class="font-semibold text-gray-800">{{ $customer->student_id }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold">Kelas</p>
                        <p class="font-semibold text-gray-800">{{ $customer->class }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 text-center py-3 border-t text-xs text-gray-500">
                Gunakan QR Code ini untuk bertransaksi di loket Bank Mini.
            </div>
        </div>
    </div>

</body>
</html>
