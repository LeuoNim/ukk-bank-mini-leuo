# Blueprint revisi lanjutan sistem E-Teller Bank Mini Sekolah

Tanggal penyusunan: 6 November 2026  
Status: **RANCANGAN — BELUM DIEKSEKUSI**  
Backend: `D:\Herd\ukk\bank_mini_sekolah`  
Mobile: `D:\Herd\ukk\bank_mini_mobile`

## 1. Tujuan dan batas pekerjaan

Dokumen ini menjadi urutan pekerjaan untuk sesi berikutnya. Prioritasnya adalah memastikan akses akun, penerimaan uang, koreksi, dan laporan konsisten sebelum menambah fitur kosmetik. Pembuatan dokumen ini hanya memakai peninjauan kode; tidak menjalankan tes, server, ADB, pemasangan APK, migrasi, atau perubahan data.

Dokumen tidak menyatakan aplikasi sudah bebas celah atau sesuai seluruh standar perbankan. Aturan operasional sekolah perlu dibedakan dari pengaman teknis. Implementasi baru dimulai setelah pengguna meminta melanjutkan pelaksanaan blueprint, bukan hanya membaca atau membahasnya.

Jangan menganggap instruksi dari blueprint lama sebagai bukti fitur sudah diterapkan. Cocokkan kembali dengan kode saat eksekusi karena proyek dapat berubah.

## 2. Kondisi awal yang harus dipertahankan

Perubahan berikut sudah ada sebelum blueprint ini ditulis; jangan dibangun ulang tanpa alasan:

- Mobile memakai `http://127.0.0.1:8000/api`, tanpa menu konfigurasi API. Preferensi alamat lama tidak digunakan.
- Tersedia `connect-usb.ps1` di proyek mobile untuk dijalankan pengguna melalui Antigravity. Kabel USB tetap membutuhkan USB debugging, pemetaan ADB, dan backend aktif; aplikasi tidak otomatis membuat server Laravel.
- Pendaftaran manual/impor baru membuat status internal `BelumAktif`, ditampilkan sebagai **Nonaktif — belum aktivasi**, saldo Rp0, tanpa jurnal.
- Aktivasi teller menerima setoran minimal Rp10.000 sesuai aturan aplikasi saat ini, membuat transaksi dan jurnal, mengaktifkan rekening, dan menghasilkan password/PIN.
- CSV tetap memiliki lima kolom. `pin` boleh kosong; PIN lama yang diisi tidak menjadi kredensial aktivasi. `setoran_awal` hanya kosong atau 0.
- Tersedia perubahan kredensial di HP/web dan pemulihan melalui teller. Reset publik hanya dengan NIS dan nomor rekening telah ditutup.
- Akun lama bersaldo nol dapat ditandai belum aktivasi hanya jika tidak memiliki transaksi. Tidak ada konversi massal otomatis.
- Aktivasi dan konfirmasi penarikan menggunakan transaksi database dan penguncian baris. Tes sebelumnya memeriksa pengulangan permintaan dan pembatalan perubahan ketika pembukuan gagal.

Catatan verifikasi sesi sebelumnya: 42 tes backend dan 10 tes Flutter dilaporkan lulus; analisis Flutter bersih; APK build number 2 berhasil dibuat. Angka ini bukan hasil pengujian ulang malam ini. Tes SQLite tidak membuktikan perilaku konkurensi MySQL. Login nyata dari HP belum terkonfirmasi; pemeriksaan server sebelumnya hanya membuktikan respons dari laptop pada saat itu.

## 3. Peta prioritas

| ID | Prioritas | Pekerjaan | Dasar |
| --- | --- | --- | --- |
| R01 | P0 | Batasi endpoint daftar nasabah dan akun petugas nonaktif | Terlihat pada route/controller/middleware |
| R02 | P0 | Satukan otorisasi koreksi dan arah arus kas | Nominal laporan dapat berlawanan dengan jurnal |
| R03 | P1 | Laporan harian dengan revisi, tanggal, dan identitas teller yang tepat | Terlihat pada controller laporan |
| R04 | P1 | Satukan perubahan kredensial dan pencabutan sesi | Endpoint lama dan baru masih berbeda |
| R05 | P1 | Penomoran dan pengulangan transaksi yang aman | Generator masih memakai jumlah baris |
| R06 | P1 | Buktikan alur USB dari APK sampai database | Memerlukan perangkat dan persetujuan waktu uji pengguna |
| R07 | P1 | Perjelas status akun, impor, dan penyerahan kredensial awal | Penyempurnaan alur yang baru diterapkan |
| R08 | P1 | Tentukan zona waktu dan cakupan kas teller | Konfigurasi UTC dan perhitungan lintas teller terlihat di kode |
| R09 | P2 | Audit aktivitas terstruktur, ekspor, dan penyajian | Penambahan setelah P0/P1 stabil |

