@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Loket Transaksi</h1>
        <p class="text-gray-600">Cari nasabah dan lakukan transaksi</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Kolom Kiri: Cari Nasabah -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-bold border-b pb-2 mb-4">Pencarian Nasabah</h2>

            <form action="{{ route('teller.transaksi') }}" method="GET" class="mb-6 flex gap-2">
                <input type="text" name="search" id="searchInput" placeholder="Ketik NIS atau Nomor Rekening..."
                    value="{{ request('search') }}"
                    class="flex-1 px-4 py-2 border rounded focus:outline-none focus:border-amber-500" required>
                <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-700">Cari</button>
                <button type="button" id="scanQrBtn"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center justify-center"
                    title="Scan QR Code">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path
                            d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0v-3Zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5ZM.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5Zm15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5ZM4 4h1v1H4V4Z" />
                        <path d="M7 2H2v5h5V2ZM3 3h3v3H3V3Zm2 8H4v1h1v-1Z" />
                        <path d="M7 9H2v5h5V9Zm-4 1h3v3H3v-3Zm8-6h1v1h-1V4Z" />
                        <path
                            d="M9 2h5v5H9V2Zm1 1v3h3V3h-3ZM8 8v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8H8Zm2 2H9V9h1v1Zm4 2h-1v1h-2v1h3v-2Zm-4 2v-1H8v1z" />
                        <path d="M12 9h2V8h-2v1Z" />
                    </svg>
                </button>
            </form>

            <!-- Container Scanner QR -->
            <div id="reader-container" class="hidden mb-6 border rounded overflow-hidden">
                <div class="bg-gray-800 text-white p-2 text-sm flex justify-between items-center">
                    <span>Arahkan QR Code ke Kamera</span>
                    <button type="button" id="closeScannerBtn" class="text-red-400 hover:text-red-300 font-bold">X
                        Tutup</button>
                </div>
                <div id="reader" width="100%"></div>
            </div>

            @if(isset($customer))
                <div class="bg-gray-50 border p-4 rounded-lg">
                    <h3 class="font-bold text-gray-700 mb-2">Profil Nasabah</h3>
                    <table class="w-full text-sm">
                        <tr>
                            <td class="py-1 text-gray-500 w-1/3">No. Rekening</td>
                            <td class="font-semibold">{{ $customer->account_number }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 text-gray-500">NIS</td>
                            <td class="font-semibold">{{ $customer->student_id }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 text-gray-500">Nama</td>
                            <td class="font-semibold">{{ $customer->student_name }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 text-gray-500">Kelas</td>
                            <td class="font-semibold">{{ $customer->class }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 text-gray-500">Saldo Saat Ini</td>
                            <td class="font-bold text-lg text-green-600">Rp {{ number_format($customer->balance, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            @elseif(request('search'))
                <div class="text-red-500 text-sm">Data nasabah tidak ditemukan.</div>
            @endif
        </div>

        <!-- Kolom Kanan: Form Transaksi -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-bold border-b pb-2 mb-4">Form Transaksi</h2>

            @if(isset($customer))
                <form action="{{ route('teller.deposit') }}" method="POST" id="formTransaksi">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Transaksi</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="jenis" value="setoran" checked
                                    onclick="document.getElementById('pinGroup').style.display='none'; this.form.action='{{ route('teller.deposit') }}'">
                                <span
                                    class="px-4 py-2 bg-green-100 text-green-800 border border-green-300 rounded hover:bg-green-200">Setoran</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="jenis" value="penarikan"
                                    onclick="document.getElementById('pinGroup').style.display='block'; this.form.action='{{ route('teller.withdraw') }}'">
                                <span
                                    class="px-4 py-2 bg-red-100 text-red-800 border border-red-300 rounded hover:bg-red-200">Penarikan</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nominal (Rp)</label>
                        <input type="number" name="amount"
                            class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500" required
                            min="1000">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Keterangan (Opsional)</label>
                        <input type="text" name="description"
                            class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500">
                    </div>

                    <div class="mb-6" id="pinGroup" style="display: none;">
                        <label class="block text-sm font-bold text-gray-700 mb-2">PIN Nasabah (Wajib untuk Penarikan)</label>
                        <input type="password" name="pin"
                            class="w-full px-4 py-2 border rounded focus:outline-none focus:border-red-500">
                    </div>

                    <button type="submit" class="w-full bg-gray-900 text-white font-bold py-3 px-4 rounded hover:bg-black">
                        Submit Transaksi
                    </button>
                </form>
            @else
                <div class="text-gray-500 text-sm italic text-center py-8">
                    Silakan cari nasabah terlebih dahulu di kolom sebelah kiri untuk melakukan transaksi.
                </div>
            @endif
        </div>

    </div>

    <!-- HTML5-QRCode Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.querySelector('select[name="transaction_type"]');
            const pinGroup = document.getElementById('pinGroup');
            const formAction = document.getElementById('transactionForm');

            if (typeSelect && pinGroup && formAction) {
                typeSelect.addEventListener('change', function () {
                    if (this.value === 'withdraw') {
                        pinGroup.style.display = 'block';
                        formAction.action = "{{ route('teller.withdraw') }}";
                    } else {
                        pinGroup.style.display = 'none';
                        formAction.action = "{{ route('teller.deposit') }}";
                    }
                });
            }

            // Scanner Logic
            const scanBtn = document.getElementById('scanQrBtn');
            const closeBtn = document.getElementById('closeScannerBtn');
            const scannerContainer = document.getElementById('reader-container');
            const searchInput = document.getElementById('searchInput');
            let html5QrCode = null;

            if (scanBtn) {
                scanBtn.addEventListener('click', function () {
                    scannerContainer.classList.remove('hidden');
                    
                    if (!html5QrCode) {
                        html5QrCode = new Html5Qrcode("reader");
                    }
                    
                    if (!html5QrCode.isScanning) {
                        html5QrCode.start(
                            { facingMode: "environment" },
                            {
                                fps: 10,
                                qrbox: { width: 250, height: 250 }
                            },
                            onScanSuccess,
                            onScanFailure
                        ).catch(err => {
                            alert("Gagal mengakses kamera. Pastikan browser Anda mengizinkan akses kamera, atau gunakan koneksi HTTPS / localhost.");
                            console.error(err);
                        });
                    }
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    scannerContainer.classList.add('hidden');
                    if (html5QrCode && html5QrCode.isScanning) {
                        html5QrCode.stop().then(() => {
                            html5QrCode.clear();
                        }).catch(err => {
                            console.log("Error stopping scanner", err);
                        });
                    }
                });
            }

            function onScanSuccess(decodedText, decodedResult) {
                // handle the scanned code
                searchInput.value = decodedText;

                // close scanner
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then(() => {
                        html5QrCode.clear();
                        scannerContainer.classList.add('hidden');
                        // submit form
                        searchInput.closest('form').submit();
                    }).catch(err => {
                        console.log("Error stopping scanner", err);
                    });
                }
            }

            function onScanFailure(error) {
                // handle scan failure, usually better to ignore and keep scanning.
            }
        });
    </script>
@endsection