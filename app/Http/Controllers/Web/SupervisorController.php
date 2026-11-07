<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Accounting_Journal;
use App\Models\DailyReport;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function dashboard()
    {
        $today = date('Y-m-d');
        $total_deposit = Transaction::whereDate('transaction_date', $today)->where('transaction_type', 'Setoran')->sum('amount');
        $total_withdrawal = Transaction::whereDate('transaction_date', $today)->where('transaction_type', 'Penarikan')->sum('amount');
        $pending_reports = DailyReport::where('approval_status', 'Pending')->count();

        return view('supervisor.dashboard', compact('total_deposit', 'total_withdrawal', 'pending_reports'));
    }

    public function approval()
    {
        $reports = DailyReport::with(['teller', 'supervisor'])
            ->orderBy('report_date', 'desc')
            ->paginate(15)->withQueryString();

        return view('supervisor.approval', compact('reports'));
    }

    public function approve($id)
    {
        $report = DailyReport::findOrFail($id);

        $updated = DailyReport::whereKey($report->id)->where('approval_status', 'Pending')->update([
            'supervisor_id' => auth()->id(),
            'approval_status' => 'Approved',
        ]);
        if (! $updated) {
            return back()->with('error', 'Hanya laporan Pending yang dapat disetujui.');
        }

        return redirect()->back()->with('success', 'Laporan berhasil disetujui.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ], [
            'notes.required' => 'Alasan penolakan wajib diisi agar teller dapat memperbaiki laporan.',
        ]);

        $report = DailyReport::findOrFail($id);

        $updated = DailyReport::whereKey($report->id)->where('approval_status', 'Pending')->update([
            'supervisor_id' => auth()->id(),
            'approval_status' => 'Rejected',
            'notes' => $request->notes,
        ]);
        if (! $updated) {
            return back()->with('error', 'Hanya laporan Pending yang dapat ditolak.');
        }

        return redirect()->back()->with('success', 'Laporan kas harian ditolak. Catatan penolakan telah dikirimkan ke Teller.');
    }

    public function detailLaporan($id)
    {
        $report = DailyReport::with(['teller', 'supervisor'])->findOrFail($id);
        $transactions = Transaction::whereDate('transaction_date', $report->report_date)
            ->where('user_id', $report->teller_id)
            ->get();

        return view('supervisor.detail_laporan', compact('report', 'transactions'));
    }

    public function laporanMingguan(Request $request)
    {
        $request->validate([
            'month' => 'sometimes|integer|between:1,12',
            'year' => 'sometimes|integer|between:1900,2100',
            'week' => 'sometimes|integer|between:1,5',
        ]);
        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        // Tentukan minggu default berdasarkan tanggal hari ini jika bulan & tahun sama
        $defaultWeek = 1;
        if ($month == (int) date('n') && $year == (int) date('Y')) {
            $dayNow = (int) date('j');
            $defaultWeek = min(5, (int) ceil($dayNow / 7));
        }
        $week = (int) $request->input('week', $defaultWeek);

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        if ($week === 5 && $daysInMonth === 28) {
            return redirect()->route('supervisor.laporan_mingguan', [
                'month' => $month, 'year' => $year, 'week' => 4,
            ])->with('error', 'Februari ini hanya memiliki 28 hari. Periode terakhir adalah minggu ke-4.');
        }

        switch ($week) {
            case 1:
                $startDay = 1;
                $endDay = 7;
                break;
            case 2:
                $startDay = 8;
                $endDay = 14;
                break;
            case 3:
                $startDay = 15;
                $endDay = 21;
                break;
            case 4:
                $startDay = 22;
                $endDay = 28;
                break;
            case 5:
            default:
                $startDay = 29;
                $endDay = $daysInMonth;
                $week = 5;
                break;
        }

        $startDate = sprintf('%04d-%02d-%02d', $year, $month, $startDay);
        $endDate = sprintf('%04d-%02d-%02d', $year, $month, min($endDay, $daysInMonth));

        $query = Transaction::with(['customer', 'user'])
            ->where('status', 'success')
            ->whereBetween('transaction_date', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ]);

        $allTransactions = (clone $query)->get();

        $total_deposit = $allTransactions->where('transaction_type', 'Setoran')->sum('amount');
        $total_withdrawal = $allTransactions->where('transaction_type', 'Penarikan')->sum('amount');
        $net_cash = $total_deposit - $total_withdrawal;

        // Rekap ringkasan per tanggal dalam minggu tersebut
        $dailySummary = $allTransactions->groupBy(function ($item) {
            return Carbon::parse($item->transaction_date)->format('Y-m-d');
        })->map(function ($group) {
            $deposit = $group->where('transaction_type', 'Setoran')->sum('amount');
            $withdrawal = $group->where('transaction_type', 'Penarikan')->sum('amount');

            return [
                'count' => $group->count(),
                'deposit' => $deposit,
                'withdrawal' => $withdrawal,
                'net' => $deposit - $withdrawal,
            ];
        });

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(15)->withQueryString();

        return view('supervisor.laporan_mingguan', compact(
            'transactions',
            'month',
            'year',
            'week',
            'daysInMonth',
            'startDate',
            'endDate',
            'total_deposit',
            'total_withdrawal',
            'net_cash',
            'dailySummary'
        ));
    }

    public function laporanBulanan(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        $query = Transaction::with(['customer', 'user'])
            ->where('status', 'success')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month);

        $allTransactions = (clone $query)->get();

        $total_deposit = $allTransactions->where('transaction_type', 'Setoran')->sum('amount');
        $total_withdrawal = $allTransactions->where('transaction_type', 'Penarikan')->sum('amount');
        $net_cash = $total_deposit - $total_withdrawal;

        // Rekap ringkasan per tanggal dalam bulan tersebut
        $dailySummary = $allTransactions->groupBy(function ($item) {
            return Carbon::parse($item->transaction_date)->format('Y-m-d');
        })->map(function ($group) {
            $deposit = $group->where('transaction_type', 'Setoran')->sum('amount');
            $withdrawal = $group->where('transaction_type', 'Penarikan')->sum('amount');

            return [
                'count' => $group->count(),
                'deposit' => $deposit,
                'withdrawal' => $withdrawal,
                'net' => $deposit - $withdrawal,
            ];
        });

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(15)->withQueryString();

        return view('supervisor.laporan_bulanan', compact('transactions', 'month', 'year', 'total_deposit', 'total_withdrawal', 'net_cash', 'dailySummary'));
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

        return view('supervisor.audit', compact('journals', 'total_debet', 'total_kredit', 'tellers'));
    }
}
