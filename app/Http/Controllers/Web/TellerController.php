<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Controller;
use App\Models\Accounting_Journal;
use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TellerController extends Controller
{
    public function transaksi(Request $request)
    {
        $customer = null;

        // Ambil nasabah jika sudah terkunci di session
        if (session()->has('locked_customer_id')) {
            $customer = Customer::find(session('locked_customer_id'));
        }

        // Jika belum terkunci dan ada input pencarian atau hasil scan QR
        if (! $customer && $request->filled('search')) {
            $search = trim($request->search);
            $customer = Customer::where('student_id', $search)
                ->orWhere('account_number', $search)
                ->first();

            // Kunci nasabah ke dalam sesi jika ditemukan
            if ($customer) {
                session(['locked_customer_id' => $customer->id]);
            }
        }

        $isLocked = ! empty($customer);

        $pendingWithdrawal = null;
        if ($customer) {
            $pendingWithdrawal = Transaction::where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->latest()
                ->first();
        }

        return view('teller.transaksi', compact('customer', 'isLocked', 'pendingWithdrawal'));
    }

    public function unlockCustomer()
    {
        session()->forget('locked_customer_id');

        return redirect()->route('teller.transaksi');
    }

    public function riwayat(Request $request)
    {
        $query = Transaction::with('customer')
            ->where('user_id', auth()->id());

        if ($request->date) {
            $query->whereDate('transaction_date', $request->date);
        } else {
            $query->whereDate('transaction_date', date('Y-m-d'));
        }

        if ($request->type) {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('teller.riwayat', compact('transactions'));
    }

    public function laporan()
    {
        $today = date('Y-m-d');

        $report_today = DailyReport::where('teller_id', auth()->id())
            ->whereDate('report_date', $today)
            ->first();

        if ($report_today) {
            $total_deposit = $report_today->total_deposit;
            $total_withdrawal = $report_today->total_withdrawal;
        } else {
            $total_deposit = Transaction::where('user_id', auth()->id())
                ->whereDate('transaction_date', $today)
                ->where('transaction_type', 'Setoran')
                ->where('status', 'success')
                ->sum('amount');

            $total_withdrawal = Transaction::where('user_id', auth()->id())
                ->whereDate('transaction_date', $today)
                ->where('transaction_type', 'Penarikan')
                ->where('status', 'success')
                ->sum('amount');
        }

        $reports = DailyReport::where('teller_id', auth()->id())
            ->whereDate('report_date', '<', $today)
            ->orderBy('report_date', 'desc')
            ->take(10)
            ->get();

        // Saldo Awal (sebelum hari ini)
        // Hitung total debet kas - total kredit kas sebelum hari ini
        $debet_sebelumnya = Accounting_Journal::where('account_code', '111')
            ->where('position', 'Debet')
            ->whereHas('transaction', function ($q) use ($today) {
                $q->whereDate('transaction_date', '<', $today);
            })->sum('amount');

        $kredit_sebelumnya = Accounting_Journal::where('account_code', '111')
            ->where('position', 'Kredit')
            ->whereHas('transaction', function ($q) use ($today) {
                $q->whereDate('transaction_date', '<', $today);
            })->sum('amount');

        $initial_balance = $debet_sebelumnya - $kredit_sebelumnya;
        $final_balance = $initial_balance + $total_deposit - $total_withdrawal;

        return view('teller.laporan', compact('total_deposit', 'total_withdrawal', 'report_today', 'reports', 'initial_balance', 'final_balance'));
    }

    public function deposit(Request $request)
    {
        try {
            $response = app(TransactionController::class)->deposit($request);
            $data = json_decode($response->getContent());
            if (! isset($data->success) || ! $data->success) {
                return redirect()->back()->with('error', $data->message ?? 'Setoran gagal diproses.');
            }
            $trxId = $data->data->id ?? null;

            return redirect()->back()
                ->with('success', 'Setoran tunai berhasil diproses.')
                ->with('last_transaction_id', $trxId);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function withdraw(Request $request)
    {
        try {
            $response = app(TransactionController::class)->withdraw($request);
            $data = json_decode($response->getContent());
            if (! isset($data->success) || ! $data->success) {
                return redirect()->back()->with('error', $data->message ?? 'Penarikan gagal diproses.');
            }
            $trxId = $data->data->id ?? null;
            $status = $data->status ?? 'success';

            if ($status === 'pending') {
                return redirect()->back()
                    ->with('pending_info', $data->message)
                    ->with('pending_transaction_id', $trxId);
            }

            return redirect()->back()
                ->with('success', 'Penarikan tunai berhasil diproses.')
                ->with('last_transaction_id', $trxId);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancelWithdrawal($id)
    {
        $updated = Transaction::where('id', $id)->where('user_id', auth()->id())
            ->where('status', 'pending')->update(['status' => 'cancelled']);
        if ($updated) {

            return redirect()->back()->with('success', 'Permintaan penarikan berhasil dibatalkan.');
        }

        return redirect()->back()->with('error', 'Transaksi tidak ditemukan atau sudah diproses.');
    }

    public function submitLaporan(Request $request)
    {
        $response = app(ReportController::class)->submit($request);
        $data = json_decode($response->getContent());
        if (! $data->success) {
            return redirect()->back()->with('error', $data->message);
        }

        return redirect()->back()->with('success', 'Laporan hari ini berhasil dikirim ke Supervisor.');
    }

    public function cetakStruk($id)
    {
        $transaction = Transaction::with(['customer', 'user'])->findOrFail($id);

        // Pastikan hanya bisa mencetak transaksi miliknya sendiri
        if ($transaction->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        return view('teller.cetak_struk', compact('transaction'));
    }
}
