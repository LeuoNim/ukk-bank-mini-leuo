@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Loket Transaksi</h1>
        <p class="text-gray-600">Cari nasabah dan lakukan transaksi</p>
    </div>

    @if(session('success'))
        <div
            class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 flex flex-wrap justify-between items-center gap-2">
            <span class="font-medium">{{ session('success') }}</span>
            @if(session('last_transaction_id'))
                <a href="{{ route('teller.cetak', session('last_transaction_id')) }}" target="_blank"
                    class="inline-flex items-center gap-1.5 bg-green-700 hover:bg-green-800 text-white text-xs font-bold py-1.5 px-3 rounded shadow transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Struk Transaksi
                </a>
            @endif
        </div>
    @endif

    @if(session('pending_info'))
        <div
            class="bg-blue-50 border border-blue-300 text-blue-800 px-4 py-3 rounded relative mb-4 flex justify-between items-center">
            <span>{{ session('pending_info') }}</span>
            <a href="{{ route('teller.transaksi') }}"
                class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1 px-3 rounded">
                Periksa Status
            </a>
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
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-6 lg:p-7">
            <div class="flex justify-between items-center border-b pb-3 mb-5">
                <h2 class="text-lg font-bold text-gray-800">Data Nasabah</h2>
                @if(!empty($isLocked) && isset($customer))
                    <span
                        class="inline-flex items-center text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                        Sesi Terkunci
                    </span>
                @endif
            </div>

            @if(!empty($isLocked) && isset($customer))
                <!-- Banner Terkunci & Tombol Ganti Nasabah -->
                <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg mb-6 flex flex-wrap justify-between items-center gap-3">
                    <div>
                        <p class="text-xs font-bold text-slate-800 uppercase tracking-wide">Nasabah Terpilih</p>
                        <p class="text-xs text-slate-500 mt-1">Pencarian dikunci untuk menjaga keamanan transaksi.</p>
                    </div>
                    <form action="{{ route('teller.unlock') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="bg-slate-900 hover:bg-black text-white text-xs font-semibold py-2 px-4 rounded-md shadow-sm transition">
                            Ganti Nasabah
                        </button>
                    </form>
                </div>
            @else
                <form action="{{ route('teller.transaksi') }}" method="GET" class="mb-6 flex gap-2">
                    <input type="text" name="search" id="searchInput" placeholder="Ketik NIS atau Nomor Rekening..."
                        value="{{ request('search') }}"
                        class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-gray-900 text-sm" required>
                    <button type="submit" class="bg-gray-900 text-white px-5 py-2.5 rounded-lg hover:bg-black font-semibold text-sm transition">Cari</button>
                    <button type="button" id="scanQrBtn"
                        class="bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 flex items-center justify-center transition"
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
                <div id="reader-container" class="hidden mb-6 border rounded-lg overflow-hidden">
                    <div class="bg-gray-800 text-white p-2.5 text-sm flex justify-between items-center">
                        <span>Arahkan QR Code ke Kamera</span>
                        <button type="button" id="closeScannerBtn" class="text-red-400 hover:text-red-300 font-bold">Tutup</button>
                    </div>
                    <div id="reader" width="100%"></div>
                </div>
            @endif

            @if(isset($customer))
                <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide border-b pb-3 mb-4">Profil Nasabah</h3>
                    <table class="w-full text-sm">
                        <tr class="border-b border-gray-100">
                            <td class="py-2.5 px-2 text-gray-500 w-2/5">No. Rekening</td>
                            <td class="py-2.5 px-2 font-mono font-bold text-gray-900">{{ $customer->account_number }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2.5 px-2 text-gray-500">NIS</td>
                            <td class="py-2.5 px-2 font-mono font-semibold text-gray-900">{{ $customer->student_id }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2.5 px-2 text-gray-500">Nama</td>
                            <td class="py-2.5 px-2 font-bold text-gray-900">{{ $customer->student_name }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2.5 px-2 text-gray-500">Kelas</td>
                            <td class="py-2.5 px-2 font-medium text-gray-900">{{ $customer->class }}</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-2 text-gray-500">Saldo Saat Ini</td>
                            <td class="py-3 px-2 font-bold text-xl text-emerald-600">
                                Rp {{ number_format($customer->balance, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            @elseif(request('search'))
                <div class="text-red-500 text-sm p-4 bg-red-50 rounded-lg border border-red-200">Data nasabah tidak ditemukan.</div>
            @endif
        </div>

        <!-- Kolom Kanan: Form Transaksi -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-6 lg:p-7">
            <h2 class="text-lg font-bold border-b pb-3 mb-5 text-gray-800">Form Transaksi</h2>

            @if(isset($customer) && $customer->status === \App\Models\Customer::STATUS_PENDING)
                @include('teller.activation')
            @elseif(isset($customer) && $customer->status !== 'Aktif')
                <p class="text-red-700">Rekening diblokir. Hubungi administrator; transaksi tidak dapat dilakukan.</p>
            @elseif(isset($customer))
                @if(isset($pendingWithdrawal))
                    <!-- Notifikasi Status Penarikan Pending -->
                    <div id="pendingWithdrawalBanner" class="bg-blue-50 border border-blue-200 text-blue-900 p-4 rounded-lg mb-5 shadow-sm transition-all duration-300">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex-1">
                                <span id="pendingBadge"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 mb-1">
                                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-ping mr-1.5"></span>
                                    Menunggu Otorisasi Nasabah
                                </span>
                                <p class="text-sm font-bold text-gray-900" id="pendingAmountText">Penarikan Rp
                                    {{ number_format($pendingWithdrawal->amount, 0, ',', '.') }}
                                </p>
                                <p class="text-xs text-blue-700 mt-1" id="pendingNoticeText">
                                    Permintaan penarikan telah dikirim ke HP nasabah. Nasabah memasukkan PIN di aplikasi mobile untuk menyelesaikan transaksi ini.
                                </p>
                                <div id="statusCheckFeedback" class="hidden text-xs mt-1 font-medium"></div>
                            </div>
                            <div class="flex items-center gap-2" id="pendingActionButtons">
                                <button type="button" id="btnCheckStatus" onclick="checkPendingManual()"
                                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-3 rounded shadow transition flex items-center gap-1.5">
                                    <svg id="checkSpinner" class="hidden animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span id="checkBtnLabel">Periksa Status</span>
                                </button>
                                <form action="{{ route('teller.withdraw.cancel', $pendingWithdrawal->id) }}" method="POST"
                                    onsubmit="return confirm('Batalkan permintaan penarikan ini?')">
                                    @csrf
                                    <button type="submit"
                                        class="bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 text-xs font-medium py-2 px-3 rounded transition">
                                        Batalkan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('teller.deposit') }}" method="POST" id="formTransaksi">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Transaksi</label>
                        <div class="flex flex-wrap gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="jenis" value="setoran" checked
                                    onclick="toggleFields(false, '{{ route('teller.deposit') }}')">
                                <span
                                    class="px-4 py-2 bg-green-50 text-green-700 border border-green-300 font-semibold rounded text-sm hover:bg-green-100">Setoran</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="jenis" value="penarikan"
                                    onclick="toggleFields(true, '{{ route('teller.withdraw') }}')">
                                <span
                                    class="px-4 py-2 bg-red-50 text-red-700 border border-red-300 font-semibold rounded text-sm hover:bg-red-100">Penarikan</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nominal (Rp)</label>
                        <input type="number" name="amount"
                            class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm" required
                            min="1000">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Keterangan (Opsional)</label>
                        <input type="text" name="description"
                            class="w-full px-4 py-2 border rounded focus:outline-none focus:border-amber-500 text-sm">
                    </div>

                    <!-- Informasi Otorisasi PIN Mobile untuk Penarikan Biasa -->
                    <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-900 p-3 rounded-lg text-xs"
                        id="mobileNoticeGroup" style="display: none;">
                        <span class="font-bold block mb-0.5 text-blue-950">Otorisasi PIN di Aplikasi Mobile</span>
                        <p class="text-blue-800">
                            Setelah penarikan dilakukan oleh teller maka proses selanjutnya menunggu konfirmasi pin transaksi
                            oleh nasabah dan permintaan konfirmasi penarikan akan dikirim ke aplikasi mobile nasabah.
                        </p>
                    </div>



                    <button type="submit" @if(isset($pendingWithdrawal)) disabled
                    class="w-full bg-gray-400 text-white font-bold py-3 px-4 rounded cursor-not-allowed text-sm" @else
                            class="w-full bg-gray-900 hover:bg-black text-white font-bold py-3 px-4 rounded shadow transition text-sm"
                        @endif>
                        @if(isset($pendingWithdrawal))
                            Menunggu Otorisasi Nasabah...
                        @else
                            Submit Transaksi <span class="text-xs font-normal opacity-75">(Ctrl+Enter)</span>
                        @endif
                    </button>
                </form>
            @else
                <div class="text-gray-500 text-sm italic text-center py-8">
                    Silakan cari nasabah terlebih dahulu di kolom sebelah kiri untuk melakukan transaksi.
                </div>
            @endif
        </div>

    </div>

    <!-- HTML5-QRCode Library (Local & Offline Ready) -->
    <script src="{{ asset('html5-qrcode.min.js') }}"></script>
    <script>
        function toggleFields(isWithdraw, actionUrl) {
            const mobileNoticeGroup = document.getElementById('mobileNoticeGroup');
            const formTransaksi = document.getElementById('formTransaksi');

            if (mobileNoticeGroup) mobileNoticeGroup.style.display = isWithdraw ? 'block' : 'none';
            if (formTransaksi) formTransaksi.action = actionUrl;
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('keydown', function (e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    let form = document.getElementById('formTransaksi');
                    if (form) form.submit();
                }
            });

            // Realtime Auto-Polling Status Pending Withdrawal
            @if(isset($pendingWithdrawal))
            const pendingTrxId = {{ $pendingWithdrawal->id }};
            const statusUrl = "{{ route('teller.withdraw.status', $pendingWithdrawal->id) }}";
            let pollInterval = null;

            function checkPendingStatusAjax(isManual = false) {
                const btn = document.getElementById('btnCheckStatus');
                const spinner = document.getElementById('checkSpinner');
                const btnLabel = document.getElementById('checkBtnLabel');
                const feedback = document.getElementById('statusCheckFeedback');

                if (isManual && btn) {
                    btn.disabled = true;
                    if (spinner) spinner.classList.remove('hidden');
                    if (btnLabel) btnLabel.textContent = 'Memeriksa...';
                }

                fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (pollInterval) clearInterval(pollInterval);
                        const banner = document.getElementById('pendingWithdrawalBanner');
                        if (banner) {
                            banner.className = 'bg-emerald-50 border border-emerald-300 text-emerald-900 p-5 rounded-lg mb-5 shadow';
                            banner.innerHTML = `
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 mb-1">
                                            ✓ Penarikan Berhasil Diotorisasi
                                        </span>
                                        <p class="text-base font-bold text-emerald-950">Penarikan Tunai Rp ${data.formatted_amount} Sukses!</p>
                                        <p class="text-xs text-emerald-700 mt-0.5">PIN nasabah valid. Transaksi telah dibukukan ke sistem & kas.</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="${data.cetak_url}" target="_blank" class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs py-2 px-3.5 rounded shadow flex items-center gap-1.5 transition">
                                            🖨 Cetak Struk
                                        </a>
                                        <a href="{{ route('teller.transaksi') }}" class="bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold py-2 px-3 rounded shadow transition">
                                            Transaksi Baru
                                        </a>
                                    </div>
                                </div>
                            `;
                        }
                    } else if (data.status === 'cancelled') {
                        if (pollInterval) clearInterval(pollInterval);
                        alert("Permintaan penarikan telah dibatalkan.");
                        window.location.href = "{{ route('teller.transaksi') }}";
                    } else {
                        if (isManual && feedback) {
                            feedback.classList.remove('hidden');
                            feedback.className = 'text-xs mt-1.5 font-medium text-amber-700';
                            feedback.textContent = 'Status: Masih menunggu nasabah memasukkan PIN di aplikasi HP...';
                            setTimeout(() => { feedback.classList.add('hidden'); }, 4000);
                        }
                    }
                })
                .catch(err => {
                    console.error("Gagal memeriksa status penarikan:", err);
                })
                .finally(() => {
                    if (isManual && btn) {
                        btn.disabled = false;
                        if (spinner) spinner.classList.add('hidden');
                        if (btnLabel) btnLabel.textContent = 'Periksa Status';
                    }
                });
            }

            window.checkPendingManual = function() {
                checkPendingStatusAjax(true);
            };

            // Jalankan polling otomatis setiap 2.5 detik
            pollInterval = setInterval(() => {
                checkPendingStatusAjax(false);
            }, 2500);
            @endif

            // Scanner Logic
            const scanBtn = document.getElementById('scanQrBtn');
            const closeBtn = document.getElementById('closeScannerBtn');
            const scannerContainer = document.getElementById('reader-container');
            const searchInput = document.getElementById('searchInput');
            let html5QrCode = null;

            if (scanBtn) {
                scanBtn.addEventListener('click', function () {
                    if (typeof Html5Qrcode === 'undefined') {
                        alert("Library pemindai QR belum siap atau gagal dimuat.");
                        return;
                    }

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
                            alert("Gagal mengakses kamera: " + err + "\n\nPastikan browser Anda mengizinkan akses kamera dan Anda mengakses lewat http://localhost:8000 atau https.");
                            console.error("Camera access error:", err);
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
                if (searchInput) {
                    searchInput.value = decodedText;
                }

                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then(() => {
                        html5QrCode.clear();
                        scannerContainer.classList.add('hidden');
                        if (searchInput) {
                            searchInput.closest('form').submit();
                        }
                    }).catch(err => {
                        console.log("Error stopping scanner", err);
                        scannerContainer.classList.add('hidden');
                        if (searchInput) {
                            searchInput.closest('form').submit();
                        }
                    });
                } else {
                    scannerContainer.classList.add('hidden');
                    if (searchInput) {
                        searchInput.closest('form').submit();
                    }
                }
            }

            function onScanFailure(error) {
                // Ignore per-frame scanning noise
            }
        });
    </script>
@endsection
