<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Accounting_Journal;
use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerOnboarding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function nasabah(Request $request)
    {
        $query = Customer::withTrashed();
        if ($request->search) {
            $query->where('student_name', 'like', "%{$request->search}%")
                ->orWhere('student_id', 'like', "%{$request->search}%")
                ->orWhere('account_number', 'like', "%{$request->search}%");
        }
        $customers = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return view('admin.nasabah', compact('customers'));
    }

    public function tambahNasabah()
    {
        return view('admin.nasabah_form');
    }

    public function importNasabahView()
    {
        return view('admin.import_nasabah');
    }

    public function downloadNasabahTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_import_nasabah.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['nis', 'nama_siswa', 'kelas', 'pin', 'setoran_awal']);
            fputcsv($file, ['2026001', 'Ahmad Pratama', 'X RPL 1', '', '0']);
            fputcsv($file, ['2026002', 'Budi Santoso', 'X AKL 2', '', '0']);
            fputcsv($file, ['2026003', 'Citra Lestari', 'X DKV 1', '', '0']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importNasabahProcess(Request $request)
    {
        $upload = $request->file('file');
        if ($upload && ! $upload->isValid()) {
            $message = match ($upload->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran CSV melebihi batas upload PHP. Gunakan berkas maksimal 2 MB.',
                UPLOAD_ERR_PARTIAL => 'Upload CSV terputus. Pilih ulang berkas dan coba lagi.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara upload PHP tidak tersedia. Jalankan ulang server Antigravity setelah perbaikan TEMP/TMP, lalu pilih ulang CSV.',
                UPLOAD_ERR_CANT_WRITE => 'PHP tidak dapat menulis berkas sementara. Periksa izin tulis folder TEMP/TMP pada server.',
                UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP. Periksa konfigurasi server.',
                default => 'Upload CSV gagal. Pilih ulang berkas dan coba lagi.',
            };

            return back()->withErrors(['file' => $message.' (Kode upload: '.$upload->getError().')']);
        }

        $request->validate([
            'file' => 'required|file|extensions:csv|max:2048',
        ], [
            'file.required' => 'Pilih berkas CSV terlebih dahulu.',
            'file.extensions' => 'Gunakan berkas .csv, bukan workbook .xlsx.',
            'file.max' => 'Ukuran CSV maksimal 2 MB.',
            'file.uploaded' => 'PHP gagal menerima berkas. Periksa folder sementara upload dan pilih ulang CSV.',
        ]);

        $filePath = $request->file('file')->getRealPath();
        $file = fopen($filePath, 'r');

        $header = fgetcsv($file, 0, ',', '"', '');
        if ($header) {
            $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        }
        if ($header !== ['nis', 'nama_siswa', 'kelas', 'pin', 'setoran_awal']) {
            fclose($file);

            return back()->withErrors(['file' => 'Header CSV harus nis,nama_siswa,kelas,pin,setoran_awal dengan pemisah koma.']);
        }

        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $rowNumber = 1;

        while (($data = fgetcsv($file, 0, ',', '"', '')) !== false) {
            $rowNumber++;

            if ($data === [null]) {
                continue;
            }
            if (count($data) !== 5) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: wajib berisi 5 kolom.";

                continue;
            }

            $nis = trim($data[0]);
            $nama = trim($data[1]);
            $kelas = trim($data[2]);
            $pin = trim($data[3]);
            $setoranAwal = trim($data[4]);

            if ($nis === '' || $nama === '' || $kelas === '' || strlen($nis) > 50 || strlen($nama) > 150 || strlen($kelas) > 50
                || ! in_array($setoranAwal, ['', '0'], true)) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: identitas wajib valid dan setoran_awal harus kosong atau 0. Setoran diterima teller saat aktivasi.";

                continue;
            }

            if (Customer::withTrashed()->where('student_id', $nis)->exists()) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: NIS {$nis} ({$nama}) sudah terdaftar di sistem.";

                continue;
            }

            if ($pin !== '' && ! preg_match('/^[0-9]{4,6}$/D', $pin)) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: PIN untuk NIS {$nis} harus berupa 4 - 6 digit angka.";

                continue;
            }

            DB::beginTransaction();
            try {
                app(CustomerOnboarding::class)->register($nis, $nama, $kelas);

                DB::commit();
                $successCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: Gagal menyimpan NIS {$nis} (".$e->getMessage().').';
            }
        }

        fclose($file);

        $message = "Proses impor selesai: {$successCount} nasabah baru disimpan nonaktif, saldo Rp0. Password/PIN baru dibuat saat aktivasi di teller; PIN CSV lama tidak digunakan.";
        if ($failedCount > 0) {
            $message .= " ({$failedCount} data dilewati)";
        }

        return redirect()->route('admin.nasabah.import')
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    public function simpanNasabah(Request $request)
    {
        $data = $request->validate([
            'student_id' => [
                'required',
                'regex:/^[0-9]{4,12}$/',
                'unique:customers,student_id',
            ],
            'student_name' => [
                'required',
                'string',
                'min:3',
                'max:150',
                'regex:/^[a-zA-Z\s\.,\'-]+$/',
            ],
            'class' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^(X|XI|XII)\s+(AKL|LPS|MP|BR|BD|TKJ|DKV|RPL|AK|MPLB|BDP)\s+[0-9]+$/i',
            ],
            'jurusan' => 'nullable|string',
            'initial_deposit' => 'nullable|integer|in:0',
        ], [
            'student_id.required' => 'NIS Siswa wajib diisi.',
            'student_id.regex' => 'NIS harus berupa deret angka (4 - 12 digit), tidak boleh huruf atau acak.',
            'student_id.unique' => 'NIS ini sudah terdaftar di sistem.',
            'student_name.required' => 'Nama lengkap siswa wajib diisi.',
            'student_name.min' => 'Nama siswa minimal 3 huruf.',
            'student_name.regex' => 'Nama siswa hanya boleh berisi huruf dan tanda baca resmi nama.',
            'class.required' => 'Kelas siswa wajib diisi.',
            'class.regex' => 'Format kelas harus valid, contoh: X RPL 1, XI AKL 2, atau XII MPLB 1.',
            'initial_deposit.in' => 'Setoran awal dicatat teller saat aktivasi, bukan saat pendaftaran.',
        ]);

        app(CustomerOnboarding::class)->register(
            trim($data['student_id']),
            trim($data['student_name']),
            strtoupper(trim($data['class']))
        );

        return redirect()->route('admin.nasabah')->with('success', 'Data nasabah berhasil didaftarkan dengan status Nonaktif (saldo Rp0). Kredensial dan setoran awal akan diproses saat aktivasi.');
    }

    public function editNasabah($id)
    {
        $customer = Customer::findOrFail($id);

        return view('admin.nasabah_form', compact('customer'));
    }

    public function updateNasabah(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'student_id' => [
                'required',
                'regex:/^[0-9]{4,12}$/',
                'unique:customers,student_id,'.$id,
            ],
            'student_name' => [
                'required',
                'string',
                'min:3',
                'max:150',
                'regex:/^[a-zA-Z\s\.,\'-]+$/',
            ],
            'class' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^(X|XI|XII)\s+(AKL|LPS|MP|BR|BD|TKJ|DKV|RPL|AK|MPLB|BDP)\s+[0-9]+$/i',
            ],
            'jurusan' => 'nullable|string',
            'pin' => 'prohibited',
        ], [
            'student_id.regex' => 'NIS harus berupa deret angka (4 - 12 digit).',
            'student_name.min' => 'Nama siswa minimal 3 huruf.',
            'student_name.regex' => 'Nama siswa hanya boleh berisi huruf resmi nama.',
            'class.regex' => 'Format kelas harus valid, contoh: X RPL 1, XI AKL 2, atau XII MPLB 1.',
        ]);

        $customer->update([
            'student_id' => trim($request->student_id),
            'student_name' => trim($request->student_name),
            'class' => strtoupper(trim($request->class)),
        ]);

        return redirect()->route('admin.nasabah')->with('success', 'Data Nasabah berhasil diupdate.');
    }

    public function statusNasabah($id)
    {
        $message = 'Status nasabah berhasil diperbarui.';

        DB::transaction(function () use ($id, &$message): void {
            $customer = Customer::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($customer->status === Customer::STATUS_PENDING) {
                // Rekening belum aktivasi wajib melalui loket Teller dengan setoran awal tunai
                $message = "Nasabah {$customer->student_name} berstatus belum aktivasi. Aktivasi resmi wajib dilakukan di loket Teller dengan setoran awal minimal Rp 10.000.";
                return;
            }
            $customer->status = $customer->status === 'Aktif' ? 'NonAktif' : 'Aktif';
            $customer->save();
            $customer->tokens()->delete();
        });

        return redirect()->route('admin.nasabah')->with('success', $message);
    }

    public function cetakQrNasabah($id)
    {
        $customer = Customer::findOrFail($id);

        return view('admin.cetak_qr', compact('customer'));
    }

    public function petugas()
    {
        $users = User::orderBy('role')->orderBy('staff_name')->get();

        return view('admin.petugas', compact('users'));
    }

    public function tambahPetugas()
    {
        return view('admin.petugas_form');
    }

    public function simpanPetugas(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'staff_name' => 'required|string',
            'role' => 'required|in:Administrator,Supervisor,Teller',
            'password' => 'required|min:6',
        ]);

        User::create([
            'username' => $request->username,
            'staff_name' => $request->staff_name,
            'role' => $request->role,
            'password' => bcrypt($request->password),
        ]);

        return redirect()->route('admin.petugas')->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function editPetugas($id)
    {
        $user = User::findOrFail($id);

        return view('admin.petugas_form', compact('user'));
    }

    public function updatePetugas(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => 'required|unique:users,username,'.$id,
            'staff_name' => 'required|string',
            'role' => 'required|in:Administrator,Supervisor,Teller',
            'password' => 'nullable|min:6',
        ]);

        $user->update([
            'username' => $request->username,
            'staff_name' => $request->staff_name,
            'role' => $request->role,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => bcrypt($request->password)]);
        }

        return redirect()->route('admin.petugas')->with('success', 'Data Petugas berhasil diupdate.');
    }

    public function resetPasswordPetugas($id)
    {
        $user = User::findOrFail($id);
        // Buat password acak yang unik di setiap percobaan
        $randomPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8);
        $user->password = Hash::make($randomPassword);
        $user->save();

        \Illuminate\Support\Facades\Log::info("Administrator mereset kata sandi petugas: {$user->username} ({$user->staff_name}).");

        return redirect()->route('admin.petugas')->with('success', "Kata sandi petugas {$user->staff_name} berhasil direset menjadi: {$randomPassword} (Catat dan serahkan kepada petugas yang bersangkutan).");
    }

    public function statusPetugas($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.petugas')->with('error', 'Tidak dapat memblokir akun Anda sendiri.');
        }
        $user->status = $user->status === 'Aktif' ? 'NonAktif' : 'Aktif';
        $user->save();

        return redirect()->route('admin.petugas')->with('success', 'Status petugas berhasil diubah.');
    }

    public function audit(Request $request)
    {
        $query = Accounting_Journal::with(['transaction.customer', 'transaction.user']);

        if ($request->date) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->whereDate('transaction_date', $request->date);
            });
        }

        if ($request->teller_id) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->where('user_id', $request->teller_id);
            });
        }

        $total_debet = (clone $query)->where('position', 'Debet')->sum('amount');
        $total_kredit = (clone $query)->where('position', 'Kredit')->sum('amount');

        $journals = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $tellers = User::where('role', 'Teller')->get();

        return view('admin.audit', compact('journals', 'total_debet', 'total_kredit', 'tellers'));
    }
}
