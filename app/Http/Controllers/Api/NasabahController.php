<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Accounting_Journal;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class NasabahController extends Controller
{
    // cek saldo nasabah
    public function saldo(Request $request)
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Informasi saldo berhasil diambil',
            'data' => [
                'id' => $customer->id,
                'student_id' => $customer->student_id,
                'status' => $customer->status,
                'created_at' => $customer->created_at,
                'account_number' => $customer->account_number,
                'student_name' => $customer->student_name,
                'class' => $customer->class,
                'balance' => $customer->balance,
            ],
        ], 200);
    }

    // cek riwayat mutasi transaksi nasabah
    public function mutasi(Request $request)
    {
        $customer = $request->user();

        $query = Transaction::where('customer_id', $customer->id)
            ->where('status', 'success');

        // filter tanggal jika ada
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }

        // urutkan dari transaksi terbaru
        $mutasi = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat mutasi transaksi berhasil diambil',
            'data' => $mutasi,
        ], 200);
    }

    // Cek apakah ada penarikan yang menunggu otorisasi PIN di mobile
    public function getPendingWithdrawal(Request $request)
    {
        $customer = $request->user();

        $pending = Transaction::with('user:id,staff_name')
            ->where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->where('transaction_type', 'Penarikan')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $pending,
        ], 200);
    }

    // Otorisasi penarikan oleh nasabah menggunakan PIN
    public function confirmWithdrawal(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'pin' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $customer = Customer::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($customer->status !== 'Aktif' || ! Hash::check($request->pin, $customer->pin)) {
                return response()->json(['success' => false, 'message' => 'PIN salah atau rekening tidak aktif.'], 403);
            }
            $trx = Transaction::whereKey($request->transaction_id)->where('customer_id', $customer->id)
                ->where('status', 'pending')->lockForUpdate()->first();
            if (! $trx) {
                return response()->json(['success' => false, 'message' => 'Penarikan tidak ditemukan atau sudah diproses.'], 404);
            }
            if ($customer->balance - $trx->amount < 10000) {
                $trx->update(['status' => 'cancelled']);

                return response()->json(['success' => false, 'message' => 'Saldo setelah penarikan minimal Rp10.000.'], 422);
            }
            $customer->balance -= $trx->amount;
            $customer->save();
            foreach (['111' => 'Kredit', '211' => 'Debet'] as $code => $position) {
                Accounting_Journal::create(['transaction_id' => $trx->id, 'account_code' => $code,
                    'position' => $position, 'amount' => $trx->amount]);
            }
            $trx->update(['status' => 'success']);

            return response()->json(['success' => true, 'message' => 'Penarikan berhasil diotorisasi.',
                'data' => ['transaction' => $trx, 'current_balance' => $customer->balance]]);
        });
    }

    // Tolak permintaan penarikan
    public function rejectWithdrawal(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        $updated = Transaction::whereKey($request->transaction_id)->where('customer_id', $request->user()->id)
            ->where('status', 'pending')->update(['status' => 'cancelled']);

        return response()->json(['success' => (bool) $updated,
            'message' => $updated ? 'Penarikan dibatalkan.' : 'Penarikan sudah diproses.'], $updated ? 200 : 409);
    }
}
