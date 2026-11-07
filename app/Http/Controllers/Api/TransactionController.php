<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Accounting_Journal;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    // menampilkan riwayat transaksi
    public function index(Request $request)
    {
        $query = Transaction::with(['customer', 'user']);

        // jika yang akses adalah Teller, hanya tampilkan transaksi miliknya
        if ($request->user()->role === 'Teller') {
            $query->where('user_id', $request->user()->id);
        }

        // filter tanggal jika ada
        if ($request->has('date')) {
            $query->whereDate('transaction_date', $request->date);
        }

        // filter jenis transaksi jika ada
        if ($request->has('type')) {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->orderBy('id', 'desc')->take(100)->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat transaksi berhasil diambil',
            'data' => $transactions,
        ], 200);
    }

    private function generateTransactionCode($type)
    {
        $prefix = $type === 'Setoran' ? 'DEP' : 'WDR';
        $date = now()->format('Ymd');
        $count = Transaction::whereDate('transaction_date', now()->format('Y-m-d'))->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }

    public function deposit(Request $request)
    {
        // 1. Validasi input dari petugas
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1000|multiple_of:1000',
            'description' => 'nullable|string',
        ]);

        if ($request->has('is_koreksi') && $request->is_koreksi == '1') {
            if ($request->sandi_koreksi !== 'KOREKSI123') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi Ditolak. Kode sandi koreksi salah.',
                ], 403);
            }
            $type = 'Setoran Koreksi';
            $modifier = -$request->amount;
            $pos_kas = 'Kredit';
            $pos_kewajiban = 'Debet';
        } else {
            $type = 'Setoran';
            $modifier = $request->amount;
            $pos_kas = 'Debet';
            $pos_kewajiban = 'Kredit';
        }

        // Mulai pengawasan proses ke db
        DB::beginTransaction();

        try {
            $customer = Customer::whereKey($request->customer_id)->lockForUpdate()->firstOrFail();
            if ($customer->status !== 'Aktif') {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Rekening belum aktif atau diblokir. Setoran ditolak.'], 403);
            }
            if ($customer->balance + $modifier < 0) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Koreksi tidak boleh membuat saldo negatif.'], 422);
            }
            $transaction = Transaction::create([
                'transaction_code' => $this->generateTransactionCode($type),
                'customer_id' => $request->customer_id,
                'user_id' => $request->user()->id,
                'transaction_date' => now(),
                'transaction_type' => $type,
                'amount' => $request->amount,
                'description' => $request->description ?? $type,
            ]);

            // 3, Tambah/kurangi saldo nasabah
            $customer->balance += $modifier;
            $customer->save();

            // 4. Pembukuan jurnal akuntansi
            Accounting_Journal::create([
                'transaction_id' => $transaction->id,
                'account_code' => '111',
                'position' => $pos_kas,
                'amount' => $request->amount,
            ]);

            Accounting_Journal::create([
                'transaction_id' => $transaction->id,
                'account_code' => '211',
                'position' => $pos_kewajiban,
                'amount' => $request->amount,
            ]);

            // Jika langkah2 di atas lancar, simpan ke db secara permanen
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi setoran berhasil dicatat',
                'data' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            // Jika ada satu yang error. Batalkan semua!
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Transaksi gagal, sistem dibatalkan'.$e->getMessage(),
            ], 500);
        }
    }

    public function withdraw(Request $request)
    {
        // 1. validasi input
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1000|multiple_of:1000',
            'description' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $customer = Customer::whereKey($request->customer_id)->lockForUpdate()->first();
            if (! $customer || $customer->status !== 'Aktif') {
                return response()->json(['success' => false, 'message' => 'Rekening belum aktif atau diblokir. Penarikan ditolak.'], 403);
            }

            if ($request->has('is_koreksi') && $request->is_koreksi == '1') {
                if ($request->sandi_koreksi !== 'KOREKSI123') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaksi Ditolak. Kode sandi koreksi salah.',
                    ], 403);
                }
                $type = 'Penarikan Koreksi';
                $modifier = $request->amount; // Penarikan koreksi adds back the balance
                $pos_kas = 'Debet';
                $pos_kewajiban = 'Kredit';

                // Validasi batas saldo minimum
                if (($customer->balance + $modifier) < 10000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaksi Ditolak: Sisa saldo setelah penarikan minimal Rp 10.000',
                    ], 400);
                }

                DB::beginTransaction();

                try {
                    $transaction = Transaction::create([
                        'transaction_code' => $this->generateTransactionCode($type),
                        'customer_id' => $request->customer_id,
                        'user_id' => $request->user()->id,
                        'transaction_date' => now(),
                        'transaction_type' => $type,
                        'amount' => $request->amount,
                        'description' => $request->description ?? $type,
                        'status' => 'success',
                    ]);

                    $customer->balance += $modifier;
                    $customer->save();

                    Accounting_Journal::create([
                        'transaction_id' => $transaction->id,
                        'account_code' => '111',
                        'position' => $pos_kas,
                        'amount' => $request->amount,
                    ]);

                    Accounting_Journal::create([
                        'transaction_id' => $transaction->id,
                        'account_code' => '211',
                        'position' => $pos_kewajiban,
                        'amount' => $request->amount,
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'status' => 'success',
                        'message' => 'Transaksi penarikan koreksi berhasil dicatat',
                        'data' => $transaction,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Transaksi penarikan koreksi gagal: '.$e->getMessage(),
                    ], 500);
                }
            }

            // Penarikan biasa (tanpa input PIN di teller; memerlukan otorisasi PIN oleh nasabah di aplikasi mobile)
            // 1. Validasi batas saldo minimum (Rp. 10.000)
            if (($customer->balance - $request->amount) < 10000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi Ditolak: Sisa saldo setelah penarikan minimal Rp 10.000 (Saldo saat ini: Rp '.number_format($customer->balance, 0, ',', '.').')',
                ], 400);
            }

            // 2. Cek apakah ada permintaan penarikan sebelumnya yang masih pending
            $existingPending = Transaction::where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->first();

            if ($existingPending) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masih ada permintaan penarikan sebesar Rp '.number_format($existingPending->amount, 0, ',', '.').' yang menunggu otorisasi PIN nasabah.',
                    'data' => $existingPending,
                ], 422);
            }

            // 3. Buat transaksi penarikan berstatus 'pending'
            $transaction = Transaction::create([
                'transaction_code' => $this->generateTransactionCode('Penarikan'),
                'customer_id' => $request->customer_id,
                'user_id' => $request->user()->id,
                'transaction_date' => now(),
                'transaction_type' => 'Penarikan',
                'amount' => $request->amount,
                'description' => $request->description ?? 'Penarikan Tunai',
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'status' => 'pending',
                'message' => 'Permintaan penarikan Rp '.number_format($request->amount, 0, ',', '.').' berhasil diajukan. Menunggu konfirmasi PIN nasabah di aplikasi mobile.',
                'data' => $transaction,
            ], 200);
        });
    }
}