P0: selesaikan sebelum pemakaian dengan data siswa nyata. P1: selesaikan sebelum simulasi UKK menyeluruh. P2: peningkatan setelah alur inti lulus.

## 4. R01 — Otorisasi dan status petugas

### Temuan kode

- `routes/api.php`: `GET /api/customers` berada dalam `auth:sanctum`, tetapi tidak berada dalam grup role petugas. `CustomerController::index()` mengambil daftar nasabah tanpa pembatasan pemilik. Ini membuka kemungkinan token nasabah membaca daftar nasabah lain; perlu dibuktikan melalui tes negatif, tanpa menggunakan data nyata.
- `Api/AuthController::login()` memeriksa identitas/password tetapi belum menolak `User.status` nonaktif sebelum membuat token.
- `RoleMiddleware` memeriksa role, bukan status aktif. Menonaktifkan petugas tidak otomatis menjamin token/sesi lamanya berhenti bekerja.

### Rancangan

1. Terapkan daftar hak akses eksplisit, bukan hanya syarat “sudah login”. Untuk daftar nasabah, usulan awal: Administrator dan Teller; akses Supervisor hanya jika kebutuhan pemeriksaannya disepakati.
2. Pastikan endpoint internal mensyaratkan model `User`, role yang sesuai, dan status `Aktif`. Endpoint pribadi mensyaratkan model `Customer`, status aktif, dan kepemilikan sumber daya.
3. Tolak login API petugas nonaktif; cabut token saat pemblokiran. Middleware tetap harus memeriksa status setiap permintaan agar sesi web lama juga ditolak.
4. Tambahkan pembatasan percobaan login web/mobile dan percobaan PIN. Hindari penguncian permanen hanya berdasarkan NIS yang mudah ditebak; gunakan kombinasi identitas normalisasi dan asal permintaan serta masa tunggu.
5. Audit seluruh route melalui matriks hak akses. Jangan hanya menyembunyikan tombol.

**Kriteria selesai:** token siswa ditolak untuk daftar siswa lain dan transaksi petugas; petugas diblokir ditolak dengan token/sesi lama; role yang memang berhak tetap berfungsi.

## 5. R02 — Koreksi transaksi dan laporan kas

### Temuan kode

- `Api/TransactionController` masih memakai sandi koreksi bersama yang ditulis langsung dalam source code.
- `Setoran Koreksi` mengurangi saldo nasabah dan mengkredit akun kas 111. Namun `SupervisorController` menjumlahkannya bersama `Setoran` sebagai pemasukan.
- `Penarikan Koreksi` menambah saldo nasabah dan mendebet kas 111. Namun rekap menjumlahkannya bersama `Penarikan` sebagai pengeluaran.
- `Api/ReportController::submit()` hanya menjumlahkan jenis Setoran/Penarikan biasa, sehingga koreksi tidak konsisten dengan rekap mingguan/bulanan.

### Rancangan

Gunakan jurnal kas berhasil sebagai dasar arah arus kas: **debet akun kas = kas masuk; kredit akun kas = kas keluar**. Nilai transaksi disimpan positif; arah tidak ditentukan hanya dari nama jenis transaksi. Terapkan satu perhitungan bersama untuk harian, mingguan, bulanan, dan saldo awal/akhir. Tampilkan arus bruto dan nilai koreksi dengan label yang tidak membingungkan.

Contoh penerimaan hasil: setoran Rp50.000 dan pembalikan penuh setoran tersebut menghasilkan arus kas bersih Rp0 serta perubahan saldo bersih Rp0. Penarikan Rp10.000 dan pembalikannya juga menghasilkan perubahan bersih Rp0. Ini contoh pemeriksaan konsistensi aplikasi, bukan sertifikasi standar akuntansi.

