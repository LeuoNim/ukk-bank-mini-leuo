<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Accounting_Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TransactionController extends Controller
{
    //menampilkan riwayat transaksi
    public function index(Request $request)
    {
        $query = Transaction::with(['customer', 'user']);

        //jika yang akses adalah Teller, hanya tampilkan transaksi miliknya
        if ($request->user()->role === 'Teller') {
            $query->where('user_id', $request->user()->id);
        }

        //filter tanggal jika ada
        if ($request->has('date')) {
            $query->whereDate('transaction_date', $request->date);
        }

        //filter jenis transaksi jika ada
        if ($request->has('type')) {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->orderBy('id', 'desc')->take(100)->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat transaksi berhasil diambil',
            'data'    => $transactions
        ], 200);
    }

    public function deposit(Request $request)
    {
        //1. Validasi input dari petugas
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount'      => 'required|numeric|min:1',
            'description' => 'nullable|string'
        ]);

        //Mulai pengawasan proses ke db
        DB::beginTransaction();

        try {
            //2. Cetak riwayat transaksi utama
            $transaction = Transaction::create([
                'transaction_code' => 'DEP-' . time(),
                'customer_id'      => $request->customer_id,
                'user_id'          => $request->user()->id,
                'transaction_date' => now(),
                'transaction_type' => 'Setoran',
                'amount'           => $request->amount,
                'description'      => $request->description ?? 'Setoran Tunai'
            ]);

            //3, Tambah saldo nasabah
            $customer = Customer::find($request->customer_id);
            $customer->balance += $request->amount;
            $customer->save();

            //4. Pembukuan jurnal akuntansi
            // Posisi 1: uang fisik di kas bank bertambah (Debet)
            Accounting_Journal::create([
                'transaction_id' => $transaction->id,
                'account_code'   => '111',
                'position'       => 'Debet',
                'amount'         => $request->amount
            ]);

            //posisi 2: kewajiban bank ke nasabah bertambah (kredit)
            Accounting_Journal::create([
                'transaction_id' => $transaction->id,
                'account_code'   => '211',
                'position'       => 'Kredit',
                'amount'         => $request->amount
            ]);

            //Jika langkah2 di atas lancar, simpan ke db secara permanen
            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Transaksi setoran berhasil dicatat',
                'data'     => $transaction
            ], 201);
        } catch (\Exception $e) {
            //Jika ada satu yang error. Batalkan semua!
            DB::rollBack();

            return response()->json([
                'success'  => false,
                'message'  => 'Transaksi gagal, sistem dibatalkan' . $e->getMessage()   
            ], 500);
        }
    }

    public function withdraw(Request $request) 
    {
        //1. validasi input
        $request->validate([
            'customer_id'  => 'required|exists:customers,id',
            'amount'       => 'required|numeric|min:1',
            'pin'          => 'required|numeric|digits_between:4,6',
            'description'  => 'nullable|string'
        ]);

        $customer = Customer::find($request->customer_id);

        //2. validasi keamanan pin
        if (!Hash::check($request->pin, $customer->pin))
            {
                return response()->json([
                    'success'  => false,
                    'message'  => 'Transaksi Ditolak. Pin nasabah salah.'
                ], 403);
            }

        //3. validasi batas saldo minimum  (Rp.10.000)
        if (($customer->balance - $request->amount) < 10000) 
            {
                return response()->json([
                    'success'  => false,
                    'message'  => 'Transasksi Ditolak: Sisa saldo setelah penarikan minimal Rp.10.000'
                ], 400);
            } 
        
        DB::beginTransaction();

        try {
            //4. Catat riwayat transaksi penarikan
            $transaction = Transaction::create([
                'transaction_code'  => 'WDR-' . time(),
                'customer_id'       => $request->customer_id,
                'user_id'           => $request->user()->id,
                'transaction_date'  => now(),
                'transaction_type'  => 'Penarikan',
                'amount'            => $request->amount,
                'description'       => $request->description ?? 'Penarikan Tunai'
            ]);

            //5, kurangi saldo nasabah
            $customer->balance -= $request->amount;
            $customer->save();

            //6. Pembukuan jurnal akuntansi
            Accounting_Journal::create([
                'transaction_id'  => $transaction->id,
                'account_code'    => '211',
                'position'        => 'Debet',
                'amount'          => $request->amount
            ]);

            Accounting_Journal::create([
                'transaction_id'  => $transaction->id,
                'account_code'    => '111',
                'position'        => 'Kredit',
                'amount'          => $request->amount   
            ]);

            //simpan ke database
            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Transakaksi penarikan tunai berhasil dicatat',
                'data'     => $transaction
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success'  => false,
                'message'  => 'Transaksi penarikan tunai gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}
