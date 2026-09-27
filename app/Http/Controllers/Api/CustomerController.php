<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Accounting_Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    //menampilkan daftar data nasabah
    public function index(Request $request)
    {
        $query = Customer::query();

        //filter berdasarkan kelas jika ada
        if ($request->has('class')) {
            $query->where('class', $request->class);
        }

        $customers = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar data nasabah berhasil diambil',
            'data'    => $customers
        ], 200);
    }

    //membuat rekening nasabah baru
    public function store(Request $request)
    {
        //1. Validasi input
        $request->validate([
            'student_id'      => 'required|string|unique:customers,student_id',
            'student_name'    => 'required|string|max:150',
            'class'           => 'required|string|max:50',
            'initial_deposit' => 'nullable|numeric|min:0',
            'pin'             => 'required|numeric|digits_between:4,6'
        ]);

        //2. Generate nomor rekening otomatis
        $accountNumber = $this->generateAccountNumber($request->class);
        $initialDeposit = $request->initial_deposit ?? 0;

        DB::beginTransaction();

        try {
            //3. Simpan data nasabah baru
            $customer = Customer::create([
                'account_number' => $accountNumber,
                'student_id'     => $request->student_id,
                'student_name'   => $request->student_name,
                'class'          => $request->class,
                'balance'        => $initialDeposit,
                'pin'            => Hash::make($request->pin)
            ]);

            //4. Jika ada setoran awal, buat transaksi dan jurnal pembukuan
            if ($initialDeposit > 0) {
                $transaction = Transaction::create([
                    'transaction_code' => 'DEP-' . time(),
                    'customer_id'      => $customer->id,
                    'user_id'          => $request->user()->id,
                    'transaction_date' => now(),
                    'transaction_type' => 'Setoran',
                    'amount'           => $initialDeposit,
                    'description'      => 'Setoran Awal Buka Rekening'
                ]);

                //kas bank bertambah (Debet)
                Accounting_Journal::create([
                    'transaction_id' => $transaction->id,
                    'account_code'   => '111',
                    'position'       => 'Debet',
                    'amount'         => $initialDeposit
                ]);

                //kewajiban bank ke nasabah bertambah (Kredit)
                Accounting_Journal::create([
                    'transaction_id' => $transaction->id,
                    'account_code'   => '211',
                    'position'       => 'Kredit',
                    'amount'         => $initialDeposit
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rekening nasabah baru berhasil dibuat',
                'data'    => $customer
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat rekening nasabah: ' . $e->getMessage()
            ], 500);
        }
    }

    //menampilkan detail satu data nasabah
    public function show($id)
    {
        $customer = Customer::with(['transactions' => function ($q) {
            $q->orderBy('id', 'desc')->take(10);
        }])->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data nasabah tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail data nasabah berhasil diambil',
            'data'    => $customer
        ], 200);
    }

    //mengupdate data nasabah
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data nasabah tidak ditemukan'
            ], 404);
        }

        //1. Validasi input
        $request->validate([
            'student_id'   => 'required|string|unique:customers,student_id,' . $id,
            'student_name' => 'required|string|max:150',
            'class'        => 'required|string|max:50',
            'pin'          => 'nullable|numeric|digits_between:4,6'
        ]);

        //2. Data yang akan diupdate
        $data = [
            'student_id'   => $request->student_id,
            'student_name' => $request->student_name,
            'class'        => $request->class
        ];

        //ganti pin jika diisi
        if ($request->filled('pin')) {
            $data['pin'] = Hash::make($request->pin);
        }

        $customer->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data nasabah berhasil diperbarui',
            'data'    => $customer
        ], 200);
    }

    //menghapus data rekening nasabah
    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data nasabah tidak ditemukan'
            ], 404);
        }

        //cek jika nasabah masih ada saldo
        if ($customer->balance > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus rekening nasabah yang masih memiliki saldo'
            ], 400);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data nasabah berhasil dihapus'
        ], 200);
    }

    //cari nasabah berdasarkan nama, NIS, atau no rekening (untuk loket teller)
    public function search(Request $request)
    {
        $keyword = $request->query('q');

        if (!$keyword) {
            return response()->json([
                'success' => false,
                'message' => 'Kata kunci pencarian tidak boleh kosong'
            ], 400);
        }

        $customers = Customer::where('student_name', 'like', "%{$keyword}%")
            ->orWhere('student_id', 'like', "%{$keyword}%")
            ->orWhere('account_number', 'like', "%{$keyword}%")
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Hasil pencarian nasabah berhasil diambil',
            'data'    => $customers
        ], 200);
    }

    //cari nasabah dari no rekening (scan qr code)
    public function findByAccountNumber($account_number)
    {
        $customer = Customer::where('account_number', $account_number)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Rekening nasabah tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data rekening nasabah berhasil ditemukan',
            'data'    => $customer
        ], 200);
    }

    //helper generate nomor rekening otomatis
    private function generateAccountNumber(string $class): string
    {
        $upperClass = strtoupper($class);
        $prefix = 'BM';

        if (str_contains($upperClass, 'AKL') || str_contains($upperClass, 'AK')) {
            $prefix = 'AK';
        } elseif (str_contains($upperClass, 'RPL')) {
            $prefix = 'RPL';
        } elseif (str_contains($upperClass, 'TKJ')) {
            $prefix = 'TKJ';
        } elseif (str_contains($upperClass, 'OTKP') || str_contains($upperClass, 'MP')) {
            $prefix = 'MP';
        } elseif (str_contains($upperClass, 'BDP') || str_contains($upperClass, 'BR')) {
            $prefix = 'BR';
        }

        $count = Customer::where('account_number', 'like', "{$prefix}-%")->count() + 1;
        $accountNumber = sprintf('%s-%04d', $prefix, $count);

        while (Customer::where('account_number', $accountNumber)->exists()) {
            $count++;
            $accountNumber = sprintf('%s-%04d', $prefix, $count);
        }

        return $accountNumber;
    }
}