Koreksi diusulkan menjadi transaksi pembalik yang:

- Merujuk transaksi asal, alasan, pemohon, penyetuju, dan waktu keputusan.
- Tidak menghapus/mengedit bukti transaksi asal.
- Memerlukan persetujuan Supervisor; pemohon tidak menyetujui permohonannya sendiri.
- Memeriksa kembali status rekening, nilai, saldo, dan status transaksi asal di dalam transaksi database.
- Tidak dapat membalik transaksi yang sama dua kali. Untuk lingkup UKK, utamakan pembalikan penuh satu kali; koreksi sebagian ditunda kecuali diwajibkan.

Keputusan yang perlu dibahas dengan guru: apakah uang fisik benar-benar dikembalikan/diserahkan saat koreksi, atau hanya pembetulan pencatatan? UI dan jurnal harus mengikuti kejadian yang sebenarnya. Jangan menganggap semua koreksi otomatis menimbulkan pergerakan uang fisik baru.

**Kriteria selesai:** pembalikan dapat ditelusuri, sandi bersama dihapus dari UI/backend, persetujuan berulang tidak membukukan ulang, dan seluruh rekap sesuai jurnal.

## 6. R03 — Siklus laporan harian

### Temuan kode

- Pengajuan ulang laporan ditolak hanya mencari tanggal hari ini; alasan penolakan lama dihapus dengan `notes = null`.
- API persetujuan memilih laporan pertama pada tanggal hari ini, bukan ID laporan yang dipilih supervisor. Penjagaan statusnya belum setara dengan endpoint web.
- Detail laporan API mengambil transaksi berdasarkan tanggal tanpa filter `teller_id` pemilik laporan.
- Rekap tersimpan dapat tidak lagi sama dengan transaksi jika teller masih bertransaksi setelah pengajuan laporan. Perlu kebijakan penutupan yang jelas.

### Rancangan

Alur target: `Draft → Pending → Approved`, atau `Pending → Rejected → revisi baru → Pending`. Riwayat setiap pengajuan/penolakan tetap tersedia.

1. Semua keputusan memakai ID laporan dan nomor revisi yang jelas. Validasi tanggal, teller, status, serta hak akses di server.
2. Buat revisi yang memuat snapshot total, transaksi yang dicakup, waktu pengajuan, catatan perbaikan teller, penyetuju, dan alasan keputusan. Jangan menimpa revisi lama.
3. Terapkan satu laporan per teller per tanggal operasional, dengan beberapa revisi. Periksa data ganda sebelum menambahkan constraint unik.
4. Izinkan revisi tanggal lampau melalui ID laporan; tanggal transaksi asal tidak digeser agar masuk laporan baru.
5. Usulan awal: saat laporan Pending/Approved, penutupan kas menolak transaksi baru oleh teller untuk tanggal tersebut. Jika perlu dibuka kembali, Supervisor memberi alasan dan membuat riwayat pembukaan kembali. Alternatif ini harus disepakati dengan guru sebelum diterapkan.
6. Rekap operasional boleh menampilkan seluruh transaksi berhasil, tetapi status persetujuan harus jelas. Rekap yang disebut final hanya boleh memakai laporan/revisi yang memenuhi kebijakan finalisasi; jangan mencampur kedua definisi tanpa label.
7. Alasan penolakan harus menunjukkan temuan, bukti/nomor transaksi bila ada, dan tindakan yang diminta. Validasi panjang teks saja tidak dapat menilai kebenaran alasan.

**Kriteria selesai:** dua teller pada tanggal sama tidak tertukar; penolakan dapat direvisi esok hari; catatan lama tetap terlihat; laporan final tidak berubah diam-diam setelah disetujui.

## 7. R04 — Password, PIN, dan sesi

### Temuan kode

Endpoint baru `/api/nasabah/security` memverifikasi kredensial lama, memakai penguncian, dan mencabut token. Endpoint lama `change-password` dan `change-pin` masih memiliki aturan berbeda, termasuk belum mencabut token. Web juga memiliki beberapa jalur perubahan. Reset PIN saja tidak mengubah hash password yang dipakai `auth.session` untuk mendeteksi sesi lama.

