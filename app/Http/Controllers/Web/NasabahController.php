<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class NasabahController extends Controller
{
    public function updateSecurity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'current_pin' => 'required|digits_between:4,6',
            'new_password' => 'required|string|min:6|confirmed',
            'new_pin' => 'required|digits:6|confirmed',
        ]);

        DB::transaction(function () use ($data): void {
            $customer = Customer::whereKey(auth('nasabah')->id())->lockForUpdate()->firstOrFail();
            if ($customer->status !== 'Aktif' || ! Hash::check($data['current_password'], $customer->getAuthPassword())
                || ! Hash::check($data['current_pin'], $customer->pin)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Password atau PIN saat ini salah. Keduanya belum diubah.',
                ]);
            }

            $customer->password = Hash::make($data['new_password']);
            $customer->pin = Hash::make($data['new_pin']);
            $customer->save();
            $customer->tokens()->delete();
        });

        auth('nasabah')->user()->refresh();
        $request->session()->regenerate();

        return redirect()->route('nasabah.profil')->with('success_password', 'Password dan PIN berhasil diubah bersama. Gunakan password baru saat login kembali di aplikasi mobile.');
    }

    public function dashboard()
    {
        $customer = auth('nasabah')->user();

        return view('nasabah.dashboard', compact('customer'));
    }

    public function mutasi(Request $request)
    {
        $customer = auth('nasabah')->user();
        $query = Transaction::where('customer_id', $customer->id)
            ->where('status', 'success');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('transaction_date', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        } elseif ($request->filled('date')) {
            $query->whereDate('transaction_date', $request->date);
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('nasabah.mutasi', compact('customer', 'transactions'));
    }

    public function profil()
    {
        $customer = auth('nasabah')->user();

        return view('nasabah.profil', compact('customer'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_pin' => 'required|digits_between:4,6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $customer = Customer::findOrFail(auth('nasabah')->id());

        // Verifikasi identitas menggunakan PIN transaksi
        if (! Hash::check($request->current_pin, $customer->pin)) {
            return back()->withErrors(['current_pin' => 'PIN transaksi yang dimasukkan salah.']);
        }

        $customer->password = Hash::make($request->new_password);
        $customer->save();
        $customer->tokens()->delete();
        auth('nasabah')->setUser($customer);

        return back()->with('success_password', 'Password akun nasabah berhasil diubah.');
    }

    public function updatePin(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_pin' => 'required|numeric|digits:6|confirmed',
        ]);

        $customer = Customer::findOrFail(auth('nasabah')->id());

        // Verifikasi identitas: jika punya password gunakan password, jika belum gunakan PIN lama
        $valid = false;
        if (! empty($customer->password)) {
            $valid = Hash::check($request->current_password, $customer->password);
        } else {
            $valid = Hash::check($request->current_password, $customer->pin);
        }

        if (! $valid) {
            return back()->withErrors(['current_password' => 'Password saat ini yang dimasukkan salah.']);
        }

        $customer->pin = Hash::make($request->new_pin);
        $customer->save();
        $customer->tokens()->delete();
        auth('nasabah')->setUser($customer);

        return back()->with('success_pin', 'PIN transaksi berhasil diperbarui.');
    }
}
