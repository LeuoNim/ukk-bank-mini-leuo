<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Accounting_Journal;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerLifecycleController extends Controller
{
    public function activate(Request $request, Customer $customer): Response
    {
        $data = $request->validate([
            'identity_verified' => 'required|accepted',
            'verification_note' => 'required|string|min:10|max:300',
            'cash_received' => 'required|accepted',
            'amount' => 'required|integer|min:10000|max:100000000|multiple_of:1000',
        ]);

        $password = Str::random(12);
        $pin = (string) random_int(100000, 999999);
        $transaction = DB::transaction(function () use ($customer, $data, $password, $pin): Transaction {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if ($customer->status !== Customer::STATUS_PENDING || $customer->balance != 0 || $customer->transactions()->exists()) {
                throw ValidationException::withMessages(['amount' => 'Rekening tidak dalam kondisi belum aktivasi atau sudah memiliki transaksi. Aktivasi tidak boleh diulang.']);
            }
            $transaction = Transaction::create([
                'transaction_code' => 'DEP-'.Str::ulid(),
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'transaction_date' => now(),
                'transaction_type' => 'Setoran',
                'amount' => $data['amount'],
                'description' => 'Setoran awal saat aktivasi rekening',
                'status' => 'success',
            ]);
            foreach (['111' => 'Debet', '211' => 'Kredit'] as $code => $position) {
                Accounting_Journal::create(['transaction_id' => $transaction->id, 'account_code' => $code,
                    'position' => $position, 'amount' => $data['amount']]);
            }
            $customer->update(['status' => 'Aktif', 'balance' => $data['amount'],
                'password' => Hash::make($password), 'pin' => Hash::make($pin)]);
            $customer->tokens()->delete();

            return $transaction;
        });
        Log::notice('customer.activated', ['customer_id' => $customer->id, 'actor_id' => auth()->id(),
            'transaction_id' => $transaction->id, 'verification' => $data['verification_note']]);

        return response()->view('teller.credentials', compact('customer', 'password', 'pin', 'transaction'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function resetCredentials(Request $request, Customer $customer): Response
    {
        $data = $request->validate([
            'identity_verified' => 'required|accepted',
            'verification_note' => 'required|string|min:10|max:300',
            'reset_type' => 'required|in:password,pin,both',
        ]);
        $password = $data['reset_type'] !== 'pin' ? Str::random(12) : null;
        $pin = $data['reset_type'] !== 'password' ? (string) random_int(100000, 999999) : null;
        DB::transaction(function () use ($customer, $password, $pin): void {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if ($customer->status !== 'Aktif') {
                throw ValidationException::withMessages(['reset_type' => 'Hanya rekening aktif yang dapat dipulihkan. Selesaikan aktivasi atau pembukaan blokir terlebih dahulu.']);
            }
            if ($password !== null) {
                $customer->password = Hash::make($password);
            }
            if ($pin !== null) {
                $customer->pin = Hash::make($pin);
            }
            $customer->save();
            $customer->tokens()->delete();
        });
        Log::notice('customer.credentials_reset', ['customer_id' => $customer->id, 'actor_id' => auth()->id(),
            'type' => $data['reset_type'], 'verification' => $data['verification_note']]);

        return response()->view('teller.credentials', compact('customer', 'password', 'pin'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function markPending(Customer $customer): RedirectResponse
    {
        DB::transaction(function () use ($customer): void {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if ($customer->balance != 0 || $customer->transactions()->exists()) {
                throw ValidationException::withMessages(['customer' => 'Rekening bersaldo atau memiliki riwayat transaksi tidak boleh dikembalikan menjadi belum aktivasi.']);
            }
            $customer->update(['status' => Customer::STATUS_PENDING]);
            $customer->tokens()->delete();
        });
        Log::notice('customer.marked_pending', ['customer_id' => $customer->id, 'actor_id' => auth()->id()]);

        return back()->with('success', 'Rekening ditandai nonaktif — belum aktivasi. Saldo tetap Rp0.');
    }
}
