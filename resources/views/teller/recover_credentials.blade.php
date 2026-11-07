<details class="border rounded-lg p-4 mt-5">
    <summary class="font-bold cursor-pointer">Bantuan lupa password / PIN di teller</summary>
    <p class="text-sm my-3">Periksa identitas siswa secara langsung. NIS atau nomor rekening saja bukan bukti kepemilikan. Password/PIN baru dibuat sistem; kredensial lama tidak ditampilkan.</p>
    <form action="{{ route('teller.reset_credentials', $customer->id) }}" method="POST" class="space-y-3">
        @csrf
        <label class="block">Yang dipulihkan
            <select class="border rounded p-2" name="reset_type"><option value="password">Password login</option><option value="pin">PIN transaksi</option><option value="both">Keduanya</option></select>
        </label>
        <label class="block">Catatan verifikasi
            <input class="w-full border rounded p-2" name="verification_note" minlength="10" maxlength="300" required>
        </label>
        <label class="block"><input type="checkbox" name="identity_verified" value="1" required> Siswa hadir dan identitas sudah saya periksa.</label>
        <button class="bg-gray-900 text-white px-4 py-2 rounded">Buat Kredensial Pengganti</button>
    </form>
</details>
