<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Accounting_Journal;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    //menampilkan audit jurnal akuntansi
    public function index(Request $request)
    {
        $query = Accounting_Journal::with(['transaction.customer', 'transaction.user']);

        //filter tanggal transaksi jika ada
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->whereBetween('transaction_date', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            });
        }

        //filter kode akun jika ada
        if ($request->has('account_code')) {
            $query->where('account_code', $request->account_code);
        }

        //filter posisi debet atau kredit jika ada
        if ($request->has('position')) {
            $query->where('position', $request->position);
        }

        $journals = $query->orderBy('id', 'desc')->get();

        //hitung total debet dan kredit
        $totalDebet = Accounting_Journal::where('position', 'Debet')->sum('amount');
        $totalKredit = Accounting_Journal::where('position', 'Kredit')->sum('amount');

        return response()->json([
            'success' => true,
            'message' => 'Data jurnal akuntansi berhasil diambil',
            'summary' => [
                'total_debet'  => (float) $totalDebet,
                'total_kredit' => (float) $totalKredit,
                'is_balanced'  => (float) $totalDebet === (float) $totalKredit
            ],
            'data'    => $journals
        ], 200);
    }

    //menampilkan detail satu data jurnal
    public function show($id)
    {
        $journal = Accounting_Journal::with(['transaction.customer', 'transaction.user'])->find($id);

        if (!$journal) {
            return response()->json([
                'success' => false,
                'message' => 'Data jurnal tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail jurnal akuntansi berhasil diambil',
            'data'    => $journal
        ], 200);
    }
}
