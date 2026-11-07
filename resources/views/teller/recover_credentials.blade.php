<details class="bg-amber-50/60 border border-amber-200 rounded-xl p-4 mt-4 shadow-sm group">
    <summary class="font-bold text-amber-900 cursor-pointer flex items-center justify-between text-sm select-none">
        <span class="flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
            Layanan Reset Password / PIN (Nasabah Lupa)
        </span>
        <span class="text-xs text-amber-600 font-normal underline">Buka Form</span>
    </summary>
    <div class="mt-3 pt-3 border-t border-amber-200/70 text-xs text-gray-700">
        <p class="mb-3 text-gray-600 leading-relaxed">
            Gunakan fitur ini jika siswa lupa Password dan PIN sekaligus, atau akun terkunci akibat 3x salah PIN. Pastikan siswa hadir langsung di loket.
        </p>

        <!-- Alert Error Peringatan Dinamis -->
        <div id="resetErrorNotice" class="hidden mb-3 p-2.5 bg-red-100 border border-red-300 text-red-800 rounded-lg font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <span id="resetErrorText">Harap lengkapi bukti verifikasi terlebih dahulu.</span>
        </div>

        <form id="formResetKredensial" action="{{ route('teller.reset_credentials', $customer->id) }}" method="POST" class="space-y-3" onsubmit="return handleResetKredensialSubmit(event)">
            @csrf
            <div>
                <label class="block font-semibold text-gray-800 mb-1">Kredensial yang Akan Direset</label>
                <select class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white focus:ring-2 focus:ring-amber-500" name="reset_type">
                    <option value="both">Keduanya (Password Baru & PIN Baru)</option>
                    <option value="password">Password Login Saja</option>
                    <option value="pin">PIN Transaksi Saja</option>
                </select>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="font-semibold text-gray-800">Bukti / Catatan Verifikasi Identitas <span class="text-red-500">*</span></label>
                    <button type="button" onclick="isiCatatanStandar()" class="text-[11px] text-amber-700 hover:text-amber-900 underline font-medium">Gunakan Teks Standar</button>
                </div>
                <input type="text" id="verification_note_input"
                    class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-amber-500" 
                    name="verification_note" minlength="10" maxlength="300" 
                    value="Siswa hadir langsung, kartu pelajar fisik terverifikasi sesuai buku induk"
                    placeholder="Siswa hadir langsung, kartu pelajar fisik terverifikasi sesuai buku induk" required>
                <span class="text-[11px] text-gray-500 mt-1 block">Kalimat wajib: <em>Siswa hadir langsung, kartu pelajar fisik terverifikasi sesuai buku induk</em></span>
            </div>
            <div class="flex items-start gap-2 pt-1">
                <input type="checkbox" id="verify_check" name="identity_verified" value="1" class="mt-0.5 rounded text-amber-600 focus:ring-amber-500 cursor-pointer" required>
                <label for="verify_check" class="text-xs text-gray-700 cursor-pointer select-none">Siswa hadir di loket dan identitas fisiknya telah diverifikasi.</label>
            </div>
            <button type="submit" id="btnSubmitReset"
                class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-sm transition text-xs flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Generate Kredensial Baru
            </button>
        </form>
    </div>
</details>

<script>
    function isiCatatanStandar() {
        const input = document.getElementById('verification_note_input');
        if (input) {
            input.value = "Siswa hadir langsung, kartu pelajar fisik terverifikasi sesuai buku induk";
            input.focus();
            const err = document.getElementById('resetErrorNotice');
            if (err) err.classList.add('hidden');
        }
    }

    function handleResetKredensialSubmit(e) {
        e.preventDefault();
        const form = document.getElementById('formResetKredensial');
        const noteInput = document.getElementById('verification_note_input');
        const checkInput = document.getElementById('verify_check');
        const errorDiv = document.getElementById('resetErrorNotice');
        const errorText = document.getElementById('resetErrorText');

        // 1. Validasi teks catatan verifikasi terlebih dahulu
        if (!noteInput || !noteInput.value.trim() || noteInput.value.trim().length < 10) {
            if (errorDiv && errorText) {
                errorText.textContent = 'Harap isi field Bukti / Catatan Verifikasi Identitas terlebih dahulu!';
                errorDiv.classList.remove('hidden');
            }
            if (noteInput) {
                noteInput.focus();
                noteInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
            }
            return false;
        }

        if (noteInput) {
            noteInput.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
        }

        // 2. Validasi checkbox verifikasi
        if (!checkInput || !checkInput.checked) {
            if (errorDiv && errorText) {
                errorText.textContent = 'Harap centang pernyataan: Siswa hadir di loket dan identitas fisiknya telah diverifikasi.';
                errorDiv.classList.remove('hidden');
            }
            if (checkInput) checkInput.focus();
            return false;
        }

        // Sembunyikan error jika semua valid
        if (errorDiv) errorDiv.classList.add('hidden');

        // 3. Munculkan konfirmasi HANYA SETELAH semua field terisi lengkap & valid
        if (confirm('Reset kredensial nasabah ini? Password/PIN lama akan diganti dan sesi akun otomatis dikeluarkan.')) {
            form.submit();
        }

        return false;
    }
</script>