### Rancangan

- Satukan validasi dan perubahan dalam layanan yang dipakai web/mobile/teller; otorisasi pemulihan teller tetap berbeda dari perubahan mandiri.
- Jika endpoint lama dipertahankan, delegasikan ke layanan yang sama. Jika dihentikan, tentukan respons dan versi APK yang masih didukung.
- Tambahkan versi keamanan akun bila diperlukan: sesi/token terikat versi, dan reset/pemblokiran menaikkan versi sehingga akses lama ditolak. Jangan mengandalkan perubahan hash password untuk peristiwa reset PIN saja.
- Pertahankan sesi web yang sedang melakukan perubahan mandiri sesuai kebijakan; cabut sesi lain. Pemulihan oleh teller harus mencabut akses lama.
- Usulan berikutnya: kredensial aktivasi/reset bersifat sementara dan wajib diganti. Sebelum diganti, akses hanya ke penyelesaian keamanan dan logout; transaksi dan QR operasional tidak tersedia. Terapkan batas ini di backend, bukan hanya layar HP.
- Jangan menyimpan password/PIN mentah dalam log, audit, ekspor, atau database. Halaman penyerahan kredensial tetap `no-store`.
- Jika respons aktivasi hilang, periksa status rekening/transaksi dahulu. Cetak ulang struk boleh; membukukan setoran ulang tidak boleh. Kredensial yang tidak tersampaikan diterbitkan ulang melalui pemulihan terverifikasi, bukan dibaca dari hash.

**Kriteria selesai:** semua jalur perubahan menolak kredensial salah, tidak mengubah sebagian data, tidak membocorkan rahasia, dan memiliki kebijakan sesi yang sama.

## 8. R05 — Nomor rekening, kode transaksi, dan permintaan ganda

### Temuan kode

Nomor rekening memakai jumlah baris dan pemeriksaan keberadaan tanpa mengunci pengalokasian nomor. Query model normal tidak memasukkan rekening yang dihapus secara soft delete. Kode transaksi biasa memakai jumlah transaksi hari itu ditambah satu. Dua permintaan bersamaan berpotensi memilih nomor sama; constraint unik mencegah duplikasi tersimpan, tetapi pengguna masih bisa mengalami kegagalan.

### Rancangan

- Nomor rekening: tabel urutan per jurusan, kenaikan atomik dengan penguncian, constraint unik, dan larangan mendaur ulang nomor historis/soft-deleted.
- Kode transaksi: gunakan identitas unik yang tidak bergantung pada `count()`, atau sequence terkunci jika format urut wajib dipertahankan. Pisahkan ID internal dari nomor tampilan bila diperlukan.
- Tambahkan kunci idempotensi untuk permintaan pembukuan: identitas aktor, operasi, kunci unik, fingerprint payload, serta referensi hasil. Kunci sama/payload sama mengembalikan hasil lama; payload berbeda ditolak.
- Hindari menyimpan kredensial mentah untuk memutar ulang hasil aktivasi. Respons idempoten cukup menjelaskan aktivasi sudah selesai dan merujuk transaksi.
- Gunakan urutan penguncian yang konsisten, validasi ulang di dalam transaksi, dan penanganan konflik yang tidak mengekspos SQL kepada pengguna.

**Kriteria selesai:** klik ganda dan pengiriman ulang setelah timeout tidak menambah saldo/jurnal dua kali. Uji dua koneksi bersamaan pada MySQL uji; tes berurutan SQLite saja belum cukup.

## 9. R06 — Uji USB dan perilaku mobile

### Kondisi yang belum dibuktikan

Backend pernah merespons dari laptop, tetapi login end-to-end dari APK pada HP belum dikonfirmasi. Penghapusan konfigurasi API tidak otomatis membuktikan kabel, otorisasi perangkat, dan pemetaan port masih aktif.

### Rancangan pemeriksaan untuk sesi uji berikutnya

