<div class="bg-amber-50 border rounded-lg p-5 mb-5">
    <h3 class="font-bold mb-2">Nonaktif — belum aktivasi</h3>
    <p class="text-sm mb-4">Siswa harus hadir. Periksa kartu pelajar/data sekolah, terima uang fisik, lalu aktifkan rekening. Password login dan PIN transaksi enam digit akan dibuat otomatis dan ditampilkan untuk diserahkan kepada siswa.</p>
    <form action="{{ route('teller.activate', $customer->id) }}" method="POST" class="space-y-3">
        @csrf
        <label class="block">Setoran awal yang diterima (Rp)
            <input class="block w-full border rounded p-2" type="number" name="amount" min="10000" max="100000000" step="1000" required>
        </label>
        <label class="block">Catatan verifikasi identitas
            <input class="block w-full border rounded p-2" name="verification_note" minlength="10" maxlength="300" placeholder="Contoh: kartu pelajar cocok dengan daftar siswa sekolah" required>
        </label>
        <label class="block"><input type="checkbox" name="identity_verified" value="1" required> Identitas siswa sudah saya periksa.</label>
        <label class="block"><input type="checkbox" name="cash_received" value="1" required> Uang setoran sudah saya terima dan hitung.</label>
        <button class="bg-gray-900 text-white font-bold px-4 py-2 rounded">Aktifkan dan Catat Setoran Awal</button>
    </form>
</div>
