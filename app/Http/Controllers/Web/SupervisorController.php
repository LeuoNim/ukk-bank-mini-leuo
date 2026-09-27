<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function dashboard()
    {
        $today = date('Y-m-d');
        $total_deposit = \App\Models\Transaction::whereDate('transaction_date', $today)->where('transaction_type', 'Setoran')->sum('amount');
        $total_withdrawal = \App\Models\Transaction::whereDate('transaction_date', $today)->where('transaction_type', 'Penarikan')->sum('amount');
        $pending_reports = \App\Models\DailyReport::where('approval_status', 'Pending')->count();

        return view('supervisor.dashboard', compact('total_deposit', 'total_withdrawal', 'pending_reports'));
    }

    public function approval()
    {
        $reports = \App\Models\DailyReport::with(['teller', 'supervisor'])
                    ->orderBy('report_date', 'desc')
                    ->get();
        return view('supervisor.approval', compact('reports'));
    }

    public function approve($id)
    {
        $report = \App\Models\DailyReport::findOrFail($id);
        
        if ($report->approval_status === 'Approved') {
            return redirect()->back()->with('error', 'Laporan sudah disetujui sebelumnya.');
        }

        $report->update([
            'supervisor_id'  => auth()->id(),
            'approval_status' => 'Approved'
        ]);

        return redirect()->back()->with('success', 'Laporan berhasil disetujui.');
    }

    public function detailLaporan($id)
    {
        $report = \App\Models\DailyReport::with(['teller', 'supervisor'])->findOrFail($id);
        $transactions = \App\Models\Transaction::whereDate('transaction_date', $report->report_date)
                            ->where('user_id', $report->teller_id)
                            ->get();
                            
        return view('supervisor.detail_laporan', compact('report', 'transactions'));
    }

    public function audit()
    {
        $journals = \App\Models\Accounting_Journal::with(['transaction.customer', 'transaction.user'])
                    ->orderBy('id', 'desc')
                    ->get();
        $total_debet = \App\Models\Accounting_Journal::where('position', 'Debet')->sum('amount');
        $total_kredit = \App\Models\Accounting_Journal::where('position', 'Kredit')->sum('amount');
        
        return view('supervisor.audit', compact('journals', 'total_debet', 'total_kredit'));
    }
}