1. Pastikan APK terbaru terpasang dan backend berasal dari checkout yang sama. Catat versi APK, waktu build, serta versi backend.
2. Jalankan helper USB dari Antigravity saat pengguna siap. Jangan memulai server atau mengganti perangkat secara diam-diam.
3. Uji langsung dari aplikasi: login akun aktif, ambil saldo, ambil mutasi, perubahan kredensial, logout, lalu login ulang.
4. Lepas USB saat aplikasi terbuka. UI harus menunjukkan koneksi terputus; saldo cache diberi label data tersimpan beserta waktu terakhir diperbarui, bukan “saldo terkini”.
5. Cabut token/blokir akun dari web. Respons 401/403 harus membawa aplikasi ke alur yang jelas, membersihkan data sensitif yang sesuai, serta menghentikan polling. Saat offline, aplikasi tidak boleh menyatakan status akun sudah diverifikasi ulang.
6. Pastikan respons non-JSON/HTTP 500 tidak disebut kesalahan password atau kesalahan IP. Tampilkan pesan ramah; rincian teknis untuk pengujian tidak perlu muncul di alur normal siswa.
7. Uji membuka aplikasi tanpa internet eksternal. Audit pemakaian Google Fonts: bila font masih diambil saat runtime, pertimbangkan membundel font dan lisensinya atau memakai font sistem. Ini pemeriksaan lanjutan, bukan temuan bahwa font sudah menyebabkan kegagalan login.

**Kriteria selesai:** ada bukti login dari HP, mutasi/saldo sesuai backend, USB putus ditangani dengan benar, serta kredensial baru benar-benar berlaku setelah login ulang.

## 10. R07 — Status rekening, impor, dan aktivasi

Pertahankan pemisahan berikut:

| Status internal | Makna | Login/transaksi | Perubahan yang sah |
| --- | --- | --- | --- |
| `BelumAktif` | Data terdaftar, pembukaan rekening belum selesai | Ditolak | Aktivasi terverifikasi oleh teller |
| `Aktif` | Rekening dapat digunakan sesuai izin | Diizinkan sesuai otorisasi | Pemblokiran admin |
| `NonAktif` | Rekening diblokir, berbeda dari belum aktivasi | Ditolak | Pembukaan blokir oleh petugas berwenang |

Rencana penyempurnaan:

- Tampilkan istilah konsisten pada admin, teller, web siswa, dan HP. Akun belum aktivasi jangan hanya disebut “telah dinonaktifkan”.
- Selaraskan template CSV, panduan, contoh unduhan, dan dokumen UKK. Tetapkan masa transisi kolom PIN lama; jangan mempertahankan kolom yang seolah digunakan selamanya.
- Tambahkan pratinjau impor: jumlah valid, duplikat, tidak valid, dan alasan per baris. Kebijakan impor sebagian atau seluruhnya harus jelas sebelum tombol konfirmasi.
- Lindungi pengalokasian nomor rekening dan NIS unik termasuk soft delete. Hindari mengirim detail exception database ke layar.
- Akun lama: inventarisasi berdasarkan status, saldo, transaksi, dan riwayat penyerahan. Saldo nol bukan bukti pasti belum pernah dipakai. Konversi hanya untuk akun yang benar-benar belum digunakan dan dikonfirmasi admin.
- Pembukaan blokir tidak boleh dianggap aktivasi baru atau menerima setoran awal kedua.
- Tentukan apakah setoran minimal Rp10.000 adalah aturan UKK/sekolah yang disetujui. Nilai saat ini berasal dari aplikasi; jangan menyebutnya aturan universal.

**Kriteria selesai:** tidak ada saldo muncul hanya karena impor, seluruh hasil impor dapat dijelaskan, dan akun lama tidak berubah massal tanpa peninjauan.

## 11. R08 — Tanggal operasional dan kas per teller

### Temuan kode

`config/app.php` menggunakan UTC. Beberapa controller memakai `date()`/`now()` untuk menentukan “hari ini”. Di `TellerController::laporan()`, saldo awal mengambil jurnal kas sebelum hari ini tanpa filter teller, sedangkan arus hari ini difilter berdasarkan teller. Akibatnya cakupan saldo awal dan arus harian berbeda.

### Rancangan

