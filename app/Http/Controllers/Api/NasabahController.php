<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class NasabahController extends Controller
{
    //cek saldo nasabah
    public function saldo(Request $request)
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Informasi saldo berhasil diambil',
            'data'    => [
                'account_number' => $customer->account_number,
                'student_name'   => $customer->student_name,
                'class'          => $customer->class,
                'balance'        => $customer->balance
            ]
        ], 200);
    }

    //cek riwayat mutasi transaksi nasabah
    public function mutasi(Request $request)
    {
        $customer = $request->user();

        $query = Transaction::where('customer_id', $customer->id);

        //filter tanggal jika ada
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        //urutkan dari transaksi terbaru
        $mutasi = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat mutasi transaksi berhasil diambil',
            'data'    => $mutasi
        ], 200);
    }
}
