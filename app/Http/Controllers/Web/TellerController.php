<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TellerController extends Controller
{
    public function transaksi(Request $request)
    {
        $customer = null;
        if ($request->search) {
            $customer = \App\Models\Customer::where('student_id', $request->search)
                        ->orWhere('account_number', $request->search)
                        ->first();
        }
        return view('teller.transaksi', compact('customer'));
    }

    public function riwayat(Request $request)
    {
        $query = \App\Models\Transaction::with('customer')
                ->where('user_id', auth()->id());
                
        if ($request->date) {
            $query->whereDate('transaction_date', $request->date);
        } else {
            $query->whereDate('transaction_date', date('Y-m-d'));
        }

        if ($request->type) {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->orderBy('id', 'desc')->get();
        return view('teller.riwayat', compact('transactions'));
    }

    public function laporan()
    {
        $today = date('Y-m-d');
        
        $total_deposit = \App\Models\Transaction::where('user_id', auth()->id())
                        ->whereDate('transaction_date', $today)
                        ->where('transaction_type', 'Setoran')
                        ->sum('amount');
                        
        $total_withdrawal = \App\Models\Transaction::where('user_id', auth()->id())
                        ->whereDate('transaction_date', $today)
                        ->where('transaction_type', 'Penarikan')
                        ->sum('amount');

        $report_today = \App\Models\DailyReport::where('teller_id', auth()->id())
                        ->whereDate('report_date', $today)
                        ->first();

        $reports = \App\Models\DailyReport::where('teller_id', auth()->id())
                        ->whereDate('report_date', '<', $today)
                        ->orderBy('report_date', 'desc')
                        ->take(10)
                        ->get();

        return view('teller.laporan', compact('total_deposit', 'total_withdrawal', 'report_today', 'reports'));
    }

    public function deposit(Request $request)
    {
        // Panggil logika dari TransactionController API atau implementasi ulang
        app(\App\Http\Controllers\Api\TransactionController::class)->deposit($request);
        return redirect()->back()->with('success', 'Setoran tunai berhasil diproses.');
    }

    public function withdraw(Request $request)
    {
        $response = app(\App\Http\Controllers\Api\TransactionController::class)->withdraw($request);
        $data = json_decode($response->getContent());
        if (!$data->success) {
            return redirect()->back()->with('error', $data->message);
        }
        return redirect()->back()->with('success', 'Penarikan tunai berhasil diproses.');
    }

    public function submitLaporan(Request $request)
    {
        $response = app(\App\Http\Controllers\Api\ReportController::class)->submit($request);
        $data = json_decode($response->getContent());
        if (!$data->success) {
            return redirect()->back()->with('error', $data->message);
        }
        return redirect()->back()->with('success', 'Laporan hari ini berhasil dikirim ke Supervisor.');
    }
}