- Tentukan zona waktu operasional sekolah. Usulan untuk Bantul: `Asia/Jakarta`; jangan memakai zona perangkat secara otomatis. Putuskan apakah penyimpanan tetap UTC dengan konversi batas hari operasional.
- Audit semantik timestamp lama sebelum migrasi. Jangan menggeser seluruh tanggal/jam database secara massal tanpa mengetahui format asal.
- Pilih model kas: kas sekolah terpusat atau kas per teller/sesi loket. Jika per teller, perlu kas pembukaan, serah-terima, penerimaan/pengeluaran, dan saldo penutupan yang konsisten. Jika terpusat, jangan menyajikan kas global sebagai kas milik seorang teller.
- Pertahankan periode mingguan berdasarkan blok tanggal dalam bulan bila disetujui guru. Label harus menyebut pembagian tanggal; tidak mengklaim minggu kalender Senin–Minggu atau tujuh hari kerja.
- Validasi bulan/tahun pada rekap bulanan juga. Selaraskan rentang tahun UI dengan backend dan data historis.

**Kriteria selesai:** transaksi sekitar pergantian hari masuk laporan yang tepat; angka pembukaan + arus bersih = penutupan dengan cakupan kas yang sama; Februari dan tanggal 31 tercakup.

## 12. R09 — Audit dan penyajian

Aktivasi/reset/penandaan belum aktif saat ini dicatat melalui log aplikasi. Log ini belum sama dengan riwayat audit bisnis terstruktur pada halaman supervisor.

Usulkan tabel audit append-only dengan aktor, jenis aktor, tindakan, sasaran, waktu, alasan, ID transaksi/laporan, dan perubahan status yang diizinkan. Jangan memasukkan password, PIN, token, atau salinan mentah request. Hak akses membaca audit harus dibatasi; operasi bisnis tidak boleh diam-diam menghapus jejak audit.

Setelah data benar, tambahkan pencarian/paginasi, tampilan kosong/error yang konsisten, cetak/ekspor laporan dengan periode dan status persetujuan, serta aksesibilitas formulir. Jangan memprioritaskan dashboard dekoratif, WhatsApp/OTP, hosting publik, atau banyak pilihan jaringan sebelum alur inti stabil.

## 13. Rancangan perubahan skema — belum menjadi migrasi

| Usulan struktur | Data utama | Tujuan |
| --- | --- | --- |
| `account_sequences` | prefix unik, nomor terakhir | Alokasi rekening atomik |
| `transaction_requests` | aktor, jenis operasi, idempotency key unik dalam scope, fingerprint, result ID | Mencegah pembukuan ulang |
| `transaction_reversals` | transaksi asal, transaksi pembalik, pemohon/penyetuju, status, alasan | Koreksi dapat ditelusuri |
| `daily_report_revisions` | laporan, versi unik per laporan, total, cutoff, snapshot referensi, status | Riwayat revisi laporan |
| `audit_events` | aktor, tindakan, sasaran, waktu, metadata tanpa rahasia | Audit bisnis |
| Kolom keamanan customer | versi keamanan, kewajiban ganti kredensial awal | Pengendalian sesi dan onboarding |

Tentukan tipe, foreign key, index, constraint, dan kebijakan retensi setelah memeriksa skema nyata. Jangan langsung menjalankan tabel usulan ini. Untuk data keuangan lama, perubahan versi sebaiknya tetap dapat dibaca oleh kode selama masa transisi; rollback aplikasi tidak boleh membuang jurnal atau bukti transaksi baru.

## 14. Urutan pelaksanaan yang disarankan

1. **Persiapan:** periksa perubahan pengguna yang belum di-commit, snapshot kode, backup database, dan uji pemulihan di salinan lokal. Jangan menjalankan `migrate:fresh` pada database demo/utama.
2. **Paket A — akses:** R01 dan penutupan perbedaan endpoint keamanan R04. Uji matriks role/status dan sesi lama.
3. **Paket B — uang:** R05, kemudian R02 dan cakupan kas R08. Uji saldo/jurnal serta permintaan bersamaan pada MySQL uji.
4. **Paket C — laporan:** R03 dan sisa periode/zona waktu R08. Uji dua teller, revisi lintas hari, serta laporan final.
5. **Paket D — pengalaman siswa:** R07 dan R06. Build APK setelah kontrak API stabil, baru uji HP bersama pengguna.
6. **Paket E — penyelesaian:** R09, bukti uji UKK, dan penyesuaian dokumen rancangan agar sesuai implementasi akhir.

