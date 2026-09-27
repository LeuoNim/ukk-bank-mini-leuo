<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\DailyReport;
use Carbon\Carbon;

class ReportController extends Controller
{
    //
    public function submit(Request $request) 
    {
        //validasi RBAC
        if ($request->user()-> role == 'Supervisor')
            {
                return response()->json([
                    'success'  => false,
                    'message'  => 'Akses ditolak! Supervisor tidak bertugas membuat laporan.'
                ], 403);
            }

        $today = Carbon::today();

        //Cek apakah laporan hari ini sudah dibuat
        $existing_report = DailyReport::whereDate('report_date', $today)
            ->where('teller_id', $request->user()->id)
            ->first();
        if ($existing_report) 
            {
               return response()->json([
                'success'  => false,
                'message'  => 'Laporan untuk hari ini sudah disubmit sebelumnya.'
               ], 400); 
            } 

        //hitung total transaksi hari ini
        $total_deposit = Transaction::whereDate('transaction_date', $today)->where('transaction_type', 'Setoran')->sum('amount');
        $total_withdrawal = Transaction::whereDate('transaction_date', $today)->where('transaction_type', 'Penarikan')->sum('amount');

        //Simpan ke database dengan status pending
        $report = DailyReport::create([
            'teller_id'  => $request->user()->id,
            'report_date' => $today,
            'total_deposit' => $total_deposit,
            'total_withdrawal' => $total_withdrawal,
            'approval_status'  => 'Pending'
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Laporan harian berhasil disubmit. Menunggu persetujuan Supervisor.'
        ], 201);
    }

    public function approve(Request $request)
    {
        //validasi rbac
        if ($request->user()->role !== 'Supervisor') 
            {
                return response()->json([
                    'success'  => false,
                    'message'  => 'Akses Ditolak! Hanya Supervisor yang berhak melakukan Approval.'
                ], 403);
            }
        
        $today = Carbon::today();

        //Cari laporan hari ini yang status nya masih pending
        $report = DailyReport::whereDate('report_date', $today)->first();

        //Cek kondisi laporan
        if (!$report)
            {
                return response()->json([
                    'success'  => false,
                    'message'  => 'Belum ada laporan yang disubmit oleh Teller hari ini.'
                ], 404);
            }

        if ($report->approval_status === 'Approved')
            {
                return response()->json([
                    'success'  => false,
                    'message'  => 'Laporan hari ini sudah disetujui sebelumnya.'
                ], 400);
            }
        
        //update data: catat id supervisor dan ubah status jadi approved
        $report->update([
            'supervisor_id'  => $request->user()->id,
            'approval_status' => 'Approved'
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Laporan harian berhasil diverifikasi dan disetujui',
            'data'  => $report
        ], 200);
    }

    //menampilkan daftar riwayat laporan harian
    public function daily(Request $request)
    {
        $query = DailyReport::query();

        //filter tanggal jika ada
        if ($request->has('date')) {
            $query->whereDate('report_date', $request->date);
        }

        $reports = $query->orderBy('report_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar laporan harian berhasil diambil',
            'data'    => $reports
        ], 200);
    }

    //menampilkan detail laporan harian dan transaksi hari itu
    public function show($id)
    {
        $report = DailyReport::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Data laporan tidak ditemukan'
            ], 404);
        }

        //ambil transaksi pada tanggal laporan bersangkutan
        $transactions = Transaction::whereDate('transaction_date', $report->report_date)->get();

        return response()->json([
            'success' => true,
            'message' => 'Detail laporan berhasil diambil',
            'data'    => [
                'report'       => $report,
                'transactions' => $transactions
            ]
        ], 200);
    }
}