Setiap paket harus memiliki diff yang dapat ditinjau, tes yang relevan, dan catatan hasil. Jangan menggabungkan semua perubahan ke satu paket besar. Jika ada keputusan operasional belum jelas, kerjakan bagian teknis independen dahulu dan konfirmasikan keputusan tersebut sebelum mengubah pembukuan.

## 15. Checklist penerimaan untuk sesi berikutnya

- [ ] Token siswa tidak dapat membaca data siswa lain atau endpoint petugas.
- [ ] Petugas nonaktif tidak dapat login maupun memakai sesi/token lama.
- [ ] Impor tidak membuat penerimaan kas dan tidak mengaktifkan rekening.
- [ ] Aktivasi menghasilkan satu transaksi dan jurnal seimbang, sekalipun tombol diklik ulang.
- [ ] Kegagalan jurnal membatalkan perubahan status dan saldo.
- [ ] Pembukaan blokir tidak menambah setoran awal.
- [ ] Nomor rekening tidak bentrok atau dipakai ulang setelah soft delete.
- [ ] Konfirmasi penarikan berulang hanya mendebit satu kali; uji konkurensi nyata selesai.
- [ ] Pembalikan transaksi membutuhkan otorisasi dan sesuai seluruh rekap.
- [ ] Revisi laporan menyimpan catatan penolakan serta transaksi teller yang benar.
- [ ] Laporan tanggal lampau dapat diperbaiki tanpa memindahkan tanggal transaksi.
- [ ] Data sekitar tengah malam dan akhir bulan masuk periode yang benar.
- [ ] Semua jalur perubahan kredensial menjalankan kebijakan sesi yang sama.
- [ ] Kredensial awal/reset diserahkan tanpa menyimpan rahasia mentah dalam audit.
- [ ] APK terbaru login dari HP melalui USB dan menampilkan data backend yang benar.
- [ ] USB terputus, token dicabut, serta HTTP 500 memiliki perilaku yang jelas.
- [ ] Backup dapat dipulihkan di lingkungan uji; rollback tidak menghilangkan transaksi.
- [ ] Dokumen UKK, template CSV, UI web, API, dan APK menjelaskan alur yang sama.

## 16. Berkas utama untuk pelaksana

Path berikut relatif terhadap root masing-masing proyek.

Backend: `routes/api.php`, `routes/web.php`, `app/Http/Middleware/RoleMiddleware.php`, `app/Http/Middleware/EnsureActiveCustomer.php`, `app/Http/Controllers/Api/AuthController.php`, `CustomerController.php`, `TransactionController.php`, `ReportController.php`, `NasabahAuthController.php`, `CustomerSecurityController.php`, serta controller Web `AdminController.php`, `TellerController.php`, `SupervisorController.php`, `NasabahController.php`, `CustomerLifecycleController.php`; `app/Services/CustomerOnboarding.php`; `config/app.php`; model dan migrasi terkait.

Mobile: `lib/core/constants/api_constants.dart`, `lib/services/api_service.dart`, `lib/screens/login_screen.dart`, `security_screen.dart`, `dashboard_tab.dart`, `profile_tab.dart`, `splash_screen.dart`; `connect-usb.ps1`; konfigurasi build Android dan tes.

Titik awal tes backend saat ini: `tests/Feature/LoginSecurityPeriodTest.php`. Saat cakupan bertambah, pisahkan pengujian menjadi berkas per domain dengan fixture bersama; jangan menghapus skenario regresi lama hanya agar suite lulus. Adaptor SQLite untuk constraint transaksi dalam tes bukan bukti migrasi telah diuji pada MySQL.

## 17. Instruksi pembuka untuk sesi implementasi

> Baca blueprint ini dan periksa kembali kode serta aturan proyek. Mulai dari Paket A, bukan menjalankan semua usulan sekaligus. Laporkan perubahan sejak blueprint dibuat. Gunakan database uji untuk verifikasi; jangan mengubah status akun atau saldo lama tanpa peninjauan. Pertahankan koneksi mobile khusus USB dan jangan menambahkan kembali menu konfigurasi API. Server dan ADB tetap dijalankan pengguna melalui Antigravity kecuali pengguna memberikan instruksi baru. Sebutkan hasil yang benar-benar diuji dan bagian yang masih belum terverifikasi.
